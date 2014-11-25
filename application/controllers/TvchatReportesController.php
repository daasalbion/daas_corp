<?php

class TvchatReportesController extends Zend_Controller_Action{

    public $logger;
    var $usuarios = array(

        'daas' => array('clave' => 'daas', 'nombre' => 'DAAS', 'rol' => 'admin'),
        'david' => array('clave' => '952397', 'nombre' => 'David Villalba', 'rol' => 'admin'),
        'ezequiel' => array('clave' => 'ezequiel', 'nombre' => 'Ezequiel Garcia', 'rol' => 'admin'),
        'felix' => array('clave' => 'felix', 'nombre' => 'Felix Ovelar', 'rol' => 'admin'),
        'diego' => array('clave' => 'diego', 'nombre' => 'Diego Borja', 'rol' => 'productor'),
        'asesor' => array('clave' => '2014', 'nombre' => 'Asesor', 'rol' => 'asesor')

    );
    var $meses = array(
        'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Setiembre', 'Octubre', 'Noviembre', 'Diciembre'
    );
    var $dias_semana = array(
        'Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'
    );
    var $rango_seleccion = array(
        array('anho' => 2014, 'mes' => 7, 'descripcion' => '2014 - Julio')
    );
    var $numeros = array( '6767', '8540' );
    var $carriers = array(
        1 => 'PERSONAL',
        2 => 'TIGO'
    );

    public function init(){
        /* Initialize action controller here */
        $this->logger = $this->getLog();
        //Agregamos otro Writer, para escribir los WebServices Request en otro archivo de log
        $writer = new Zend_Log_Writer_Stream(APPLICATION_PATH . '/../log/tvchat_reportes_'.date('Y-m-d').'.log');
        $format = '%timestamp% %priorityName% - [%remoteAddr%]: %message%' . PHP_EOL;
        $formatter = new Zend_Log_Formatter_Simple($format);
        $writer->setFormatter($formatter);
        $this->logger->addWriter($writer);
        $this->logger->setEventItem('remoteAddr', $_SERVER['REMOTE_ADDR']);

        //$this->_helper->layout->disableLayout();
        //Habilitar layouts
        $this->_helper->_layout->setLayout('tvchat-reporte-layout');
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
        $this->_forward('login');
    }

    public function loginAction() {

        $this->_helper->layout->disableLayout();

        $form = new Application_Form_Login();

        if( $this->getRequest()->isPost() ) {

            $formData = $this->getRequest()->getPost();

            if( $form->isValid( $formData ) ){

                $nick = $form->getValue('login_user');
                $clave = $form->getValue('login_pass');

                if( !empty( $nick ) && !empty( $clave ) ) {

                    if( array_key_exists($nick, $this->usuarios) && $clave == $this->usuarios[$nick]['clave'] ) {

                        $this->logger->info('LOGIN:[' . $nick . ']');
                        $namespace = new Zend_Session_Namespace("entermovil-tvchat-reportes");
                        $namespace->usuario = $nick;
                        $namespace->nombre = $this->usuarios[$nick]['nombre'];

                        if( $this->usuarios[$nick]['rol'] == 'admin' ){

                            $namespace->accesos = array(

                                'admin' => 'FULL',
                                'cobros' => '88,89,94,95,96',
                                'id_promociones' => '88,89,94,95,96',
                                'numeros' => "'6767', '8540'",
                                'altas_bajas' => array('FULL'),
                                'cobros_chat' => 'FULL'
                            );

                        }else if( $this->usuarios[$nick]['rol'] == 'asesor' ){

                            $namespace->accesos = array(

                                'cobros' => '89',
                                'id_promociones' => '89',
                                'numeros' => "'6767'",
                                'altas_bajas' => array('FULL')
                            );

                        }else if( $this->usuarios[$nick]['rol'] == 'productor' ){

                            $namespace->accesos = array(

                                'id_promociones' => '88,89,94,95,96',
                                'numeros' => "'6767', '8540'",
                                'altas_bajas' => array('FULL')
                            );
                        }

                        $this->_redirect('/tvchat-reportes/reporte/');

                    } else {

                        $this->_redirect('/tvchat-reportes/login');
                    }

                } else {

                    $this->_redirect('/tvchat-reportes/login');
                }

            } else {

                $this->_redirect('/tvchat-reportes/login');
            }
        }
    }

    public function logoutAction() {

        $namespace = new Zend_Session_Namespace("entermovil-tvchat-reportes");
        $this->logger->info('logout:[' . ( isset($namespace->usuario) ? $namespace->usuario : '')  . ']('.$namespace->nombre.')');

        unset($namespace->usuario);
        unset($namespace->nombre);

        $namespace->unsetAll();
        unset($namespace);

        $this->_redirect('/tvchat-reportes/login');
    }

    public function reporteAction(){

        $namespace = new Zend_Session_Namespace("entermovil-tvchat-reportes");
        if( !isset( $namespace->usuario ) ){

            $this->_redirect('/tvchat-reportes/login');
        }

        $this->_helper->_layout->setLayout('tvchat-reporte-layout');

        $this->view->headLink()->setStylesheet('/css/reportes_base.css', 'screen');
        $this->view->headScript()->appendFile('/js/tvchat_reportes_suscriptos.js', 'text/javascript');
        $this->view->headLink()->appendStylesheet('/css/tvchat_reportes_altas_bajas.css', 'screen');
        $this->view->headTitle()->append('Resumen - Altas - Bajas');

        $fecha_seleccionada = $this->_getParam('fecha', null);

        if(!is_null($fecha_seleccionada)) {

            list($anho, $mes) = explode('-', $fecha_seleccionada);
            $mes = (int)$mes;

        } else {

            $anho = date('Y');
            $mes = date('n');

        }

        $this->_setupRangoSeleccion( $anho, $mes );

        $this->view->nombre_mes = $this->meses[$mes-1];

        $this->view->cantidad_dias = date('t', mktime(0, 0, 0, $mes, 1, $anho));

        $this->view->anho = $anho;

        $this->view->mes = $mes;

        $this->view->dia_hoy = date('j');

        $this->view->dias_semana = $this->dias_semana;

        $this->view->nombres_dias_del_mes = $this->cargarNombresDiasDelMes( $anho, $mes );

        $this->view->rango_seleccion = $this->rango_seleccion;

        $datos = array();

        $total_general = array(
            'total_general' => array(
                'ALTA' => 0,
                'BAJA' => 0
            ),
            'total_suscriptos' => 0
        );

        $numeros = $this->numeros;

        if( $namespace->accesos['numeros'] == "'6767'" ){

            $numeros = array( '6767' );
        }

        foreach($numeros as $numero) {

            $resultado = $this->_cargarSuscriptosNumero( $numero, $anho, $mes );
            $datos[$numero] = $resultado[$numero];

        }

        $cantidad_dias = date('t', mktime(0, 0, 0, $mes, 1, $anho));
        for($i=1; $i<=$cantidad_dias; $i++) {

            $total_general['total'][$i]['ALTA'] = 0;
            $total_general['total'][$i]['BAJA'] = 0;
        }

        foreach( $numeros as $numero ){

            foreach( $datos[$numero]['altas_bajas_x_mes']['TOTALES_MES']['datos'] as $dia => $datos_del_mes ){

                    $total_general['total'][$dia]['ALTA'] +=  $datos_del_mes['ALTA'];
                    $total_general['total'][$dia]['BAJA'] +=  $datos_del_mes['BAJA'];

                    $total_general['total_general']['ALTA'] += $datos_del_mes['ALTA'];
                    $total_general['total_general']['BAJA'] += $datos_del_mes['BAJA'];
            }

            $total_general['total_suscriptos'] += $datos[$numero]['total_suscriptos'];
        }

        $suscriptos_acumulados = $this->_consulta( 'SUSCRIPTOS_ACUMULADOS', array( 'anho' => $anho, 'mes' => $mes ) );
        //print_r($suscriptos_acumulados);exit;
        $this->logger->info('mirar 2 -> ' . print_r( $total_general, true ));

        $this->view->numeros = $numeros;
        $this->view->datos = $datos;
        $this->view->total_general = $total_general;
        $this->view->carriers = $this->carriers;
        $this->view->suscriptos_acumulados = $suscriptos_acumulados;

    }

    public function altasBajasChatAction(){

        $namespace = new Zend_Session_Namespace("entermovil-tvchat-reportes");
        if( !isset( $namespace->usuario ) ){

            $this->_redirect('/tvchat-reportes/login');
        }

        $this->_helper->_layout->setLayout('tvchat-reporte-layout');

        $this->view->headLink()->setStylesheet('/css/reportes_base.css', 'screen');
        //$this->view->headScript()->appendFile('/js/tvchat_reportes_suscriptos.js', 'text/javascript');
        $this->view->headScript()->appendFile('/js/tvchat_reportes_suscriptos_chat.js', 'text/javascript');
        $this->view->headLink()->appendStylesheet('/css/tvchat_reportes_altas_bajas_chat.css', 'screen');
        $this->view->headTitle()->append('Altas - Bajas - Chat');

        $fecha_seleccionada = $this->_getParam('fecha', null);

        if(!is_null($fecha_seleccionada)) {

            list($anho, $mes) = explode('-', $fecha_seleccionada);
            $mes = (int)$mes;

        } else {

            $anho = date('Y');
            $mes = date('n');

        }

        $this->_setupRangoSeleccion( $anho, $mes );

        $this->view->nombre_mes = $this->meses[$mes-1];

        $this->view->cantidad_dias = date('t', mktime(0, 0, 0, $mes, 1, $anho));

        $this->view->anho = $anho;

        $this->view->mes = $mes;

        $this->view->dia_hoy = date('j');

        $this->view->dias_semana = $this->dias_semana;

        $this->view->nombres_dias_del_mes = $this->cargarNombresDiasDelMes( $anho, $mes );

        $this->view->rango_seleccion = $this->rango_seleccion;

        $datos = array();

        $total_general = array(
            'total_general' => array(
                'ALTA' => 0,
                'BAJA' => 0
            ),
            'total_suscriptos' => 0
        );

        $numeros = array( '6767' );

        foreach($numeros as $numero) {

            $resultado = $this->_cargarSuscriptosNumeroChat( $numero, $anho, $mes );
            $datos[$numero] = $resultado[$numero];

        }

        $cantidad_dias = date('t', mktime(0, 0, 0, $mes, 1, $anho));
        for($i=1; $i<=$cantidad_dias; $i++) {

            $total_general['total'][$i]['ALTA'] = 0;
            $total_general['total'][$i]['BAJA'] = 0;
        }

        foreach( $numeros as $numero ){

            foreach( $datos[$numero]['altas_bajas_x_mes']['TOTALES_MES']['datos'] as $dia => $datos_del_mes ){

                    $total_general['total'][$dia]['ALTA'] +=  $datos_del_mes['ALTA'];
                    $total_general['total'][$dia]['BAJA'] +=  $datos_del_mes['BAJA'];

                    $total_general['total_general']['ALTA'] += $datos_del_mes['ALTA'];
                    $total_general['total_general']['BAJA'] += $datos_del_mes['BAJA'];
            }

            $total_general['total_suscriptos'] += $datos[$numero]['total_suscriptos'];
        }

        $this->logger->info('mirar 2 -> ' . print_r( $total_general, true ));

        $this->view->numeros = $numeros;
        $this->view->datos = $datos;
        $this->view->total_general = $total_general;
        $this->view->carriers = $this->carriers;
    }

