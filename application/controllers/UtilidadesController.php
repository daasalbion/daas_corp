<?php

class UtilidadesController extends Zend_Controller_Action
{
    var $logger;
    var $ua = array();
    var $msisdn;
    var $nroCel = null;
    var $info_usuario; //toda la info del usuario
    var $formato;//formato a desplegar
    var $solicitudValida = false;
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

    //FORMATO DE LOGGERS
    //ACTION
    //--->NOMBRE_ACTION
    //FUNCTION
    //NOMBRE

    public function init()
    {
        $this->_configurarLogger();
        $this->logger->info('--->INIT DETECTAR - ENTERMOVIL');
        $this->_helper->layout->setLayout( 'utilidades-layout' );
        $this->_procesarMSISDN();
        $this->logger->info( 'info_usuario: [' .print_r( $this->info_usuario, true ). ']' );

    }

    private function _configurarLogger() {

        $logger = new Zend_Log();
        //$writer = new Zend_Log_Writer_Stream(APPLICATION_PATH . '/../log/www.entermovil.com.py_utilidades_'.date('Y-m-d').'.log');
        $writer = new Zend_Log_Writer_Stream(APPLICATION_PATH . '/../log/www.entermovil.com.py_waptwo_'.date('Y-m-d').'.log');
        $format = '%timestamp% %priorityName% - [%remoteAddr%]: %message%' . PHP_EOL;
        $formatter = new Zend_Log_Formatter_Simple($format);
        $writer->setFormatter($formatter);
        $logger->addWriter($writer);
        $logger->setEventItem('remoteAddr', $_SERVER['REMOTE_ADDR']);
        $this->logger = $logger;

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

        /*$nombre_header = 'HTTP_MSISDN';
        $this->msisdn = '595982313289';*/
        //si no son nulos se conectaron desde la red 3g de su celular
        if( !is_null( $this->msisdn ) && !is_null( $nombre_header ) ){

            $this->nroCel = $this->_getFormatoCorto($this->msisdn);
            $this->logger->info('Header:[' . $nombre_header . ']:[' . $this->msisdn . ']');
            $this->logger->info('Nrocel:[' . $this->nroCel . ']');
            //seteo la informacion ya disponible del usuario
            $this->info_usuario['cel'] = $this->nroCel;
            $this->info_usuario['msisdn'] = $this->msisdn;
            $this->info_usuario['solicitud_valida'] = true;

        }else{

            $this->info_usuario['solicitud_valida'] = false;
            $this->logger->info('--->SOLICITUD NO VALIDA ---> NO TIENE MSISDN O NO TIENE HEADER ');
        }
    }

    private function _getFormatoCorto($nro_largo) {

        $this->logger->info('_getFormatoCorto');
        //Verificamos que el nro recibido este en formato largo
        //Ejemplo: 595 971 200 211
        if(strlen($nro_largo) == 12 && substr($nro_largo, 0, 3) == '595') {//esta en formato largo

            $this->info_usuario['id_pais'] = $this->prefijo_telefonico_pais['595'];
            return '0'.substr($nro_largo, 3);

        }//solo paraguay
        /*CON ESTO YO SE DE QUE PAIS ES EL NUMERO Y LUEGO BUSCO EL RANGO DE POSIBLES IPS PARA EL CARRIER*/
        if( strlen($nro_largo) >= 12 && substr($nro_largo, 0, 3) == '591' ){

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
        }
        $this->info_usuario['id_pais'] = null;
        return $nro_largo;
    }

    public function indexAction() {

        $this->logger->info('--->INDEX_ACTION');
        $this->_forward('utilidades','utilidades');

        return;
    }

