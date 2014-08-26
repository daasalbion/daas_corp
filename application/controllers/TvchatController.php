<?php

class TvchatController extends Zend_Controller_Action{

    public $logger;
    var $NRO_ELEMENTOS_SORTEADOS_TRAGAMONEDAS = 3;
    var $NRO_ELEMENTOS_SORTEADOS_TOMBOLA = 5;
    var $usuarios = array(

        'daas' => array('clave' => 'daas', 'nombre' => 'DAAS'),
    );

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

        $form = new Application_Form_Login();

        if( $this->getRequest()->isPost() ) {

            $formData = $this->getRequest()->getPost();

            if( $form->isValid( $formData ) ){

                $nick = $form->getValue('login_user');
                $clave = $form->getValue('login_pass');

                if( !empty( $nick ) && !empty( $clave ) ) {

                    if( array_key_exists($nick, $this->usuarios) && $clave == $this->usuarios[$nick]['clave'] ) {

                        $this->logger->info('LOGIN:[' . $nick . ']');
                        $namespace = new Zend_Session_Namespace("entermovil-tvchat");
                        $namespace->usuario = $nick;
                        $namespace->nombre = $this->usuarios[$nick]['nombre'];
                        $namespace->accesos = array(
                            'FULL'
                        );

                        $this->_redirect('/tvchat/admin/');

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
        $this->_helper->layout->disableLayout();
    }

    public function obtenerMensajesAction(){

        $this->logger->info( "solicitud de mensajes nuevos" );
        $this->_helper->viewRenderer->setNoRender(true);
        $this->logger->info( "request ". print_r( $_GET, true ) );
        $inicio = 0;
        $mensajes_marquee = '';

        if ( ( isset( $_GET['solicitud'] ) ) && ( $_GET['solicitud'] == 'marquee' ) && ( isset( $_GET['id_mensaje'] ) ) ){

            $mensajes_nuevos = $this->_consulta( 'GET_MENSAJES_MARQUEE', array( 'id_mensaje' => $_GET['id_mensaje'] ) );
            $this->logger->info( 'datos a obtenidos ' . print_r( $mensajes_nuevos, true ) );

            //mensajes mostrar marquee

            //sino esta vacio concatenamos las cadenas
            if( !is_null( $mensajes_nuevos ) ){

                foreach ( $mensajes_nuevos as $indice => $mensaje ){

                    if( $inicio == 0 ){

                        $mensajes_marquee .= $mensaje['mensaje'];
                        $inicio++;

                    }else{

                        $mensajes_marquee .= '&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp' . $mensaje['mensaje'];
                    }
                }
            }else{

                $indice = $_GET['id_mensaje'];
            }

            //seteo el siguiente id a solicitar
            $siguiente_id_solicitar = $indice;
            $this->logger->info('siguiente_id_solicitar ' . $siguiente_id_solicitar );
            $this->logger->info('mensajes_marquee ' . $mensajes_marquee );
            $respuesta = json_encode(

                array(
                        'mensajes_operador' => $mensajes_nuevos,
                        'mensajes_marquee' => $mensajes_marquee,
                        'siguiente_id_solicitar' => $siguiente_id_solicitar
                )
            );

            $this->logger->info('datos a enviar ' . $respuesta );
            echo $respuesta;
            exit;
        }else{

            $mensajes_nuevos = $this->_consulta( 'GET_MENSAJES_MARQUEE', array( 'id_tvchat_mensaje' => null ) );
            $this->logger->info( 'datos a obtenidos ' . print_r( $mensajes_nuevos, true ) );

            //seteo el siguiente id a solicitar
            $respuesta = json_encode( array( "mensajes" => $mensajes_nuevos ) );
            $this->logger->info( 'datos a enviar ' . $respuesta );
            echo $respuesta;
            exit;
        }
    }

    public function administracionAction(){

        //ver como utilizar predispatch
        $namespace = new Zend_Session_Namespace("entermovil-tvchat");
        if( !isset( $namespace->usuario ) ){

            $this->_redirect('/tvchat/login');
        }

        //$this->view->headScript()->appendFile('/js/plugins/jquery-1.8.0.min.js', 'text/javascript');
        $this->view->headScript()->appendFile('/js/plugins/bootstrap.js', 'text/javascript');
        $this->view->headScript()->appendFile('/js/tvchat/tvchat.js', 'text/javascript');
        $this->view->headScript()->appendFile('/js/tvchat/tvchat.manager.js', 'text/javascript');
        $this->logger->info("setup");
    }

    public function tvAction(){

        //ver como utilizar predispatch
        $namespace = new Zend_Session_Namespace("entermovil-tvchat");
        if( !isset( $namespace->usuario ) ){

            $this->_redirect('/tvchat/login');
        }

        //$this->view->headScript()->appendFile('/js/plugins/jquery-1.7.js', 'text/javascript');
        $this->view->headScript()->appendFile('/js/plugins/jquery.marquee.js', 'text/javascript');
        $this->view->headScript()->appendFile('/js/plugins/roulette.js', 'text/javascript');
        $this->view->headScript()->appendFile('/js/plugins/jquery.scrollbox.js', 'text/javascript');
        $this->view->headScript()->appendFile('/js/tvchat/tvchat.utils.js', 'text/javascript');
        $this->view->headScript()->appendFile('/js/plugins/jquery.wheel.js', 'text/javascript');
        $this->view->headScript()->appendFile('/js/tvchat/tvchat.child.js', 'text/javascript');
        $this->logger->info("tv");
        $this->_helper->_layout->setLayout('tvchat-window-layout');
    }

    public function tvhotAction(){

        //ver como utilizar predispatch
        $namespace = new Zend_Session_Namespace("entermovil-tvchat");
        if( !isset( $namespace->usuario ) ){

            $this->_redirect('/tvchat/login');
        }

        $this->view->headLink()->appendStylesheet('/css/tvchat/tvhot.css', 'screen');
        $this->view->headScript()->appendFile('/js/plugins/jquery.marquee.js', 'text/javascript');
        $this->logger->info("tv");
        $this->_helper->_layout->setLayout('tvchat-window-layout');
    }

    public function getWinElementsTragamonedasAction(){

        $elementos_ganadores = array();
        $datos_obtenidos = array();
        $parametros = array();
        $nro = $this->NRO_ELEMENTOS_SORTEADOS_TRAGAMONEDAS*2;

        $parametros['premio'] = $_GET['premio'];

        $this->logger->info( "parametros: ". print_r( $parametros, true ) );

        if( $parametros['premio'] == 'true' ){

            $datos = array(

                'id_juego' => 1,
                'premio' => 'true'
            );

            $datos_obtenidos = $this->_consulta( 'GET_ELEMENTS_TRAGAMONEDAS', $datos );

            $this->logger->info( 'datos recibidos [' . print_r( $datos_obtenidos, true ) .']' );

            if( !is_null( $datos_obtenidos ) ){

                $cel_ganador = $datos_obtenidos['cel'];
                $id_sorteo = $datos_obtenidos['id_sorteo'];
                $codigo = $datos_obtenidos['codigo'];

                for( $i = 0; $i < $nro; $i = $i+2 ){

                    $elementos_ganadores[] = substr( $codigo, $i, 2);
                }

                $this->logger->info( 'datos a obtenidos ' . print_r( $elementos_ganadores, true ) );
                $respuesta = json_encode( array( "id_sorteo" => $id_sorteo, "sorteo" => $elementos_ganadores,
                    "cel_ganador" => $cel_ganador, "juego" => "tragamonedas" ) );
                $this->logger->info( 'datos a enviar ' . $respuesta );
            }

        }else if( $parametros['premio'] == 'false' ){

            $datos = array(

                'id_juego' => 1,
                'premio' => 'false'
            );

            $datos_obtenidos = $this->_consulta( 'GET_ELEMENTS_TRAGAMONEDAS', $datos );

            $this->logger->info( 'datos recibidos [' . print_r( $datos_obtenidos, true ) .']' );

            if( !is_null( $datos_obtenidos ) ){

                $cel_ganador = 'Sin Ganador';
                $id_sorteo = $datos_obtenidos['id_sorteo'];
                $codigo = $datos_obtenidos['codigo'];

                for( $i = 0; $i < $nro; $i = $i+2 ){

                    $elementos_ganadores[] = substr( $codigo, $i, 2);
                }

                //numero de celular randomico

                $this->logger->info( 'datos a obtenidos ' . print_r( $elementos_ganadores, true ) );
                $respuesta = json_encode( array( "id_sorteo" => $id_sorteo, "sorteo" => $elementos_ganadores,
                    "cel_ganador" => $cel_ganador, "juego" => "tragamonedas" ) );
                $this->logger->info( 'datos a enviar ' . $respuesta );

            }
        }

        echo $respuesta;
        exit;
    }

    public function getWinElementsTragamonedasSexyAction(){

        $parametros = array();
        $datos_obtenidos = array();
        $elementos_ganadores = array();

        $parametros['premio'] = $_GET['premio'];

        $this->logger->info( "parametros: ". print_r( $parametros, true ) );

        if( $parametros['premio'] == 'true' ){

            $datos = array(

                'id_juego' => 2,
                'premio' => 'true'
            );

            //$datos_obtenidos = $this->_consulta( 'GET_ELEMENTS_TRAGAMONEDAS_SEXY', $datos );
            $datos_obtenidos = array(
                'cel'=>'0982313289',
                'id_sorteo'=>'1',
                'codigo'=>'No tiene'
            );

            $this->logger->info( 'datos recibidos [' . print_r( $datos_obtenidos, true ) .']' );

            if( !is_null( $datos_obtenidos ) ){

                $cel_ganador = $datos_obtenidos['cel'];
                $id_sorteo = $datos_obtenidos['id_sorteo'];
                $codigo = $datos_obtenidos['codigo'];

                $this->logger->info( 'datos a obtenidos ' . print_r( $elementos_ganadores, true ) );
                $respuesta = json_encode( array( "sorteo" => $elementos_ganadores, "cel_ganador" => $cel_ganador,
                    "juego" => "tragamonedas_sexy" ) );

                $this->logger->info( 'datos a enviar ' . $respuesta );
            }
        }

        echo $respuesta;
        exit;
    }

    public function getWinElementsTombolaAction(){

        $parametros = array();
        $elementos_ganadores = array();
        $nro = $this->NRO_ELEMENTOS_SORTEADOS_TOMBOLA;
        $cel_ganador = 'Sin Ganador';
        $id_sorteo = 000000;
        $codigo = 000000;

        $parametros['premio'] = $_GET['premio'];

        $this->logger->info( "parametros: ". print_r( $parametros, true ) );

        if( $parametros['premio'] == 'true' ){

            $datos = array(

                'id_juego' => 3,
                'premio' => 'true'
            );

            $datos_obtenidos = $this->_consulta( 'GET_ELEMENTS_TOMBOLA', $datos );

            $this->logger->info( 'datos recibidos [' . print_r( $datos_obtenidos, true ) .']' );

            if( !is_null( $datos_obtenidos ) ){

                $cel_ganador = $datos_obtenidos['cel'];
                $id_sorteo = $datos_obtenidos['id_sorteo'];
                $codigo = $datos_obtenidos['codigo'];

                for( $i = 0; $i < $nro; $i++ ){

                    $elementos_ganadores[] = substr( $codigo, $i, 1);
                }

                $this->logger->info( 'datos a obtenidos ' . print_r( $elementos_ganadores, true ) );
                $respuesta = json_encode( array( "id_sorteo" => $id_sorteo, "sorteo" => $elementos_ganadores,
                    "cel_ganador" => $cel_ganador, "juego" => "tombola" ) );
                $this->logger->info( 'datos a enviar ' . $respuesta );
            }

        }
        else if( $parametros['premio'] == 'false' ){

            $datos = array(

                'id_juego' => 3,
                'premio' => 'false'
            );

            $datos_obtenidos = $this->_consulta( 'GET_ELEMENTS_TRAGAMONEDAS', $datos );

            $this->logger->info( 'datos recibidos [' . print_r( $datos_obtenidos, true ) .']' );

            if( !is_null( $datos_obtenidos ) ){

                $cel_ganador = 'Sin Ganador';
                $id_sorteo = $datos_obtenidos['id_sorteo'];
                $codigo = $datos_obtenidos['codigo'];

                for( $i = 0; $i < $nro; $i++ ){

                    $elementos_ganadores[] = substr( $codigo, $i, 2);
                }

                //numero de celular randomico

                $this->logger->info( 'datos a obtenidos ' . print_r( $elementos_ganadores, true ) );
                $respuesta = json_encode( array( "id_sorteo" => $id_sorteo, "sorteo" => $elementos_ganadores,
                    "cel_ganador" => $cel_ganador, "juego" => "tombola" ) );
                $this->logger->info( 'datos a enviar ' . $respuesta );

            }
        }

        /*for( $i = 1; $i <= $nro; $i++ ){

            $elementos_ganadores[] = rand( 0, 9 );
        }

        //numero de celular randomico
        $cel_ganador = "0982000000" + rand( 0, 999999);
        $this->logger->info( 'datos a obtenidos ' . print_r( $elementos_ganadores, true ) );
        $respuesta = json_encode( array( "sorteo" => $elementos_ganadores, "cel_ganador" =>"0$cel_ganador", "juego" => "tombola" ) );
        $this->logger->info( 'datos a enviar ' . $respuesta );

    }else{

        for( $i = 1; $i <= $nro; $i++ ){

            $elementos_ganadores[] = rand( 0, 9 );
        }

        //numero de celular randomico
        $cel_ganador = "0982000000" + rand( 0, 999999);
        $this->logger->info( 'datos a obtenidos ' . print_r( $elementos_ganadores, true ) );
        $respuesta = json_encode( array( "sorteo" => $elementos_ganadores, "cel_ganador" => "Sin Ganador", "juego" => "tombola" ) );
        $this->logger->info( 'datos a enviar ' . $respuesta );

        */


        echo $respuesta;
        exit;
    }

    public function getWinElementsPiropoAction(){

        $parametros = array();
        $parametros['premio'] = $_GET['premio'];

        $this->logger->info( "parametros: ". print_r( $parametros, true ) );

        if( $parametros['premio'] == 'true' ){

            //numero de celular randomico
            $cel_ganador = "0982000000" + rand( 0, 999999);
            $respuesta = json_encode( array( "cel_ganador" => "0$cel_ganador", "juego" => "piropo2" ) );
            $this->logger->info( 'datos a enviar ' . $respuesta );
        }

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
        }
        else if( $accion == 'GET_MENSAJES_MARQUEE' ){

            $sql = "select * from promosuscripcion.obtener_mensajes(?, 50)";

            if($datos['id_mensaje'] == null)
                $datos['id_mensaje'] = 1;

            $rs = $db->fetchAll( $sql, array( $datos['id_mensaje'] ) );

            if( !empty( $rs ) ){

                foreach( $rs as $fila ){

                    $resultado[$fila['id_mensaje']]['mensaje'] = $fila['mensaje'];
                    $resultado[$fila['id_mensaje']]['cel'] = $fila['cel'];
                    $resultado[$fila['id_mensaje']]['id_mensaje'] = $fila['id_mensaje'];

                }

                return $resultado;

            }else{

                return $resultado;
            }
        }
        else if( $accion == 'INSERTAR' ){

            $insertar = array(
                'id_carrier' => '2',
                'n_llamado' => '6767',
                'n_remitente' => $datos['cel'],
                'alias' => 'DEMO-CHAT',
                'sms' => $datos['mensaje'],
                'ts_local' => 'now()',
                'id_cliente' => '2',
                'id_sms_carrier' => '2',
                'estado' => '2',
                'id_sc' => '2',
                'cmd_id' => '2',
                'id_promocion' => '2',
                'id_tipo_promocion' => '2',
            );

            $status = $db->insert('chatcenter.sms_entrantes', $insertar);

            return;
        }
        else if( $accion == 'MOSTRAR' ){

            $sql = "select id_historial, fh_in as fh_mensaje_usuario, msj_in as mensaje_usuario, fh_out as fh_mensaje_operador,
                    msj_out as mensaje_operador
                    from chatcenter.historial
                    where cel = ?
                    order by id_historial asc";

            $rs = $db->fetchAll( $sql, array( $datos['cel'] ) );

            if( !empty( $rs ) ){

                foreach( $rs as $fila ){

                    $resultado[] = $fila;
                }

                return $resultado;

            }else{

                return $resultado;
            }
        }
        else if( $accion == 'MOSTRAR_MENSAJES_NUEVOS' ){

            $sql = "select id_historial, fh_in as fh_mensaje_usuario, msj_in as mensaje_usuario, fh_out as fh_mensaje_operador,
                    msj_out as mensaje_operador
                    from chatcenter.historial
                    where cel = ?
                    and id_historial > ?
                    and msj_out is not null
                    order by id_historial asc";

            $rs = $db->fetchAll( $sql, array( $datos['cel'], $datos['id_historial'] ) );

            if( !empty( $rs ) ){

                foreach( $rs as $fila ){

                    $resultado[] = $fila;
                }

                return $resultado;

            }else{

                return $resultado;
            }


        }
        else if( $accion == 'GET_ELEMENTS_TRAGAMONEDAS' ){

            $this->logger->info( 'datos recibidos [' . print_r( $datos, true ) .']' );

            $sql = "select * from promosuscripcion.jugar_sorteo( ?, ? )";

            $rs = $db->fetchAll( $sql, array( $datos['id_juego'], $datos['premio'] ) );

            if( !empty( $rs ) ){

                foreach( $rs as $fila ){

                    $resultado = $fila;
                }

                return $resultado;

            }else{

                return $resultado;
            }
        }
        else if( $accion == 'GET_ELEMENTS_TRAGAMONEDAS_SEXY' ){

            $sql = "select * from promosuscripcion.jugar_sorteo( ?, ? )";

            $rs = $db->fetchAll( $sql, array( $datos['id_juego'], $datos['premio'] ) );

            if( !empty( $rs ) ){

                foreach( $rs as $fila ){

                    $resultado = $fila;
                }

                return $resultado;

            }else{

                return $resultado;
            }
        }
        else if( $accion == 'GET_ELEMENTS_TOMBOLA' ){

            $sql = "select * from promosuscripcion.jugar_sorteo( ?, ? )";

            $rs = $db->fetchAll( $sql, array( $datos['id_juego'], $datos['premio'] ) );

            if( !empty( $rs ) ){

                foreach( $rs as $fila ){

                    $resultado = $fila;
                }

                return $resultado;

            }else{

                return $resultado;
            }
        }
    }

    public function chatNumeroAction() {

        $this->_helper->layout->disableLayout();

        $namespace = new Zend_Session_Namespace("entermovil-cliente-chat-sexy");

        if($this->getRequest()->isPost()) {

            $formData = $this->getRequest()->getPost();
            if(trim($formData['cel']) == "") {
                $this->_forward('chat-numero');

            } else {
                $namespace->cel = $formData['cel'];
            }
            $this->_redirect('/tvchat/chat-historial');
            return;

        } else {

            if(isset($namespace->cel)) {
                $this->view->cel = $namespace->cel;
            }
        }
    }

    public function chatNuevosMensajesAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $namespace = new Zend_Session_Namespace("entermovil-cliente-chat-sexy");

        $nuevos_mensajes = array();

        if(isset($namespace->cel)) {

            $this->view->cel = $namespace->cel;

            $id_historial = $this->_getParam('ultimo', 0);

            $datos = array();

            $datos['cel'] = $namespace->cel;
            $datos['id_historial'] = $id_historial;

            $nuevos_mensajes = $this->_consulta( 'MOSTRAR_MENSAJES_NUEVOS', $datos );

        }

        $respuesta = array(
            'hayNuevosMensajes' => false
        );

        if(count($nuevos_mensajes) > 0) {

            $respuesta['hayNuevosMensajes'] = true;
            $respuesta['ultimoId'] = $nuevos_mensajes[count($nuevos_mensajes)-1]['id_historial'];
        }

        header('Content-type: application/json');
        echo json_encode($respuesta);
    }

    public function chatHistorialAction() {

        $this->_helper->layout->disableLayout();
        $namespace = new Zend_Session_Namespace("entermovil-cliente-chat-sexy");

        if($this->getRequest()->isPost()) {

            $formData = $this->getRequest()->getPost();

            if(isset($formData)){

                $datos['mensaje'] = $formData['mensaje'];
                $datos['cel'] = $formData['cel'];

                $this->logger->info( print_r($datos, true));

                $this->_consulta( 'INSERTAR', $datos );
                $this->_redirect('/tvchat/chat-historial');
            }

        } else {

            if(!isset($namespace->cel)) {
                $this->_redirect('/tvchat/chat-numero');

            } else {

                $this->view->cel = $namespace->cel;

                $datos = array();

                $datos['cel'] = $namespace->cel;
                $datos_mostrar = $this->_consulta( 'MOSTRAR', $datos );
                $ultimo_id = 0;
                foreach($datos_mostrar as $fila) {
                    if(!empty($fila['mensaje_usuario'])) {
                        $ultimo_id = $fila['id_historial'];
                    }

                }
                $this->view->ultimo_id = $ultimo_id;
                $this->view->datos_mostrar = $datos_mostrar;
                $this->view->cel = $namespace->cel;

            }
        }



    }

    public function adminAction(){

        $this->_helper->layout->disableLayout();
        //ver como utilizar predispatch
        $namespace = new Zend_Session_Namespace("entermovil-tvchat");
        if( !isset( $namespace->usuario ) ){

            $this->_redirect('/tvchat/login');
        }
    }

    public function testearConexionAction(){

        $respuesta = 0;
        echo $respuesta;
        exit;
    }

    public function descargarAction() {

        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $this->logger->info('--->DESCARGAR_ACTION');

        $parametros = $this->_getAllParams( 'id', null );

        if( is_null( $parametros ) ) {

            $this->logger->info('Auth NO-DEFINIDO!!');
            throw new Zend_Exception("Parámetros No Válidos");
            return;
        }

        $this->logger->info( 'Parametros:[' . print_r( $parametros, true ) . ']' );

        if( isset( $parametros['id'] ) ) {

            if( !empty( $parametros ) ){

                $path_archivo_descarga = 'C:\Users\USER\ENTERMOVIL\DAAS\PROYECTOS\www.entermovil.desarrollodaas.com.py\public\img\tvchat\fotos\foto_1.png';
                $size_archivo = filesize($path_archivo_descarga);
                $this->logger->info( 'size:[' . $size_archivo . ']' );
                header('Content-Description: File Transfer');
                $content_type = 'image/jpeg';
                header('Content-Type: ' . $content_type);
                $nombre_contenido = basename($path_archivo_descarga);

                header('Content-Disposition: inline; filename='.$nombre_contenido);
                header('Content-Transfer-Encoding: binary');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                header('Pragma: public');
                header('Content-Length: ' . $size_archivo);
                ob_clean();
                flush();

                $bytes_leidos = readfile($path_archivo_descarga);
                exit;
            }
        }
    }

}