    public function reporteXHoraAction() {

        $namespace = new Zend_Session_Namespace("entermovil-tvchat-reportes");

        if( !isset( $namespace->usuario ) ){

            $this->_redirect('/tvchat-reportes/login');
        }

        $this->_helper->_layout->setLayout('tvchat-reporte-layout');

        $this->view->headLink()->setStylesheet('/css/reportes_base.css', 'screen');
        $this->view->headLink()->appendStylesheet('/css/tvchat_reportes_altas_bajas_x_hora.css', 'screen');
        $this->view->headLink()->appendStylesheet('http://code.jquery.com/ui/1.10.0/themes/base/jquery-ui.css');

        $this->view->headScript()->appendFile('http://code.jquery.com/ui/1.10.0/jquery-ui.js', 'text/javascript');
        $this->view->headScript()->appendFile('/js/tvchat_reportes_resumen.js', 'text/javascript');

        $this->view->headTitle()->append('Resumen Cobros');


        $this->view->promociones = array(

            'TVCHAT' => 88,
            'JUGAR' => 89
        );

        if(  $namespace->accesos['numeros'] == "'6767'" ){

            $this->view->promociones = array(

                'JUGAR' => 89
            );
        }

        $fecha_seleccionada = $this->_getParam('fecha', null);

        $this->view->fecha = $fecha_seleccionada;

        if(!is_null($fecha_seleccionada)) {

            list( $anho, $mes, $dia ) = explode('-', $fecha_seleccionada);
            $mes = (int)$mes;
            $dia = (int)$dia;

        } else {

            $anho = date('Y');
            $mes = date('n');
            $dia = date('j');

        }

        $cadena_mes = $mes < 10 ? '0' . $mes : $mes;
        $fecha_seleccionada = $anho . '-' . $cadena_mes. '-' . $dia;

        $horas_exactas = array(
            '0' => array(
                'ALTA' => 0,
                'BAJA' => 0
            ),
            '1' => array(
                'ALTA' => 0,
                'BAJA' => 0
            ),
            '2' => array(
                'ALTA' => 0,
                'BAJA' => 0
            ),
            '3' => array(
                'ALTA' => 0,
                'BAJA' => 0
            ),
            '4' => array(
                'ALTA' => 0,
                'BAJA' => 0
            ),
            '5' => array(
                'ALTA' => 0,
                'BAJA' => 0
            ),
            '6' => array(
                'ALTA' => 0,
                'BAJA' => 0
            ),
            '7' => array(
                'ALTA' => 0,
                'BAJA' => 0
            ),
            '8' => array(
                'ALTA' => 0,
                'BAJA' => 0
            ),
            '9' => array(
                'ALTA' => 0,
                'BAJA' => 0
            ),
            '10' => array(
                'ALTA' => 0,
                'BAJA' => 0
            ),
            '11' => array(
                'ALTA' => 0,
                'BAJA' => 0
            ),
            '12' => array(
                'ALTA' => 0,
                'BAJA' => 0
            ),
            '13' => array(
                'ALTA' => 0,
                'BAJA' => 0
            ),
            '14' => array(
                'ALTA' => 0,
                'BAJA' => 0
            ),
            '15' => array(
                'ALTA' => 0,
                'BAJA' => 0
            ),
            '16' => array(
                'ALTA' => 0,
                'BAJA' => 0
            ),
            '17' => array(
                'ALTA' => 0,
                'BAJA' => 0
            ),
            '18' => array(
                'ALTA' => 0,
                'BAJA' => 0
            ),
            '19' => array(
                'ALTA' => 0,
                'BAJA' => 0
            ),
            '20' => array(
                'ALTA' => 0,
                'BAJA' => 0
            ),
            '21' => array(
                'ALTA' => 0,
                'BAJA' => 0
            ),
            '22' => array(
                'ALTA' => 0,
                'BAJA' => 0
            ),
            '23' => array(
                'ALTA' => 0,
                'BAJA' => 0
            ),
            '24' => array(
                'ALTA' => 0,
                'BAJA' => 0
            ),
        );

        $parametros = array( 'fecha_seleccionada' => $fecha_seleccionada );

        $datos = $this->_consulta( 'GET_ALTAS_BAJAS_X_HORA', $parametros );

        if( !is_null( $datos ) ){

            $datos_mostrar = array();

            foreach( $datos as $numero => $aliases ){

                foreach( $aliases as $alias => $horas ){

                    foreach( $horas_exactas as $hora => $acciones ){

                        if( isset( $datos[$numero][$alias][$hora] ) ){

                            if( isset( $datos[$numero][$alias][$hora]['ALTA'] ) ){

                                $datos_mostrar[$numero][$alias][$hora]['ALTA'] = $datos[$numero][$alias][$hora]['ALTA'];
                            }else{

                                $datos_mostrar[$numero][$alias][$hora]['ALTA'] = 0;
                            }

                            if( isset( $datos[$numero][$alias][$hora]['BAJA'] ) ){

                                $datos_mostrar[$numero][$alias][$hora]['BAJA'] = $datos[$numero][$alias][$hora]['BAJA'];
                            }else{

                                $datos_mostrar[$numero][$alias][$hora]['BAJA'] = 0;
                            }

                        }else{

                            $datos_mostrar[$numero][$alias][$hora] = $acciones;
                        }
                    }
                }
            }

            $this->view->vacio = false;
            $this->view->datos = $datos_mostrar;

        }else{

            $this->view->vacio = true;
        }
    }

