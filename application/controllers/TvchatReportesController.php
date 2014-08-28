<?php

class TvchatReportesController extends Zend_Controller_Action{

    public $logger;
    var $usuarios = array(

        'daas' => array('clave' => 'daas', 'nombre' => 'DAAS'),
        'david' => array('clave' => 'david', 'nombre' => 'David Villalba')
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
                        $namespace->accesos = array(
                            'FULL'
                        );

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

        foreach($this->numeros as $numero) {

            $resultado = $this->_cargarSuscriptosNumero( $numero, $anho, $mes );
            //print_r($resultado);exit;
            $datos[$numero] = $resultado[$numero];

        }

        $this->view->numeros = $this->numeros;
        $this->view->datos = $datos;
        $this->view->carriers = $this->carriers;
    }

    public function reporteXHoraAction() {

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

        $this->view->promociones = array(

            'TVCHAT' => 88,
            'JUGAR' => 89
        );

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

            //print_r($datos_mostrar);exit;

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

    public function pruebaAction(){

    }

    private function _consulta( $accion, $datos = null ){

        $bootstrap = $this->getInvokeArg('bootstrap');
        $options = $bootstrap->getOptions();

        $db = Zend_Db::factory(new Zend_Config($options['resources']['db']));
        $db->getConnection();
        $resultado = null;

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
                and id_promocion in(88,89) and accion = '"."ALTA"."'
                union
                select *
                from promosuscripcion.log_suscriptos PL
                where id_carrier in(1,2) and ts_local::date = ?
                and id_promocion in(88,89) and accion = '"."BAJA"."'
            ) T1 join (
                select IP.numero, IP.id_promocion, IP.alias, IP.id_carrier
                from info_promociones IP
                where IP.id_promocion in (88,89)
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
                select T1.*, count(*)::integer as total from (
                    select PL.id_promocion, extract(minute from PL.ts_local)::integer as minuto, PL.accion
                    from promosuscripcion.log_suscriptos PL
                    where id_carrier in(1,2) and ts_local::date = ?
                    and id_promocion = ? and accion = '"."ALTA"."'
                    and extract(hour from ts_local)::integer = ?
                    union
                    select PL.id_promocion, extract(minute from PL.ts_local)::integer as minuto, PL.accion
                    from promosuscripcion.log_suscriptos PL
                    where id_carrier in(1,2) and ts_local::date = ?
                    and id_promocion = ? and accion = '"."BAJA"."'
                    and extract(hour from ts_local)::integer = ?
                ) T1 group by 1,2,3
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

        $resultado = array();

        $bootstrap = $this->getInvokeArg('bootstrap');
        $options = $bootstrap->getOptions();

        $db = Zend_Db::factory(new Zend_Config($options['resources']['db']));
        $db->getConnection();

        $sql = 'select T1.*, count(T2.id_suscripto)::integer as total_suscriptos from (
            select IP.alias, IP.id_promocion, IP.id_carrier
            from info_promociones IP
            where id_promocion in (88,89) and numero = ?
            group by 1,2,3
        ) T1 join (
            select IP.id_promocion, IP.id_carrier, IP.cel, IP.id_suscripto
            from promosuscripcion.suscriptos IP
            where id_promocion in (88,89)
        ) T2 on T1.id_promocion = T2.id_promocion and T1.id_carrier = T2.id_carrier
        group by 1,2,3 order by 3,2 desc';

        $rs_suscriptos = $db->fetchAll($sql, array($numero));
        $promociones = array();
        $suscriptos_x_promo = array();
        $total_suscriptos = 0;

        foreach($rs_suscriptos as $fila) {

            $promociones[] = $fila;
            $total_suscriptos += $fila['total_suscriptos'];
        }

        $resultado[$numero]['total_suscriptos'] = $total_suscriptos;

        $resultado[$numero]['promociones'] = $promociones;

        $sql = 'select extract(day from ts_local)::integer as dia_mes, extract(dow from ts_local)::integer as dia_semana, ts_local::date as fecha, id_promocion, id_carrier, accion, count(*) as total
        from  promosuscripcion.log_suscriptos
        where id_carrier in(1,2) and extract(year from ts_local)::integer = ? and extract(month from ts_local)::integer = ? and id_promocion in(select id_promocion from info_promociones where numero = ? and id_promocion in(88,89) group by 1 order by 1) and accion = \'ALTA\'
        group by 1,2,3,4,5,6
        union
        select extract(day from ts_local)::integer as dia_mes, extract(dow from ts_local)::integer as dia_semana, ts_local::date as fecha, id_promocion, id_carrier, accion, count(*) as total
        from promosuscripcion.log_suscriptos
        where id_carrier in(1,2) and extract(year from ts_local)::integer = ? and extract(month from ts_local)::integer = ? and id_promocion in(select id_promocion from info_promociones where numero = ? and id_promocion in(88,89) group by 1 order by 1) and accion = \'BAJA\'
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

}

