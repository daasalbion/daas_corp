<?php

    define('CONSUMER_KEY', 'KYIoJUCLpPqeGQhxHPcTA');
    define('CONSUMER_SECRET', 'e4gVrlpWPiMmpGBVYcpp5hYx0lNPci3bvvoCpaEodg');
    define('OAUTH_CALLBACK', 'http://10.0.2.8/noticias/home');
class NoticiasController extends Zend_Controller_Action
{
    var $logger;
    var $ua;
    var $msisdn;
    var $nroCel;
    var $solicitudValida = false;
    var $tipo_dispositivo;
    var $info_promocion;
    var $info_contenido;
    var $descripciones;
    var $usuarios = array(

        'oscar' => array('clave' => 'oa2013', 'nombre' => 'OSCAR', 'id'=>'63', 'prefijo' =>'OSCAR: ','rango' =>array('inicio'=>'8','fin'=>'20')),
        'magali' => array('clave' => 'mp2013', 'nombre' => 'MAGALI', 'id'=>'64', 'prefijo' =>'MAGALI: ', 'rango' =>array('inicio'=>'8','fin'=>'19')),
        'pavon' => array('clave' => 'ep2013', 'nombre' => 'PAVON', 'id'=>'65', 'prefijo' =>'PAVON: ', 'rango' =>array('inicio'=>'8','fin'=>'19')),
        'angelito' => array('clave' => 'ao2013', 'nombre' => 'ANGELITO', 'id'=>'81', 'prefijo' =>'ANGELITO: ', 'rango' =>array('inicio'=>'8','fin'=>'19')),
        'cine' => array('clave'=>'cine2013', 'nombre' => 'CINE', 'id' => '84', 'prefijo' => 'CINE: ', 'rango' =>array('inicio'=>'8','fin'=>'19')),
        'daas' => array('clave' => 'daas', 'nombre' => 'DAAS'),
    );

    var $clavesURL = array(
        'oscar2013' => 'oscar',
        'maga2013' => 'magali',
        'pavon2013' => 'pavon',
        'angelito2013' => 'angelito',
        'cine2013' => 'cine',
    );

    public function init()
    {
        $this->_configurarLogger();

        /* Initialize action controller here */
        //$this->_helper->layout->disableLayout();
        $this->_helper->_layout->setLayout('wap-layout');

        $this->_procesarUserAgent();
        $this->logger->info('ua:[' . print_r($this->ua, true) . ']');
        $this->logger->info('xhtml_support_level:[' . $this->ua['xhtml_support_level'] . ']');

        $this->_procesarMSISDN();

        $this->logger->info('MSISDN:[' . $this->msisdn . '] cel:[' . $this->_getFormatoCorto($this->msisdn) . ']');

        $bootstrap = $this->getInvokeArg('bootstrap');
        //echo 'bootstrap' . "\n";
        $userAgent = $bootstrap->getResource('useragent');
        //print_r($userAgent);
        $device = $userAgent->getDevice();
        //si es blacberry controlo
        $this->tipo_dispositivo = strpos($_SERVER['HTTP_USER_AGENT'],'BlackBerry85');
        if($this->tipo_dispositivo === false){

            $this->tipo_dispositivo = strpos($_SERVER['HTTP_USER_AGENT'],'BlackBerry83');
            
        }
        /*        $namespace = new Zend_Session_Namespace("entermovil");
                if(!isset($namespace->usuario) || empty($namespace->usuario)) {
                    $this->_redirect('/noticias/login');
                }*/
    }

    private function _configurarLogger() {

        //Creamos nuevo Logger para Mobile (ContentMagic)
        $logger = new Zend_Log();
        $writer = new Zend_Log_Writer_Stream(APPLICATION_PATH . '/../log/www.entermovil.com.py_noticias_'.date('Y-m-d').'.log');
        $format = '%timestamp% %priorityName% - [%remoteAddr%]: %message%' . PHP_EOL;
        $formatter = new Zend_Log_Formatter_Simple($format);
        $writer->setFormatter($formatter);
        $logger->addWriter($writer);
        $logger->setEventItem('remoteAddr', $_SERVER['REMOTE_ADDR']);

        $this->logger = $logger;
    }

    public function indexAction() {

        $this->_forward('login');

        return;
    }

    public function clearsessionsAction(){

        /* Load and clear sessions */
        session_start();
        session_destroy();

        /* Redirect to page with the connect to Twitter option. */
        $this->_redirect('/noticias/connect');
    }

    public function connectAction(){

        /**
         * @file
         * Check if consumer token is set and if so send user to get a request token.
         */

        /**
         * Exit with an error message if the CONSUMER_KEY or CONSUMER_SECRET is not defined.
         */
        //require_once('config.php');
        if (CONSUMER_KEY === '' || CONSUMER_SECRET === '' || CONSUMER_KEY === 'CONSUMER_KEY_HERE' || CONSUMER_SECRET === 'CONSUMER_SECRET_HERE') {
            echo 'You need a consumer key and secret to test the sample code. Get one from <a href="https://dev.twitter.com/apps">dev.twitter.com/apps</a>';
            exit;
        }

        /* Build an image link to start the redirect process. */
        $content = '<a href="./redirect.php"><img src="./images/lighter.png" alt="Sign in with Twitter"/></a>';

        /* Include HTML to display on the page. */

    }