    public function deteccionJavascriptAction()
    {
        $destino = $this->_getParam( 'destino', null );

        if( !is_null( $destino ) ){

            $dimensiones_telefono = $this->_getAllParams( 'ancho', 'alto', null );

            if( ( isset( $dimensiones_telefono['ancho'] ) && !is_null( $dimensiones_telefono['ancho'] ) ) &&
                ( isset( $dimensiones_telefono['alto'] ) && !is_null( $dimensiones_telefono['alto'] ) ) ){

                $datos = array(

                    'ancho' => $dimensiones_telefono['ancho'],
                    'alto' => $dimensiones_telefono['alto'],
                    'cel' => $this->info_usuario['cel'],
                    'solicitud_valida' => $this->info_usuario['solicitud_valida'],
                    'direccion_ip' => $_SERVER['REMOTE_ADDR']
                );
                $this->_cargarDatosUsuario( $datos, true );

                $datos_user_agent = array(

                    'useragent' => $_SERVER['HTTP_USER_AGENT'],
                    'hash' => md5($_SERVER['HTTP_USER_AGENT']),
                    'modelo' => '',
                    'marca' => '',
                    'ancho' => $datos['ancho'],
                    'alto' => $datos['alto'],
                    'estado' => 'GENERICO',
                );

                if( $datos_user_agent['ancho'] > 1024 ){

                    $datos_user_agent['ancho'] = 1024;
                }

                $this->_consulta( 'AGREGAR_USER_AGENT', $datos_user_agent );

                switch ( $destino ){

                    case md5('/waptwo/index/alias/PORTAL'):
                        $destino = '/waptwo/index/alias/PORTAL';
                        break;
                    case md5('/waptwo/suscripcion/id-promocion/72/origen/B1'):
                        $destino = '/waptwo/suscripcion/id-promocion/72/origen/B1';
                        break;
                    case md5('/waptwo/suscripcion/id-promocion/77/origen/SMS'):
                        $destino = '/waptwo/suscripcion/id-promocion/77/origen/SMS';
                        break;
                    case md5('/waptwo/suscripcion/id-promocion/58/origen/SMS'):
                        $destino = '/waptwo/suscripcion/id-promocion/58/origen/SMS';
                        break;
                    case md5('/waptwo/suscripcion/id-promocion/82/origen/SMS'):
                        $destino = '/waptwo/suscripcion/id-promocion/82/origen/SMS';
                        break;
                    case md5('/waptwo/index/alias/PATRON'):
                        $destino = '/waptwo/index/alias/PATRON';
                        break;
                }
                $this->_redirect( $destino );

            }else{

                $this->view->destino = $destino;
            }
        }else{

            $this->view->sms = 'Lo sentimos ha ocurrido un error';
            $this->logger->info('alias nulo');
        }
    }

    public function detectarAction(){

        $this->logger->info('--->DETECTAR_ACTION');
        //si esta seteado es una de los generic y por eso al pedo vamos a llamar al wurlf
        $parametros = $this->_getAllParams( 'alias', 'redireccionar', null );

        if( !is_null( $parametros ) ){

            switch ( $parametros['redireccionar'] ) {
                //paraguay
                case "PORTAL":
                    $url = '/waptwo/index/alias/PORTAL';
                    break;

                //paraguay
                case "/B1/PORTAL":
                    $url = '/waptwo/suscripcion/id-promocion/72/origen/B1';
                    break;

                //guatemala
                case "gt/alta/PORTAL":

                    $url = '/waptwo/suscripcion/id-promocion/58/origen/SMS';
                    break;

                //guatemala
                case "col/alta/PORTAL":

                    $url = '/waptwo/suscripcion/id-promocion/82/origen/SMS';
                    break;

                //paraguay
                case "/activar/PORTAL":

                    $url = '/waptwo/suscripcion/id-promocion/72/origen/SMS';
                    break;

                //patron del mal paraguay
                case "/activar/PATRON":

                    $url = '/waptwo/suscripcion/id-promocion/77/origen/SMS';
                    break;

                //patron del mal paraguay
                case "PATRON":

                    $url = '/waptwo/index/alias/PATRON';
                    break;

                //PRUEBAS - eliminar despues

                case "PRUEBA":

                    $url = '/wap/suscripcion/id-promocion/72/origen/B1';
                    break;

                //FIN PRUEBAS
            }

            $datos_user_agent = $this->_comprobarUseragent( $_SERVER['HTTP_USER_AGENT'] );
            $this->logger->info( 'USER_AGENT: ' . print_r(  $datos_user_agent, true ) );

            if( is_null( $datos_user_agent ) ){
                //por el wurlf
                $bootstrap = $this->getInvokeArg('bootstrap');
                $userAgent = $bootstrap->getResource('useragent');
                $device = $userAgent->getDevice();

                $is_mobile = (bool)($device->hasFeature('is_mobile')) ? true : false;

                if( $is_mobile ){

                    $es_generic = (strpos($device->getFeature('brand_name'),'Generic') !== FALSE )? TRUE: FALSE;
                    if( $es_generic ){

                        //solo para los dispositivos android que no se detectan obtengo su ancho por java script en deteccionJavascriptAction
                        $url = md5( $url );
                        $this->_redirect( '/utilidades/deteccion-javascript/destino/' .$url);
                    }else{

                        $datos = array(

                            'ancho' => $device->getFeature('resolution_width'),
                            'alto' => $device->getFeature('resolution_height'),
                            'cel' => $this->info_usuario['cel'],
                            'solicitud_valida' => $this->info_usuario['solicitud_valida'],
                            'direccion_ip' => $_SERVER['REMOTE_ADDR'],
                            //agregado 2013-12-09
                            'useragent' => $_SERVER['HTTP_USER_AGENT']
                        );

                        $this->_cargarDatosUsuario( $datos );

                        $datos_user_agent = array(

                            'useragent' => $_SERVER['HTTP_USER_AGENT'],
                            'hash' => md5($_SERVER['HTTP_USER_AGENT']),
                            'modelo' => $device->getFeature('model_name'),
                            'marca' => $device->getFeature('brand_name'),
                            'ancho' => $datos['ancho'],
                            'alto' => $datos['alto'],
                            'estado' => 'RECONOCIDO',
                        );

                        $this->_consulta( 'AGREGAR_USER_AGENT', $datos_user_agent );
                        $this->_redirect( $url );
                    }

                }else{

                    $this->_helper->layout->disableLayout();
                    $this->logger->err('No es un dispositivo movil');
                    $this->_helper->viewRenderer('desktop');
                    return;
                }
            }else{

                $datos = array(

                    'ancho' => $datos_user_agent['ancho'],
                    'alto' => $datos_user_agent['alto'],
                    'cel' => $this->info_usuario['cel'],
                    'solicitud_valida' => $this->info_usuario['solicitud_valida'],
                    'direccion_ip' => $_SERVER['REMOTE_ADDR'],
                    //agregado 2013-12-09
                    'useragent' => $_SERVER['HTTP_USER_AGENT']
                );

                $this->_cargarDatosUsuario( $datos );
                $this->logger->info('DATOS: ' . print_r( $datos, true ) );

                $this->_redirect( $url );
            }
        }else{

            $this->logger->err( 'Hubo un error' );
            exit;

            //voy a obtener solo el ancho para mostrarle un mensaje
        }
    }

