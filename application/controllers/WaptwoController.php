<?php

class WaptwoController extends Zend_Controller_Action
{
    var $logger;
    var $ua = array();
    var $msisdn;
    var $nroCel = null;
    var $solicitudValida = false;
    var $info_usuario; //toda la info del usuario
    var $contenidos; //todos los contenidos
    var $formato;//formato a desplegar
    var $info_contenido;
    var $preview;
    var $prefijo_telefonico_pais = array(

        '595' => '1',//'PARAGUAY',
        '591' => '2',//'BOLIVIA',
        '502' => '3',//'GUATEMALA',
        '849' => '4',//'REPUBLICA DOMINICANA',
        '809' => '4',//'REPUBLICA DOMINICANA',
        '829' => '4',//'REPUBLICA DOMINICANA',
        '52'  => '5',//'MEXICO',
        '57'  => '6',//'COLOMBIA',
    );
    var $categorias_padre = array();
    var $layouts = array(
        '72' => 'wap-layout',
        '82' => 'wap-layout',
        '58' => 'wap-layout',
        '77' => 'waptwo-layout',
    );
    var $alias = array(
        'PATRON' => '77',
        'PORTAL' => '72',
        'PORTAL' => '82',
    );
    var $actions = array(
        '0' => 'home',
        '1' => 'imagenes',
        '2' => 'audios',
        '3' => 'videos',
        '4' => 'contenidos',
    );

    public function init()
    {
        $this->_configurarLogger();

        $this->logger->info('INIT WAP - ENTERMOVIL');

        $this->_procesarMSISDN();

        $this->logger->info('MSISDN:[' . $this->msisdn . '] cel:[' . $this->_getFormatoCorto( $this->msisdn ) . ']');

        /*$namespace= new Zend_Session_Namespace('ENTERMOVIL_PORTAL_ESCOBAR');

        if( !isset( $namespace->cel ) ){
            //si no esta seteado la sesion podria deberse a los siguientes casos
            //1) es una pc
            //2) es un dispositivo movil con wifi activado
            //3) es un dispositivo movil con 3g activado pero la sesion ha terminado
            //4) es un dispositivo movil con 3g activado pero no se encuentra suscripto
            $this->_analizarSolicitud();
            if( isset( $this->tipo_mensaje ) ){

                $this->_forward( 'manejador','waptwo' );
            }
        }*/
        $namespace= new Zend_Session_Namespace('ENTERMOVIL_PORTAL_ESCOBAR');

        $this->logger->info('RESOLUCION: ' . $namespace->ancho .'x' .$namespace->alto );
    }

    private function _procesarMSISDN() {

        $this->logger->info('_procesarMSISDN');

        //headers aceptados
        $header_names = array('HTTP_MSISDN', 'HTTP_X_UP_CALLING_LINE_ID', 'HTTP_X_MSISDN', 'HTTP_X_NOKIA_MSISDN', 'HTTP_X_WAP_NETWORK_CLIENT_MSISDN');
        $nombre_header = null;
        foreach($header_names as $header_name) {

            if( isset( $_SERVER[$header_name] ) && !empty( $_SERVER[$header_name] ) ) {

                $this->msisdn = $_SERVER[$header_name];
                $nombre_header = $header_name;
                break;
            }
        }
        //ARBITRO LOS VALORES PARA PROBAR
        /*$nombre_header = 'HTTP_MSISDN';
        $this->msisdn = '595971797965';
        $_SERVER['HTTP_ENTERMOVIL_DEBUG_IP_ORIGEN'] = '190.104.162.12';*/
        //FIN
        //si no son nulos se conectaron desde la red 3g de su celular
        if( !is_null( $this->msisdn ) && !is_null( $nombre_header ) ){

            $this->nroCel = $this->_getFormatoCorto($this->msisdn);
            $this->logger->info('Header:[' . $nombre_header . ']:[' . $this->msisdn . ']');
            $this->logger->info('Nrocel:[' . $this->nroCel . ']');
            //seteo la informacion ya disponible del usuario
            $this->info_usuario['cel'] = $this->nroCel;
            $this->info_usuario['ip'] = $_SERVER['REMOTE_ADDR'];

            if( isset( $_SERVER['HTTP_ENTERMOVIL_DEBUG_IP_ORIGEN'] ) ){

                $this->info_usuario['ip'] = $_SERVER['HTTP_ENTERMOVIL_DEBUG_IP_ORIGEN'];
                $this->logger->info('Header-Entermovil-Debug IP_ORIGEN:[' . $this->info_usuario['ip'] .']');
            }

            if( isset( $this->info_usuario['id_pais'] ) && !is_null( $this->info_usuario['id_pais'] ) ){

                $this->_procesarOrigen();

            }else{

                $this->logger->err('NO SE ENCUENTRA EL ID_PAIS -->cel[' . $this->info_usuario['cel'] . ']');
                $info_error['cel'] = $this->info_usuario['cel'];
                $info_error['url'] = '/wap/init';
                $info_error['error'] = 'NO SE ENCUENTRA EL ID_PAIS';
                $info_error['linea'] = 839; //id_carrier
                $info_error['direccion_ip'] = $_SERVER['REMOTE_ADDR'];
                $this->_logearErrores( $info_error );
            }

            //tiene msisdn, ip y por eso id_carrier
            if( isset( $this->info_usuario['id_carrier'] ) && !is_null( $this->info_usuario['id_carrier'] ) ){

                $this->solicitudValida = true;
                $this->logger->info('--->SOLICITUD VALIDA');

            }else{

                $this->solicitudValida = false;
                $this->logger->info('--->SOLICITUD NO VALIDA ---> NO SE ENCUENTRA EL ID_CARRIER');

                //setear errores
                $info_error['cel'] = $this->info_usuario['cel'];
                $info_error['error'] = 'NO SE ENCUENTRA EL ID_CARRIER';
                $info_error['url'] = '/wap/init';
                $info_error['linea'] = 823; //id_carrier
                $info_error['direccion_ip'] = $_SERVER['REMOTE_ADDR'];
                $this->_logearErrores( $info_error );
            }
        }else{

            $this->solicitudValida = false;
            //setear log errores
            $info_error['error'] = 'NO TIENE MSISDN O NO TIENE HEADER';
            $info_error['linea'] = 90; //msisdn
            $info_error['url'] = '/wap/init';
            $info_error['direccion_ip'] = $_SERVER['REMOTE_ADDR'];
            $this->_logearErrores( $info_error );
            $this->logger->info('--->SOLICITUD NO VALIDA ---> NO TIENE MSISDN O NO TIENE HEADER ');
        }
    }

    private function analizarSolicitud(){
        //agregado
        if( ( $this->solicitudValida == false )  ){

            $this->_procesarUserAgent();
            if( $this->ua['is_mobile'] ){
                //es un telefono movil pero se conecto por wifi
                $this->_redirect('/waptwo/manejador-de-mensajes/tipo/1');
            }else{
                //es una pc
                $this->_redirect('/waptwo/manejador-de-mensajes/tipo/2');
            }
        }
    }

    private function _procesarUserAgent() {

        $this->logger->info('UserAgent:['.$_SERVER['HTTP_USER_AGENT'] .'] Parametros:[' . print_r($this->_getAllParams(), true) . ']');

        $bootstrap = $this->getInvokeArg('bootstrap');
        $userAgent = $bootstrap->getResource('useragent');
        $device = $userAgent->getDevice();

        $this->ua = array();

        $is_mobile = ($device->hasFeature('is_mobile')) ? true : false;
        $xhtml_support_level = $device->getFeature('xhtml_support_level');

        /*if( $xhtml_support_level >= 0 ) {

            $this->_helper->_layout->setLayout('wap-layout');
        } else {

            $this->_helper->_layout->setLayout('wap-layout');
        }*/

        $is_mobile = ($device->hasFeature('is_mobile')) ? $device->getFeature('is_mobile') : false;
        $xhtml_support_level = $device->getFeature('xhtml_support_level');
        $this->ua['is_mobile'] = $is_mobile;
        $this->ua['xhtml_support_level'] = $xhtml_support_level;
        $this->ua['marca'] = $device->getFeature('brand_name');
        $this->ua['modelo'] = $device->getFeature('model_name');
        $this->ua['version'] = $device->getFeature('marketing_name') . ' ' . $device->getFeature('model_extra_info');
        $this->ua['ancho'] = $device->getFeature('resolution_width');
        $this->ua['alto'] = $device->getFeature('resolution_height');

        $this->logger->info('Telefono -> [' . $this->ua['marca'] . ' - ' . $this->ua['modelo'] . ' - ' . $this->ua['version'] . ']');

    }

    private function _configurarLogger() {

        //Creamos nuevo Logger para Mobile (ContentMagic)
        $logger = new Zend_Log();
        $writer = new Zend_Log_Writer_Stream(APPLICATION_PATH . '/../log/www.entermovil.com.py_waptwo_'.date('Y-m-d').'.log');
        $format = '%timestamp% %priorityName% - [%remoteAddr%]: %message%' . PHP_EOL;
        $formatter = new Zend_Log_Formatter_Simple($format);
        $writer->setFormatter($formatter);
        $logger->addWriter($writer);
        $logger->setEventItem('remoteAddr', $_SERVER['REMOTE_ADDR']);
        $this->logger = $logger;
    }

    public function indexAction() {

        $this->logger->info('--->INDEX_ACTION');

        $acceso_categorias = true;
        $namespace= new Zend_Session_Namespace('ENTERMOVIL_PORTAL_ESCOBAR');

        if($this->solicitudValida == false){

            $this->logger->info('solicitud no valida');

            $alias = $this->_getParam( 'alias', null );
            if( !is_null( $alias ) ){

                $this->logger->info('ALIAS RECIBIDO: ['.$alias.']');
                //mensaje especifico para el alias recibido
                $this->_helper->_layout->setLayout( $this->layouts[$this->alias[$alias]] );
                $this->view->mensaje = array(
                    'Conectese a la red 3g de su operadora',
                    "Enviar la palabra ". $alias . " al 35500 para suscribirse"
                );
                if( $this->alias[$alias] == '77' ){

                    $this->_cargarImagenes( $namespace->ancho );
                    $this->view->ua = $this->formato;

                }else{

                    $this->_cargarImagenes2( $namespace->ancho );
                    $this->view->ua= $this->formato;
                }
                //setear errores
                $info_error['id_promocion'] = $this->alias[$alias];
                $info_error['error'] = 'RED WIFI';
                $info_error['linea'] = 90;
                $info_error['alias'] = $alias;
                $info_error['direccion_ip'] = $_SERVER['REMOTE_ADDR'];
                $this->_logearErrores( $info_error );
                //mostrar vista apropiada
                $this->_helper->viewRenderer('mensaje-error');
                return;

            }else{
                //mensaje por defecto porque no se id_carrier ni pais
                $this->_helper->layout->setLayout('wap-layout');
                $this->_cargarImagenes2( $namespace->ancho );
                $this->view->ua = $this->formato;
                $this->view->mensaje = array(
                    'Debe utilizar la conexión 3G de su celular',
                    'Enviar PORTAL al 35500 o',
                    'Enviar PATRON al 35500',
                    'para suscribirse',
                    'Desde tu TIGO o PERSONAL'
                );
                //mostrar vista apropiada
                $this->_helper->viewRenderer('mensaje-error');
                return;
            }


        }else{

            $alias = $this->_getParam('alias', null);

            $this->logger->info('alias - index: [[' . $alias . ']]');

            if( !is_null( $alias ) ){

                $namespace->alias = $alias;

                $this->logger->info('alias ' .  $namespace->alias );
                $this->logger->info('sesion: ' .  print_r( $namespace, true ) );
                $this->info_usuario['alias'] = $alias;

                $datos = array(

                    'id_carrier' => $this->info_usuario['id_carrier'],
                    'alias' => $this->info_usuario['alias'],
                );

                $this->info_usuario['id_promocion'] = $this->_consulta( 'GET_ID_PROMOCION', $datos );
                //si no esta seteado el id promocion del usuario le muestro un mensaje de error por defecto
                if( !isset( $this->info_usuario['id_promocion'] ) ){

                    $this->_helper->_layout->setLayout( 'wap-layout' );
                    $this->view->mensaje = "El alias no existe";

                    $this->_cargarImagenes2( $namespace->ancho );
                    $this->view->ua = $this->formato;

                    //se dessete todod
                    $namespace->unsetAll();
                    unset($namespace);
                    //mostrar vista apropiada
                    $this->_helper->viewRenderer('mensaje-error');
                    return;

                }

                $datos['cel'] = $this->info_usuario['cel'];
                $datos['id_promocion'] = $this->info_usuario['id_promocion'];
                $this->info_usuario['nivel'] = $this->_consulta( 'GET_SUSCRIPTO_NIVEL', $datos );

                $namespace->id_pais = $this->info_usuario['id_pais'];
                $namespace->id_promocion = $this->info_usuario['id_promocion'];
                $namespace->id_carrier = $this->info_usuario['id_carrier'];
                $namespace->ip = $this->info_usuario['ip'];
                $namespace->cel = $this->info_usuario['cel'];
                $namespace->nivel = $this->info_usuario['nivel'];
                $namespace->banner = array();
                $namespace->banner['0'] = -1;
                $namespace->banner['1'] = -1;
                $namespace->banner['2'] = -1;
                $namespace->banner['3'] = -1;
                //1 dia
                $namespace->setExpirationSeconds(60*60*24*7);

                $this->logger->info('info_usuario_index: ' .print_r($this->info_usuario, true));
                $this->logger->info('namespace_index: ' .print_r($namespace, true));
                $this->logger->info('id_pais: ' . $namespace->id_pais);
                $this->logger->info('id_promocion: ' .$namespace->id_promocion);
                $this->logger->info('id_carrier: ' . $namespace->id_carrier);
                $this->logger->info('alias: ' . $namespace->alias);
                $this->logger->info('ip: ' . $namespace->ip);
                $this->logger->info('cel: ' . $namespace->cel);
                $this->logger->info('nivel: ' . $namespace->nivel);
                $this->logger->info('banners: ' . print_r($namespace->banner, true));
                $this->logger->info('ancho: ' . print_r($namespace->ancho, true));
                $this->logger->info('alto: ' . print_r($namespace->alto, true));

                $solicitud = $this->_preProcesarSolicitud();

                if( $solicitud === 'NO-SUSCRIPTO' ){

                    //setear errores
                    $info_error['id_promocion'] = $namespace->id_promocion;
                    $info_error['cel'] = $namespace->cel;
                    $info_error['id_carrier'] = $namespace->id_carrier;
                    $info_error['error'] = 'NO SUSCRIPTO';
                    $info_error['url'] = '/waptwo/index';
                    $info_error['linea'] = 467;
                    $info_error['alias'] = $namespace->alias;
                    $info_error['direccion_ip'] = $namespace->ip;
                    $this->_logearErrores( $info_error );


                    $this->_helper->_layout->setLayout( $this->layouts[$namespace->id_promocion] );
                    $this->view->mensaje = "Enviar la palabra ". $alias . " al 35500 para suscribirse";

                    if( $namespace->id_promocion == '77' ){

                        $this->_cargarImagenes( $namespace->ancho );
                        $this->view->ua = $this->formato;

                    }else{

                        $this->_cargarImagenes2( $namespace->ancho );
                        $this->view->ua= $this->formato;
                    }
                    //se dessete todod
                    $namespace->unsetAll();
                    unset($namespace);
                    //mostrar vista apropiada
                    $this->_helper->viewRenderer('mensaje-error');

                    $acceso_categorias = false;


                }else if( $solicitud === 'NO-USUARIO' ){

                    $this->_helper->_layout->setLayout( $this->layouts[$namespace->id_promocion] );
                    $this->view->mensaje = "El servicio no se ha abonado por eso no se encuentra disponible";
                    //setear errores
                    $info_error['id_promocion'] = $namespace->id_promocion;
                    $info_error['cel'] = $namespace->cel;
                    $info_error['id_carrier'] = $namespace->id_carrier;
                    $info_error['error'] = 'NO USUARIO';
                    $info_error['url'] = '/waptwo/index';
                    $info_error['linea'] = 487;
                    $info_error['alias'] = $namespace->alias;
                    $info_error['direccion_ip'] = $namespace->ip;
                    $this->_logearErrores( $info_error );


                    if( $namespace->id_promocion == '77' ){

                        $this->_cargarImagenes();
                        $this->view->ua = $this->formato;

                    }else{

                        $this->_cargarImagenes2();
                        $this->view->ua= $this->formato;
                    }

                    //mostrar vista apropiada
                    $this->_helper->viewRenderer('mensaje-error');

                    $acceso_categorias = false;

                }
            }
        }
        if( $acceso_categorias ) {

            $this->logger->info("----------------> ACCESO PERMITIDO");
            $namespace->configuracion = $this->_consulta( 'OBTENER_CONFIGURACION', $datos );
            $this->_procesarSolicitud( $namespace->alias );
            $this->_forward( 'categorias', 'waptwo' );

        } else {

            $this->logger->info("----------------> ACCESO DENEGADO");
        }

        return;
    }

    public function altaSuscriptoAction(){

        $parametros = $this->_getAllParams( 'id-promocion', 'id-carrier', 'cel', null );

        if( !is_null( $parametros ) ){

            //setear log
            $info_accion = array();
            $fullUrl =  $this->view->url();
            $info_accion['id_promocion'] = $parametros['id-promocion'];
            $info_accion['cel'] = $parametros['id-carrier'];
            $info_accion['id_carrier'] = $parametros['cel'];
            $info_accion['url'] = $fullUrl;
            $this->_logearAccion( $info_accion );

            $datos = array();
            $datos['id_promocion'] = $parametros['id-promocion'];
            $datos['id_carrier'] = $parametros['id-carrier'];
            $datos['cel'] = $parametros['cel'];

            if( $this->_estaSuscripto( $datos ) ){

                $alta_suscripto = $this->_consulta( 'ALTA_SUSCRIPTO', $datos );
                $this->_redirect( '/waptwo/activacion/id-promocion/' . $datos['id_promocion'] .'/suscripto/false' );

            }else{

                $this->_redirect( '/waptwo/activacion/id-promocion/' . $datos['id_promocion'] .'/suscripto/true' );
            }

        }else{

            $this->_redirect('/waptwo/aps');
        }
    }