    public function reporteXMinutoAction() {

        $namespace = new Zend_Session_Namespace("entermovil-tvchat-reportes");

        if( !isset( $namespace->usuario ) ){

            $this->_redirect('/tvchat-reportes/login');
        }

        //$this->_helper->_layout->setLayout('tvchat-tvchat-reporte-layout');
        $this->_helper->_layout->setLayout('tvchat-reporte-layout');

        $this->view->headLink()->setStylesheet('/css/reportes_base.css', 'screen');
        $this->view->headLink()->appendStylesheet('/css/tvchat_reportes_altas_bajas_x_hora.css', 'screen');
        $this->view->headLink()->appendStylesheet('http://code.jquery.com/ui/1.10.0/themes/base/jquery-ui.css');

        $this->view->headScript()->appendFile('http://code.jquery.com/ui/1.10.0/jquery-ui.js', 'text/javascript');
        $this->view->headScript()->appendFile('/js/tvchat_reportes_resumen.js', 'text/javascript');

        $this->view->headTitle()->append('Resumen Cobros');

        $parametros_get = $this->_getAllParams('fecha', 'hora', 'id-promocion', null);

        if( !is_null( $parametros_get ) ) {

            $minutos_exactos = array(
                '0' => array(
                    'ALTA' => 0,
                    'BAJA' => 0
                ),
                '1' => array(
                    'ALTA' => 0,
                    'BAJA' => 0
                ),
                '2' => array(
                    'ALTA' => 0,
                    'BAJA' => 0
                ),
                '3' => array(
                    'ALTA' => 0,
                    'BAJA' => 0
                ),
                '4' => array(
                    'ALTA' => 0,
                    'BAJA' => 0
                ),
                '5' => array(
                    'ALTA' => 0,
                    'BAJA' => 0
                ),
                '6' => array(
                    'ALTA' => 0,
                    'BAJA' => 0
                ),
                '7' => array(
                    'ALTA' => 0,
                    'BAJA' => 0
                ),
                '8' => array(
                    'ALTA' => 0,
                    'BAJA' => 0
                ),
                '9' => array(
                    'ALTA' => 0,
                    'BAJA' => 0
                ),
                '10' => array(
                    'ALTA' => 0,
                    'BAJA' => 0
                ),
                '11' => array(
                    'ALTA' => 0,
                    'BAJA' => 0
                ),
                '12' => array(
                    'ALTA' => 0,
                    'BAJA' => 0
                ),
                '13' => array(
                    'ALTA' => 0,
                    'BAJA' => 0
                ),
                '14' => array(
                    'ALTA' => 0,
                    'BAJA' => 0
                ),
                '15' => array(
                    'ALTA' => 0,
                    'BAJA' => 0
                ),
                '16' => array(
                    'ALTA' => 0,
                    'BAJA' => 0
                ),
                '17' => array(
                    'ALTA' => 0,
                    'BAJA' => 0
                ),
                '18' => array(
                    'ALTA' => 0,
                    'BAJA' => 0
                ),
                '19' => array(
                    'ALTA' => 0,
                    'BAJA' => 0
                ),
                '20' => array(
                    'ALTA' => 0,
                    'BAJA' => 0
                ),
                '21' => array(
                    'ALTA' => 0,
                    'BAJA' => 0
                ),
                '22' => array(
                    'ALTA' => 0,
                    'BAJA' => 0
                ),
                '23' => array(
                    'ALTA' => 0,
                    'BAJA' => 0
                ),
                '24' => array(
                    'ALTA' => 0,
                    'BAJA' => 0
                ),
                '25' => array(
                    'ALTA' => 0,
                    'BAJA' => 0
                )
                ,'26' => array(
                    'ALTA' => 0,
                    'BAJA' => 0
                ),'27' => array(
                    'ALTA' => 0,
                    'BAJA' => 0
                ),'28' => array(
                    'ALTA' => 0,
                    'BAJA' => 0
                ),'29' => array(
                    'ALTA' => 0,
                    'BAJA' => 0
                ),'30' => array(
                    'ALTA' => 0,
                    'BAJA' => 0
                ),'31' => array(
                    'ALTA' => 0,
                    'BAJA' => 0
                ),'32' => array(
                    'ALTA' => 0,
                    'BAJA' => 0
                ),'33' => array(
                    'ALTA' => 0,
                    'BAJA' => 0
                ),'34' => array(
                    'ALTA' => 0,
                    'BAJA' => 0
                ),'35' => array(
                    'ALTA' => 0,
                    'BAJA' => 0
                ),'36' => array(
                    'ALTA' => 0,
                    'BAJA' => 0
                ),'37' => array(
                    'ALTA' => 0,
                    'BAJA' => 0
                ),'38' => array(
                    'ALTA' => 0,
                    'BAJA' => 0
                ),'39' => array(
                    'ALTA' => 0,
                    'BAJA' => 0
                ),'40' => array(
                    'ALTA' => 0,
                    'BAJA' => 0
                ),'41' => array(
                    'ALTA' => 0,
                    'BAJA' => 0
                ),'42' => array(
                    'ALTA' => 0,
                    'BAJA' => 0
                ),'43' => array(
                    'ALTA' => 0,
                    'BAJA' => 0
                ),'44' => array(
                    'ALTA' => 0,
                    'BAJA' => 0
                ),'45' => array(
                    'ALTA' => 0,
                    'BAJA' => 0
                ),'46' => array(
                    'ALTA' => 0,
                    'BAJA' => 0
                ),'47' => array(
                    'ALTA' => 0,
                    'BAJA' => 0
                ),'48' => array(
                    'ALTA' => 0,
                    'BAJA' => 0
                ),'49' => array(
                    'ALTA' => 0,
                    'BAJA' => 0
                ),'50' => array(
                    'ALTA' => 0,
                    'BAJA' => 0
                ),'51' => array(
                    'ALTA' => 0,
                    'BAJA' => 0
                ),'52' => array(
                    'ALTA' => 0,
                    'BAJA' => 0
                ),'53' => array(
                    'ALTA' => 0,
                    'BAJA' => 0
                ),'54' => array(
                    'ALTA' => 0,
                    'BAJA' => 0
                ),'55' => array(
                    'ALTA' => 0,
                    'BAJA' => 0
                ),'56' => array(
                    'ALTA' => 0,
                    'BAJA' => 0
                ),'57' => array(
                    'ALTA' => 0,
                    'BAJA' => 0
                ),'58' => array(
                    'ALTA' => 0,
                    'BAJA' => 0
                ),'59' => array(
                    'ALTA' => 0,
                    'BAJA' => 0
                ),'60' => array(
                    'ALTA' => 0,
                    'BAJA' => 0
                ),
            );

            $parametros = array(

                'fecha' => $parametros_get['fecha'],
                'hora' => $parametros_get['hora'],
                'id_promocion' => $parametros_get['id-promocion']
            );

            $datos = $this->_consulta( 'GET_ALTAS_BAJAS_X_MINUTO', $parametros );

            if( !is_null( $datos ) ){

                $datos_mostrar = array();

                    foreach( $datos as $alias => $minutos ){

                        foreach( $minutos_exactos as $minuto => $acciones ){

                            if( isset( $datos[$alias][$minuto] ) ){

                                if( isset( $datos[$alias][$minuto]['ALTA'] ) ){

                                    $datos_mostrar[$alias][$minuto]['ALTA'] = $datos[$alias][$minuto]['ALTA'];
                                }else{

                                    $datos_mostrar[$alias][$minuto]['ALTA'] = 0;
                                }

                                if( isset( $datos[$alias][$minuto]['BAJA'] ) ){

                                    $datos_mostrar[$alias][$minuto]['BAJA'] = $datos[$alias][$minuto]['BAJA'];
                                }else{

                                    $datos_mostrar[$alias][$minuto]['BAJA'] = 0;
                                }

                            }else{

                                $datos_mostrar[$alias][$minuto] = $acciones;
                            }
                        }
                }

                $this->view->vacio = false;
                $this->view->datos = $datos_mostrar;

            }else{

                $this->view->vacio = true;
            }

        }

    }

    public function suscriptosPorHoraAction() {

        $namespace = new Zend_Session_Namespace("entermovil-tvchat-reportes");

        if( !isset( $namespace->usuario ) ){

            $this->_redirect('/tvchat-reportes/login');
        }

        $this->_helper->_layout->setLayout('tvchat-reporte-layout');

        $this->view->headLink()->setStylesheet('/css/reportes_base.css', 'screen');
        $this->view->headScript()->appendFile('/js/tvchat_reportes_suscriptos_por_hora.js', 'text/javascript');
        $this->view->headLink()->appendStylesheet('/css/reportes_suscriptos_por_hora.css', 'screen');

        $this->view->headTitle()->append('Suscriptos');

        $fecha_seleccionada = $this->_getParam('fecha', null);
        if(!is_null($fecha_seleccionada)) {
            list($anho, $mes) = explode('-', $fecha_seleccionada);
            $mes = (int)$mes;
        } else {
            $anho = date('Y');
            $mes = date('n');
        }

        $this->_setupRangoSeleccion($anho, $mes);

        $this->view->nombre_mes = $this->meses[$mes-1];

        $this->view->cantidad_dias = date('t', mktime(0, 0, 0, $mes, 1, $anho));


        $this->view->anho = $anho;
        $this->view->mes = $mes;

        $this->view->dia_hoy = date('j');
        $this->view->hora_actual = date('G');

        $this->view->dias_semana = $this->dias_semana;

        $this->view->nombres_dias_del_mes = $this->cargarNombresDiasDelMes($anho, $mes);

        $this->view->rango_seleccion = $this->rango_seleccion;

        $promociones = $this->_cargarPromociones();
        $this->view->promociones = $promociones;

        $id_promocion = $this->_getParam('id_promocion', 0);
        $carriers_promocion = array();
        if($id_promocion > 0) {

            $this->view->id_promocion_seleccionado = $id_promocion;
            foreach($promociones as $promocion) {
                if($promocion['id_promocion'] == $id_promocion) {
                    $this->view->promocion = $promocion;
                    switch($id_promocion) {

                        case 88:
                        case 89:
                        case 94:
                        case 95:
                            $carriers_promocion = array(1,2);
                            break;

                        default:
                            $carriers_promocion = array(2);
                    }

                    break;
                }
            }

            $this->view->carriers_promocion = $carriers_promocion;

            $datos = $this->_cargarSuscriptosPromocion($id_promocion, $anho, $mes);



            $this->view->numeros = $this->numeros;
            $this->view->datos = $datos;
            $this->view->carriers = $this->carriers;

            $this->logger->info('datos:[' . print_r($datos, true) . ']');

        } else {

            //
        }

    }

