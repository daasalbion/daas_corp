<?php

class TvchatController extends Zend_Controller_Action{

    public $logger;
    var $NRO_ELEMENTOS_SORTEADOS_TRAGAMONEDAS = 3;
    var $NRO_ELEMENTOS_SORTEADOS_TOMBOLA = 6;
    var $usuarios = array(

        'daas' => array('clave' => 'daas', 'nombre' => 'DAAS'),
        'admin' => array('clave' => 'admin2014', 'nombre' => 'Operador'),
        'operador' => array('clave' => 'operador123', 'nombre' => 'Operador')
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

        $this->view->headScript()->appendFile('/js/plugins/jquery.marquee.js', 'text/javascript');
        $this->view->headScript()->appendFile('/js/plugins/roulette.js', 'text/javascript');
        $this->view->headScript()->appendFile('/js/plugins/jquery.scrollbox.js', 'text/javascript');
        $this->view->headScript()->appendFile('/js/tvchat/tvchat.utils.js', 'text/javascript');
        $this->view->headScript()->appendFile('/js/plugins/jquery.wheel.js', 'text/javascript');
        $this->view->headScript()->appendFile('/js/tvchat/tvchat.child.js', 'text/javascript');
        $this->logger->info("tv");
        $this->_helper->_layout->setLayout('tvchat-window-layout');
    }

    public function afortunadosAction(){

        //ver como utilizar predispatch
        $namespace = new Zend_Session_Namespace("entermovil-tvchat");
        if( !isset( $namespace->usuario ) ){

            $this->_redirect('/tvchat/login');
        }

        $this->view->headScript()->appendFile('/js/plugins/jquery.marquee.js', 'text/javascript');
        $this->view->headScript()->appendFile('/js/plugins/roulette.js', 'text/javascript');
        $this->view->headScript()->appendFile('/js/plugins/jquery.scrollbox.js', 'text/javascript');
        $this->view->headScript()->appendFile('/js/tvchat/tvchat.utils.js', 'text/javascript');
        $this->view->headScript()->appendFile('/js/plugins/jquery.wheel.js', 'text/javascript');
        $this->view->headScript()->appendFile('/js/tvchat/tvchat.child.emision.js', 'text/javascript');
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

        $parametros = array();
        $nro = $this->NRO_ELEMENTOS_SORTEADOS_TRAGAMONEDAS*2;
        $datos_obtenidos = array();
        $elementos_ganadores = array();

        //valores por defecto
        $cel_ganador = 'No obtenido';
        $id_sorteo = null;
        $codigo = 'No obtenido';

        $parametros['premio'] = $_GET['premio'];

        $this->logger->info( "parametros: ". print_r( $parametros, true ) );

        if( $parametros['premio'] == 'true' ){

            $datos = array(

                'id_juego' => 1,
                'premio' => 'true'
            );

            //$datos_obtenidos = $this->_consulta( 'GET_ELEMENTS_TRAGAMONEDAS', $datos );

            $datos_obtenidos = array(
                'id_sorteo' => 1,
                'codigo' => rand(0, 999999),
                'cel' => '0982' . rand(0, 999999),
            );

            $this->logger->info( 'datos recibidos [' . print_r( $datos_obtenidos, true ) .']' );

            if( !is_null( $datos_obtenidos ) ){

                $cel_ganador = $datos_obtenidos['cel'];
                $id_sorteo = $datos_obtenidos['id_sorteo'];
                $codigo = $datos_obtenidos['codigo'];

                for( $i = 0; $i < $nro; $i = $i+2 ){

                    $elementos_ganadores[] = substr( $codigo, $i, 2 );
                }
            }

        }else if( $parametros['premio'] == 'false' ){

            $datos = array(

                'id_juego' => 1,
                'premio' => 'false'
            );

            //$datos_obtenidos = $this->_consulta( 'GET_ELEMENTS_TRAGAMONEDAS', $datos );

            $datos_obtenidos = array(
                'id_sorteo' => 1,
                'codigo' => rand(0, 999999),
                'cel' => '0982' . rand(0, 999999),
            );

            $this->logger->info( 'datos recibidos [' . print_r( $datos_obtenidos, true ) .']' );

            if( !is_null( $datos_obtenidos ) ){

                $cel_ganador = 'Sin Ganador';
                $id_sorteo = $datos_obtenidos['id_sorteo'];
                $codigo = $datos_obtenidos['codigo'];

                for( $i = 0; $i < $nro; $i = $i+2 ){

                    $elementos_ganadores[] = substr( $codigo, $i, 2);
                }

            }
        }

        $this->logger->info( 'datos a obtenidos ' . print_r( $elementos_ganadores, true ) );
        $respuesta = json_encode(
            array(
                "id_sorteo" => $id_sorteo,
                "sorteo" => $elementos_ganadores,
                "cel_ganador" => $cel_ganador,
                "juego" => "tragamonedas"
            )
        );

        $this->logger->info( 'datos a enviar ' . $respuesta );
        echo $respuesta;

        exit;

    }