    private function _preProcesarSolicitud(){

        //buscar usuario en wap.usuario
        $this->logger->info('_preProcesarSolicitud');

        $namespace= new Zend_Session_Namespace('ENTERMOVIL_PORTAL_ESCOBAR');
        $this->logger->info('id_pais: ' . $namespace->id_pais);
        $this->logger->info('id_promocion: ' .$namespace->id_promocion);
        $this->logger->info('id_carrier: ' . $namespace->id_carrier);
        $this->logger->info('ip: ' . $namespace->ip);
        $this->logger->info('cel: ' . $namespace->cel);

        $datos = array(

            'cel' => $namespace->cel,
            'id_promocion' => $namespace->id_promocion,
            'id_carrier' => $namespace->id_carrier,
        );

        if( $this->_estaSuscripto( $datos ) ){

            $this->logger->info( 'DATOS: ' . print_r($datos,true) );

            $existe_usuario = $this->_consulta( 'EXISTE_USUARIO', $datos );

            if( !empty( $existe_usuario ) ){

                return 'EXISTE_USUARIO';

            }else{

                return 'NO-USUARIO';
            }

        }else{

            return 'NO-SUSCRIPTO';
        }
    }

    public function noSuscriptoAction(){

        $namespace= new Zend_Session_Namespace('ENTERMOVIL_PORTAL_ESCOBAR');
        $this->view->formato = $this->ua;
        $this->info_usuario['alias'] = $namespace->alias;
        $this->logger->info('informacion usuario - wifi : '.  print_r( $this->info_usuario, true ) );
        $informacion_para_usuario = $this->_consulta('GET_INFO_NO_SUSCRIPTO','');

        $this->_cargarImagenes( $namespace->ancho );
        $this->view->ua = $this->formato;

        foreach ( $informacion_para_usuario as $indice=>$info ){

            if( $info['alias'] == 'PORTAL' && $this->info_usuario['id_carrier'] == $info['id_carrier'] ){

                $this->view->info_usuario = $info;
                break;
            }
        }
        $namespace->unsetAll();
        unset($namespace);
    }

    public function noUsuarioAction(){

        $namespace= new Zend_Session_Namespace('ENTERMOVIL_PORTAL_ESCOBAR');
        $this->view->formato = $this->ua;
        $this->info_usuario['alias'] = $namespace->alias;
        $this->logger->info('informacion usuario - wifi : '.  print_r( $this->info_usuario, true ) );
        $this->_cargarImagenes();
        $this->view->ua = $this->formato;

        $namespace->unsetAll();
        unset($namespace);
    }

    public function pantallaAction() {

        //$this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();

    }

    public function heAction()
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

        $header_names = array('HTTP_MSISDN', 'HTTP_X_UP_CALLING_LINE_ID', 'HTTP_X_MSISDN', 'HTTP_X_NOKIA_MSISDN', 'HTTP_X_WAP_NETWORK_CLIENT_MSISDN');
        $nro_cel = 'NO-RECIBIDO';//En formato largo: 595 981 524 664
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
        echo 'direccion ip:[' . $_SERVER['REMOTE_ADDR'] . ']';
        echo '</pre>';