    public function cobrosAction() {

        $namespace = new Zend_Session_Namespace("entermovil-tvchat-reportes");

        if( !isset( $namespace->usuario ) ){

            $this->_redirect('/tvchat-reportes/login');
        }

        $this->view->headLink()->setStylesheet('/css/reportes_base.css', 'screen');
        $this->view->headScript()->appendFile('/js/tvchat_reportes_cobros.js', 'text/javascript');
        $this->view->headLink()->appendStylesheet('/css/reportes_cobros.css', 'screen');

        $this->view->headTitle()->append('Cobros');

        $fecha_seleccionada = $this->_getParam('fecha', null);

        if(!is_null($fecha_seleccionada)) {
            list($anho, $mes) = explode('-', $fecha_seleccionada);
            $mes = (int)$mes;
        } else {
            $anho = date('Y');
            $mes = date('n');
        }

        $this->_setupRangoSeleccion($anho, $mes);

        $this->view->nombre_mes = $this->meses[$mes-1];

        $this->view->cantidad_dias = date('t', mktime(0, 0, 0, $mes, 1, $anho));

        $this->view->anho = $anho;
        $this->view->mes = $mes;

        $this->view->dia_hoy = date('j');

        $this->view->dias_semana = $this->dias_semana;

        $this->view->nombres_dias_del_mes = $this->cargarNombresDiasDelMes($anho, $mes);

        $this->view->rango_seleccion = $this->rango_seleccion;

        $resultado = $this->_cargarCobrosPorCarrier($anho,$mes,1);

        //print_r($resultado);exit;

        //definicion de estructuras
        $datos = array();
        $totales_x_promocion_x_dia = array();
        $totales_x_dia = array();
        //lateral derecha promociones
        $total_promocion_mes = array(
            'cobros'=>0,
            'enter'=>0,
            'otros'=>0,
        );
        //lateral derecha totales por promocion
        $totales_generales_promocion_mes = array(

            'cobros' => 0,
            'enter' => 0,
            'otros' => 0,
        );
        $datos['totales_generales'] = array(

            'cobros' => 0,
            'enter' => 0,
            'otros' => 0,
        );

        for($i=1; $i<=$this->view->cantidad_dias; $i++) {

            $totales_x_promocion_x_dia[$i]['total_cobros_dia'] = 0;
            $totales_x_promocion_x_dia[$i]['total_neto_gs_dia'] = 0;
            $totales_x_promocion_x_dia[$i]['total_bruto_gs_dia'] = 0;
            //global
            $totales_x_dia[$i]['total_cobros_dia'] = 0;
            $totales_x_dia[$i]['total_neto_gs_dia'] = 0;
            $totales_x_dia[$i]['total_bruto_gs_dia'] = 0;
        }

        $totales_x_dia['suscriptos'] = 0;

        $datos['totales'] = $totales_x_dia;

        $numeros = $this->numeros;

        if( $namespace->accesos['numeros'] == "'6767'" ){

            $numeros = array( '6767' );
        }

        foreach($numeros as $numero) {

            foreach ( $resultado[$numero] as $alias=>$estructura_alias ){

                $datos['promociones'][$numero][$alias]['totales_x_dia'] = $totales_x_promocion_x_dia;
                $datos['promociones'][$numero][$alias]['suscriptos_x_carrier']['total'] = 0;
                $datos['promociones'][$numero][$alias]['totales_generales_promocion_mes'] = $totales_generales_promocion_mes;

                foreach( $estructura_alias as $id_carrier=>$estructura_id_carrier ){

                    $datos['promociones'][$numero][$alias]['total_promocion_mes'][$id_carrier] = $total_promocion_mes;

                    for( $i=1; $i<=$this->view->cantidad_dias; $i++ ) {

                        if(!isset($estructura_id_carrier[$i])) {

                            $datos['promociones'][$numero][$alias]['cobros_x_carrier'][$id_carrier][$i] = array(
                                'total_cobros' => 0,
                                'total_bruto_gs' => 0,
                                'total_bruto_usd' => 0,
                                'total_neto_gs' => 0,
                                'total_neto_usd' => 0,
                            );
                        }else{

                            $datos['promociones'][$numero][$alias]['cobros_x_carrier'][$id_carrier][$i] = $estructura_id_carrier[$i];
                        }

                        //por servicio
                        $datos['promociones'][$numero][$alias]['totales_x_dia'][$i]['total_cobros_dia'] += $datos['promociones'][$numero][$alias]['cobros_x_carrier'][$id_carrier][$i]['total_cobros'];
                        $datos['promociones'][$numero][$alias]['totales_x_dia'][$i]['total_neto_gs_dia'] += $datos['promociones'][$numero][$alias]['cobros_x_carrier'][$id_carrier][$i]['total_neto_gs'];
                        $datos['promociones'][$numero][$alias]['totales_x_dia'][$i]['total_bruto_gs_dia'] += $datos['promociones'][$numero][$alias]['cobros_x_carrier'][$id_carrier][$i]['total_bruto_gs'];
                        //totales laterales promociones promocion
                        $datos['promociones'][$numero][$alias]['total_promocion_mes'][$id_carrier]['cobros'] += $datos['promociones'][$numero][$alias]['cobros_x_carrier'][$id_carrier][$i]['total_cobros'];
                        $datos['promociones'][$numero][$alias]['total_promocion_mes'][$id_carrier]['enter'] += $datos['promociones'][$numero][$alias]['cobros_x_carrier'][$id_carrier][$i]['total_neto_gs'];
                        $datos['promociones'][$numero][$alias]['total_promocion_mes'][$id_carrier]['otros'] += $datos['promociones'][$numero][$alias]['cobros_x_carrier'][$id_carrier][$i]['total_bruto_gs'];

                        //totales inferior
                        $datos['totales'][$i]['total_cobros_dia'] += $datos['promociones'][$numero][$alias]['cobros_x_carrier'][$id_carrier][$i]['total_cobros'];
                        $datos['totales'][$i]['total_neto_gs_dia'] += $datos['promociones'][$numero][$alias]['cobros_x_carrier'][$id_carrier][$i]['total_neto_gs'];
                        $datos['totales'][$i]['total_bruto_gs_dia'] += $datos['promociones'][$numero][$alias]['cobros_x_carrier'][$id_carrier][$i]['total_bruto_gs'];

                    }

                    //suscriptos por carrier
                    $datos['promociones'][$numero][$alias]['suscriptos_x_carrier'][$id_carrier]['suscriptos'] = $estructura_id_carrier['suscriptos'];
                    $datos['promociones'][$numero][$alias]['suscriptos_x_carrier']['total'] += $estructura_id_carrier['suscriptos'];
                    $datos['totales']['suscriptos'] += $estructura_id_carrier['suscriptos'];
                }

                //totales laterales promociones total
                for( $i=1; $i<=$this->view->cantidad_dias; $i++ ) {

                    $datos['promociones'][$numero][$alias]['totales_generales_promocion_mes']['cobros'] += $datos['promociones'][$numero][$alias]['totales_x_dia'][$i]['total_cobros_dia'];
                    $datos['promociones'][$numero][$alias]['totales_generales_promocion_mes']['enter'] += $datos['promociones'][$numero][$alias]['totales_x_dia'][$i]['total_neto_gs_dia'];
                    $datos['promociones'][$numero][$alias]['totales_generales_promocion_mes']['otros'] += $datos['promociones'][$numero][$alias]['totales_x_dia'][$i]['total_bruto_gs_dia'];
                }
            }
        }

        //totales inferior derecha
        foreach( $datos['totales'] as $dia=>$datos_cobros ){

            $datos['totales_generales']['cobros'] += $datos_cobros['total_cobros_dia'];
            $datos['totales_generales']['enter'] += $datos_cobros['total_neto_gs_dia'];
            $datos['totales_generales']['otros'] += $datos_cobros['total_bruto_gs_dia'];
        }

        $this->view->suscriptos_a_cobrar = $this->_consulta('GET_SUSCRIPTOS_A_COBRAR_DIA_COBROS', array( 'anho' => $anho, 'mes' => $mes ));
        //print_r($this->view->suscriptos_a_cobrar);exit;
        $this->view->numeros = $numeros;
        $this->view->datos = $datos;
        $this->view->carriers = $this->carriers;

    }

    public function cobrosChatAction() {

        
        $namespace = new Zend_Session_Namespace("entermovil-tvchat-reportes");

        if( !isset( $namespace->usuario ) ){

            $this->_redirect('/tvchat-reportes/login');
        }

        $this->view->headLink()->setStylesheet('/css/reportes_base.css', 'screen');
        $this->view->headScript()->appendFile('/js/tvchat_reportes_cobros_chat.js', 'text/javascript');
        $this->view->headLink()->appendStylesheet('/css/tvchat_reportes_cobros_chat.css', 'screen');

        $this->view->headTitle()->append('Cobros-Chat');

        $fecha_seleccionada = $this->_getParam('fecha', null);

        if(!is_null($fecha_seleccionada)) {
            list($anho, $mes) = explode('-', $fecha_seleccionada);
            $mes = (int)$mes;
        } else {
            $anho = date('Y');
            $mes = date('n');
        }

        $this->_setupRangoSeleccion($anho, $mes);

        $this->view->nombre_mes = $this->meses[$mes-1];

        $this->view->cantidad_dias = date('t', mktime(0, 0, 0, $mes, 1, $anho));

        $this->view->anho = $anho;
        $this->view->mes = $mes;

        $this->view->dia_hoy = date('j');

        $this->view->dias_semana = $this->dias_semana;

        $this->view->nombres_dias_del_mes = $this->cargarNombresDiasDelMes($anho, $mes);

        $this->view->rango_seleccion = $this->rango_seleccion;

        $resultado = $this->_cargarCobrosChatPorCarrier($anho,$mes,1);

        //print_r($resultado);exit;

        //definicion de estructuras
        $datos = array();
        $totales_x_promocion_x_dia = array();
        $totales_x_dia = array();
        //lateral derecha promociones
        $total_promocion_mes = array(
            'cobros'=>0,
            'enter'=>0,
            'otros'=>0,
        );
        //lateral derecha totales por promocion
        $totales_generales_promocion_mes = array(

            'cobros' => 0,
            'enter' => 0,
            'otros' => 0,
        );
        $datos['totales_generales'] = array(

            'cobros' => 0,
            'enter' => 0,
            'otros' => 0,
        );

        for($i=1; $i<=$this->view->cantidad_dias; $i++) {

            $totales_x_promocion_x_dia[$i]['total_cobros_dia'] = 0;
            $totales_x_promocion_x_dia[$i]['total_neto_gs_dia'] = 0;
            $totales_x_promocion_x_dia[$i]['total_bruto_gs_dia'] = 0;
            //global
            $totales_x_dia[$i]['total_cobros_dia'] = 0;
            $totales_x_dia[$i]['total_neto_gs_dia'] = 0;
            $totales_x_dia[$i]['total_bruto_gs_dia'] = 0;
        }

        $totales_x_dia['suscriptos'] = 0;

        $datos['totales'] = $totales_x_dia;

        $numeros = array( '6767' );

        foreach($numeros as $numero) {

            foreach ( $resultado[$numero] as $alias=>$estructura_alias ){

                $datos['promociones'][$numero][$alias]['totales_x_dia'] = $totales_x_promocion_x_dia;
                $datos['promociones'][$numero][$alias]['suscriptos_x_carrier']['total'] = 0;
                $datos['promociones'][$numero][$alias]['totales_generales_promocion_mes'] = $totales_generales_promocion_mes;

                foreach( $estructura_alias as $id_carrier=>$estructura_id_carrier ){

                    $datos['promociones'][$numero][$alias]['total_promocion_mes'][$id_carrier] = $total_promocion_mes;

                    for( $i=1; $i<=$this->view->cantidad_dias; $i++ ) {

                        if(!isset($estructura_id_carrier[$i])) {

                            $datos['promociones'][$numero][$alias]['cobros_x_carrier'][$id_carrier][$i] = array(
                                'total_cobros' => 0,
                                'total_bruto_gs' => 0,
                                'total_bruto_usd' => 0,
                                'total_neto_gs' => 0,
                                'total_neto_usd' => 0,
                            );
                        }else{

                            $datos['promociones'][$numero][$alias]['cobros_x_carrier'][$id_carrier][$i] = $estructura_id_carrier[$i];
                        }

                        //por servicio
                        $datos['promociones'][$numero][$alias]['totales_x_dia'][$i]['total_cobros_dia'] += $datos['promociones'][$numero][$alias]['cobros_x_carrier'][$id_carrier][$i]['total_cobros'];
                        $datos['promociones'][$numero][$alias]['totales_x_dia'][$i]['total_neto_gs_dia'] += $datos['promociones'][$numero][$alias]['cobros_x_carrier'][$id_carrier][$i]['total_neto_gs'];
                        $datos['promociones'][$numero][$alias]['totales_x_dia'][$i]['total_bruto_gs_dia'] += $datos['promociones'][$numero][$alias]['cobros_x_carrier'][$id_carrier][$i]['total_bruto_gs'];
                        //totales laterales promociones promocion
                        $datos['promociones'][$numero][$alias]['total_promocion_mes'][$id_carrier]['cobros'] += $datos['promociones'][$numero][$alias]['cobros_x_carrier'][$id_carrier][$i]['total_cobros'];
                        $datos['promociones'][$numero][$alias]['total_promocion_mes'][$id_carrier]['enter'] += $datos['promociones'][$numero][$alias]['cobros_x_carrier'][$id_carrier][$i]['total_neto_gs'];
                        $datos['promociones'][$numero][$alias]['total_promocion_mes'][$id_carrier]['otros'] += $datos['promociones'][$numero][$alias]['cobros_x_carrier'][$id_carrier][$i]['total_bruto_gs'];

                        //totales inferior
                        $datos['totales'][$i]['total_cobros_dia'] += $datos['promociones'][$numero][$alias]['cobros_x_carrier'][$id_carrier][$i]['total_cobros'];
                        $datos['totales'][$i]['total_neto_gs_dia'] += $datos['promociones'][$numero][$alias]['cobros_x_carrier'][$id_carrier][$i]['total_neto_gs'];
                        $datos['totales'][$i]['total_bruto_gs_dia'] += $datos['promociones'][$numero][$alias]['cobros_x_carrier'][$id_carrier][$i]['total_bruto_gs'];

                    }

                    //suscriptos por carrier
                    $datos['promociones'][$numero][$alias]['suscriptos_x_carrier'][$id_carrier]['suscriptos'] = $estructura_id_carrier['suscriptos'];
                    $datos['promociones'][$numero][$alias]['suscriptos_x_carrier']['total'] += $estructura_id_carrier['suscriptos'];
                    $datos['totales']['suscriptos'] += $estructura_id_carrier['suscriptos'];
                }

                //totales laterales promociones total
                for( $i=1; $i<=$this->view->cantidad_dias; $i++ ) {

                    $datos['promociones'][$numero][$alias]['totales_generales_promocion_mes']['cobros'] += $datos['promociones'][$numero][$alias]['totales_x_dia'][$i]['total_cobros_dia'];
                    $datos['promociones'][$numero][$alias]['totales_generales_promocion_mes']['enter'] += $datos['promociones'][$numero][$alias]['totales_x_dia'][$i]['total_neto_gs_dia'];
                    $datos['promociones'][$numero][$alias]['totales_generales_promocion_mes']['otros'] += $datos['promociones'][$numero][$alias]['totales_x_dia'][$i]['total_bruto_gs_dia'];
                }
            }
        }

        //totales inferior derecha
        foreach( $datos['totales'] as $dia=>$datos_cobros ){

            $datos['totales_generales']['cobros'] += $datos_cobros['total_cobros_dia'];
            $datos['totales_generales']['enter'] += $datos_cobros['total_neto_gs_dia'];
            $datos['totales_generales']['otros'] += $datos_cobros['total_bruto_gs_dia'];
        }

        //print_r($datos);

        $this->view->suscriptos_a_cobrar = $this->_consulta('GET_SUSCRIPTOS_A_COBRAR_CHAT_DIA_COBROS', array( 'anho' => $anho, 'mes' => $mes ));
        //print_r($this->view->suscriptos_a_cobrar);exit;
        $this->view->numeros = $numeros;
        $this->view->datos = $datos;
        $this->view->carriers = $this->carriers;

    }