    public function getWinElementsTragamonedasSexyAction(){

        $parametros = array();
        $datos_obtenidos = array();
        $elementos_ganadores = array();
        $cel_ganador = 'No obtenido';
        $id_sorteo = 'No obtenido';
        $codigo = 'No obtenido';

        $parametros['premio'] = $_GET['premio'];

        $this->logger->info( "parametros: ". print_r( $parametros, true ) );

        if( $parametros['premio'] == 'true' ){

            $datos = array(

                'id_juego' => 2,
                'premio' => 'true'
            );

            //$datos_obtenidos = $this->_consulta( 'GET_ELEMENTS_TRAGAMONEDAS_SEXY', $datos );

            $datos_obtenidos = array(
                'id_sorteo' => 1,
                'codigo' => rand(0, 999999),
                'cel' => '0982' . rand(0, 999999),
            );

            $this->logger->info( 'datos recibidos [' . print_r( $datos_obtenidos, true ) .']' );

            if( !is_null( $datos_obtenidos ) ){

                $cel_ganador = $datos_obtenidos['cel'];
                $id_sorteo = $datos_obtenidos['id_sorteo'];
                $codigo = $datos_obtenidos['codigo'];

            }
        }

        $this->logger->info( 'datos a obtenidos ' . print_r( $elementos_ganadores, true ) );
        $respuesta = json_encode(
            array(
                "id_sorteo" => $id_sorteo,
                "sorteo" => $elementos_ganadores,
                "cel_ganador" => $cel_ganador,
                "juego" => "tragamonedas_sexy"
            )
        );

        $this->logger->info( 'datos a enviar ' . $respuesta );

        echo $respuesta;
        exit;
    }

    public function getWinElementsTombolaAction(){

        $parametros = array();
        $elementos_ganadores = array();
        $nro = $this->NRO_ELEMENTOS_SORTEADOS_TOMBOLA;
        $cel_ganador = 'No obtenido';
        $id_sorteo = 'No obtenido';
        $codigo = 'No obtenido';

        $parametros['premio'] = $_GET['premio'];

        $this->logger->info( "parametros: ". print_r( $parametros, true ) );

        if( $parametros['premio'] == 'true' ){

            $datos = array(

                'id_juego' => 3,
                'premio' => 'true'
            );

            //$datos_obtenidos = $this->_consulta( 'GET_ELEMENTS_TOMBOLA', $datos );

            $datos_obtenidos = array(
                'id_sorteo' => 1,
                'codigo' => rand(0,999999),
                'cel' => '0982' . rand(0, 999999),
            );

            $this->logger->info( 'datos recibidos [' . print_r( $datos_obtenidos, true ) .']' );

            if( !is_null( $datos_obtenidos ) ){

                $cel_ganador = $datos_obtenidos['cel'];
                $id_sorteo = $datos_obtenidos['id_sorteo'];
                $codigo = $datos_obtenidos['codigo'];

                for( $i = 0; $i < $nro; $i++ ){

                    $elementos_ganadores[] = substr( $codigo, $i, 1);
                }
            }

        }else if( $parametros['premio'] == 'false' ){

            $datos = array(

                'id_juego' => 3,
                'premio' => 'false'
            );

            //$datos_obtenidos = $this->_consulta( 'GET_ELEMENTS_TRAGAMONEDAS', $datos );

            $datos_obtenidos = array(
                'id_sorteo' => 1,
                'codigo' => rand(111111,999999),
                'cel' => '0982' . rand(0, 999999),
            );

            $this->logger->info( 'datos recibidos [' . print_r( $datos_obtenidos, true ) .']' );

            if( !is_null( $datos_obtenidos ) ){

                $cel_ganador = 'Sin Ganador';
                $id_sorteo = $datos_obtenidos['id_sorteo'];
                $codigo = $datos_obtenidos['codigo'];

                for( $i = 0; $i < $nro; $i++ ){

                    $elementos_ganadores[] = substr( $codigo, $i, 1);
                }
            }
        }

        $this->logger->info( 'datos a obtenidos ' . print_r( $elementos_ganadores, true ) );

        $respuesta = json_encode(
            array(
                "id_sorteo" => $id_sorteo,
                "sorteo" => $elementos_ganadores,
                "cel_ganador" => $cel_ganador,
                "juego" => "tombola"
            )
        );
        $this->logger->info( 'datos a enviar ' . $respuesta );

        echo $respuesta;
        exit;
    }

