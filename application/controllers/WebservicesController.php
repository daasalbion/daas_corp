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
                'el-salvador' => array(
                    'notifySubscription' => 'tigo-el-salvador-notify-subscription',//oneapi/userprofile/v1/
                    'notifyUnsubscription' => 'tigo-el-salvador-notify-unsubscription'//oneapi/userprofile/v1/
                ),
                'colombia' => array(
                    'notifySubscription' => 'tigo-colombia-notify-subscription',//oneapi/userprofile/v1/
                    'notifyUnsubscription' => 'tigo-colombia-notify-unsubscription'//oneapi/userprofile/v1/
                ),
                'bolivia' => '',
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
                'paraguay' => array(
                    'wsdl'                      =>  'personal-paraguay-servicios',
                    'WSDL'                      =>  'personal-paraguay-servicios',
                    'NotificarEvento'           =>  'personal-paraguay-servicios',
                )
            ),

            'claro' => array(
                'dominicana' => array(
                    'Status' => 'claro-dominicana-servicios'
                )
            ),

            'telcel' => array(
                'mexico' => array(
                    'Baja' => 'telcel-mexico-servicios',
                    'Cobrar' => 'telcel-mexico-servicios',
                    'Suscribir' => 'telcel-mexico-servicios',
                    'Enviar' => 'telcel-mexico-servicios'
                )
            ),
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

    public function telcelMexicoServiciosAction() {

        $respuesta = 'ERROR';
        $servicio = $this->_getParam('accion');

        $this->logger->info('GET:[' . print_r($_GET, true) . ']');

        if(strtoupper($servicio) == 'BAJA') {

            $this->logger->info('Servicio BAJA solicitado...');

            if(!isset($_GET['cel']) || !isset($_GET['id'])) {

                $respuesta = 'ERROR|Parametros Incorrectos';
                $this->logger->err($respuesta);

            } else {

                $bootstrap = $this->getInvokeArg('bootstrap');
                $options = $bootstrap->getOptions();

                $db = Zend_Db::factory(new Zend_Config($options['resources']['db']));
                $db->getConnection();

                $sql = "select * from promosuscripcion.telcel_mexico_solicitudes_alta where subscription_id = ? order by id_solicitud desc limit 1";
                $rsSolicitudAlta = $db->fetchRow($sql, array($_GET['id']));

                if($rsSolicitudAlta) {

                    $datosSolicitudBaja = array(
                        'ts_local' => new Zend_Db_Expr('NOW()'),
                        'id_suscripto' => $rsSolicitudAlta['id_suscripto'],
                        'cel' => $rsSolicitudAlta['cel'],
                        'id_promocion' => $rsSolicitudAlta['id_promocion'],
                        'id_carrier' => $rsSolicitudAlta['id_carrier'],
                        'estado' => 0,
                        'alias' => 'BAJA'
                    );
                    $this->logger->info('datosSolicitudBaja:[' . print_r($datosSolicitudBaja, true) . ']');

                    $status = $db->insert('promosuscripcion.telcel_mexico_solicitudes_baja', $datosSolicitudBaja);
                    $this->logger->info('statusInsert:[' . $status . ']');

                    $respuesta = 'OK';
                    $this->logger->info($respuesta);

                } else {
                    $respuesta = 'ERROR|SubscriptionId-No-Encontrado';
                    $this->logger->err($respuesta);
                }
            }

            header("HTTP/1.0 200 OK");

        } else if(strtoupper($servicio) == 'COBRAR') {

            $this->logger->info('Servicio COBRAR solicitado...');

            /*if(!isset($_GET['alias'])) {
                $_GET['alias'] = 'PORTAL';
            }*/
            if(!isset($_GET['cel']) || !isset($_GET['service_type']) || !isset($_GET['transId']) || !isset($_GET['alias'])) {

                $respuesta = 'ERROR|Parametros Incorrectos';
                $this->logger->err($respuesta);

            } else {

                $bootstrap = $this->getInvokeArg('bootstrap');
                $options = $bootstrap->getOptions();

                $db = Zend_Db::factory(new Zend_Config($options['resources']['db']));
                $db->getConnection();

                /*$sql = "select IP.id_promocion from promosuscripcion.telcel_mexico_codigos_cobros MCC left join info_promociones IP on IP.id_carrier = MCC.id_carrier and IP.numero = MCC.numero and IP.id_servicio = MCC.id_servicio where MCC.service_type = ? and IP.alias = ? limit 1";
                $rsIdPromocion = $db->fetchRow($sql, array($_GET['service_type'], strtoupper($_GET['alias'])));
                $this->logger->info('rsIdPromocion:[' . print_r($rsIdPromocion, true) . ']');
                if($rsIdPromocion) {*/

                    $datosSolicitudCobro = array(
                        'ts_local' => new Zend_Db_Expr('NOW()'),
                        'cel' => $_GET['cel'],
                        'id_promocion' => 46, //$rsIdPromocion['id_promocion'],
                        'estado' => 0,
                        'service_type' => $_GET['service_type'],
                        'id_transaccion' => $_GET['transId']
                    );

                    $this->logger->info('datosSolicitudCobro:[' . print_r($datosSolicitudCobro, true) . ']');

                    $status = $db->insert('promosuscripcion.telcel_mexico_solicitudes_cobros', $datosSolicitudCobro);
                    $this->logger->info('statusInsert:[' . $status . ']');

                    $respuesta = 'OK';
                    $this->logger->info($respuesta);
                //}
            }

            header("HTTP/1.0 200 OK");

        } else if(strtoupper($servicio) == 'SUSCRIBIR') {

            $this->logger->info('Servicio SUSCRIBIR solicitado...');

            if(!isset($_GET['cel']) || !isset($_GET['alias']) || trim($_GET['cel']) == '') {

                $respuesta = 'ERROR|Parametros Incorrectos';
                $this->logger->err($respuesta);

            } else {

                $bootstrap = $this->getInvokeArg('bootstrap');
                $options = $bootstrap->getOptions();

                $db = Zend_Db::factory(new Zend_Config($options['resources']['db']));
                $db->getConnection();

                $datosSuscripcion = array(
                    'cel' => $_GET['cel'],
                    'id_promocion' => 46,
                    'id_carrier' => 7
                );

                $this->logger->info('datosSuscripcion:[' . print_r($datosSuscripcion, true) . ']');

                $status = $db->insert('promosuscripcion.suscriptos', $datosSuscripcion);
                $this->logger->info('statusInsert:[' . $status . ']');

                $respuesta = 'OK';
                $this->logger->info($respuesta);
            }

            header("HTTP/1.0 200 OK");

        } else if(strtoupper($servicio) == 'ENVIAR') {

            $this->logger->info('Servicio ENVIAR solicitado...');

            if(!isset($_GET['cel']) || !isset($_GET['mensaje'])) {

                $respuesta = 'ERROR|Parametros Incorrectos';
                $this->logger->err($respuesta);

            } else {

                $bootstrap = $this->getInvokeArg('bootstrap');
                $options = $bootstrap->getOptions();

                $db = Zend_Db::factory(new Zend_Config($options['resources']['db']));
                $db->getConnection();

                $mensaje=trim($_GET['mensaje']);
                $this->logger->info('LongitudMensaje:[' . strlen($mensaje) . ']');
                if(strlen($mensaje) > 160) {

                    $respuesta = 'ERROR|Mensaje-Excede-160-Caracteres';
                    $this->logger->err($respuesta);

                } else {

                    $datosSalientes = array(
                        'n_remitente' => $_GET['cel'],
                        'n_llamado' => '30000',
                        'id_sc' => 46,
                        'id_carrier' => 7,
                        'ts_local' => new Zend_Db_Expr('NOW()'),
                        'id_cliente' => 3,
                        'estado' => 0,
                        'pendiente_billing' => 0,
                        'tipo_mensaje' => 1,
                        'id_contenido' => 0,
                        'sms' => $mensaje
                    );

                    $this->logger->info('datosSuscripcion:[' . print_r($datosSalientes, true) . ']');

                    $status = $db->insert('sms_salientes', $datosSalientes);
                    $this->logger->info('statusInsert:[' . $status . ']');

                    $respuesta = 'OK';
                    $this->logger->info($respuesta);
                }


            }

            header("HTTP/1.0 200 OK");
        }

        echo $respuesta;
        exit;
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
    //http://www.entermovil.com.py.localserver/ws/tigo/guatemala/SubscribeToService
    public function tigoGuatemalaServiciosAction() {

        $servicio = $this->_getParam('accion');
        $this->logger->info('Tigo-Guatemala-Servicios Servicio:[' . $servicio . ']');
        $wsdl = 'http://www.entermovil.com.py.localserver/ws/tigo/guatemala/'.$servicio.'?wsdl';
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
    //http://www.entermovil.com.py.localserver/ws/personal/paraguay/wsdl
    public function personalParaguayServiciosAction() {

        $servicio = $this->_getParam('accion');
        $this->logger->info('Personal-Paraguay-Servicios Servicio:[' . $servicio . ']');
        $wsdl = 'http://www.entermovil.com.py.localserver/ws/personal/paraguay/'.$servicio.'?wsdl';
        $this->logger->info('Personal-Paraguay-Servicios WSDL:[' . $wsdl . ']');

        include_once APPLICATION_PATH . '/../library/Webservices/Personal/Paraguay/WSNotificationsHandler.php';

        if(isset($_GET['wsdl']) || isset($_GET['WSDL'])) {

            $this->logger->info('Solicitud WSDL');
            $autodiscover = new Zend_Soap_AutoDiscover('Zend_Soap_Wsdl_Strategy_ArrayOfTypeComplex');
            //$autodiscover = new Zend_Soap_AutoDiscover('Zend_Soap_Wsdl_Strategy_Composite');
            $autodiscover->setClass('WSNotificationsHandler');
            $autodiscover->handle();

        } else {

            $server = new Zend_Soap_Server($wsdl);
            $server->setClass('WSNotificationsHandler');
            $server->handle();
        }
    }

    //application/x-www-form-urlencoded
    public function tigoElSalvadorNotifySubscriptionAction() {

        if( $this->getRequest()->isPost() ) {

            $formData = $this->getRequest()->getPost();

            $this->logger->info('Tigo-El-Salvador-Servicios Servicio:[Suscripcion]');
            $wsdl = 'http://www.entermovil.com.py/ws/tigo/paraguay/NotifySubscription?wsdl';
            $this->logger->info('Tigo-El-Salvador-Servicios WSDL:[' . $wsdl . ']');

            include_once APPLICATION_PATH . '/../library/Webservices/Tigo/ElSalvador/notify.php';

            //print_r($formData);//exit;

            $solicitud = new notify();
            $status = $solicitud->notifySuscription( $formData );

            $this->logger->info( 'datos: ' . print_r($formData, true ));

            $cadena_respuesta = '{"status":[STATUS]}';

            header("HTTP/1.1 200 OK\r\n");
            header("Content-Type: application/json");

            $cadena_respuesta = str_replace('[STATUS]', $status, $cadena_respuesta);
            header("Content-Length: " . strlen($cadena_respuesta));
            echo $cadena_respuesta;
            exit;

        }else{

            $status = 100;
            $cadena_respuesta = '{"status":[STATUS]}';

            header("HTTP/1.1 200 OK\r\n");
            header("Content-Type: application/json");

            $cadena_respuesta = str_replace('[STATUS]', $status, $cadena_respuesta);
            header("Content-Length: " . strlen($cadena_respuesta));
            echo $cadena_respuesta;
            exit;
        }

        /*exit;

        if(isset($_GET['wsdl']) || isset($_GET['WSDL'])) {

            $this->logger->info('Solicitud WSDL');
            $autodiscover = new Zend_Soap_AutoDiscover();
            $autodiscover->setClass('notify');
            $autodiscover->handle();

        } else {

            $server = new Zend_Soap_Server($wsdl);
            $server->setClass('notify');
            $server->handle();

        }*/
    }

    public function tigoElSalvadorNotifyUnsubscriptionAction() {

        if( $this->getRequest()->isPost() ) {

            $formData = $this->getRequest()->getPost();

            $this->logger->info('Tigo-El-Salvador-Servicios Servicio:[Suscripcion]');
            $wsdl = 'http://www.entermovil.com.py/ws/tigo/paraguay/NotifySubscription?wsdl';
            $this->logger->info('Tigo-El-Salvador-Servicios WSDL:[' . $wsdl . ']');

            include_once APPLICATION_PATH . '/../library/Webservices/Tigo/ElSalvador/notify.php';

            $solicitud = new notify();
            $status = $solicitud->notifyUnsubscription( $formData );

            $this->logger->info( 'datos: ' . print_r($formData, true ));

            $cadena_respuesta = '{"status":[STATUS]}';
            header("HTTP/1.1 200 OK\r\n");
            header("Content-Type: application/json");
            $cadena_respuesta = str_replace('[STATUS]', $status, $cadena_respuesta);
            header("Content-Length: " . strlen($cadena_respuesta));
            echo $cadena_respuesta;
            exit;


        }else{

            $status = 100;
            $cadena_respuesta = '{"status":[STATUS]}';
            header("HTTP/1.1 200 OK\r\n");
            header("Content-Type: application/json");
            $cadena_respuesta = str_replace('[STATUS]', $status, $cadena_respuesta);
            header("Content-Length: " . strlen($cadena_respuesta));
            echo $cadena_respuesta;
            exit;
        }
    }

    //tigo-colombia-notify-subscription
    public function tigoColombiaNotifySubscriptionAction() {

        if( $this->getRequest()->isPost() ) {

            $formData = $this->getRequest()->getPost();

            $this->logger->info('Tigo-El-Salvador-Servicios Servicio:[Suscripcion]');
            $wsdl = 'http://www.entermovil.com.py/ws/tigo/paraguay/NotifySubscription?wsdl';
            $this->logger->info('Tigo-El-Salvador-Servicios WSDL:[' . $wsdl . ']');

            include_once APPLICATION_PATH . '/../library/Webservices/Tigo/ElSalvador/notify.php';

            $solicitud = new notify();
            $status = $solicitud->notifySuscription( $formData );

            $this->logger->info( 'datos: ' . print_r($formData, true ));

            $cadena_respuesta = '{"status":[STATUS]}';

            header("HTTP/1.1 200 OK\r\n");
            header("Content-Type: application/json");

            $cadena_respuesta = str_replace('[STATUS]', $status, $cadena_respuesta);
            header("Content-Length: " . strlen($cadena_respuesta));
            echo $cadena_respuesta;
            exit;

        }else{

            $status = 100;
            $cadena_respuesta = '{"status":[STATUS]}';

            header("HTTP/1.1 200 OK\r\n");
            header("Content-Type: application/json");

            $cadena_respuesta = str_replace('[STATUS]', $status, $cadena_respuesta);
            header("Content-Length: " . strlen($cadena_respuesta));
            echo $cadena_respuesta;
            exit;
        }

        /*exit;

        if(isset($_GET['wsdl']) || isset($_GET['WSDL'])) {

            $this->logger->info('Solicitud WSDL');
            $autodiscover = new Zend_Soap_AutoDiscover();
            $autodiscover->setClass('notify');
            $autodiscover->handle();

        } else {

            $server = new Zend_Soap_Server($wsdl);
            $server->setClass('notify');
            $server->handle();

        }*/
    }

    //tigo-colombia-notify-unsubscription
    public function tigoColombiaNotifyUnsubscriptionAction() {

        if( $this->getRequest()->isPost() ) {

            $formData = $this->getRequest()->getPost();

            $this->logger->info('Tigo-El-Salvador-Servicios Servicio:[Suscripcion]');
            $wsdl = 'http://www.entermovil.com.py/ws/tigo/paraguay/NotifySubscription?wsdl';
            $this->logger->info('Tigo-El-Salvador-Servicios WSDL:[' . $wsdl . ']');

            include_once APPLICATION_PATH . '/../library/Webservices/Tigo/ElSalvador/notify.php';

            $solicitud = new notify();
            $status = $solicitud->notifyUnsubscription( $formData );

            $this->logger->info( 'datos: ' . print_r($formData, true ));

            $cadena_respuesta = '{"status":[STATUS]}';
            header("HTTP/1.1 200 OK\r\n");
            header("Content-Type: application/json");
            $cadena_respuesta = str_replace('[STATUS]', $status, $cadena_respuesta);
            header("Content-Length: " . strlen($cadena_respuesta));
            echo $cadena_respuesta;
            exit;


        }else{

            $status = 100;
            $cadena_respuesta = '{"status":[STATUS]}';
            header("HTTP/1.1 200 OK\r\n");
            header("Content-Type: application/json");
            $cadena_respuesta = str_replace('[STATUS]', $status, $cadena_respuesta);
            header("Content-Length: " . strlen($cadena_respuesta));
            echo $cadena_respuesta;
            exit;
        }
    }
}

?>