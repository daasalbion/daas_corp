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

        //agregado para el wap
        /*$r = new Zend_Controller_Router_Route(
            'PORTAL',
            array(
                'controller' => 'wap',
                'action' => 'index',
                'alias' => 'PORTAL'
            )
        );
        $router->addRoute('servicio_portal_wap', $r);*/

        //De la forma: www.entermovil.com.py/ws/tigo/guatemala/SubscribeToService
        $r = new Zend_Controller_Router_Route(
            'smsfwstatus/tigo/sv/:request_id/:status',
            array(
                'controller' => 'waptwo',
                'action' => 'tigowapfwsv',
                'id_carrier' => 11
            )
        );
        $router->addRoute('tigo-sv-confirmacion-alta', $r);

        $r = new Zend_Controller_Router_Route(
            'oneapi/userprofile/v1/notifySubscription',
            array(
                'controller' => 'index',
                'action' => 'oneapi',
                'comando' => 'Subscription'
            )
        );
        $router->addRoute('oneapi_webservices_subscribe', $r);

        $r = new Zend_Controller_Router_Route(
            'oneapi/userprofile/v1/notifyUnsubscription',
            array(
                'controller' => 'index',
                'action' => 'oneapi',
                'comando' => 'Unsubscription'
            )
        );
        $router->addRoute('oneapi_webservices_unsubscribe', $r);

        $r = new Zend_Controller_Router_Route(
            'PORTAL',
            array(
                'controller' => 'utilidades',
                'action' => 'detectar',
                'alias' => 'PORTAL',
                'redireccionar' => 'PORTAL',
            )
        );
        $router->addRoute('servicio_portal_wap', $r);


        //PORTAL -> Categoria VIDEOS
        $r = new Zend_Controller_Router_Route(
            'PORTAL/Videos',
            array(
                'controller' => 'wap',
                'action' => 'videos'
            )
        );
        $router->addRoute('servicio_portal_wap_videos', $r);

        //PORTAL -> Categoria IMAGENES
        $r = new Zend_Controller_Router_Route(
            'PORTAL/Imagenes',
            array(
                'controller' => 'wap',
                'action' => 'imagenes'
            )
        );
        $router->addRoute('servicio_portal_wap_imagenes', $r);

        //PORTAL -> Categoria AUDIOS
        $r = new Zend_Controller_Router_Route(
            'PORTAL/Audios',
            array(
                'controller' => 'wap',
                'action' => 'audios'
            )
        );
        $router->addRoute('servicio_portal_wap_audios', $r);



        //PATRON
        $r = new Zend_Controller_Router_Route(
            'PATRON',
            array(
                'controller' => 'utilidades',
                'action' => 'detectar',
                'alias' => 'PATRON',
                'redireccionar' => 'PATRON',
            )
        );
        $router->addRoute('servicio_patron_home', $r);

        //Suscripcion PORTAL - GUATEMALA
        //http://entermovil.com.py/gt/alta/PORTAL
        $r = new Zend_Controller_Router_Route(
            'gt/alta/PORTAL',
            array(
                'controller' => 'utilidades',
                'action' => 'detectar',
                'alias' => 'PORTAL',
                'redireccionar' => 'gt/alta/PORTAL'
            )
        );
        $router->addRoute('alta_portal_gt', $r);

        //Suscripcion PORTAL - COLOMBIA
        //http://entermovil.com.py/co/alta/PORTAL
        $r = new Zend_Controller_Router_Route(
            'co/alta/PORTAL',
            array(
                'controller' => 'utilidades',
                'action' => 'detectar',
                'alias' => 'PORTAL',
                'redireccionar' => 'co/alta/PORTAL'
            )
        );
        $router->addRoute('alta_portal_co', $r);

        //Suscripcion PORTAL - EL SALVADOR
        //http://entermovil.com.py/sv/alta/PORTAL
        $r = new Zend_Controller_Router_Route(
            'sv/alta/PORTAL',
            array(
                'controller' => 'utilidades',
                'action' => 'detectar',
                'alias' => 'PORTAL',
                'redireccionar' => 'sv/alta/PORTAL'
            )
        );
        $router->addRoute('alta_portal_sv', $r);

        //Suscripcion PORTAL - PARAGUAY
        //http://entermovil.com.py/activar/PORTAL
        $r = new Zend_Controller_Router_Route(
            'activar/PORTAL',
            array(
                'controller' => 'utilidades',
                'action' => 'detectar',
                'alias' => 'PORTAL',
                'redireccionar' => '/activar/PORTAL'
            )
        );
        $router->addRoute('activar_portal_py_sms', $r);

        //Suscripcion PORTAL - PARAGUAY
        //http://entermovil.com.py/B1/PORTAL
        $r = new Zend_Controller_Router_Route(
            'B1/PORTAL',
            array(
                'controller' => 'utilidades',
                'action' => 'detectar',
                'alias' => 'PORTAL',
                'redireccionar' => '/B1/PORTAL'
            )
        );
        $router->addRoute('activar_portal_py_banner', $r);

        //Agregado para pruebas Derlis
        //http://entermovil.com.py/RE/PORTAL
        $r = new Zend_Controller_Router_Route(
            'prueba/TIGO-SV',
            array(
                'controller' => 'utilidades',
                'action' => 'detectar',
                'alias' => 'PORTAL',
                'redireccionar' => 'PRUEBA'
            )
        );
        $router->addRoute('prueba_portal_elsalvador', $r);

        $r = new Zend_Controller_Router_Route(
            'prueba/TIGO-CO',
            array(
                'controller' => 'utilidades',
                'action' => 'detectar',
                'alias' => 'PORTAL',
                'redireccionar' => 'PRUEBA'
            )
        );
        $router->addRoute('prueba_portal_colombia', $r);


        //Suscripcion PATRON - PARAGUAY
        //http://entermovil.com.py/activar/PATRON
        $r = new Zend_Controller_Router_Route(
            'activar/PATRON',
            array(
                'controller' => 'utilidades',
                'action' => 'detectar',
                'alias' => 'PATRON',
                'redireccionar' => '/activar/PATRON',
            )
        );
        $router->addRoute('activar_patron_py', $r);


        $r = new Zend_Controller_Router_Route(
            'oa2013',
            array(
                'controller' => 'noticias',
                'action' => 'login',
                'clave' => 'oscar2013'
            )
        );
        $router->addRoute('servicio_noticias_oscar', $r);

        $r = new Zend_Controller_Router_Route(
            'mp2013',
            array(
                'controller' => 'noticias',
                'action' => 'login',
                'clave' => 'maga2013'
            )
        );
        $router->addRoute('servicio_noticias_magali', $r);

        $r = new Zend_Controller_Router_Route(
            'ep2013',
            array(
                'controller' => 'noticias',
                'action' => 'login',
                'clave' => 'pavon2013'
            )
        );
        $router->addRoute('servicio_noticias_pavon', $r);

        $r = new Zend_Controller_Router_Route(
            'ao2013',
            array(
                'controller' => 'noticias',
                'action' => 'login',
                'clave' => 'angelito2013'
            )
        );
        $router->addRoute('servicio_noticias_angelito', $r);

        //agregue
        $r = new Zend_Controller_Router_Route(
            'cine2013',
            array(
                'controller' => 'noticias',
                'action' => 'login',
                'clave' => 'cine2013'
            )
        );
        $router->addRoute('servicio_noticias_cine', $r);


        $r = new Zend_Controller_Router_Route(
            'Noticias',
            array(
                'controller' => 'noticias',
                'action' => 'login'
            )
        );
        $router->addRoute('servicio_noticias_login', $r);

        $r = new Zend_Controller_Router_Route(
            'RadioDisney',
            array(
                'controller' => 'index',
                'action' => 'mensajero-disney'
            )
        );
        $router->addRoute('servicio_RadioDisney', $r);

        $r = new Zend_Controller_Router_Route(
            'RadioPop',
            array(
                'controller' => 'index',
                'action' => 'mensajero-disney'
            )
        );
        $router->addRoute('servicio_RadioPop', $r);

        $r = new Zend_Controller_Router_Route(
            'RadioFarra',
            array(
                'controller' => 'index',
                'action' => 'mensajero-disney'
            )
        );
        $router->addRoute('servicio_RadioFarra', $r);

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

        $r = new Zend_Controller_Router_Route(
            'EventoAplicacionTelcelMexico',
            array(
                'controller' => 'evento',
                'action' => 'enviar',
                'hash' => '2365b3577481864e3e6ea21a87a19708',
                'origen' => 'TelcelMexico'
            )
        );
        $router->addRoute('enviar_mensaje_telcel_mexico', $r);


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
            'regalo-afortunado/:id',
            array(
                'controller' => 'tvchat',
                'action' => 'descargar',
                'accion' => null
            )
        );
        $router->addRoute('regalo-afortunado', $r);

        $r = new Zend_Controller_Router_Route(
            'regalo-sexy/:id',
            array(
                'controller' => 'tvchat',
                'action' => 'descargarsexy',
                'accion' => null
            )
        );
        $router->addRoute('regalo-sexy', $r);

        $r = new Zend_Controller_Router_Route(
            'regalos-sexy',
            array(
                'controller' => 'tvchat',
                'action' => 'fotos'
            )
        );
        $router->addRoute('regalo-sexy-2', $r);

        $r = new Zend_Controller_Router_Route(
            'chatcenter',
            array(
                'controller' => 'tvchat',
                'action' => 'chatcenter',
                'accion' => null
            )
        );
        $router->addRoute('chatcenter', $r);

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

        $r = new Zend_Controller_Router_Route(
            'chat',
            array(
                'controller' => 'index',
                'action' => 'chatcenter',
            )
        );
        $router->addRoute('demo_chat', $r);

    }

    protected function _initDoctype()
    {
        $this->bootstrap('view');
        $view = $this->getResource('view');

        $view->doctype('XHTML1_STRICT');
        $view->headMeta()->appendHttpEquiv('Content-Type', 'text/html;charset=utf-8');
        $view->headTitle()->setSeparator(' - ');
        $view->headTitle('ENTERMOVIL');

        $view->headScript()->setFile('/js/plugins/jquery-1.8.0.min.js', 'text/javascript');

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

