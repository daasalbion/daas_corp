<?php

class ComportamientoController extends Zend_Controller_Action{

    public $logger;
    var $usuarios = array(

        'daas' => array('clave' => 'daas', 'nombre' => 'DAAS')

    );

    public function init(){
        /* Initialize action controller here */
        $this->logger = $this->getLog();
        $writer = new Zend_Log_Writer_Stream(APPLICATION_PATH . '/../log/comportamiento_'.date('Y-m-d').'.log');
        $format = '%timestamp% %priorityName% - [%remoteAddr%]: %message%' . PHP_EOL;
        $formatter = new Zend_Log_Formatter_Simple($format);
        $writer->setFormatter($formatter);
        $this->logger->addWriter($writer);
        $this->logger->setEventItem('remoteAddr', $_SERVER['REMOTE_ADDR']);

        //Habilitar layout
        $this->_helper->_layout->setLayout('comportamiento-layout');

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
        $this->_forward('home');
    }

    public function loginAction() {

        $this->logger->info("login");
        $this->_helper->layout->disableLayout();

        $form = new Application_Form_Login();

        if( $this->getRequest()->isPost() ) {

            $formData = $this->getRequest()->getPost();

            if( $form->isValid( $formData ) ){

                $nick = $form->getValue('login_user');
                $clave = $form->getValue('login_pass');

                if( !empty( $nick ) && !empty( $clave ) ) {

                    if( array_key_exists($nick, $this->usuarios) && $clave == $this->usuarios[$nick]['clave'] ) {

                        $this->logger->info( 'LOGIN:[' . $nick . ']' );
                        $namespace = new Zend_Session_Namespace( "entermovil-reporte-integradores" );
                        $namespace->usuario = $nick;
                        $namespace->nombre = $this->usuarios[$nick]['nombre'];
                        $namespace->id_carrier = $this->usuarios[$nick]['id_carrier'];
                        $namespace->numeros = $this->numeros[$namespace->id_carrier];

                        $namespace->accesos = array(
                            'FULL'
                        );

                        $this->_redirect('/reporte-integradores/resumen/');

                    } else {

                        $this->_redirect('/reporte-integradores/login');
                    }

                } else {

                    $this->_redirect('/reporte-integradores/login');
                }

            } else {

                $this->_redirect('/reporte-integradores/login');
            }
        }
    }

    public function logoutAction() {

        $namespace = new Zend_Session_Namespace("entermovil-reporte-integradores");
        $this->logger->info('logout:[' . ( isset($namespace->usuario) ? $namespace->usuario : '') . ']('.$namespace->nombre.')');

        unset($namespace->usuario);
        unset($namespace->nombre);

        $namespace->unsetAll();
        unset($namespace);

        $this->_redirect('/reporte-integradores/login');
    }

    public function homeAction(){

        $this->view->headTitle()->append('Home');
    }

    private function _consulta( $accion, $datos = null ){

        $bootstrap = $this->getInvokeArg('bootstrap');
        $config = new Zend_Config(array(
            'database' => array(
                'adapter' => 'Pdo_Pgsql',
                'params'  => array(
                    'host'     => '10.0.2.8',
                    'username' => 'konectagw',
                    'password' => 'konectagw2006',
                    'dbname'   => 'gw'
                )
            )
        ));

        $db = Zend_Db::factory($config->database);
        $db->getConnection();
        $resultado = null;

        $namespace = new Zend_Session_Namespace("entermovil-reporte-integradores");

        if( $accion == 'GET_COBROS_MES' ){

            $sql = "select STR.fecha, STR.source_address, STR.command_status, STR.total as cantidad
                    from smpp_tx_resumen STR
                    where extract(year from fecha)::integer = ? and extract(month from fecha)::integer = ?
                    and id_carrier = ? and STR.source_address like '%@%' and STR.command_status = 0";

            $rs = $db->fetchAll( $sql, array( $datos['anho'], $datos['mes'], $datos['id_carrier'] ) );

            if( !empty( $rs ) ){

                foreach( $rs as $fila ){

                    $resultado[] = (array)$fila;
                }
            }
        }
        else if( $accion == 'GET_REPORTE_X_DIA' ){

            $sql = "select STR.fecha, STR.source_address, STR.command_status, STR.total as cantidad
                    from smpp_tx_resumen STR
                    where id_carrier = ? and fecha = ?";

            $rs = $db->fetchAll( $sql, array( $datos['id_carrier'], $datos['fecha'] ) );

            if( !empty( $rs ) ){

                foreach( $rs as $fila ){

                    $resultado[] = (array)$fila;
                }
            }
        }
        else if( $accion == 'GET_CSV_MES' ){

            $sql = "select ts_submit, source_address, destination_address, service_type, command_status,
                    coalesce( message_id, 'null') as message_id
                    from smpp_tx
                    where id_carrier = ? and extract(year from ts_submit) = ? and extract(month from ts_submit) = ?
                    order by 1,2,3";

            $rs = $db->fetchAll( $sql, array( $datos['id_carrier'], $datos['anho'], $datos['mes'] ) );

            if( !empty( $rs ) ){

                foreach( $rs as $fila ){

                    $resultado[] = (array)$fila;
                }
            }
        }

        $db->closeConnection();

        return $resultado;
    }

}