    public function holaAction()
    {
        $this->getResponse()
            ->setHeader('Cache-Control', 'no-cache')
            ->setHeader('Expires', '0');

        $this->_helper->viewRenderer->setNoRender(true);

        $bootstrap = $this->getInvokeArg('bootstrap');
        //echo 'bootstrap' . "\n";
        $userAgent = $bootstrap->getResource('useragent');
        //print_r($userAgent);
        $device = $userAgent->getDevice();

        //var_dump($device->hasFeature('is_mobile'));

        $header_names = array('HTTP_MSISDN', 'HTTP_X_UP_CALLING_LINE_ID', 'HTTP_X_MSISDN', 'HTTP_X_NOKIA_MSISDN');
        $nro_cel = 'NO-RECIBIDO';//En formato largo: 595 981 524 664
        $nombre_header = null;
        foreach($header_names as $header_name) {
            if(isset($_SERVER[$header_name]) && !empty($_SERVER[$header_name])) {
                $nro_cel = $_SERVER[$header_name];
                $nombre_header = $header_name;
                break;
            }
        }

        $is_mobile = ($device->hasFeature('is_mobile')) ? $device->getFeature('is_mobile') : false;
        echo '<h2>DispositivoMovil:['. ($is_mobile ? 'SI' : 'NO') .']</h2>';
        echo '<h2>'. $device->getFeature('brand_name') . ' - ' . $device->getFeature('model_name') . ' - ' . $device->getFeature('marketing_name') .'</h2>';
        echo '<h3>Navegador:['. $userAgent->getBrowserType().']</h3>';
        echo '<h3>Resolucion:['. $device->getPhysicalScreenWidth() . ' x ' . $device->getPhysicalScreenHeight() .']</h3>';
        //exit;

        $is_wireless = (bool)$device->getFeature('is_wireless_device');
        if($is_wireless) {
            echo '<h2>Dispositivo MOVIL</h2>';
            echo '<h2>'. $device->getFeature('brand_name') . ' - ' . $device->getFeature('model_name') . ' - ' . $device->getFeature('marketing_name') .'</h2>';
            echo '<h2>CEL:['. $nro_cel.'] Header:['.$nombre_header.']</h2>';
            echo '<h3>Resolucion:['. $device->getFeature('resolution_width') . 'x' . $device->getFeature('resolution_height') .']</h3>';
            echo '<h3>Pantalla:['. $device->getFeature('columns') . 'x' . $device->getFeature('rows') .']</h3>';
            echo '<h3>Soporta Wap-Push:['. ($device->getFeature('wap_push_support') == "1" ? "SI" : "NO")  .']</h3>';
            echo '<h3>xhtml_support_level:['. $device->getFeature('xhtml_support_level')  .']</h3>';
            echo '<h3>preferred_markup:['. $device->getFeature('preferred_markup')  .']</h3>';
            echo '<h3>wml_1_1:['. ($device->getFeature('wml_1_1') == "1" ? "SI" : "NO")  .']</h3>';
            echo '<h3>wml_1_2:['. ($device->getFeature('wml_1_2') == "1" ? "SI" : "NO")  .']</h3>';
            echo '<h3>wml_1_3:['. ($device->getFeature('wml_1_3') == "1" ? "SI" : "NO")  .']</h3>';
        } else {
            echo '<h2>PC-DESKTOP:[' . $device->getFeature('product_name') . ']</h2>';
        }

        echo '<pre>';
        echo ($device->getFeature('is_wireless_device')) ? 'Wireless' : 'Desktop' . "\n";
        echo $device->getFeature('brand_name') . "\n";
        echo $device->getFeature('model_name') . "\n";
        echo $device->getFeature('product_name') . "\n\n";
        echo 'resolucion:[' . $device->getFeature('resolution_width') . 'x' . $device->getFeature('resolution_height') . ']' . "\n";
        echo 'columnas x filas:[' . $device->getFeature('columns') . 'x' . $device->getFeature('rows') . ']' . "\n";
        echo 'pantalla(mm):[' . $device->getFeature('physical_screen_width') . 'x' . $device->getFeature('physical_screen_height') . ']' . "\n";
        echo '</pre>';

        //print_r($device);
        //echo trim($device->getFeature('brand_name') . ' ' . $device->getFeature('model_name') . ' ' . $device->getFeature('marketing_name') . ' ' . $device->getFeature('model_extra_info'));
        exit;
    }

    private function _getFormatoCorto($nro_largo) {

        //Verificamos que el nro recibido este en formato largo
        //Ejemplo: 595 971 200 211
        if(strlen($nro_largo) == 12 && substr($nro_largo, 0, 3) == '595') {//esta en formato largo
            return '0' . substr($nro_largo, 3);
        }

        return $nro_largo;
    }

    private function _getFormatoLargo($nro_corto, $con_signo = false) {

        //Verificamos que el nro recibido este en formato corto
        //Ejemplo: 0971 200 211
        if(strlen($nro_corto) == 10 && $nro_corto[0] == '0') {//esta en formato corto
            return ($con_signo ? '+' : '') . '595' . substr($nro_corto, 1);
        }

        return $nro_corto;
    }

