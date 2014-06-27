<?php

class MobileController extends Zend_Controller_Action
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
        $this->_helper->layout->disableLayout();

        $this->_procesarUserAgent();
        $this->logger->info('ua:[' . print_r($this->ua, true) . ']');
        $this->logger->info('xhtml_support_level:[' . $this->ua['xhtml_support_level'] . ']');

        $this->_procesarMSISDN();
        $this->logger->info('MSISDN:[' . $this->msisdn . '] cel:[' . $this->_getFormatoCorto($this->msisdn) . ']');

        $this->descripciones = array(
            42 => 'Canciones Inéditas',
            39 => 'Servicio de Prueba'
        );
    }

    private function _configurarLogger() {

        //Creamos nuevo Logger para Mobile (ContentMagic)
        $logger = new Zend_Log();
        $writer = new Zend_Log_Writer_Stream(APPLICATION_PATH . '/../log/www.entermovil.com.py_mobile_'.date('Y-m-d').'.log');
        $format = '%timestamp% %priorityName% - [%remoteAddr%]: %message%' . PHP_EOL;
        $formatter = new Zend_Log_Formatter_Simple($format);
        $writer->setFormatter($formatter);
        $logger->addWriter($writer);
        $logger->setEventItem('remoteAddr', $_SERVER['REMOTE_ADDR']);

        $this->logger = $logger;
    }

    public function pruebaAction() {

        $this->_helper->viewRenderer->setNoRender(true);

        echo '<pre>';

        //$hash_hex_aes128 = '84e737d298d020ee2099c04f8b60ca02';
        //$hash_base64_aes128 = '0//8NbG7g2l5Z+LKhp7vGuJVFsPE/QF/3S1WldZTKk//AVRTb2R0Ks7dheLfrRcV';

        $cipher = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
        $iv_size = mcrypt_enc_get_iv_size($cipher);
        printf("iv_size = %d\n",$iv_size);

        //$key256 = '12345678901234561234567890123456';
        $key128 = 'Cont3ntM4gic+NtRm0v1L';
        $iv     = '1234567890123456';

        printf("iv: %s\n",bin2hex($iv));
        //printf("key256: %s\n",bin2hex($key256));
        printf("key128: %s\n",bin2hex($key128));

        $cleartext = '5510103504|42';//'The quick brown fox jumped over the lazy dog';
        printf("plainText: %s\n\n",$cleartext);

        if (mcrypt_generic_init($cipher, $key128, $iv) != -1)
        {
            // PHP pads with NULL bytes if $cleartext is not a multiple of the block size..
            $cipherText = mcrypt_generic($cipher,$cleartext );
            mcrypt_generic_deinit($cipher);

            // Display the result in hex.
            printf("128-bit encrypted result:\n%s\n\n",bin2hex($cipherText));

            //printf("128-bit encrypted result(base64):\n%s\n\n",base64_encode($cipherText));

            printf("postgresql:\n%s\n\n", '84e737d298d020ee2099c04f8b60ca02');//"2c63986510860bbf0baf257e79fa4e34d4002c487588c3d27cf18341fb2af12c6b63c2bcbb5ced644c64679a82b84881");

            $desencriptado = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key128, $cipherText, MCRYPT_MODE_CBC, $iv);
            printf("128-bit des-encrypted result:\n%s\n\n",$desencriptado);
        }



        echo '</pre>';

        exit;
    }

    public function indexAction()
    {

        $this->getResponse()
            ->setHeader('Cache-Control', 'no-cache')
            ->setHeader('Expires', '0');

        $this->logger->info('UserAgent:[' . $_SERVER['HTTP_USER_AGENT'] . ']');


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
        //Ejemplo: 52 - 5510103504
        if(strlen($nro_largo) == 12 && substr($nro_largo, 0, 2) == '52') {//esta en formato largo
            return substr($nro_largo, 2);
        }

        return $nro_largo;
    }

    private function _getFormatoLargo($nro_corto, $con_signo = false) {

        //Verificamos que el nro recibido este en formato corto
        //Ejemplo: 55 1010 3504
        if(strlen($nro_corto) == 10) {//esta en formato corto
            return ($con_signo ? '+' : '') . '52' . $nro_corto;
        }

        return $nro_corto;
    }

    private function _procesarMSISDN() {

        $this->msisdn = null;

        //$this->logger->info('SERVER:[' . print_r($_SERVER, true) . ']');

        if(isset($_SERVER['HTTP_X_NOKIA_MSISDN'])) {
            $this->logger->info('HTTP_X_NOKIA_MSISDN[' . $_SERVER['HTTP_X_NOKIA_MSISDN'] . ']');
            $this->msisdn = $_SERVER['HTTP_X_NOKIA_MSISDN'];
            $this->nroCel = $this->_getFormatoCorto($this->msisdn);

        } else {
            $this->logger->err('NO-SE-RECIBIO-HEADER!!!!');
            $this->msisdn = null;

            $this->msisdn = '525510103504';
            $this->nroCel = $this->_getFormatoCorto($this->msisdn);
            $this->logger->err('SE-ASIGNA-MSISDN-PRUEBA:[]');
        }
    }

    private function _procesarUserAgent() {

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

        $this->logger->info('Telefono -> [' . $this->ua['marca'] . ' - ' . $this->ua['modelo'] . ' - ' . $this->ua['version'] . ']');

        $this->_cargarImagenes();
    }

    private function _cargarImagenes() {

        require_once APPLICATION_PATH . '/models/phMagick.php';

        $nombre_logo_original_jpg = 'img/mobile/que_pachoo_w1920px.jpg';
        $this->logger->info('LOGO-ORIGINAL:[' . $nombre_logo_original_jpg . ']');

        $nombre_logo_max_ancho_jpg = 'img/mobile/cache/que_pachoo_w' . $this->ua['ancho'] . 'px.jpg';
        $this->logger->info('LOGO-PERSONALIZADO:[' . $nombre_logo_max_ancho_jpg . ']');

        if(!file_exists($nombre_logo_max_ancho_jpg)) {

            $this->logger->info("LOGO-PERSONALIZADO NO Existe en Cache...");
            //Generamos Imagen
            $phMagick = &new phMagick($nombre_logo_original_jpg, $nombre_logo_max_ancho_jpg);
            $phMagick->resize($this->ua['ancho']);
            list($ancho_calculado, $alto_calculado) = $phMagick->getDimentions();
            $this->logger->info("LOGO-PERSONALIZADO -> Dimensiones:[". $ancho_calculado ." x " . $alto_calculado ."]");

        } else {
            $this->logger->info("Logo YA EXISTE en Cache");
        }

        $this->ua['imagen_logo'] = $nombre_logo_max_ancho_jpg;

    }

    private function _consulta($accion, $datos) {

        $bootstrap = $this->getInvokeArg('bootstrap');
        $options = $bootstrap->getOptions();

        $db = Zend_Db::factory(new Zend_Config($options['resources']['db']));
        $db->getConnection();

        if($accion == 'GET_INFO_CONTENIDO') {
            $sql = "SELECT * FROM promosuscripcion.mensajes_periodicos WHERE id_mensaje_periodico = ?";
            $rs = $db->fetchRow($sql, array(
                $datos['id_contenido']
            ));
            $resultado = array();
            if($rs) {
                $resultado = (array) $rs;
                $this->logger->info('resultado:[' . print_r($resultado, true) . ']');
            }

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
            $sql = "SELECT * FROM promosuscripcion.suscriptos WHERE cel = ? AND id_promocion = ? AND id_carrier = ?";
            $rs = $db->fetchRow($sql, array(
                $datos['cel'], $datos['id_promocion'], $datos['id_carrier']
            ));
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

    private function _procesarAlta() {

        $parametros = array(
            'cel' => $this->nroCel,
            'id_promocion' => $this->info_promocion['id_promocion'],
            'id_carrier' => 7
        );

        //Verificar existencia de Usuario. Si ya está suscripto
        $suscripto = $this->_consulta('GET_SUSCRIPTO', $parametros);
        $this->logger->info('suscripto:[' . print_r($suscripto, true) . ']');
        if(empty($suscripto)) {
            $this->logger->info('SUSCRIPTO-VACIO -> Usuario No esta suscripto');
            $statusInsertarSuscripto = $this->_consulta('INSERTAR_SUSCRIPTO', $parametros);
            $this->logger->info('INSERTAR_SUSCRIPTO -> status:[' . $statusInsertarSuscripto . ']');

            $respuesta = 'Suscripción Exitosa';

        } else {

            $this->logger->info('USUARIO-YA-ESTA-SUSCRIPTO');

            $respuesta = 'Tu suscripción a ' . $this->info_promocion['alias'] . ' está activa';

        }

        return $respuesta;

    }

    private function _procesarBaja() {
        //
    }

    private function _procesarDescarga() {
        //
    }

    public function cmAction() {

        $this->_helper->viewRenderer->setNoRender(true);


    }


    public function cmDescargaAction() {

        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        if($this->solicitudValida) {

            //echo 'info_promocion:[' . print_r($this->info_contenido, true) . ']';
            //exit;

            $path_archivo_descarga = "/var/opt/konecta/promociones_mms/promo_" . $this->info_contenido['id_promocion'] . "/" . $this->info_contenido['mensaje'];
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
            $this->logger->info('bytes_leios:[' . $bytes_leidos .']');
            exit;

        } else {
            $this->logger->err('Solicitu NO-VALIDA');
        }
    }

    //$prueba = pack('H*', $auth);
    /*private function hex2bin($data) {
        $len = strlen($data);
        $newdata = null;
        for($i=0;$i<$len;$i+=2) {
            $newdata .= pack("C",hexdec(substr($data,$i,2)));
        }
        return $newdata;
    }*/

    private function _procesarAuth($auth) {

        $this->logger->info('Procesando auth:[' . $auth . ']');

        $cipher = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
        $iv_size = mcrypt_enc_get_iv_size($cipher);
        $this->logger->info(sprintf("iv_size = %d\n",$iv_size));

        $key128 = 'Cont3ntM4gic+NtRm0v1L';
        $iv     = '1234567890123456';

        $desencriptado = null;

        if (mcrypt_generic_init($cipher, $key128, $iv) != -1)
        {
            mcrypt_generic_deinit($cipher);
            $desencriptado = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key128, pack('H*', $auth), MCRYPT_MODE_CBC, $iv);
            $this->logger->info(sprintf("AUTH-DESENCRIPTADO:[%s]",$desencriptado));
        }

        return $desencriptado;
    }



    public function preDispatch() {

        //$controller = $this->getRequest()->getControllerName();
        $action = $this->getRequest()->getActionName();
        $this->logger->info('Accion:[' . $action . ']');
        if($action == 'cm-alta') {

            $auth = $this->_getParam('auth', null);
            if(is_null($auth)) {
                $this->logger->info('Auth NO-DEFINIDO!!');
                throw new Zend_Exception("Parámetros No Válidos");
                return;
            }

            $this->logger->info('Auth:[' . $auth . ']');
            $parametros_desencriptados = $this->_procesarAuth($auth);
            $this->logger->info('Parametros Desencriptados:[' . $parametros_desencriptados . ']');
            list($cel, $id_promocion) = explode('|', $parametros_desencriptados);
            $this->logger->info('cel:[' . $cel . '] id_promocion:[' . $id_promocion .']');

            if($id_promocion > 0) {
                $this->logger->info('id_promocion:[' . $id_promocion . '] OK');
                if($cel == $this->_getFormatoCorto($this->msisdn)) {

                    $this->logger->info('cel:[' . $cel . '] OK');
                    $this->info_promocion = $this->_consulta('GET_INFO_PROMOCION', array(
                        'id_carrier' => 7,
                        'id_promocion' => $id_promocion
                    ));
                    $servicio = $this->_getParam('servicio', null);
                    if(!is_null($servicio) && $this->info_promocion['alias'] == $servicio) {
                        $this->logger->info('alias:[' . $servicio . '] OK');
                        $this->solicitudValida = true;

                        $this->_setParam('cel', $cel);
                        $this->_setParam('id_promocion', $id_promocion);

                    } else {
                        $this->logger->err('alias ERROR');
                    }
                } else {
                    $this->logger->err('cel ERROR');
                }
            } else {
                $this->logger->err('id_promocion ERROR');
            }

        } else if($action == 'cm-descarga') {

            $auth = $this->_getParam('auth', null);
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
                    $contenido = $this->_getParam('contenido', null);
                    if(!is_null($contenido) && $this->info_contenido['mensaje'] == $contenido) {//no se modifico el nombre del contenido a descargar
                        $this->logger->info('contenido:[' . $contenido . '] OK');
                        $this->solicitudValida = true;

                        $this->_setParam('cel', $cel);
                        $this->_setParam('id_promocion', $id_promocion);
                        $this->_setParam('id_contenido', $id_contenido);

                    } else {
                        $this->logger->err('alias ERROR');
                    }
                } else {
                    $this->logger->err('cel ERROR');
                }
            } else {
                $this->logger->err('id_promocion ERROR');
            }
        }

        $this->logger->info('Accion:[' . $action . ']');
    }



    public function cmAltaAction() {

        $this->_helper->viewRenderer->setNoRender(true);

        //$archivo_vista = 'alta-wml';

        if($this->solicitudValida) {

            $respuesta = $this->_procesarAlta();

            if($this->ua['xhtml_support_level'] >= 0) {

                $this->logger->info('HTML');
                $archivo_vista = 'alta-level-3';

                $this->view->ancho_tabla = $this->ua['ancho'];

            } else {

                $this->logger->info('WML');
                $archivo_vista = 'alta-wml';

            }

            $this->view->proveedor = '¿quepachoo?';
            $this->view->servicio = $this->info_promocion['alias'];
            $this->view->descripcion = $this->descripciones[$this->info_promocion['id_promocion']];
            $this->view->mensaje = $respuesta;

            $this->view->ua = $this->ua;

            $this->render($archivo_vista);

        } else {
            $this->logger->err('Solicitu NO-VALIDA');
        }


    }


}