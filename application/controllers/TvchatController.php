<?php

class TvchatController extends Zend_Controller_Action{

    public $logger;
    var $usuarios = array(

        'daas' => array('clave' => 'daas', 'nombre' => 'DAAS'),
    );
    var $NRO_ELEMENTOS_SORTEADOS_TRAGAMONEDAS = 3;
    var $NRO_ELEMENTOS_SORTEADOS_TOMBOLA = 4;

    public function init(){
        /* Initialize action controller here */
        $this->logger = $this->getLog();
        //Agregamos otro Writer, para escribir los WebServices Request en otro archivo de log
        $writer = new Zend_Log_Writer_Stream(APPLICATION_PATH . '/../log/tvchat_'.date('Y-m-d').'.log');
        $format = '%timestamp% %priorityName% - [%remoteAddr%]: %message%' . PHP_EOL;
        $formatter = new Zend_Log_Formatter_Simple($format);
        $writer->setFormatter($formatter);
        $this->logger->addWriter($writer);
        $this->logger->setEventItem('remoteAddr', $_SERVER['REMOTE_ADDR']);

        //Habilitar layouts
        $this->_helper->_layout->setLayout('tvchat-layout');
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

        $this->logger->info("index");
        $this->_forward('login');
    }

    public function loginAction() {

        $this->_helper->layout->disableLayout();
        /*$this->view->headLink()->setStylesheet('/css/tvchat/style.css', 'screen');
        $this->view->headLink()->setStylesheet('/css/plugins/bootstrap/bootstrap.css', 'screen');*/

        $form = new Application_Form_Login();

        if($this->getRequest()->isPost()) {

            $formData = $this->getRequest()->getPost();

            if($form->isValid($formData)){

                $nick = $form->getValue('login_user');
                $clave = $form->getValue('login_pass');

                if(!empty($nick) && !empty($clave)) {

                    if(array_key_exists($nick, $this->usuarios) && $clave == $this->usuarios[$nick]['clave']) {

                        $this->logger->info('LOGIN:[' . $nick . ']');
                        $namespace = new Zend_Session_Namespace("entermovil-tvchat");
                        $namespace->usuario = $nick;
                        $namespace->nombre = $this->usuarios[$nick]['nombre'];
                        $namespace->id= $this->usuarios[$nick]['id'];
                        $namespace->prefijo = $this->usuarios[$nick]['prefijo'];
                        $namespace->accesos = array(
                            'FULL'
                        );

                        $this->_redirect('/tvchat/administracion');

                    } else {

                        $this->_redirect('/tvchat/login');
                    }

                } else {

                    $this->_redirect('/tvchat/login');
                }

            } else {

                $this->_redirect('/tvchat/login');
            }
        }
    }

    public function logoutAction() {

        $namespace = new Zend_Session_Namespace("entermovil-tvchat");
        $this->logger->info('logout:[' . ( isset($namespace->usuario) ? $namespace->usuario : '')  . ']('.$namespace->nombre.')');

        unset($namespace->usuario);
        unset($namespace->nombre);

        $namespace->unsetAll();
        unset($namespace);

        $this->_redirect('/tvchat/login');
    }

    public function marqueeManagerAction(){

        $this->logger->info("marqueeManager");
    }

    public function marqueeAction(){

        $this->logger->info("marquee");
    }

    public function ajaxRequestAction(){

        $this->logger->info( "ajax request" );
        $this->_helper->viewRenderer->setNoRender(true);
        $this->logger->info( "request ". print_r( $_GET, true ) );

        if ($_GET['id_promocion'] == 25){

            $mensajes_nuevos = $this->_consulta('GET_MENSAJES', array('id_promocion'=> 25));
            $this->logger->info('datos a obtenidos ' . print_r($mensajes_nuevos, true));
            $respuesta = json_encode(array("tieneiva"=>"1", "mensajes_nuevos"=>$mensajes_nuevos ));
            $this->logger->info('datos a enviar ' . $respuesta );
            echo $respuesta;
        }
    }