    public function getWinElementsPiropoAction(){

        $parametros = array();
        $datos_obtenidos = array();
        $elementos_ganadores = array();
        $cel_ganador = 'No obtenido';
        $id_sorteo = 'No obtenido';
        $codigo = 'No obtenido';

        $parametros['premio'] = $_GET['premio'];

        $this->logger->info( "parametros: ". print_r( $parametros, true ) );

        if( $parametros['premio'] == 'true' ){

            $datos = array(

                'id_juego' => 5,
                'premio' => 'true'
            );

            //$datos_obtenidos = $this->_consulta( 'GET_ELEMENTS_PIROPO2', $datos );

            $datos_obtenidos = array(
                'id_sorteo' => 1,
                'codigo' => rand(0, 999999),
                'cel' => '0982' . rand(0, 999999),
            );

            $this->logger->info( 'datos recibidos [' . print_r( $datos_obtenidos, true ) .']' );

            if( !is_null( $datos_obtenidos ) ){

                $cel_ganador = $datos_obtenidos['cel'];
                $id_sorteo = $datos_obtenidos['id_sorteo'];
                $codigo = $datos_obtenidos['codigo'];

            }
        }

        $this->logger->info( 'datos a obtenidos ' . print_r( $elementos_ganadores, true ) );
        $respuesta = json_encode(
            array(
                "id_sorteo" => $id_sorteo,
                "sorteo" => $elementos_ganadores,
                "cel_ganador" => $cel_ganador,
                "juego" => "piropo2"
            )
        );

        $this->logger->info( 'datos a enviar ' . $respuesta );

        echo $respuesta;
        exit;

    }

    public function guardarSorteoAction(){

        $parametros = array();
        $id_sorteo = null;
        $id_premio = null;
        $status = 0;

        $parametros['id_sorteo'] = $_GET['id_sorteo'];
        $parametros['id_premio'] = $_GET['id_premio'];

        $this->logger->info( "parametros: ". print_r( $parametros, true ) );

        if( isset( $parametros['id_sorteo'] ) && isset( $parametros['id_premio'] ) ){

            $datos = array(

                'id_sorteo' => $parametros['id_sorteo'],
                'id_premio' => $parametros['id_premio']
            );

            $status = $this->_consulta( 'UPDATE_SORTEO', $datos );

            $this->logger->info( 'datos recibidos [' . $status. ']' );

        }

        $respuesta = json_encode(
            array(
                "status" => $status,
            )
        );

        $this->logger->info( 'datos a enviar ' . $respuesta );
        echo $respuesta;

        exit;

    }

