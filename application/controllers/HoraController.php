<?php

class HoraController extends Zend_Controller_Action
{

    var $logger;
    var $ua;
    var $msisdn;
    var $nroCel;
    var $solicitudValida = false;
    var $info_promocion;
    var $info_contenido;
    var $descripciones;

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

    }

    private function _configurarLogger() {

        //Creamos nuevo Logger para Mobile (ContentMagic)
        $logger = new Zend_Log();
        $writer = new Zend_Log_Writer_Stream(APPLICATION_PATH . '/../log/www.entermovil.com.py_wap_'.date('Y-m-d').'.log');
        $format = '%timestamp% %priorityName% - [%remoteAddr%]: %message%' . PHP_EOL;
        $formatter = new Zend_Log_Formatter_Simple($format);
        $writer->setFormatter($formatter);
        $logger->addWriter($writer);
        $logger->setEventItem('remoteAddr', $_SERVER['REMOTE_ADDR']);

        $this->logger = $logger;
    }

    public function indexAction() {
        $this->_forward('home');
        return;
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

    public function descargarAction() {

        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $this->solicitudValida = false;

        $auth = $this->_getParam('k', null);
        if(is_null($auth)) {
            $this->logger->info('Auth NO-DEFINIDO!!');
            throw new Zend_Exception("Parámetros No Válidos");
            return;
        }

        $this->logger->info('Auth:[' . $auth . ']');
        $parametros_desencriptados = $this->_procesarAuth($auth);
        $this->logger->info('Parametros Desencriptados:[' . $parametros_desencriptados . ']');
        list($cel, $id_contenido, $id_promocion) = explode('|', $parametros_desencriptados);
        $this->logger->info('cel:[' . $cel . '] id_contenido:['.$id_contenido.'] id_promocion:[' . $id_promocion .']');

        if($id_promocion > 0) {

            $this->logger->info('id_promocion:[' . $id_promocion . '] OK');
            if($cel == $this->_getFormatoCorto($this->msisdn)) {
                $this->logger->info('cel:[' . $cel . '] OK');

                $this->info_contenido = $this->_consulta('GET_INFO_CONTENIDO', array(
                    'id_contenido' => $id_contenido
                ));

                $this->logger->info('contenido:[' . $this->info_contenido['mensaje'] . '] OK');
                $this->solicitudValida = true;

                //cel, id_promocion, id_contenido
                $info_audio = $this->_consulta('GET_AUDIO_CONTENIDO', array(
                    'id_contenido' => $id_contenido,
                    'id_promocion' => $id_promocion
                ));
                $this->logger->info('info_audio:[' . print_r($info_audio, true) . ']');

                $path_archivo_descarga = APPLICATION_PATH . "/../data/audio-ingles/" . $info_audio['audio'];
                $this->logger->info('path_archivo:[' . $path_archivo_descarga . ']');

                $size_archivo = filesize($path_archivo_descarga);

                header('Content-Description: File Transfer');
                //header('Content-Type: application/octet-stream');
                $content_type = 'audio/mpeg';
                header('Content-Type: ' . $content_type);
                //header('Content-Disposition: attachment; filename='.$this->info_contenido['mensaje']);
                header('Content-Disposition: inline; filename='.$this->info_contenido['mensaje']);
                header('Content-Transfer-Encoding: binary');
                header('X-Pad: avoid browser bug');
                header('Expires: 0');
                //header('Cache-Control: must-revalidate');
                header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                header('Pragma: public');
                header('Content-Length: ' . $size_archivo);
                ob_clean();
                flush();

                $bytes_leidos = readfile($path_archivo_descarga);
                $this->logger->info('bytes_leidos:[' . $bytes_leidos .']');
                exit;
            }
        }
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
;
        //obtengo el nivel de acceso a contenidos permitido
        //$datos['cel'] = '0984100058';
        //$datos['cel'] = '0982313289';
        $datos['cel'] = $this->ua['cel'];
        //$this->ua['cel']= $datos['cel'];
        $nivel = array();
        $nivel = $this->_consulta('GET_SUSCRIPTO',$datos);
        $this->ua['nivel_acceso'] = $nivel['nivel'];

        $this->_cargarImagenes();
    }

    private function _cargarImagenes() {

        require_once APPLICATION_PATH . '/models/phMagick.php';
        //ubicacion del logo de entermovil
        //$nombre_logo_original_jpg = 'img/wap-imagenes/wap_logo_entermovil_w455x84px.jpg';
        $nombre_logo_original_jpg = 'img/wap-imagenes/wap-logo-entermovil-w1024x188px.jpg';
        $this->logger->info('LOGO-ORIGINAL:[' . $nombre_logo_original_jpg . ']');
        //empezamos a calcular
        //calculo del ancho de la imagen a desplegar
        //rediseño el ancho de la imagen para redefinir su tamaño
        //tengo que rediseñar el con un ancho de 1024
        $ancho_logo = round($this->ua['ancho']*0.96875);
        if($ancho_logo % 2 != 0){
            //me aseguro que tenga resolucion par
            $ancho_logo = $ancho_logo - 1;
        }
        if($this->ua['ancho'] < 240){
            //le quito 4px del scroll lateral derecho
            $ancho_logo = $ancho_logo - 4;
        }
        $this->logger->info('ANCHO-LOGO:[' . $ancho_logo . ']');
        $this->ua['ancho_fondo'] = $ancho_logo;
        $nombre_logo_max_ancho_jpg = 'img/wap-imagenes/cache/logo_entermovil_w' . $ancho_logo . 'px.jpg';
        $this->logger->info('LOGO-PERSONALIZADO:[' . $nombre_logo_max_ancho_jpg . ']');

        if(!file_exists($nombre_logo_max_ancho_jpg)) {
            $this->logger->info("LOGO-PERSONALIZADO NO Existe en Cache...");
            //Generamos Imagen
            $phMagick = &new phMagick($nombre_logo_original_jpg, $nombre_logo_max_ancho_jpg);
            $phMagick->resize($ancho_logo);

            list($ancho_calculado, $alto_calculado) = $phMagick->getDimentions();
            $this->logger->info("LOGO-PERSONALIZADO -> Dimensiones:[". $ancho_calculado ." x " . $alto_calculado ."]");

        } else {
            $this->logger->info("Logo YA EXISTE en Cache");
        }

        $this->ua['imagen_logo'] = $nombre_logo_max_ancho_jpg; //envio

        //calculamos el ancho de la tabla blanca
        $ancho_tabla = round((($this->ua['ancho'])*300)/320);
        if($ancho_tabla % 2 != 0){
            //para que tambien tenga resolución par
            $ancho_tabla = $ancho_tabla - 1;
        }
        $this->ua['ancho_tabla'] = $ancho_tabla;
        $this->logger->info('ANCHO-TABLA:[' . $ancho_tabla . ']');

        $datos = array(
            'nivel_acceso'=> $this->ua['nivel_acceso'],
        );
        $contenidos = $this->_consulta('GET_CONTENIDO', $datos);
        $this->ua['contenidos'] = $contenidos;

    }

    private function _consulta($accion, $datos) {

        $config = new Zend_Config(array(
            'database' => array(
                'adapter' => 'Pdo_Pgsql',
                'params'  => array(
                    'host'     => '190.128.201.42',//
                    'username' => 'postgres',
                    'password' => '',
                    'dbname'   => 'gw'
                )
            )
        ));

        $db = Zend_Db::factory($config->database);
        $db->getConnection();

        if($accion == 'GET_CATEGORIAS'){

            $sql = "select id_categoria, id_categoria_padre, nombre_categoria from wap.categoria where estado = 1 order by id_categoria ASC";
            $rs = $db->fetchAll($sql);

            $resultado = array();
            foreach($rs as $fila)
            {
                $resultado[] = (array) $fila;

            }
            $this->logger->info('resultado:[' . print_r($resultado, true) . ']');

            return $resultado;

        }

        if($accion == 'GET_CONTENIDO') {

            $sql = "select * from hora.hora_contenidos where nivel <= ? order by nivel desc";

            $rs = $db->fetchAll($sql, array( $datos['nivel_acceso']));
            foreach($rs as $fila)
            {
                $resultado[] = (array) $fila;

            }
            $this->logger->info('resultado:[' . print_r($resultado, true) . ']');

            return $resultado;
        }

        if($accion == 'GET_INFO_PROMOCION') {
            $sql = "SELECT * FROM info_promociones WHERE id_carrier = ? AND id_promocion = ?";
            $rs = $db->fetchRow($sql, array(
                $datos['id_carrier'], $datos['id_promocion']
            ));
            $resultado = array();
            if($rs) {
                $resultado = (array) $rs;
                $this->logger->info('resultado:[' . print_r($resultado, true) . ']');
            }

            return $resultado;
        }

        if($accion == 'GET_SUSCRIPTO') {

            $sql = "SELECT * FROM hora.hora_usuarios WHERE cel = ?";
            $rs = $db->fetchRow($sql, array($datos['cel']));
            $resultado = array();
            if($rs) {
                $resultado = (array) $rs;
                $this->logger->info('resultado:[' . print_r($resultado, true) . ']');
            }

            return $resultado;
        }

        if($accion == 'INSERTAR_SUSCRIPTO') {

            $status = $db->insert('promosuscripcion.suscriptos', $datos);
            //$this->logger->info('INSERTAR_SUSCRIPTO -> status:[' . $status . ']');
            return $status;
        }
    }

    //comienza wap hora
    public function homeAction(){

        $this->_helper->_layout->setLayout('hora-layout');
        $this->view->ua = $this->ua;
        $contenidos = $this->ua['contenidos'];
        $resultado = array();
        foreach($contenidos as $fila){

            if($fila['nivel'] == $this->ua['nivel_acceso'] ){

                $resultado['nuevos'][] = $fila;
            }
        }
        $this->view->contenidos = $resultado;
    }
    public function contenidosAction(){

        $this->_helper->_layout->setLayout('hora-layout');
        $this->view->ua = $this->ua;
        $res = $this->ua['contenidos'];
        $page=$this->_getParam('page',1);
        $paginator = Zend_Paginator::factory($res);
        $paginator->setItemCountPerPage(4);
        $paginator->setCurrentPageNumber($page);
        $paginator->setPageRange(4);
        $this->view->paginator=$paginator;

    }

}
