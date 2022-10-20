<?php /** @noinspection MagicMethodsValidityInspection */

namespace SP\Soap;

use SP\Debug\Debug as SP_Debug;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class AbstractClient implements ServiceLocatorAwareInterface
{
    /** @var \Zend\ServiceManager\ServiceManager $_serviceManager */
    protected $_serviceManager;

    protected const CODES = [
        0 => 'OK',                   // Нет ошибки.
        1 => 'COMMON_ERROR',         // Общая ошибка (вне классификации).
        2 => 'INTERNAL_ERROR',       // Внутренняя ошибка (никогда не должна происходить).
        3 => 'CANCELLED',            // Операция прервана.
        4 => 'OBJECT_NOT_FOUND',     // Объект не найден.
        5 => 'NETWORK_ERROR',        // Ошибка сети.
        6 => 'SYSTEM_ERROR',         // Системная ошибка.
        7 => 'INITIALIZATION_ERROR', // Ошибка инициализации.
        8 => 'SERVICE_ERROR',        // Ошибка сервиса.

        // Ошибки Service (100 - 199)
        100 => 'RELOGON_REQUIRED',   // Требуется перелогон.
        101 => 'LOGIN_INCORRECT',    // Ошибка ввода логина или пароля.
        102 => 'BAD_INPUT_DT',     // Неверные входные данные.
        103 => 'ACCESS_DENIED',      // Доступ запрещен.
        104 => 'RESOURCE_NOT_FOUND', // Запрашиваемый ресурс не найден.
        105 => 'BAD_RQ',        // Неверный запрос.
        106 => 'LOGIN_EXPIRATIONDATE', // Просрочен пароль
        107 => 'ACCOUNT_LOCKED', // Учетка заблокирована

        // Ошибки Proxy (200 - 299)
        200 => 'EMPTY_RESULT_DATA', // Пустые данные в ответе сервиса.
        201 => 'BAD_RESULT_DATA',   // Ошибочные данные в ответе сервиса.

        // Ошибки Client (300 - 399)
        300 => 'INTERNAL_MODULE_ERROR', // Внутренняя ошибка модуля.

        // Ошибки модулей (1000 - 1099)
        1000 => 'LOAD_DATA_ERROR', // Ошибка загрузки данных.

        // Свободный диапазон: 1110 - 65535

        // AutoOrders (11000 - 11099)        1100 => 'RIGHT_NECESSARILY', //  Не найдено обязательное право
        1101 => 'RIGHT_ERROR',       // Ошибка обработки прав
        1120 => 'LOGON_ERROR'        // Ошибка подключения к авторизации
    ];

    protected $_url = '';

    protected $_newFormat = false;

    protected $_requestJSON = '';
    protected $_responseJSON = '';
    protected $_farResponseXML = '';

    protected $_responseArr = [];

    protected $_lastError = false;
    protected $_lastErrorOrigin = ''; //TODO

    public function __construct($url) {
        $this->_url = $url . (substr($this->_url, -1) !== '/' ? '/' : '');
    }

    public function setServiceLocator(ServiceLocatorInterface $serviceLocator) {
        $this->_serviceManager = $serviceLocator;
        return $this;
    }

    public function rc2err($code) {
        return (strpos($code, 'RC_') === 0 ? str_replace('RC_', 'ERR_', $code) : $code);
    }
    public function getServiceLocator() { return $this->_serviceManager;  }
    public function getErrorOrigin(): string { return $this->_lastErrorOrigin; }

    protected function _getErrorCode($errNum): string
    {
        if(isset(self::CODES[$errNum])) {
            $this->_lastErrorOrigin = 'CORE';
            $code = self::CODES[$errNum];
            return !empty($code)
                ? (strpos($code, 'RC_') !== 0 ? 'RC_' : '') . $code
                : '';
        }
        $this->_lastErrorOrigin = '';
        return 'RC_UNKNOWN';
    }

    protected function _call($action,  $params): bool
    {
        $this->_farResponseXML = '';
        $this->_responseArr    = [];

        $fullAction = $this->_url . $action;

        $this->_requestJSON = is_array($params) && count($params)
                ? str_replace(['[]'],['{}'],json_encode($params/*,JSON_UNESCAPED_UNICODE*/))
                : '{}';

        $dataLength = strlen($this->_requestJSON);

        $proxy = curl_init($fullAction);
        curl_setopt($proxy, CURLOPT_POST, true); // set POST method
        curl_setopt($proxy, CURLOPT_POSTFIELDS, $this->_requestJSON);
        curl_setopt($proxy, CURLOPT_RETURNTRANSFER,true); // return into a variable
        curl_setopt($proxy, CURLOPT_HTTPHEADER,
                    ['Content-Type:application/json',
                            'charset=utf-8',
                            'Content-length: '.$dataLength]);
        curl_setopt($proxy, CURLOPT_FAILONERROR, true);
        curl_setopt($proxy, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($proxy, CURLINFO_HEADER_OUT, true);

        $this->_responseJSON = curl_exec($proxy); // run the whole process

        if($this->_responseJSON === false && $action !== 'Logoff') {
            $this->_lastError = ['code' => 'RC_FORBIDDEN', 'msg'  => curl_error($proxy), 'origin' => 'NETWORK'];
            SP_Debug::mail($this->_serviceManager, 'debug SOAP fail log '.$dataLength, curl_getinfo($proxy));
            SP_Debug::mail($this->getServiceLocator(), 'debug SOAP call fault '.$dataLength, $this->_lastError);
            $this->_fullDbgMail('SOAP FAULT', $fullAction, $params);
            curl_close($proxy);
            return false;
        }

        curl_close($proxy);

        $this->_responseArr = json_decode( $this->_responseJSON, true );
        $this->_newFormat = isset($this->_responseArr['dataSet']['Table'][0]['Result']);

        if(empty($this->_responseArr)) {
            $this->_fullDbgMail('debug SOAP response fault', $fullAction, $params);
            return false;
        }

        return true;
    }

    protected function _getCoreError() {
        $table = isset($this->_responseArr['dataSet']['Table1']) ? 'Table1' : 'Table';

        $resStr = $this->_newFormat
                ? '<?xml version="1.0" encoding="utf-8" ?>'.$this->_responseArr['dataSet'][$table][0]['Result']
                : ( $this->_responseArr['dataSet'][$table][0]['ScalarData'] ?? '' );

        $this->_farResponseXML = simplexml_load_string($resStr);
        $frXml = &$this->_farResponseXML;
        $innerMsg = $msg = $code = '';
        if($this->_newFormat) {
            $code = trim($frXml->Code);
            $msg  = trim($frXml->Msg);
        } elseif(isset($frXml->exception)) {
            $msg = trim($frXml->exception);
        } elseif(isset($frXml->exceptionsList)
            && $frXml->exceptionsList->children()->count()
            && $frXml->exceptionsList->SerializableError->children()->count())
        {
            $sErr = &$frXml->exceptionsList->SerializableError[0];
            if(isset($sErr->InnerExceptionMessage)) { $innerMsg = trim($sErr->InnerExceptionMessage); }
            if(isset($sErr->Message)) { $msg = trim($sErr->Message); }
            if(strpos($innerMsg, 'RC_') === 0) {
                $code = $innerMsg;
                $msg = '';
            } elseif (strpos($msg, 'RC_') === 0) {
                $code = $msg;
                $msg = '';
            } elseif (empty($msg)) {
                $msg = $innerMsg;
            }
        }

        if(!empty($code) || (!$this->_newFormat && !empty($msg))) {
            return [
                'code'   => strpos($code, 'RC_') === 0 ? $code : 'RC_ERROR',
                'msg'    => $msg??'',
                'origin' => ''
            ];
        }
        return false;
    }

    protected function _getProxyError() {
        $resCode = '';
        $errNum = (int)trim($this->_responseArr['errorCode']);
        $errStr = trim($this->_responseArr['errorString']);
        if(!empty($errNum)) { //есть ошибка
            if(!empty($errStr)) { //что-то есть в описании ошибки
                if(strpos($errStr, 'RC_') === 0) { //описание ошибки начинается с кода ошибки типа RC_SOME_ERROR
                    $err = explode(' ', $errStr);
                    $err = explode(',', $err[0]);
                    $resCode = trim($err[0]); //отбрасываем лишнее
                } else {
                    $matches = [];
                    //ищем код ошибки в ее описании в виде "блаблабла [RC_SOME_ERROR] блаблабла"
                    preg_match('/^\[[A-Z0-9\_]*\]/', $this->_responseArr['errorString'], $matches, PREG_OFFSET_CAPTURE);
                    $resCode = count($matches) ? str_replace(['[',']'],'',$matches[0][0]) : '';
                    if ($resCode) { $resCode = (strpos($resCode, 'RC_') !== 0 ? 'RC_' : '') . $resCode; }
                }
            }

            if ($resCode === '') { $resCode = $this->_getErrorCode($errNum); }

            $resMsg = (!$resCode && $errStr)
                            ? explode("\n", (explode("\r",$errStr))[0])[0]
                            : '';
            return ['code' => $resCode, 'msg'  => $resMsg];
        }
        return false;
    }

    public function getLastRequest():  string { return $this->_requestJSON;  }
    public function getLastResponse(): string { return $this->_responseJSON; }

    protected function _fullDbgMail($subj, $action, $params = null): void
    {
        SP_Debug::mail($this->_serviceManager, $subj,
            "ACTION :\n\n".($action?:'NO')
            . ($params ? "\n\n\n\nREQUEST PHP :\n\n".var_export(empty($params)?'NO':$params,true) : '')
            ."\n\n\n\nREQUEST JSON :\n\n".var_export(str_replace("\t",'  ',$this->_requestJSON?:'NO'),true)
            ."\n\n\n\nRESPONSE JSON :\n\n".var_export($this->_responseJSON?:'NO',true)
            ."\n\n\n\nRESPONSE PHP :\n\n".var_export($this->_responseArr?:'NO',true)
        );
    }
}
