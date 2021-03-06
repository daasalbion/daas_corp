<?php

class CallcenterController extends Zend_Controller_Action
{

    public $log = null;
    public $logger;

    public function init()
    {
        /* Initialize action controller here */
        $this->log = $this->getLog();
        if($this->log) {
            $this->log->info('CallcenterController -> Request');
            $this->logger = $this->log;
        }

        $headLinkContainer = $this->view->headLink()->getContainer();
        if(isset($headLinkContainer[0])) {
            unset($headLinkContainer[0]);//reportes_base.css
        }
        $this->view->headLink()->setStylesheet('/css/base.css', 'screen');

        $headScriptContainer = $this->view->headScript()->getContainer();
        if(count($headScriptContainer) > 1 && isset($headScriptContainer[1])) {
            unset($headScriptContainer[1]);//reportes_base.js
        }
        //$this->view->headScript()->appendFile('/js/base.js', 'text/javascript');

        $this->_helper->_layout->setLayout('callcenter-layout');

        $namespace = new Zend_Session_Namespace("entermovil_callcenter");
        if(!isset($namespace->usuario) || empty($namespace->usuario)) {
            $this->_redirect('/CallCenter');
        }

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
        $this->view->headScript()->appendFile('/js/modernizr-2.0.6.js', 'text/javascript');
        $this->view->headScript()->appendFile('/js/jquery.placeholder.min.js', 'text/javascript');
        $this->view->headScript()->appendFile('/js/jquery.form.js', 'text/javascript');
        $this->view->headScript()->appendFile('/js/tempo.js', 'text/javascript');
        $this->view->headScript()->appendFile('/js/callcenter.js', 'text/javascript');


        $this->view->headLink()->appendStylesheet('/css/callcenter.css', 'screen');

        $namespace = new Zend_Session_Namespace("entermovil_callcenter");
        $this->view->nombre = $namespace->nombre;

        $layout = $this->_helper->layout();
        $layout->mostrar_busqueda = true;
    }



    public function cancelarAction() {

        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $respuesta = array(
            'status' => 'OK'
        );

        $cel = $this->_getParam('nro_linea', 0);
        $idPromocion = $this->_getParam('id_promocion', 0);
        $id_carrier = $this->consulta('GET_ID_CARRIER', array(
            'cel' => $cel
        ));
        $namespace = new Zend_Session_Namespace("entermovil_callcenter");

        $datos_promocion = $this->consulta('GET_DATOS_ID_PROMOCION', array(
            'id_carrier' => $id_carrier,
            'id_promocion' => $idPromocion
        ));

        $enviar_mensaje_cancelacion = false;
        $mensaje_cancelacion = '-';
        if($id_carrier == 6) {
            //Insertar en tabla
            $status = $this->consulta('INSERTAR_CANCELACION_CALLCENTER', array(
                'cel' => $cel,
                'id_carrier' => $id_carrier,
                'id_promocion' => $idPromocion,
                'ts_local' => new Zend_Db_Expr('NOW()'),
                'usuario' => $namespace->usuario,
                'enviar_mensaje_cancelacion' => true,
                'mensaje' => 'Estimado cliente el servicio de suscripcion '. $datos_promocion['alias'] .' del '. $datos_promocion['numero'] .' fue eliminado con exito.',
                'numero' => $datos_promocion['numero']
            ));

        } else {
            //Insertar en tabla
            $status = $this->consulta('INSERTAR_CANCELACION_CALLCENTER', array(
                'cel' => $cel,
                'id_carrier' => $id_carrier,
                'id_promocion' => $idPromocion,
                'ts_local' => new Zend_Db_Expr('NOW()'),
                'usuario' => $namespace->usuario,
                'mensaje' => '--',
                'numero' => $datos_promocion['numero']
            ));
        }

        $this->logger->info('InsertarCancelacionCallcenter -> status:[' . $status . ']');

        $status = $this->consulta('CANCELAR_SUSCRIPCION', array(
            'cel' => $cel,
            'id_promocion' => $idPromocion
        ));
        $this->logger->info('CancelarSuscripcion -> status:[' . $status . ']');
        echo json_encode($respuesta);
        return;
    }

