<?php

class AuthController extends Zend_Controller_Action
{

    var $log;
    public function init()
    {
        /* Initialize action controller here */
        /* Initialize action controller here */
        $this->log = $this->getLog();
        if($this->log) {
            $this->log->info('IndexController -> Log inicializado');
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
        $this->_forward('login');
    }

    public function accesoAction() {

        $this->_helper->layout->disableLayout();
        $this->view->headLink()->setStylesheet('/css/reportes_base.css', 'screen');
        $this->view->headLink()->appendStylesheet('/css/acceso.css', 'screen');
        $this->view->headScript()->appendFile('/js/acceso.js', 'text/javascript');
    }

    public function logoutAction() {

        $namespace = new Zend_Session_Namespace("entermovil");
        $this->log->info('LOGOUT:[' . ( isset($namespace->usuario) ? $namespace->usuario : '')  . ']('.$namespace->nombre.')');
        
        unset($namespace->usuario);
        unset($namespace->nombre);

        $namespace->unsetAll();
        unset($namespace);

        $this->_redirect('/acceso');
    }

    public function loginAction() {

        $this->view->headLink()->setStylesheet('/css/reportes_base.css', 'screen');
        $this->view->headLink()->appendStylesheet('/css/acceso.css', 'screen');
        $this->view->headScript()->appendFile('/js/acceso.js', 'text/javascript');

        $form = new Application_Form_Login();
        if($this->getRequest()->isPost()) {

            $formData = $this->getRequest()->getPost();
            //$this->log->info('formData:[' . print_r($formData, true) . ']');

            if($form->isValid($formData)) {

                $nick = $form->getValue('login_user');
                $clave = $form->getValue('login_pass');

                if(!empty($nick) && !empty($clave)) {

                    $usuarios = array(
                        'david' => array('clave' => 'david', 'nombre' => 'David Villalba'),
                        'e' => array('clave' => '965', 'nombre' => 'Ezequiel García'),
                        'felix' => array('clave' => 'ovelar212940', 'nombre' => 'Félix Ovelar'),
                        'daas' => array('clave' => 'daas', 'nombre' => 'Derlis Arguello'),
                        'redaccion' => array('clave' => 'enter4589', 'nombre' => 'Redaccion Entermovil'),
                        'linda' => array('clave' => 'poletti', 'nombre' => 'Linda Poletti'),
                        'entermovil' => array('clave' => 'enter4589', 'nombre' => 'Entermovil S.A.'),
                        'content' => array('clave' => 'magic', 'nombre' => 'Content Magic'),
                        'disney' => array('clave' => '965', 'nombre' => 'Radio Disney'),
                    );

                    if(array_key_exists($nick, $usuarios) && $clave == $usuarios[$nick]['clave']) {

                        $this->log->info('LOGIN:[' . $nick . ']');

                        $namespace = new Zend_Session_Namespace("entermovil");
                        $namespace->usuario = $nick;
                        $namespace->nombre = $usuarios[$nick]['nombre'];

                        if($nick == 'redaccion') {
                            $namespace->accesos = array(
                                'contenidos-nuevo'
                            );

                            $this->_redirect('/reportes/contenidos-nuevo');

                        } else if( $nick == 'linda' ){
                            $namespace->accesos = array(
                                'informe-pautas'
                            );

                            $this->_redirect('/reportes/informe-pautas');

                        } else if( $nick == 'content' ){
                            $namespace->accesos = array(
                                'resumen-cobros'
                            );

                            $namespace->id_pais = 5;

                            $this->_redirect('/reportes/resumen-cobros');

                        } else if( $nick == 'disney' ){
                            $namespace->accesos = array(
                                'cobros-por-carrier'
                            );

                            $namespace->numeros = array('965');

                            $this->_redirect('/reportes/cobros-por-carrier');

                        } else {

                            $namespace->accesos = array(
                                'FULL'
                            );

                            $this->_redirect('/reportes/resumen-cobros');
                        }

                    } else {

                        $this->_redirect('/acceso');
                    }

                } else {

                    $this->_redirect('/acceso');
                }

            } else {

                $this->_redirect('/acceso');
            }
        }
    }


    public function callcenterAction() {

        $this->_helper->_layout->setLayout('callcenter-layout');

        $this->view->headLink()->setStylesheet('/css/base.css', 'screen');

        $this->view->headScript()->appendFile('/js/modernizr-2.0.6.js', 'text/javascript');
        $this->view->headScript()->appendFile('/js/jquery.placeholder.min.js', 'text/javascript');
        $this->view->headScript()->appendFile('/js/base.js', 'text/javascript');
        $this->view->headScript()->appendFile('/js/acceso.js', 'text/javascript');

        $this->view->headLink()->appendStylesheet('/css/acceso-contacto.css', 'screen');

        $this->_helper->viewRenderer('acceso-callcenter');

        $form = new Application_Form_Login();
        if($this->getRequest()->isPost()) {

            $formData = $this->getRequest()->getPost();
            //$this->log->info('formData:[' . print_r($formData, true) . ']');

            if($form->isValid($formData)) {

                $nick = $form->getValue('login_user');
                $clave = $form->getValue('login_pass');

                if(!empty($nick) && !empty($clave)) {

                    $usuarios = array(
                        'david' => array('clave' => 'david', 'nombre' => 'David Villalba'),
                        'e' => array('clave' => '965', 'nombre' => 'Ezequiel García'),
                        'fo' => array('clave' => 'velar', 'nombre' => 'Félix Ovelar'),//, 'prefijo' => '098'),
                        'tigo' => array('clave' => 'w1J5873', 'nombre' => 'Callcenter TIGO', 'id_carrier' => 2),
                        'personal' => array('clave' => '7q236Rd', 'nombre' => 'Callcenter PERSONAL', 'id_carrier' => 1),
                        'tigogt' => array('clave' => 'T1G0g7', 'nombre' => 'Callcenter TigoGT', 'id_carrier' => 5)

                    );

                    if(array_key_exists($nick, $usuarios) && $clave == $usuarios[$nick]['clave']) {

                        $this->log->info('LOGIN:[' . $nick . ']');

                        $namespace = new Zend_Session_Namespace("entermovil_callcenter");
                        $namespace->usuario = $nick;
                        $namespace->nombre = $usuarios[$nick]['nombre'];
                        if(isset($usuarios[$nick]['id_carrier'])) {//Es un usuario de una operadora
                            //Solo debe poder consultar lineas correspondientes a su operadora
                            $namespace->id_carrier = $usuarios[$nick]['id_carrier'];
                        }

                        $this->_redirect('/callcenter');

                    } else {

                        $this->_redirect('/auth/callcenter');
                    }

                } else {

                    $this->_redirect('/auth/callcenter');
                }

            } else {

                $this->_redirect('/auth/callcenter');
            }
        }
    }

    public function callcenterLogoutAction() {

        $namespace = new Zend_Session_Namespace("entermovil_callcenter");
        $this->log->info('LOGOUT:[' . ( isset($namespace->usuario) ? $namespace->usuario : '')  . ']('.$namespace->nombre.')');

        unset($namespace->usuario);
        unset($namespace->nombre);

        $namespace->unsetAll();
        unset($namespace);

        $this->_redirect('/CallCenter');
    }


}

