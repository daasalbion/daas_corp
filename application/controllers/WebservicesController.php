<?php

class WebservicesController extends Zend_Controller_Action
{

    public $logger;

    public function init()
    {
        /* Initialize action controller here */
        $this->logger = $this->getLog();
        if($this->logger) {
            $this->logger->info('WebservicesController -> Request');

            //Agregamos otro Writer, para escribir los WebServices Request en otro archivo de log
            $writer = new Zend_Log_Writer_Stream(APPLICATION_PATH . '/../log/webservices_'.date('Y-m-d').'.log');
            $format = '%timestamp% %priorityName% - [%remoteAddr%]: %message%' . PHP_EOL;
            $formatter = new Zend_Log_Formatter_Simple($format);
            $writer->setFormatter($formatter);
            $this->logger->addWriter($writer);
            $this->logger->setEventItem('remoteAddr', $_SERVER['REMOTE_ADDR']);
        }

        //Deshabilitar layout y vista
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        ini_set('soap.wsdl_cache_enabled', '0'); // disabling WSDL cache

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

    public function indexAction()
    {
        $mapeo_servicios = array(
            'tigo' => array(
                'paraguay' => '',
                'bolivia' => '',
                'colombia' => '',
                'guatemala' => array(
                    'wsdl'                      => 'tigo-guatemala-servicios',
                    'WSDL'                      => 'tigo-guatemala-servicios',
                    'SubscriptionServices'      => 'tigo-guatemala-servicios',

                    'SubscribeToService'        => 'tigo-guatemala-servicios',
                    'UnSubscribeToService'      => 'tigo-guatemala-servicios',
                    'UnSubscribeUser'           => 'tigo-guatemala-servicios',
                    'GetUserServices'           => 'tigo-guatemala-servicios',
                    'GetAvailableServices'      => 'tigo-guatemala-servicios',
                    'RequestUserService'        => 'tigo-guatemala-servicios',
                    'BlackListUser'             => 'tigo-guatemala-servicios',
                    'UnBlackListUser'           => 'tigo-guatemala-servicios'
                )
            ),

            'personal' => array(
                'paraguay' => ''
            ),

            'claro' => array(
                'dominicana' => array(
                    'Status' => 'claro-dominicana-servicios'
                )
            )
        );

        $operadora = $this->_getParam('operadora');
        $pais = $this->_getParam('pais');
        $accion = $this->_getParam('accion');
        $this->logger->info('Operadora:[' . $operadora . '] Pais:[' . $pais . '] Accion:[' . $accion . ']');

        if(is_null($accion) && (isset($_GET['wsdl']) || isset($_GET['WSDL']))) {
            $this->logger->info('Se establece accion => WSDL');
            $accion = 'WSDL';
        }

        if(isset($mapeo_servicios[$operadora][$pais][$accion])) {
            $this->logger->info('Forward:[' . $mapeo_servicios[$operadora][$pais][$accion] . ']');
            $this->_forward($mapeo_servicios[$operadora][$pais][$accion]);
        } else {
            $this->logger->err('Accion-No-Soportada:[' . $accion . ']');
            echo 'Accion-No-Soportada:[' . $accion . ']';
            exit;
        }
    }

    public function claroDominicanaServiciosAction() {

        $cadenaXML = file_get_contents('php://input');
        $this->logger->info('cadenaXML:[' . print_r($cadenaXML, true) . ']');
        $xml = simplexml_load_string($cadenaXML);
        $this->logger->info('xml:[' . print_r($xml, true) . ']');

        //echo 'Entermovil S.A.';
        echo '<?xml version="1.0" encoding="UTF-8"?><billingcheck><msisdn>'.$xml->msisdn.'</msisdn><transid>123456789</transid><result>0</result><comment>OK</comment></billingcheck>';
        header("Content-type: text/xml");
        header("HTTP/1.1 200 OK");
    }

    public function tigoGuatemalaServiciosAction() {

        $servicio = $this->_getParam('accion');
        $this->logger->info('Tigo-Guatemala-Servicios Servicio:[' . $servicio . ']');
        $wsdl = 'http://www.entermovil.com.py/ws/tigo/guatemala/'.$servicio.'?wsdl';
        $this->logger->info('Tigo-Guatemala-Servicios WSDL:[' . $wsdl . ']');

        include_once APPLICATION_PATH . '/../library/Webservices/Tigo/Guatemala/SubscriptionServices.php';

        if(isset($_GET['wsdl']) || isset($_GET['WSDL'])) {

            $this->logger->info('Solicitud WSDL');
            $autodiscover = new Zend_Soap_AutoDiscover();
            $autodiscover->setClass('SubscriptionServices');
            $autodiscover->handle();

        } else {

            $server = new Zend_Soap_Server($wsdl);
            $server->setClass('SubscriptionServices');
            $server->handle();

        }
    }
}

?>