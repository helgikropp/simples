<?php
namespace Application\Model;

use SP\Debug\Debug as SP_Debug;

class AbstractWrapper extends \SP\Model\SoapModel {

    /** @var \Auth\Model\Sp\User $_user */
    protected $_user = null;
    protected $_tmpSessionId = '';
    protected $_cfg = null;

    public const UT_INNER = 0;
    public const UT_AUTHORIZED = 1;
    public const UT_ANY = 2;

    /** ========================================================================
     * @param $modelName
     * @return mixed
     */
    final public function model($modelName) { return $this->_serviceManager->get(strtolower($modelName) . 'Wrapper'); }

    /** ========================================================================
     * @return string
     */
    public function getSessionId(): string { return $this->_tmpSessionId; }

    /** ========================================================================
     * @return array
     */
    protected function _getRequestCommonFields() {
        $opt = $this->_getConfig('core_options');
        return [
                'appGuid' => $opt['app_guid'],
                'appVersion' => $opt['app_version'],
                'sid' => $this->getSessionId(),
                'filter' => null,
                'request' => '',
                'commandTimeout' => 1200
            ];
    }

    /** ========================================================================
     * @return array|object
     */
    protected function _getCloudOptions() { return $this->_getConfig('cloud'); }

    /** ========================================================================
     * @return \Auth\Model\Sp\User
     */
    protected function _getUser() {
        $this->_user = $this->getServiceLocator()->get('authService')->getIdentity();
        $this->_tmpSessionId = $this->_user ? $this->_user->sessionId : '';
        return $this->_user;
    }


    /** ========================================================================
     *
     * @param int $userType
     * @return array
     */
    protected function _getUserCustom(int $userType): array
    {
        if (!$this->_getCoreClient()) {
            return [
                'state' => ['code' => 'RC_FAIL', 'msg' => 'ERR_SOAP_MISSED'],
                'data' => 'class: ' . __CLASS__ . ', line: ' . __LINE__
            ];
        }

        switch ($userType) {
            case self::UT_INNER:
                $user = $this->_getPassport();
                break;
            case self::UT_AUTHORIZED:
                $user = $this->_getUser();
                break;
            case self::UT_ANY:
                $user = $this->_getUser();
                if (!$user) { $user = $this->_getPassport(); }
                break;
            default :
                $user = null;
                $this->_tmpSessionId = '';
                return [
                    'state' => ['code' => 'RC_FAIL', 'msg' => 'ERR_WRONG_USER_T'],
                    'data' => 'userType: ' . $userType . ', class: ' . __CLASS__ . ', line: ' . __LINE__
                ];
        }
        if (!$user) {
            $this->_tmpSessionId = '';
            return [
                'state' => ['code' => 'RC_FAIL', 'msg' => 'ERR_FORBIDDEN'],
                'data' => 'class: ' . __CLASS__ . ', line: ' . __LINE__
            ];
        }

        $this->_tmpSessionId = $user->sessionId;
        return [
            'state' => ['code' => 'RC_OK', 'msg' => 'SUCCESSFULLY'],
            'data' => $user
        ];
    }

    /** ========================================================================
     * @return array | bool
     */
    protected function _getUserInner() { return $this->_getUserCustom(self::UT_INNER); }

    /** ========================================================================
     *
     * @param string $operPath
     * @param string $operName
     * @param string $data
     * @return array
     */
    protected function _prepareK2ProcessInstance($operPath, $operName, $data) {
        return [ 
            'Folio' => $operName . '_' . time(),
            'FullName' => $operPath,
            'DataField' => [
                [
                    'Name' => 'Data',
                    'Value' => '<InvokeRequest'
                    . ' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'
                    . ' xmlns:xsd="http://www.w3.org/2001/XMLSchema">'
                    . '<methodArgs>'
                    . '<lang xsi:type="xsd:string">' . $this->getLng() . '</lang>'
                    . $data
                    . '</methodArgs>'
                    . '</InvokeRequest>'
                ]
            ]
        ];
    }

    /**
     * Undocumented function
     *
     * @param string $xml
     * @return void
     */
    protected function _prepareOmniTrackerRequest(string $xml = '') {
        return [
            'invokeScriptParameters' => [
                'Parameters' => [
                    'Items' => [
                        [
                            '$type' => 'StringVal',
                            'name' => 'xml',
                            'Value' => $xml
                        ]
                    ]
                ],
                'name' => 'G_AddNewRequest'
            ]
        ];
    }
}
