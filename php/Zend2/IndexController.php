<?php
namespace Application\Controller;

use Zend\View\Model\ViewModel;

class IndexController extends AbstractPortalController
{
    private $_header;

    /**
     * IndexController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $headers = new \Zend\Http\PhpEnvironment\Request;

        $this->_redirectUrl = $headers->getServer('HTTP_REFERER','/application');
        $server_name = $headers->getServer('SERVER_NAME','');

        $this->_header = new \Zend\Http\Header\SetCookie();
        $this->_header->setDomain($server_name);
        $this->_header->setPath('/');
        $this->_header->setExpires(0x6FFFFFFF);
    }

    /**
     * @return ViewModel
     */
    public function indexAction()
    {
        return new ViewModel();
    }

    /**
     *
     */
    public function uaAction()
    {
        $this->_header->setName('locale');
        $this->_header->setValue('uk_UA');
        $this->getResponse()->getHeaders()->addHeader($this->_header);
        $this->redirect()->toUrl($this->_redirectUrl);
    }

    /**
     *
     */
    public function enAction()
    {
        $this->_header->setName('locale');
        $this->_header->setValue('en_US');
        $this->getResponse()->getHeaders()->addHeader($this->_header);
        $this->redirect()->toUrl($this->_redirectUrl);
    }
}