    private function _cargarCobrosPorCarrier( $anho, $mes, $id_pais ){

        $datos = array(
            'id_pais' => $id_pais,
            'anho' => $anho,
            'mes' => $mes,
        );

        $datos_por_promocion = $this->_consulta('GET_COBROS_POR_PROMOCION', $datos);

        return $datos_por_promocion;
    }

    private function _cargarCobrosChatPorCarrier( $anho, $mes, $id_pais ){

        $datos = array(
            'id_pais' => $id_pais,
            'anho' => $anho,
            'mes' => $mes,
        );

        $datos_por_promocion = $this->_consulta('GET_COBROS_CHAT_POR_PROMOCION', $datos);

        return $datos_por_promocion;
    }

    private function _consulta( $accion, $datos = null ){

        $bootstrap = $this->getInvokeArg('bootstrap');
        $options = $bootstrap->getOptions();

        $db = Zend_Db::factory(new Zend_Config($options['resources']['db']));
        $db->getConnection();
        $resultado = null;

        $namespace = new Zend_Session_Namespace("entermovil-tvchat-reportes");

        if( $accion == 'GET_MENSAJES' ){

            $sql = "select *
                    from promosuscripcion.tvchat_mensajes
                    where emitido = false or emitido is null order by id_tvchat_mensaje limit 5";

            $rs = $db->fetchAll( $sql );

            if( !empty( $rs ) ){

                $resultado = '';

                foreach( $rs as $fila ){

                    $resultado .= '_______' .$fila['mensaje'];

                    $where = array(

                        'id_tvchat_mensaje = ? ' => $fila['id_tvchat_mensaje']
                    );

                    $data = array(

                        'emitido' => true,
                    );

                    //$status = $db->update('promosuscripcion.tvchat_mensajes', $data, $where );
                }

                return $resultado;

            }else{

                return $resultado;
            }
        }
        else if( $accion == 'GET_ALTAS_BAJAS_X_HORA' ){

            $sql = "select T1.ts_local::date as fecha, extract(hour from T1.ts_local)::integer as hora,
            T1.id_promocion, T2.numero, T2.alias, T1.accion, count(*)::integer as total from (
                select *
                from promosuscripcion.log_suscriptos PL
                where id_carrier in(1,2) and ts_local::date = ?
                and id_promocion in(".$namespace->accesos['id_promociones'].") and accion = '"."ALTA"."'
                union
                select *
                from promosuscripcion.log_suscriptos PL
                where id_carrier in(1,2) and ts_local::date = ?
                and id_promocion in(".$namespace->accesos['id_promociones'].") and accion = '"."BAJA"."'
            ) T1 join (
                select IP.numero, IP.id_promocion, IP.alias, IP.id_carrier
                from info_promociones IP
                where IP.id_promocion in (".$namespace->accesos['id_promociones'].")
                group by 1,2,3,4
            ) T2 on T1.id_carrier = T2.id_carrier and T1.id_promocion = T2.id_promocion
            group by 1,2,3,4,5,6";

            $rs = $db->fetchAll( $sql, array( $datos['fecha_seleccionada'] , $datos['fecha_seleccionada']  ) );

            if( !empty( $rs ) ){

                foreach( $rs as $fila ){

                    $resultado[$fila['numero']][$fila['alias']][$fila['hora']][$fila['accion']] = $fila['total'];
                }


                return $resultado;

            }else{

                return $resultado;
            }
        }
        else if( $accion == 'GET_ALTAS_BAJAS_X_MINUTO' ){

            $sql = "select T3.alias, T2.* from (
                select PL.id_promocion, extract(minute from PL.ts_local)::integer as minuto, PL.accion, count(*)::integer as total
                from promosuscripcion.log_suscriptos PL
                where id_carrier in(1,2) and ts_local::date = ?
                and id_promocion = ? and accion = '"."ALTA"."'
                and extract(hour from ts_local)::integer = ?
                group by 1,2,3
                union
                select PL.id_promocion, extract(minute from PL.ts_local)::integer as minuto, PL.accion, count(*)::integer as total
                from promosuscripcion.log_suscriptos PL
                where id_carrier in(1,2) and ts_local::date = ?
                and id_promocion = ? and accion = '"."BAJA"."'
                and extract(hour from ts_local)::integer = ?
                group by 1,2,3

            ) T2 join (
            select id_promocion, alias
            from info_promociones
            where id_promocion = ?
            group by 1, 2
            ) T3 on T2.id_promocion = T3.id_promocion";

            $rs = $db->fetchAll( $sql, array( $datos['fecha'] , $datos['id_promocion'],
                $datos['hora'], $datos['fecha'] , $datos['id_promocion'], $datos['hora'],  $datos['id_promocion']  ) );

            if( !empty( $rs ) ){

                foreach( $rs as $fila ){

                    $resultado[$fila['alias']][$fila['minuto']][$fila['accion']] = $fila['total'];
                }

                return $resultado;

            }else{

                return $resultado;
            }
        }
        else if( $accion == 'GET_COBROS_POR_PROMOCION' ){

            $sql = 'select T5.*, T6.alias from (
                select T3.*, coalesce(T4.suscriptos,0) as suscriptos from (
                select T2.numero, T2.fecha, T2.dia_semana, T2.id_carrier, T2.id_promocion, sum(T2.total_cobros)::integer as total_cobros, sum(T2.total_bruto_gs)::integer as total_bruto_gs, sum(T2.total_bruto_usd)::numeric(10,2) as total_bruto_usd,
                sum(T2.total_neto_gs)::integer as total_neto_gs, sum(T2.total_neto_usd)::numeric(10,2) as total_neto_usd
                from (

                select T1.*, (T1.total_cobros * T1.costo_gs)::integer as total_bruto_gs, (T1.total_cobros * T1.costo_usd)::numeric(10,2) as total_bruto_usd,
                (T1.total_cobros * T1.costo_gs * T1.revenue)::integer as total_neto_gs, (T1.total_cobros * T1.costo_usd * T1.revenue)::numeric(10,2) as total_neto_usd
                from (

                select RM.*, CC.costo_gs, CC.costo_usd, RS.porcentaje_proveedor as revenue
                from reporte_mensual_cobros_con_id_servicio(?, ?) RM
                left join codigos_cobro CC on CC.id_servicio = RM.id_servicio and CC.id_carrier = RM.id_carrier and CC.numero = RM.numero
                left join revenue_share RS on RS.numero = RM.numero and RS.id_carrier = RM.id_carrier
                where RM.numero in( ' . $namespace->accesos['numeros'] . ' )
                and RM.id_promocion in( ' . $namespace->accesos['id_promociones'] . ' )
                and
                RM.id_carrier in(
                    select CxP.id_carrier
                    from paises P
                    left join carriers_x_paises CxP on CxP.id_pais = P.id_pais
                    where P.id_pais = ?
                )

                ) T1 order by 2 asc

                ) T2 group by 1,2,3,4,5 order by 1,2,3,4,5

                ) T3 left join (
                    select PS.id_promocion,PS.id_carrier, count(*)::integer as suscriptos from promosuscripcion.suscriptos PS group by 1,2 order by 1
                ) T4 on T3.id_carrier = T4.id_carrier and T3.id_promocion = T4.id_promocion

                ) T5 left join (
                    select IP.id_promocion, IP.alias from info_promociones IP group by 1,2 order by 1
                ) T6 on T5.id_promocion = T6.id_promocion order by 6 desc';

            $rs = $db->fetchAll( $sql, array( $datos['anho'], $datos['mes'], $datos['id_pais'] ) );

