<?php
namespace Application\Controller;

use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Http\Response;

use SP\Utils\Lib;
use SP\Session\Container as Safe;
use SP\Authentication\Storage\AuthenticationStorage;
use SP\Form\TranslatedForm;
use SP\Debug\Debug as SP_Debug;


class AbstractPortalController extends \Zend\Mvc\Controller\AbstractActionController
{
    protected $_redirectUrl;
    protected $_app    = 'web';
    protected $_portal = 'stc'; /

    public function __construct() {
        $rq = new \Zend\Http\PhpEnvironment\Request;
        $pathArr = explode('/',$rq->getUri()->getPath());
        $app  = $pathArr[1];
        $this->_app  = in_array($app,['web','pwa']) ? $app : 'web';
        $portal = count($pathArr)>2 ? $pathArr[2] : $pathArr[1];
        $this->_portal = in_array($portal,['sp','spc','spn']) ? $portal : '';

        if(strpos($rq->getServer('HTTP_REFERER','/'),'/get?f=')===FALSE) {
            $this->_redirectUrl = $rq->getServer('HTTP_REFERER','/');
        } else {
            $this->_redirectUrl = '/';
        }
    }

    /**
     * @param Safe $safe
     * @return bool|Response
     */
    final public function resetContractsAccessInfo(Safe $safe)
    {
        $tsCurrent = time();
        if($safe->offsetExists('LAST_RESET_TIME') && $safe->offsetExists('hasActualAccess')) {
            $tsLast = $safe->getParam('LAST_RESET_TIME',0);
            if($tsCurrent - $tsLast < 60) {
                return true;
            }
        }
        $safe->saveParam('LAST_RESET_TIME',$tsCurrent);

        $res = $this->model('SPC_Contract')->getContracts();
        if($res['state']['code'] === 'RC_RELOGON_REQUIRED') {
            return $this->redirect()->toRoute($this->_app.'/auth/exit');
        }

        if(Lib::isOK($res)) {
            $companies = &$res['data']['Companies'];
            $contracts = &$res['data']['Contracts'];

            $activeCompanies =
                array_values(
                    array_filter($companies,static function($v){ return $v['isActive'] === '1'; })
                );
            $refuellers = array_filter($activeCompanies, static function($v){ return $v['isRefueller'] === '1';});

            $contractsDict = [];
            foreach($contracts as $c) {
                $contractsDict[$c['contractId']] = [
                    'okpo'            => $c['okpo'],
                    'companyName'     => $c['companyName'],
                    'contractId'      => $c['contractId'],
                    'contractNum'     => $c['contractNum'],
                    'contractDate'    => $c['contractDate'],
                    'sapId'           => $c['sapId'],
                    'contractName'    => $c['contractName'],
                    'periodOfChanges' => $c['periodOfChanges']
                ];
            }

            self::setAccessObjects(
                $activeCompanies,
                $contractsDict
            );

            self::setAccessRec(
                !empty($activeCompanies),
                ($res['data']['HasExpired']??'0') === '1',
                !empty($contractsDict),
                !empty($refuellers),
                $res['data']['IsCommercial']==='1', 
                $res['data']['IsNoncommercial']==='1',
                $res['data']['DigiSignAllowed']==='1',
                $res['data']['IsСandidate']==='1',
                $res['data']['IsDenied']==='1',
                array_values(
                    array_filter($companies, static function($v){ return $v['isDigiSignAllowed'] === '1';})
                )
            );
        }

        return  $res;
    }


    //======== COMMON ==================================

    /**
     * @return array
     */
    final public static function getAccessRec(): array
    {
        $safe = new Safe('User','Data');
        return [
            'okpo'           => $safe->getParam('hasActualAccess', false),
            'expired'        => $safe->getParam('hasExpiredAccess',false),
            'contracts'      => $safe->getParam('hasContracts',    false),
            'fuel'           => $safe->getParam('hasFuelAccess',   false),
            'commercial'     => $safe->getParam('isCommercial',    false),
            'noncommercial'  => $safe->getParam('isNoncommercial', false),
            'digisigner'     => $safe->getParam('digiSignAllowed', false),
            'candidate'      => $safe->getParam('isCandidate',false),
            'denied'         => $safe->getParam('isDenied', false),
            'arrForDigisign' => $safe->getParam('okpoForDigiSign', false)
        ];
    }