        $this->logger->info('*******************DATOS - HE - ACTION*****************');
        $this->logger->info('SERVER: [' . print_r( $_SERVER, true ) . ']');
        //$this->logger->info('DEVICE: [' . print_r( $device, true ) . ']');
        $this->logger->info('USER_AGENT: [' . print_r( $this->ua, true ) . ']');
        $this->logger->info('NUMERO_CEL: ['. $nro_cel .']');
        $this->logger->info('NOMBRE_HEADER: ['. $nombre_header .']');
        $this->logger->info('RESOLUCION: ['. $device->getFeature('resolution_width') . 'x' . $device->getFeature('resolution_height') .']');
        $this->logger->info('DIRECCION_IP: [' . $_SERVER['REMOTE_ADDR'] . ']');
        $this->logger->info('NAVEGADOR: ['. $userAgent->getBrowserType().']');
        //print_r($device);
        //echo trim($device->getFeature('brand_name') . ' ' . $device->getFeature('model_name') . ' ' . $device->getFeature('marketing_name') . ' ' . $device->getFeature('model_extra_info'));
        exit;
    }

    public function descargarAction() {

        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $this->logger->info('--->DESCARGAR_ACTION');

        $parametros = $this->_getAllParams('id-p', 'id-c', 'id', 'origen', null);

        if(is_null($parametros)) {

            $this->logger->info('Auth NO-DEFINIDO!!');
            throw new Zend_Exception("Parámetros No Válidos");
            return;
        }

        $this->logger->info('Parametros:[' . $parametros . ']');

        if($parametros['id-p'] > 0) {

            //setear log
            $info_accion = array();
            $fullUrl =  $this->view->url();
            $info_accion['id_promocion'] = $parametros['id-p'];
            $info_accion['cel'] = $this->info_usuario['cel'];
            $info_accion['id_carrier'] = $this->info_usuario['id_carrier'];
            $info_accion['url'] = $fullUrl;
            $info_accion['origen'] = $this->actions[$parametros['origen']];
            $this->_logearAccion( $info_accion );

            $id_promocion = $parametros['id-p'];
            $id_contenido = $parametros['id-c'];
            $id_categoria = $parametros['id'];

            $this->logger->info('id_promocion:[' . $id_promocion . '] OK');

            //cel, id_promocion, id_contenido
            $datos = array(

                'id_categoria' => $id_categoria,
                'id_contenido' => $id_contenido,
                'id_promocion' => $id_promocion
            );

            $info_contenido = $this->_consulta('GET_CONTENIDO_DESCARGAR', $datos);
            $this->logger->info('info_contenido:[' . print_r($info_contenido, true) . ']');

            if(!empty($info_contenido)){

                $path_archivo_descarga = $info_contenido['path'];

                $this->logger->info('path_archivo:[' . $path_archivo_descarga . ']');

                header("Content-type: video/3gpp"); // change mimetype
                header('Accept-Ranges: bytes');

                $this->logger->info("HTTP_RANGE: " . $_SERVER['HTTP_RANGE']);

                if( $info_contenido['tipo'] == 'video/3gpp' ){

                    if (is_file($path_archivo_descarga)){

                        if (isset($_SERVER['HTTP_RANGE'])){ // do it for any device that supports byte-ranges not only iPhone

                            $this->logger->info('Es iphone');
                            //carpeta temporal
                            $tmp = '/home/entermovil/web/www.entermovil.com.py/public/tmp/';
                            //armar carpeta
                            $fecha = date("y-m-d");
                            $tmp = $tmp . $fecha;
                            $informacion = pathinfo($path_archivo_descarga);
                            $archivo = $informacion['basename'];
                            $path_archivo_descarga_iphone = $tmp .'/' . md5($archivo . date("Y-m-d")) . '.' .$informacion['extension'];
                            $this->logger->info( 'path_archivo_descargar_iphone: ' . $path_archivo_descarga_iphone );

                            if(!is_dir( $tmp )){

                                mkdir( $tmp );
                                $this->logger->info('Creamos la carpeta: ' . $tmp);
                                //creamos index.php
                                $index = fopen($tmp.'/index.php', "r");
                                $this->logger->info( 'creamos el fichero: ' . $index );
                                copy($path_archivo_descarga, $path_archivo_descarga_iphone);
                                $this->logger->info('Archivo_para_iphone: ' . $path_archivo_descarga_iphone);
                                //armar url
                                $pos = strpos('/tmp' , $path_archivo_descarga_iphone);
                                $url = substr($path_archivo_descarga_iphone, $pos );
                                $this->logger->info('url: ' . $url );
                                $this->_redirect( $url );
                                exit;

                            }else{

                                copy($path_archivo_descarga, $path_archivo_descarga_iphone);
                                $this->logger->info('Archivo_para_iphone: ' . $path_archivo_descarga_iphone);
                                //armar url
                                $pos = strpos( $path_archivo_descarga_iphone, '/tmp' );
                                $this->logger->info('pos: ' . $pos);
                                $url = substr($path_archivo_descarga_iphone, $pos );
                                $this->logger->info('url: ' . $url );
                                $this->_redirect( $url );
                                exit;
                            }
                        } else {

                            $this->logger->info( 'No es iphone' );
                            $size_archivo = filesize($path_archivo_descarga);

                            header('Content-Description: File Transfer');
                            //header('Content-Type: application/octet-stream');
                            $content_type = $info_contenido['tipo'];

                            header('Content-Type: ' . $content_type);
                            //
                            $nombre_contenido = basename($path_archivo_descarga);
                            $this->logger->info('nombre-contenido: ' . $nombre_contenido);

                            header('Content-Disposition: inline; filename='.$nombre_contenido);
                            //header('Content-Disposition: attachment; filename='.$nombre_contenido);
                            header('Content-Transfer-Encoding: binary');
                            //header('X-Pad: avoid browser bug');
                            header('Expires: 0');
                            header('Cache-Control: must-revalidate');
                            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                            header('Pragma: public');
                            header('Content-Length: ' . $size_archivo);
                            ob_clean();
                            flush();

                            $bytes_leidos = readfile($path_archivo_descarga);
                            $this->logger->info('bytes_leidos:[' . $bytes_leidos .']');
                            exit;
                        } // fim do if
                    } // fim do if

                }else{

                    $this->logger->info( 'Es: jpg, mp4, mp3' );
                    $size_archivo = filesize($path_archivo_descarga);

                    header('Content-Description: File Transfer');
                    //header('Content-Type: application/octet-stream');
                    $content_type = $info_contenido['tipo'];

                    header('Content-Type: ' . $content_type);
                    //
                    $nombre_contenido = basename($path_archivo_descarga);
                    $this->logger->info('nombre-contenido: ' . $nombre_contenido);

                    header('Content-Disposition: inline; filename='.$nombre_contenido);
                    //header('Content-Disposition: attachment; filename='.$nombre_contenido);
                    header('Content-Transfer-Encoding: binary');
                    //header('X-Pad: avoid browser bug');
                    header('Expires: 0');
                    header('Cache-Control: must-revalidate');
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
    }

    private function _getFormatoCorto($nro_largo) {

        //Verificamos que el nro recibido este en formato largo
        //Ejemplo: 595 971 200 211
        if(strlen($nro_largo) == 12 && substr($nro_largo, 0, 3) == '595') {//esta en formato largo

            $this->info_usuario['id_pais'] = $this->prefijo_telefonico_pais['595'];
            return '0'.substr($nro_largo, 3);

        }//solo paraguay
        /*CON ESTO YO SE DE QUE PAIS ES EL NUMERO Y LUEGO BUSCO EL RANGO DE POSIBLES IPS PARA EL CARRIER*/
        else if( strlen($nro_largo) >= 12 && substr($nro_largo, 0, 3) == '591' ){

            $this->info_usuario['id_pais'] = $this->prefijo_telefonico_pais['591'];
            return substr($nro_largo, 3);

        }else if( strlen($nro_largo) >= 11 && substr($nro_largo, 0, 3) == '502' ){

            $this->logger->info('Tigo-Guatemala');
            $this->info_usuario['id_pais'] = $this->prefijo_telefonico_pais['502'];
            return substr($nro_largo, 3);

        }else if( strlen($nro_largo) >= 12 && (substr($nro_largo, 0, 3) == '849')){

            $this->info_usuario['id_pais'] = $this->prefijo_telefonico_pais['849'];
            return substr($nro_largo, 3);

        }else if( strlen($nro_largo) >= 12 && (substr($nro_largo, 0, 3) == '809')){

            $this->info_usuario['id_pais'] = $this->prefijo_telefonico_pais['809'];
            return substr($nro_largo, 3);

        }else if( strlen($nro_largo) >= 12 && (substr($nro_largo, 0, 3) == '829')){

            $this->info_usuario['id_pais'] = $this->prefijo_telefonico_pais['829'];
            return substr($nro_largo, 3);
        }else if( strlen($nro_largo) <= 12 && (substr($nro_largo, 0, 2) == '52')){

            $this->info_usuario['id_pais'] = $this->prefijo_telefonico_pais['52'];
            return substr($nro_largo, 2);
        }else if( strlen($nro_largo) <= 12 && (substr($nro_largo, 0, 2) == '57')){

            $this->info_usuario['id_pais'] = $this->prefijo_telefonico_pais['57'];
            return substr($nro_largo, 2);
        }else{

            $this->info_usuario['id_pais'] = null;
        }

        return $nro_largo;
    }

    private function _getFormatoLargo( $nro_corto, $con_signo = false ) {

        //Verificamos que el nro recibido este en formato corto
        //Ejemplo: 0971 200 211
        if(strlen($nro_corto) == 10 && $nro_corto[0] == '0') {//esta en formato corto
            return ($con_signo ? '+' : '') . '595' . substr($nro_corto, 1);
        }

        return $nro_corto;
    }

    private function _procesarOrigen(){

        $this->logger->info('_procesarOrigen');

        $informacion = $this->_consulta('GET_INFO_RED', array( 'id_pais' => $this->info_usuario['id_pais'] ) );
        //$this->logger->info( "informacion: " .print_r( $informacion, true) );

        foreach( $informacion as $indice => $info ){

            if(  $this->_ipPertenece( $this->info_usuario['ip'], $info['direccion_ip'], $info['mascara'] )  ) {

                $this->info_usuario['id_carrier'] = $info['id_carrier'];
                $this->logger->info('IP Pertenece a Rango!!');
                break;
            }
        }

        if( !isset( $this->info_usuario['id_carrier'] ) ) {

            $this->logger->err('NO-SE-OBTUVO-ID-CARRIER IP:[' . $this->info_usuario['ip'] . ']');
            $this->info_usuario['id_carrier'] = null;
        }
    }

    private function _ipPertenece( $ip, $red, $mascara ) {
        // Dividimos en octetos
        $octip = explode(".",$ip);
        $octnet = explode(".",$red);
        $octmask = explode(".",$mascara);
        // Comparamos con AND binario
        for ($i=0;$i<4;$i++) {

            $a = (int)$octip[$i] & (int)$octmask[$i];
            $b = (int)$octnet[$i] & (int)$octmask[$i];
            if ($a != $b) return(0);
        }
        return(1);
    }

    //cargamos los navigator layers
    private function _cargarImagenes( $ancho ) {

        require_once APPLICATION_PATH . '/models/phMagick.php';
        //ubicacion del logo de entermovil
        $this->ua['ancho'] = $ancho;
        $nombre_logo_original_jpg = 'img/wap-imagenes-colombia/wap_logo_escobar_w1024x189px.jpg';
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
        $this->formato['ancho'] = $this->ua['ancho'];
        $this->logger->info('ANCHO-LOGO:[' . $ancho_logo . ']');
        $this->formato['ancho_logo'] = $ancho_logo;
        $nombre_logo_max_ancho_jpg = 'img/wap-imagenes-colombia/cache/logo_escobar_w' . $ancho_logo . 'px.jpg';
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

        $this->formato['imagen_logo'] = $nombre_logo_max_ancho_jpg; //envio

        //calculamos el ancho de la tabla blanca
        $ancho_tabla = round((($this->ua['ancho'])*300)/320);
        if($ancho_tabla % 2 != 0){
            //para que tambien tenga resolución par
            $ancho_tabla = $ancho_tabla - 1;
        }
        $this->formato['ancho_tabla'] = $ancho_tabla;
        $this->logger->info('ANCHO-TABLA:[' . $ancho_tabla . ']');

        //agregue navegator layer
        //calculo para cada boton
        $ancho_navegator1 = round((($this->formato['ancho_tabla'])*36)/300);
        $ancho_navegator2 = round((($this->formato['ancho_tabla'])*59)/300);
        $ancho_navegator3 = round((($this->formato['ancho_tabla'])*56)/300);
        $ancho_navegator4 = round((($this->formato['ancho_tabla'])*56)/300);
        $ancho_navegator5 = round((($this->formato['ancho_tabla'])*50)/300);
        $ancho_navegator6 = round((($this->formato['ancho_tabla'])*43)/300);
        $suma_ancho= $ancho_navegator1 + $ancho_navegator2 + $ancho_navegator3 + $ancho_navegator4 + $ancho_navegator5 + $ancho_navegator6;
        $this->logger->info('SUMA-DE-NAVIGATORLAYERS:[' . $suma_ancho . ']');

        //defino el ancho de los costados
        $this->formato['ancho_costado'] = $ancho_navegator1;
        //si no suma el ancho de la tabla entonces le agrego lo que le falta al ultimo navegator layer
        $diferencia = $ancho_tabla - $suma_ancho;
        if( $diferencia > 0){

            $ancho_navegator6 = $ancho_navegator6 + ($ancho_tabla-$suma_ancho);
            $suma_ancho= $ancho_navegator1 + $ancho_navegator2 + $ancho_navegator3 + $ancho_navegator4 + $ancho_navegator5 + $ancho_navegator6;
            $this->logger->info('NUEVA-SUMA-DE-NAVIGATORLAYERS-SIESMENOR:[' . $suma_ancho . ']');
        }
        elseif( $diferencia < 0 ){

            $ancho_navegator1 = $ancho_navegator1 - ($suma_ancho-$ancho_tabla);
            $suma_ancho= $ancho_navegator1 + $ancho_navegator2 + $ancho_navegator3 + $ancho_navegator4 + $ancho_navegator5 + $ancho_navegator6;
            $this->logger->info('NUEVA-SUMA-DE-NAVIGATORLAYERS-SIESMAYOR:[' . $suma_ancho . ']');
        }
        //calculo tambien el alto
        $alto_calculado = round((($this->ua['ancho']*36)/320));
        $this->formato['alto_calculado'] = $alto_calculado;
        //navegador 1
        $nombre_navegator1 = 'img/wap-imagenes-colombia/navigator-layer-1-w132x121px.jpg';
        $this->logger->info('NAVEGATOR1-ORIGINAL:[' . $nombre_navegator1 . ']');
        $nombre_navegator1_max_ancho_jpg = 'img/wap-imagenes-colombia/cache/navigator_layer1_w' . $ancho_navegator1 . 'px.jpg';
        $this->logger->info('NAVEGATOR1-PERSONALIZADO:[' . $nombre_navegator1_max_ancho_jpg . ']');

        if(!file_exists($nombre_navegator1_max_ancho_jpg)) {

            $this->logger->info("NAVEGATOR1-PERSONALIZADO NO Existe en Cache...");
            //Generamos Imagen
            $phMagick = &new phMagick($nombre_navegator1, $nombre_navegator1_max_ancho_jpg);
            $phMagick->resize($ancho_navegator1,$alto_calculado,true);

            list($ancho_calculado, $alto_calculado) = $phMagick->getDimentions();
            $this->logger->info("NAVEGATOR1-PERSONALIZADO -> Dimensiones:[". $ancho_calculado ." x " . $alto_calculado ."]");

        } else {

            $this->logger->info("NAVEGATOR1-PERSONALIZADO YA EXISTE en Cache");
        }
        $this->formato['navigator_layer1'] = $nombre_navegator1_max_ancho_jpg;

        //navegador 2
        $nombre_navegator2 = 'img/wap-imagenes-colombia/navigator-layer-2-w197x121px.jpg';
        $this->logger->info('NAVEGATOR2-ORIGINAL:[' . $nombre_navegator2 . ']');

        $nombre_navegator2_max_ancho_jpg = 'img/wap-imagenes-colombia/cache/navigator_layer2_w' . $ancho_navegator2 . 'px.jpg';
        $this->logger->info('NAVEGATOR2-PERSONALIZADO:[' . $nombre_navegator2_max_ancho_jpg . ']');

        if(!file_exists($nombre_navegator2_max_ancho_jpg)) {

            $this->logger->info("NAVEGATOR2-PERSONALIZADO NO Existe en Cache...");
            //Generamos Imagen
            $phMagick = &new phMagick($nombre_navegator2, $nombre_navegator2_max_ancho_jpg);
            $phMagick->resize($ancho_navegator2,$alto_calculado,true);

            list($ancho_calculado, $alto_calculado) = $phMagick->getDimentions();
            $this->logger->info("NAVEGATOR2-PERSONALIZADO -> Dimensiones:[". $ancho_calculado ." x " . $alto_calculado ."]");

        } else {
            $this->logger->info("NAVEGATOR2-PERSONALIZADO YA EXISTE en Cache");
        }
        $this->formato['navigator_layer2'] = $nombre_navegator2_max_ancho_jpg;

        //visitado-marcado
        $nombre_navegator2 = 'img/wap-imagenes-colombia/navigator-layer-2-visited-w197x121px.jpg';
        $this->logger->info('NAVEGATOR2-ORIGINAL:[' . $nombre_navegator2 . ']');

        $nombre_navegator2_max_ancho_jpg = 'img/wap-imagenes-colombia/cache/navigator_layer2_visited_w' . $ancho_navegator2 . 'px.jpg';
        $this->logger->info('NAVEGATOR2-PERSONALIZADO:[' . $nombre_navegator2_max_ancho_jpg . ']');

        if(!file_exists($nombre_navegator2_max_ancho_jpg)) {

            $this->logger->info("NAVEGATOR2-PERSONALIZADO NO Existe en Cache...");
            //Generamos Imagen
            $phMagick = &new phMagick($nombre_navegator2, $nombre_navegator2_max_ancho_jpg);
            $phMagick->resize($ancho_navegator2,$alto_calculado,true);

            list($ancho_calculado, $alto_calculado) = $phMagick->getDimentions();
            $this->logger->info("NAVEGATOR2-PERSONALIZADO -> Dimensiones:[". $ancho_calculado ." x " . $alto_calculado ."]");

        } else {

            $this->logger->info("NAVEGATOR2-PERSONALIZADO YA EXISTE en Cache");
        }
        $this->formato['navigator_layer2_v'] = $nombre_navegator2_max_ancho_jpg;
        //navegador 3
        $nombre_navegator3 = 'img/wap-imagenes-colombia/navigator-layer-3-w192x121px.jpg';
        $this->logger->info('NAVEGATOR3-ORIGINAL:[' . $nombre_navegator3 . ']');
        $nombre_navegator3_max_ancho_jpg = 'img/wap-imagenes-colombia/cache/navigator_layer3_w' . $ancho_navegator3 . 'px.jpg';
        $this->logger->info('NAVEGATOR3-PERSONALIZADO:[' . $nombre_navegator3_max_ancho_jpg . ']');

        if(!file_exists($nombre_navegator3_max_ancho_jpg)) {

            $this->logger->info("NAVEGATOR3-PERSONALIZADO NO Existe en Cache...");
            //Generamos Imagen
            $phMagick = &new phMagick($nombre_navegator3, $nombre_navegator3_max_ancho_jpg);
            $phMagick->resize($ancho_navegator3,$alto_calculado,true);

            list($ancho_calculado, $alto_calculado) = $phMagick->getDimentions();
            $this->logger->info("NAVEGATOR3-PERSONALIZADO -> Dimensiones:[". $ancho_calculado ." x " . $alto_calculado ."]");

        } else {

            $this->logger->info("NAVEGATOR3-PERSONALIZADO YA EXISTE en Cache");
        }
        $this->formato['navigator_layer3'] = $nombre_navegator3_max_ancho_jpg;
        //marcado-visitado
        $nombre_navegator3 = 'img/wap-imagenes-colombia/navigator-layer-3-visited-w192x121px.jpg';
        $this->logger->info('NAVEGATOR3-ORIGINAL:[' . $nombre_navegator3 . ']');
        $nombre_navegator3_max_ancho_jpg = 'img/wap-imagenes-colombia/cache/navigator_layer3_visited_w' . $ancho_navegator3 . 'px.jpg';
        $this->logger->info('NAVEGATOR3-PERSONALIZADO:[' . $nombre_navegator3_max_ancho_jpg . ']');

        if(!file_exists($nombre_navegator3_max_ancho_jpg)) {

            $this->logger->info("NAVEGATOR3-PERSONALIZADO NO Existe en Cache...");
            //Generamos Imagen
            $phMagick = &new phMagick($nombre_navegator3, $nombre_navegator3_max_ancho_jpg);
            $phMagick->resize($ancho_navegator3,$alto_calculado,true);

            list($ancho_calculado, $alto_calculado) = $phMagick->getDimentions();
            $this->logger->info("NAVEGATOR3-PERSONALIZADO -> Dimensiones:[". $ancho_calculado ." x " . $alto_calculado ."]");

        } else {

            $this->logger->info("NAVEGATOR3-PERSONALIZADO YA EXISTE en Cache");
        }
        $this->formato['navigator_layer3_v'] = $nombre_navegator3_max_ancho_jpg;
        //navegador 4
        $nombre_navegator4 = 'img/wap-imagenes-colombia/navigator-layer-4-w192x121px.jpg';
        $this->logger->info('NAVEGATOR4-ORIGINAL:[' . $nombre_navegator4 . ']');
        $nombre_navegator4_max_ancho_jpg = 'img/wap-imagenes-colombia/cache/navigator_layer4_w' . $ancho_navegator4 . 'px.jpg';
        $this->logger->info('NAVEGATOR4-PERSONALIZADO:[' . $nombre_navegator4_max_ancho_jpg . ']');

        if(!file_exists($nombre_navegator4_max_ancho_jpg)) {

            $this->logger->info("NAVEGATOR4-PERSONALIZADO NO Existe en Cache...");
            //Generamos Imagen
            $phMagick = &new phMagick($nombre_navegator4, $nombre_navegator4_max_ancho_jpg);
            $phMagick->resize($ancho_navegator4,$alto_calculado,true);

            list($ancho_calculado, $alto_calculado) = $phMagick->getDimentions();
            $this->logger->info("NAVEGATOR4-PERSONALIZADO -> Dimensiones:[". $ancho_calculado ." x " . $alto_calculado ."]");

        } else {

            $this->logger->info("NAVEGATOR4-PERSONALIZADO YA EXISTE en Cache");
        }
        $this->formato['navigator_layer4'] = $nombre_navegator4_max_ancho_jpg;
        //marcado-visitado
        $nombre_navegator4 = 'img/wap-imagenes-colombia/navigator-layer-4-visited-w192x121px.jpg';
        $this->logger->info('NAVEGATOR4-ORIGINAL:[' . $nombre_navegator4 . ']');
        $nombre_navegator4_max_ancho_jpg = 'img/wap-imagenes-colombia/cache/navigator_layer4_visited_w' . $ancho_navegator4 . 'px.jpg';
        $this->logger->info('NAVEGATOR4-PERSONALIZADO:[' . $nombre_navegator4_max_ancho_jpg . ']');

        if(!file_exists($nombre_navegator4_max_ancho_jpg)) {

            $this->logger->info("NAVEGATOR4-PERSONALIZADO NO Existe en Cache...");
            //Generamos Imagen
            $phMagick = &new phMagick($nombre_navegator4, $nombre_navegator4_max_ancho_jpg);
            $phMagick->resize($ancho_navegator4,$alto_calculado,true);

            list($ancho_calculado, $alto_calculado) = $phMagick->getDimentions();
            $this->logger->info("NAVEGATOR4-PERSONALIZADO -> Dimensiones:[". $ancho_calculado ." x " . $alto_calculado ."]");

        } else {

            $this->logger->info("NAVEGATOR4-PERSONALIZADO YA EXISTE en Cache");
        }
        $this->formato['navigator_layer4_v'] = $nombre_navegator4_max_ancho_jpg;
        //navegador 5
        $nombre_navegator5 = 'img/wap-imagenes-colombia/navigator-layer-5-w193x121px.jpg';
        $this->logger->info('NAVEGATOR5-ORIGINAL:[' . $nombre_navegator5 . ']');
        $nombre_navegator5_max_ancho_jpg = 'img/wap-imagenes-colombia/cache/navigator_layer5_w' . $ancho_navegator5 . 'px.jpg';
        $this->logger->info('NAVEGATOR5-PERSONALIZADO:[' . $nombre_navegator5_max_ancho_jpg . ']');

        if(!file_exists($nombre_navegator5_max_ancho_jpg)) {

            $this->logger->info("NAVEGATOR5-PERSONALIZADO NO Existe en Cache...");
            //Generamos Imagen
            $phMagick = &new phMagick($nombre_navegator5, $nombre_navegator5_max_ancho_jpg);
            $phMagick->resize($ancho_navegator5,$alto_calculado,true);

            list($ancho_calculado, $alto_calculado) = $phMagick->getDimentions();
            $this->logger->info("NAVEGATOR5-PERSONALIZADO -> Dimensiones:[". $ancho_calculado ." x " . $alto_calculado ."]");

        } else {

            $this->logger->info("NAVEGATOR5-PERSONALIZADO YA EXISTE en Cache");
        }
        $this->formato['navigator_layer5'] = $nombre_navegator5_max_ancho_jpg;
        //marcado-visitado
        $nombre_navegator5 = 'img/wap-imagenes-colombia/navigator-layer-5-visited-w193x121px.jpg';
        $this->logger->info('NAVEGATOR5-ORIGINAL:[' . $nombre_navegator5 . ']');
        $nombre_navegator5_max_ancho_jpg = 'img/wap-imagenes-colombia/cache/navigator_layer5_visited_w' . $ancho_navegator5 . 'px.jpg';
        $this->logger->info('NAVEGATOR5-PERSONALIZADO:[' . $nombre_navegator5_max_ancho_jpg . ']');

        if(!file_exists($nombre_navegator5_max_ancho_jpg)) {

            $this->logger->info("NAVEGATOR5-PERSONALIZADO NO Existe en Cache...");
            //Generamos Imagen
            $phMagick = &new phMagick($nombre_navegator5, $nombre_navegator5_max_ancho_jpg);
            $phMagick->resize($ancho_navegator5,$alto_calculado,true);

            list($ancho_calculado, $alto_calculado) = $phMagick->getDimentions();
            $this->logger->info("NAVEGATOR5-PERSONALIZADO -> Dimensiones:[". $ancho_calculado ." x " . $alto_calculado ."]");

        } else {
            $this->logger->info("NAVEGATOR5-PERSONALIZADO YA EXISTE en Cache");
        }
        $this->formato['navigator_layer5_v'] = $nombre_navegator5_max_ancho_jpg;
        //navegador 6
        $nombre_navegator6 = 'img/wap-imagenes-colombia/navigator-layer-6-w119x121px.jpg';

        $this->logger->info('NAVEGATOR6-ORIGINAL:[' . $nombre_navegator6 . ']');
        $nombre_navegator6_max_ancho_jpg = 'img/wap-imagenes-colombia/cache/navigator_layer6_w' . $ancho_navegator6 . 'px.jpg';
        $this->logger->info('NAVEGATOR6-PERSONALIZADO:[' . $nombre_navegator6_max_ancho_jpg . ']');

        if(!file_exists($nombre_navegator6_max_ancho_jpg)) {

            $this->logger->info("NAVEGATOR6-PERSONALIZADO NO Existe en Cache...");
            //Generamos Imagen
            $phMagick = &new phMagick($nombre_navegator6, $nombre_navegator6_max_ancho_jpg);
            $phMagick->resize($ancho_navegator6,$alto_calculado,true);

            list($ancho_calculado, $alto_calculado) = $phMagick->getDimentions();
            $this->logger->info("NAVEGATOR6-PERSONALIZADO -> Dimensiones:[". $ancho_calculado ." x " . $alto_calculado ."]");

        } else {

            $this->logger->info("NAVEGATOR6-PERSONALIZADO YA EXISTE en Cache");
        }
        $this->formato['navigator_layer6'] = $nombre_navegator6_max_ancho_jpg;

        $nombre_logo_original_jpg = 'img/wap-imagenes-colombia/borde-abajo-tabla-w1024x64px.jpg';

        $this->logger->info('BORDE-ABAJO-ORIGINAL:[' . $nombre_logo_original_jpg . ']');

        //borde abajo
        $nombre_logo_max_ancho_jpg = 'img/wap-imagenes-colombia/cache/borde_abajo_tabla_w' . $this->formato['ancho_tabla'] . 'px.jpg';
        $this->logger->info('BORDE-ABAJO-PERSONALIZADO:[' . $nombre_logo_max_ancho_jpg . ']');

        if(!file_exists($nombre_logo_max_ancho_jpg)) {

            $this->logger->info("BORDE-ABAJO-PERSONALIZADO NO Existe en Cache...");
            //Generamos Imagen
            $phMagick = &new phMagick($nombre_logo_original_jpg, $nombre_logo_max_ancho_jpg);
            $phMagick->resize($this->formato['ancho_tabla']);

            list($ancho_calculado, $alto_calculado) = $phMagick->getDimentions();
            $this->logger->info("BORDE-ABAJO-PERSONALIZADO -> Dimensiones:[". $ancho_calculado ." x " . $alto_calculado ."]");

        } else {

            $this->logger->info("BORDE-ABAJO YA EXISTE en Cache");
        }

        $this->formato['borde_abajo'] = $nombre_logo_max_ancho_jpg;

        //borde arriba
        $nombre_logo_original_jpg = 'img/wap-imagenes-colombia/borde-arriba-tabla-w1024x64px.jpg';

        $this->logger->info('BORDE-ARRIBA-ORIGINAL:[' . $nombre_logo_original_jpg . ']');

        $nombre_logo_max_ancho_jpg = 'img/wap-imagenes-colombia/cache/borde_arriba_tabla_w' . $this->formato['ancho_tabla'] . 'px.jpg';
        $this->logger->info('BORDE-ARRIBA-PERSONALIZADO:[' . $nombre_logo_max_ancho_jpg . ']');

        if(!file_exists($nombre_logo_max_ancho_jpg)) {

            $this->logger->info("BORDE-ARRIBA-PERSONALIZADO NO Existe en Cache...");
            //Generamos Imagen
            $phMagick = &new phMagick($nombre_logo_original_jpg, $nombre_logo_max_ancho_jpg);
            $phMagick->resize($this->formato['ancho_tabla']);
            list($ancho_calculado, $alto_calculado) = $phMagick->getDimentions();
            $this->logger->info("BORDE-ARRIBA-PERSONALIZADO -> Dimensiones:[". $ancho_calculado ." x " . $alto_calculado ."]");

        } else {

            $this->logger->info("BORDE-ARRIBA YA EXISTE en Cache");
        }

        $this->formato['borde_arriba'] = $nombre_logo_max_ancho_jpg;

        // tambien agregue

        $nombre_logo_original_jpg = 'img/wap-imagenes-colombia/videos.jpg';

        $this->logger->info('IMAGEN-ORIGINAL:[' . $nombre_logo_original_jpg . ']');

        $ancho_imagen = round( (63*($this->formato['ancho']))/320 );

        $this->formato['ancho_imagen'] = $ancho_imagen;

        $this->logger->info('IMAGEN-ANCHO:[' . $ancho_imagen . ']');

        $nombre_logo_max_ancho_jpg = 'img/wap-imagenes-colombia/cache/videos_nuevo_w' . $ancho_imagen . 'px.jpg';
        $this->logger->info('LOGO-PERSONALIZADO:[' . $nombre_logo_max_ancho_jpg . ']');

        if(!file_exists($nombre_logo_max_ancho_jpg)) {

            $this->logger->info("LOGO-PERSONALIZADO NO Existe en Cache...");
            //Generamos Imagen
            $phMagick = &new phMagick($nombre_logo_original_jpg, $nombre_logo_max_ancho_jpg);
            $phMagick->resize($ancho_imagen,$ancho_imagen);

            list($ancho_calculado, $alto_calculado) = $phMagick->getDimentions();
            $this->logger->info("IMAGEN-PERSONALIZADA -> Dimensiones:[". $ancho_calculado ." x " . $alto_calculado ."]");

        } else {

            $this->logger->info("IMAGEN YA EXISTE en Cache");
        }

        $this->formato['imagen'] = $nombre_logo_max_ancho_jpg;

        //margenes dinamicos arriba y abajo
        $margen_arriba = round((($this->formato['ancho'])*10)/320);
        $this->logger->info('MARGEN ARRIBA:[' . $margen_arriba . ']');
        $this->formato['margen_arriba'] = $margen_arriba;
        $this->formato['margen_abajo'] = $margen_arriba;

        //defino el ancho de los costados
        $this->formato['ancho_costado'] = $ancho_navegator1;
        $this->formato['alto_titulo'] = round( $this->formato['ancho_costado']*0.8 );
        $this->formato['tamanho_titulo'] = round( ( $this->formato['ancho']*3 )/16 );//en porcentaje
        $this->formato['tamanho_subtitulo'] = round( ( ( $this->formato['ancho']*3 )/16 )*0.9 );//en porcentaje
        $this->formato['tamanho_textos'] = round( $this->formato['tamanho_titulo']*1.1 );//en porcentaje
        $this->formato['tamanho_separacion'] = round( ( $this->formato['ancho']*110 )/320 );//en porcentaje
    }

    private function _cargarImagenes2( $ancho ) {

        require_once APPLICATION_PATH . '/models/phMagick.php';
        //ubicacion del logo de entermovil
        $this->ua['ancho'] = $ancho;

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
        $this->formato['ancho'] = $this->ua['ancho'];
        $this->logger->info('ANCHO-LOGO:[' . $ancho_logo . ']');
        $this->formato['ancho_logo'] = $ancho_logo;
        $nombre_logo_max_ancho_jpg = 'img/wap-imagenes/cache/logo_entermovil_w' . $ancho_logo . 'px.jpg';
        $this->logger->info('LOGO-PERSONALIZADO:[' . $nombre_logo_max_ancho_jpg . ']');

        if(!file_exists($nombre_logo_max_ancho_jpg)) {

            $this->logger->info("LOGO-PERSONALIZADO NO Existe en Cache...");
            //Generamos Imagen
            $phMagick = new phMagick($nombre_logo_original_jpg, $nombre_logo_max_ancho_jpg);
            $phMagick->resize($ancho_logo);

            list($ancho_calculado, $alto_calculado) = $phMagick->getDimentions();
            $this->logger->info("LOGO-PERSONALIZADO -> Dimensiones:[". $ancho_calculado ." x " . $alto_calculado ."]");

        } else {
            $this->logger->info("Logo YA EXISTE en Cache");
        }

        $this->formato['imagen_logo'] = $nombre_logo_max_ancho_jpg; //envio

        //calculamos el ancho de la tabla blanca
        $ancho_tabla = round((($this->ua['ancho'])*300)/320);
        if($ancho_tabla % 2 != 0){
            //para que tambien tenga resolución par
            $ancho_tabla = $ancho_tabla - 1;
        }
        $this->formato['ancho_tabla'] = $ancho_tabla;
        $this->logger->info('ANCHO-TABLA:[' . $ancho_tabla . ']');

        //agregue navegator layer
        //calculo para cada boton
        $ancho_navegator1 = round((($this->formato['ancho_tabla'])*36)/300);
        $ancho_navegator2 = round((($this->formato['ancho_tabla'])*59)/300);
        $ancho_navegator3 = round((($this->formato['ancho_tabla'])*56)/300);
        $ancho_navegator4 = round((($this->formato['ancho_tabla'])*56)/300);
        $ancho_navegator5 = round((($this->formato['ancho_tabla'])*50)/300);
        $ancho_navegator6 = round((($this->formato['ancho_tabla'])*43)/300);
        $suma_ancho= $ancho_navegator1 + $ancho_navegator2 + $ancho_navegator3 + $ancho_navegator4 + $ancho_navegator5 + $ancho_navegator6;
        $this->logger->info('SUMA-DE-NAVIGATORLAYERS:[' . $suma_ancho . ']');

        //defino el ancho de los costados
        $this->formato['ancho_costado'] = $ancho_navegator1;
        $this->formato['alto_titulo'] = round( $this->formato['ancho_costado']*0.8 );
        $this->formato['tamanho_titulo'] = round( ( $this->formato['ancho']*3 )/16 );//en porcentaje
        $this->formato['tamanho_subtitulo'] = round( ( ( $this->formato['ancho']*3 )/16 )*0.9 );//en porcentaje
        $this->formato['tamanho_textos'] = round( $this->formato['tamanho_titulo']*1.1 );//en porcentaje
        $this->formato['tamanho_separacion'] = round( ( $this->formato['ancho']*110 )/320 );//en porcentaje
        //si no suma el ancho de la tabla entonces le agrego lo que le falta al ultimo navegator layer
        $diferencia = $ancho_tabla - $suma_ancho;
        if( $diferencia > 0){

            $ancho_navegator6 = $ancho_navegator6 + ($ancho_tabla-$suma_ancho);
            $suma_ancho= $ancho_navegator1 + $ancho_navegator2 + $ancho_navegator3 + $ancho_navegator4 + $ancho_navegator5 + $ancho_navegator6;
            $this->logger->info('NUEVA-SUMA-DE-NAVIGATORLAYERS-SIESMENOR:[' . $suma_ancho . ']');
        }
        elseif( $diferencia < 0 ){

            $ancho_navegator1 = $ancho_navegator1 - ($suma_ancho-$ancho_tabla);
            $suma_ancho= $ancho_navegator1 + $ancho_navegator2 + $ancho_navegator3 + $ancho_navegator4 + $ancho_navegator5 + $ancho_navegator6;
            $this->logger->info('NUEVA-SUMA-DE-NAVIGATORLAYERS-SIESMAYOR:[' . $suma_ancho . ']');
        }
        //calculo tambien el alto
        $alto_calculado = round((($this->ua['ancho']*36)/320));
        $this->formato['alto_calculado'] = $alto_calculado;
        //navegador 1
        $nombre_navegator1 = 'img/wap-imagenes/navigator-layer-1-w132x121px.jpg';
        $this->logger->info('NAVEGATOR1-ORIGINAL:[' . $nombre_navegator1 . ']');
        $nombre_navegator1_max_ancho_jpg = 'img/wap-imagenes/cache/navigator_layer1_w' . $ancho_navegator1 . 'px.jpg';
        $this->logger->info('NAVEGATOR1-PERSONALIZADO:[' . $nombre_navegator1_max_ancho_jpg . ']');

        if(!file_exists($nombre_navegator1_max_ancho_jpg)) {

            $this->logger->info("NAVEGATOR1-PERSONALIZADO NO Existe en Cache...");
            //Generamos Imagen
            $phMagick = new phMagick($nombre_navegator1, $nombre_navegator1_max_ancho_jpg);
            $phMagick->resize($ancho_navegator1,$alto_calculado,true);

            list($ancho_calculado, $alto_calculado) = $phMagick->getDimentions();
            $this->logger->info("NAVEGATOR1-PERSONALIZADO -> Dimensiones:[". $ancho_calculado ." x " . $alto_calculado ."]");

        } else {

            $this->logger->info("NAVEGATOR1-PERSONALIZADO YA EXISTE en Cache");
        }
        $this->formato['navigator_layer1'] = $nombre_navegator1_max_ancho_jpg;

        //navegador 2
        $nombre_navegator2 = 'img/wap-imagenes/navigator-layer-2-w197x121px.jpg';
        $this->logger->info('NAVEGATOR2-ORIGINAL:[' . $nombre_navegator2 . ']');

        $nombre_navegator2_max_ancho_jpg = 'img/wap-imagenes/cache/navigator_layer2_w' . $ancho_navegator2 . 'px.jpg';
        $this->logger->info('NAVEGATOR2-PERSONALIZADO:[' . $nombre_navegator2_max_ancho_jpg . ']');

        if(!file_exists($nombre_navegator2_max_ancho_jpg)) {

            $this->logger->info("NAVEGATOR2-PERSONALIZADO NO Existe en Cache...");
            //Generamos Imagen
            $phMagick = new phMagick($nombre_navegator2, $nombre_navegator2_max_ancho_jpg);
            $phMagick->resize($ancho_navegator2,$alto_calculado,true);

            list($ancho_calculado, $alto_calculado) = $phMagick->getDimentions();
            $this->logger->info("NAVEGATOR2-PERSONALIZADO -> Dimensiones:[". $ancho_calculado ." x " . $alto_calculado ."]");

        } else {
            $this->logger->info("NAVEGATOR2-PERSONALIZADO YA EXISTE en Cache");
        }
        $this->formato['navigator_layer2'] = $nombre_navegator2_max_ancho_jpg;

        //visitado-marcado
        $nombre_navegator2 = 'img/wap-imagenes/navigator-layer-2-visited-w197x121px.jpg';
        $this->logger->info('NAVEGATOR2-ORIGINAL:[' . $nombre_navegator2 . ']');

        $nombre_navegator2_max_ancho_jpg = 'img/wap-imagenes/cache/navigator_layer2_visited_w' . $ancho_navegator2 . 'px.jpg';
        $this->logger->info('NAVEGATOR2-PERSONALIZADO:[' . $nombre_navegator2_max_ancho_jpg . ']');

        if(!file_exists($nombre_navegator2_max_ancho_jpg)) {

            $this->logger->info("NAVEGATOR2-PERSONALIZADO NO Existe en Cache...");
            //Generamos Imagen
            $phMagick = new phMagick($nombre_navegator2, $nombre_navegator2_max_ancho_jpg);
            $phMagick->resize($ancho_navegator2,$alto_calculado,true);

            list($ancho_calculado, $alto_calculado) = $phMagick->getDimentions();
            $this->logger->info("NAVEGATOR2-PERSONALIZADO -> Dimensiones:[". $ancho_calculado ." x " . $alto_calculado ."]");

        } else {

            $this->logger->info("NAVEGATOR2-PERSONALIZADO YA EXISTE en Cache");
        }
        $this->formato['navigator_layer2_v'] = $nombre_navegator2_max_ancho_jpg;
        //navegador 3
        $nombre_navegator3 = 'img/wap-imagenes/navigator-layer-3-w192x121px.jpg';
        $this->logger->info('NAVEGATOR3-ORIGINAL:[' . $nombre_navegator3 . ']');
        $nombre_navegator3_max_ancho_jpg = 'img/wap-imagenes/cache/navigator_layer3_w' . $ancho_navegator3 . 'px.jpg';
        $this->logger->info('NAVEGATOR3-PERSONALIZADO:[' . $nombre_navegator3_max_ancho_jpg . ']');

        if(!file_exists($nombre_navegator3_max_ancho_jpg)) {

            $this->logger->info("NAVEGATOR3-PERSONALIZADO NO Existe en Cache...");
            //Generamos Imagen
            $phMagick = new phMagick($nombre_navegator3, $nombre_navegator3_max_ancho_jpg);
            $phMagick->resize($ancho_navegator3,$alto_calculado,true);

            list($ancho_calculado, $alto_calculado) = $phMagick->getDimentions();
            $this->logger->info("NAVEGATOR3-PERSONALIZADO -> Dimensiones:[". $ancho_calculado ." x " . $alto_calculado ."]");

        } else {

            $this->logger->info("NAVEGATOR3-PERSONALIZADO YA EXISTE en Cache");
        }
        $this->formato['navigator_layer3'] = $nombre_navegator3_max_ancho_jpg;
        //marcado-visitado
        $nombre_navegator3 = 'img/wap-imagenes/navigator-layer-3-visited-w192x121px.jpg';
        $this->logger->info('NAVEGATOR3-ORIGINAL:[' . $nombre_navegator3 . ']');
        $nombre_navegator3_max_ancho_jpg = 'img/wap-imagenes/cache/navigator_layer3_visited_w' . $ancho_navegator3 . 'px.jpg';
        $this->logger->info('NAVEGATOR3-PERSONALIZADO:[' . $nombre_navegator3_max_ancho_jpg . ']');

        if(!file_exists($nombre_navegator3_max_ancho_jpg)) {

            $this->logger->info("NAVEGATOR3-PERSONALIZADO NO Existe en Cache...");
            //Generamos Imagen
            $phMagick = new phMagick($nombre_navegator3, $nombre_navegator3_max_ancho_jpg);
            $phMagick->resize($ancho_navegator3,$alto_calculado,true);

            list($ancho_calculado, $alto_calculado) = $phMagick->getDimentions();
            $this->logger->info("NAVEGATOR3-PERSONALIZADO -> Dimensiones:[". $ancho_calculado ." x " . $alto_calculado ."]");

        } else {

            $this->logger->info("NAVEGATOR3-PERSONALIZADO YA EXISTE en Cache");
        }
        $this->formato['navigator_layer3_v'] = $nombre_navegator3_max_ancho_jpg;
        //navegador 4
        $nombre_navegator4 = 'img/wap-imagenes/navigator-layer-4-w192x121px.jpg';
        $this->logger->info('NAVEGATOR4-ORIGINAL:[' . $nombre_navegator4 . ']');
        $nombre_navegator4_max_ancho_jpg = 'img/wap-imagenes/cache/navigator_layer4_w' . $ancho_navegator4 . 'px.jpg';
        $this->logger->info('NAVEGATOR4-PERSONALIZADO:[' . $nombre_navegator4_max_ancho_jpg . ']');

        if(!file_exists($nombre_navegator4_max_ancho_jpg)) {

            $this->logger->info("NAVEGATOR4-PERSONALIZADO NO Existe en Cache...");
            //Generamos Imagen
            $phMagick = new phMagick($nombre_navegator4, $nombre_navegator4_max_ancho_jpg);
            $phMagick->resize($ancho_navegator4,$alto_calculado,true);

            list($ancho_calculado, $alto_calculado) = $phMagick->getDimentions();
            $this->logger->info("NAVEGATOR4-PERSONALIZADO -> Dimensiones:[". $ancho_calculado ." x " . $alto_calculado ."]");

        } else {

            $this->logger->info("NAVEGATOR4-PERSONALIZADO YA EXISTE en Cache");
        }
        $this->formato['navigator_layer4'] = $nombre_navegator4_max_ancho_jpg;
        //marcado-visitado
        $nombre_navegator4 = 'img/wap-imagenes/navigator-layer-4-visited-w192x121px.jpg';
        $this->logger->info('NAVEGATOR4-ORIGINAL:[' . $nombre_navegator4 . ']');
        $nombre_navegator4_max_ancho_jpg = 'img/wap-imagenes/cache/navigator_layer4_visited_w' . $ancho_navegator4 . 'px.jpg';
        $this->logger->info('NAVEGATOR4-PERSONALIZADO:[' . $nombre_navegator4_max_ancho_jpg . ']');

        if(!file_exists($nombre_navegator4_max_ancho_jpg)) {

            $this->logger->info("NAVEGATOR4-PERSONALIZADO NO Existe en Cache...");
            //Generamos Imagen
            $phMagick = new phMagick($nombre_navegator4, $nombre_navegator4_max_ancho_jpg);
            $phMagick->resize($ancho_navegator4,$alto_calculado,true);

            list($ancho_calculado, $alto_calculado) = $phMagick->getDimentions();
            $this->logger->info("NAVEGATOR4-PERSONALIZADO -> Dimensiones:[". $ancho_calculado ." x " . $alto_calculado ."]");

        } else {

            $this->logger->info("NAVEGATOR4-PERSONALIZADO YA EXISTE en Cache");
        }
        $this->formato['navigator_layer4_v'] = $nombre_navegator4_max_ancho_jpg;
        //navegador 5
        $nombre_navegator5 = 'img/wap-imagenes/navigator-layer-5-w193x121px.jpg';
        $this->logger->info('NAVEGATOR5-ORIGINAL:[' . $nombre_navegator5 . ']');
        $nombre_navegator5_max_ancho_jpg = 'img/wap-imagenes/cache/navigator_layer5_w' . $ancho_navegator5 . 'px.jpg';
        $this->logger->info('NAVEGATOR5-PERSONALIZADO:[' . $nombre_navegator5_max_ancho_jpg . ']');

        if(!file_exists($nombre_navegator5_max_ancho_jpg)) {

            $this->logger->info("NAVEGATOR5-PERSONALIZADO NO Existe en Cache...");
            //Generamos Imagen
            $phMagick = new phMagick($nombre_navegator5, $nombre_navegator5_max_ancho_jpg);
            $phMagick->resize($ancho_navegator5,$alto_calculado,true);

            list($ancho_calculado, $alto_calculado) = $phMagick->getDimentions();
            $this->logger->info("NAVEGATOR5-PERSONALIZADO -> Dimensiones:[". $ancho_calculado ." x " . $alto_calculado ."]");

        } else {

            $this->logger->info("NAVEGATOR5-PERSONALIZADO YA EXISTE en Cache");
        }
        $this->formato['navigator_layer5'] = $nombre_navegator5_max_ancho_jpg;
        //marcado-visitado
        $nombre_navegator5 = 'img/wap-imagenes/navigator-layer-5-visited-w193x121px.jpg';
        $this->logger->info('NAVEGATOR5-ORIGINAL:[' . $nombre_navegator5 . ']');
        $nombre_navegator5_max_ancho_jpg = 'img/wap-imagenes/cache/navigator_layer5_visited_w' . $ancho_navegator5 . 'px.jpg';
        $this->logger->info('NAVEGATOR5-PERSONALIZADO:[' . $nombre_navegator5_max_ancho_jpg . ']');

        if(!file_exists($nombre_navegator5_max_ancho_jpg)) {

            $this->logger->info("NAVEGATOR5-PERSONALIZADO NO Existe en Cache...");
            //Generamos Imagen
            $phMagick = new phMagick($nombre_navegator5, $nombre_navegator5_max_ancho_jpg);
            $phMagick->resize($ancho_navegator5,$alto_calculado,true);

            list($ancho_calculado, $alto_calculado) = $phMagick->getDimentions();
            $this->logger->info("NAVEGATOR5-PERSONALIZADO -> Dimensiones:[". $ancho_calculado ." x " . $alto_calculado ."]");

        } else {
            $this->logger->info("NAVEGATOR5-PERSONALIZADO YA EXISTE en Cache");
        }
        $this->formato['navigator_layer5_v'] = $nombre_navegator5_max_ancho_jpg;
        //navegador 6
        $nombre_navegator6 = 'img/wap-imagenes/navigator-layer-6-w119x121px.jpg';
        $this->logger->info('NAVEGATOR6-ORIGINAL:[' . $nombre_navegator6 . ']');
        $nombre_navegator6_max_ancho_jpg = 'img/wap-imagenes/cache/navigator_layer6_w' . $ancho_navegator6 . 'px.jpg';
        $this->logger->info('NAVEGATOR6-PERSONALIZADO:[' . $nombre_navegator6_max_ancho_jpg . ']');

        if(!file_exists($nombre_navegator6_max_ancho_jpg)) {

            $this->logger->info("NAVEGATOR6-PERSONALIZADO NO Existe en Cache...");
            //Generamos Imagen
            $phMagick = new phMagick($nombre_navegator6, $nombre_navegator6_max_ancho_jpg);
            $phMagick->resize($ancho_navegator6,$alto_calculado,true);

            list($ancho_calculado, $alto_calculado) = $phMagick->getDimentions();
            $this->logger->info("NAVEGATOR6-PERSONALIZADO -> Dimensiones:[". $ancho_calculado ." x " . $alto_calculado ."]");

        } else {

            $this->logger->info("NAVEGATOR6-PERSONALIZADO YA EXISTE en Cache");
        }
        $this->formato['navigator_layer6'] = $nombre_navegator6_max_ancho_jpg;

        $nombre_logo_original_jpg = 'img/wap-imagenes/borde-abajo-tabla-w1024x64px.jpg';

        $this->logger->info('BORDE-ABAJO-ORIGINAL:[' . $nombre_logo_original_jpg . ']');

        //borde abajo
        $nombre_logo_max_ancho_jpg = 'img/wap-imagenes/cache/borde_abajo_tabla_w' . $this->formato['ancho_tabla'] . 'px.jpg';
        $this->logger->info('BORDE-ABAJO-PERSONALIZADO:[' . $nombre_logo_max_ancho_jpg . ']');

        if(!file_exists($nombre_logo_max_ancho_jpg)) {

            $this->logger->info("BORDE-ABAJO-PERSONALIZADO NO Existe en Cache...");
            //Generamos Imagen
            $phMagick = new phMagick($nombre_logo_original_jpg, $nombre_logo_max_ancho_jpg);
            $phMagick->resize($this->formato['ancho_tabla']);

            list($ancho_calculado, $alto_calculado) = $phMagick->getDimentions();
            $this->logger->info("BORDE-ABAJO-PERSONALIZADO -> Dimensiones:[". $ancho_calculado ." x " . $alto_calculado ."]");

        } else {

            $this->logger->info("BORDE-ABAJO YA EXISTE en Cache");
        }

        $this->formato['borde_abajo'] = $nombre_logo_max_ancho_jpg;

        //borde arriba
        $nombre_logo_original_jpg = 'img/wap-imagenes/borde-arriba-tabla-w1024x64px.jpg';

        $this->logger->info('BORDE-ARRIBA-ORIGINAL:[' . $nombre_logo_original_jpg . ']');

        $nombre_logo_max_ancho_jpg = 'img/wap-imagenes/cache/borde_arriba_tabla_w' . $this->formato['ancho_tabla'] . 'px.jpg';
        $this->logger->info('BORDE-ARRIBA-PERSONALIZADO:[' . $nombre_logo_max_ancho_jpg . ']');

        if(!file_exists($nombre_logo_max_ancho_jpg)) {

            $this->logger->info("BORDE-ARRIBA-PERSONALIZADO NO Existe en Cache...");
            //Generamos Imagen
            $phMagick = new phMagick($nombre_logo_original_jpg, $nombre_logo_max_ancho_jpg);
            $phMagick->resize($this->formato['ancho_tabla']);
            list($ancho_calculado, $alto_calculado) = $phMagick->getDimentions();
            $this->logger->info("BORDE-ARRIBA-PERSONALIZADO -> Dimensiones:[". $ancho_calculado ." x " . $alto_calculado ."]");

        } else {

            $this->logger->info("BORDE-ARRIBA YA EXISTE en Cache");
        }

        $this->formato['borde_arriba'] = $nombre_logo_max_ancho_jpg;

        // tambien agregue

        $nombre_logo_original_jpg = 'img/wap-imagenes/imagen1_w63x63px.jpg';

        $this->logger->info('IMAGEN-ORIGINAL:[' . $nombre_logo_original_jpg . ']');

        $ancho_imagen = round( (63*($this->formato['ancho']))/320 );

        $this->formato['ancho_imagen'] = $ancho_imagen;

        $this->logger->info('IMAGEN-ANCHO:[' . $ancho_imagen . ']');

        $nombre_logo_max_ancho_jpg = 'img/wap-imagenes/cache/imagen_w' . $ancho_imagen . 'px.jpg';
        $this->logger->info('LOGO-PERSONALIZADO:[' . $nombre_logo_max_ancho_jpg . ']');

        if(!file_exists($nombre_logo_max_ancho_jpg)) {

            $this->logger->info("LOGO-PERSONALIZADO NO Existe en Cache...");
            //Generamos Imagen
            $phMagick = new phMagick($nombre_logo_original_jpg, $nombre_logo_max_ancho_jpg);
            $phMagick->resize($ancho_imagen,$ancho_imagen);

            list($ancho_calculado, $alto_calculado) = $phMagick->getDimentions();
            $this->logger->info("IMAGEN-PERSONALIZADA -> Dimensiones:[". $ancho_calculado ." x " . $alto_calculado ."]");

        } else {

            $this->logger->info("IMAGEN YA EXISTE en Cache");
        }

        $this->formato['imagen'] = $nombre_logo_max_ancho_jpg;

        //margenes dinamicos arriba y abajo
        $margen_arriba = round((($this->formato['ancho'])*10)/320);
        $this->logger->info('MARGEN ARRIBA:[' . $margen_arriba . ']');
        $this->formato['margen_arriba'] = $margen_arriba;
        $this->formato['margen_abajo'] = $margen_arriba;
    }

    private function _consulta($accion, $datos) {

        /*$config = new Zend_Config(array(
                'database' => array(
                    'adapter' => 'Pdo_Pgsql',
                    'params'  => array(
                        'host'     => '190.128.201.42',//'localhost',//
                        'username' => 'postgres',
                        'password' => '',
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

        if($accion == 'SECUENCIA_WAP_REQUEST') {

            $sql = "select nextval('promosuscripcion.suscripciones_wap_requests_request_id_seq'::regclass) as secuencia";
            $rs = $db->fetchRow( $sql);
            $secuencia = 0;
            if($rs){
                $secuencia = $rs['secuencia'];
            }
            $this->logger->info('Secuencia:[' . $secuencia . ']');

            return $secuencia;
        }

        if($accion == 'GET_WAP_REQUEST') {

            $sql = 'select * from promosuscripcion.suscripciones_wap_requests where request_id = ?;';
            $rs = $db->fetchRow( $sql, array( $datos['secuencia'] ) );
            $resultado = array();
            if($rs){
                $resultado = (array)$rs;
            }
            $this->logger->info('WAP_REQUEST:[' . print_r($resultado, true) . ']');

            return $resultado;
        }

        if($accion == 'INSERTAR_WAP_REQUEST') {

            $status= $db->insert('promosuscripcion.suscripciones_wap_requests', $datos);

            return $status;


        }

        if($accion == 'GET_SERVICE_CODE') {
            $sql = 'select IP.numero, IP.id_servicio, TCS.codigo_servicio
            from info_promociones IP
            left join promosuscripcion.tigo_codigos_servicio TCS
            on TCS.id_promocion = IP.id_promocion and TCS.numero = IP.numero and TCS.id_servicio = IP.id_servicio
            where IP.id_promocion = ? and IP.id_carrier = 2';

            $rs = $db->fetchRow( $sql, array( $datos['id_promocion'] ) );
            $resultado = array();
            if($rs){
                $resultado = (array)$rs;
            }
            $this->logger->info('SERVICE_CODE:[' . print_r($resultado, true) . ']');

            return $resultado;
        }

        if( $accion == 'EXISTE_USUARIO' ){

            $sql = "select * from wap.usuarios where cel = ? and id_promocion = ? and id_carrier = ?";
            $rs = $db->fetchRow( $sql, array( $datos['cel'], $datos['id_promocion'], $datos['id_carrier'] ) );
            $resultado = array();
            if($rs){

                $resultado = (array)$rs;
            }
            $this->logger->info('EXISTE_USUARIO:[' . print_r($resultado, true) . ']');

            return $resultado;
        }

        if( $accion == 'INSERTAR_USUARIO' ){

            $status= $db->insert('wap.usuarios', $datos);

            return $status;
        }

        if( $accion == 'ALTA_SUSCRIPTO' ){

            //$status= $db->insert('wap.suscriptos', $datos);
            $status= $db->insert('promosuscripcion.suscriptos', $datos);

            return $status;
        }

        if( $accion == 'INFO_SUSCRIPTO' ){

            //$this->logger->info('INFO-SUSCRIPTOS datos:[' . print_r($datos, true). ']');
            //$sql = 'select * from wap.suscriptos where cel = ? and id_promocion = ? and id_carrier = ?';
            $sql = 'select * from promosuscripcion.suscriptos where cel = ? and id_promocion = ? and id_carrier = ?';

            $rs = $db->fetchAll($sql, array( $datos['cel'], $datos['id_promocion'], $datos['id_carrier'] ) );
            //$this->logger->info('rs-suscripto:[' . print_r($rs, true) . ']');
            $resultado = array();

            if($rs){

                $resultado = (array)$rs[0];
            }

            $this->logger->info('INFO_SUSCRIPTO:[' . print_r($resultado, true) . ']');

            return $resultado;
        }

        if( $accion == 'GET_CATEGORIAS'){

            $sql = "select  id_categoria, id_categoria_padre, nombre_categoria, ultimo_hijo, id_promocion from wap.categoria where estado = 1 and id_promocion = ? order by id_categoria DESC";
            $rs = $db->fetchAll( $sql, array( $datos['id_promocion'] ) );

            $resultado = array();
            foreach( $rs as $fila )
            {
                $resultado[] = (array) $fila;

                if( $fila['id_categoria_padre'] == '0' ){

                    $this->categorias_padre[$fila['nombre_categoria']] = $fila['id_categoria'];
                }

            }
            //$this->logger->info('GET_CATEGORIAS:[' . print_r($resultado, true) . ']');

            return $resultado;

        }

        if( $accion == 'GET_PREVIEW' ){

            $sql = "SELECT * FROM wap.contenidos where id_categoria = ? and nivel <= ? and id_promocion = ? --and prioridad is null
                    ORDER BY prioridad, id_contenido desc
                    LIMIT 3"; //cambie antes era 2 debo manejar las resoluciones para poder modificar
            $rs = $db->fetchAll($sql,array($datos['id_categoria'], $datos['nivel_acceso'], $datos['id_promocion']));
            $resultado = array();
            foreach($rs as $fila){

                $resultado[] = (array) $fila;

            }
            //$this->logger->info('GET_PREVIEW:[' . print_r($resultado, true) . ']');

            if(!empty($resultado)){

                if( $resultado['0']['tipo'] == 'image/jpeg' ){

                    foreach($resultado as $indice => $fila){

                        $resultado[$indice]['path'] = '/' . $this->_convertirImagenes($fila['path']);
                    }

                    //$this->logger->info('GET_PREVIEW_FORMATEADO:[' . print_r($resultado, true) . ']');
                }else if( $resultado['0']['tipo'] == 'video/3gpp' ){

                    foreach($resultado as $indice => $fila){

                        $resultado[$indice]['descripcion'] = '/' . $this->_convertirImagenes($fila['descripcion']);
                    }
                }
            }
            return $resultado;
        }

        if( $accion == 'GET_CONTENIDO' ) {

            $sql = "select T1.id_categoria as categoria, T1.nombre_categoria, T2.id_contenido, T2.nombre_contenido, T2.descripcion, T2.path, T2.tamanho, T2.tipo, T2.descargas, T2.estado, T2.nivel, T2.id_promocion
            from (select id_categoria, nombre_categoria from wap.categoria where id_promocion = ? ) as T1,
            (select * from wap.contenidos where id_promocion = ? and id_categoria = ? and nivel <= ?) as T2
            where T1.id_categoria =  T2.id_categoria order by id_contenido desc";

            $rs = $db->fetchAll( $sql, array( $datos['id_promocion'], $datos['id_promocion'], $datos['id_padre'], $datos['nivel_acceso'] ) );

            //si la categoria es audio no es necesario formatear
            if( $datos['formato'] == 1 ){
                //formateo mi consulta de acuerdo a la resolucion del telefono
                //si es menor a 240 lo formateo a dos imagenes por fila sino 3 por fila
                if( $this->ua['ancho'] >= 240 ){

                    $resultado = array();
                    $fil=0;
                    $col=0;
                    foreach($rs as $fila)
                    {
                        if($col<3){

                            $resultado[$fil][] = (array) $fila;
                        }else{

                            $fil++;
                            $resultado[$fil][] = (array) $fila;
                            $col=0;
                        }
                        $col++;
                    }
                }else{

                    $resultado = array();
                    $fil=0;
                    $col=0;
                    foreach($rs as $fila)
                    {
                        if($col<2){

                            $resultado[$fil][] = (array) $fila;
                        }else{

                            $fil++;
                            $resultado[$fil][] = (array) $fila;
                            $col=0;
                        }
                        $col++;
                    }
                }
                /*print_r($resultado);
                exit;*/
                foreach( $resultado as $indice => $fila ){

                    foreach( $fila as $segundo_indice => $contenido  ){

                        $resultado[ $indice ][ $segundo_indice ][ 'path' ] = '/' . $this->_convertirImagenes($contenido['path']);
                    }
                }

                $this->logger->info('GET_CONTENIDO_FORMATEADO_1: [' . print_r($resultado, true) . ']');
            }else{

                foreach($rs as $fila)
                {
                    $resultado[] = (array) $fila;
                }
            }
            //$this->logger->info('GET_CONTENIDO:[' . print_r($resultado, true) . ']');

            return $resultado;
        }

        if( $accion == 'GET_INFO_PROMOCION' ) {

            $sql = "SELECT * FROM info_promociones WHERE id_carrier = ? AND id_promocion = ?";
            $rs = $db->fetchRow($sql, array(
                $datos['id_carrier'], $datos['id_promocion']
            ));
            $resultado = array();
            if($rs) {

                $resultado = (array) $rs;
            }
            //$this->logger->info('resultado:[' . print_r($resultado, true) . ']');
            return $resultado;
        }

        if( $accion == 'GET_SUSCRIPTO' ) {

            $sql = "SELECT * FROM promosuscripcion.suscriptos WHERE cel = ? AND id_promocion = ? AND id_carrier = ?";
            $rs = $db->fetchRow($sql, array(
                $datos['cel'], $datos['id_promocion'], $datos['id_carrier']
            ));
            $resultado = array();
            if($rs) {

                $resultado = (array) $rs;
            }
            //$this->logger->info('resultado:[' . print_r($resultado, true) . ']');
            return $resultado;
        }

        if( $accion == 'GET_SUSCRIPTO_NIVEL' ) {

            $sql = "SELECT nivel FROM wap.usuarios WHERE cel = ? and id_carrier = ? and id_promocion = ?";
            $rs = $db->fetchRow($sql, array($datos['cel'], $datos['id_carrier'], $datos['id_promocion']));
            $resultado = array();

            $nivel = 0;

            if($rs) {

                $resultado = (array) $rs;
                $nivel = $resultado['nivel'];
            }

            return $nivel;
        }

        if( $accion == 'INSERTAR_SUSCRIPTO' ) {

            $status = $db->insert('promosuscripcion.suscriptos', $datos);
            //$this->logger->info('INSERTAR_SUSCRIPTO -> status:[' . $status . ']');
            return $status;
        }

        if( $accion == 'GET_INFO_RED' ){

            $sql = "SELECT id_carrier, direccion_ip, mascara FROM wap.posibles_origenes WHERE id_pais = ?";
            $rs = $db->fetchAll( $sql, array( $datos['id_pais'] ) );
            $resultado = array();
            foreach($rs as $fila) {

                $resultado[] = (array) $fila;
            }
            //$this->logger->info('GET_INFO_RED:[' . print_r($resultado, true) . ']');
            return $resultado;

        }

        if( $accion == 'GET_ID_PROMOCION' ){

            $sql = "SELECT id_promocion FROM info_promociones WHERE id_carrier = ? and alias = ?";
            $rs = $db->fetchRow( $sql, array( $datos['id_carrier'], $datos['alias'] ) );
            $resultado = array();
            if(  !empty( $rs ) ) {

                $resultado = (array) $rs;
                return $resultado['id_promocion'];
            }else{

                return null;
            }
        }

        if( $accion == 'GET_CONTENIDO_DESCARGAR' ){

            $sql = "SELECT * FROM wap.contenidos where id_contenido = ? and id_promocion = ? and id_categoria = ?";
            $rs = $db->fetchRow($sql, array($datos['id_contenido'], $datos['id_promocion'], $datos['id_categoria']));
            $resultado = array();
            if($rs){

                $resultado = (array)$rs;

            }
            $this->logger->info("GET_CONTENIDO_DESCARGAR" . print_r( $resultado, true ));

            if( isset( $resultado ) ){

                $data = array(

                    'descargas' => $resultado['descargas'] + 1,
                );
                $where = array(

                    'id_promocion = ?' => $resultado['id_promocion'],
                    'id_contenido = ?' => $resultado['id_contenido'],
                    'id_categoria = ?' => $resultado['id_categoria'],
                );
                $n = $db->update('wap.contenidos', $data, $where);
            }

            return $resultado;
        }

        if( $accion == 'GET_INFO_NO_SUSCRIPTO' ){

            $sql = "select DISTINCT c.id_promocion, c.numero, c.alias, c.id_carrier,
                    c.costo_gs, c.costo_usd, c.id_pais, d.iva, d.abreviatura_moneda as moneda from ( select a.id_promocion, a.numero, alias, a.id_carrier as id_carrier_p,
                    costo_gs, costo_usd, b.id_carrier, b.id_pais from info_promociones as a
                    join promociones_x_pais as b on a.id_promocion = b.id_promocion and alias = 'PORTAL'
                    and a.id_promocion != '46' ) as c
                    join paises as d on c.id_pais = d.id_pais";

            $rs = $db->fetchAll($sql);

            $resultado = array();

            foreach( $rs as $fila ){

                $resultado[$fila['id_carrier']] = (array)$fila;
            }
            $this->logger->info('GET_INFO_NO_SUSCRIPTO: ' . print_r( $resultado, true ) );

            return $resultado;
        }

        if( $accion == 'GET_SMS_USUARIO' ){

            $sql = "select * from wap.mensajes where id_promocion = ? and tipo = ? and origen = ? order by id_promocion, tipo_mensaje asc";
            $rs = $db->fetchAll( $sql, array( $datos['id_promocion'], $datos['tipo'], $datos['origen'] ) );
            $resultado = array();
            foreach($rs as $fila){

                $resultado[] = (array)$fila;

            }
            $this->logger->info('GET_SMS_USUARIO: ' . print_r( $resultado, true ) );

            return $resultado;
        }

        if( $accion == 'GET_BANNER' ){

            $sql = "select * from wap.banners where nivel = ? and id_promocion = ? and id_categoria = ? order by orden ASC";
            $rs = $db->fetchAll( $sql, array( $datos['nivel'], $datos['id_promocion'], $datos['id_categoria'] ) );
            $resultado = array();
            foreach( $rs as $fila ){

                $resultado[] = (array)$fila;
            }
            $this->logger->info( 'GET_BANNER: '. print_r($resultado, true) );

            return $resultado;

        }

        if( $accion == 'OBTENER_CONFIGURACION' ){

            $sql = "select 	tipo_vista, banner, numero_vista, nombre_vista, mostrar_categoria from wap.wap_configuracion where id_promocion = ? order by numero_vista asc";
            $rs = $db->fetchAll( $sql, array( $datos['id_promocion'] ) );

            if( !empty( $rs ) ){

                $resultado = array();
                foreach( $rs as $fila ){

                    $resultado[] = (array)$fila;
                }

                return $resultado;

            }else{

                return null;
            }
        }

        if( $accion == 'LOGEAR_ACCION' ){

            $datos['fecha_hora'] = 'now';
            $status = $db->insert('wap.log_accion_wap', $datos);
            //$this->logger->info('INSERTAR_SUSCRIPTO -> status:[' . $status . ']');
            return $status;
        }

        if( $accion == 'LOGEAR_ACTIVACION' ){

            $status = $db->insert('wap.log_activaciones', $datos);
            $newID = $db->lastSequenceId('wap.log_activaciones_id_seq');
            $this->logger->info('INSERT_CONTENIDO' . $status);

            return $newID;
        }

        if( $accion == 'LOGEAR_ERROR' ){

            $datos['fecha_hora'] = 'now';
            $status = $db->insert('wap.log_errores_wap', $datos);
            //$this->logger->info('INSERTAR_SUSCRIPTO -> status:[' . $status . ']');
            return $status;
        }

        if( $accion == 'ACTUALIZAR_LOG_ACTIVACION' ){

            $data = array(
                'fecha_hora_alta' => 'now',
                'suscripto' => 'true',
            );
            $where = array(
                'id = ?' => $datos['id_activacion']
            );

            $status = $db->update('wap.log_activaciones', $data, $where);
            return $status;
        }
    }

    //crea un array de los preview formateado para desplegar en categorias
    private function _generarPreview(){

        //preview anda perfecto
        $preview_contenidos= array();
        $contenido_formateado = array();
        $estructura_auxliar = array(

            '1' => array(
                'cantidad_contenidos' => 0,
                'cantidad_contenidos_categoria' => 0,
            ),
            '2' => array(
                'cantidad_contenidos' => 0,
                'cantidad_contenidos_categoria' => 0,
            ),
            '3' => array(
                'cantidad_contenidos' => 0,
                'cantidad_contenidos_categoria' => 0,
            ),
        );

        if( !empty( $this->preview ) ){

            foreach ( $this->preview as $id_padre => $datos_hijos ){
                foreach( $datos_hijos as $id_hijo => $contenidos ){
                    //if( $id_hijo != '39' ){
                    foreach( $contenidos as $i => $datos ){
                        if( !is_null( $datos['prioridad'] ) ){
                            $preview_contenidos[$id_padre][] = $datos;
                            $estructura_auxliar[$id_padre]['cantidad_contenidos']++;
                        }
                        $estructura_auxliar[$id_padre]['cantidad_contenidos_categoria']++;
                    }
                    //}
                }
            }
            foreach ( $this->preview as $id_padre => $datos_hijos ){
                $indice = 0;
                while( $estructura_auxliar[$id_padre]['cantidad_contenidos'] < 6 && $estructura_auxliar[$id_padre]['cantidad_contenidos'] != $estructura_auxliar[$id_padre]['cantidad_contenidos_categoria']){
                    foreach( $datos_hijos as $id_hijo => $contenidos ){
                        if( $id_hijo != '39' ){
                            if( isset( $this->preview[$id_padre][$id_hijo][$indice] ) && ( $this->preview[$id_padre][$id_hijo][$indice]['prioridad'] == null ) && ( $estructura_auxliar[$id_padre]['cantidad_contenidos'] < 6 ) ){
                                $preview_contenidos[$id_padre][] = $this->preview[$id_padre][$id_hijo][$indice];
                                $estructura_auxliar[$id_padre]['cantidad_contenidos']++;
                            }
                        }
                    }
                    $indice++;
                }
            }

            /*print_r($estructura_auxliar);
            print_r($preview_contenidos);
            exit;*/

            $contenido_formateado = $this->_formatearContenidos( $preview_contenidos );

            return $contenido_formateado;

        }else{

            $contenido_formateado = null;
            return $contenido_formateado;
        }
    }

    private function _formatearContenidos( $datos ){

        $resultado = array();
        foreach( $datos as $categoria => $id_categoria ){

            if( $categoria == 1 ){
                //formateo mi consulta de acuerdo a la resolucion del telefono
                //si es menor a 240 lo formateo a dos imagenes por fila sino 3 por fila
                $result[$categoria] = array();
                if( $this->ua['ancho'] >= 240 ){

                    $resultado = array();
                    $fil=0;
                    $col=0;
                    foreach($id_categoria as $contenido){

                        if( $col <= 2 ){

                            $resultado[$fil][] = $contenido;
                        }else{

                            $fil++;
                            if($fil != 2){

                                $resultado[$fil][] = $contenido;
                                $col=0;
                            }else{

                                break;
                            }
                        }
                        $col++;
                    }
                    $result[$categoria] = $resultado;
                }else{

                    $resultado = array();
                    $fil=0;
                    $col=0;
                    foreach($id_categoria as $contenido){

                        if($col<2){

                            $resultado[$fil][] = $contenido;
                        }else{

                            $fil++;
                            if($fil != 2){

                                $resultado[$fil][] = $contenido;
                                $col=0;
                            }else{
                                break;
                            }
                        }
                        $col++;
                    }
                    $result[$categoria] = $resultado;
                }

            }else if( $categoria == 2 ){

                $fil = 0;
                $resultado = array();
                foreach( $id_categoria as $contenido ){

                    if( $fil < 6 ){

                        $resultado[] = $contenido;
                    }else{

                        break;
                    }
                }
                $result[$categoria] = $resultado;
            }else if( $categoria == 3 ){

                $result[$categoria] = array();
                if( $this->ua['ancho'] >= 240 ){

                    $resultado = array();
                    $fil=0;
                    $col=0;
                    foreach($id_categoria as $contenido){

                        if( $col <= 2 ){

                            $resultado[$fil][] = $contenido;
                        }else{

                            $fil++;
                            if($fil != 2){

                                $resultado[$fil][] = $contenido;
                                $col=0;
                            }else{

                                break;
                            }
                        }
                        $col++;
                    }
                    $result[$categoria] = $resultado;
                }else{

                    $resultado = array();
                    $fil=0;
                    $col=0;
                    foreach($id_categoria as $contenido){

                        if($col<2){

                            $resultado[$fil][] = $contenido;
                        }else{

                            $fil++;
                            if($fil != 2){

                                $resultado[$fil][] = $contenido;
                                $col=0;
                            }else{
                                break;
                            }
                        }
                        $col++;
                    }
                    $result[$categoria] = $resultado;
                }
            }
        }

        return $result;
    }

    private function _buildTree(Array $data, $parent = 0, $nivel) {

        $tree = array();
        foreach ($data as $d) {

            if ($d['id_categoria_padre'] == $parent) {

                $children = $this->_buildTree($data, $d['id_categoria'], $nivel);

                if (!empty($children)) {

                    $d['hijos'] = $children;
                }
                $tree[$d['id_categoria']] = $d;
                $datos = array(
                    'id_promocion' => $d['id_promocion'],
                    'id_categoria' => $d['id_categoria'],
                    'nivel_acceso' => $nivel,
                );
                $preview = $this->_consulta('GET_PREVIEW',$datos);
                if(!empty($preview)){

                    $tree[$d['id_categoria']]['preview'] = $preview;
                }

                /*                $fijos = $this->_consulta('GET_CONTENIDOS_MAYOR_PRIORIDAD', $datos);

                                if(!empty($fijos)){
                                    $tree[$d['id_categoria']]['fijos'] = $fijos;
                                }*/
            }
        }
        return $tree;
    }

    private function _preview($tree, $r = 0, $p = 0) {

        foreach ($tree as $i => $t) {

            if ($t['id_categoria_padre'] == '0') {
                // reset $r
                $r = $t['id_categoria'];
            }
            if (isset($t['hijos'])) {

                $this->_preview($t['hijos'],$r, $t['id_categoria_padre']);
            }else if($t['ultimo_hijo'] == 'true'){

                $p = $t['id_categoria'];
                if(isset($t['preview'])){
                    $this->preview[$r][$p] = $t['preview'];
                }
                if(isset( $t['fijos'] )){
                    $this->preview[$r][$p]['fijos'] = $t['fijos'];
                }
            }
        }
    }

    private function _convertirImagenes( $path ){

        require_once APPLICATION_PATH . '/models/phMagick.php';

        $nombre_logo_original_jpg = $path;

        $nombre_archivo = basename($path);

        $this->logger->info('IMAGEN-ORIGINAL:[' . $nombre_logo_original_jpg . ']');

        $ancho_imagen = round( (63*($this->formato['ancho']))/320 );

        $this->logger->info('IMAGEN-ANCHO:[' . $ancho_imagen . ']');

        $nombre_logo_max_ancho_jpg = 'img/wap-imagenes/cache/imagen_w' . $ancho_imagen . $nombre_archivo . 'px.jpg';
        $this->logger->info('LOGO-PERSONALIZADO:[' . $nombre_logo_max_ancho_jpg . ']');

        if(!file_exists($nombre_logo_max_ancho_jpg)) {

            $this->logger->info("LOGO-PERSONALIZADO NO Existe en Cache...");
            //Generamos Imagen
            $phMagick = &new phMagick($nombre_logo_original_jpg, $nombre_logo_max_ancho_jpg);
            $phMagick->resize($ancho_imagen,$ancho_imagen);

            list($ancho_calculado, $alto_calculado) = $phMagick->getDimentions();
            $this->logger->info("IMAGEN-PERSONALIZADA -> Dimensiones:[". $ancho_calculado ." x " . $alto_calculado ."]");

        } else {

            $this->logger->info("IMAGEN YA EXISTE en Cache");
        }

        return $nombre_logo_max_ancho_jpg;
    }

    private function _convertirImagenesBanners( $path, $alias ){

        require_once APPLICATION_PATH . '/models/phMagick.php';

        $namespace= new Zend_Session_Namespace('ENTERMOVIL_PORTAL_ESCOBAR');

        $this->ua['ancho'] = $namespace->ancho;

        $nombre_logo_original_jpg = $path;

        $nombre_archivo = basename($path);

        $this->logger->info('IMAGEN-ORIGINAL:[' . $nombre_logo_original_jpg . ']');

        $ancho_imagen = round( ( $this->ua['ancho'] )*0.85 );
        //$alto_imagen = 50;

        $this->logger->info('IMAGEN-ANCHO:[' . $ancho_imagen . ']');

        $nombre_logo_max_ancho_jpg = 'img/wap-imagenes/cache/imagen_banner_w' . $ancho_imagen .'_'. $nombre_archivo . '_' . $alias;
        $this->logger->info('LOGO-PERSONALIZADO:[' . $nombre_logo_max_ancho_jpg . ']');

        if(!file_exists($nombre_logo_max_ancho_jpg)) {

            $this->logger->info("LOGO-PERSONALIZADO NO Existe en Cache...");
            //Generamos Imagen
            $phMagick = &new phMagick($nombre_logo_original_jpg, $nombre_logo_max_ancho_jpg);
            $phMagick->resize( $ancho_imagen );

            list($ancho_calculado, $alto_calculado) = $phMagick->getDimentions();
            $this->logger->info("IMAGEN-PERSONALIZADA -> Dimensiones:[". $ancho_calculado ." x " . $alto_calculado ."]");

        } else {

            $this->logger->info("IMAGEN YA EXISTE en Cache");
        }

        return '/' .$nombre_logo_max_ancho_jpg;
    }

    private function _estaSuscripto( $datos ){

        //verificar si esta suscripto
        $suscripto = $this->_consulta( 'INFO_SUSCRIPTO', $datos );

        if( !empty( $suscripto ) ){

            return true;

        }else{

            return false;
        }
    }

    private function _procesarSolicitud( $alias ){

        $this->logger->info('_procesarSolicitud');
        $namespace= new Zend_Session_Namespace('ENTERMOVIL_PORTAL_ESCOBAR');
        if(!isset($namespace->alias) || empty($namespace->alias)){

            $this->_redirect('/waptwo/aps');
        }

        if( !is_null( $this->nroCel ) && !is_null( $alias ) ){

            if( $namespace->id_promocion == '77' ){

                $this->_cargarImagenes( $namespace->ancho );
                $namespace->formato = $this->formato;

            }else{

                $this->_cargarImagenes2( $namespace->ancho );
                $namespace->formato = $this->formato;
            }

            //ver que se pasa aca
            $categorias = array();
            $datos['id_promocion'] = $namespace->id_promocion;
            $categorias = $this->_consulta( 'GET_CATEGORIAS', $datos );
            $categorias = $this->_buildTree( $categorias, 0, $namespace->nivel ) ;

            $namespace->contenidos['categorias'] = $categorias;
            $this->contenidos['categorias'] = $categorias;
            $this->_preview($categorias);

            /*print_r($this->preview);
           exit;*/
            //$namespace->contenidos['preview'] = $this->preview;

            $namespace->contenidos['preview'] = $this->_generarPreview( );

            /*  print_r($namespace->contenidos['preview']);
              exit;*/

        } else {

            $this->logger->err('procesar-solicitud-error-IF');
        }
    }

    public function obtenerBanner( $n, $id_categoria = null ){

        $this->logger->info('obtenerBanner');

        $namespace= new Zend_Session_Namespace('ENTERMOVIL_PORTAL_ESCOBAR');
        if( !isset( $namespace->alias ) || empty( $namespace->alias ) ){

            $this->logger->info('SOLICITUD NO VALIDA');
            $this->_redirect('/waptwo/aps');
        }
        $this->logger->info('nro_banner: '. $namespace->banner[$id_categoria]);
        $this->logger->info('next_nro_banner: '. ($namespace->banner[$id_categoria] + $n));
        $namespace->banner[$id_categoria] += $n;
        $banner = array();
        $datos['nivel'] = $namespace->nivel;
        $datos['id_promocion'] = $namespace->id_promocion;
        $datos['id_categoria'] = $id_categoria;

        $this->logger->info('DATOS[ ' . print_r($datos, true) . ']');

        $banner = $this->_consulta('GET_BANNER', $datos);

        $ciclo = count($banner);

        $this->logger->info('Nro de elementos: ' . $ciclo );

        if( $ciclo != 0 ){
            if( $namespace->banner[$id_categoria] <= ( $ciclo - 1 ) ){

                $this->logger->info('Elemento: ' . $namespace->banner[$id_categoria] );
                //print_r($banner);
                $banner = $this->_convertirImagenesBanners( $banner[$namespace->banner[$id_categoria]]['path'], $namespace->alias );

            }else{

                $namespace->banner[$id_categoria]= 0;
                $banner = $this->_convertirImagenesBanners( $banner[$namespace->banner[$id_categoria]]['path'] , $namespace->alias);
            }
        }else{

            $banner = "";
        }
        return $banner;
    }

    public function siglasPreviewAction(){

        $namespace= new Zend_Session_Namespace('ENTERMOVIL_PORTAL_ESCOBAR');
        if(!isset($namespace->alias) || empty($namespace->alias)){

            $this->_redirect('waptwo/aps');
        }else{

            $this->_procesarSolicitud( $namespace->alias );
            $fullUrl =  $this->view->url();
            $info_accion['id_promocion'] = $namespace->id_promocion;
            $info_accion['cel'] = $this->info_usuario['cel'];;
            $info_accion['id_carrier'] = $this->info_usuario['id_carrier'];
            $info_accion['url'] = $fullUrl;
            $this->_logearAccion( $info_accion );
        }
        $this->logger->info('DAAS ' .  $namespace->alias );
        $this->logger->info('sesion: ' .  print_r($namespace, true) );
        $this->_helper->_layout->setLayout($this->layouts[$namespace->id_promocion]);
        $this->view->ancho_tabla = $this->formato['ancho_tabla'];

        $this->view->ua = $this->formato;

        $this->view->id_categoria = $this->categorias_padre['imagenes'];

        $this->view->contenidos = $this->contenidos['categorias'][$this->view->id_categoria]['hijos'];

        $iniciales = $this->_getParam( 'siglas', null );

        if( !is_null( $iniciales ) ){

            $this->view->sigla_tipeada = $iniciales;
        }
    }

    public function siglasAction() {

        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $iniciales = $this->_getParam('iniciales', 'XXXX');
        $parametros['id-c'] = $this->_getParam('id-c', null);
        $parametros['id'] = $this->_getParam('id', null);
        $parametros['id-p'] = $this->_getParam('id-p', null);

        $fullUrl =  $this->view->url();
        $info_accion['id_promocion'] = $parametros['id-p'];
        $info_accion['cel'] = $this->info_usuario['cel'];;
        $info_accion['id_carrier'] = $this->info_usuario['id_carrier'];
        $info_accion['url'] = $fullUrl;
        $this->_logearAccion( $info_accion );

        $datos = array(

            'id_categoria' => $parametros['id-c'],
            'id_contenido' => $parametros['id'],
            'id_promocion' => $parametros['id-p'],
        );

        $info_contenido = $this->_consulta('GET_CONTENIDO_DESCARGAR', $datos);

        $path_fondo = $info_contenido['path'];
        $this->logger->info('path_archivo:[' . $path_fondo . ']');
        $path_archivo_descarga = $this->generarFondoPersonalizado($path_fondo, $iniciales);
        $this->logger->info('path_archivo_descarga:[' . $path_archivo_descarga . ']');
        $nombre_archivo_descarga = basename($path_archivo_descarga);
        $this->logger->info('nombre_archivo_descarga:[' . $nombre_archivo_descarga . ']');

        //$this->getResponse()->setHeader('Content-Type', 'text/plain');
        $this->getResponse()->setHeader('Content-Type', 'image/jpeg');
        readfile($path_archivo_descarga);
        //echo 'hola';
    }

    private function generarFondoPersonalizado($path_fondo, $iniciales) {

        $this->logger->info('generarFondoPersonalizado() -> path_fondo:[' . $path_fondo . ']');
        $this->logger->info('generarFondoPersonalizado() -> iniciales:[' . $iniciales . ']');
        $path_resultado = null;
        $path_fondos_personalizados = APPLICATION_PATH . '/fondospersonalizados';
        $this->logger->info('generarFondoPersonalizado() -> path_fondos_personalizados:[' . $path_fondos_personalizados . ']');
        $path_aplicacion = $path_fondos_personalizados . '/ImagenAArchivo_con_shadow.jar';
        $this->logger->info('generarFondoPersonalizado() -> path_aplicacion:[' . $path_aplicacion . ']');
        $path_cache = $path_fondos_personalizados . '/cache';
        $this->logger->info('generarFondoPersonalizado() -> path_cache:[' . $path_cache . ']');
        $nombre_final = $this->paraNombreArchivo($iniciales) . '_' . basename($path_fondo);
        $this->logger->info('generarFondoPersonalizado() -> nombre_final:[' . $nombre_final . ']');
        $path_final = $path_cache . '/' . $nombre_final;

        $directorio_inicial = getcwd();
        chdir($path_fondos_personalizados);
        $this->logger->info('directorio_inicial:[' . $directorio_inicial . '] nuevo:[' . getcwd() . ']');

        if(file_exists($path_final)) {//Si existe en /cache

            $this->logger->info('generarFondoPersonalizado() -> Ya existe en Cache');
            $path_resultado = $path_final;

        } else {

            //Copiamos temporalmente el fondo en la carpeta de trabajo
            $path_fondo_temporal = $path_fondos_personalizados . '/' . basename($path_fondo);
            if(!file_exists($path_fondo_temporal)) {
                @copy($path_fondo, $path_fondo_temporal);
                $this->logger->info('copiar [' . $path_fondo . '] a [' . $path_fondo_temporal . ']');
                if(file_exists($path_fondo_temporal)) {
                    $this->logger->info('Se COPIO CON EXITO!!!');
                } else {
                    $this->logger->err('NO SE PUDO COPIAR FONDO!!!');
                }
            } else {
                $this->logger->info('Ya existe archivo FONDO:[' . basename($path_fondo) . ']');
            }


            $tpl_comando = '/usr/bin/java -Dsun.jnu.encoding=UTF-8 -Dfile.encoding=UTF-8 -jar {PATH_APLICACION} -path {PATH_IM} -fondo {PATH_FONDO} -dimension {DIMENSION} -iniciales "{INICIALES}" -color \\#FFFFFF -bcolor \\#FFFFFF -xsombra 3 -ysombra 3 -colorSombra \\#000000 -margen 20 -trans 40 -dpi {DPI} -origen 1500 -salida {PATH_SALIDA}';
            $path_aplicacion = 'ImagenAArchivo_con_shadow.jar';
            $tpl_origen = array(

                '{PATH_APLICACION}', '{PATH_IM}', '{PATH_FONDO}', '{DIMENSION}', '{INICIALES}', '{DPI}', '{PATH_SALIDA}'
            );
            $namespace= new Zend_Session_Namespace('ENTERMOVIL_PORTAL_ESCOBAR');
            $this->ua['ancho'] = $namespace->ancho;
            $this->ua['alto'] = $namespace->alto;

            $dimension = $this->ua['ancho']. 'x'. $this->ua['alto'];
            $this->logger->info( 'dimensiones: ' . $dimension );
            //$dimension = '480x800';

            $tpl_destino = array(
                $path_aplicacion,
                '/usr/bin/',
                basename($path_fondo_temporal),
                $dimension,
                $this->paraParametroIniciales($iniciales),
                72,
                basename($path_final)
            );//voy a cambiar 172x287 por this->ua['ancho']
            $comando_generar_fondo_personalizado = str_replace($tpl_origen, $tpl_destino, $tpl_comando);

            $this->logger->info('generarFondoPersonalizado() -> comando:[' . $comando_generar_fondo_personalizado . ']');
            $resultado = exec($comando_generar_fondo_personalizado, $salida);
            $this->logger->info('generarFondoPersonalizado() -> salida_comando:[' . print_r($salida, true) . ']');

            if( ( $namespace->ancho > 1000 ) || ( $namespace->alto > 1000 ) ){

                foreach( $salida as $indice => $comando ){

                    $this->logger->info( 'comando a ejecutar: ' . $comando );
                    $ejecutar_comando = $this->_ejecutar_comando( $comando );
                }
            }
            
            $this->logger->info('generarFondoPersonalizado() -> verificando si existe:[' . basename($path_final) . ']');
            if(file_exists(basename($path_final))) {
                $this->logger->info('generarFondoPersonalizado() -> EXISTE Archivo:[' . basename($path_final) . ']');
                @rename(basename($path_final), $path_final);
                $this->logger->info('generarFondoPersonalizado() -> Movemos a /cache');
                $path_resultado = $path_final;
            } else {
                $this->logger->err('generarFondoPersonalizado() -> NO SE CREO EL ARCHIVO!!!');
                $path_resultado = null;
            }

            chdir($directorio_inicial);
            $this->logger->info('chdir:[' . getcwd() . ']');

        }

        //@unlink($path_fondo_temporal);

        return $path_resultado;
    }

    private function paraParametroIniciales($iniciales) {

        $traduccion = array(
            "\\" => "\\\\",
            "\"" => "\\\"",
            "&" => "\&",
            "#" => "\#"
        );

        $iniciales_escapeadas = strtr($iniciales, $traduccion);
        $iniciales_escapeadas = utf8_encode($iniciales_escapeadas);

        return $iniciales_escapeadas;
    }

    private function paraNombreArchivo($nombre) {

        $this->logger->info('ANALISIS PARA-NOMBRE-ARCHIVO');
        $longitud = strlen($nombre);
        for($i=0;$i<$longitud;$i++) {
            $this->logger->info('[' . $nombre[$i] . '] ascii:[' . ord($nombre[$i]) . ']');
        }

        $nuevo_nombre = preg_replace("/[^a-zA-Z0-9\.]/","-", $nombre);

        $this->logger->info("NuevoNombre:[" . $nuevo_nombre . ']');

        return $nuevo_nombre;
    }
    //dessetear el usuario
    public function logoutAction() {

        $namespace = new Zend_Session_Namespace("ENTERMOVIL_PORTAL_ESCOBAR");

        $namespace->unsetAll();
        unset($namespace);
        exit;
    }

    private function _logearAccion( $info_accion ){

        $this->_consulta('LOGEAR_ACCION' , $info_accion );
        return;
    }

    private function _logearErrores( $info_error ){

        $this->_consulta( 'LOGEAR_ERROR' , $info_error );
        return;
    }
    //comienza wap
    public function categoriasAction(){

        $namespace= new Zend_Session_Namespace('ENTERMOVIL_PORTAL_ESCOBAR');
        if( !isset( $namespace->alias ) || empty( $namespace->alias ) ){

            $this->logger->info('SOLICITUD NO VALIDA');
            //$this->_redirect('/waptwo/aps');
            if( $this->ua['is_mobile'] ){

                //mensaje por defecto porque no se id_carrier ni pais
                $this->_helper->layout->setLayout('wap-layout');
                $this->_cargarImagenes2();
                $this->view->ua = $this->formato;
                $this->view->mensaje = array(
                    'Debe utilizar la conexión 3G de su celular',
                    'Enviar PORTAL al 35500 o',
                    'Enviar PATRON al 35500',
                    'para suscribirse',
                    'Desde tu TIGO o PERSONAL'
                );
                //mostrar vista apropiada
                $this->_helper->viewRenderer('mensaje-error');
                return;

            }else{

                $this->view->es_movil = false;
                $this->_helper->layout->disableLayout();
                $this->view->formato = $this->ua;
                //mostrar vista apropiada
                $this->_helper->viewRenderer('desktop');
                return;
            }

            return;
        }else{

            $info_accion = array();
            $fullUrl =  $this->view->url();
            $info_accion['id_promocion'] = $namespace->id_promocion;
            $info_accion['cel'] = $namespace->cel;
            $info_accion['id_carrier'] = $namespace->id_carrier;
            $info_accion['url'] = $fullUrl;
            $this->_logearAccion( $info_accion );
        }
        //seteao el layout de su promocion
        $this->_helper->_layout->setLayout( $this->layouts[$namespace->id_promocion] );

        $this->view->padres = $this->categorias_padre;
        $this->view->ancho_tabla = $namespace->formato['ancho_tabla'];
        $this->view->ua = $namespace->formato;
        $this->view->contenidos = $namespace->contenidos['preview'];
        $this->view->controller = $this;
        $this->view->sigla_tipeada = $namespace->siglas;
        $configuracion = array(

            'banner' => $namespace->configuracion['0']['banner'],//si tiene banner
            'mostrar' => $namespace->configuracion['0']['mostrar_categoria'], //si tiene siglas
        );

        $this->logger->info( print_r( $configuracion, true ) );

        $this->view->configuracion = $configuracion;


        if( $this->getRequest()->isPost() ){

            $formData = $this->getRequest()->getPost();

            if( isset( $formData ) ){

                $this->view->sigla_tipeada = $formData['iniciales'];
                $namespace->siglas = $this->view->sigla_tipeada;
            }
        }

    }

    public function imagenesAction(){

        $namespace= new Zend_Session_Namespace('ENTERMOVIL_PORTAL_ESCOBAR');
        if( !isset( $namespace->alias ) || empty( $namespace->alias ) ){

            $this->logger->info('SOLICITUD NO VALIDA');
            //$this->_redirect('/waptwo/aps');
            if( $this->ua['is_mobile'] ){

                //mensaje por defecto porque no se id_carrier ni pais
                $this->_helper->layout->setLayout('wap-layout');
                $this->_cargarImagenes2();
                $this->view->ua = $this->formato;
                $this->view->mensaje = array(
                    'Debe utilizar la conexión 3G de su celular',
                    'Enviar PORTAL al 35500 o',
                    'Enviar PATRON al 35500',
                    'para suscribirse',
                    'Desde tu TIGO o PERSONAL'
                );
                //mostrar vista apropiada
                $this->_helper->viewRenderer('mensaje-error');
                return;

            }else{

                $this->view->es_movil = false;
                $this->_helper->layout->disableLayout();
                $this->view->formato = $this->ua;
                //mostrar vista apropiada
                $this->_helper->viewRenderer('desktop');
                return;
            }
        }else{

            //setear log
            $info_accion = array();
            $fullUrl = $this->view->url();
            $info_accion['id_promocion'] = $namespace->id_promocion;
            $info_accion['cel'] = $namespace->cel;
            $info_accion['id_carrier'] = $namespace->id_carrier;
            $info_accion['url'] = $fullUrl;
            $this->_logearAccion( $info_accion );
        }

        //seteao el layout de su promocion
        $this->_helper->_layout->setLayout( $this->layouts[$namespace->id_promocion] );

        $this->view->ancho_tabla = $namespace->formato['ancho_tabla'];
        $this->view->ua = $namespace->formato;
        $this->view->controller = $this;
        $this->view->id_categoria = '1';
        $this->view->contenidos = $namespace->contenidos['categorias'][$this->view->id_categoria]['hijos'];
        $configuracion = array(
            'tipo' => $namespace->configuracion['1']['tipo_vista'],//tipo vista
            'banner' => $namespace->configuracion['1']['banner'],//si tiene banner
        );

        $this->view->configuracion = $configuracion;

        $this->logger->info( print_r( $configuracion, true ) );
    }

    public function audiosAction(){

        $namespace= new Zend_Session_Namespace('ENTERMOVIL_PORTAL_ESCOBAR');
        if(!isset($namespace->alias) || empty($namespace->alias)){

            $this->logger->info('SOLICITUD NO VALIDA');
            //$this->_redirect('/waptwo/aps');
            if( $this->ua['is_mobile'] ){

                //mensaje por defecto porque no se id_carrier ni pais
                $this->_helper->layout->setLayout('wap-layout');
                $this->_cargarImagenes2();
                $this->view->ua = $this->formato;
                $this->view->mensaje = array(
                    'Debe utilizar la conexión 3G de su celular',
                    'Enviar PORTAL al 35500 o',
                    'Enviar PATRON al 35500',
                    'para suscribirse',
                    'Desde tu TIGO o PERSONAL'
                );
                //mostrar vista apropiada
                $this->_helper->viewRenderer('mensaje-error');
                return;

            }else{

                $this->view->es_movil = false;
                $this->_helper->layout->disableLayout();
                $this->view->formato = $this->ua;
                //mostrar vista apropiada
                $this->_helper->viewRenderer('desktop');
                return;
            }
        }else{

            $info_accion = array();
            $fullUrl =  $this->view->url();
            $info_accion['id_promocion'] = $namespace->id_promocion;
            $info_accion['cel'] = $namespace->cel;
            $info_accion['id_carrier'] = $namespace->id_carrier;
            $info_accion['url'] = $fullUrl;
            $this->_logearAccion( $info_accion );
        }

        //seteao el layout de su promocion
        $this->_helper->_layout->setLayout( $this->layouts[$namespace->id_promocion] );

        $this->view->ancho_tabla = $namespace->formato['ancho_tabla'];
        $this->view->ua = $namespace->formato;
        $this->view->contenidos = $namespace->contenidos;
        $this->view->controller = $this;
        $this->view->id_categoria = '2';
        $configuracion = array(

            'tipo' => $namespace->configuracion['2']['tipo_vista'],//tipo vista
            'banner' => $namespace->configuracion['2']['banner'],//si tiene banner
        );

        $this->view->configuracion = $configuracion;

        $this->logger->info( print_r( $configuracion, true ) );
    }

    public function videosAction(){

        $namespace= new Zend_Session_Namespace('ENTERMOVIL_PORTAL_ESCOBAR');
        if(!isset($namespace->alias) || empty($namespace->alias)){

            $this->logger->info('SOLICITUD NO VALIDA');
            //$this->_redirect('/waptwo/aps');
            if( $this->ua['is_mobile'] ){

                //mensaje por defecto porque no se id_carrier ni pais
                $this->_helper->layout->setLayout('wap-layout');
                $this->_cargarImagenes2();
                $this->view->ua = $this->formato;
                $this->view->mensaje = array(
                    'Debe utilizar la conexión 3G de su celular',
                    'Enviar PORTAL al 35500 o',
                    'Enviar PATRON al 35500',
                    'para suscribirse',
                    'Desde tu TIGO o PERSONAL'
                );
                //mostrar vista apropiada
                $this->_helper->viewRenderer('mensaje-error');
                return;

            }else{

                $this->view->es_movil = false;
                $this->_helper->layout->disableLayout();
                $this->view->formato = $this->ua;
                //mostrar vista apropiada
                $this->_helper->viewRenderer('desktop');
                return;
            }
        }else{

            $info_accion = array();
            $fullUrl =  $this->view->url();
            $info_accion['id_promocion'] = $namespace->id_promocion;
            $info_accion['cel'] = $namespace->cel;
            $info_accion['id_carrier'] = $namespace->id_carrier;
            $info_accion['url'] = $fullUrl;
            $this->_logearAccion( $info_accion );
        }

        //seteao el layout de su promocion
        $this->_helper->_layout->setLayout( $this->layouts[$namespace->id_promocion] );

        $this->view->ancho_tabla = $namespace->formato['ancho_tabla'];
        $this->view->ua = $namespace->formato;
        $this->view->controller = $this;
        $this->view->id_categoria = '3';
        $this->view->contenidos = $namespace->contenidos['categorias'][$this->view->id_categoria]['hijos'];
        $configuracion = array(

            'tipo' => $namespace->configuracion['3']['tipo_vista'],//tipo vista
            'banner' => $namespace->configuracion['3']['banner'],//si tiene banner
        );

        $this->view->configuracion = $configuracion;

        $this->logger->info( print_r( $configuracion, true ) );

    }

    public function contenidosAction(){

        $namespace= new Zend_Session_Namespace('ENTERMOVIL_PORTAL_ESCOBAR');

        if(!isset($namespace->alias) || empty($namespace->alias)){

            $this->logger->info('SOLICITUD NO VALIDA');
            //$this->_redirect('/wap/aps');
            if( $this->ua['is_mobile'] ){

                //mensaje por defecto porque no se id_carrier ni pais
                $this->_helper->layout->setLayout('wap-layout');
                $this->_cargarImagenes2();
                $this->view->ua = $this->formato;
                $this->view->mensaje = array(
                    'Debe utilizar la conexión 3G de su celular',
                    'Enviar PORTAL al 35500 o',
                    'Enviar PATRON al 35500',
                    'para suscribirse',
                    'Desde tu TIGO o PERSONAL'
                );
                //mostrar vista apropiada
                $this->_helper->viewRenderer('mensaje-error');
                return;

            }else{

                $this->view->es_movil = false;
                $this->_helper->layout->disableLayout();
                $this->view->formato = $this->ua;
                //mostrar vista apropiada
                $this->_helper->viewRenderer('desktop');
                return;
            }
        }else{

            $this->_procesarSolicitud( $namespace->alias );
            $info_accion = array();
            $fullUrl =  $this->view->url();
            $info_accion['id_promocion'] = $namespace->id_promocion;
            $info_accion['cel'] = $namespace->cel;
            $info_accion['id_carrier'] = $namespace->id_carrier;
            $info_accion['url'] = $fullUrl;
            $this->_logearAccion( $info_accion );
        }

        //seteao el layout de su promocion
        $this->_helper->_layout->setLayout( $this->layouts[$namespace->id_promocion] );

        $this->view->ancho_tabla = $namespace->formato['ancho_tabla'];
        $this->view->ua = $namespace->formato;
        $this->view->controller = $this;

        $parametros = $this->_getAllParams( 'idp','id', null );
        $parametro = $parametros['id'];
        $padre = $parametros['idp'];

        $id_promocion = $namespace->id_promocion;

        if(is_null($parametro)){

            printf("contenido aun no cargado");
            printf($parametro);
        }
        $datos = array(

            'id_promocion' => $id_promocion,
            'id_padre' => $parametro,
            'nivel_acceso' => $namespace->nivel,
            'formato' => $padre,
        );

        $res = $this->_consulta( 'GET_CONTENIDO', $datos );

        if($parametros['idp'] == 2 || $parametros['idp'] == 3){

            $this->view->subtitulo = $res['0']['nombre_categoria'];
            if(!empty($res)){

                $page=$this->_getParam('page',1);
                $paginator = Zend_Paginator::factory($res);
                $paginator->setItemCountPerPage(10);
                $paginator->setCurrentPageNumber($page);
                $paginator->setPageRange(4);
                $this->view->paginator=$paginator;
            }
            else{

                printf("No hay datos que mostrar");
            }
        }else{

            $this->view->subtitulo = $res['0']['0']['nombre_categoria'];
            if( !empty( $res ) ){

                $page=$this->_getParam('page',1);
                $paginator = Zend_Paginator::factory($res);
                $paginator->setItemCountPerPage(4);
                $paginator->setCurrentPageNumber($page);
                $paginator->setPageRange(4);
                $this->view->paginator=$paginator;
            }
            else{

                printf("No hay datos que mostrar");
            }
        }
        $this->view->id_categoria = $parametros['idp'];

    }

    public function tigowapfwAction() {

        $namespace= new Zend_Session_Namespace('ENTERMOVIL_PORTAL_ESCOBAR');

        $statusTigo = (int)$this->_getParam('status', 9999);
        $request_id = (int)$this->_getParam('request_id');

        $this->logger->info('tigowapfw() status:[' . $statusTigo . '] request_id:[' . $request_id . ']');

        $wap_request = $this->_consulta('GET_WAP_REQUEST', array('secuencia' => $request_id));

        $datos['id_promocion'] = $wap_request['id_promocion'];
        $datos['id_carrier'] = $wap_request['id_carrier'];
        $datos['cel'] = $wap_request['cel'];

        $this->logger->info('tigowapfw() -> insertando en suscripto...');

        //suscribir
        $this->_consulta( 'ALTA_SUSCRIPTO', $datos );

        //setear mensaje
        $this->_helper->_layout->setLayout( $this->layouts[$wap_request['id_promocion']] );

        $this->view->mensaje = "Ha activado el servicio. En breve recibira un mensaje con el link de acceso al mismo";

        /* $this->_cargarImagenes();
         $this->view->ua = $this->formato;*/
        //modifique el 2013-11-04
        if( $wap_request['id_promocion'] == '77' ){

            $this->_cargarImagenes( $namespace->ancho );
            $this->view->ua = $this->formato;

        }else{

            $this->_cargarImagenes2( $namespace->ancho );
            $this->view->ua= $this->formato;
        }
        //mostrar vista apropiada
        $this->_helper->viewRenderer('mensaje-error');

    }

    public function suscripcionAction(){

        $namespace= new Zend_Session_Namespace('ENTERMOVIL_PORTAL_ESCOBAR');

        /* @var $request Zend_Controller_Request_Http */
        $request = $this->getRequest();

        if( $request->isPost() ) {//El usuario posteo el formulario de activacion

            $parametros = $this->_getAllParams( 'id_promocion', 'id_carrier', 'cel', 'id_activacion', null );

            if( !is_null( $parametros ) ){

                //setear log
                $info_accion = array();
                $fullUrl =  $this->view->url();
                $info_accion['id_promocion'] = $parametros['id_promocion'];
                $info_accion['cel'] = $parametros['cel'];;
                $info_accion['id_carrier'] = $parametros['id_carrier'];
                $info_accion['url'] = $fullUrl;
                $this->_logearAccion( $info_accion );

                $this->_helper->_layout->setLayout( $this->layouts[$parametros['id_promocion']] );
                $datos = array();
                $datos['id_promocion'] = $parametros['id_promocion'];
                $datos['id_carrier'] = $parametros['id_carrier'];
                $datos['cel'] = $parametros['cel'];

                //verifico si ya no esta suscripto
                //$ya_suscripto = $this->_consulta('INFO_SUSCRIPTO', $datos);
                $estaSuscripto = $this->_estaSuscripto( $datos );

                if( !$estaSuscripto ){//NO esta suscripto...

                    if( isset( $parametros['id_activacion'] ) ){

                        $this->_consulta( 'ACTUALIZAR_LOG_ACTIVACION', array( 'id_activacion'=> $parametros['id_activacion']) );
                    }
                    //Si el usuario todavia no esta suscripto
                    //Si es un usuario de Tigo
                    //Se procede a redireccionar a Tigo Wap FW
                    if($datos['id_carrier'] == 2) {//TIGO

                        $service_code = $this->_consulta('GET_SERVICE_CODE', array('id_promocion' => $datos['id_promocion']));
                        $this->logger->info('service_code:[' . print_r($service_code, true) . ']');

                        $secuencia_wap_request = $this->_consulta('SECUENCIA_WAP_REQUEST', array());
                        if($secuencia_wap_request > 0) {

                            //insertar wap request
                            $status = $this->_consulta('INSERTAR_WAP_REQUEST', array(
                                'request_id' => $secuencia_wap_request,
                                'cel' => $datos['cel'],
                                'id_carrier' => $datos['id_carrier'],
                                'id_promocion' => $datos['id_promocion'],
                                'ts_local' => new Zend_Db_Expr('NOW()'),
                                'status' => 0
                            ));
                            $this->logger->info('StatusInsertWapRequest:[' . $status . ']');

                            $ip_tigo_publica = '200.85.32.101:8080';
                            $url_entermovil = 'http://www.entermovil.com.py/waptwo/tigowapfw/status/$status$/request_id/$request_id$';
                            $url_tigo = 'http://'. $ip_tigo_publica .'/wapfw/subscribe?group_code='.$service_code['numero'].'&service_code='.$service_code['codigo_servicio'].'&request_id='.$secuencia_wap_request.'&redirect_url='.urlencode($url_entermovil);
                            $this->logger->info('URL(tigowapfw):[' . $url_tigo . ']');
                            //Redireccionando....
                            //$this->_redirect($url_tigo, array('code' => 301));
                            $this->logger->info('Redireccionando... [' . $datos['cel'] .']');
                            $this->_redirect($url_tigo);

                            return;

                        } else {

                            //error - intente mas tarde
                        }

                    }

                    $this->logger->info('suscripcion() -> insertando en suscripto...');
                    //suscribir
                    $this->_consulta( 'ALTA_SUSCRIPTO', $datos );


                    //setear mensaje
                    $this->view->mensaje = "Ha activado el servicio. En breve recibira un mensaje con el link de acceso al mismo";

                    if( $datos['id_promocion'] == '77' ){

                        $this->_cargarImagenes( $namespace->ancho );
                        $this->view->ua = $this->formato;

                    }else{

                        $this->_cargarImagenes2( $namespace->ancho );
                        $this->view->ua= $this->formato;
                    }
                    //mostrar vista apropiada
                    $this->_helper->viewRenderer('mensaje-error');

                }else{

                    //esta suscripto
                    //setear mensaje
                    $this->view->mensaje = "El servicio ya se encuentra activado. Ingrese al link www.entermovil.com.py/PORTAL para acceder al mismo";

                    $this->_cargarImagenes( $namespace->ancho );
                    $this->view->ua = $this->formato;

                    //mostrar vista apropiada
                    $this->_helper->viewRenderer('mensaje-error');
                }

            }else{

                $this->view->mensaje = "Error de parametros";

                $this->_cargarImagenes( $namespace->ancho );
                $this->view->ua = $this->formato;

                //mostrar vista apropiada
                $this->_helper->viewRenderer('mensaje-error');
            }

        } else {//se muestra el mensaje de confirmación con el boton de activacion

            $parametros = $this->_getAllParams( 'id-promocion', 'origen', null );

            $this->_helper->_layout->setLayout( $this->layouts[$parametros['id-promocion']] );
            $datos = array();
            $datos['id_promocion'] = $parametros['id-promocion'];

            $this->logger->info('info_usuario:[' . print_r($this->info_usuario, true) . ']');

            if( isset( $this->info_usuario['cel'] ) ) {

                $datos['id_carrier'] = $this->info_usuario['id_carrier'];
                $datos['cel'] =  $this->info_usuario['cel'];

                $estaSuscripto = $this->_estaSuscripto( $datos );

                if( !is_null( $parametros ) ){

                    //setear log
                    $fullUrl =  $this->view->url();
                    $info_accion['id_promocion'] = $parametros['id-promocion'];
                    $info_accion['cel'] = $this->info_usuario['cel'];;
                    $info_accion['id_carrier'] = $this->info_usuario['id_carrier'];
                    $info_accion['url'] = $fullUrl;
                    $this->_logearAccion( $info_accion );

                    if( $estaSuscripto ){

                        //setear mensaje
                        $this->view->mensaje = "El servicio ya se encuentra activado.";

                        if( $parametros['id-promocion'] == '77' ){

                            $this->_cargarImagenes( $namespace->ancho );
                            $this->view->ua = $this->formato;

                        }else{

                            $this->_cargarImagenes2( $namespace->ancho );
                            $this->view->ua= $this->formato;
                        }

                        //mostrar vista apropiada
                        $this->_helper->viewRenderer('mensaje-error');

                        //logearActivacionn
                        $info_activacion = array();
                        $info_activacion['cel'] = $this->info_usuario['cel'];
                        $info_activacion['id_promocion'] = $parametros['id-promocion'];
                        $info_activacion['origen'] = $this->_getParam('origen', 'SMS'); //is_null($parametros['origen'])?'SMS': (isset($parametros['origen']) ? $parametros['origen'] : 'SMS');
                        $info_activacion['fecha_hora_acceso'] = 'now';
                        $info_activacion['suscripto'] = 'true';
                        //agregado 2013-12-09
                        $info_activacion['useragent'] = $namespace->useragent;

                        $this->_consulta( 'LOGEAR_ACTIVACION', $info_activacion);

                    }else{

                        if( $parametros['id-promocion'] == '77' ){

                            $this->_cargarImagenes( $namespace->ancho );
                            $this->view->ua = $this->formato;

                        }else{

                            $this->_cargarImagenes2( $namespace->ancho );
                            $this->view->ua= $this->formato;
                        }

                        $datos = array();
                        $datos['id_promocion'] = $parametros['id-promocion'];
                        $datos['id_carrier'] = $this->info_usuario['id_carrier'];
                        $datos['id_pais'] =  $this->info_usuario['id_pais'];
                        $datos['origen'] = $this->_getParam('origen', 'SMS'); //is_null($parametros['origen'])?'SMS':$parametros['origen'];
                        $datos['tipo'] = '1';
                        $sms = $this->_consulta('GET_SMS_USUARIO', $datos);
                        $this->view->sms = $sms;

                        $suscripto['id_promocion'] = $datos['id_promocion'];
                        $suscripto['id_carrier'] = $datos['id_carrier'];
                        $suscripto['cel'] = $this->info_usuario['cel'];

                        //setear log activacion
                        $info_activacion = array();
                        $info_activacion['cel'] = $this->info_usuario['cel'];
                        $info_activacion['id_promocion'] = $parametros['id-promocion'];
                        $info_activacion['origen'] = $this->_getParam('origen', 'SMS'); //is_null($parametros['origen'])?'SMS': (isset($parametros['origen']) ? $parametros['origen'] : 'SMS');
                        $info_activacion['fecha_hora_acceso'] = 'now';
                        $info_activacion['suscripto'] = 'false';
                        //agregado 2013-12-09
                        $info_activacion['useragent'] = $namespace->useragent;

                        $suscripto['id_activacion'] = $this->_consulta( 'LOGEAR_ACTIVACION', $info_activacion);

                        $this->view->suscripto = $suscripto;
                        $this->view->url_action = $this->getRequest()->getRequestUri();

                    }
                }

            } else {

                //no esta seteado cel
                //setear mensaje
                $this->view->mensaje = array(
                    'Al parecer estas utilizando tu conexión WiFi',
                    'Activa tu conexion 3G para acceder al PORTAL',
                    'Volvé a recargar esta página',
                    '<a href="'."http://". $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'].'">RECARGAR</a>'

                );

                if( $parametros['id-promocion'] == '77' ){

                    $this->_cargarImagenes( $namespace->ancho );
                    $this->view->ua = $this->formato;

                }else{

                    $this->_cargarImagenes2( $namespace->ancho );
                    $this->view->ua= $this->formato;
                }

                //mostrar vista apropiada
                $this->_helper->viewRenderer('mensaje-error');
            }
        }
    }

    public function manejadorAction(){

        switch ( $this->tipo_mensaje ) {

            case 3:

                $this->_helper->_layout->setLayout( $this->layouts['72'] );
                $this->view->mensaje = array(
                    'Favor ingresar a:',
                    "www.entermovil.com.py/PORTAL"
                );

                $this->_cargarImagenes2( $this->ua['ancho'] );
                $this->view->ua= $this->formato;

                //setear errores
                $info_error['id_promocion'] = 72;
                $info_error['error'] = 'SESION EXPIRADA';
                $info_error['linea'] = 90;
                $info_error['alias'] = 'PORTAL';
                $info_error['direccion_ip'] = $_SERVER['REMOTE_ADDR'];
                $this->_logearErrores( $info_error );
                //mostrar vista apropiada
                $this->_helper->viewRenderer('mensaje-error');
                return;
                break;
            case 1:
                $this->_helper->layout->disableLayout();
                $this->logger->err('No es un dispositivo movil');
                $this->_helper->viewRenderer('desktop');
                return;
                break;
            case 4:

                $this->_helper->_layout->setLayout( $this->layouts['72'] );
                $this->view->mensaje = array(
                    'Enviar la palabra PORTAL',
                    "al 35500 para suscribirse al servicio"
                );

                $this->_cargarImagenes2( $this->ua['ancho'] );
                $this->view->ua= $this->formato;

                //setear errores
                $info_error['id_promocion'] = 72;
                $info_error['error'] = 'NO SUSCRIPTO';
                $info_error['linea'] = 90;
                $info_error['alias'] = 'PORTAL';
                $info_error['direccion_ip'] = $_SERVER['REMOTE_ADDR'];
                $this->_logearErrores( $info_error );
                //mostrar vista apropiada
                $this->_helper->viewRenderer('mensaje-error');
                return;
                break;
            case 2:

                $this->_helper->_layout->setLayout( $this->layouts['72'] );
                $this->view->mensaje = array(
                    'Utilice la conexión 3G de su celular',
                    'e ingrese a: ',
                    'www.entermovil.com.py/PORTAL',
                );

                $this->_cargarImagenes2( $this->ua['ancho'] );
                $this->view->ua= $this->formato;

                //setear errores
                $info_error['id_promocion'] = 72;
                $info_error['error'] = 'SESION EXPIRADA';
                $info_error['linea'] = 90;
                $info_error['alias'] = 'PORTAL';
                $info_error['direccion_ip'] = $_SERVER['REMOTE_ADDR'];
                $this->_logearErrores( $info_error );
                //mostrar vista apropiada
                $this->_helper->viewRenderer('mensaje-error');
                return;
                break;
        }
    }

    private function _analizarSolicitud(){

        $this->logger->info( '_analizarSolicitud' );

        $this->_procesarUserAgent();
        if( $this->ua['is_mobile'] ){

            if( $this->solicitudValida ){

                $datos = array(

                    'cel' => $this->info_usuario['cel'],
                    'id_promocion' => 72,
                    'id_carrier' => $this->info_usuario['id_carrier'],
                );
                if( $this->_estaSuscripto( $datos ) ){

                    $this->tipo_mensaje = 3;
                }else{

                    $this->tipo_mensaje = 4;
                }
            }else{

                $this->tipo_mensaje = 2;
            }
        }else{

            $this->tipo_mensaje = 1;
        }
    }

    private function _ejecutar_comando($comando) {
        //se aguarda el comando en un buffer interno
        ob_start();
        //comando para llamar a una funcion externa, en este caso la linea de comandos
        passthru($comando);
        //se asigna a $resultado lo guardado en el buffer interno
        $resultado = ob_get_contents();
        //eliminar el buffer interno
        ob_end_clean();

        return $resultado;
    }
}
