<?php

//SubscribeToService
include_once APPLICATION_PATH . '/../library/Webservices/Personal/Paraguay/NotificarEvento.php';
include_once APPLICATION_PATH . '/../library/Webservices/Personal/Paraguay/notification.php';
include_once APPLICATION_PATH . '/../library/Webservices/Personal/Paraguay/NotificarEventoResponse.php';


//Constantes
define('user', 'user');
define('password', 'password');
define('notifications', 'notifications');

/**
 *
 */
class WSNotificationsHandler
{
    /**
     * @param NotificarEvento $requestParams
     * @return NotificarEventoResponse
     */
    public function NotificarEvento(NotificarEvento $requestParams) {

        $front = Zend_Controller_Front::getInstance();
        $bootstrap = $front->getParam("bootstrap");
        $logger = $bootstrap->getResource('Logger');
        $logger->info("parametros recibidos: ". print_r($requestParams, true));
        $this->_validateParams($requestParams, 'NotificarEvento');

        $options = $bootstrap->getOptions();

        $db = Zend_Db::factory(new Zend_Config($options['resources']['db']));
        $db->getConnection();
        // Obtengo el vector de notificaciones
        $arrayNotificaciones = $requestParams->notifications;
        $logger->info("parametros1: ". print_r($arrayNotificaciones, true));

        if(!is_array($arrayNotificaciones)){

            $arrayNotificaciones = (array)$arrayNotificaciones;
        }
        $logger->info("parametros2 casteado: ". print_r($arrayNotificaciones, true));
        // Recorro las notificaciones y las proceso
        foreach($arrayNotificaciones as $notificacion) {
            // Obtengo el tid de la operacion
            $tid = $notificacion->tid; // Obtengo el dato de la notificacion
            $datoEvento = $notificacion->datoEvento;
            // Parseo el dato
            $arrayDatoEvento = explode("|",$notificacion->datoEvento);
            // Proceso el dato de la notificacion segun su tipo
            switch ($notificacion->tipoEvento) {
                // Alta de suscripcion
                case 0:
                    // Guardar alta de usuario
                    $logger->info("datos a guardar:" .print_r($arrayDatoEvento, true));
                    $telefono = $arrayDatoEvento[0];
                    $fechaAlta = $arrayDatoEvento[1];
                    $nroSuscripcion = $arrayDatoEvento[2];
                    $palabraClave = $arrayDatoEvento[3];
                    $numeroCorto = $arrayDatoEvento[4];

                    $datos = array(
                        'telefono' => $telefono,
                        'fecha_alta' => $fechaAlta,
                        'nro_suscripcion' => $nroSuscripcion,
                        'keyword' => $palabraClave,
                        'numero_corto' => $numeroCorto,
                    );

                    $guardar = $this->_consulta('alta_baja', $datos);
                    $logger->info( "Alta telefono $telefono de suscripcion nro $nroSuscripcion");
                    syslog(LOG_INFO, "Alta telefono $telefono de suscripcion nro $nroSuscripcion");
                    break;
                //Baja de suscripcion
                case 1:
                    // Guardar baja de usuario
                    $logger->info("datos a guardar:" .print_r($arrayDatoEvento, true));
                    $telefono = $arrayDatoEvento[0];
                    $fechaBaja = $arrayDatoEvento[1];
                    $nroSuscripcion = $arrayDatoEvento[2];
                    $palabraClave = $arrayDatoEvento[3];
                    $numeroCorto = $arrayDatoEvento[4];
                    $datos = array(
                        'telefono' => $telefono,
                        'fecha_alta' => $fechaAlta,
                        'nro_suscripcion' => $nroSuscripcion,
                        'keyword' => $palabraClave,
                        'numero_corto' => $numeroCorto,
                    );

                    $guardar = $this->_consulta('alta_baja', $datos);
                    $logger->info( "Baja telefono $telefono de suscripcion nro $nroSuscripcion" );
                    syslog(LOG_INFO, "Baja telefono $telefono de suscripcion nro $nroSuscripcion");
                    break;
                    // Billing
                case 2:
                    // Guardar estado de cobro de usuario
                    $logger->info("datos a guardar:" .print_r($arrayDatoEvento, true));
                    $estado = $arrayDatoEvento[0];
                    $telefono = $arrayDatoEvento[1];
                    $destino = $arrayDatoEvento[2];
                    $nroAplicacion = $arrayDatoEvento[3];
                    $aplicacion = $arrayDatoEvento[4];
                    $precio = $arrayDatoEvento[5];
                    $timeIn = $arrayDatoEvento[6];
                    $timeOut = $arrayDatoEvento[7];
                    $tid = $arrayDatoEvento[8];

                    $datos = array(
                        'estado' => $estado,
                        'telefono' => $telefono,
                        'destino' => $destino,
                        'nro_aplicacion' => $nroAplicacion,
                        'aplicacion' => $aplicacion,
                        'precio' => $precio,
                        'time_in' => $timeIn,
                        'time_out' => $timeOut,
                        'tid' => $tid,
                    );

                    $guardar = $this->_consulta( 'billing', $datos );

                    $logger->info( "Se cobro $precio al telefono $telefono" );
                    syslog(LOG_INFO, "Se cobro $precio al telefono $telefono");

                    break;
                default:
                    $error = true;
                    break;
            }
        }

        if ($error) throw new SoapFault("105","Tipo de notificacion no soportado");
        // Si no hay error, devolver vacio
        return;
    }

/*    private function _insertarSmsSaliente($phone,$n_llamado,$sms){
        $front = Zend_Controller_Front::getInstance();
        $bootstrap = $front->getParam("bootstrap");
        $logger = $bootstrap->getResource('Logger');

        $options = $bootstrap->getOptions();

        $db = Zend_Db::factory(new Zend_Config($options['resources']['db']));
        $db->getConnection();

        try {
            $logger->info('PREPARANDO PARA INSERCION EN SMS_SALIENTES');                                                                           // 'current_timestamp'
            $status = $db->insert('sms_salientes', array('id_carrier' => 5,'n_llamado' =>$n_llamado,'n_remitente'=>$phone,'sms' =>$sms,'ts_local'=> 'now()','id_cliente'=> 3,'estado'=>0,'pendiente_billing'=>0,'tipo_mensaje' => 1,'id_contenido'=> 0));
            $logger->info('status:[' . $status . ']');
            $logger->info('INSERCION EXITOSA ....');

        } catch(Zend_Db_Exception $e) {
            $logger->err($e);
        }

    }

    private function _estaSuscripto($phone,$alias,$shortcodenumber)
    {
        $front = Zend_Controller_Front::getInstance();
        $bootstrap = $front->getParam("bootstrap");
        $logger = $bootstrap->getResource('Logger');

        $options = $bootstrap->getOptions();

        $db = Zend_Db::factory(new Zend_Config($options['resources']['db']));
        $db->getConnection();

        $sql = "select cel from promosuscripcion.suscriptos S
                join info_promociones ip on (S.id_promocion=ip.id_promocion and S.id_carrier=5 AND S.cel= ? and ip.alias=?  and S.id_carrier=ip.id_carrier)";

        if($shortcodenumber)
         {
         $logger->info('Posee shortcodenumber');
         $sql .=" where ip.numero= ?";
         $rs = $db->fetchAll($sql, array($phone,$alias,$shortcodenumber));
         $logger->info('CONSULTA con shortcodenumber realizada');
         }
        else
         $rs = $db->fetchAll($sql, array($phone,$alias));

        $existe=false;
        if($rs)
         $existe=true;

     return $existe;
    }

    private function _getFormatoCorto($nro_largo) {

        //Verificamos que el nro recibido este en formato largo
        //Ejemplo: 502 40009752
        if(strlen($nro_largo) == 11 && substr($nro_largo, 0, 3) == '502') {//esta en formato largo
            return   substr($nro_largo, 3);
        }

        return $nro_largo;
    }*/