    private function idCarrierCel($cel) {

        $idCarrier = 0;
        switch($cel[2]) {//El 2to digito nos dice el carrier. Ejemplo: 09[7] o 09[8]
            case '6'://VOX
                $idCarrier = 3;
                break;

            case '7'://PERSONAL
                $idCarrier = 1;
                break;

            case '8'://TIGO
                $idCarrier = 2;
                break;

            case '9'://CLARO
                $idCarrier = 4;
                break;
        }
        $this->logger->info("idCarrier:[" . $idCarrier . "]");
        return $idCarrier;


    }

    public function historialAction() {

        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $cel = $this->_getParam('nro_linea', null);
        $respuesta = array(
            'status' => 'ERROR',
            'cantidad' => -1,
            'historial' => array(),
            'cel' => ''
        );
        if(!is_null($cel)) {
            $nro_linea = trim($cel);
            $respuesta['cel'] = $nro_linea;
            $this->logger->info('NroLinea:[' . $nro_linea . ']');
            $historial = $this->consulta('HISTORIAL', array(
                'cel' => $nro_linea,
                'id_carrier' => $id_carrier = $this->consulta('GET_ID_CARRIER', array(
                    'cel' => $nro_linea
                ))
            ));
            $respuesta['status'] = 'OK';
            $respuesta['cantidad'] = 0;
            if(!empty($historial)) {

                $respuesta['cantidad'] = count($historial);
                foreach($historial as $historico) {
                    $respuesta['historial'][] = $historico;
                }
                echo json_encode($respuesta);
                return;
            }
        } else {
            //cel vacio
            $respuesta['status'] = 'ERROR';
            $respuesta['error'] = 'Parámetro Incorrecto';
        }

        echo json_encode($respuesta);
        return;
    }

    public function buscarAction() {

        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $busqueda = $this->_getParam('q', null);
        $respuesta = array(
            'status' => 'ERROR',
            'cantidad' => -1,
            'suscripciones' => array(),
            'cel' => ''
        );
        if(!is_null($busqueda)) {

            $namespace = new Zend_Session_Namespace("entermovil_callcenter");
            $id_carrier_operador = 0;
            if(isset($namespace->id_carrier)) {
                $id_carrier_operador = $namespace->id_carrier;
            }
            $this->logger->info('id_carrier_operador:[' . $id_carrier_operador . ']');

            $nro_linea = trim($busqueda);
            //de que carrier es la linea
            $id_carrier = $this->consulta('GET_ID_CARRIER', array(
                'cel' => $nro_linea
            ));
            $this->logger->info('NroLinea:[' . $nro_linea . '] id_carrier:[' . $id_carrier . ']');

            if($id_carrier > 0) {//Si el cel tiene id_carrier, alguna vez se suscribio a algun servicio

                if($id_carrier_operador > 0 && $id_carrier_operador != $id_carrier) {//Un usuario de una operador solicito una linea de otra operadora

                    //usuario de operadora A pide numero de operadora B
                    $respuesta['status'] = 'ERROR';
                    $respuesta['error'] = 'Nro. de Línea Incorrecto';

                } else {

                    $respuesta['cel'] = $nro_linea;

                    $suscripciones = $this->consulta('BUSQUEDA_SUSCRIPCIONES', array(
                        'cel' => $nro_linea
                    ));

                    $respuesta['status'] = 'OK';
                    $respuesta['cantidad'] = 0;
                    if(!empty($suscripciones)) {

                        $respuesta['cantidad'] = count($suscripciones);
                        foreach($suscripciones as $suscripcion) {
                            $respuesta['suscripciones'][] = $suscripcion;
                        }
                        echo json_encode($respuesta);
                        return;
                    }
                }


            } else {
                //nro de linea no encontrada
                $respuesta['status'] = 'ERROR';
                $respuesta['error'] = 'Nro. de Línea No Encontrado';
            }

        } else {
            //busqueda vacia
            $respuesta['status'] = 'ERROR';
            $respuesta['error'] = 'Búsqueda Vacía';
        }

        echo json_encode($respuesta);
        return;
    }