    private function _procesarMSISDN() {

        $this->msisdn = null;

        //$this->logger->info('SERVER:[' . print_r($_SERVER, true) . ']');
        $header_names = array('HTTP_MSISDN', 'HTTP_X_UP_CALLING_LINE_ID', 'HTTP_X_MSISDN', 'HTTP_X_NOKIA_MSISDN');
        $nombre_header = null;
        foreach($header_names as $header_name) {
            if(isset($_SERVER[$header_name]) && !empty($_SERVER[$header_name])) {
                $this->msisdn = $_SERVER[$header_name];
                $this->nroCel = $this->_getFormatoCorto($this->msisdn);
                $nombre_header = $header_name;
                break;
            }
        }
        $this->logger->info('Header:[' . $nombre_header . ']:[' . $this->msisdn . ']');
    }

    private function _procesarUserAgent() {

        /*
         * <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
                "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
            <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
         *
         */
        $datos = array();

        $this->logger->info('UserAgent:['.$_SERVER['HTTP_USER_AGENT'] .'] Parametros:[' . print_r($this->_getAllParams(), true) . ']');

        $bootstrap = $this->getInvokeArg('bootstrap');
        $userAgent = $bootstrap->getResource('useragent');
        $device = $userAgent->getDevice();

        $this->ua = array();

        $is_mobile = ($device->hasFeature('is_mobile')) ? $device->getFeature('is_mobile') : false;
        $xhtml_support_level = $device->getFeature('xhtml_support_level');
        if($xhtml_support_level >= 0) {
            $this->_helper->_layout->setLayout('mobile-layout-level-3');
        } else {
            $this->_helper->_layout->setLayout('mobile-layout-level-wml');
        }

        $this->ua['is_mobile'] = $is_mobile;
        $this->ua['xhtml_support_level'] = $xhtml_support_level;
        $this->ua['marca'] = $device->getFeature('brand_name');
        $this->ua['modelo'] = $device->getFeature('model_name');
        $this->ua['version'] = $device->getFeature('marketing_name') . ' ' . $device->getFeature('model_extra_info');
        $this->ua['ancho'] = $device->getFeature('resolution_width');
        $this->ua['alto'] = $device->getFeature('resolution_height');
        $this->_procesarMSISDN();
        //cargamos el numero de telefono al objeto $ua
        $this->ua['cel'] = $this->_getFormatoCorto($this->msisdn);
        $this->logger->info('Telefono -> [' . $this->ua['marca'] . ' - ' . $this->ua['modelo'] . ' - ' . $this->ua['version'] . ']');
        $datos['cel'] = $this->ua['cel'];
        //obtengo el nivel de acceso a contenidos permitido
        $datos['cel'] = '0984100058';
        //$datos['cel'] = '0982313289';
/*        $nivel = array();
        $nivel = $this->_consulta('GET_SUSCRIPTO',$datos);
        $this->ua['nivel_acceso'] = $nivel['nivel'];*/
        $ancho_tabla = round((($this->ua['ancho'])*300)/320);
        if($ancho_tabla % 2 != 0){
            //para que tambien tenga resolución par
            $ancho_tabla = $ancho_tabla - 1;
        }
        $this->ua['ancho_tabla'] = $ancho_tabla;
        $this->logger->info('ANCHO-TABLA:[' . $ancho_tabla . ']');

    }

    private function _consulta($accion, $datos) {

/*        $config = new Zend_Config(array(

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
        $db->getConnection();*/

        $bootstrap = $this->getInvokeArg('bootstrap');
        $options = $bootstrap->getOptions();

        $db = Zend_Db::factory(new Zend_Config($options['resources']['db']));
        $db->getConnection();

        if($accion == 'GET_MENSAJES'){

            /*$sql = "select mensaje,id_promocion,fecha from (
                    select * from promosuscripcion.mensajes_periodicos where id_promocion = ? order by fecha desc ) as t1 where t1.fecha::date = CURRENT_DATE";*/

            $sql = "select mensaje,id_promocion,substring(fecha::varchar from 1 for 19)::timestamp as fecha, id_mensaje_periodico from (
                    select * from promosuscripcion.mensajes_periodicos where id_promocion = ? order by fecha desc ) as t1 where t1.fecha::date = CURRENT_DATE";
            $rs = $db->fetchAll($sql,array($datos['id']));

            $resultado = array();
            foreach($rs as $fila)
            {
                $resultado[] = (array) $fila;
            }
            $this->logger->info('resultado:[' . print_r($resultado, true) . ']');

            return $resultado;

        }
        if($accion == 'GET_MENSAJE'){

            $sql = "select mensaje,fecha from promosuscripcion.mensajes_periodicos where id_promocion = ? and id_mensaje_periodico = ?
                        order by fecha desc";
            $rs = $db->fetchAll($sql,array($datos['id'],$datos['id_mensaje_periodico']));

            $resultado = array();
            foreach($rs as $fila)
            {
                $resultado = (array) $fila;
            }
            $this->logger->info('resultado:[' . print_r($resultado, true) . ']');

            return $resultado;

        }
        if($accion == 'GET_ALL_MENSAJES'){

            $sql = "select mensaje,substring(fecha::varchar from 1 for 19)::timestamp as fecha_hora, substring(fecha::varchar from 1 for 10)::date as fecha from (
                    select * from promosuscripcion.mensajes_periodicos where id_promocion = ? order by fecha desc ) as t1 order by fecha desc";
            $rs = $db->fetchAll($sql,array($datos['id']));

