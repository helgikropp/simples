<?php
namespace Request\Controller;

use Zend\Http\Response;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

use SP\Utils\Lib;

class RequestController extends \Api\Controller\ApiController
{
    /** ========================================================================
     *
     * @return bool|Response|JsonModel
     */
    public function commentAction() {
        $decision = $this->getRightDecision(false,true);
        if($decision) { return $decision; }

        $requestId = $this->params()->fromRoute('requestid');
        $comment   = $this->params()->fromPost('comment','');

        $res = $this->model('Requests')->commentRequest($requestId, $comment);

        if (Lib::isFailed($res)) { return $this->asJsonResponse($res); }
            $view = (new ViewModel())
                    ->setTerminal(true)
                    ->setTemplate('request/get/part-request-tab-comments-msg')
                    ->setVariable('msg', $res['data']);

        return $this->asJsonResponse(['code' => 'RC_OK', 'data' => $res['data'], 'content' => $this->_render($view)]);
    }
}
