<?php
class ErrorController extends AppController  
{  
    public $_models=array();
    public $_daos=array();
    public $_components=array('NotifyError');
    public $_forms=array();
    private $_error;  
    private $_environment;  
  
    public function init()  
    {  

        parent::init();  
        $error = $this->_getParam('error_handler');
        if(!isset($error)||empty($error))
          {
          return;
          }
        $mailer = new Zend_Mail();  
        $session = new Zend_Session_Namespace('Auth_User');  
        $db = Zend_Registry::get('dbAdapter');
        $profiler = $db->getProfiler();
        $environment = Zend_Registry::get('configGlobal')->environment;
        $this->_environment=$environment;
        $this->Component->NotifyError->initNotifier(
            $environment,  
            $error,  
            $mailer,  
            $session,  
            $profiler,  
            $_SERVER  
        );  
  
        $this->_error = $error;  

        $this->_environment = $environment;  
   }  
  
    public function errorAction()  
    {  
        $error = $this->_getParam('error_handler');  
        if(!isset($error)||empty($error))
          {
          $this->view->message = 'Page not found'; 
          return;
          }
        switch ($this->_error->type) {  
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:  
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:  
                $this->getResponse()->setHttpResponseCode(404);  
                $this->view->message = 'Page not found';  
                break;  
  
            default:  
                $this->getResponse()->setHttpResponseCode(500);  
                $this->_applicationError();  
                break;  
        } 
        $fullMessage = $this->Component->NotifyError->getFullErrorMessage();  
        $this->getLogger()->crit($fullMessage);
      if(isset($this->fullMessage))
        {
        $this->getLogger()->crit($this->fullMessage);
        }
      else
        {
        $this->getLogger()->crit($this->view->message);
        }
    }  
  
    private function _applicationError()  
    {  
        $fullMessage = $this->Component->NotifyError->getFullErrorMessage();  
        $shortMessage = $this->Component->NotifyError->getShortErrorMessage();  
        $this->fullMessage=$fullMessage;
  
        switch ($this->_environment) {  
            case 'production':  
                $this->view->message = $shortMessage;  
                break;  
            case 'testing':  
                $this->_helper->layout->setLayout('blank');  
                $this->_helper->viewRenderer->setNoRender();  
  
                $this->getResponse()->appendBody($shortMessage);  
                break;  
            default:  
                $this->view->message = nl2br($fullMessage);  
        }  
  
        $this->Component->NotifyError->notify();  
    }  
}  