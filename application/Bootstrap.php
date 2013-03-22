<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
    protected function _initAutoLoad() {

        $moduleLoader = new Zend_Application_Module_Autoloader(array(
                'namespace' => '',
                'basePath' => APPLICATION_PATH
            ));
        return $moduleLoader;
    }

    protected function _initConstantes() {

        /*$opciones = $this->getOptions();

        print_r($opciones['contact']);
        exit;*/

        //define('DIRECTORIO_RESOURCES', $opciones['directorios']['resources']);
        //define('DIRECTORIO_UPLOAD', $opciones['directorios']['upload']);

    }

    protected function _initRuteo() {

        $this->bootstrap('FrontController');
        $front = $this->frontController;

        $router = $front->getRouter();

        $r = new Zend_Controller_Router_Route(
            'OK',
            array(
                'controller' => 'index',
                'action' => 'banner-estatico'
            )
        );
        $router->addRoute('servicio_OK', $r);

        $r = new Zend_Controller_Router_Route(
            'EventoAplicacion',
            array(
                'controller' => 'evento',
                'action' => 'enviar',
                'hash' => '2365b3577481864e3e6ea21a87a19708'
            )
        );
        $router->addRoute('enviar_mensaje', $r);

        //Webservices
        //De la forma: www.entermovil.com.py/ws/tigo/guatemala/SubscribeToService
        $r = new Zend_Controller_Router_Route(
            'ws/:operadora/:pais/:accion/*',
            array(
                'controller' => 'webservices',
                'action' => 'index'
            )
        );
        $router->addRoute('tigo-guatemala', $r);

        $r = new Zend_Controller_Router_Route(
            'ws/:operadora/:pais/',
            array(
                'controller' => 'webservices',
                'action' => 'index',
                'accion' => null
            )
        );
        $router->addRoute('tigo-guatemala-sin-accion', $r);


        $r = new Zend_Controller_Router_Route(
            'login',
            array(
                'controller' => 'auth',
                'action' => 'login'
            )
        );
        $router->addRoute('auth_login', $r);

        $r = new Zend_Controller_Router_Route(
            'logout',
            array(
                'controller' => 'auth',
                'action' => 'logout'
            )
        );
        $router->addRoute('auth_logout', $r);

        //webmail
        $r = new Zend_Controller_Router_Route(
            'webmail',
            array(
                'controller' => 'index',
                'action' => 'webmail'
            )
        );
        $router->addRoute('webmail_route', $r);

        //nueva-web-beta
        $r = new Zend_Controller_Router_Route(
            'NuevaWebBeta',
            array(
                'controller' => 'index',
                'action' => 'nuevo'
            )
        );
        $router->addRoute('nueva_web_route', $r);

        //acceso-contacto
        $r = new Zend_Controller_Router_Route(
            'acceso',
            array(
                'controller' => 'index',
                'action' => 'acceso-contacto'
            )
        );
        $router->addRoute('acceso-contacto_route', $r);

        //Q'Somos
        $r = new Zend_Controller_Router_Route(
            'QSomos',
            array(
                'controller' => 'index',
                'action' => 'q-somos'
            )
        );
        $router->addRoute('qsomos_route', $r);

        //Q'Creamos
        $r = new Zend_Controller_Router_Route(
            'QCreamos',
            array(
                'controller' => 'index',
                'action' => 'q-creamos'
            )
        );
        $router->addRoute('qcreamos_route', $r);

        //CallCenter
        $r = new Zend_Controller_Router_Route(
            'CallCenter',
            array(
                'controller' => 'auth',
                'action' => 'callcenter'
            )
        );
        $router->addRoute('callcenter_route', $r);

        //CallCenter/Salir
        $r = new Zend_Controller_Router_Route(
            'SalirCallCenter',
            array(
                'controller' => 'auth',
                'action' => 'callcenter-logout'
            )
        );
        $router->addRoute('callcentersalir_route', $r);

        //Servicios
        $r = new Zend_Controller_Router_Route(
            'Servicios',
            array(
                'controller' => 'index',
                'action' => 'servicios'
            )
        );
        $router->addRoute('callcenter_servicios', $r);



    }

    protected function _initDoctype()
    {
        $this->bootstrap('view');
        $view = $this->getResource('view');

        $view->doctype('XHTML1_STRICT');
        $view->headMeta()->appendHttpEquiv('Content-Type', 'text/html;charset=utf-8');
        $view->headTitle()->setSeparator(' - ');
        $view->headTitle('ENTERMOVIL');

        $view->headScript()->setFile('/js/jquery-1.7.min.js', 'text/javascript');

        //$view->headScript()->appendFile('/js/base.js', 'text/javascript');
        //$view->headLink()->setStylesheet('/css/base.css', 'screen');

    }

    protected function _initLogger() {

        $this->bootstrap('FrontController');

        $logger = new Zend_Log();
        $writer = new Zend_Log_Writer_Stream(APPLICATION_PATH . '/../log/www.entermovil.com.py_'.date('Y-m-d').'.log');

        $format = '%timestamp% %priorityName% - [%remoteAddr%]: %message%' . PHP_EOL;
        $formatter = new Zend_Log_Formatter_Simple($format);

        $writer->setFormatter($formatter);
        $logger->addWriter($writer);

        $logger->setEventItem('remoteAddr', $_SERVER['REMOTE_ADDR']);

        $logger->info('initLogger()');

        //Zend_Registry::set('logger', $logger);
        //Zend_Registry::set('Zend_Log', $logger);
        //Zend_Registry::set('Log', $logger);



        return $logger;
    }
}

