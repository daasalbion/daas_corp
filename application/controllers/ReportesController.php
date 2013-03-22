<?php

class ReportesController extends Zend_Controller_Action
{


    var $meses = array(
        'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Setiembre', 'Octubre', 'Noviembre', 'Diciembre'
    );

    var $dias_semana = array(
        'Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'
    );

    var $rango_seleccion = array(
        array('anho' => 2011, 'mes' => 11, 'descripcion' => '2011 - Noviembre')
    );

    var $carriers = array(
        1 => 'PERSONAL',
        2 => 'TIGO'
    );

    var $log;
    var $numeros;
    public function init()
    {
        /* Initialize action controller here */
        $this->log = $this->getLog();
        if($this->log) {
            $this->log->info('ReportesController -> Request');
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
        //$this->view->headScript()->appendFile('/js/reportes_base.js', 'text/javascript');

        $this->_helper->_layout->setLayout('reporte-layout');
        //$this->_helper->viewRenderer->setNoRender(true);

        $namespace = new Zend_Session_Namespace("entermovil");
        if(!isset($namespace->usuario) || empty($namespace->usuario)) {
            $this->_redirect('/acceso');
        }

        define('PERSONAL', 1);
        define('TIGO', 2);
        
        $this->numeros = array('6767', '35500');
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

    private function _setupRangoSeleccion($anho_seleccionado, $mes_seleccionado) {

        $anho_inicio = $this->rango_seleccion[0]['anho'];
        $mes_inicio = (int)$this->rango_seleccion[0]['mes'];
        $this->log->info('anho_inicio:[' . $anho_inicio . '] mes_inicio:[' . $mes_inicio . ']');

        $this->rango_seleccion[0]['selected'] = '';
        if($anho_inicio == $anho_seleccionado && $mes_inicio == $mes_seleccionado) {
            $this->rango_seleccion[0]['selected'] = 'selected';
        }

        $anho_actual = date('Y');
        $mes_actual = date('n');
        $this->log->info('anho_actual:[' . $anho_actual . '] mes_actual:[' . $mes_actual . ']');

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

            $this->log->info('continuar:[' . ($continuar ? 'SI' : 'NO') . ']');

            $loops++;
        }

        //$this->log->info('rango_seleccion:[' . print_r($this->rango_seleccion, true) . ']');
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
    }

    private function _cargarFilasPautas( $fecha_completa ) {

        $resultado = array();

        $fecha = substr( $fecha_completa, 0, 7 );

        $bootstrap = $this->getInvokeArg('bootstrap');
        $options = $bootstrap->getOptions();

        $db = Zend_Db::factory(new Zend_Config($options['resources']['db']));
        $db->getConnection();

        /*
         * 17 INGLES
         * 22 CUERNOS
         * 26 SALUD
         * 37 SANTO
         * 32 PAREJA
         *
         * 20 USA
         * 23 VENADO
         * 27 VIDA
         * 38 ORAR
         * 31 SEXO
         */

        $sql = "select S1.*, S2.suscriptos, S3.cantidad as cantidad_hoy
                from (
                    select S.id_promocion, count(S.*)::integer as suscriptos
                    from promosuscripcion.suscriptos S
                    where S.id_promocion in(17,22,26,37,32, 20,23,27,38,31) and S.id_carrier in(1,2)
                    group by 1 order by 1
                ) S2 left join (
                    select 'SNT'::varchar(20) as canal, IP.alias, LS.id_promocion, LS.accion, count(LS.*)::integer as cantidad
                    from promosuscripcion.log_suscriptos LS
                    left join (select id_promocion, alias from info_promociones group by 1,2 order by 1,2) IP on IP.id_promocion = LS.id_promocion
                    where LS.ts_local::date between '".$fecha."-01'::date and ('".$fecha."-01'::date + interval '1 month' - interval '1 day')
                    and LS.id_promocion in(17,22,26,37,32) and LS.id_carrier in (1,2)
                    group by 1,2,3,4
                    union
                    select 'TELEFUTURO'::varchar(20) as canal, IP.alias, LS.id_promocion, LS.accion, count(LS.*)::integer as cantidad
                    from promosuscripcion.log_suscriptos LS
                    left join (select id_promocion, alias from info_promociones group by 1,2 order by 1,2) IP on IP.id_promocion = LS.id_promocion
                    where LS.ts_local::date between '".$fecha."-01'::date and ('".$fecha."-01'::date + interval '1 month' - interval '1 day')
                    and LS.id_promocion in(20,23,27,38,31) and LS.id_carrier in (1,2)
                    group by 1,2,3,4
                    order by 1,2,3

                ) S1 on S1.id_promocion = S2.id_promocion
                left join (
                    select 'SNT'::varchar(20) as canal, IP.alias, LS.id_promocion, LS.accion, count(LS.*)::integer as cantidad
                    from promosuscripcion.log_suscriptos LS
                    left join (select id_promocion, alias from info_promociones group by 1,2 order by 1,2) IP on IP.id_promocion = LS.id_promocion
                    where LS.ts_local::date = '".$fecha_completa."'::date
                    and LS.id_promocion in(17,22,26,37,32) and LS.id_carrier in (1,2)
                    group by 1,2,3,4
                    union
                    select 'TELEFUTURO'::varchar(20) as canal, IP.alias, LS.id_promocion, LS.accion, count(LS.*)::integer as cantidad
                    from promosuscripcion.log_suscriptos LS
                    left join (select id_promocion, alias from info_promociones group by 1,2 order by 1,2) IP on IP.id_promocion = LS.id_promocion
                    where LS.ts_local::date = '".$fecha_completa."'::date
                    and LS.id_promocion in(20,23,27,38,31) and LS.id_carrier in (1,2)
                    group by 1,2,3,4
                    order by 1,2,3
                ) S3 on S3.id_promocion = S1.id_promocion and S3.accion = S1.accion
                where S1.id_promocion is not null
                order by 1,2,3";

        $rs = $db->fetchAll($sql);

        foreach($rs as $fila) {
            $resultado[] = (array)$fila;
        }

        return $resultado;
    }

    private function _cargarFilasCobrosPautas($anho, $mes) {

        $resultado = array();

        $bootstrap = $this->getInvokeArg('bootstrap');
        $options = $bootstrap->getOptions();

        $db = Zend_Db::factory(new Zend_Config($options['resources']['db']));
        $db->getConnection();

        $sql = "select S4.*, S5.total_cobros_hoy, S5.total_bruto_gs_hoy, S5.total_neto_gs_hoy
                from (

                select S3.numero, S3.id_promocion, S3.costo_gs, sum(S3.total_cobros)::integer as total_cobros, sum(S3.total_bruto_gs)::integer as total_bruto_gs, sum(S3.total_neto_gs)::integer as total_neto_gs
                from (
                select S2.*, (S2.total_bruto_gs * S2.porcentaje_proveedor)::integer as total_neto_gs
                from (
                select S1.*, IP.costo_gs, (S1.total_cobros * IP.costo_gs)::integer as total_bruto_gs
                from (
                    select RM.numero, RM.fecha, RM.id_carrier, RM.id_promocion, RS.porcentaje_proveedor, sum(RM.total_cobros)::integer as total_cobros
                    from reporte_mensual_cobros(?, ?) RM
                    left join revenue_share RS on RS.id_carrier = RM.id_carrier and RS.numero = RM.numero
                    where RM.id_carrier IN(1,2) and RM.numero = '6767' and RM.id_promocion IN(17,22,26,37,32, 20,23,27,38,31)
                    group by 1,2,3,4,5
                    order by 1,2,3,4
                ) S1 left join info_promociones IP on IP.id_promocion = S1.id_promocion and IP.id_carrier = S1.id_carrier
                ) S2
                ) S3 group by 1,2,3 order by 1,2

                ) S4 left join (

                select S3.numero, S3.id_promocion, S3.costo_gs, sum(S3.total_cobros)::integer as total_cobros_hoy, sum(S3.total_bruto_gs)::integer as total_bruto_gs_hoy, sum(S3.total_neto_gs)::integer as total_neto_gs_hoy
                from (
                select S2.*, (S2.total_bruto_gs * S2.porcentaje_proveedor)::integer as total_neto_gs
                from (
                select S1.*, IP.costo_gs, (S1.total_cobros * IP.costo_gs)::integer as total_bruto_gs
                from (
                    select RM.numero, RM.fecha, RM.id_carrier, RM.id_promocion, RS.porcentaje_proveedor, sum(RM.total_cobros)::integer as total_cobros
                    from reporte_mensual_cobros(?, ?) RM
                    left join revenue_share RS on RS.id_carrier = RM.id_carrier and RS.numero = RM.numero
                    where RM.id_carrier IN(1,2) and RM.numero = '6767' and RM.id_promocion IN(17,22,26,37,32, 20,23,27,38,31) and RM.fecha = current_date
                    group by 1,2,3,4,5
                    order by 1,2,3,4
                ) S1 left join info_promociones IP on IP.id_promocion = S1.id_promocion and IP.id_carrier = S1.id_carrier
                ) S2
                ) S3 group by 1,2,3 order by 1,2

                ) S5 on S5.numero = S4.numero and S5.id_promocion = S4.id_promocion";

        $rs = $db->fetchAll($sql, array($anho, $mes, $anho, $mes));

        foreach($rs as $fila) {
            $fila = (array)$fila;
            $resultado[$fila['id_promocion']] = $fila;
        }

        return $resultado;
    }

    public function pautasAction(){

        $this->view->headLink()->appendStylesheet('/css/reportes_pautas.css', 'screen');
        //agregado para probar
        $this->view->headScript()->appendFile('http://code.jquery.com/ui/1.10.0/jquery-ui.js', 'text/javascript');
        $this->view->headLink()->appendStylesheet('http://code.jquery.com/ui/1.10.0/themes/base/jquery-ui.css');
        $this->view->headScript()->appendFile('/js/reportes_pautas.js', 'text/javascript');

        //despliego el titulo
        $this->view->headTitle()->append('Control TV');

        //obtengo el parametro fecha
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

        $this->_setupRangoSeleccion($anho, $mes);

        $this->view->nombre_mes = $this->meses[$mes-1];

        $this->view->cantidad_dias = date('t', mktime(0, 0, 0, $mes, 1, $anho));


        $this->view->anho = $anho;
        $this->view->mes = $mes;

        $this->view->dia_hoy = date('j');

        $this->view->dias_semana = $this->dias_semana;

        $this->view->nombres_dias_del_mes = $this->cargarNombresDiasDelMes($anho, $mes);

        $this->view->rango_seleccion = $this->rango_seleccion;



        //array para verificar alias que no se existian anteriormente
        $servicios = array(

            'CUERNOS' => 'SNT',
            'INGLES' => 'SNT',
            'SALUD' => 'SNT',
            'PAREJA' => 'SNT',
            'SANTO' => 'SNT',

            'USA' => 'TELEFUTURO',
            'VENADO' => 'TELEFUTURO',
            'VIDA' => 'TELEFUTURO',
            'SEXO' => 'TELEFUTURO',
            'ORAR' => 'TELEFUTURO',

        );


        /*agregado*/
        $canales = array(
            'SNT'=>array(
                'descripcion' => 'Reporte Canal 9 - SNT',
                'datos' => array(),
                'totales'=> array(
                    'TOTAL_ALTA_HOY' => 0,
                    'TOTAL_BAJA_HOY' => 0,
                    'TOTAL_COBROS_HOY' => 0,
                    'TOTAL_NETO_HOY' => 0,
                    'TOTAL_ALTA' => 0,
                    'TOTAL_BAJA' => 0,
                    'TOTAL_COBROS' => 0,
                    'TOTAL_NETO' => 0,
                    'TOTAL_SUSCRIPTOS' => 0
                ),
                'css_titulo' => "fondo_titulo_SNT"
            ),
            'TELEFUTURO' => array(
                'descripcion' => 'Reporte Canal 4 - Telefuturo',
                'datos'=> array(),
                'totales'=> array(
                    'TOTAL_ALTA_HOY' => 0,
                    'TOTAL_BAJA_HOY' => 0,
                    'TOTAL_COBROS_HOY' => 0,
                    'TOTAL_NETO_HOY' => 0,
                    'TOTAL_ALTA' => 0,
                    'TOTAL_BAJA' => 0,
                    'TOTAL_COBROS' => 0,
                    'TOTAL_NETO' => 0,
                    'TOTAL_SUSCRIPTOS' => 0
                ),
                'css_titulo' => 'fondo_titulo_TELEFUTURO'

            )
        );


        $filasPautas = $this->_cargarFilasPautas($fecha_seleccionada);


        $filasCobrosPautas = $this->_cargarFilasCobrosPautas($anho, $mes);
        //$this->log->info("filasCobrosPautas:[" . print_r($filasCobrosPautas, true) . ']');
        //print_r($filasCobrosPautas);
        //exit;

        $columna_suscriptos_ya_utilizada = array();

        foreach($filasPautas as $fila) {

            //agregado


            if( !isset($canales[$fila['canal']]['datos'][$fila['alias']] ) ) {

                if( isset( $servicios[ $fila[ 'alias' ] ] ) ){

                    unset( $servicios[ $fila[ 'alias' ] ] );



                    $canales[$fila['canal']]['datos'][$fila['alias']] = array(
                        'ALTA_HOY' => 0,
                        'BAJA_HOY' => 0,
                        'COBROS_HOY' => $filasCobrosPautas[$fila['id_promocion']]['total_cobros_hoy'],
                        'NETO_HOY' => $filasCobrosPautas[$fila['id_promocion']]['total_neto_gs_hoy'],
                        'ALTA' => 0,
                        'BAJA' => 0,
                        'COBROS' => $filasCobrosPautas[$fila['id_promocion']]['total_cobros'],
                        'NETO' => $filasCobrosPautas[$fila['id_promocion']]['total_neto_gs'],
                        'SUSCRIPTOS' => 0
                    );

                    $canales[$fila['canal']]['totales']['TOTAL_COBROS_HOY'] += $filasCobrosPautas[$fila['id_promocion']]['total_cobros_hoy'];
                    $canales[$fila['canal']]['totales']['TOTAL_NETO_HOY'] += $filasCobrosPautas[$fila['id_promocion']]['total_neto_gs_hoy'];

                    $canales[$fila['canal']]['totales']['TOTAL_COBROS'] += $filasCobrosPautas[$fila['id_promocion']]['total_cobros'];
                    $canales[$fila['canal']]['totales']['TOTAL_NETO'] += $filasCobrosPautas[$fila['id_promocion']]['total_neto_gs'];

                    $canales[$fila['canal']]['datos'][$fila['alias']][$fila['accion'].'_HOY'] = $fila['cantidad_hoy'];
                    $canales[$fila['canal']]['datos'][$fila['alias']][$fila['accion']] = $fila['cantidad'];

                    $canales[$fila['canal']]['datos'][$fila['alias']]['SUSCRIPTOS'] = $fila['suscriptos'];

                    $canales[$fila['canal']]['totales']['TOTAL_' . $fila['accion'] . '_HOY'] += $fila['cantidad_hoy'];
                    $canales[$fila['canal']]['totales']['TOTAL_' . $fila['accion']] += $fila['cantidad'];


                    if(!isset($columna_suscriptos_ya_utilizada[$fila['alias']])) {
                        $columna_suscriptos_ya_utilizada[$fila['alias']] = true;
                        $canales[$fila['canal']]['totales']['TOTAL_SUSCRIPTOS'] += $fila['suscriptos'];
                    }
                }

            }

        }

        //seteo los valores de servicios
        foreach( $servicios as $alias => $canal ){

            $canales[ $canal ][ 'datos' ][ $alias ] = array(

                'ALTA_HOY' => 0,
                'BAJA_HOY' => 0,
                'COBROS_HOY' => 0,
                'NETO_HOY' => 0,
                'ALTA' => 0,
                'BAJA' => 0,
                'COBROS' => 0,
                'NETO' => 0,
                'SUSCRIPTOS' => 0
            );

        }

        //$this->log->info("CANALES:[" . print_r($canales, true) . ']');
        $this->view->canales = $canales;

    }

    private function cargarNombresDiasDelMes($anho, $mes) {

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

    private function _cargarPromociones() {

        $servicios = array();

        $bootstrap = $this->getInvokeArg('bootstrap');
        $options = $bootstrap->getOptions();

        $db = Zend_Db::factory(new Zend_Config($options['resources']['db']));
        $db->getConnection();
        $sql = "SELECT numero, id_promocion, promocion, alias FROM info_promociones WHERE numero IN('6767', '35500') GROUP BY 1,2,3,4 ORDER BY 1 desc,2";
        $rs_promociones = $db->fetchAll($sql);
        foreach($rs_promociones as $fila) {
            $servicios[] = $fila;
        }

        return $servicios;
    }

    private function _cargarCostosPorPromocion($anho, $mes) {

        $costos_x_promocion = array();

        $bootstrap = $this->getInvokeArg('bootstrap');
        $options = $bootstrap->getOptions();

        $db = Zend_Db::factory(new Zend_Config($options['resources']['db']));
        $db->getConnection();

        $sql = "SELECT * FROM info_promociones WHERE numero IN('6767', '35500') and id_carrier in(1,2) ORDER BY numero, id_promocion";
        $rs_info_promociones = $db->fetchAll($sql);
        foreach($rs_info_promociones as $info_promocion) {

            if($info_promocion['numero'] == '6767') {//ALERTAS DE TEXTO

                if($info_promocion['id_carrier'] == 2) {//TIGO
                    $porcentaje_enter = 0.35;//35%
                    if(($anho == 2012 && $mes >= 11) || $anho > 2012) {
                        $porcentaje_enter = 0.25;//25%
                    }
                } else if($info_promocion['id_carrier'] == 1) {//PERSONAL
                    $porcentaje_enter = 0.30;//30%
                }

            } else if($info_promocion['numero'] == '35500') {//25
                if($info_promocion['id_carrier'] == 2) {//TIGO
                    $porcentaje_enter = 0.25;//25%
                } else if($info_promocion['id_carrier'] == 1) {//PERSONAL
                    $porcentaje_enter = 0.40;//40%
                }

            }
            $porcentaje_otros = 1 - $porcentaje_enter;

            $monto_entermovil = $info_promocion['costo_gs'] * $porcentaje_enter;
            $monto_otros = $info_promocion['costo_gs'] * $porcentaje_otros;

            $costos_x_promocion[$info_promocion['id_promocion']][$info_promocion['id_carrier']] = array(
                'costo_gs' => $info_promocion['costo_gs'],
                'monto_entermovil' => $monto_entermovil,
                'monto_otros' => $monto_otros
            );
        }


        return $costos_x_promocion;
    }

    //private function _cargarResumenCobros($idPais)

    private function _cargarCobrosNumero($numero, $anho, $mes) {

        $resultado = array();

        $bootstrap = $this->getInvokeArg('bootstrap');
        $options = $bootstrap->getOptions();

        $db = Zend_Db::factory(new Zend_Config($options['resources']['db']));
        $db->getConnection();

        $sql = 'select IP.id_promocion, IP.id_carrier, IP.alias, count(S.id_suscripto)::integer as total_suscriptos
                from info_promociones IP
                left join promosuscripcion.suscriptos S on S.id_promocion = IP.id_promocion and S.id_carrier = IP.id_carrier
                where IP.numero = ? and IP.id_promocion not in(9) and IP.id_carrier in(1,2)
                group by 1,2,3 order by 3,2';
        $rs_suscriptos = $db->fetchAll($sql, array($numero));
        $promociones = array();
        $promociones_agrupadas = array();
        $suscriptos_x_promo = array();
        foreach($rs_suscriptos as $fila) {
            $promociones[] = $fila;
            $suscriptos_x_promo[$fila['alias']][$fila['id_carrier']] = $fila['total_suscriptos'];

            if(!isset($promociones_agrupadas[$fila['alias']])) {
                $promociones_agrupadas[$fila['alias']] = array(
                    'id_promocion' => $fila['id_promocion'],
                    'lista_carriers' => array(),
                    'suscriptos' => array(
                        'TOTAL' => 0
                    )
                );
            }

            $promociones_agrupadas[$fila['alias']]['suscriptos'][$fila['id_carrier']] = $fila['total_suscriptos'];
            $promociones_agrupadas[$fila['alias']]['suscriptos']['TOTAL'] += $fila['total_suscriptos'];

            if(!isset($promociones_agrupadas[$fila['alias']]['lista_carriers'][$fila['id_carrier']])) {
                $promociones_agrupadas[$fila['alias']]['lista_carriers'][] = $fila['id_carrier'];
            }
        }

        //print_r($suscriptos_x_promo); exit;

        $resultado[$numero]['promociones'] = $promociones;
        $resultado[$numero]['promociones_agrupadas'] = $promociones_agrupadas;


        $calendario_envios = array();
        //$sql_envio = 'select dia from promosuscripcion.dias_envio where id_promocion = ? and id_carrier in(1,2)';
        $sql_envio = 'select id_carrier, dia from promosuscripcion.dias_envio where id_promocion = ? and id_carrier in(1,2) group by 1,2 order by 1,2';
        foreach($promociones as $promocion) {

            $rs_envios = $db->fetchAll($sql_envio, array($promocion['id_promocion']));
            $dias_envio = array();
            foreach($rs_envios as $fila) {
                $dias_envio[$fila['id_carrier']][] = $fila['dia'];
            }
            $calendario_envios[$promocion['id_promocion']] = $dias_envio;
        }

        $resultado[$numero]['calendario_envios'] = $calendario_envios;

        $this->log->info('numero:[' . $numero . '] calendario_envios:[' . print_r($calendario_envios, true) . ']');

        /*$sql = "select S2.*, ((S2.total_bruto * (CASE WHEN S2.numero = '6767' THEN 35 WHEN S2.numero = '35500' THEN 25 ELSE 25 END))/100)::integer as total_bruto_cliente, ((S2.total_bruto * (CASE WHEN S2.numero = '6767' THEN 65 WHEN S2.numero = '35500' THEN 75 ELSE 75 END))/100)::integer as total_bruto_otros
        from (
            select S1.*, IP.alias::varchar(10), (IP.costo_gs * S1.total_cobros)::integer as total_bruto
            from (
                select	RM.numero, RM.fecha, extract(day from RM.fecha)::integer as dia_mes, RM.dia_semana, RM.id_carrier, RM.id_promocion, sum(RM.total_cobros)::integer as total_cobros
                from reporte_mensual_cobros(?, ?) RM
                where RM.numero = ?
                group by 1,2,3,4,5,6
                order by 1,2,3,4,5,6
            ) S1 left join info_promociones IP on IP.id_promocion = S1.id_promocion and IP.id_carrier = S1.id_carrier
        ) S2";*/

        $carriers = array(
            1 => 'PERSONAL',
            2 => 'TIGO'
        );

        $cobros_x_mes = array(
            'TOTALES_MES' => array(
                'TOTAL' => array(
                    'total_suscriptos' => 0,
                    'total_cobros' => 0,
                    'total_bruto' => 0,
                    'total_bruto_cliente' => 0,
                    'total_bruto_otros' => 0,
                    'datos_cobros' => array()
                )
            )
        );

        $cantidad_dias = date('t', mktime(0, 0, 0, $mes, 1, $anho));
        for($i=1; $i<=$cantidad_dias; $i++) {
            $cobros_x_mes['TOTALES_MES']['TOTAL']['datos_cobros'][$i] = array(
                'total_cobros' => 0,
                'total_bruto' => 0,
                'total_bruto_cliente' => 0,
                'total_bruto_otros' => 0
            );
        }


        foreach($carriers as $id_carrier => $nombre_carrier) {

            if(!isset($cobros_x_mes['TOTALES_MES'][$id_carrier])) {
                $cobros_x_mes['TOTALES_MES'][$id_carrier] = array(
                    'total_suscriptos' => 0,
                    'total_cobros' => 0,
                    'total_bruto' => 0,
                    'total_bruto_cliente' => 0,
                    'total_bruto_otros' => 0,
                    'datos_cobros' => array()
                );

                $cantidad_dias = date('t', mktime(0, 0, 0, $mes, 1, $anho));
                for($i=1; $i<=$cantidad_dias; $i++) {
                    $cobros_x_mes['TOTALES_MES'][$id_carrier]['datos_cobros'][$i] = array(
                        'total_cobros' => 0,
                        'total_bruto' => 0,
                        'total_bruto_cliente' => 0,
                        'total_bruto_otros' => 0
                    );
                }
            }



            $this->log->info('['.$nombre_carrier.']:[' . $id_carrier . ']');

            if($id_carrier == 2) {//TIGO

                $porcentaje['35500']['ENTER'] = 25;
                $porcentaje['6767']['ENTER'] = 35;

                if(($anho==2012 && $mes>=11) || $anho > 2012) {
                    $porcentaje['6767']['ENTER'] = 25;
                }

            } else if($id_carrier == 1) {//PERSONAL

                $porcentaje['6767']['ENTER'] = 30;
                $porcentaje['35500']['ENTER'] = 40;
            }

            $porcentaje['6767']['OTROS'] = 100 - $porcentaje['6767']['ENTER'];
            $porcentaje['35500']['OTROS'] = 100 - $porcentaje['35500']['ENTER'];

            $this->log->info('porcentajes:[' . print_r($porcentaje, true) . ']');

            $porcentaje_enter = $porcentaje[$numero]['ENTER'];
            $porcentaje_otros = $porcentaje[$numero]['OTROS'];

            $this->log->info('numero:['. $numero .'] ['.$nombre_carrier.']:['.$id_carrier.'] anho:[' . $anho . '] mes:[' . $mes .'] porcentaje:[' . $porcentaje_enter . ' / ' . $porcentaje_otros . ']');

            //select S2.*, ((S2.total_bruto * (CASE WHEN S2.numero = '6767' THEN $porcentaje_6767_enter WHEN S2.numero = '35500' THEN 25 ELSE 25 END))/100)::integer as total_bruto_cliente, ((S2.total_bruto * (CASE WHEN S2.numero = '6767' THEN $porcentaje_6767_otros WHEN S2.numero = '35500' THEN 75 ELSE 75 END))/100)::integer as total_bruto_otros
            $sql = "select IP2.promocion, IP2.id_promocion, IP2.numero, IP2.id_carrier, IP2.alias, S3.fecha, S3.dia_mes, S3.dia_semana, S3.total_cobros, S3.total_bruto, S3.total_bruto_cliente, S3.total_bruto_otros
            from info_promociones IP2
            left join (
                select S2.*, ((S2.total_bruto * $porcentaje_enter)/100)::integer as total_bruto_cliente, ((S2.total_bruto * $porcentaje_otros)/100)::integer as total_bruto_otros
                    from (
                        select S1.*, IP.alias::varchar(10), (IP.costo_gs * S1.total_cobros)::integer as total_bruto
                        from (
                            select	RM.numero, RM.fecha, extract(day from RM.fecha)::integer as dia_mes, RM.dia_semana, RM.id_carrier, RM.id_promocion, sum(RM.total_cobros)::integer as total_cobros
                            from reporte_mensual_cobros(?, ?) RM
                            where RM.numero = ? and id_carrier = ?
                            group by 1,2,3,4,5,6
                            order by 1,2,3,4,5,6
                        ) S1 left join info_promociones IP on IP.id_promocion = S1.id_promocion and IP.id_carrier = S1.id_carrier
                    ) S2
            ) S3 on S3.id_promocion = IP2.id_promocion
            where IP2.id_carrier = ? and IP2.numero = ?
            order by 2,4, 6";

            //$this->log->info($sql);

            /*$namespace = new Zend_Session_Namespace("entermovil");
            if($namespace->usuario == 'felix') {
                echo "<pre>";
                print_r($datos);
                echo "</pre>";
                exit;
            }*/

            $rs_cobros = $db->fetchAll($sql, array($anho, $mes, $numero, $id_carrier, $id_carrier, $numero));
            /*$cobros_x_mes = array(
                'TOTALES_MES' => array(
                    'total_suscriptos' => 0,
                    'total_cobros' => 0,
                    'total_bruto' => 0,
                    'total_bruto_cliente' => 0,
                    'total_bruto_otros' => 0,
                    'datos_cobros' => array()
                )
            );*/




            foreach($rs_cobros as $fila) {

                //Si todavia no esta el nodo del alias
                if(!isset($cobros_x_mes[$fila['alias']])) {
                    $cobros_x_mes[$fila['alias']]['TOTAL'] = array(
                        'total_suscriptos' => 0,
                        'total_cobros' => 0,
                        'total_bruto' => 0,
                        'total_bruto_cliente' => 0,
                        'total_bruto_otros' => 0,
                        'datos_cobros' => array()
                    );
                    for($i=1; $i<=$cantidad_dias; $i++) {
                        $cobros_x_mes[$fila['alias']]['TOTAL']['datos_cobros'][$i] = array(
                            'total_cobros' => 0,
                            'total_bruto' => 0,
                            'total_bruto_cliente' => 0,
                            'total_bruto_otros' => 0
                        );
                    }
                }

                if(!isset($cobros_x_mes[$fila['alias']][$id_carrier])) {
                    $cobros_x_mes[$fila['alias']][$id_carrier] = array(
                        'total_suscriptos' => isset($suscriptos_x_promo[$fila['alias']][$id_carrier]) ? $suscriptos_x_promo[$fila['alias']][$id_carrier] : 0,
                        'total_cobros' => 0,
                        'total_bruto' => 0,
                        'total_bruto_cliente' => 0,
                        'total_bruto_otros' => 0,
                        'datos_cobros' => array()
                    );

                    $cobros_x_mes[$fila['alias']]['TOTAL']['total_suscriptos'] += $cobros_x_mes[$fila['alias']][$id_carrier]['total_suscriptos'];


                    for($i=1; $i<=$cantidad_dias; $i++) {
                        $cobros_x_mes[$fila['alias']][$id_carrier]['datos_cobros'][$i] = array(
                            'total_cobros' => 0,
                            'total_bruto' => 0,
                            'total_bruto_cliente' => 0,
                            'total_bruto_otros' => 0
                        );
                    }

                    $cobros_x_mes['TOTALES_MES'][$id_carrier]['total_suscriptos'] += isset($suscriptos_x_promo[$fila['alias']][$id_carrier]) ? $suscriptos_x_promo[$fila['alias']][$id_carrier] : 0;
                    $cobros_x_mes['TOTALES_MES']['TOTAL']['total_suscriptos'] += isset($suscriptos_x_promo[$fila['alias']][$id_carrier]) ? $suscriptos_x_promo[$fila['alias']][$id_carrier] : 0;

                }

                if(!isset($cobros_x_mes[$fila['alias']][$id_carrier]['datos_cobros'][$fila['dia_mes']])) {
                    $cobros_x_mes[$fila['alias']][$id_carrier]['datos_cobros'][$fila['dia_mes']] = array(
                        'total_cobros' => 0,
                        'total_bruto' => 0,
                        'total_bruto_cliente' => 0,
                        'total_bruto_otros' => 0
                    );
                }

                //Cargamos valores para el dia en particular
                $cobros_x_mes[$fila['alias']][$id_carrier]['datos_cobros'][$fila['dia_mes']]['total_cobros'] = $fila['total_cobros'];
                $cobros_x_mes[$fila['alias']][$id_carrier]['datos_cobros'][$fila['dia_mes']]['total_bruto'] = $fila['total_bruto'];
                $cobros_x_mes[$fila['alias']][$id_carrier]['datos_cobros'][$fila['dia_mes']]['total_bruto_cliente'] = $fila['total_bruto_cliente'];
                $cobros_x_mes[$fila['alias']][$id_carrier]['datos_cobros'][$fila['dia_mes']]['total_bruto_otros'] = $fila['total_bruto_otros'];


                $this->log->info('DiaMes:[' . $fila['dia_mes'] . '] seteado?:[' . isset($cobros_x_mes[$fila['alias']]['TOTAL']['datos_cobros'][$fila['dia_mes']]) . ']');
                if(isset($fila['dia_mes'])) {
                    //$this->log->info('FILA:[' . print_r($fila, true) . ']');
                    $cobros_x_mes[$fila['alias']]['TOTAL']['datos_cobros'][$fila['dia_mes']]['total_cobros'] += $fila['total_cobros'];
                    $cobros_x_mes[$fila['alias']]['TOTAL']['datos_cobros'][$fila['dia_mes']]['total_bruto'] += $fila['total_bruto'];
                    $cobros_x_mes[$fila['alias']]['TOTAL']['datos_cobros'][$fila['dia_mes']]['total_bruto_cliente'] += $fila['total_bruto_cliente'];
                    $cobros_x_mes[$fila['alias']]['TOTAL']['datos_cobros'][$fila['dia_mes']]['total_bruto_otros'] += $fila['total_bruto_otros'];
                }




                if(!isset($cobros_x_mes['TOTALES_MES']['TOTAL']['datos_cobros'][$fila['dia_mes']])) {
                    $cobros_x_mes['TOTALES_MES']['TOTAL']['datos_cobros'][$fila['dia_mes']] = array(
                        'total_cobros' => 0,
                        'total_bruto' => 0,
                        'total_bruto_cliente' => 0,
                        'total_bruto_otros' => 0
                    );
                }

                if(!isset($cobros_x_mes['TOTALES_MES'][$id_carrier]['datos_cobros'][$fila['dia_mes']])) {
                    $cobros_x_mes['TOTALES_MES'][$id_carrier]['datos_cobros'][$fila['dia_mes']] = array(
                        'total_cobros' => 0,
                        'total_bruto' => 0,
                        'total_bruto_cliente' => 0,
                        'total_bruto_otros' => 0
                    );
                }

                //Cargamos valores para totales por dia de todos los alias
                $cobros_x_mes['TOTALES_MES']['TOTAL']['datos_cobros'][$fila['dia_mes']]['total_cobros'] += $fila['total_cobros'];
                $cobros_x_mes['TOTALES_MES']['TOTAL']['datos_cobros'][$fila['dia_mes']]['total_bruto'] += $fila['total_bruto'];
                $cobros_x_mes['TOTALES_MES']['TOTAL']['datos_cobros'][$fila['dia_mes']]['total_bruto_cliente'] += $fila['total_bruto_cliente'];
                $cobros_x_mes['TOTALES_MES']['TOTAL']['datos_cobros'][$fila['dia_mes']]['total_bruto_otros'] += $fila['total_bruto_otros'];

                $cobros_x_mes['TOTALES_MES'][$id_carrier]['datos_cobros'][$fila['dia_mes']]['total_cobros'] += $fila['total_cobros'];
                $cobros_x_mes['TOTALES_MES'][$id_carrier]['datos_cobros'][$fila['dia_mes']]['total_bruto'] += $fila['total_bruto'];
                $cobros_x_mes['TOTALES_MES'][$id_carrier]['datos_cobros'][$fila['dia_mes']]['total_bruto_cliente'] += $fila['total_bruto_cliente'];
                $cobros_x_mes['TOTALES_MES'][$id_carrier]['datos_cobros'][$fila['dia_mes']]['total_bruto_otros'] += $fila['total_bruto_otros'];


                //Cargamos valores para el total del alias
                $cobros_x_mes[$fila['alias']][$id_carrier]['total_cobros'] += $fila['total_cobros'];
                $cobros_x_mes[$fila['alias']][$id_carrier]['total_bruto'] += $fila['total_bruto'];
                $cobros_x_mes[$fila['alias']][$id_carrier]['total_bruto_cliente'] += $fila['total_bruto_cliente'];
                $cobros_x_mes[$fila['alias']][$id_carrier]['total_bruto_otros'] += $fila['total_bruto_otros'];

                $cobros_x_mes[$fila['alias']]['TOTAL']['total_cobros'] += $fila['total_cobros'];
                $cobros_x_mes[$fila['alias']]['TOTAL']['total_bruto'] += $fila['total_bruto'];
                $cobros_x_mes[$fila['alias']]['TOTAL']['total_bruto_cliente'] += $fila['total_bruto_cliente'];
                $cobros_x_mes[$fila['alias']]['TOTAL']['total_bruto_otros'] += $fila['total_bruto_otros'];

                //Cargamos valores para el total del mes
                $cobros_x_mes['TOTALES_MES']['TOTAL']['total_cobros'] += $fila['total_cobros'];
                $cobros_x_mes['TOTALES_MES']['TOTAL']['total_bruto'] += $fila['total_bruto'];
                $cobros_x_mes['TOTALES_MES']['TOTAL']['total_bruto_cliente'] += $fila['total_bruto_cliente'];
                $cobros_x_mes['TOTALES_MES']['TOTAL']['total_bruto_otros'] += $fila['total_bruto_otros'];

                $cobros_x_mes['TOTALES_MES'][$id_carrier]['total_cobros'] += $fila['total_cobros'];
                $cobros_x_mes['TOTALES_MES'][$id_carrier]['total_bruto'] += $fila['total_bruto'];
                $cobros_x_mes['TOTALES_MES'][$id_carrier]['total_bruto_cliente'] += $fila['total_bruto_cliente'];
                $cobros_x_mes['TOTALES_MES'][$id_carrier]['total_bruto_otros'] += $fila['total_bruto_otros'];

            }
        }

        $resultado[$numero]['cobros_x_mes'] = $cobros_x_mes;


        return $resultado;
    }

    public function cobrosPorCarrierAction() {

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

        //$this->log->info('Datos:[' . print_r($datos, true) . ']');

        $this->view->numeros = $this->numeros;
        $this->view->datos = $datos;
        $this->view->carriers = $this->carriers;

    }

    private function _cargarPaisesConPromociones() {

        $resultado = array();

        $bootstrap = $this->getInvokeArg('bootstrap');
        $options = $bootstrap->getOptions();

        $db = Zend_Db::factory(new Zend_Config($options['resources']['db']));
        $db->getConnection();

        $sql = 'select PP.id_pais, PA.nombre
        from promociones_x_pais PP
        left join paises PA on PA.id_pais = PP.id_pais
        group by 1, 2
        order by 1';

        $rs = $db->fetchAll($sql);
        foreach($rs as $fila) {
            $resultado[] = $fila;
        }

        return $resultado;
    }

    private function _cargarPromocionesCarrierNumero($id_pais) {

        $resultado = array();

        $bootstrap = $this->getInvokeArg('bootstrap');
        $options = $bootstrap->getOptions();

        $db = Zend_Db::factory(new Zend_Config($options['resources']['db']));
        $db->getConnection();

        $sql = 'select CxP.id_carrier, IP.numero, IP.id_promocion, IP.alias, IP.costo_gs, IP.costo_usd, RS.porcentaje_proveedor
        , (select count(*) from promosuscripcion.suscriptos where id_promocion = IP.id_promocion and id_carrier = CxP.id_carrier)::integer as suscriptos
        from paises PA
        left join carriers_x_paises CxP on CxP.id_pais = PA.id_pais
        left join info_promociones IP on IP.id_carrier = CxP.id_carrier
        left join revenue_share RS on RS.numero = IP.numero and RS.id_carrier = IP.id_carrier
        where PA.id_pais = ?
        group by 1,2,3,4,5,6,7
        order by 1,2,4';

        $rs = $db->fetchAll($sql, array($id_pais));
        foreach($rs as $fila) {
            $resultado[$fila['id_carrier']][$fila['numero']][] = $fila;
        }

        return $resultado;
    }

    private function _cargarCarriersYNumeros( $id_pais, $fecha_completa ) {

        $resultado = array();

        $anho = substr( $fecha_completa, 0, 4 );
        $mes = substr( $fecha_completa, 5, 2 );

        $bootstrap = $this->getInvokeArg('bootstrap');
        $options = $bootstrap->getOptions();

        $db = Zend_Db::factory(new Zend_Config($options['resources']['db']));
        $db->getConnection();

        $sql = 'select CxP.id_carrier, IP.numero
        from paises PA
        left join carriers_x_paises CxP on CxP.id_pais = PA.id_pais
        left join info_promociones IP on IP.id_carrier = CxP.id_carrier
        where PA.id_pais = ?
        group by 1,2
        order by 1,2';

        $rs = $db->fetchAll($sql, array($id_pais));
        foreach($rs as $fila) {


            /*$sql_cobros = 'select RM.numero, extract(year from RM.fecha)::integer as anho, extract(month from RM.fecha)::integer as mes,RM.id_carrier, RM.id_promocion, sum(RM.total_cobros)::integer as total_cobros
            from reporte_mensual_cobros(?, ?) RM
            where id_carrier = ? and numero = ?
            group by 1,2,3,4,5
            order by 1,2,3,4,5';*/
            //anho, mes, id_carrier

            $sql_cobros = 'select S3.*, S4.total_cobros as total_cobros_hoy, S4.costo_gs, S4.total_bruto_gs, S4.total_neto_gs, S4.costo_usd, S4.total_bruto_usd, S4.total_neto_usd
                from (
                    select RM.numero, extract(year from RM.fecha)::integer as anho, extract(month from RM.fecha)::integer as mes,RM.id_carrier, RM.id_promocion, sum(RM.total_cobros)::integer as total_cobros
                    from reporte_mensual_cobros(?, ?) RM
                    where id_carrier = ? and numero = ?
                    group by 1,2,3,4,5
                    order by 1,2,3,4,5
                ) S3 left join (

                    select S2.*, (S2.total_bruto_gs * S2.porcentaje_proveedor)::integer as total_neto_gs, (S2.total_bruto_usd * S2.porcentaje_proveedor)::numeric(11,3) as total_neto_usd
                    from (
                        select S1.*, IP.costo_gs, IP.costo_usd, (S1.total_cobros * IP.costo_gs)::integer as total_bruto_gs, (S1.total_cobros * IP.costo_usd)::numeric(11,3) as total_bruto_usd
                        from (
                            select RM.numero, RM.fecha, RM.id_carrier, RM.id_promocion, RS.porcentaje_proveedor, sum(RM.total_cobros)::integer as total_cobros
                            from reporte_mensual_cobros(?, ?) RM
                            left join revenue_share RS on RS.id_carrier = RM.id_carrier and RS.numero = RM.numero
                            where RM.id_carrier = ? and RM.numero = ? and RM.fecha = ? group by 1,2,3,4,5
                            order by 1,2,3,4
                        ) S1 left join info_promociones IP on IP.id_promocion = S1.id_promocion and IP.id_carrier = S1.id_carrier
                    ) S2

                ) S4 on S4.numero = S3.numero and S4.id_carrier = S3.id_carrier and S4.id_promocion = S3.id_promocion';

            $rs_cobros = $db->fetchAll($sql_cobros, array($anho, $mes, $fila['id_carrier'], $fila['numero'], $anho, $mes, $fila['id_carrier'], $fila['numero'], $fecha_completa));

            foreach($rs_cobros as $fila_cobro) {
                $fila['cobros'][$fila_cobro['id_promocion']] = array(
                    'total_cobros' => $fila_cobro['total_cobros'],
                    'total_cobros_hoy' => $fila_cobro['total_cobros_hoy'],
                    'costo_gs' => $fila_cobro['costo_gs'],
                    'costo_usd' => $fila_cobro['costo_usd'],
                    'total_bruto_gs' => $fila_cobro['total_bruto_gs'],
                    'total_bruto_usd' => $fila_cobro['total_bruto_usd'],
                    'total_neto_gs' =>  $fila_cobro['total_neto_gs'],
                    'total_neto_usd' => $fila_cobro['total_neto_usd'],
                );
            }


            $resultado[] = $fila;
        }
        return $resultado;
    }

    public function servicioResumenCobrosPorDiaAction() {

        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $id_carrier = $this->_getParam('id_carrier', 0);
        $numero = $this->_getParam('numero', "");
        $fecha = $this->_getParam('fecha', date('Y-m-d'));
        list($anho, $mes, $dia) = explode('-', $fecha);

        $bootstrap = $this->getInvokeArg('bootstrap');
        $options = $bootstrap->getOptions();

        $db = Zend_Db::factory(new Zend_Config($options['resources']['db']));
        $db->getConnection();

        $sql =  "select S2.*, (S2.total_bruto_gs * S2.porcentaje_proveedor)::integer as total_neto_gs, (S2.total_bruto_usd * S2.porcentaje_proveedor)::numeric(11,3) as total_neto_usd
                from (
                    select S1.*, IP.costo_gs, IP.costo_usd, (S1.total_cobros * IP.costo_gs)::integer as total_bruto_gs, (S1.total_cobros * IP.costo_usd)::numeric(11,3) as total_bruto_usd
                    from (
                        select RM.numero, RM.fecha, RM.id_carrier, RM.id_promocion, RS.porcentaje_proveedor, sum(RM.total_cobros)::integer as total_cobros
                        from reporte_mensual_cobros(?, ?) RM
                        left join revenue_share RS on RS.id_carrier = RM.id_carrier and RS.numero = RM.numero
                        where RM.id_carrier = ? and RM.numero = ? and RM.fecha = ?
                        group by 1,2,3,4,5
                        order by 1,2,3,4
                    ) S1 left join info_promociones IP on IP.id_promocion = S1.id_promocion and IP.id_carrier = S1.id_carrier
                ) S2";

        $resultado = array();
        $rs = $db->fetchAll($sql, array($anho, $mes, $id_carrier, $numero, $fecha));
        foreach($rs as $fila) {
            $resultado[$fila['id_promocion']] = array(
                'revenue_share' => $fila['porcentaje_proveedor'],
                'total_cobros' => $fila['total_cobros'],
                'costo_gs' => $fila['costo_gs'], 'costo_usd' => $fila['costo_usd']
            );
        }

        header('Content-Type: text/json');
        echo json_encode($resultado);
    }

    public function resumenCobrosAction() {

        $this->view->headLink()->appendStylesheet('/css/reportes_resumen.css', 'screen');
        //agregado para probar
        $this->view->headScript()->appendFile('http://code.jquery.com/ui/1.10.0/jquery-ui.js', 'text/javascript');
        $this->view->headLink()->appendStylesheet('http://code.jquery.com/ui/1.10.0/themes/base/jquery-ui.css');
        $this->view->headScript()->appendFile('/js/reportes_resumen.js', 'text/javascript');

        $this->view->headTitle()->append('Resumen Cobros');

        $this->view->nombre_carrier = array(
            1 => 'PERSONAL', 2 => 'TIGO', 3 => 'VOX', 4 => 'CLARO',
            5 => 'TIGO_GUATEMALA', 6 => 'TIGO_BOLIVIA'
        );

        $this->view->paises = $this->_cargarPaisesConPromociones();

        $id_pais = $this->_getParam('pais', 1);

        $this->view->id_pais = $id_pais;

        /*$fecha = $this->_getParam('fecha', date('Y-m'));

        list($anho, $mes) = explode('-', $fecha);*/ //codigo original

        //agregado DAAS

        //obtengo el parametro fecha
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

        $this->_setupRangoSeleccion($anho, $mes);

        $this->view->nombre_mes = $this->meses[$mes-1];

        $this->view->cantidad_dias = date('t', mktime(0, 0, 0, $mes, 1, $anho));


        $this->view->anho = $anho;
        $this->view->mes = $mes;

        $this->view->dia_hoy = date('j');

        $this->view->dias_semana = $this->dias_semana;

        $this->view->nombres_dias_del_mes = $this->cargarNombresDiasDelMes($anho, $mes);

        $this->view->rango_seleccion = $this->rango_seleccion;

        //fin agregado DAAS

        $carriers_y_numeros = $this->_cargarCarriersYNumeros( $id_pais, $fecha_seleccionada );

        $this->view->carriers_numeros = $carriers_y_numeros;
        //echo 'carriers_numeros:[' . print_r($carriers_y_numeros, true) . ']' . "\n\n"; exit;
        //$this->log->info('carriers_numeros:[' . print_r($carriers_y_numeros, true) . ']');

        //print_r($this->_cargarPromocionesCarrierNumero($id_pais));
        $promociones_carriers_numeros = $this->_cargarPromocionesCarrierNumero($id_pais);
        $this->view->promociones_carriers_numeros = $promociones_carriers_numeros;
        //echo 'promociones_carriers_numeros:[' . print_r($promociones_carriers_numeros, true) . ']' . "\n\n"; exit;

        $total_sumatoria = array(
            'TOTAL_COBROS'=>0,
            'TOTAL_BRUTO'=>0,
            'TOTAL_NETO'=>0,
            'decimal' => false,
            'TOTAL_SUSCRIPTOS' => 0
        );
        $totales_promociones_carriers_numeros = array();
        foreach($carriers_y_numeros as $carrier_numero) {
            //echo 'carrier_numero:[' . print_r($carrier_numero, true) . ']' . "\n\n";
            $sumatoria[$carrier_numero['id_carrier']][$carrier_numero['numero']] = array(
                'TOTAL_COBROS'=>0,
                'TOTAL_BRUTO'=>0,
                'TOTAL_NETO'=>0,
                'decimal' => false,
                'TOTAL_SUSCRIPTOS' => 0
            );

            $totales[$carrier_numero['id_carrier']][$carrier_numero['numero']] = array();
            if(isset($carrier_numero['cobros'])) {
                foreach($carrier_numero['cobros'] as $id_promocion => $datos_cobros) {//$cantidad_cobros
                    $totales[$carrier_numero['id_carrier']][$carrier_numero['numero']][$id_promocion]['cobros'] = $datos_cobros['total_cobros'];
                    $totales[$carrier_numero['id_carrier']][$carrier_numero['numero']][$id_promocion]['cobros_hoy'] = $datos_cobros['total_cobros_hoy'];

                    $totales[$carrier_numero['id_carrier']][$carrier_numero['numero']][$id_promocion]['costo_gs'] = $datos_cobros['costo_gs'];
                    $totales[$carrier_numero['id_carrier']][$carrier_numero['numero']][$id_promocion]['costo_usd'] = $datos_cobros['costo_usd'];

                    $totales[$carrier_numero['id_carrier']][$carrier_numero['numero']][$id_promocion]['total_bruto_gs'] = $datos_cobros['total_bruto_gs'];
                    $totales[$carrier_numero['id_carrier']][$carrier_numero['numero']][$id_promocion]['total_bruto_usd'] = $datos_cobros['total_bruto_usd'];

                    $totales[$carrier_numero['id_carrier']][$carrier_numero['numero']][$id_promocion]['total_neto_gs'] = $datos_cobros['total_neto_gs'];
                    $totales[$carrier_numero['id_carrier']][$carrier_numero['numero']][$id_promocion]['total_neto_usd'] = $datos_cobros['total_neto_usd'];
                }
            }

            foreach($promociones_carriers_numeros[$carrier_numero['id_carrier']][$carrier_numero['numero']] as $promocion_carrier_numero) {

                //echo "=======\n\n\n";
                //echo 'promocion_carrier_numero:[' . print_r($promocion_carrier_numero, true) . ']' . "\n\n";
                //echo "=======\n\n\n";

                $totales[$carrier_numero['id_carrier']][$carrier_numero['numero']][$promocion_carrier_numero['id_promocion']]['costo'] = ($promocion_carrier_numero['costo_gs'] == 0 ? $promocion_carrier_numero['costo_usd'] : $promocion_carrier_numero['costo_gs']);
                $totales[$carrier_numero['id_carrier']][$carrier_numero['numero']][$promocion_carrier_numero['id_promocion']]['decimal'] = ($promocion_carrier_numero['costo_gs'] == 0 ? true : false);
                //Si no tiene cobros, ponemos zero
                if(!isset($totales[$carrier_numero['id_carrier']][$carrier_numero['numero']][$promocion_carrier_numero['id_promocion']]['cobros'])) {
                    $totales[$carrier_numero['id_carrier']][$carrier_numero['numero']][$promocion_carrier_numero['id_promocion']]['cobros'] = 0;
                }
                if(!isset($totales[$carrier_numero['id_carrier']][$carrier_numero['numero']][$promocion_carrier_numero['id_promocion']]['cobros_hoy'])) {
                    $totales[$carrier_numero['id_carrier']][$carrier_numero['numero']][$promocion_carrier_numero['id_promocion']]['cobros_hoy'] = 0;
                }

                //calculamos total_bruto
                $totales[$carrier_numero['id_carrier']][$carrier_numero['numero']][$promocion_carrier_numero['id_promocion']]['total_bruto'] = $totales[$carrier_numero['id_carrier']][$carrier_numero['numero']][$promocion_carrier_numero['id_promocion']]['costo'] * $totales[$carrier_numero['id_carrier']][$carrier_numero['numero']][$promocion_carrier_numero['id_promocion']]['cobros'];
                $totales[$carrier_numero['id_carrier']][$carrier_numero['numero']][$promocion_carrier_numero['id_promocion']]['revenue_share'] = $promocion_carrier_numero['porcentaje_proveedor'];
                $totales[$carrier_numero['id_carrier']][$carrier_numero['numero']][$promocion_carrier_numero['id_promocion']]['total_neto'] = round($totales[$carrier_numero['id_carrier']][$carrier_numero['numero']][$promocion_carrier_numero['id_promocion']]['total_bruto'] * $promocion_carrier_numero['porcentaje_proveedor'],2);
                //agregado


                /*
                 * Sumatoria de los totales: Cobros, Bruto, Neto
                 */
                $sumatoria[$carrier_numero['id_carrier']][$carrier_numero['numero']]['TOTAL_COBROS'] +=
                    $totales[$carrier_numero['id_carrier']][$carrier_numero['numero']][$promocion_carrier_numero['id_promocion']]['cobros'];

                $sumatoria[$carrier_numero['id_carrier']][$carrier_numero['numero']]['TOTAL_BRUTO'] +=
                    $totales[$carrier_numero['id_carrier']][$carrier_numero['numero']][$promocion_carrier_numero['id_promocion']]['total_bruto'];

                 $sumatoria[$carrier_numero['id_carrier']][$carrier_numero['numero']]['TOTAL_NETO'] +=
                     $totales[$carrier_numero['id_carrier']][$carrier_numero['numero']][$promocion_carrier_numero['id_promocion']]['total_neto'];

                $sumatoria[$carrier_numero['id_carrier']][$carrier_numero['numero']]['decimal'] =
                    $totales[$carrier_numero['id_carrier']][$carrier_numero['numero']][$promocion_carrier_numero['id_promocion']]['decimal'];

                $sumatoria[$carrier_numero['id_carrier']][$carrier_numero['numero']]['TOTAL_SUSCRIPTOS'] +=
                    $promocion_carrier_numero['suscriptos'];



            }
            //totales del mes
            $total_sumatoria['TOTAL_COBROS'] += $sumatoria[$carrier_numero['id_carrier']][$carrier_numero['numero']]['TOTAL_COBROS'];

            $total_sumatoria['TOTAL_BRUTO'] += $sumatoria[$carrier_numero['id_carrier']][$carrier_numero['numero']]['TOTAL_BRUTO'];

            $total_sumatoria['TOTAL_NETO'] += $sumatoria[$carrier_numero['id_carrier']][$carrier_numero['numero']]['TOTAL_NETO'];

            $total_sumatoria['TOTAL_SUSCRIPTOS'] += $sumatoria[$carrier_numero['id_carrier']][$carrier_numero['numero']]['TOTAL_SUSCRIPTOS'];

            $total_sumatoria['decimal'] =
                $totales[$carrier_numero['id_carrier']][$carrier_numero['numero']][$promocion_carrier_numero['id_promocion']]['decimal'];


        }

        $this->view->total_sumatoria = $total_sumatoria;
        $this->view->totales = $totales;
        $this->view->sumatoria = $sumatoria;
        $this->log->info('totales:[' . print_r($totales, true) . ']');
       // print_r($totales);
        //exit;

        /*foreach($this->view->carriers_numeros as $fila) {
            if(isset($fila['cobros'])) {
                foreach($fila['cobros'] as $id_promocion => $total_cobros) {
                    $this->view->promociones_carriers_numeros[$fila['id_carrier']][$fila['numero']][$id_promocion]['total'] = $this->view->promociones_carriers_numeros
                }
            }
        }*/

        /*$fecha_seleccionada = $this->_getParam('fecha', null);
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

        $this->view->rango_seleccion = $this->rango_seleccion;*/

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

    public function cobrosViejoAction() {

        $this->view->headScript()->appendFile('/js/reportes_cobros.js', 'text/javascript');
        $this->view->headLink()->appendStylesheet('/css/reportes_cobros.css', 'screen');

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

        $bootstrap = $this->getInvokeArg('bootstrap');
        $options = $bootstrap->getOptions();

        $db = Zend_Db::factory(new Zend_Config($options['resources']['db']));
        $db->getConnection();

        $sql = 'select IP.id_promocion, IP.alias, count(S.id_suscripto)::integer as total_suscriptos
                from info_promociones IP
                left join promosuscripcion.suscriptos S on S.id_promocion = IP.id_promocion
                where IP.numero = ? and IP.id_promocion not in(9)
                group by 1,2 order by total_suscriptos desc';
        $rs_suscriptos = $db->fetchAll($sql, array('35500'));
        $promociones = array();
        $suscriptos_x_promo = array();
        foreach($rs_suscriptos as $fila) {
            $promociones[] = $fila;
            $suscriptos_x_promo[$fila['alias']] = $fila['total_suscriptos'];
        }

        //print_r($suscriptos_x_promo); exit;

        $this->view->promociones = $promociones;


        $calendario_envios = array();
        $sql_envio = 'select dia from promosuscripcion.dias_envio where id_promocion = ?';
        foreach($promociones as $promocion) {

            $rs_envios = $db->fetchAll($sql_envio, array($promocion['id_promocion']));
            $dias_envio = array();
            foreach($rs_envios as $fila) {
                $dias_envio[] = $fila['dia'];
            }
            $calendario_envios[$promocion['id_promocion']] = $dias_envio;
        }

        $this->view->calendario_envios = $calendario_envios;


        $sql = 'select S2.*, ((S2.total_bruto * 25)/100)::integer as total_bruto_cliente, ((S2.total_bruto * 75)/100)::integer as total_bruto_otros
            from (
                select S1.*, IP.alias::varchar(10), (IP.costo_gs * S1.total_cobros)::integer as total_bruto
                from (
                    select	RM.numero, RM.fecha, extract(day from RM.fecha)::integer as dia_mes, RM.dia_semana, RM.id_carrier, RM.id_promocion, sum(RM.total_cobros)::integer as total_cobros
                    from reporte_mensual_cobros(?, ?) RM
                    where RM.numero = ?
                    group by 1,2,3,4,5,6
                    order by 1,2,3,4,5,6
                ) S1 left join info_promociones IP on IP.id_promocion = S1.id_promocion and IP.id_carrier = S1.id_carrier
            ) S2';

        /*$cobros = array(
            'BB' => array(
                'total_suscriptos' => 20,
                'total_cobros' => 3333,
                'total_bruto_gs' => 2342323,
                'datos_cobros' => array(
                    1 => array(
                        'total_cobros' => 2,
                        'total_bruto' => 2000,
                        'total_bruto_cliente' => 500,
                        'total_bruto_otros' => 1500
                    ),
                    2 => array(
                        'total_cobros' => 2,
                        'total_bruto' => 2000,
                        'total_bruto_cliente' => 500,
                        'total_bruto_otros' => 1500
                    )
                )
            ),
            'TONO' => array(
                ...
            )
        );*/

        $rs_cobros = $db->fetchAll($sql, array($anho, $mes, '35500'));
        $cobros_x_mes = array(
            'TOTALES_MES' => array(
                'total_suscriptos' => 0,
                'total_cobros' => 0,
                'total_bruto' => 0,
                'total_bruto_cliente' => 0,
                'total_bruto_otros' => 0,
                'datos_cobros' => array()
            )
        );

        foreach($rs_cobros as $fila) {

            //Si todavia no esta el nodo del alias
            if(!isset($cobros_x_mes[$fila['alias']])) {
                $cobros_x_mes[$fila['alias']] = array(
                    'total_suscriptos' => $suscriptos_x_promo[$fila['alias']],
                    'total_cobros' => 0,
                    'total_bruto' => 0,
                    'total_bruto_cliente' => 0,
                    'total_bruto_otros' => 0,
                    'datos_cobros' => array()
                );

                $cobros_x_mes['TOTALES_MES']['total_suscriptos'] += $suscriptos_x_promo[$fila['alias']];
            }

            if(!isset($cobros_x_mes[$fila['alias']]['datos_cobros'][$fila['dia_mes']])) {
                $cobros_x_mes[$fila['alias']]['datos_cobros'][$fila['dia_mes']] = array(
                    'total_cobros' => 0,
                    'total_bruto' => 0,
                    'total_bruto_cliente' => 0,
                    'total_bruto_otros' => 0
                );
            }

            //Cargamos valores para el dia en particular
            $cobros_x_mes[$fila['alias']]['datos_cobros'][$fila['dia_mes']]['total_cobros'] = $fila['total_cobros'];
            $cobros_x_mes[$fila['alias']]['datos_cobros'][$fila['dia_mes']]['total_bruto'] = $fila['total_bruto'];
            $cobros_x_mes[$fila['alias']]['datos_cobros'][$fila['dia_mes']]['total_bruto_cliente'] = $fila['total_bruto_cliente'];
            $cobros_x_mes[$fila['alias']]['datos_cobros'][$fila['dia_mes']]['total_bruto_otros'] = $fila['total_bruto_otros'];

            //Cargamos valores para totales por dia de todos los alias
            $cobros_x_mes['TOTALES_MES']['datos_cobros'][$fila['dia_mes']]['total_cobros'] += $fila['total_cobros'];
            $cobros_x_mes['TOTALES_MES']['datos_cobros'][$fila['dia_mes']]['total_bruto'] += $fila['total_bruto'];
            $cobros_x_mes['TOTALES_MES']['datos_cobros'][$fila['dia_mes']]['total_bruto_cliente'] += $fila['total_bruto_cliente'];
            $cobros_x_mes['TOTALES_MES']['datos_cobros'][$fila['dia_mes']]['total_bruto_otros'] += $fila['total_bruto_otros'];


            //Cargamos valores para el total del alias
            $cobros_x_mes[$fila['alias']]['total_cobros'] += $fila['total_cobros'];
            $cobros_x_mes[$fila['alias']]['total_bruto'] += $fila['total_bruto'];
            $cobros_x_mes[$fila['alias']]['total_bruto_cliente'] += $fila['total_bruto_cliente'];
            $cobros_x_mes[$fila['alias']]['total_bruto_otros'] += $fila['total_bruto_otros'];

            //Cargamos valores para el total del mes
            $cobros_x_mes['TOTALES_MES']['total_cobros'] += $fila['total_cobros'];
            $cobros_x_mes['TOTALES_MES']['total_bruto'] += $fila['total_bruto'];
            $cobros_x_mes['TOTALES_MES']['total_bruto_cliente'] += $fila['total_bruto_cliente'];
            $cobros_x_mes['TOTALES_MES']['total_bruto_otros'] += $fila['total_bruto_otros'];

        }


        //print_r($cobros_x_mes); exit;
        //$this->log->info('cobros_x_mes:[' . print_r($cobros_x_mes, true) . ']');


        $this->view->cobros_x_mes = $cobros_x_mes;
        //$this->view->totales_x_mes = $totales_x_mes;

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

                $this->log->info('fila -> dia:['. $fila['dia_mes'] .'] hora:[' . $fila['hora'] . '] id_carrier:[' . $fila['id_carrier'] . '] accion:[' . $fila['accion'] . ']');

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

    private function _cargarSuscriptosNumero($numero, $anho, $mes) {

        $resultado = array();

        $bootstrap = $this->getInvokeArg('bootstrap');
        $options = $bootstrap->getOptions();

        $db = Zend_Db::factory(new Zend_Config($options['resources']['db']));
        $db->getConnection();

        $sql = 'select IP.id_promocion, IP.id_carrier, IP.alias, count(S.id_suscripto)::integer as total_suscriptos
                from info_promociones IP
                left join promosuscripcion.suscriptos S on S.id_promocion = IP.id_promocion and S.id_carrier = IP.id_carrier
                where IP.numero = ? and IP.id_promocion not in(9) and IP.id_carrier in(1,2)
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

        //print_r($suscriptos_x_promo); exit;

        $resultado[$numero]['promociones'] = $promociones;

        $sql = 'select extract(day from ts_local)::integer as dia_mes, extract(dow from ts_local)::integer as dia_semana, ts_local::date as fecha, id_promocion, id_carrier, accion, count(*) as total
        from  promosuscripcion.log_suscriptos
        where id_carrier in(1,2) and extract(year from ts_local)::integer = ? and extract(month from ts_local)::integer = ? and id_promocion in(select id_promocion from info_promociones where numero = ? group by 1 order by 1) and accion = \'ALTA\'
        group by 1,2,3,4,5,6
        union
        select extract(day from ts_local)::integer as dia_mes, extract(dow from ts_local)::integer as dia_semana, ts_local::date as fecha, id_promocion, id_carrier, accion, count(*) as total
        from promosuscripcion.log_suscriptos
        where id_carrier in(1,2) and extract(year from ts_local)::integer = ? and extract(month from ts_local)::integer = ? and id_promocion in(select id_promocion from info_promociones where numero = ? group by 1 order by 1) and accion = \'BAJA\'
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

    /*
     * Reporte de suscriptos por minuto
     */
    public function suscriptosPorMinutoAction() {
        $this->view->headScript()->appendFile('http://code.jquery.com/ui/1.10.0/jquery-ui.js', 'text/javascript');
        $this->view->headLink()->appendStylesheet('http://code.jquery.com/ui/1.10.0/themes/base/jquery-ui.css');
        $this->view->headScript()->appendFile('/js/jquery.timeentry.min.js', 'text/javascript');
        $this->view->headScript()->appendFile('/js/jquery.timeentry.js', 'text/javascript');
        $this->view->headScript()->appendFile('/js/jquery.timeentry-es.js', 'text/javascript');
        $this->view->headLink()->appendStylesheet('/css/jquery.timeentry.css', 'screen');
        $this->view->headScript()->appendFile('/js/reportes_suscriptos_por_minuto.js', 'text/javascript');
        $this->view->headLink()->appendStylesheet('/css/reportes_suscriptos_x_minuto.css', 'screen');

        $this->view->headTitle()->append('Suscriptos');

        $fecha_seleccionada = $this->_getParam('fecha', null);
        $this->view->fecha = $fecha_seleccionada;
        $hora_desde = $this->_getParam('desde', null);
        $this->view->hrdesde = $hora_desde;
        $hora_hasta = $this->_getParam('hasta', null);
        $this->view->hrhasta = $hora_hasta;
        if(!is_null($fecha_seleccionada)) {
            list($anho, $mes, $dia) = explode('-', $fecha_seleccionada);
            $mes = (int)$mes;
            $dia = (int)$dia;
        } else {
            $anho = date('Y');
            $mes = date('n');
        }

        $this->_setupRangoSeleccion($anho, $mes);

        $this->view->nombre_mes = $this->meses[$mes-1];

        // Te da la cantidad de dias del mes y anho
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
            $datos =$this->_cargarSuscriptosPromocionMin($id_promocion, $anho, $mes, $dia, $hora_desde, $hora_hasta);



            $this->view->numeros = $this->numeros;
            $this->view->datos = $datos;
            $this->view->carriers = $this->carriers;

            $this->log->info('datos:[' . print_r($datos, true) . ']');

        } else {

            //
        }

    }

    /*
     * Crea una estructura de array con las horas y minutos de un rango seleccionado
     */
    private function _cantidadMinutos($hora_desde, $hora_hasta){
        list($horaD, $minD) = explode(':',$hora_desde);
        list($horaH, $minH) = explode(':',$hora_hasta);
        $hora_inicio = mktime($horaD,$minD);
        $hora_fin = mktime($horaH,$minH);
        $cant_min = abs(($hora_fin-$hora_inicio)/60);
        $array_hora = array();
        $array_hora[date('H:i',mktime($horaD,$minD))]= array('ALTA'=>0, 'BAJA'=>0, 'NETO' =>0);
        for($i=0; $i<$cant_min; $i++) {
            $hora_inicio = $hora_inicio + 60;
            $array_hora[date('H:i',$hora_inicio)] = array('ALTA'=> 0, 'BAJA'=>0, 'NETO' =>0);
        }
        return $array_hora;
    }

    /*
     * Carga el reporte de suscriptos dentro de un rango de minutos
     */
    private function _cargarSuscriptosPromocionMin($id_promocion, $anho, $mes, $dia, $hora_desde, $hora_hasta) {

        $resultado = array();

        $bootstrap = $this->getInvokeArg('bootstrap');
        $options = $bootstrap->getOptions();

        $db = Zend_Db::factory(new Zend_Config($options['resources']['db']));
        $db->getConnection();

        $fecha_hora_desde = $anho . '-' . $mes . '-' . $dia . ' ' . $hora_desde;
        $fecha_hora_hasta = $anho . '-' . $mes . '-' . $dia . ' ' . $hora_hasta;

        $sql = "select id_promocion, extract(hour from ts_local)::integer as hora,
              extract (minute from ts_local)::integer as minuto,
              extract(dow from ts_local)::integer as dia_semana,
              extract(day from ts_local)::integer as dia_mes,
              date_trunc('minute', ts_local::timestamp) as fechaHora,
              ts_local::date as fecha, accion, count(*) as total
              from promosuscripcion.log_suscriptos
              where id_promocion = ? and extract(year from ts_local)::integer = ? and
              extract(month from ts_local)::integer = ? and accion = 'ALTA' and
              extract(day from ts_local)::integer = ?
              and ts_local::timestamp BETWEEN CAST(? AS TIMESTAMP) AND
              CAST(? AS TIMESTAMP)
              group by 1,2,3,4,5,6,7,8
              union
              select id_promocion, extract(hour from ts_local)::integer as hora,
              extract (minute from ts_local)::integer as minuto,
              extract(dow from ts_local)::integer as dia_semana,
              extract(day from ts_local)::integer as dia_mes,
              date_trunc('minute', ts_local::timestamp) as fechaHora,
              ts_local::date as fecha, accion, count(*) as total
              from promosuscripcion.log_suscriptos
              where id_promocion = ? and extract(year from ts_local)::integer = ? and
              extract(month from ts_local)::integer = ? and accion = 'BAJA' and
              extract(day from ts_local)::integer = ?
              and ts_local::timestamp BETWEEN CAST(? AS TIMESTAMP) AND
              CAST(? AS TIMESTAMP)
              group by 1,2,3,4,5,6,7,8
              order by 1,2,3,4,5,7";

        $rs_suscriptos = $db->fetchAll($sql, array($id_promocion, $anho, $mes, $dia, $fecha_hora_desde, $fecha_hora_hasta,
            $id_promocion, $anho, $mes , $dia,$fecha_hora_desde, $fecha_hora_hasta));

        $list_min = $this->_cantidadMinutos($hora_desde,$hora_hasta);
        $altas_bajas_promocion = array(
            'TOTAL_ALTA'=>0,
            'TOTAL_BAJA'=>0,
            'anho' => $anho,
            'mes' => $this->meses[$mes-1],
            'dia' => $this->dias_semana[date('w',mktime(0, 0, 0,$mes,$dia,$anho))] . '-' . $dia,
            'datos' =>  $list_min
        );
        if(count($rs_suscriptos) > 0) {
            foreach($rs_suscriptos as $fila){
                $altas_bajas_promocion['datos'][date('H:i',mktime($fila['hora'],$fila['minuto']))][$fila['accion']] =
                    $fila['total'];
                $altas_bajas_promocion['datos'][date('H:i',mktime($fila['hora'],$fila['minuto']))]['NETO'] =
                    $altas_bajas_promocion['datos'][date('H:i',mktime($fila['hora'],$fila['minuto']))]['ALTA'] -
                        $altas_bajas_promocion['datos'][date('H:i',mktime($fila['hora'],$fila['minuto']))]['BAJA'];
                $altas_bajas_promocion['TOTAL_' . $fila['accion']] += $fila['total'];
            }

        }
        $resultado = $altas_bajas_promocion;
        //$this->log->info('resultado:[' . print_r($resultado, true) . ']');
        return $resultado;
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

    public function suscriptosAction() {

        $this->view->headScript()->appendFile('/js/reportes_suscriptos.js', 'text/javascript');
        $this->view->headLink()->appendStylesheet('/css/reportes_suscriptos.css', 'screen');

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

        $this->view->dias_semana = $this->dias_semana;

        $this->view->nombres_dias_del_mes = $this->cargarNombresDiasDelMes($anho, $mes);

        $this->view->rango_seleccion = $this->rango_seleccion;

        
        $datos = array();
        foreach($this->numeros as $numero) {

            $resultado = $this->_cargarSuscriptosNumero($numero, $anho, $mes);
            $datos[$numero] = $resultado[$numero];
        }

        $this->view->numeros = $this->numeros;
        $this->view->datos = $datos;
        $this->view->carriers = $this->carriers;
    }

    public function suscriptosViejoAction() {

        $this->view->headScript()->appendFile('/js/reportes_suscriptos.js', 'text/javascript');
        $this->view->headLink()->appendStylesheet('/css/reportes_suscriptos.css', 'screen');

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

        $this->view->dias_semana = $this->dias_semana;

        $this->view->nombres_dias_del_mes = $this->cargarNombresDiasDelMes($anho, $mes);

        $this->view->rango_seleccion = $this->rango_seleccion;

        $bootstrap = $this->getInvokeArg('bootstrap');
        $options = $bootstrap->getOptions();

        $db = Zend_Db::factory(new Zend_Config($options['resources']['db']));
        $db->getConnection();

        $sql = 'select IP.id_promocion, IP.alias, count(S.id_suscripto)::integer as total_suscriptos
                from info_promociones IP
                left join promosuscripcion.suscriptos S on S.id_promocion = IP.id_promocion
                where IP.numero = ? and IP.id_promocion <> 216
                group by 1,2 order by total_suscriptos desc';
        $rs_suscriptos = $db->fetchAll($sql, array('35500'));
        $promociones = array();
        $suscriptos_x_promo = array();
        $total_suscriptos = 0;
        foreach($rs_suscriptos as $fila) {
            $promociones[] = $fila;
            $total_suscriptos += $fila['total_suscriptos'];
        }
        $this->view->total_suscriptos = $total_suscriptos;

        //print_r($suscriptos_x_promo); exit;

        $this->view->promociones = $promociones;

        $sql = 'select extract(day from ts_local)::integer as dia_mes, extract(dow from ts_local)::integer as dia_semana, ts_local::date as fecha, id_promocion, accion, count(*) as total
        from promosuscripcion.log_suscriptos
        where extract(year from ts_local)::integer = ? and extract(month from ts_local)::integer = ? and id_promocion in(select id_promocion from info_promociones where numero = ? group by 1 order by 1) and accion = \'ALTA\'
        group by 1,2,3,4,5
        union
        select extract(day from ts_local)::integer as dia_mes, extract(dow from ts_local)::integer as dia_semana, ts_local::date as fecha, id_promocion, accion, count(*) as total
        from promosuscripcion.log_suscriptos
        where extract(year from ts_local)::integer = ? and extract(month from ts_local)::integer = ? and id_promocion in(select id_promocion from info_promociones where numero = ? group by 1 order by 1) and accion = \'BAJA\'
        group by 1,2,3,4,5
        order by 1,2,3,4';

        $rs_suscriptos = $db->fetchAll($sql, array($anho, $mes, '35500', $anho, $mes, '35500'));

        $altas_bajas_x_mes = array(
            'TOTALES_MES' => array(
                'TOTAL_ALTA' => 0,
                'TOTAL_BAJA' => 0,
                'datos' => array()
            )
        );
        foreach($rs_suscriptos as $fila) {

            if(!isset($altas_bajas_x_mes[$fila['id_promocion']])) {
                $altas_bajas_x_mes[$fila['id_promocion']] = array(
                    'TOTAL_ALTA' => 0,
                    'TOTAL_BAJA' => 0,
                    'datos' => array()
                );
            }

            if(!isset($altas_bajas_x_mes[$fila['id_promocion']]['datos'][$fila['dia_mes']])) {
                $altas_bajas_x_mes[$fila['id_promocion']]['datos'][$fila['dia_mes']] = array(
                    'ALTA' => 0, 'BAJA' => 0
                );
            }

            $altas_bajas_x_mes[$fila['id_promocion']]['datos'][$fila['dia_mes']][$fila['accion']] = $fila['total'];

            $altas_bajas_x_mes[$fila['id_promocion']]['TOTAL_'.$fila['accion']] += $fila['total'];

            if(!isset($altas_bajas_x_mes['TOTALES_MES']['datos'][$fila['dia_mes']])) {
                $altas_bajas_x_mes['TOTALES_MES']['datos'][$fila['dia_mes']] = array(
                    'ALTA' => 0, 'BAJA' => 0
                );
            }

            $altas_bajas_x_mes['TOTALES_MES']['datos'][$fila['dia_mes']][$fila['accion']] += $fila['total'];
            $altas_bajas_x_mes['TOTALES_MES']['TOTAL_'.$fila['accion']] += $fila['total'];
        }

        $this->view->altas_bajas_x_mes = $altas_bajas_x_mes;

        //print_r($altas_bajas_x_mes); exit;

    }

    private function _cargarContenidosNumero($numero) {

        $resultado = array();

        $bootstrap = $this->getInvokeArg('bootstrap');
        $options = $bootstrap->getOptions();

        $db = Zend_Db::factory(new Zend_Config($options['resources']['db']));
        $db->getConnection();

        $datos_contenidos = array();

        $sql = 'select IP.id_promocion, IP.alias, count(S.id_suscripto)::integer as total_suscriptos
                from info_promociones IP
                left join promosuscripcion.suscriptos S on S.id_promocion = IP.id_promocion
                where IP.numero = ? and IP.id_promocion not in(9)
                group by 1,2 order by total_suscriptos desc';
        $rs_suscriptos = $db->fetchAll($sql, array($numero));

        $promociones = array();
        foreach($rs_suscriptos as $fila) {
            $promociones[] = $fila;
        }

        $resultado[$numero]['promociones'] = $promociones;

        foreach($promociones as $promocion) {

            $datos_contenidos[$promocion['alias']] = array('CARGADO' => 0, 'ENVIADO' => 0, 'PENDIENTE' => 0);

            $sql = "select (case when S4.id_contenido > (select ME.id_mensaje_periodico_enviado as id_contenido
                from promosuscripcion.mensajes_enviados ME
                where ME.id_promocion = ?
                group by 1
                order by 1 desc
                limit 1) THEN 'PENDIENTE' ELSE 'ENVIADO' END)::varchar as estado, count(*)::integer as cantidad
            from (

                select MP.id_mensaje_periodico as id_contenido, MP.mensaje as contenido, coalesce(S1.cantidad_suscriptos, 0)::integer as cantidad_suscriptos
                from promosuscripcion.mensajes_periodicos as MP
                left join (
                    select ME.id_mensaje_periodico_enviado as id_contenido, count(*)::integer as cantidad_suscriptos
                    from promosuscripcion.mensajes_enviados ME
                    where ME.id_promocion = ?
                    group by 1
                    order by 1 asc
                ) S1 on S1.id_contenido = MP.id_mensaje_periodico
                where MP.id_promocion = ? and MP.tipo in(0,5)
                order by MP.id_mensaje_periodico

            ) S4 group by 1";

            $rs_contenidos = $db->fetchAll($sql, array($promocion['id_promocion'], $promocion['id_promocion'], $promocion['id_promocion']));

            foreach($rs_contenidos as $fila) {
                $datos_contenidos[$promocion['alias']][$fila['estado']] = $fila['cantidad'];
                $datos_contenidos[$promocion['alias']]['CARGADO'] += $fila['cantidad'];
            }
        }

        $resultado[$numero]['datos_contenidos'] = $datos_contenidos;

        return $resultado;
    }


    public function contenidosAction() {

        $this->view->headLink()->appendStylesheet('/css/reportes_contenidos.css', 'screen');

        $this->view->headTitle()->append('Contenidos');

        $datos = array();
        foreach($this->numeros as $numero) {

            $resultado = $this->_cargarContenidosNumero($numero);
            $datos[$numero] = $resultado[$numero];
        }

        $this->view->numeros = $this->numeros;
        $this->view->datos = $datos;
    }

    public function salirAction() {

        $this->_forward('logout', 'auth');
    }

}

?>