            $resultado = array();
            foreach($rs as $fila)
            {
                $resultado[$fila['fecha']][] = (array) $fila;
            }
            $this->logger->info('resultado:[' . print_r($resultado, true) . ']');


            return $resultado;

        }
        if($accion == 'GET_MENSAJES_ANHO'){

            $sql = "select t.anho, t.cantidad from (select extract(year from fecha)::integer as anho, count(*)::integer as cantidad
                        from promosuscripcion.mensajes_periodicos
                        where id_promocion = ? group by 1 order by 1
                    ) as t where t.anho > 2000";
            $rs = $db->fetchAll($sql,array($datos['id']));

            $resultado = array();
            foreach($rs as $fila)
            {
                $resultado[] = (array) $fila;
            }
            $this->logger->info('resultado:[' . print_r($resultado, true) . ']');

            return $resultado;

        }
        if($accion == 'GET_MENSAJES_MESES'){
            $meses_nombres = array(

                '1' => 'Enero',
                '2' => 'Febrero',
                '3' => 'Marzo',
                '4' => 'Abril',
                '5' => 'Mayo',
                '6' => 'Junio',
                '7' => 'Julio',
                '8' => 'Agosto',
                '9' => 'Setiembre',
                '10' => 'Octubre',
                '11' => 'Noviembre',
                '12' => 'Diciembre',
            );

            $sql = "select extract(year from fecha)::integer as anho, extract(month from fecha)::integer as mes, count(*)::integer as cantidad
                    from promosuscripcion.mensajes_periodicos
                    where id_promocion = ? and extract(year from fecha)::integer = ?
                    group by 1,2
                    order by mes desc";

            $rs = $db->fetchAll($sql,array($datos['id'],$datos['anho']));

            $resultado = array();
            foreach($rs as $fila)
            {
                $resultado[$fila['mes']] = (array) $fila;
                $resultado[$fila['mes']]['mes'] = $meses_nombres[$fila['mes']];
                $resultado[$fila['mes']]['id'] = $fila['mes'];
            }
            $this->logger->info('resultado:[' . print_r($resultado, true) . ']');

            return $resultado;

        }
        if($accion == 'GET_MENSAJES_DIAS'){

            $dias = array(

                '0' => 'Domingo',
                '1' => 'Lunes',
                '2' => 'Martes',
                '3' => 'Miercoles',
                '4' => 'Jueves',
                '5' => 'Viernes',
                '6' => 'Sabado',
            );

            $sql = "select * from (select extract(year from fecha)::integer as anho, extract(month from fecha)::integer as mes, extract(day from fecha)::integer as dia, extract(dow from fecha)::integer as dia_semana, count(*)::integer as cantidad
                    from promosuscripcion.mensajes_periodicos
                    where id_promocion = ? and extract(year from fecha)::integer = ?
                    group by 1,2,3,4
                    order by 1,2,3) as t1 where mes = ? order by dia desc";

            $rs = $db->fetchAll($sql,array($datos['id'],$datos['anho'],$datos['mes']));

            $resultado = array();
            foreach($rs as $fila)
            {
                $resultado[$fila['dia']] = (array) $fila;
                $resultado[$fila['dia']]['dia_semana'] = $dias[$fila['dia_semana']];


            }
            $this->logger->info('resultado:[' . print_r($resultado, true) . ']');

            return $resultado;

        }
        if($accion == 'GET_MENSAJES_DEL_DIA'){


            $sql = "select mensaje,id_promocion,substring(fecha::varchar from 1 for 19)::timestamp as fecha from (
                    select * from promosuscripcion.mensajes_periodicos where id_promocion = ? order by fecha desc ) as t1 where t1.fecha::date = ? order by fecha desc";

            $rs = $db->fetchAll($sql,array($datos['id'],$datos['fecha']));

            $resultado = array();
            foreach($rs as $fila)
            {
                $resultado[] = (array) $fila;

            }
            $this->logger->info('resultado:[' . print_r($resultado, true) . ']');

            return $resultado;

        }
        if($accion == 'GET_MENSAJES_MES'){

            $fecha = $datos['mes'];
            $sql = "select mensaje, substring(fecha::varchar from 1 for 19)::timestamp as fecha from promosuscripcion.mensajes_periodicos where fecha::date between '".$fecha."-01'::date and ('".$fecha."-01'::date + interval '1 month' - interval '1 day') and id_promocion = ? order by fecha desc";
            $rs = $db->fetchAll($sql,array($datos['id']));

            $resultado = array();
            foreach($rs as $fila)
            {
                $resultado[$fila['fecha']][] = (array) $fila;
            }
            $this->logger->info('resultado:[' . print_r($resultado, true) . ']');

            return $resultado;

        }
        if($accion == 'INSERT_MENSAJES'){

            $data = array(

                'mensaje' => $datos['mensaje'],
                'id_promocion' => $datos['id'],
                'fecha' => 'now',

            );
            $db->insert('promosuscripcion.mensajes_periodicos',$data);
            return;

        }
        if($accion == 'ACTUALIZAR_MENSAJE'){

            $data = array(

                'mensaje' => $datos['mensaje'],
            );
            $where = array(

                'id_promocion = ?' => $datos['id'],
                'id_mensaje_periodico = ?' => $datos['id_mensaje_periodico'],
            );

            $n = $db->update('promosuscripcion.mensajes_periodicos',$data,$where);
            return;
        }
        if($accion == 'ELIMINAR_MENSAJE'){

            $where = array(

                'id_mensaje_periodico = ?' => array($datos['id_mensaje_periodico']),
            );

            $n = $db->delete('promosuscripcion.mensajes_periodicos',$where);
            return;

        }
    }

    public function loginAction() {

        $this->view->headLink()->setStylesheet('/css/reportes_base.css', 'screen');
        $this->view->headLink()->appendStylesheet('/css/acceso.css', 'screen');
        $this->view->headScript()->appendFile('/js/acceso.js', 'text/javascript');
        $this->view->ua = $this->ua;
        $this->_helper->_layout->setLayout('noticias-layout');

        $form = new Application_Form_Login();
        if($this->getRequest()->isPost()) {

            $formData = $this->getRequest()->getPost();

            if($form->isValid($formData)){

                $nick = $form->getValue('login_user');
                $clave = $form->getValue('login_pass');

                if(!empty($nick) && !empty($clave)) {

                    if(array_key_exists($nick, $this->usuarios) && $clave == $this->usuarios[$nick]['clave']) {

                        $this->logger->info('LOGIN:[' . $nick . ']');
                        $namespace = new Zend_Session_Namespace("entermovil-noticias");
                        $namespace->usuario = $nick;
                        $namespace->nombre = $this->usuarios[$nick]['nombre'];
                        $namespace->id= $this->usuarios[$nick]['id'];
                        $namespace->prefijo = $this->usuarios[$nick]['prefijo'];
                        $namespace->accesos = array(
                            'FULL'
                        );
                        if ($this->tipo_dispositivo !== false) {

                            $this->_redirect('/noticias/homeb');
                        }

                        $this->_redirect('/noticias/home');

                    } else {

                        $this->_redirect('/noticias/login');
                    }

                } else {

                    $this->_redirect('/noticias/login');
                }

            } else {

                $this->_redirect('/noticias/login');
            }

        } else {

            $clave = $this->_getParam('clave', null);
            if(!is_null($clave)) {

                $clave = trim($clave);
                if(array_key_exists($clave, $this->clavesURL)) {

                    $nick = $this->clavesURL[$clave];
                    $this->logger->info('LOGIN-X-URL:[' . $nick . ']');
                    $namespace = new Zend_Session_Namespace("entermovil-noticias");
                    $namespace->usuario = $nick;
                    $namespace->nombre = $this->usuarios[$nick]['nombre'];
                    $namespace->id= $this->usuarios[$nick]['id'];
                    $namespace->prefijo = $this->usuarios[$nick]['prefijo'];
                    $namespace->accesos = array(
                        'FULL'
                    );
                    if ($this->tipo_dispositivo !== false) {

                        $this->_forward('homeb', 'noticias');
                        //$this->_redirect('/noticias/homeb');
                    }else{

                        $this->_forward('home', 'noticias');
                    }

                }
            }
        }
    }

    public function logoutAction() {

        $namespace = new Zend_Session_Namespace("entermovil-noticias");
        $this->logger->info('LOGOUT:[' . ( isset($namespace->usuario) ? $namespace->usuario : '')  . ']('.$namespace->nombre.')');

        unset($namespace->usuario);
        unset($namespace->nombre);

        $namespace->unsetAll();
        unset($namespace);

        $this->_redirect('/noticias/login');
    }
    //comienza noticias
    public function homeAction(){

        $this->_helper->_layout->setLayout('noticias-layout');
        $this->view->ua = $this->ua;

        $namespace = new Zend_Session_Namespace("entermovil-noticias");
        if(!isset($namespace->usuario) || empty($namespace->usuario)) {
            $this->_redirect('/noticias/login');
        }
        $datos = array(

            'id'=> $namespace->id,
        );
        $sms = $this->_consulta('GET_MENSAJES',$datos);

        foreach($sms as $mensaje){
            //no se porque mierda no anda ver despues
            $enviar = substr($mensaje['fecha'],10,9);
            $mensaje['fecha'] = $enviar;
        }

        $this->view->sms  = $sms;
    }

    public function homebAction(){

        $this->view->ua = $this->ua;

        $namespace = new Zend_Session_Namespace("entermovil-noticias");
        if(!isset($namespace->usuario) || empty($namespace->usuario)) {
            $this->_redirect('/noticias/login');
        }
        $datos = array(

            'id'=> $namespace->id,
        );
        $sms = $this->_consulta('GET_MENSAJES',$datos);

        $this->view->sms  = $sms;
    }

    public function textoAction(){

        $namespace = new Zend_Session_Namespace("entermovil-noticias");
        if(!isset($namespace->usuario) || empty($namespace->usuario)) {
            $this->_redirect('/noticias/login');
        }
        $this->view->ua = $this->ua;
        $this->_helper->_layout->setLayout('noticias-layout');
        $parametro = $this->_getParam('id',null);
        //si la opcion es modificar
        $tiempo_ahora = date('H');

        //if($tiempo_ahora <= $this->usuarios[$namespace->usuario]['rango']['fin'] && $tiempo_ahora >= $this->usuarios[$namespace->usuario]['rango']['inicio']){

            if(!is_null($parametro)){

                $datos = array(

                    'id'=> $namespace->id,
                    'id_mensaje_periodico' => $parametro,
                );
                $mensaje = $this->_consulta('GET_MENSAJE',$datos);
                $mensaje['mensaje'] = substr($mensaje['mensaje'],strlen($namespace->prefijo));
                $this->view->sms = $mensaje['mensaje'];
                $this->view->id_sms = $parametro;
                $this->view->disponible = true;
                $this->view->nombre_form = 'formModificarMensaje';

            }else{
                $this->view->nombre_form = 'formEnviarMensaje';
                $this->view->disponible = true;
            }

        /*}else{

            $this->view->disponible = false;
        }*/

    }

    public function textobAction(){

        $namespace = new Zend_Session_Namespace("entermovil-noticias");
        if(!isset($namespace->usuario) || empty($namespace->usuario)) {
            $this->_redirect('/noticias/login');
        }
        $this->view->ua = $this->ua;
        $parametro = $this->_getParam('id',null);
        if(!is_null($parametro)){

            $datos = array(

                'id'=> $namespace->id,
                'id_mensaje_periodico' => $parametro,
            );
            $mensaje = $this->_consulta('GET_MENSAJE',$datos);
            $mensaje['mensaje'] = substr($mensaje['mensaje'],strlen($namespace->prefijo));
            $this->view->sms = $mensaje['mensaje'];
            $this->view->id_sms = $parametro;
            $this->view->caracteres = 150 - strlen($mensaje['mensaje']);
        }
    }

    public function insertarAction(){

        if($this->getRequest()->isPost()) {

            $formData = $this->getRequest()->getPost();

            $namespace = new Zend_Session_Namespace("entermovil-noticias");
            if(!isset($namespace->usuario) || empty($namespace->usuario)) {

                $this->_redirect('/noticias/login');
            }
            //elimnino los espacios en blanco al inicio
            $formData['mensaje'] = trim($formData['mensaje']);
            $caracteres_no_validos = array('á','Á','é','É','í','Í','ó','Ó','ú','Ú','ñ','Ñ','ü','Ü','¿');
            $caracteres_validos = array('a','A','e','E','i','I','o','O','u','U','n','N','u','U','');
            $formData['mensaje'] = str_replace($caracteres_no_validos,$caracteres_validos,$formData['mensaje']);

            $formData['mensaje'] = $namespace->prefijo . $formData['mensaje'];

            $longitud = strlen($formData['mensaje']);
            //print_r($formData['mensaje']);

            if($longitud<= 160){

                $datos = array(

                    'id'=> $namespace->id,
                    'mensaje' => $formData['mensaje'],
                );

                $this->_consulta('INSERT_MENSAJES',$datos);

                if ($this->tipo_dispositivo !== false) {

                    $this->_redirect("/noticias/homeb");
                }else{

                    $this->_redirect("/noticias/home");
                }
            }else{
                //espero que nunca entre ak
                if ($this->tipo_dispositivo !== false) {

                    $this->_redirect("/noticias/homeb");
                }else{

                    $this->_redirect("/noticias/home");
                }
            }
        }
    }

    public function enviarMensajeAction(){

        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $respuesta = array(
            'status' => 'ERROR',
            'message' => 'ERROR'
        );

        if($this->getRequest()->isPost()) {

            $formData = $this->getRequest()->getPost();

            $namespace = new Zend_Session_Namespace("entermovil-noticias");
            if(!isset($namespace->usuario) || empty($namespace->usuario)) {

                $this->_redirect('/noticias/login');
            }
            //elimnino los espacios en blanco al inicio
            $formData['mensaje'] = trim($formData['mensaje']);
            $caracteres_no_validos = array('á','Á','é','É','í','Í','ó','Ó','ú','Ú','ñ','Ñ','ü','Ü','¿');
            $caracteres_validos = array('a','A','e','E','i','I','o','O','u','U','n','N','u','U','');
            $formData['mensaje'] = str_replace($caracteres_no_validos,$caracteres_validos,$formData['mensaje']);

            $formData['mensaje'] = $namespace->prefijo . $formData['mensaje'];

            $longitud = strlen($formData['mensaje']);
            //print_r($formData['mensaje']);

            if($longitud<= 160){

                $datos = array(

                    'id'=> $namespace->id,
                    'mensaje' => $formData['mensaje'],
                );

                $this->_consulta('INSERT_MENSAJES',$datos);

                $respuesta = array(
                    'status' => 'OK',
                    'message' => 'Mensaje Enviado'
                );


            }else{

                $respuesta = array(
                    'status' => 'ERROR',
                    'message' => 'Longitud Excedida'
                );
            }
        }

        $this->getResponse()->setHeader('Content-type', 'application/json; charset=UTF-8', true);
        $this->getResponse()->setBody(json_encode($respuesta));
    }

    public function modificarMensajeAction(){

        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        if($this->getRequest()->isPost()) {

            $formData = $this->getRequest()->getPost();

            $namespace = new Zend_Session_Namespace("entermovil-noticias");
            if(!isset($namespace->usuario) || empty($namespace->usuario)) {

                $this->_redirect('/noticias/login');
            }
            //elimnino los espacios en blanco al inicio
            $formData['mensaje'] = trim($formData['mensaje']);
            $caracteres_no_validos = array('á','Á','é','É','í','Í','ó','Ó','ú','Ú','ñ','Ñ','ü','Ü','¿');
            $caracteres_validos = array('a','A','e','E','i','I','o','O','u','U','n','N','u','U','');
            $formData['mensaje'] = str_replace($caracteres_no_validos,$caracteres_validos,$formData['mensaje']);

            $formData['mensaje'] = $namespace->prefijo . $formData['mensaje'];

            $longitud = strlen($formData['mensaje']);

            if($longitud<= 160){

                $datos = array(

                    'id'=> $namespace->id,
                    'mensaje' => $formData['mensaje'],
                    'id_mensaje_periodico' =>$formData['id_mensaje'],
                );

                $this->_consulta('ACTUALIZAR_MENSAJE',$datos);

                if ($this->tipo_dispositivo !== false) {

                    $this->_redirect('/noticias/homeb');
                }
                $this->_redirect('/noticias/home');

            }else{
                if ($this->tipo_dispositivo !== false) {

                    $this->_redirect('/noticias/homeb');
                }
                $this->_redirect('/noticias/home');
            }
        }
    }

    public function eliminarMensajeAction(){

        $parametro = $this->_getParam('id',null);
        //print_r($parametro);

        if(!is_null($parametro)){

           $datos = array(

               'id_mensaje_periodico' => $parametro,
           );
           $this->_consulta('ELIMINAR_MENSAJE',$datos);
        }
        if ($this->tipo_dispositivo !== false) {

            $this->_redirect("/noticias/homeb");
        }else{

            $this->_redirect("/noticias/home");
        }
    }

    private function _limpia_espacios($cadena){
        $cadena = str_replace(' ', '', $cadena);
        return $cadena;
    }

    public function logeoTwitterAction(){

        //configuracion
        $config = array
        (
            "requestTokenURL" => "http://twitter.com/oauth/request_token",
            "requestTokenMethod" => "POST",
            "signatureMethod" => "HMAC-SHA1",
            "consumerKey" => "KYIoJUCLpPqeGQhxHPcTA",
            "consumerSecret" => "e4gVrlpWPiMmpGBVYcpp5hYx0lNPci3bvvoCpaEodg"
        );

        $requestParameters = array
        (
            "oauth_consumer_key" => $config['consumerKey'],
            "oauth_nonce" => md5(time()),
            "oauth_signature_method" => $config['signatureMethod'],
            "oauth_timestamp" => time()
        );
        //de nuevo ya me hace la api de twitter
        $signature= 'POST&http%3A%2F%2Ftwitter.com%2Foauth%2Frequest_token&oauth_consumer_key%3DKYIoJUCLpPqeGQhxHPcTA%26oauth_nonce%3Dcaaa5966d6b66df5546f7645bfe89e16%26oauth_signature_method%3DHMAC-SHA1%26oauth_timestamp%3D1374077086%26oauth_token%3D164893250-pOdr7YP0LWh0nPEiY5hUcDT0zSHtf0SC0H4N1Dgg%26oauth_version%3D1.0';
        $authorizationHeader= 'Authorization: OAuth oauth_consumer_key="KYIoJUCLpPqeGQhxHPcTA", oauth_nonce="caaa5966d6b66df5546f7645bfe89e16", oauth_signature="kYVqs3DGjopUurZHvWRr1ng6q5c%3D", oauth_signature_method="HMAC-SHA1", oauth_timestamp="1374077086", oauth_token="164893250-pOdr7YP0LWh0nPEiY5hUcDT0zSHtf0SC0H4N1Dgg", oauth_version="1.0"';

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_POST, TRUE);
        curl_setopt($curl, CURLOPT_URL, $config['requestTokenURL']);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array($authorizationHeader, "Expect:"));
        $response = curl_exec($curl);
        printf("\nEsta es la respuesta\n");
        printf("\n(%s)\n",$response);
        $access_token = '164893250-pOdr7YP0LWh0nPEiY5hUcDT0zSHtf0SC0H4N1Dgg';
        $access_token_secret = 'eNiOO59e2Bs9EX5xd8z1eUZRxFBolZhxOIUZHlrtTFY';
        $this->_redirect('https://api.twitter.com/oauth/authenticate?oauth_token=' .$access_token);

    }

    private function _rfc3986_encode($string)
    {
        $result = rawurlencode($string);
        $result = str_replace('%7E', '~', $result);
        $result = str_replace('=', '%3D', $result);
        $result = str_replace('+', '%2B', $result);

        return $result;
    }

    public function jmobileAction(){

        $this->view->ua = $this->ua;
        $namespace = new Zend_Session_Namespace("entermovil-noticias");
        $datos = array(

            'id'=> $namespace->id,
        );

        $this->view->sms  = $this->_consulta('GET_MENSAJES',$datos);


    }

    public function mensajesAction(){

        $this->_helper->_layout->setLayout('noticias-layout');
        $namespace = new Zend_Session_Namespace("entermovil-noticias");
        if(!isset($namespace->usuario) || empty($namespace->usuario)) {
            $this->_redirect('/noticias/login');
        }

        $datos = array(

            'id'=> $namespace->id,
        );

        $sms = $this->_consulta('GET_MENSAJES_ANHO',$datos);
        $this->view->sms = $sms;
    }

    public function mensajesAnhoAction(){

        $this->_helper->_layout->setLayout('noticias-layout');
        $namespace = new Zend_Session_Namespace("entermovil-noticias");
        if(!isset($namespace->usuario) || empty($namespace->usuario)) {
            $this->_redirect('/noticias/login');
        }
        $anho = $this->_getParam('anho', null);

        if(!is_null($anho)){

            $this->view->anho = $anho;
            $datos = array(

                'id'=> $namespace->id,
                'anho'=>$anho,
            );
            $sms = $this->_consulta('GET_MENSAJES_MESES',$datos);
            $this->view->sms = $sms;
            /*print_r($sms);*/
            $this->view->anho = $anho;

        }
    }

    public function mensajesMesAction(){

        $this->_helper->_layout->setLayout('noticias-layout');
        $namespace = new Zend_Session_Namespace("entermovil-noticias");
        if(!isset($namespace->usuario) || empty($namespace->usuario)) {
            $this->_redirect('/noticias/login');
        }

        $dias = array(

            '0' => 'Domingo',
            '1' => 'Lunes',
            '2' => 'Martes',
            '3' => 'Miercoles',
            '4' => 'Jueves',
            '5' => 'Viernes',
            '6' => 'Sabado',
        );
        $meses_nombres = array(

            '1' => 'Enero',
            '2' => 'Febrero',
            '3' => 'Marzo',
            '4' => 'Abril',
            '5' => 'Mayo',
            '6' => 'Junio',
            '7' => 'Julio',
            '8' => 'Agosto',
            '9' => 'Setiembre',
            '10' => 'Octubre',
            '11' => 'Noviembre',
            '12' => 'Diciembre',
        );
        $mes = $this->_getAllParams('anho','mes', null);

        if(!is_null($mes)){

            /*if(!isset($mes['anho'])) {
                $mes['anho'] = date('Y');
            }
            if(!isset($mes['mes'])) {
                $mes['mes'] = date('n');
            }*/
            $this->view->mes = $mes['anho'].' - '.$meses_nombres[$mes['mes']];
            $datos = array(

                'id'=> $namespace->id,
                'anho'=>$mes['anho'],
                'mes'=>$mes['mes'],

            );
            $sms = $this->_consulta('GET_MENSAJES_DIAS',$datos);
            $this->view->sms = $sms;
            $this->view->anho = $mes['anho'];
        }
    }

    public function mensajesDiaAction(){

        $this->_helper->_layout->setLayout('noticias-layout');
        $namespace = new Zend_Session_Namespace("entermovil-noticias");
        if(!isset($namespace->usuario) || empty($namespace->usuario)) {
            $this->_redirect('/noticias/login');
        }
        $dias = array(

            '0' => 'Domingo',
            '1' => 'Lunes',
            '2' => 'Martes',
            '3' => 'Miercoles',
            '4' => 'Jueves',
            '5' => 'Viernes',
            '6' => 'Sabado',
        );
        $meses_nombres = array(

            '1' => 'Enero',
            '2' => 'Febrero',
            '3' => 'Marzo',
            '4' => 'Abril',
            '5' => 'Mayo',
            '6' => 'Junio',
            '7' => 'Julio',
            '8' => 'Agosto',
            '9' => 'Setiembre',
            '10' => 'Octubre',
            '11' => 'Noviembre',
            '12' => 'Diciembre',
        );
        $fecha = $this->_getAllParams('anho','mes','dia', null);

        if($fecha['mes'] < 10){
            $fecha['mes'] = '0'.$fecha['mes'];
        }
        if($fecha['dia'] < 10){
            $fecha['dia'] = '0'.$fecha['dia'];
        }

        $fecha_completa = $fecha['anho'].'-' . $fecha['mes'] .'-'.$fecha['dia'];

        if(!is_null($fecha)){

            $this->view->mes = $fecha_completa;
            $datos = array(

                'id'=> $namespace->id,
                'fecha'=> $fecha_completa,

            );
            $sms = $this->_consulta('GET_MENSAJES_DEL_DIA',$datos);
            $this->view->sms = $sms;
            $this->view->nro_anho = $fecha['anho'];
            $this->view->nro_mes = (int)$fecha['mes'];

        }
    }
}