    public function obtenerMensajesAction(){

        $this->logger->info( "solicitud de mensajes nuevos" );
        $this->_helper->viewRenderer->setNoRender(true);
        $this->logger->info( "request ". print_r( $_GET, true ) );
        $inicio = 0;
        $mensajes_marquee = '';

        if ( ( isset( $_GET['solicitud'] ) ) && ( $_GET['solicitud'] == true ) && ( isset( $_GET['id_mensaje'] ) ) ){

            $mensajes_nuevos = $this->_consulta( 'GET_MENSAJES_NUEVOS', array( 'id_tvchat_mensaje' => $_GET['id_mensaje'] ) );
            $this->logger->info( 'datos a obtenidos ' . print_r( $mensajes_nuevos, true ) );

            //mensajes mostrar marquee

            //sino esta vacio concatenamos las cadenas
            if( !is_null( $mensajes_nuevos ) ){

                foreach ( $mensajes_nuevos as $indice => $mensaje ){

                    if( $inicio == 0 ){

                        $mensajes_marquee .= $mensaje;
                        $inicio++;

                    }else{

                        $mensajes_marquee .= '__________' . $mensaje;
                    }
                }
            }
            //seteo el siguiente id a solicitar
            $siguiente_id_solicitar = $indice;
            $this->logger->info('siguiente_id_solicitar ' . $siguiente_id_solicitar );
            $this->logger->info('mensajes_marquee ' . $mensajes_marquee );
            $respuesta = json_encode( array( "mensajes_operador" => $mensajes_nuevos,
                'mensajes_marquee' => $mensajes_marquee, 'siguiente_id_solicitar' => $siguiente_id_solicitar ) );
            $this->logger->info('datos a enviar ' . $respuesta );
            echo $respuesta;
            exit;
        }else{

            $mensajes_nuevos = $this->_consulta( 'GET_MENSAJES_NUEVOS', array( 'id_tvchat_mensaje' => null ) );
            $this->logger->info( 'datos a obtenidos ' . print_r( $mensajes_nuevos, true ) );

            //seteo el siguiente id a solicitar
            $respuesta = json_encode( array( "mensajes" => $mensajes_nuevos ) );
            $this->logger->info( 'datos a enviar ' . $respuesta );
            echo $respuesta;
            exit;
        }
    }

    public function administracionAction(){

        $this->view->headScript()->appendFile('/js/plugins/jquery-1.8.0.min.js', 'text/javascript');
        $this->view->headScript()->appendFile('/js/plugins/bootstrap.js', 'text/javascript');
        $this->view->headScript()->appendFile('/js/tvchat/tvchat.js', 'text/javascript');
        $this->view->headScript()->appendFile('/js/tvchat/tvchat.manager.js', 'text/javascript');
        $this->view->headScript()->appendFile('/js/tvchat/tvchat.marquee.manager.js', 'text/javascript');
        $this->logger->info("setup");
    }

    public function tvAction(){

        //$this->view->headScript()->appendFile('/js/plugins/jquery-1.7.js', 'text/javascript');
        $this->view->headScript()->appendFile('/js/plugins/jquery.marquee.js', 'text/javascript');
        $this->view->headScript()->appendFile('/js/plugins/roulette.js', 'text/javascript');
        $this->view->headScript()->appendFile('/js/tvchat/tvchat.utils.js', 'text/javascript');
        $this->view->headScript()->appendFile('/js/tvchat/tvchat.wheel.js', 'text/javascript');
        $this->view->headScript()->appendFile('/js/tvchat/tvchat.child.js', 'text/javascript');
        //$this->view->headScript()->appendFile('/js/tvchat/tvchat.tragamonedas.manager.js', 'text/javascript');
        $this->logger->info("tv");
        $this->_helper->_layout->setLayout('tvchat-window-layout');
    }

    public function bingoShowAction(){

        $this->logger->info("bingo-show");
        $this->_helper->layout->disableLayout();
    }

