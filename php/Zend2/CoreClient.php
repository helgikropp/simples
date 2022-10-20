<?php
namespace SP\Soap;

use SP\Debug\Debug as SP_Debug;
use SP\Utils\Lib;

class CoreClient extends AbstractClient
{

    /**
     * Формирует XML-структуру из упорядоченного массива параметров
     * @param array $args
     * @return mixed
     * params - массив типа
     *    [
     *      [ 'type' => /string, bool,.../,  'value' => /value/ ],
     *      .....
     *    ]
     */
    protected function _createMethodArgs(array $args) {
        if(isset($args['request']) && is_array($args['request'])) {
            $argsStr = '';
            foreach($args['request'] as $arg) {
                $nodeName = $arg['node'] ?? 'anyType';

                if($arg['value'] === NULL || $arg['type'] === NULL) {
                    $argsStr .= '<' . $nodeName . ' xsi:nil="true" />';
                } else {
                    $type = (strpos($arg['type'], 'xsd:') !== 0 ? 'xsd:' : '') . $arg['type'];
                    $typeAttr = ($type !== 'xsd:' && $type !== 'xsd:xml')
                                ? ' xsi:type="' . $type . '"'
                                : '';

                    $val  = (strpos($arg['value'], '<![CDATA[<Files') !== 0)
                                ? Lib::sanitizeXmlValue($arg['value'])
                                : $arg['value'];
                    $argsStr .= '<' . $nodeName . $typeAttr . '>' . $val . '</' . $nodeName . '>';
                }
            }
            $args['request'] = '<InvokeRequest'
                . ' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'
                . ' xmlns:xsd="http://www.w3.org/2001/XMLSchema">'
                . '<methodArgs>' . $argsStr . '</methodArgs>'
                . '</InvokeRequest>';
        } else if(isset($args['request_outher'])) { //for Logon action (without 'request' node)
            $args = $args['request_outher'];
        } else {
            if (isset($args['request_raw'])) {
                $args['request'] = $args['request_raw'];
                unset($args['request_raw']);
            }
            if (isset($args['filter_raw'])) {
                $args['filter'] = $args['filter_raw'];
                unset($args['filter_raw']);
            }
        }

        return $args;
    }

    public function exec($params): array
    {
        $params['request'] = $this->_createMethodArgs(is_array($params['request'])?$params['request']:[]);
        $this->_lastError = false;
        $action = $params['action'];

        $data = null;

        if($this->_call($action, $params['request'])) {
            $this->_lastError = $this->_getProxyError();
            if(strpos($action, 'K2') === 0) {
                $data = $this->_responseArr;
            } else {
                if($action === 'Execute' && !$this->_lastError) {
                    $this->_lastError = $this->_getCoreError();
                }
                if($action === 'Execute' && !$this->_lastError) {
                    if($this->_newFormat) {
                        $data = $this->_farResponseXML->Data->children()->asXML();
                    } elseif(isset($this->_farResponseXML->resultData)) {
                        $data = trim($this->_farResponseXML->resultData);
                    } elseif(isset($this->_responseArr['dataSet']['Table'])) {
                        $data = $this->_responseArr['dataSet']['Table'];
                    }
                }
            }
        }

        if(!empty($this->_responseArr['dataSet'])) {
            $this->_responseArr = $this->_responseArr['dataSet'];
        }

        $ret = [
            'state' => $this->_lastError ?: ['code' => 'RC_OK', 'msg' => ''], 
            'data'  => $data !== null
                ? ( $this->_newFormat || is_array($data)
                    ? $data
                    : htmlspecialchars_decode($data, ENT_NOQUOTES) )
                : ($this->_lastError ? '' : $this->_responseArr),
            'dbg' => [
                'request'  => $this->getLastRequest(),
                'response' => $this->getLastResponse()
            ]
        ];

        if(!in_array($ret['state']['code'], Lib::getSoftErrors())) {
            SP_Debug::mail($this->getServiceLocator(),
                'SOAP FAIL',
                "ACTION :\n\n".$action
                . "\n\n\n\nREQUEST JSON :\n\n".var_export(str_replace("\t","  ",$this->_requestJSON),true)
                . "\n\n\n\nRESPONSE JSON :\n\n". var_export(str_replace("\t","  ",$this->_responseJSON),true)
                . "\n\n\n\nRESPONSE XML :\n\n" . var_export($this->_farResponseXML, true)
                . "\n\n\n\nRESPONSE REC :\n\n" . var_export($ret, true));
        }

        return $ret;
    }
}