    private function consulta($accion, $datos) {

        $config = new Zend_Config(array(
            'database' => array(
                'adapter' => 'Pdo_Pgsql',
                'params'  => array(
                    'host'     => '192.168.1.4',//'190.128.183.138',
                    'username' => 'konectagw',
                    'password' => 'konectagw2006',
                    'dbname'   => 'gw'
                )
            )
        ));
        $db = Zend_Db::factory($config->database);
        $db->getConnection();

        if($accion == 'INSERTAR_CANCELACION_CALLCENTER') {

            try {

                $status = $db->insert('callcenter_cancelaciones', $datos);
                $this->logger->info('status:[' . $status . ']');

                return $status;

            } catch(Zend_Db_Exception $e){
                $this->logger->err($e);
            }
        }

        if($accion == 'GET_DATOS_ID_PROMOCION') {

            $datos_respuesta = array();
            $sql = 'select numero, alias from info_promociones where id_carrier = ? and id_promocion = ?';
            $rs = $db->fetchRow($sql, array($datos['id_carrier'], $datos['id_promocion']));
            if($rs) {
                $datos_respuesta = (array)$rs;
            }
            return $datos_respuesta;
        }

        if($accion == 'GET_ID_CARRIER') {

            $datos_respuesta = array();
            $sql = 'select promosuscripcion.id_carrier_cel(?) as id_carrier';
            $rs = $db->fetchRow($sql, array($datos['cel']));
            if($rs) {
                $datos_respuesta = (array)$rs;
            }
            return $datos_respuesta['id_carrier'];
        }

        if($accion == 'CANCELAR_SUSCRIPCION') {

            $this->logger->info('Consulta: CANCELAR_SUSCRIPCION');
            $this->logger->info('cel:[' . $datos['cel'] . '] id_promocion:[' . $datos['id_promocion'] . ']');
            try {
                $status = $db->delete('promosuscripcion.suscriptos', array('cel = ?' => $datos['cel'], 'id_promocion = ?' => $datos['id_promocion']));
                $this->logger->info('status:[' . $status . ']');
            } catch(Zend_Db_Exception $e) {
                $this->logger->err($e);
            }

        }

        if($accion == 'HISTORIAL') {

            $this->logger->info('Consulta: HISTORIAL');
            $datos_respuesta = array();
            $sql_historial = "SELECT LS.ts_local::date as fecha, substring(LS.ts_local::varchar from 1 for 16)::varchar as fecha_hora, LS.id_promocion, (IP.numero || ' ' || IP.alias)::varchar as servicio, LS.accion
            FROM promosuscripcion.log_suscriptos LS
            LEFT JOIN info_promociones IP ON IP.id_promocion = LS.id_promocion AND IP.id_carrier = ?
            WHERE LS.cel = ? AND IP.id_promocion IS NOT NULL
            ORDER BY 2 DESC";
            $rs_historial = $db->fetchAll($sql_historial, array($datos['id_carrier'], $datos['cel']));
            if($rs_historial) {
                $datos_respuesta = (array)$rs_historial;
            }
            return $datos_respuesta;
        }

        if($accion == 'BUSQUEDA_SUSCRIPCIONES') {

            $descripciones = array(
                'MASCOTA' => 'Mascotas Animadas',
                'TONO' => 'Tonos Inéditos',
                'BB' => 'Tonos Inéditos para BlackBerry',
                'SIGLAS' => 'Fondos Personalizados',
                'BL' => 'Fondos Personalizados para BlackBerry',
                'DIVER' => 'Imágenes Divertidas',
                'POSTAL' => 'Postales del Paraguay',
                'PY' => 'Historia del Paraguay',
                'ALEGRIA' => 'Frases Alegres',
                'MUNDO' => 'Historia del Mundo',
                'CIENCIA' => 'Grandes Descubrimientos de la Ciencia',
                'INGLES' => 'Lecciones de Ingles',
                'USA' => 'Lecciones de Ingles',
                'OK' => 'Lecciones de Ingles',
                'BO' => 'Historia de Bolivia',
                'CUERNOS' => 'Manual Anticornudo',
                'VENADO' => 'Manual Anticornudo',
                'PAIS' => 'Historia del Paraguay',
                'PARAGUAY' => 'Historia del Paraguay',
                'SALUD' => 'Propiedades Curativas de las Frutas y Verduras',
                'VIDA' => 'Propiedades Curativas de las Frutas y Verduras',
                'GT' => 'Historia de Guatemala',
                'SEXO' => 'Tips de Sexo',
                'PAREJA' => 'Tips de Pareja',

                'SABER' => 'Saber de Todo',
                'CONOCER' => 'Conocer de Todo',

                'DINERO' => 'Consejos para atraer dinero',
                'FORTUNA' => 'Consejos para atraer fortuna',

                'EXITO' => 'Frases para el Exito',
                /*'GANAR' => 'Frases para Ganar',*/

                'ASTRO' => 'Consejos sobre el Horoscopo',
                'SIGNO' => 'Consejos sobre el Horoscopo',
                'SANTO' => 'Oraciones Milagrosas',
                'ORAR' => 'Oraciones Milagrosas',

                'CARRITO' => 'Gana para tu Super conociendo mas sobre alimentacion',
                'SUPER' => 'Gana un celular de ultima generacion',
                'COMPRAR' => 'Gana para tu Super conociendo mas sobre alimentacion',

                'SALDO' => 'Gana Saldo aprendiendo de Cultura General',
                'GANAR' => 'Gana Saldo aprendiendo de Cultura General',

                'CUENTAS' => 'Te ayudamos a pagar tus cuentas recibiendo consejos de dinero',
                'PAGAR' => 'Te ayudamos a pagar tus cuentas recibiendo consejos de dinero',

                'CARGAR' => 'Gana Saldo para un anho',
                'LLENAR' => 'Gana Saldo para un anho',

                'CEL' => 'Gana un celular de ultima generacion',

                'HORA' => 'Hora Paraguaya',
                'BINGO' => 'Mini-Bingo',
                'OSCAR' => 'Oscar Acosta',
                'MAGALI' => 'Magali Paez',
                'PAVON' => 'Enrique Pavon',

            );

            $this->logger->info('Consulta: BUSQUEDA_SUSCRIPCIONES');
            $datos_respuesta = array();
            $sql_suscriptos = "SELECT S.id_suscripto, S.id_carrier, S.id_promocion, IP.alias, IP.numero, (select ts_local from promosuscripcion.log_suscriptos where cel = S.cel and id_promocion = S.id_promocion and accion = 'ALTA' order by ts_local desc limit 1)::date as alta, trim(both from S.cel)::varchar as cel
            FROM promosuscripcion.suscriptos S
            LEFT JOIN info_promociones IP ON IP.id_promocion = S.id_promocion and IP.id_carrier = S.id_carrier
            WHERE S.cel = ?
            ORDER BY 5,6,4";
            $rs_suscriptos = $db->fetchAll($sql_suscriptos, array($datos['cel']));
            if($rs_suscriptos) {
                foreach($rs_suscriptos as $suscripto) {
                    $suscripto['descripcion'] = $descripciones[$suscripto['alias']];
                    $datos_respuesta[] = $suscripto;
                }
                //$datos_respuesta = (array) $rs_suscriptos;
            }
            return $datos_respuesta;
        }
    }

}