    final public function makeDecision($bIdentity, $bAjax, $bPost=false, $bOkpoAccess=false, $bContractsAccess=false, $bJson=false) {
        $isAjax = $this->getRequest()->isXmlHttpRequest();
        $got = false;
        $routePortal = $this->_app . '/' . ($this->_portal?:'sp') . '/portal';
        $routeExit = $this->_app . '/auth/exit';

        if($bAjax && !$isAjax) {
            if($bJson) {
                $got = self::makeJsonResponse([
                    'code' => 'RC_RELOGON_REQUIRED',
                    'msg' => 'ERR_RELOGON_REQUIRED',
                    'redirect' => $routePortal]);
            } else {
                $got = $this->redirect()->toRoute($routePortal);
            }
        }

        if($bPost && !$this->getRequest()->isPost()) {
            $got = self::makeJsonResponse(['code'=>'RC_BAD_RQ_T','msg'=>'ERR_BAD_RQ_T']);
        }

        if(!$got && $bIdentity && !$this->hasIdentity()) {
            if($isAjax || $bJson) {
                $got = self::makeJsonResponse(['code'=>'RC_FORBIDDEN']);
            } else {
                $got = $this->redirect()->toRoute($routeExit);
            }
        }

        if(!$got && $bOkpoAccess && !self::hasActualAccess()) {
            if(!self::isDenied()) {
                $redir = $this->_urlFromRoute($routePortal);
                if ($isAjax || $bJson) {
                    $got = self::makeJsonResponse(['code' => 'RC_OKPOS_EXPD', 'data' => '', 'redirect' => $redir]);
                } else {
                    $got = $this->redirect()->toUrl($redir);
                }
            } else {
                $redir = $this->_urlFromRoute($routeExit);
                if ($isAjax || $bJson) {
                    $got = self::makeJsonResponse(['code' => 'RC_NO_PORTAL_ACCESS', 'data' => '', 'redirect' => $redir]);
                } else {
                    $got = $this->redirect()->toUrl($redir);
                }
            }
        }

        if(!$got && $bContractsAccess && !self::hasContracts()) {
            $redir = $this->plugin('url')->fromRoute($routePortal);
            if($isAjax || $bJson) {
                $got = self::makeJsonResponse([
                    'code' => 'RC_NO_KS_ACCESS',
                    'msg' => ['ERR_NO_KS_ACCESS', 'MSG_GET_KS_ACCESS'],
                    'redirect' => $redir
                ]);
            } else {
                $got = $this->redirect()->toUrl($redir);
            }
        }
        return $got;
    }

    /**
     * @param $res
     * @return JsonModel
     */
    public static function makeJsonResponse($res): JsonModel
    {
        return
            new JsonModel([
                    'code'     => $res['code'] ?? $res['state']['code'],
                    'msg'      => empty($res['state'])
                        ? preg_replace('/^RC_/','ERR_',(empty($res['msg'])?$res['code']:$res['msg']))
                        : preg_replace('/^RC_/','ERR_',(empty($res['state']['msg'])?$res['state']['code']:$res['state']['msg'])),
                    'data'     => $res['data'] ?? [],
                    'content'  => $res['content'] ?? '',
                    'html'     => $res['html'] ?? '',
                    'redirect' => $res['redirect'] ?? '',
                    'fields'   => $res['fields'] ?? [],
                    'tpl'      => $res['tpl'] ?? '',
                    'ext'      => $res['ext'] ?? ''
                ]
            );
    }

    final protected static function _routeVarIsValid($var,$constraint): bool
    {
        return (preg_replace('/'.$constraint.'/', '', $var) === '');
    }
}