    public function obtenerSorteoPiropoMensajesAction(){

        $parametros = array();

        $id_mensaje = null;
        $cel = null;
        $id_juego = null;
        $id_sorteo = 0;

        $parametros['id_mensaje'] = $_GET['id_mensaje'];
        $parametros['id_juego'] = $_GET['id_juego'];
        $parametros['cel'] = $_GET['cel'];

        $this->logger->info( "parametros: ". print_r( $parametros, true ) );

        if( isset( $parametros['id_mensaje'] ) && isset( $parametros['id_juego'] ) && isset( $parametros['cel'] ) ){

            $datos = array(

                'id_mensaje' => $parametros['id_mensaje'],
                'id_juego' => $parametros['id_juego'],
                'cel' => $parametros['cel']
            );

            $id_sorteo = $this->_consulta( 'GET_SORTEO_PIROPO_MENSAJES', $datos );

            $this->logger->info( 'datos recibidos [' . $id_sorteo. ']' );

        }

        $respuesta = json_encode(

            array(

                'id_sorteo' => $id_sorteo,
            )
        );

        $this->logger->info( 'datos a enviar ' . $respuesta );

        echo $respuesta;

        exit;

    }

    private function _consulta( $accion, $datos = null ){

        $bootstrap = $this->getInvokeArg('bootstrap');
        $options = $bootstrap->getOptions();

        $db = Zend_Db::factory(new Zend_Config($options['resources']['db']));
        $db->getConnection();
        $resultado = null;

        if($accion == 'DESCARGA_FOTO_REGALO') {



        } else if($accion == 'ESTA_HABILITADO_FOTO_REGALO') {//recibe id_regalo y verifica si el usuario ya tiene habilitado dicho regalo

            $sql = "select * from promosuscripcion.jugar_fotos_regalos_x_usuarios where cel = ? and id_promocion = ? and id_foto_regalo = ?;";
            $rs = $db->fetchAll($sql, array($datos['cel'], $datos['id_promocion'], $datos['id_foto_regalo']));
            $resultado = false;
            if(!empty($rs)) {
                foreach($rs as $fila) {
                    $resultado = true;
                    break;
                }
            }

            return $resultado;

        } else if($accion == 'GET_ID_FOTO_REGALO') {//recibe nombre de archivo y devuelve id_regalo si existe

            $sql = "select * from promosuscripcion.jugar_fotos_regalos where nombre_archivo = ?;";
            $rs = $db->fetchAll($sql, array($datos['nombre_archivo']));
            $resultado = 0;//id_regalo
            if(!empty($rs)) {
                foreach($rs as $fila) {
                    $resultado = $fila['id_foto_regalo'];
                    break;
                }
            }

            return $resultado;

        } else if( $accion == 'GET_MENSAJES' ){

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

            $sql = "select * from promosuscripcion.obtener_mensajes(?,15)";

            if($datos['id_mensaje'] == null)
                $datos['id_mensaje'] = 1;

            $rs = $db->fetchAll( $sql, array( $datos['id_mensaje'] ) );

            if( !empty( $rs ) ){

                foreach( $rs as $fila ){

                    $resultado[$fila['id_mensaje']]['mensaje'] = $fila['mensaje'];
                    $resultado[$fila['id_mensaje']]['cel'] = $fila['cel'];
                    $resultado[$fila['id_mensaje']]['id_mensaje'] = $fila['id_mensaje'];
                    $resultado[$fila['id_mensaje']]['ya_sorteado'] = $fila['ya_sorteado'];

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
        else if( $accion == 'GET_ELEMENTS_PIROPO2' ){

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
        else if( $accion == 'UPDATE_SORTEO' ){

            $sql = "select * from promosuscripcion.jugar_confirmar_sorteo( ?, ? )";

            $rs = $db->fetchAll( $sql, array( $datos['id_sorteo'], $datos['id_premio'] ) );

            if( !empty( $rs ) ){

                foreach( $rs as $fila ){

                    $resultado = $fila;
                }

                return $resultado;

            }else{

                return $resultado;
            }
        }
        else if( $accion == 'GET_SORTEO_PIROPO_MENSAJES' ){

            $sql = "select * from promosuscripcion.jugar_sorteo_piropo( ?, ?, ? )";

            $rs = $db->fetchAll( $sql, array( $datos['id_juego'], $datos['cel'], $datos['id_mensaje'] ) );

            if( !empty( $rs ) ){

                foreach( $rs as $fila ){

                    $resultado = $fila['id_sorteo'];
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

    private function _getFormatoCorto($nro_largo) {

        $this->logger->info('_getFormatoCorto -> nroLargo:[' . $nro_largo . '] longitud:[' . strlen($nro_largo) . ']');
        if(strlen($nro_largo) == 12 && substr($nro_largo, 0, 3) == '595') {//esta en formato largo
            return '0'.substr($nro_largo, 3);
        }
        return $nro_largo;
    }

    public function descargarAction() {

        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $this->logger->info('--->DESCARGAR_ACTION');

        /*$header_names = array('HTTP_MSISDN', 'HTTP_X_UP_CALLING_LINE_ID', 'HTTP_X_MSISDN', 'HTTP_X_NOKIA_MSISDN');
        $nro_cel = 'NO_RECIBIDO';//En formato largo: 595 981 524 664
        $nombre_header = null;
        foreach($header_names as $header_name) {
            if(isset($_SERVER[$header_name]) && !empty($_SERVER[$header_name])) {
                $nro_cel = $_SERVER[$header_name];
                $nombre_header = $header_name;
                break;
            }
        }

        if($nro_cel == 'NO_RECIBIDO') {
            $this->logger->info('Debe utilizar la red 3G');
            echo '<h1>Debe utilizar la conexion 3G de su teléfono</h1>';
            return;
        }

        $nombre_archivo = $this->_getParam('id', null);
        if(is_null($nombre_archivo)) {
            $this->logger->info('Parametro incorrecto');
            echo '<h1>Parámetro incorrecto</h1>';
            return;
        }

        $cel = $this->_getFormatoCorto($nro_cel);
        $id_promocion = 89;//Jugar
        $this->logger->info( 'nombre_archivo:[' . $nombre_archivo . '] cel:[' . $cel . ']' );*/

        $nombre_archivo = $this->_getParam('id', null);
        if(is_null($nombre_archivo)) {
            $this->logger->info('Parametro incorrecto');
            echo '<h1>No Existe Archivo</h1>';
            return;
        }

        if( !empty($nombre_archivo) && strlen($nombre_archivo)>0 ) {

            //Gaby-Fotos-01.jpg
            $id_foto_regalo = $this->_consulta('GET_ID_FOTO_REGALO', array('nombre_archivo' => $nombre_archivo));

            if($id_foto_regalo > 0) {

                //el id(nombre archivo) que se recibió existe.

                //ahora se verifica si el usuario puede visualizar
                $se_puede_descargar = true;// $this->_consulta('ESTA_HABILITADO_FOTO_REGALO', array('cel' => $cel, 'id_promocion' => $id_promocion, 'id_foto_regalo' => $id_foto_regalo));

                if($se_puede_descargar) {

                    $path_archivo_descarga = '/var/www/html/www.entermovil.com.py/public/img/tvchat/fotos/' . $nombre_archivo;
                    $size_archivo = filesize($path_archivo_descarga);
                    $this->logger->info( 'size:[' . $size_archivo . ']' );
                    header('Content-Description: File Transfer');
                    $content_type = 'image/jpeg';
                    header('Content-Type: ' . $content_type);
                    //$nombre_contenido = basename($path_archivo_descarga);

                    header('Content-Disposition: inline; filename='.$nombre_archivo);
                    header('Content-Transfer-Encoding: binary');
                    header('Expires: 0');
                    header('Cache-Control: must-revalidate');
                    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                    header('Pragma: public');
                    header('Content-Length: ' . $size_archivo);
                    ob_clean();
                    flush();

                    $bytes_leidos = readfile($path_archivo_descarga);
                    $this->logger->info( 'bytes_leidos:[' . $bytes_leidos . ']' );
                    exit;

                } else {

                    echo '<h1>Podras descargar esta foto de regalo en tu proxima renovacion</h1>';
                    return;
                }

            } else {

                echo '<h1>Parámetro Incorrecto</h1>';
                return;
            }

            /*if( !empty( $parametros ) ){

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
            }*/
        }
    }

}

