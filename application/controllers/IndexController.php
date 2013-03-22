<?php

class IndexController extends Zend_Controller_Action
{


    public $logger = null;

    public $operadoras = array(
        'PERSONAL' => 1,
        'TIGO' => 2/*,
        'VOX' => 3,
        'CLARO' => 4*/
    );

    public $servicios = array(
        'MASCOTA' => array(
            'id_promocion' => 6,
            'numero' => '35500',
            'descripcion' => 'Mascotas Animadas'
        ),
        'TONO' => array(
            'id_promocion' => 7,
            'numero' => '35500',
            'descripcion' => 'Tonos Inéditos'
        ),
        'SIGLAS' => array(
            'id_promocion' => 10,
            'numero' => '35500',
            'descripcion' => 'Fondos Personalizados'
        ),
        'BL' => array(
            'id_promocion' => 11,
            'numero' => '35500',
            'descripcion' => 'Fondos Personalizados'
        ),
        'DIVER' => array(
            'id_promocion' => 12,
            'numero' => '35500',
            'descripcion' => 'Imágenes Divertidas'
        ),
        'POSTAL' => array(
            'id_promocion' => 13,
            'numero' => '35500',
            'descripcion' => 'Postales del Paraguay'
        ),
        'PY' => array(
            'id_promocion' => 14,
            'numero' => '6767',
            'descripcion' => 'Historia del Paraguay'
        ),
        'ALEGRIA' => array(
            'id_promocion' => 15,
            'numero' => '6767'
        ,
            'descripcion' => 'Frases Alegres'
        ),
        'MUNDO' => array(
            'id_promocion' => 16,
            'numero' => '6767',
            'descripcion' => 'Historia del Mundo'
        ),
        'INGLES' => array(
            'id_promocion' => 17,
            'numero' => '6767',
            'descripcion' => 'Lecciones de Inglés'
        ),
        'CIENCIA' => array(
            'id_promocion' => 18,
            'numero' => '6767',
            'descripcion' => 'Grandes Descubrimientos'
        ),
        'OK' => array(
            'id_promocion' => 19,
            'numero' => '6767',
            'descripcion' => 'Lecciones de Inglés'
        ),
        'USA' => array(
            'id_promocion' => 20,
            'numero' => '6767',
            'descripcion' => 'Lecciones de Inglés'
        )
    );

    private function buscarDatoPromocion($id_promocion, $campoBuscado) {
        foreach($this->servicios as $servicio => $datos) {

            if($datos['id_promocion'] == $id_promocion) {

                if($campoBuscado == 'servicio') return $servicio;

                return $datos[$campoBuscado];
            }
        }

        return null;
    }

