<?php

class EventoController extends Zend_Controller_Action
{

    public $logger;

    public function init()
    {
        /* Initialize action controller here */
        $this->logger = $this->getLog();
        if($this->logger) {
            $this->logger->info('EventoController -> Request');

            //Agregamos otro Writer, para escribir los WebServices Request en otro archivo de log
            $writer = new Zend_Log_Writer_Stream(APPLICATION_PATH . '/../log/mail_'.date('Y-m-d').'.log');
            $format = '%timestamp% %priorityName% - [%remoteAddr%]: %message%' . PHP_EOL;
            $formatter = new Zend_Log_Formatter_Simple($format);
            $writer->setFormatter($formatter);
            $this->logger->addWriter($writer);
            $this->logger->setEventItem('remoteAddr', $_SERVER['REMOTE_ADDR']);
        }

        //Deshabilitar layout y vista
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

    }

    public function getLog()
    {

        $bootstrap = $this->getInvokeArg('bootstrap');

        if (!$bootstrap->hasResource('Logger')) {
            return false;
        }
        $log = $bootstrap->getResource('Logger');
        return $log;
    }

    public function indexAction() {
        echo '.';
        exit;
    }

    public function enviarAction()
    {


        $hash = $this->_getParam('hash', '');
        if($hash != '2365b3577481864e3e6ea21a87a19708') {
            echo ".\n";

        } else {

            if(!$this->getRequest()->isPost()) {
                echo "..\n";

            } else {

                $para = 'soporte@entermovil.com.py';
                $datos = file_get_contents('php://input');
                parse_str($datos, $parametros);
                $asunto = $parametros['asunto'];
                $mensaje = $parametros['mensaje'];

                if(is_null($asunto) || is_null($mensaje)) {
                    echo "...\n";

                } else {

                    $this->_enviarMail($para, $asunto, $mensaje);
                    echo "OK\n";
                }
            }
        }

        header("Content-type: text/plain");
        header("HTTP/1.1 200 OK");
    }

    private function _enviarMail($para, $asunto, $mensaje) {

        $config = array(
            'auth' => 'login',
            'ssl' => 'tls',
            'username' => 'soporte@entermovil.com.py',
            'password' => 'enter212940'
        );

        $tr = new Zend_Mail_Transport_Smtp('smtp.gmail.com', $config);
        Zend_Mail::setDefaultTransport($tr);

        try {

            $mail = new Zend_Mail();
            $mail->setFrom('soporte@entermovil.com.py', 'Soporte Tecnico');
            $mail->addTo( $para, 'Soporte Técnico' );
            $mail->setSubject($asunto);
            $mail->setBodyText($mensaje);
            $mail->send();

            $this->logger->info("Mail de notificacion enviado a ".$para);

        } catch (Zend_Exception $e) {
            $this->logger->err($e);
        }
    }
}

?>