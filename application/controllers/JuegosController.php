<?php

class JuegosController extends Zend_Controller_Action{

    public $logger;

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

        //Deshabilitar layout y vista
        $this->_helper->layout->disableLayout();

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
        $this->_forward('juegos');
    }

    public function juegosAction(){

        $this->logger->info("juegos");
    }

    public function tragamonedasAction(){

        $this->logger->info("tragamonedas");
    }

    public function ruletaAction(){

        $this->logger->info("ruleta");
    }

    public function ruleta2Action(){

        $this->logger->info("ruleta");
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

        if ( ( $_GET['solicitud'] == true ) and ( isset( $_GET['id_mensaje'] ) ) ){

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
        }
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