    public function init()
    {
        /* Initialize action controller here */
        $this->logger = $this->getLog();
        if($this->logger) {
            $this->logger->info('IndexController -> Request');
        }

        $headLinkContainer = $this->view->headLink()->getContainer();
        if(isset($headLinkContainer[0])) {
            unset($headLinkContainer[0]);//reportes_base.css
        }
        $this->view->headLink()->setStylesheet('/css/base.css', 'screen');

        $headScriptContainer = $this->view->headScript()->getContainer();
        if(count($headScriptContainer) > 1 && isset($headScriptContainer[1])) {
            unset($headScriptContainer[1]);//reportes_base.js
        }
        //$this->view->headScript()->appendFile('/js/base.js', 'text/javascript');

        $this->_helper->_layout->setLayout('layout');

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

    private function consulta($accion, $datos=null) {

        $bootstrap = $this->getInvokeArg('bootstrap');
        $options = $bootstrap->getOptions();
        $db = Zend_Db::factory(new Zend_Config($options['resources']['db']));
        $db->getConnection();

        if($accion == 'ID_SOLICITUD') {//Obtener id_solicitud

            $sql = "select nextval('promosuscripcion.suscripciones_web_id_solicitud_seq'::regclass) as id_solicitud;";
            $rs = $db->fetchRow($sql);
            $secuencia = $rs['id_solicitud'];
            $this->logger->info('id_solicitud:[' . $secuencia . ']');
            return $secuencia;

        }

        if($accion == 'ID_SALIENTE') {//Obtener id_saliente

            $sql = "select nextval('sms_salientes_id_saliente_seq'::regclass) as id_saliente;";
            $rs = $db->fetchRow($sql);
            $secuencia = $rs['id_saliente'];
            $this->logger->info('id_saliente:[' . $secuencia . ']');
            return $secuencia;

        }

        if($accion == 'INSERTAR_SMS_SALIENTE') {

            return $db->insert('sms_salientes', $datos);
        }

        if($accion == 'INSERTAR_SOLICITUD_WEB') {

            return $db->insert('promosuscripcion.suscripciones_web', $datos);
        }

        if($accion == 'SOLICITUD_WEB') {

            $sql = "SELECT * FROM promosuscripcion.suscripciones_web WHERE id_solicitud = ?";
            $rs = $db->fetchRow($sql, array($datos['id_solicitud']));
            $resultado = array();
            if($rs) {
                $resultado = (array)$rs;
            }
            return $resultado;
        }

        if($accion == 'SUSCRIPCION') {

            $sql = "SELECT * FROM promosuscripcion.suscriptos WHERE cel = ? AND id_carrier = ? AND id_promocion = ?";
            $rs = $db->fetchRow($sql, array($datos['cel'], $datos['id_carrier'], $datos['id_promocion']));
            $resultado = array();
            if($rs) {
                $resultado = (array)$rs;
            }
            return $resultado;
        }

        if($accion == 'ACTUALIZAR_SOLICITUD_WEB') {

            $id_solicitud = $datos['id_solicitud'];
            unset($datos['id_solicitud']);

            return $db->update('promosuscripcion.suscripciones_web', array('id_solicitud = ?' => $id_solicitud), $datos);
        }

    }

    public function suscripcionAction() {

        $this->_helper->_layout->setLayout('suscripcion');

        $this->view->headLink()->appendStylesheet('/css/suscripcion-web.css', 'screen');

        $this->view->headScript()->appendFile('/js/modernizr-2.0.6.js', 'text/javascript');
        $this->view->headScript()->appendFile('/js/jquery.placeholder.min.js', 'text/javascript');
        $this->view->headScript()->appendFile('/js/jquery.form.js', 'text/javascript');
        $this->view->headScript()->appendFile('/js/tempo.js', 'text/javascript');
        $this->view->headScript()->appendFile('/js/suscripcion-web.js', 'text/javascript');

        if($this->getRequest()->isPost()) {

            $formData = $this->getRequest()->getPost();
            if(isset($formData['id_solicitud'])) {//Si idSolicitud esta seteado entonces

                $this->_helper->viewRenderer('suscripcion-estado');

                $id_solicitud = $formData['id_solicitud'];
                $pin = trim($formData['pin']);

                $solicitud_web = $this->consulta('SOLICITUD_WEB', array(
                    'id_solicitud' => $id_solicitud
                ));

                $this->logger->info('solicitud_web:[' . print_r($solicitud_web, true) . ']');

                if($solicitud_web['id_carrier'] == 2) {//TIGO

                    //Obtener id_saliente
                    $id_saliente = $this->consulta('ID_SALIENTE');

                    //ACTUALIZAR_SOLICITUD_WEB
                    $status_solicitud_web = $this->consulta('ACTUALIZAR_SOLICITUD_WEB', array(
                        'id_solicitud' => $id_solicitud,
                        'id_saliente_confirmacion' => $id_saliente,
                        'ts_confirmacion' => 'NOW()'
                    ));
                    $this->logger->info('status_solicitud_web:[' . $status_solicitud_web . ']');

                    //Insertar sms_saliente con tipo_mensaje = 12 y esperar confirmacion
                    $tipo_mensaje = 12;//Validacion
                    $datos = array(
                        'id_saliente' => $id_saliente,
                        'sms' => $pin,
                        'tipo_mensaje' => $tipo_mensaje,
                        'n_remitente' => $solicitud_web['cel'],
                        'n_llamado' => $this->buscarDatoPromocion($solicitud_web['id_promocion'], 'numero'),
                        'id_carrier' => $solicitud_web['id_carrier'],
                        'id_sc' => $solicitud_web['id_promocion'],
                        'pendiente_billing' => 0,
                        'id_cliente' => 3,
                        'ts_local' => 'NOW()',
                        'estado' => 0,
                    );

                    $status_sms_saliente = $this->consulta('INSERTAR_SMS_SALIENTE', $datos);
                    $this->logger->info('status_sms_saliente:[' . $status_sms_saliente . ']');

                    $servicio = $this->buscarDatoPromocion($solicitud_web['id_promocion'], 'servicio');
                    $this->view->servicio = $servicio;
                    $this->view->descripcion = $this->servicios[$servicio]['descripcion'];
                    $this->view->cel = $solicitud_web['cel'];
                    $this->view->mensaje_estado = 'Validando Suscripción...';

                } else if($solicitud_web['id_carrier'] == 1) {//PERSONAL

                }

            } else {

                $this->_helper->viewRenderer('suscripcion-confirmar');

                $id_promocion = $this->servicios[$formData['servicio']]['id_promocion'];
                $id_carrier = $this->operadoras[$formData['operadora']];
                $cel = trim($formData['cel']);

                $this->view->servicio = $formData['servicio'];
                $this->view->descripcion = $this->servicios[$formData['servicio']]['descripcion'];
                $this->view->cel = $cel;

                if($id_carrier == 2) {//TIGO

                    //Obtener id_solicitud
                    $id_solicitud = $this->consulta('ID_SOLICITUD');
                    $this->view->id_solicitud = $id_solicitud;

                    //Obtener id_saliente
                    $id_saliente = $this->consulta('ID_SALIENTE');

                    //Insertar solicitud_web
                    $datos_solicitud = array(
                        'id_solicitud' => $id_solicitud,
                        'cel' => $cel,
                        'id_promocion' => $id_promocion,
                        'id_carrier' => $id_carrier,
                        'id_saliente_autorizacion' => $id_saliente,
                        'ts_autorizacion' => 'NOW()'
                    );

                    $this->logger->info('datos_solicitud:[' . print_r($datos_solicitud, true) . ']');

                    $status_solicitud_web = $this->consulta('INSERTAR_SOLICITUD_WEB', $datos_solicitud);
                    $this->logger->info('status_solicitud_web:[' . $status_solicitud_web . ']');

                    //Insertar en sms_saliente con tipo_mensaje=11 (Autorizacion)
                    $mensaje = 'PIN: '. chr(36) . 'AUTH_PIN' . chr(36) . "\n" . 'Ingresalo en la web';
                    //$mensaje = 'PIN: ¤AUTH§PING¤ CHAU';
                    $tipo_mensaje = 11;//Autorizacion

                    $datos = array(
                        'id_saliente' => $id_saliente,
                        'sms' => $mensaje,
                        'tipo_mensaje' => $tipo_mensaje,
                        'n_remitente' => $cel,
                        'n_llamado' => $this->servicios[$formData['servicio']]['numero'],
                        'id_carrier' => $id_carrier,
                        'id_sc' => $id_promocion,
                        'pendiente_billing' => 0,
                        'id_cliente' => 3,
                        'ts_local' => 'NOW()',
                        'estado' => 0,
                    );

                    $status_sms_saliente = $this->consulta('INSERTAR_SMS_SALIENTE', $datos);
                    $this->logger->info('status_sms_saliente:[' . $status_sms_saliente . ']');

                } else if($id_carrier == 1) {//PERSONAL

                }
            }

        } else {

            $id_solicitud = $this->_getParam('autorizacion', 0);
            if($id_solicitud > 0) {
                //Se esta verificando si la suscripción fue autorizada
                $this->_helper->layout->disableLayout();
                $this->_helper->viewRenderer->setNoRender(true);

                $solicitud_web = $this->consulta('SOLICITUD_WEB', array(
                    'id_solicitud' => $id_solicitud
                ));

                $this->logger->info('solicitud_web:[' . print_r($solicitud_web, true) . ']');

                $respuesta = array();
                if(is_null($solicitud_web['cmd_id_autorizacion'])) {
                    //suscripcion pendiente de autorización...
                    $respuesta['status'] = 'OK';
                    $respuesta['message'] = 'PENDIENTE_AUTORIZACION';

                } else if((int)$solicitud_web['cmd_id_autorizacion'] == 0) {
                    $respuesta['status'] = 'OK';
                    $respuesta['message'] = 'AUTORIZACION_OK';
                    $respuesta['cmd_id'] = 0;

                } else {

                    $respuesta['status'] = 'ERROR';
                    $respuesta['message'] = 'AUTORIZACION_ERROR';
                    $respuesta['cmd_id'] = $solicitud_web['cmd_id_autorizacion'];
                }

                echo json_encode($respuesta);
                return;

            } else {

                $id_solicitud = $this->_getParam('confirmacion', 0);
                if($id_solicitud > 0) {
                    //Se esta verificando si la suscripción fue confirmada

                    //Se esta verificando si la suscripción fue autorizada
                    $this->_helper->layout->disableLayout();
                    $this->_helper->viewRenderer->setNoRender(true);

                    $solicitud_web = $this->consulta('SOLICITUD_WEB', array(
                        'id_solicitud' => $id_solicitud
                    ));

                    $this->logger->info('solicitud_web:[' . print_r($solicitud_web, true) . ']');

                    if(is_null($solicitud_web['cmd_id_confirmacion'])) {
                        //suscripcion pendiente...
                        $respuesta['status'] = 'OK';
                        $respuesta['message'] = 'PENDIENTE_CONFIRMACION';

                    } else if((int)$solicitud_web['cmd_id_confirmacion'] == 0) {
                        $respuesta['status'] = 'OK';
                        $respuesta['message'] = 'CONFIRMACION_OK';
                        $respuesta['cmd_id'] = 0;

                    } else {

                        $respuesta['status'] = 'ERROR';
                        $respuesta['message'] = 'CONFIRMACION_ERROR';
                        $respuesta['cmd_id'] = $solicitud_web['cmd_id_confirmacion'];
                    }

                    echo json_encode($respuesta);
                    return;

                } else {
                    //no es post.. se muestra formulario
                }
            }
        }
    }

    public function webmailAction() {

        $this->_redirect('http://mail.google.com/a/entermovil.com.py/');
        return;
    }

    public function indexAction()
    {
        /*$white_list = array('201.217.51.198', '192.168.1.6');
        $src_ip = $_SERVER['REMOTE_ADDR'];
        if(!in_array($src_ip, $white_list)) {
            echo 'IP:[' . $src_ip . ']';
            exit;
        }*/

        //$this->view->headLink()->appendStylesheet('/css/home.css', 'screen');

        //$this->_forward('index', 'nuevo');

        $this->view->headScript()->appendFile('/js/modernizr-2.0.6.js', 'text/javascript');
        $this->view->headScript()->appendFile('/js/jquery.placeholder.min.js', 'text/javascript');
        $this->view->headScript()->appendFile('/js/base.js', 'text/javascript');
        $this->view->headScript()->appendFile('/js/home.js', 'text/javascript');

        $this->view->headLink()->appendStylesheet('/css/home.css', 'screen');
        //$this->view->headLink()->appendStylesheet('http://fonts.googleapis.com/css?family=Titan+One&subset=latin,latin-ext');


        $this->_helper->viewRenderer('nuevo');

    }

    public function serviciosAction() {

        $this->_helper->_layout->setLayout('callcenter-layout');

        $this->view->headLink()->setStylesheet('/css/base.css', 'screen');

        $this->view->headScript()->appendFile('/js/modernizr-2.0.6.js', 'text/javascript');
        $this->view->headScript()->appendFile('/js/jquery.placeholder.min.js', 'text/javascript');
        $this->view->headScript()->appendFile('/js/base.js', 'text/javascript');
        $this->view->headScript()->appendFile('/js/acceso.js', 'text/javascript');

        $this->view->headLink()->appendStylesheet('/css/servicios.css', 'screen');

        //$this->_helper->viewRenderer('acceso-callcenter');
    }

    public function bannerEstaticoAction() {

        $this->_helper->_layout->setLayout('imagen');
        $this->view->headLink()->appendStylesheet('/css/banner-estatico.css', 'screen');

        $this->logger->info('ACCESO-BANNER-ESTATICO -> [' . $_SERVER['HTTP_REFERER'] . ']');
    }

    public function nuevoAction()
    {
        $this->view->headScript()->appendFile('/js/modernizr-2.0.6.js', 'text/javascript');
        $this->view->headScript()->appendFile('/js/jquery.placeholder.min.js', 'text/javascript');
        $this->view->headScript()->appendFile('/js/base.js', 'text/javascript');
        $this->view->headScript()->appendFile('/js/home.js', 'text/javascript');

        $this->view->headLink()->appendStylesheet('/css/home.css', 'screen');
    }

    public function qSomosAction()
    {
        $this->view->headScript()->appendFile('/js/modernizr-2.0.6.js', 'text/javascript');
        $this->view->headScript()->appendFile('/js/jquery.placeholder.min.js', 'text/javascript');
        $this->view->headScript()->appendFile('/js/base.js', 'text/javascript');

        $this->view->headLink()->appendStylesheet('/css/qsomos.css', 'screen');
    }

    public function qCreamosAction()
    {
        $this->view->headScript()->appendFile('/js/modernizr-2.0.6.js', 'text/javascript');
        $this->view->headScript()->appendFile('/js/jquery.placeholder.min.js', 'text/javascript');
        $this->view->headScript()->appendFile('/js/base.js', 'text/javascript');

        $this->view->headLink()->appendStylesheet('/css/qcreamos.css', 'screen');
    }

    public function accesoContactoAction()
    {
        $this->view->headScript()->appendFile('/js/modernizr-2.0.6.js', 'text/javascript');
        $this->view->headScript()->appendFile('/js/jquery.placeholder.min.js', 'text/javascript');
        $this->view->headScript()->appendFile('/js/base.js', 'text/javascript');

        $this->view->headLink()->appendStylesheet('/css/acceso-contacto.css', 'screen');
    }

    public function siglasAction() {

        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $iniciales = $this->_getParam('iniciales', 'XXXX');
        $indice = $this->_getParam('indice', 0);

        $siglas_path = array("SIGLAS-14.jpg", "SIGLAS-17.jpg", "SIGLAS-22.jpg", "SIGLAS-28.jpg", "SIGLAS-46.jpg");
        $path_fondo = "/home/entermov/www/public/img/" . $siglas_path[$indice];
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

            $tpl_destino = array(
                $path_aplicacion,
                '/usr/bin/',
                basename($path_fondo_temporal),
                '172x287',
                $this->paraParametroIniciales($iniciales),
                72,
                basename($path_final)
            );
            $comando_generar_fondo_personalizado = str_replace($tpl_origen, $tpl_destino, $tpl_comando);

            $this->logger->info('generarFondoPersonalizado() -> comando:[' . $comando_generar_fondo_personalizado . ']');
            $resultado = exec($comando_generar_fondo_personalizado, $salida);
            $this->logger->info('generarFondoPersonalizado() -> salida_comando:[' . print_r($salida, true) . ']');
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

}