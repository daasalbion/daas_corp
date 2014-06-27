<?php

class ReporteWapController extends Zend_Controller_Action
{
    //variables globales
    var $meses = array(
        'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Setiembre', 'Octubre', 'Noviembre', 'Diciembre'
    );
    var $dias_semana = array(
        'Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'
    );
    var $carriers = array(
        1 => 'PERSONAL',
        2 => 'TIGO',
        5 => 'TIGO_GT'
    );
    var $promociones = array(
        'PORTAL_PY_PORTAL' => '72',
        'PORTAL_GT_PORTAL' => '58',
        'PORTAL_PY_PATRON' => '77'
    );
    var $categorias = array(
        'image/jpeg' => 'Imagenes',
        'video/3gpp' => 'Videos',
        'audio/mpeg' => 'Audios',
    );
    var $log;
    var $numeros;

    public function init()
    {
        /* Initialize action controller here */
        $this->log = $this->getLog();
        if($this->log) {
            $this->log->info('ReporteWapController -> Request');
        }

        $headLinkContainer = $this->view->headLink()->getContainer();
        if(isset($headLinkContainer[0])) {
            unset($headLinkContainer[0]);//base.css
        }
        $this->view->headLink()->setStylesheet('/css/reportes_base.css', 'screen');

        $headScriptContainer = $this->view->headScript()->getContainer();
        if(count($headScriptContainer) > 1 && $headScriptContainer[1]) {
            unset($headScriptContainer[1]);//base.js
        }

        $this->_helper->_layout->setLayout('reporte-layout');

        $namespace = new Zend_Session_Namespace("entermovil");
        if(!isset($namespace->usuario) || empty($namespace->usuario)) {
            $this->_redirect('/acceso');
        }
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

    public function indexAction(){

    }

    public function contenidosMasDescargadosAction(){

        $this->view->paises = $this->carriers;
    }

    public function suscriptosAction(){

        $this->view->headLink()->appendStylesheet('/css/reportes_wap.css', 'screen');
        //agregado para probar
        $this->view->headScript()->appendFile('http://code.jquery.com/ui/1.10.0/jquery-ui.js', 'text/javascript');
        $this->view->headLink()->appendStylesheet('http://code.jquery.com/ui/1.10.0/themes/base/jquery-ui.css');
        $this->view->headScript()->appendFile('/js/reportes_pautas.js', 'text/javascript');
        $this->view->promociones = $this->promociones;

        $id_promocion = $this->_getParam( 'id-promocion', null );

        if( !is_null( $id_promocion ) ){

            $datos = array(

                'id_promocion' => $id_promocion,
            );
            $suscriptos = $this->_consulta( 'GET_SUSCRIPTOS_POR_PROMOCION', $datos );
            if( !is_null( $suscriptos ) ){

                $this->view->suscriptos = $suscriptos;

                $totales = array();
                $total_general = 0;

                foreach( $suscriptos as $id_carrier => $datos_nivel ){

                    $suma_suscriptos_id_carrier = 0;
                    foreach( $datos_nivel as $nivel => $suscriptos ){

                         $suma_suscriptos_id_carrier = $suma_suscriptos_id_carrier + $suscriptos['suscriptos'];
                    }
                    $totales[$id_carrier] = $suma_suscriptos_id_carrier;
                    $total_general = $total_general + $suma_suscriptos_id_carrier;
                }
                $this->view->promocion = $id_promocion;
                $this->view->totales = $totales;
                $this->view->total_general = $total_general;

                $contenidos_mas_descargados = $this->_consulta( 'GET_CONTENIDOS_MAS_DESCARGADOS', $datos );

                if( !is_null( $contenidos_mas_descargados ) ){

                    $this->view->contenidos_mas_descargados = $contenidos_mas_descargados;
                    //print_r($contenidos_mas_descargados);

                }else{

                    $this->view->sms = 'No existen contenidos';
                }

            }else{

                $this->view->mensaje = array(

                    'No existen contenidos'
                );
                //mostrar vista apropiada
                $this->_helper->viewRenderer('mensaje-error');

                return;
            }
        }else{

            //por defecto es 72
            $id_promocion = '72';

            $datos = array(

                'id_promocion' => $id_promocion,
            );

            $suscriptos = $this->_consulta( 'GET_SUSCRIPTOS_POR_PROMOCION', $datos );
            if( !is_null( $suscriptos ) ){

                $totales = array();
                $total_general = 0;

                foreach( $suscriptos as $id_carrier => $datos_nivel ){

                    $suma_suscriptos_id_carrier = 0;
                    foreach( $datos_nivel as $nivel => $suscriptos ){

                        $suma_suscriptos_id_carrier = $suma_suscriptos_id_carrier + $suscriptos['suscriptos'];
                    }
                    $totales[$id_carrier] = $suma_suscriptos_id_carrier;
                    $total_general = $total_general + $suma_suscriptos_id_carrier;
                }

                $this->view->suscriptos = $suscriptos;
                $this->view->promocion = $id_promocion;
                $this->view->totales = $totales;
                $this->view->total_general = $total_general;

            }else{

                $this->view->mensaje = array(

                    'No existen contenidos'
                );
                //mostrar vista apropiada
                $this->_helper->viewRenderer('mensaje-error');

                return;
            }
        }
    }

    private function _consulta( $accion, $datos ){

        /*$bootstrap = $this->getInvokeArg('bootstrap');
        $options = $bootstrap->getOptions();

        $db = Zend_Db::factory(new Zend_Config($options['resources']['db']));
        $db->getConnection();*/
        $config = new Zend_Config(array(
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
        $db->getConnection();

        if( $accion == 'GET_SUSCRIPTOS_POR_PROMOCION' ){

            $sql = "select count(*) as suscriptos, t1.id_carrier, t2.nivel from promosuscripcion.suscriptos as t1, wap.usuarios as t2
                    where t1.id_promocion = ? and t1.cel = t2.cel group by t1.id_carrier, t2.nivel";
            $rs = $db->fetchAll( $sql, array( $datos['id_promocion'] ) );

            if( !empty( $rs ) ){

                $resultado = array();
                foreach( $rs as $fila ){

                    $resultado[$this->carriers[$fila['id_carrier']]][$fila['nivel']] = (array)$fila;
                }

                return $resultado;
            }else{

                return null;
            }
        }
        if( $accion == 'GET_CONTENIDOS_MAS_DESCARGADOS' ){

            $sql = "select id_categoria, id_contenido, nombre_contenido, descargas, id_promocion, nivel, tipo from wap.contenidos where id_promocion = ? order by descargas desc limit 10";
            $rs = $db->fetchAll( $sql, array( $datos['id_promocion'] ) );
            if( !empty( $rs ) ){

                $resultado = array();
                foreach( $rs as $fila ){

                    $fila['tipo'] = $this->categorias[$fila['tipo']];
                    //$resultado[$fila['nivel']][] = (array)$fila;
                    $resultado[] = (array)$fila;
                }

                return $resultado;

            }else{

                return null;
            }
        }
    }

    public function cobrosAction() {

        $this->view->headScript()->appendFile('/js/reportes_cobros.js', 'text/javascript');
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

        $this->view->costos_x_promocion = $this->_cargarCostosPorPromocion($anho, $mes);

        $this->log->info('costos_x_promocion:[' . print_r($this->view->costos_x_promocion, true) . ']');

        $datos = array();
        foreach($this->numeros as $numero) {
            $resultado = $this->_cargarCobrosNumero($numero, $anho, $mes);
            //$this->log->info('Numero:['.$numero.'] resultado:[' . print_r($resultado, true) . ']');
            $datos[$numero] = $resultado[$numero];

            if(!isset($datos['TOTALES'])) {
                $datos['TOTALES']['cobros_x_mes'] = array(
                    'TOTALES_MES' => array(
                        'total_suscriptos' => 0,
                        'total_cobros' => 0,
                        'total_bruto' => 0,
                        'total_bruto_cliente' => 0,
                        'total_bruto_otros' => 0,
                        'datos_cobros' => array()
                    )
                );
                for($i=1; $i<=$this->view->cantidad_dias; $i++) {
                    $datos['TOTALES']['cobros_x_mes']['TOTALES_MES']['datos_cobros'][$i] = array(
                        'total_cobros' => 0,
                        'total_bruto' => 0,
                        'total_bruto_cliente' => 0,
                        'total_bruto_otros' => 0
                    );
                }
            }

            $datos['TOTALES']['cobros_x_mes']['TOTALES_MES']['total_suscriptos'] += $datos[$numero]['cobros_x_mes']['TOTALES_MES']['TOTAL']['total_suscriptos'];
            $datos['TOTALES']['cobros_x_mes']['TOTALES_MES']['total_cobros'] += $datos[$numero]['cobros_x_mes']['TOTALES_MES']['TOTAL']['total_cobros'];
            $datos['TOTALES']['cobros_x_mes']['TOTALES_MES']['total_bruto'] += $datos[$numero]['cobros_x_mes']['TOTALES_MES']['TOTAL']['total_bruto'];
            $datos['TOTALES']['cobros_x_mes']['TOTALES_MES']['total_bruto_cliente'] += $datos[$numero]['cobros_x_mes']['TOTALES_MES']['TOTAL']['total_bruto_cliente'];
            $datos['TOTALES']['cobros_x_mes']['TOTALES_MES']['total_bruto_otros'] += $datos[$numero]['cobros_x_mes']['TOTALES_MES']['TOTAL']['total_bruto_otros'];

            for($i=1; $i<=$this->view->cantidad_dias; $i++) {

                if(!isset($datos['TOTALES']['cobros_x_mes']['TOTALES_MES']['datos_cobros'][$i])) {
                    $datos['TOTALES']['cobros_x_mes']['TOTALES_MES']['datos_cobros'][$i] = array(
                        'total_cobros' => 0,
                        'total_bruto' => 0,
                        'total_bruto_cliente' => 0,
                        'total_bruto_otros' => 0
                    );
                }
                $datos['TOTALES']['cobros_x_mes']['TOTALES_MES']['datos_cobros'][$i]['total_cobros'] += isset($datos[$numero]['cobros_x_mes']['TOTALES_MES']['TOTAL']['datos_cobros'][$i]['total_cobros']) ? $datos[$numero]['cobros_x_mes']['TOTALES_MES']['TOTAL']['datos_cobros'][$i]['total_cobros'] : 0;
                $datos['TOTALES']['cobros_x_mes']['TOTALES_MES']['datos_cobros'][$i]['total_bruto'] += isset($datos[$numero]['cobros_x_mes']['TOTALES_MES']['TOTAL']['datos_cobros'][$i]['total_bruto']) ? $datos[$numero]['cobros_x_mes']['TOTALES_MES']['TOTAL']['datos_cobros'][$i]['total_bruto'] : 0;
                $datos['TOTALES']['cobros_x_mes']['TOTALES_MES']['datos_cobros'][$i]['total_bruto_cliente'] += isset($datos[$numero]['cobros_x_mes']['TOTALES_MES']['TOTAL']['datos_cobros'][$i]['total_bruto_cliente']) ? $datos[$numero]['cobros_x_mes']['TOTALES_MES']['TOTAL']['datos_cobros'][$i]['total_bruto_cliente'] : 0;
                $datos['TOTALES']['cobros_x_mes']['TOTALES_MES']['datos_cobros'][$i]['total_bruto_otros'] += isset($datos[$numero]['cobros_x_mes']['TOTALES_MES']['TOTAL']['datos_cobros'][$i]['total_bruto_otros']) ? $datos[$numero]['cobros_x_mes']['TOTALES_MES']['TOTAL']['datos_cobros'][$i]['total_bruto_otros'] : 0;
            }
        }
        $this->log->info('Datos:[' . print_r($datos, true) . ']');

        $this->view->numeros = $this->numeros;
        $this->view->datos = $datos;
        $this->view->carriers = $this->carriers;

    }

    public function suscriptosPorHoraAction() {

        $this->view->headScript()->appendFile('/js/reportes_suscriptos_por_hora.js', 'text/javascript');
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
                        case 7:
                        case 14:
                        case 16:
                        case 17:
                        case 18:
                        case 19:
                        case 20:
                        case 22:
                        case 23:
                        case 24:
                        case 25:
                        case 26:
                        case 27:
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

            $this->log->info('datos:[' . print_r($datos, true) . ']');

        } else {

            //
        }

    }

    public function salirAction() {

        $this->_forward('logout', 'auth');
    }

}

?>