            if( !empty( $rs ) ){

                $resultado = array();
                foreach( $rs as $fila ){

                    $dia_del_mes = substr( $fila['fecha'], 8 );
                    $dia_del_mes = (int)$dia_del_mes;
                    $fila['total_bruto_gs'] = $fila['total_bruto_gs'] - $fila['total_neto_gs'];

                    $resultado[$fila['numero']][$fila['alias']][$this->carriers[$fila['id_carrier']]][$dia_del_mes] = (array)$fila;
                    $resultado[$fila['numero']][$fila['alias']][$this->carriers[$fila['id_carrier']]]['suscriptos'] = $fila['suscriptos'];
                }

                return $resultado;

            }else{

                return null;
            }
        }
        else if( $accion == 'GET_COBROS_CHAT_POR_PROMOCION' ){

            $sql = 'select T5.*, T6.alias from (
                select T3.*, coalesce(T4.suscriptos,0) as suscriptos from (
                select T2.numero, T2.fecha, T2.dia_semana, T2.id_carrier, T2.id_promocion, sum(T2.total_cobros)::integer as total_cobros, sum(T2.total_bruto_gs)::integer as total_bruto_gs, sum(T2.total_bruto_usd)::numeric(10,2) as total_bruto_usd,
                sum(T2.total_neto_gs)::integer as total_neto_gs, sum(T2.total_neto_usd)::numeric(10,2) as total_neto_usd
                from (

                select T1.*, (T1.total_cobros * T1.costo_gs)::integer as total_bruto_gs, (T1.total_cobros * T1.costo_usd)::numeric(10,2) as total_bruto_usd,
                (T1.total_cobros * T1.costo_gs * T1.revenue)::integer as total_neto_gs, (T1.total_cobros * T1.costo_usd * T1.revenue)::numeric(10,2) as total_neto_usd
                from (

                select RM.*, CC.costo_gs, CC.costo_usd, RS.porcentaje_proveedor as revenue
                from reporte_mensual_cobros_con_id_servicio(?, ?) RM
                left join codigos_cobro CC on CC.id_servicio = RM.id_servicio and CC.id_carrier = RM.id_carrier and CC.numero = RM.numero
                left join revenue_share RS on RS.numero = RM.numero and RS.id_carrier = RM.id_carrier
                where RM.numero in( ' . "'6767'" . ' )
                and RM.id_promocion in( ' . '94,95,96' . ' )
                and
                RM.id_carrier in(
                    select CxP.id_carrier
                    from paises P
                    left join carriers_x_paises CxP on CxP.id_pais = P.id_pais
                    where P.id_pais = ?
                )

                ) T1 order by 2 asc

                ) T2 group by 1,2,3,4,5 order by 1,2,3,4,5

                ) T3 left join (
                    select PS.id_promocion,PS.id_carrier, count(*)::integer as suscriptos from promosuscripcion.suscriptos PS group by 1,2 order by 1
                ) T4 on T3.id_carrier = T4.id_carrier and T3.id_promocion = T4.id_promocion

                ) T5 left join (
                    select IP.id_promocion, IP.alias from info_promociones IP group by 1,2 order by 1
                ) T6 on T5.id_promocion = T6.id_promocion order by 6 desc';

            $rs = $db->fetchAll( $sql, array( $datos['anho'], $datos['mes'], $datos['id_pais'] ) );

            if( !empty( $rs ) ){

                $resultado = array();
                foreach( $rs as $fila ){

                    $dia_del_mes = substr( $fila['fecha'], 8 );
                    $dia_del_mes = (int)$dia_del_mes;
                    $fila['total_bruto_gs'] = $fila['total_bruto_gs'] - $fila['total_neto_gs'];

                    $resultado[$fila['numero']][$fila['alias']][$this->carriers[$fila['id_carrier']]][$dia_del_mes] = (array)$fila;
                    $resultado[$fila['numero']][$fila['alias']][$this->carriers[$fila['id_carrier']]]['suscriptos'] = $fila['suscriptos'];
                }

                return $resultado;

            }else{

                return null;
            }
        }
        else if( $accion == 'GET_SUSCRIPTOS_A_COBRAR_DIA_COBROS' ){

            $mes = (strlen($datos['mes']) == 1)? '0'. $datos['mes'] : $datos['mes'];

                $sql = "select extract(day from T3.fecha)::integer as dia, T3.accion, T3.cantidad
                from (
                select T1.fecha, T1.accion, coalesce(T2.cantidad, T1.cantidad)::integer as cantidad
                from (
                    WITH cobros AS (
                                            SELECT fecha.*::date, 'COBROS'::varchar as accion, 0 as cantidad
                                            FROM generate_series('".$datos['anho']."-". $mes."-01'::date,'".$datos['anho']."-". $mes."-01'::date + interval '1 month -1', '1 day') fecha
                                             ), intentos AS (
                                            SELECT fecha.*::date, 'INTENTOS'::varchar as accion, 0 as cantidad
                                            FROM generate_series('".$datos['anho']."-". $mes."-01'::date,'".$datos['anho']."-". $mes."-01'::date + interval '1 month -1', '1 day') fecha
                                             )
                                            select *
                                            from intentos
                                            union
                                            SELECT *
                                            FROM cobros
                                            --order by 1,2
                ) T1 left join (
                    select pedr.fecha, pedr.accion, sum( pedr.cantidad )::integer as cantidad
                        from promosuscripcion.proceso_envios_del_dia_resumen pedr
                        where extract(year from fecha)::integer = ? and extract(month from fecha)::integer = ?
                        and id_promocion in (88,89,94,95,96)
                        group by 1,2
                        --order by 1,2
                        union
                            select current_date::date as fecha, T3.accion, sum(T3.cantidad)::integer as total
                                    from (
                                        select 'INTENTOS'::varchar as accion, id_promocion, sum(T1.total)::integer as cantidad
                                        from (
                                        select id_promocion, estado, count(*)::integer as total
                                        from promosuscripcion.proceso_envios_del_dia
                                        where ts_local::date = current_date
                                        and id_carrier in(1,2)
                                        and id_promocion in(88,89,94,95,96)
                                        and id_servicio <> ''
                                        group by 1,2
                                        order by 1,2
                                        ) T1 group by 1,2
                                        --order by 1,2
                                        union
                                            select 'COBROS'::varchar as accion, id_promocion, sum(T2.total)::integer as cantidad
                                            from (
                                            select id_promocion, estado, count(*)::integer as total
                                            from promosuscripcion.proceso_envios_del_dia
                                            where ts_local::date = current_date
                                            and id_carrier in(1,2)
                                            and id_promocion in
                                            (88,89,94,95,96)
                                            and id_servicio <> ''
                                            group by 1,2
                                            order by 1,2
                                            ) T2 where T2.estado = 3 group by 1,2
                                            order by 2,1
                                    ) T3 group by 1,2
                                    order by 1,2
                ) T2 on T1.fecha = T2.fecha and T1.accion = T2.accion
                order by 1,2
                ) T3 where T3.accion = 'INTENTOS'";

            $rs = $db->fetchAll( $sql, array( $datos['anho'], $datos['mes'] ) );

            if( !empty( $rs ) ){

                $resultado['total'] = 0;

                foreach( $rs as $fila ){

                    $resultado['por_dia_semana'][$fila['dia']] = $fila['cantidad'];
                    $resultado['total'] += $fila['cantidad'];
                }

                return $resultado;

            }else{

                for( $i=0; $i<=6; $i++ ){

                    $resultado[$i] = 0;
                }

                return $resultado;
            }
        }else if( $accion == 'GET_SUSCRIPTOS_A_COBRAR_CHAT_DIA_COBROS' ){

            $mes = (strlen($datos['mes']) == 1)? '0'. $datos['mes'] : $datos['mes'];

            $sql = "select extract(day from T3.fecha)::integer as dia, T3.accion, T3.cantidad
                from (
                select T1.fecha, T1.accion, coalesce(T2.cantidad, T1.cantidad)::integer as cantidad
                from (
                    WITH cobros AS (
                                            SELECT fecha.*::date, 'COBROS'::varchar as accion, 0 as cantidad
                                            FROM generate_series('".$datos['anho']."-". $mes."-01'::date,'".$datos['anho']."-". $mes."-01'::date + interval '1 month -1', '1 day') fecha
                                             ), intentos AS (
                                            SELECT fecha.*::date, 'INTENTOS'::varchar as accion, 0 as cantidad
                                            FROM generate_series('".$datos['anho']."-". $mes."-01'::date,'".$datos['anho']."-". $mes."-01'::date + interval '1 month -1', '1 day') fecha
                                             )
                                            select *
                                            from intentos
                                            union
                                            SELECT *
                                            FROM cobros
                                            --order by 1,2
                ) T1 left join (
                    select pedr.fecha, pedr.accion, sum( pedr.cantidad )::integer as cantidad
                        from promosuscripcion.proceso_envios_del_dia_resumen pedr
                        where extract(year from fecha)::integer = ? and extract(month from fecha)::integer = ?
                        and id_promocion in (88,94,95,96)
                        group by 1,2
                        --order by 1,2
                        union
                            select current_date::date as fecha, T3.accion, sum(T3.cantidad)::integer as total
                                    from (
                                        select 'INTENTOS'::varchar as accion, id_promocion, sum(T1.total)::integer as cantidad
                                        from (
                                        select id_promocion, estado, count(*)::integer as total
                                        from promosuscripcion.proceso_envios_del_dia
                                        where ts_local::date = current_date
                                        and id_carrier in(1,2)
                                        and id_promocion in(88,94,95,96)
                                        and id_servicio <> ''
                                        group by 1,2
                                        order by 1,2
                                        ) T1 group by 1,2
                                        --order by 1,2
                                        union
                                            select 'COBROS'::varchar as accion, id_promocion, sum(T2.total)::integer as cantidad
                                            from (
                                            select id_promocion, estado, count(*)::integer as total
                                            from promosuscripcion.proceso_envios_del_dia
                                            where ts_local::date = current_date
                                            and id_carrier in(1,2)
                                            and id_promocion in
                                            (88,94,95,96)
                                            and id_servicio <> ''
                                            group by 1,2
                                            order by 1,2
                                            ) T2 where T2.estado = 3 group by 1,2
                                            order by 2,1
                                    ) T3 group by 1,2
                                    order by 1,2
                ) T2 on T1.fecha = T2.fecha and T1.accion = T2.accion
                order by 1,2
                ) T3 where T3.accion = 'INTENTOS'";

            $rs = $db->fetchAll( $sql, array( $datos['anho'], $datos['mes'] ) );

            if( !empty( $rs ) ){

                $resultado['total'] = 0;

                foreach( $rs as $fila ){

                    $resultado['por_dia_semana'][$fila['dia']] = $fila['cantidad'];
                    $resultado['total'] += $fila['cantidad'];
                }

                return $resultado;

            }else{

                for( $i=0; $i<=6; $i++ ){

                    $resultado[$i] = 0;
                }

                return $resultado;
            }
        }else if( $accion == 'SUSCRIPTOS_ACUMULADOS' ){

            $mes = (strlen($datos['mes']) == 1)? '0'. $datos['mes'] : $datos['mes'];

            $sql = "select T3.dia, coalesce(T4.suscriptos, 0)::integer as suscriptos
                    from (
                            SELECT extract(day from fecha)::integer as dia
                            FROM generate_series( '".$datos['anho']."-". $mes."-01'::date,'".$datos['anho']."-". $mes."-01'::date + interval '1 month -1', '1 day') fecha
                        ) T3 left join (
                            select extract( day from T2.fecha)::integer as dia, sum(T2.suscriptos)::integer as suscriptos
                            from (
                            select T1.id_promocion, T1.fecha, (T1.altas - T1.bajas)::integer as suscriptos
                            from reportes_altas_bajas_cobros_x_dia T1
                            where id_promocion in(88,89,94,95,96) and extract(year from fecha)::integer = ? and extract(month from fecha)::integer = ?
                            order by 1,2
                        ) T2 group by 1
                        order by 1
                    ) T4 on T3.dia = T4.dia
                ";

            $rs = $db->fetchAll( $sql, array( $datos['anho'], $datos['mes'] ) );

            if( !empty( $rs ) ){

                $resultado['total'] = 0;

                foreach( $rs as $fila ){

                    $resultado['suscriptos_acumulados'][$fila['dia']] = $fila['suscriptos'];
                    $resultado['total'] += $fila['suscriptos'];
                }

                return $resultado;

            }else{

                return $resultado;
            }
        }
    }

    private function _setupRangoSeleccion( $anho_seleccionado, $mes_seleccionado ) {

        $anho_inicio = $this->rango_seleccion[0]['anho'];
        $mes_inicio = (int)$this->rango_seleccion[0]['mes'];
        $this->logger->info('anho_inicio:[' . $anho_inicio . '] mes_inicio:[' . $mes_inicio . ']');

        $this->rango_seleccion[0]['selected'] = '';
        if($anho_inicio == $anho_seleccionado && $mes_inicio == $mes_seleccionado) {
            $this->rango_seleccion[0]['selected'] = 'selected';
        }

        $anho_actual = date('Y');
        $mes_actual = date('n');
        $this->logger->info('anho_actual:[' . $anho_actual . '] mes_actual:[' . $mes_actual . ']');

        if($anho_inicio == $anho_actual && $mes_inicio == $mes_actual) return;

        //return;

        $continuar = true;
        $loops = 0;
        while($continuar && $loops<30) {


            if($mes_inicio == 12) {

                $mes_inicio = 1;
                $mes = $mes_inicio;

                $anho_inicio++;
                $anho = $anho_inicio;

            } else {

                $mes_inicio++;
                $mes = $mes_inicio;

                $anho = $anho_inicio;
            }

            $this->rango_seleccion[] = array(
                'anho' => $anho,
                'mes' => $mes,
                'descripcion' => $anho . ' - ' . $this->meses[$mes-1],
                'selected' => (($anho == $anho_seleccionado && $mes == $mes_seleccionado) ? 'selected' : '')
            );


            if($anho == $anho_actual && $mes == $mes_actual) {
                $continuar = false;
            }

            $this->logger->info('continuar:[' . ($continuar ? 'SI' : 'NO') . ']');

            $loops++;
        }

        $this->logger->info('rango_seleccion:[' . print_r($this->rango_seleccion, true) . ']');
    }

    private function cargarNombresDiasDelMes( $anho, $mes ) {

        $cantidad_dias = date('t', mktime(0, 0, 0, $mes, 1, $anho));
        $nombre_dias_del_mes = array();
        for($i=1; $i<=$cantidad_dias; $i++) {
            $dia_semana =  date('w', mktime(0, 0, 0, $mes, $i, $anho));
            $nombre_dias_del_mes[$i] = array(
                'dia_semana' => $dia_semana,
                'nombre_dia' => $this->dias_semana[$dia_semana]
            );
        }

        return $nombre_dias_del_mes;
    }

    private function _cargarSuscriptosNumero( $numero, $anho, $mes ) {

        $namespace = new Zend_Session_Namespace("entermovil-tvchat-reportes");

        $resultado = array();

        $bootstrap = $this->getInvokeArg('bootstrap');
        $options = $bootstrap->getOptions();

        $db = Zend_Db::factory(new Zend_Config($options['resources']['db']));
        $db->getConnection();

        $sql = 'select T1.*, count(T2.id_suscripto)::integer as total_suscriptos from (
            select IP.alias, IP.id_promocion, IP.id_carrier
            from info_promociones IP
            where id_promocion in ('. $namespace->accesos['id_promociones'] .') and numero = ?
            group by 1,2,3
        ) T1 join (
            select IP.id_promocion, IP.id_carrier, IP.cel, IP.id_suscripto
            from promosuscripcion.suscriptos IP
            where id_promocion in ('. $namespace->accesos['id_promociones'] .')
        ) T2 on T1.id_promocion = T2.id_promocion and T1.id_carrier = T2.id_carrier
        group by 1,2,3 order by 1,2 desc';

        $rs_suscriptos = $db->fetchAll($sql, array($numero));
        $promociones = array();
        $suscriptos_x_promo = array();
        $total_suscriptos = 0;
        $total_general = array();

        foreach($rs_suscriptos as $fila) {

            $promociones[] = $fila;
            $total_suscriptos += $fila['total_suscriptos'];
        }

        $resultado[$numero]['total_suscriptos'] = $total_suscriptos;

        $resultado[$numero]['promociones'] = $promociones;

        $sql = 'select extract(day from ts_local)::integer as dia_mes, extract(dow from ts_local)::integer as dia_semana, ts_local::date as fecha, id_promocion, id_carrier, accion, count(*) as total
        from  promosuscripcion.log_suscriptos
        where id_carrier in(1,2) and extract(year from ts_local)::integer = ? and extract(month from ts_local)::integer = ? and id_promocion in(select id_promocion from info_promociones where numero = ? and id_promocion in(88,89,94,95,96) group by 1 order by 1) and accion = \'ALTA\'
        group by 1,2,3,4,5,6
        union
        select extract(day from ts_local)::integer as dia_mes, extract(dow from ts_local)::integer as dia_semana, ts_local::date as fecha, id_promocion, id_carrier, accion, count(*) as total
        from promosuscripcion.log_suscriptos
        where id_carrier in(1,2) and extract(year from ts_local)::integer = ? and extract(month from ts_local)::integer = ? and id_promocion in(select id_promocion from info_promociones where numero = ? and id_promocion in(88,89,94,95,96) group by 1 order by 1) and accion = \'BAJA\'
        group by 1,2,3,4,5,6
        order by 1,2,3,4,5';

        $rs_suscriptos = $db->fetchAll($sql, array($anho, $mes, $numero, $anho, $mes, $numero));

        $altas_bajas_x_mes = array(
            'TOTALES_MES' => array(
                'TOTAL_ALTA' => 0,
                'TOTAL_BAJA' => 0,
                'datos' => array()
            )
        );

        $cantidad_dias = date('t', mktime(0, 0, 0, $mes, 1, $anho));
        for($i=1; $i<=$cantidad_dias; $i++) {
            $altas_bajas_x_mes['TOTALES_MES']['datos'][$i] = array(
                'ALTA' => 0, 'BAJA' => 0
            );
        }

        foreach($rs_suscriptos as $fila) {

            if(!isset($altas_bajas_x_mes[$fila['id_promocion']][$fila['id_carrier']])) {

                $altas_bajas_x_mes[$fila['id_promocion']][$fila['id_carrier']] = array(
                    'TOTAL_ALTA' => 0,
                    'TOTAL_BAJA' => 0,
                    'datos' => array()
                );

                for($i=1; $i<=$cantidad_dias; $i++) {

                    $altas_bajas_x_mes[$fila['id_promocion']][$fila['id_carrier']]['datos'][$i] = array(
                        'ALTA' => 0, 'BAJA' => 0
                    );
                }
            }

            if(!isset($altas_bajas_x_mes[$fila['id_promocion']][$fila['id_carrier']]['datos'][$fila['dia_mes']])) {

                $altas_bajas_x_mes[$fila['id_promocion']][$fila['id_carrier']]['datos'][$fila['dia_mes']] = array(
                    'ALTA' => 0, 'BAJA' => 0
                );
            }

            $altas_bajas_x_mes[$fila['id_promocion']][$fila['id_carrier']]['datos'][$fila['dia_mes']][$fila['accion']] = $fila['total'];

            $altas_bajas_x_mes[$fila['id_promocion']][$fila['id_carrier']]['TOTAL_'.$fila['accion']] += $fila['total'];

            if(!isset($altas_bajas_x_mes['TOTALES_MES']['datos'][$fila['dia_mes']])) {

                $altas_bajas_x_mes['TOTALES_MES']['datos'][$fila['dia_mes']] = array(
                    'ALTA' => 0, 'BAJA' => 0
                );
            }

            $altas_bajas_x_mes['TOTALES_MES']['datos'][$fila['dia_mes']][$fila['accion']] += $fila['total'];
            $altas_bajas_x_mes['TOTALES_MES']['TOTAL_'.$fila['accion']] += $fila['total'];

        }

        $resultado[$numero]['altas_bajas_x_mes'] = $altas_bajas_x_mes;


        return $resultado;
    }

    private function _cargarSuscriptosNumeroChat( $numero, $anho, $mes ) {

        $namespace = new Zend_Session_Namespace("entermovil-tvchat-reportes");

        $resultado = array();

        $bootstrap = $this->getInvokeArg('bootstrap');
        $options = $bootstrap->getOptions();

        $db = Zend_Db::factory(new Zend_Config($options['resources']['db']));
        $db->getConnection();

        $sql = "select T1.*, count(T2.id_suscripto)::integer as total_suscriptos from (
            select IP.alias, IP.id_promocion, IP.id_carrier
            from info_promociones IP
            where id_promocion in (94,95,96) and numero = ?
            group by 1,2,3
        ) T1 join (
            select IP.id_promocion, IP.id_carrier, IP.cel, IP.id_suscripto
            from promosuscripcion.suscriptos IP
            where id_promocion in (94,95,96)
        ) T2 on T1.id_promocion = T2.id_promocion and T1.id_carrier = T2.id_carrier
        group by 1,2,3 order by 1,2 desc";

        $rs_suscriptos = $db->fetchAll($sql, array($numero));
        $promociones = array();
        $suscriptos_x_promo = array();
        $total_suscriptos = 0;
        $total_general = array();

        foreach($rs_suscriptos as $fila) {

            $promociones[] = $fila;
            $total_suscriptos += $fila['total_suscriptos'];
        }

        $resultado[$numero]['total_suscriptos'] = $total_suscriptos;

        $resultado[$numero]['promociones'] = $promociones;

        $sql = 'select extract(day from ts_local)::integer as dia_mes, extract(dow from ts_local)::integer as dia_semana, ts_local::date as fecha, id_promocion, id_carrier, accion, count(*) as total
        from  promosuscripcion.log_suscriptos
        where id_carrier in(1,2) and extract(year from ts_local)::integer = ? and extract(month from ts_local)::integer = ? and id_promocion in(select id_promocion from info_promociones where numero = ? and id_promocion in(94,95,96) group by 1 order by 1) and accion = \'ALTA\'
        group by 1,2,3,4,5,6
        union
        select extract(day from ts_local)::integer as dia_mes, extract(dow from ts_local)::integer as dia_semana, ts_local::date as fecha, id_promocion, id_carrier, accion, count(*) as total
        from promosuscripcion.log_suscriptos
        where id_carrier in(1,2) and extract(year from ts_local)::integer = ? and extract(month from ts_local)::integer = ? and id_promocion in(select id_promocion from info_promociones where numero = ? and id_promocion in(94,95,96) group by 1 order by 1) and accion = \'BAJA\'
        group by 1,2,3,4,5,6
        order by 1,2,3,4,5';

        $rs_suscriptos = $db->fetchAll($sql, array($anho, $mes, $numero, $anho, $mes, $numero));

        $altas_bajas_x_mes = array(
            'TOTALES_MES' => array(
                'TOTAL_ALTA' => 0,
                'TOTAL_BAJA' => 0,
                'datos' => array()
            )
        );

        $cantidad_dias = date('t', mktime(0, 0, 0, $mes, 1, $anho));
        for($i=1; $i<=$cantidad_dias; $i++) {
            $altas_bajas_x_mes['TOTALES_MES']['datos'][$i] = array(
                'ALTA' => 0, 'BAJA' => 0
            );
        }

        foreach($rs_suscriptos as $fila) {

            if(!isset($altas_bajas_x_mes[$fila['id_promocion']][$fila['id_carrier']])) {

                $altas_bajas_x_mes[$fila['id_promocion']][$fila['id_carrier']] = array(
                    'TOTAL_ALTA' => 0,
                    'TOTAL_BAJA' => 0,
                    'datos' => array()
                );

                for($i=1; $i<=$cantidad_dias; $i++) {

                    $altas_bajas_x_mes[$fila['id_promocion']][$fila['id_carrier']]['datos'][$i] = array(
                        'ALTA' => 0, 'BAJA' => 0
                    );
                }
            }

            if(!isset($altas_bajas_x_mes[$fila['id_promocion']][$fila['id_carrier']]['datos'][$fila['dia_mes']])) {

                $altas_bajas_x_mes[$fila['id_promocion']][$fila['id_carrier']]['datos'][$fila['dia_mes']] = array(
                    'ALTA' => 0, 'BAJA' => 0
                );
            }

            $altas_bajas_x_mes[$fila['id_promocion']][$fila['id_carrier']]['datos'][$fila['dia_mes']][$fila['accion']] = $fila['total'];

            $altas_bajas_x_mes[$fila['id_promocion']][$fila['id_carrier']]['TOTAL_'.$fila['accion']] += $fila['total'];

            if(!isset($altas_bajas_x_mes['TOTALES_MES']['datos'][$fila['dia_mes']])) {

                $altas_bajas_x_mes['TOTALES_MES']['datos'][$fila['dia_mes']] = array(
                    'ALTA' => 0, 'BAJA' => 0
                );
            }

            $altas_bajas_x_mes['TOTALES_MES']['datos'][$fila['dia_mes']][$fila['accion']] += $fila['total'];
            $altas_bajas_x_mes['TOTALES_MES']['TOTAL_'.$fila['accion']] += $fila['total'];

        }

        $resultado[$numero]['altas_bajas_x_mes'] = $altas_bajas_x_mes;


        return $resultado;
    }

    private function _cargarPromociones() {

        $namespace = new Zend_Session_Namespace("entermovil-tvchat-reportes");

        $servicios = array();

        $bootstrap = $this->getInvokeArg('bootstrap');
        $options = $bootstrap->getOptions();

        $db = Zend_Db::factory(new Zend_Config($options['resources']['db']));
        $db->getConnection();
        $sql = "SELECT numero, id_promocion, promocion, alias
                FROM info_promociones
                WHERE numero IN( ". $namespace->accesos['numeros'] .")
                and id_promocion in(". $namespace->accesos['id_promociones'] .")
                GROUP BY 1,2,3,4
                ORDER BY 1 desc,2";

        $rs_promociones = $db->fetchAll($sql);
        foreach($rs_promociones as $fila) {
            $servicios[] = $fila;
        }

        return $servicios;
    }

    private function _cargarSuscriptosPromocion($id_promocion, $anho, $mes) {

        $resultado = array();

        $bootstrap = $this->getInvokeArg('bootstrap');
        $options = $bootstrap->getOptions();

        $db = Zend_Db::factory(new Zend_Config($options['resources']['db']));
        $db->getConnection();

        $sql = "select id_promocion, extract(hour from ts_local)::integer as hora, extract(dow from ts_local)::integer as dia_semana, extract(day from ts_local)::integer as dia_mes, id_carrier, ts_local::date as fecha, accion, count(*) as total
                from promosuscripcion.log_suscriptos
                where id_promocion = ? and extract(year from ts_local)::integer = ? and extract(month from ts_local)::integer = ? and accion = 'ALTA'
                group by 1,2,3,4,5,6,7
                union
                select id_promocion, extract(hour from ts_local)::integer as hora, extract(dow from ts_local)::integer as dia_semana, extract(day from ts_local)::integer as dia_mes, id_carrier, ts_local::date as fecha, accion, count(*) as total
                from promosuscripcion.log_suscriptos
                where id_promocion = ? and extract(year from ts_local)::integer = ? and extract(month from ts_local)::integer = ? and accion = 'BAJA'
                group by 1,2,3,4,5,6,7
                order by 1,2,3,4,5,7";

        $rs_suscriptos = $db->fetchAll($sql, array($id_promocion, $anho, $mes, $id_promocion, $anho, $mes));

        if(count($rs_suscriptos) > 0) {

            $altas_bajas_promocion = array(
                'TOTALES' => array(
                    'TOTAL_ALTA' => 0,
                    'TOTAL_BAJA' => 0,
                    'datos' => array()
                )
            );
            $cantidad_dias = date('t', mktime(0, 0, 0, $mes, 1, $anho));
            for($i=1; $i<=$cantidad_dias; $i++) {
                $altas_bajas_promocion['TOTALES']['datos'][$i] = array(
                    'ALTA' => 0, 'BAJA' => 0
                );
            }

            foreach($rs_suscriptos as $fila) {

                $this->logger->info('fila -> dia:['. $fila['dia_mes'] .'] hora:[' . $fila['hora'] . '] id_carrier:[' . $fila['id_carrier'] . '] accion:[' . $fila['accion'] . ']');

                if(!isset($altas_bajas_promocion['TOTALES'][$fila['id_carrier']]['TOTAL_ALTA'])) {
                    $altas_bajas_promocion['TOTALES'][$fila['id_carrier']]['TOTAL_ALTA'] = 0;
                    $altas_bajas_promocion['TOTALES'][$fila['id_carrier']]['TOTAL_BAJA'] = 0;
                }

                if(!isset($altas_bajas_promocion[$fila['hora']][$fila['id_carrier']])) {

                    $altas_bajas_promocion[$fila['hora']][$fila['id_carrier']] = array(
                        'TOTAL_ALTA' => 0,
                        'TOTAL_BAJA' => 0,
                        'datos' => array()
                    );

                    for($i=1; $i<=$cantidad_dias; $i++) {
                        $altas_bajas_promocion[$fila['hora']][$fila['id_carrier']]['datos'][$i] = array(
                            'ALTA' => 0, 'BAJA' => 0
                        );
                    }
                }

                $altas_bajas_promocion[$fila['hora']][$fila['id_carrier']]['datos'][$fila['dia_mes']][$fila['accion']] = $fila['total'];

                $altas_bajas_promocion[$fila['hora']][$fila['id_carrier']]['TOTAL_'.$fila['accion']] += $fila['total'];

                if(!isset($altas_bajas_promocion['TOTALES']['datos'][$fila['dia_mes']])) {
                    $altas_bajas_promocion['TOTALES']['datos'][$fila['dia_mes']] = array(
                        'ALTA' => 0, 'BAJA' => 0
                    );
                }

                $altas_bajas_promocion['TOTALES']['datos'][$fila['dia_mes']][$fila['accion']] += $fila['total'];
                $altas_bajas_promocion['TOTALES']['TOTAL_'.$fila['accion']] += $fila['total'];

                if(!isset($altas_bajas_promocion['TOTALES'][$fila['id_carrier']]['datos'][$fila['dia_mes']])) {
                    $altas_bajas_promocion['TOTALES'][$fila['id_carrier']]['datos'][$fila['dia_mes']] = array(
                        'ALTA' => 0, 'BAJA' => 0
                    );
                }

                $altas_bajas_promocion['TOTALES'][$fila['id_carrier']]['datos'][$fila['dia_mes']][$fila['accion']] += $fila['total'];
                $altas_bajas_promocion['TOTALES'][$fila['id_carrier']]['TOTAL_'.$fila['accion']] += $fila['total'];
            }

            $resultado = $altas_bajas_promocion;
        }

        return $resultado;
    }

}