    private function _validateParams($requestParams, $nombreClase) {

        $front = Zend_Controller_Front::getInstance();
        $bootstrap = $front->getParam("bootstrap");
        $logger = $bootstrap->getResource('Logger');

        $isValid = false;

        if(!is_object($requestParams)) {
            $isValid = false;
        } else {
            $logger->info('Clase:[' . $nombreClase . ']');

            $parametros_obligatorios = array(
                'NotificarEvento' => array(user, password, notifications),
            );

            $logger->info('ParametrosObligatorios:[' . print_r($parametros_obligatorios[$nombreClase], true) . ']');
            $logger->info('ParametrosObtenidos:[' . print_r($requestParams,true) . ']');

            $lista_parametros_obligatorios = $parametros_obligatorios[$nombreClase];
            foreach($lista_parametros_obligatorios as $parametro) {
                if(!isset($requestParams->$parametro)) {
                    $logger->err('Parametro:[' . $parametro . '] no recibido');
                    $isValid = false;
                    break;
                }
            }

            $logger->info('Parametros validos');
        }

        return $isValid;
    }

    private function _consulta( $accion, $datos = null ){

        $front = Zend_Controller_Front::getInstance();
        $bootstrap = $front->getParam("bootstrap");
        $logger = $bootstrap->getResource('Logger');
        $options = $bootstrap->getOptions();

        $config = new Zend_Config(array(
            'database' => array(
                'adapter' => 'Pdo_Pgsql',
                'params'  => array(
                    'host'     => '190.128.183.138',
                    'username' => 'konectagw',
                    'password' => 'konectagw2006',
                    'dbname'   => 'gw'
                )
            )
        ));

        $db = Zend_Db::factory($config->database);
        $db->getConnection();
        if( $accion == "alta_baja" ){
            $logger->info("datos altas bajas: ". print_r($datos, true));
            try{
                $db->insert('promosuscripcion.personal_paraguay_altas_bajas', $datos);
            }catch(Exception $e){
                $logger->info("error $e");
            }

        }else{
            $logger->info("datos billing: ". print_r($datos, true));
            try{
                $db->insert('promosuscripcion.personal_paraguay_billing', $datos);
            }catch(Exception $e){
                $logger->info("error $e");
            }
        }
        $db->closeConnection();
    }

}