    private function _cargarDatosUsuario( $datos, $es_generic = false ){

        //empezamos a cargar los datos en la sesion que creamos para el usuario
        //si es generic no tiene los datos del wurfl
        //$namespace= new Zend_Session_Namespace( 'ENTERMOVIL_WAP_' . $datos['cel'] );
        //por ahora

        $namespace= new Zend_Session_Namespace('ENTERMOVIL_PORTAL_ESCOBAR');

        $namespace->ip = $datos['direccion_ip'];
        $namespace->ancho = $datos['ancho'];
        $namespace->alto = $datos['alto'];
        $namespace->cel = $datos['cel'];
        $namespace->useragent = $datos['useragent'];

        $namespace->setExpirationSeconds(60*60*24*7);

        $this->logger->info('datos cargados: ' . print_r( $datos, true ) );
        $this->logger->info('sesion: ' . print_r( $namespace, true ) );
    }

    private function _comprobarUseragent( $user_agent ){

        $existe_useragent_terminal = $this->_consulta( 'OBTENER_USER_AGENT', $user_agent );
        if( is_null( $existe_useragent_terminal ) ){

            return null;
        }else{

            return $existe_useragent_terminal;
        }
    }

    public function descargarAction() {

        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $this->logger->info('--->DESCARGAR_ACTION');

        $namespace= new Zend_Session_Namespace('ENTERMOVIL_PORTAL_ESCOBAR');

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
            $info_accion['cel'] = $namespace->cel;
            $info_accion['id_carrier'] = $namespace->id_carrier;
            $info_accion['url'] = $fullUrl;
            $info_accion['origen'] = $this->actions[$parametros['origen']];
            $this->_consulta('LOGEAR_ACCION' , $info_accion );

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

    private function _consulta( $accion, $datos = null ){

        $bootstrap = $this->getInvokeArg('bootstrap');
        $options = $bootstrap->getOptions();

        $db = Zend_Db::factory(new Zend_Config($options['resources']['db']));
        $db->getConnection();

        switch ( $accion ) {

            case "AGREGAR_USER_AGENT":

                $db->insert('wap.terminales_useragent', $datos );
                break;
            case "OBTENER_USER_AGENT":

                $hash = md5( $datos );
                $sql = "select * from wap.terminales_useragent where hash = ?";
                $rs = $db->fetchRow( $sql, $hash );
                if( !empty( $rs ) ){

                    return (array)$rs;
                }else{

                    return null;
                }
                break;
            case "GET_CONTENIDO_DESCARGAR":
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
                break;
            case "LOGEAR_ACCION":
                $datos['fecha_hora'] = 'now';
                $status = $db->insert('wap.log_accion_wap', $datos);
                //$this->logger->info('INSERTAR_SUSCRIPTO -> status:[' . $status . ']');
                return $status;
                break;
        }
    }
}
