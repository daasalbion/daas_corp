<?php

class ComboExpressController extends Zend_Controller_Action{

    public $logger;

    public function init(){
        /* Initialize action controller here */
        $this->logger = $this->getLog();
        //Agregamos otro Writer, para escribir los WebServices Request en otro archivo de log
        $writer = new Zend_Log_Writer_Stream(APPLICATION_PATH . '/../log/combo_express_'.date('Y-m-d').'.log');
        $format = '%timestamp% %priorityName% - [%remoteAddr%]: %message%' . PHP_EOL;
        $formatter = new Zend_Log_Formatter_Simple($format);
        $writer->setFormatter($formatter);
        $this->logger->addWriter($writer);
        $this->logger->setEventItem('remoteAddr', $_SERVER['REMOTE_ADDR']);
        $this->_helper->_layout->setLayout('combo-express-layout');
        //$this->view->headLink()->appendStylesheet('/css/combo-express/combo-express.css', 'screen');

    }

    public function getLog(){

        $bootstrap = $this->getInvokeArg('bootstrap');

        if (!$bootstrap->hasResource('Logger')) {
            return false;
        }

        $log = $bootstrap->getResource('Logger');

        return $log;
    }

    public function indexAction(){

        $this->_forward('home');
    }

    public function homeAction(){


    }

    public function nuestraEmpresaAction(){


    }

}