    public function demo1Action(){
        $this->_helper->layout->disableLayout();
        $this->logger->info("demo1");
    }

    public function demo2Action(){

        $this->logger->info("demo2");
        $this->_helper->layout->disableLayout();
    }

    public function demoGalgosAction(){

        $this->logger->info("demoVideo");
        $this->_helper->layout->disableLayout();
    }

    public function demoDadosAction(){

    $this->logger->info("demoVideo");
        $this->_helper->layout->disableLayout();
    }

    public function demoAction(){

        $this->logger->info("demoVideo");
        $this->_helper->layout->disableLayout();
    }

    public function demoSlotAction(){

        $this->logger->info("demoVideo");
        $this->_helper->layout->disableLayout();
    }

    public function getWinElementsTragamonedasAction(){

        $elementos_ganadores = array();
        $nro = $this->NRO_ELEMENTOS_SORTEADOS_TRAGAMONEDAS;
        for( $i=1; $i <= $nro; $i++){

            $elementos_ganadores[] = rand( 0, 3 );
        }
        $this->logger->info( 'datos a obtenidos ' . print_r( $elementos_ganadores, true ) );
        $respuesta = json_encode( array( "sorteo" => $elementos_ganadores, "cel_ganador" => "0982313289", "juego" => "tragamonedas" ) );
        $this->logger->info( 'datos a enviar ' . $elementos_ganadores );
        echo $respuesta;
        exit;
    }

    public function getWinElementsTombolaAction(){

        $elementos_ganadores = array();
        $nro = $this->NRO_ELEMENTOS_SORTEADOS_TOMBOLA;
        $this->logger->info("numero de elementos tombola: " . $this->NRO_ELEMENTOS_SORTEADOS_TOMBOLA);
        for( $i=1; $i <= $nro; $i++){

            $elementos_ganadores[] = rand( 0, 9 );
        }
        $this->logger->info('datos a obtenidos ' . print_r($elementos_ganadores, true));
        $respuesta = json_encode(array( "sorteo" => $elementos_ganadores, "cel_ganador" => "0982313289", "juego" => "tombola" ) );
        $this->logger->info('datos a enviar ' . $elementos_ganadores );
        echo $respuesta;
        exit;
    }

    private function _consulta( $accion, $datos = null ){

        $bootstrap = $this->getInvokeArg('bootstrap');
        $options = $bootstrap->getOptions();

        $db = Zend_Db::factory(new Zend_Config($options['resources']['db']));
        $db->getConnection();
        $resultado = null;

        if( $accion == 'GET_MENSAJES' ){

            $sql = "select * from promosuscripcion.tvchat_mensajes where emitido = false or emitido is null order by id_tvchat_mensaje limit 5";
            $rs = $db->fetchAll( $sql );

            if( !empty( $rs ) ){

                $resultado = '';

                foreach( $rs as $fila ){

                    $resultado .= '_______' .$fila['mensaje'];

                    $where = array(

                        'id_tvchat_mensaje = ? ' => $fila['id_tvchat_mensaje']
                    );

                    $data = array(

                        'emitido' => true,
                    );

                    //$status = $db->update('promosuscripcion.tvchat_mensajes', $data, $where );
                }

                return $resultado;

            }else{

                return $resultado;
            }
        }elseif( $accion == 'GET_MENSAJES_NUEVOS' ){

            $sql = "select PT.id_tvchat_mensaje, PT.mensaje
                    from promosuscripcion.tvchat_mensajes PT
                    where PT.id_tvchat_mensaje > ?
                    order by 1
                    limit 10";

            if($datos['id_tvchat_mensaje'] == null)
                $datos['id_tvchat_mensaje'] = 1;

            $rs = $db->fetchAll( $sql, array( $datos['id_tvchat_mensaje'] ) );

            if( !empty( $rs ) ){

                foreach( $rs as $fila ){

                    $resultado[$fila['id_tvchat_mensaje']] = $fila['mensaje'];

                }

                return $resultado;

            }else{

                return $resultado;
            }
        }
    }
}

