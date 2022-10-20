<?php
namespace SP\Form;

use SP\Debug\Debug as SP_Debug;

use Zend\Http\PhpEnvironment\RemoteAddress;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ReCaptchaClient implements ServiceLocatorAwareInterface
{
    protected $_serviceManager;
    protected $_url;
    protected $_secret;
    protected $_responseJSON = '';
    protected $_responseArr  = [];
    protected $_lastError    = false;

    public function __construct($data) {
         $this->_url    = $data['urlReCaptcha'];
         $this->_secret = $data['secretReCaptcha'];
    }

    public function setServiceLocator(ServiceLocatorInterface $serviceLocator): ReCaptchaClient
    {
        $this->_serviceManager = $serviceLocator;
        return $this;
    }

    public function getServiceLocator(): ServiceLocatorInterface
    { return $this->_serviceManager; }

    public function check($captchaResponse) {
		$remoteIp = (new RemoteAddress())->getIpAddress();
        $cfg = $this->getServiceLocator()->get('config')['network'];

        $inputArr = 'secret='.$this->_secret.'&response='.$captchaResponse.'&remoteip='.$remoteIp;

		$proxy = curl_init($this->_url);
        curl_setopt($proxy, CURLOPT_RETURNTRANSFER,1); 
		curl_setopt($proxy, CURLOPT_POST, 1); 
        curl_setopt($proxy, CURLOPT_POSTFIELDS, $inputArr);
        curl_setopt($proxy, CURLOPT_VERBOSE, 1);
        if($cfg['use_proxy']){
            curl_setopt($proxy, CURLOPT_PROXY, $cfg['proxy_url']);
        }
        $this->_responseJSON = curl_exec($proxy); // run the whole process

        if($this->_responseJSON === false) {
            $this->_lastError = ['code' => 'RC_FORBIDDEN', 'msg'  => curl_error($proxy)];
            SP_Debug::mail($this->getServiceLocator(), 'CAPTCHA call fault', $this->_lastError);
            curl_close($proxy);
            return false;
        }

        curl_close($proxy);

        $this->_responseArr = json_decode($this->_responseJSON, 1);

        return $this->_responseArr['success']??false;
    }
}
