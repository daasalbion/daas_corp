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

    var $carriers_wap = array(
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

    var $id_promocion_x_tipo = array(

        'YA_6767_PY' => '73',
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
        
        $this->numeros = array('6767', '35500', '965', '10130');
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
        $sql = "select S1.*, coalesce(S2.accion, 'NINGUNA')::varchar as accion, coalesce(S2.cantidad, 0)::integer as cantidad,  S3.cantidad as cantidad_hoy
                from (
                    select AC.canal, IP.alias, IP.id_promocion, count(S.*)::integer as suscriptos
                    from info_promociones IP
                    left join alias AC on AC.servicio = IP.alias and ('' || AC.nro_corto)::varchar = IP.numero
                    left join promosuscripcion.suscriptos S on S.id_promocion = IP.id_promocion and S.id_carrier = IP.id_carrier
                    where IP.id_carrier in(1,2) and AC.canal is not null
                    group by 1,2,3
                    order by 1,2
                ) S1 left join (
                    select LS.id_promocion, LS.accion, count(LS.*)::integer as cantidad
                    from promosuscripcion.log_suscriptos LS
                    where LS.id_promocion in(
                        select IP.id_promocion
                        from info_promociones IP
                        left join alias AC on AC.servicio = IP.alias and ('' || AC.nro_corto)::varchar = IP.numero where AC.canal is not null
                        group by 1 order by 1
                    ) and LS.ts_local::date between '".$fecha."-01'::date and ('".$fecha."-01'::date + interval '1 month' - interval '1 day')
                    and LS.id_carrier in (1,2)
                    group by 1,2 order by 1,2
                ) S2 on S2.id_promocion = S1.id_promocion
                left join (
                    select 'SNT'::varchar(20) as canal, IP.alias, LS.id_promocion, LS.accion, count(LS.*)::integer as cantidad
                    from promosuscripcion.log_suscriptos LS
                    left join (select id_promocion, alias from info_promociones group by 1,2 order by 1,2) IP on IP.id_promocion = LS.id_promocion
                    where LS.ts_local::date = '".$fecha_completa."'::date
                    and LS.id_promocion in(17,22,26,37,32,34,40,48,49,52) and LS.id_carrier in (1,2)
                    group by 1,2,3,4
                    union
                    select 'TELEFUTURO'::varchar(20) as canal, IP.alias, LS.id_promocion, LS.accion, count(LS.*)::integer as cantidad
                    from promosuscripcion.log_suscriptos LS
                    left join (select id_promocion, alias from info_promociones group by 1,2 order by 1,2) IP on IP.id_promocion = LS.id_promocion
                    where LS.ts_local::date = '".$fecha_completa."'::date
                    and LS.id_promocion in(20,23,27,38,31,41,47,50,51,54,56) and LS.id_carrier in (1,2)
                    group by 1,2,3,4
                    order by 1,2,3
                ) S3 on S3.id_promocion = S1.id_promocion and S3.accion = S2.accion
                where S1.id_promocion is not null
                order by alias ASC";
        /*$sql = "select S1.*, S2.suscriptos, S3.cantidad as cantidad_hoy
                from (
                    select S.id_promocion, count(S.*)::integer as suscriptos
                    from promosuscripcion.suscriptos S
                    where S.id_promocion in(17,22,26,37,32,40,20,23,27,38,31,34,41,47,48,49,50,51,52,54) and S.id_carrier in(1,2)
                    group by 1 order by 1
                ) S2 left join (
                    select 'SNT'::varchar(20) as canal, IP.alias, LS.id_promocion, LS.accion, count(LS.*)::integer as cantidad
                    from promosuscripcion.log_suscriptos LS
                    left join (select id_promocion, alias from info_promociones group by 1,2 order by 1,2) IP on IP.id_promocion = LS.id_promocion
                    where LS.ts_local::date between '".$fecha."-01'::date and ('".$fecha."-01'::date + interval '1 month' - interval '1 day')
                    and LS.id_promocion in(17,22,26,37,32,34,40,48,49,52) and LS.id_carrier in (1,2)
                    group by 1,2,3,4
                    union
                    select 'TELEFUTURO'::varchar(20) as canal, IP.alias, LS.id_promocion, LS.accion, count(LS.*)::integer as cantidad
                    from promosuscripcion.log_suscriptos LS
                    left join (select id_promocion, alias from info_promociones group by 1,2 order by 1,2) IP on IP.id_promocion = LS.id_promocion
                    where LS.ts_local::date between '".$fecha."-01'::date and ('".$fecha."-01'::date + interval '1 month' - interval '1 day')
                    and LS.id_promocion in(20,23,27,38,31,41,47,50,51,54) and LS.id_carrier in (1,2)
                    group by 1,2,3,4
                    order by 1,2,3

                ) S1 on S1.id_promocion = S2.id_promocion
                left join (
                    select 'SNT'::varchar(20) as canal, IP.alias, LS.id_promocion, LS.accion, count(LS.*)::integer as cantidad
                    from promosuscripcion.log_suscriptos LS
                    left join (select id_promocion, alias from info_promociones group by 1,2 order by 1,2) IP on IP.id_promocion = LS.id_promocion
                    where LS.ts_local::date = '".$fecha_completa."'::date
                    and LS.id_promocion in(17,22,26,37,32,34,40,48,49,52) and LS.id_carrier in (1,2)
                    group by 1,2,3,4
                    union
                    select 'TELEFUTURO'::varchar(20) as canal, IP.alias, LS.id_promocion, LS.accion, count(LS.*)::integer as cantidad
                    from promosuscripcion.log_suscriptos LS
                    left join (select id_promocion, alias from info_promociones group by 1,2 order by 1,2) IP on IP.id_promocion = LS.id_promocion
                    where LS.ts_local::date = '".$fecha_completa."'::date
                    and LS.id_promocion in(20,23,27,38,31,41,47,50,51,54) and LS.id_carrier in (1,2)
                    group by 1,2,3,4
                    order by 1,2,3
                ) S3 on S3.id_promocion = S1.id_promocion and S3.accion = S1.accion
                where S1.id_promocion is not null
                order by 1,2,3";*/

        $rs = $db->fetchAll($sql);

        foreach($rs as $fila) {
            $resultado[] = (array)$fila;
        }

        return $resultado;
    }

    private function _cargarFilasCobrosPautas($fecha_completa) {

        $resultado = array();

        $anho = substr( $fecha_completa, 0, 4 );
        $mes = substr( $fecha_completa, 5, 2 );

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
                    where RM.id_carrier IN(1,2) and RM.numero = '6767' and RM.id_promocion IN(17,22,26,37,32,40,20,23,27,38,31,34,41,47,48,49,50,51,52,54,56)
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
                    where RM.id_carrier IN(1,2) and RM.numero = '6767' and RM.id_promocion IN(17,22,26,37,32,40,20,23,27,38,31,34,41,47,48,49,50,51,52,54,56) and RM.fecha = ?
                    group by 1,2,3,4,5
                    order by 1,2,3,4
                ) S1 left join info_promociones IP on IP.id_promocion = S1.id_promocion and IP.id_carrier = S1.id_carrier
                ) S2
                ) S3 group by 1,2,3 order by 1,2

                ) S5 on S5.numero = S4.numero and S5.id_promocion = S4.id_promocion";

        $rs = $db->fetchAll($sql, array($anho, $mes, $anho, $mes,$fecha_completa));

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

            'GANAR' => 'SNT',
            'LLENAR' => 'TELEFUTURO',//CAMBIE EL 2013-06-03 DE SNT A TELEFUTURO
            'CUENTAS' => 'SNT',
            'COMPRAR' => 'SNT',

            'USA' => 'TELEFUTURO',
            'VENADO' => 'TELEFUTURO',
            'VIDA' => 'TELEFUTURO',
            'SEXO' => 'TELEFUTURO',
            'ORAR' => 'TELEFUTURO',
            'SALDO' => 'TELEFUTURO',
            'CARGAR' => 'TELEFUTURO',
            'PAGAR' => 'TELEFUTURO',
            'SUPER' => 'TELEFUTURO',

            'DINERO' => 'SNT',
            'FORTUNA' => 'TELEFUTURO',
            'CEL'=>'TELEFUTURO',

        );
        $servicios_accion = array(

            'CUERNOS' => array('ALTA' => 0 ,'BAJA' => 0 ),
            'INGLES' => array('ALTA' => 0 ,'BAJA' => 0 ),
            'SALUD' => array('ALTA' => 0 ,'BAJA' => 0 ),
            'PAREJA' => array('ALTA' => 0 ,'BAJA' => 0 ),
            'SANTO' => array('ALTA' => 0 ,'BAJA' => 0 ),

            'GANAR' => array('ALTA' => 0 ,'BAJA' => 0 ),
            'LLENAR' => array('ALTA' => 0 ,'BAJA' => 0 ),
            'CUENTAS' => array('ALTA' => 0 ,'BAJA' => 0 ),
            'COMPRAR' => array('ALTA' => 0 ,'BAJA' => 0 ),

            'USA' => array('ALTA' => 0 ,'BAJA' => 0 ),
            'VENADO' => array('ALTA' => 0 ,'BAJA' => 0 ),
            'VIDA' => array('ALTA' => 0 ,'BAJA' => 0 ),
            'SEXO' => array('ALTA' => 0 ,'BAJA' => 0 ),
            'ORAR' => array('ALTA' => 0 ,'BAJA' => 0 ),

            'SALDO' => array('ALTA' => 0 ,'BAJA' => 0 ),
            'CARGAR' => array('ALTA' => 0 ,'BAJA' => 0 ),
            'PAGAR' => array('ALTA' => 0 ,'BAJA' => 0 ),
            'SUPER' => array('ALTA' => 0 ,'BAJA' => 0 ),

            'DINERO' => array('ALTA' => 0 ,'BAJA' => 0 ),
            'FORTUNA' => array('ALTA' => 0 ,'BAJA' => 0 ),
            'CEL' => array('ALTA' => 0 ,'BAJA' => 0 ),

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
        /*print_r($filasPautas);
        exit;*/
        $filasCobrosPautas = $this->_cargarFilasCobrosPautas($fecha_seleccionada);
        //$this->log->info("filasCobrosPautas:[" . print_r($filasCobrosPautas, true) . ']');
        //print_r($filasCobrosPautas);
        //exit;

        $columna_suscriptos_ya_utilizada = array();

        foreach($filasPautas as $fila) {

            //agregado

            if( !isset($canales[$fila['canal']]['datos'][$fila['alias']])){
                $canales[$fila['canal']]['datos'][$fila['alias']] = array(
                    'ALTA_HOY' => 0,
                    'BAJA_HOY' => 0,
                    'COBROS_HOY' => isset($filasCobrosPautas[$fila['id_promocion']]) ? $filasCobrosPautas[$fila['id_promocion']]['total_cobros_hoy'] : 0,
                    'NETO_HOY' => isset($filasCobrosPautas[$fila['id_promocion']]) ? $filasCobrosPautas[$fila['id_promocion']]['total_neto_gs_hoy'] : 0,
                    'ALTA' => 0,
                    'BAJA' => 0,
                    'COBROS' => isset($filasCobrosPautas[$fila['id_promocion']]) ? $filasCobrosPautas[$fila['id_promocion']]['total_cobros'] : 0,
                    'NETO' => isset($filasCobrosPautas[$fila['id_promocion']]) ? $filasCobrosPautas[$fila['id_promocion']]['total_neto_gs'] : 0,
                    'SUSCRIPTOS' => $fila['suscriptos']
                );
            }

            //if( !isset($canales[$fila['canal']]['datos'][$fila['alias']][$fila['accion']] ) ) {

            if( isset( $servicios_accion[ $fila[ 'alias' ]][$fila[ 'accion' ] ] ) ){
                //entra una vez y se unsetea

                unset( $servicios[ $fila[ 'alias' ]]);
                unset( $servicios_accion[$fila[ 'alias' ]][$fila[ 'accion' ]]);


                /*$canales[$fila['canal']]['datos'][$fila['alias']] = array(
                    'ALTA_HOY' => 0,
                    'BAJA_HOY' => 0,
                    'COBROS_HOY' => $filasCobrosPautas[$fila['id_promocion']]['total_cobros_hoy'],
                    'NETO_HOY' => $filasCobrosPautas[$fila['id_promocion']]['total_neto_gs_hoy'],
                    'ALTA' => 0,
                    'BAJA' => 0,
                    'COBROS' => $filasCobrosPautas[$fila['id_promocion']]['total_cobros'],
                    'NETO' => $filasCobrosPautas[$fila['id_promocion']]['total_neto_gs'],
                    'SUSCRIPTOS' => 0
                );*/

                $canales[$fila['canal']]['totales']['TOTAL_COBROS_HOY'] += (isset($filasCobrosPautas[$fila['id_promocion']]) ? $filasCobrosPautas[$fila['id_promocion']]['total_cobros_hoy'] : 0);
                $canales[$fila['canal']]['totales']['TOTAL_NETO_HOY'] += (isset($filasCobrosPautas[$fila['id_promocion']]) ? $filasCobrosPautas[$fila['id_promocion']]['total_neto_gs_hoy'] : 0);

                $canales[$fila['canal']]['totales']['TOTAL_COBROS'] += (isset($filasCobrosPautas[$fila['id_promocion']]) ? $filasCobrosPautas[$fila['id_promocion']]['total_cobros'] : 0);
                $canales[$fila['canal']]['totales']['TOTAL_NETO'] += (isset($filasCobrosPautas[$fila['id_promocion']]) ? $filasCobrosPautas[$fila['id_promocion']]['total_neto_gs'] : 0);

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

            //}

        }


        //seteo los valores de servicios
        foreach($filasPautas as $fila) {
            foreach( $servicios as $alias => $canal ){
                if($alias == $fila['alias'] && $fila['accion'] == 'NINGUNA' ){
                    $canales[ $canal ][ 'datos' ][ $alias ] = array(

                        'ALTA_HOY' => 0,
                        'BAJA_HOY' => 0,
                        'COBROS_HOY' => isset($filasCobrosPautas[$fila['id_promocion']]) ? $filasCobrosPautas[$fila['id_promocion']]['total_cobros_hoy'] : 0,
                        'NETO_HOY' => 0,
                        'ALTA' => 0,
                        'BAJA' => 0,
                        'COBROS' => isset($filasCobrosPautas[$fila['id_promocion']]) ? $filasCobrosPautas[$fila['id_promocion']]['total_cobros'] : 0,
                        'NETO' => isset($filasCobrosPautas[$fila['id_promocion']]) ? $filasCobrosPautas[$fila['id_promocion']]['total_neto_gs'] : 0,
                        'SUSCRIPTOS' => $fila['suscriptos'],
                    );
                }
            }
        }
        /*foreach( $servicios as $alias => $canal ){

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

        }*/

        $this->view->canales = $canales;

    }

    private function _cargarFilasUssd( $fecha_completa ) {

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
                    where S.id_promocion in(45,63) and S.id_carrier in(2)
                    group by 1 order by 1
                ) S2 left join (
                    select 'USSD'::varchar(20) as canal, IP.alias, LS.id_promocion, LS.accion, count(LS.*)::integer as cantidad
                    from promosuscripcion.log_suscriptos LS
                    left join (select id_promocion, alias from info_promociones group by 1,2 order by 1,2) IP on IP.id_promocion = LS.id_promocion
                    where LS.ts_local::date between '".$fecha."-01'::date and ('".$fecha."-01'::date + interval '1 month' - interval '1 day')
                    and LS.id_promocion in(45,63) and LS.id_carrier in (2)
                    group by 1,2,3,4
                    order by 1,2,3

                ) S1 on S1.id_promocion = S2.id_promocion
                left join (
                    select 'USSD'::varchar(20) as canal, IP.alias, LS.id_promocion, LS.accion, count(LS.*)::integer as cantidad
                    from promosuscripcion.log_suscriptos LS
                    left join (select id_promocion, alias from info_promociones group by 1,2 order by 1,2) IP on IP.id_promocion = LS.id_promocion
                    where LS.ts_local::date = '".$fecha_completa."'::date
                    and LS.id_promocion in(45,63) and LS.id_carrier in (2)
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

    private function _cargarFilasCobrosUssd($anho, $mes) {

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
                    where RM.id_carrier IN(2) and RM.numero = '6767' and RM.id_promocion IN(45,63)
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
                    where RM.id_carrier IN(2) and RM.numero = '6767' and RM.id_promocion IN(45,63) and RM.fecha = current_date
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

    public function ussdAction(){

        $this->view->headLink()->appendStylesheet('/css/reportes_ussd.css', 'screen');
        //agregado para probar
        $this->view->headScript()->appendFile('http://code.jquery.com/ui/1.10.0/jquery-ui.js', 'text/javascript');
        $this->view->headLink()->appendStylesheet('http://code.jquery.com/ui/1.10.0/themes/base/jquery-ui.css');
        $this->view->headScript()->appendFile('/js/reportes_ussd.js', 'text/javascript');

        //despliego el titulo
        $this->view->headTitle()->append('Control USSD');

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

            'CARRITO' => 'USSD',
            'OSCAR' =>  'USSD'

        );
        $servicios_accion = array(

            'CARRITO' => array('ALTA' => 0 ,'BAJA' => 0 ),
            'OSCAR' => array('ALTA' => 0 ,'BAJA' => 0 ),
        );

        /*agregado*/
        $canales = array(
            'USSD'=>array(
                'descripcion' => 'Reporte - USSD',
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
                'css_titulo' => "fondo_titulo_USSD"
            ),
        );
        //$filasPautas = array();
        $filasPautas = $this->_cargarFilasUssd($fecha_seleccionada);

        //$filasCobrosPautas = array();
        $filasCobrosPautas = $this->_cargarFilasCobrosUssd($anho, $mes);

        $columna_suscriptos_ya_utilizada = array();


        foreach($filasPautas as $fila) {

            //agregado

            if( !isset($canales[$fila['canal']]['datos'][$fila['alias']])){

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
            }

            //if( !isset($canales[$fila['canal']]['datos'][$fila['alias']][$fila['accion']] ) ) {

            if( isset( $servicios_accion[ $fila[ 'alias' ]][$fila[ 'accion' ] ] ) ){
                //entra una vez y se unsetea

                unset( $servicios[ $fila[ 'alias' ]]);
                unset( $servicios_accion[$fila[ 'alias' ]][$fila[ 'accion' ]]);

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

        $this->view->canales = $canales;

    }

    private function _cargarFilasInformePautasDetallado( $fecha ){
        global $db;
        $datos = array();
        $config = new Zend_Config(array(
            'database' => array(
                'adapter' => 'Pdo_Pgsql',
                'params'  => array(
                    'host'     => 'localhost',
                    'username' => 'postgres',
                    'password' => '',
                    'dbname'   => 'postgres'
                )
            )
        ));
        $db = Zend_Db::factory($config->database);
        $db->getConnection();

        $sql = 'select servicio, fecha, fecha_hora, canal, pautas_encontradas, nro_pautas from (select * from reportepautas join pautas on servicio = alias)  as reportepautas  where fecha = ?';
        $rs = $db->fetchAll( $sql, $fecha );
        foreach( $rs as $fila ){

            $datos[] = (array)$fila;

        }
        return $datos;
    }

    private function _cargarFilasInformePautas( $fecha ){


        $datos = array();
        $anho = (int)(substr( $fecha, 0, 4 ));
        $mes = (int)(substr( $fecha, 5, 2 ));

       /*$config = new Zend_Config(array(
            'database' => array(
                'adapter' => 'Pdo_Pgsql',
                'params'  => array(
                    'host'     => 'localhost',
                    'username' => 'postgres',
                    'password' => '',
                    'dbname'   => 'postgres'
                )
            )
        ));
        $db = Zend_Db::factory($config->database);
        $db->getConnection();*/
        $bootstrap = $this->getInvokeArg('bootstrap');
        $options = $bootstrap->getOptions();

        $db = Zend_Db::factory(new Zend_Config($options['resources']['db']));
        $db->getConnection();



        $sql = 'select alias, fecha, canal, pautas_encontradas, nro_pautas, (SELECT SUM(pautas_encontradas)
                FROM pautas RP where EXTRACT(YEAR from fecha) = '.$anho.' AND EXTRACT(MONTH from fecha) = ' .$mes. '
                AND RP.alias = RT.alias)::integer as pautas_emitidas_mes, (SELECT SUM(nro_pautas)
                FROM pautas RP where EXTRACT(YEAR from fecha) = '.$anho.' AND EXTRACT(MONTH from fecha) = ' .$mes. '
                AND RP.alias = RT.alias)::integer as pautas_a_emitir_mes from pautas RT where fecha = ?';

        /*$sql = 'select alias, fecha, canal, pautas_encontradas, nro_pautas, (SELECT SUM(pautas_encontradas)
        FROM pautas RP where EXTRACT(YEAR from fecha) = '.$anho.' AND EXTRACT(MONTH from fecha) =' .$mes. ' AND RP.alias = RT.alias
        )::integer as pautas_emitidas_mes from pautas RT where fecha = ?';*/

        $rs = $db->fetchAll( $sql, $fecha );

        foreach( $rs as $fila ){

            $datos[] = (array)$fila;

        }
        return $datos;
    }

    public function informePautasAction(){

        $this->view->headScript()->appendFile('http://code.jquery.com/ui/1.10.0/jquery-ui.js', 'text/javascript');
        $this->view->headLink()->appendStylesheet('http://code.jquery.com/ui/1.10.0/themes/base/jquery-ui.css');
        $this->view->headScript()->appendFile('/js/reportes_informe_pautas.js', 'text/javascript');
        $this->view->headLink()->appendStylesheet('/css/informe_pautas.css', 'screen');

        $this->view->headTitle()->append('Reporte Pautas');

        $fecha_seleccionada = $this->_getParam('fecha', null);
        if(!is_null($fecha_seleccionada)) {

            list($anho, $mes, $dia) = explode('-', $fecha_seleccionada);
            $mes = (int)$mes;
            $dia = (int)$dia;
        } else {

            $fecha_seleccionada = date("Y-m-d", mktime(0, 0, 0, date("m"),date("d")-1,date("Y")));
        }
        $this->view->fecha = $fecha_seleccionada;
        /*$this->_setupRangoSeleccion($anho, $mes);

        $this->view->nombre_mes = $this->meses[$mes-1];

        // Te da la cantidad de dias del mes y anho
        $this->view->cantidad_dias = date('t', mktime(0, 0, 0, $mes, 1, $anho));


        $this->view->anho = $anho;
        $this->view->mes = $mes;

        $this->view->dia_hoy = date('j');

        $this->view->dias_semana = $this->dias_semana;

        $this->view->nombres_dias_del_mes = $this->cargarNombresDiasDelMes($anho, $mes);

        $this->view->rango_seleccion = $this->rango_seleccion;*/

        $canales = array(
            'SNT'=>array(
                'descripcion' => 'Reporte Canal 9 - SNT',
                'fecha' => '',
                'servicio' => '',
                'css_titulo' => "fondo_titulo_SNT"
            ),
            'TELEFUTURO' => array(
                'descripcion' => 'Reporte Canal 4 - Telefuturo',
                'datos' => array(),
                'servicio' => '',
                'css_titulo' => 'fondo_titulo_TELEFUTURO'

            )
        );
        //obtengo los datos de la base de datos (tabla pautas)
        $datos_BD_pautas = $this->_cargarFilasInformePautas( $fecha_seleccionada );
        $canales_uno  = array();
        foreach( $datos_BD_pautas as $fila ){

            $canales_uno[$fila['canal']]['datos'][$fila['alias']]['servicio'] = $fila['alias'];
            $canales_uno[$fila['canal']]['datos'][$fila['alias']]['fecha'] = $fila['fecha'];
            $canales_uno[$fila['canal']]['datos'][$fila['alias']]['pautas_encontradas'] = $fila['pautas_encontradas'];
            $canales_uno[$fila['canal']]['datos'][$fila['alias']]['nro_pautas'] = $fila['nro_pautas'];
            $canales_uno[$fila['canal']]['datos'][$fila['alias']]['pautas_emitidas_mes'] = $fila['pautas_emitidas_mes'];
            $canales_uno[$fila['canal']]['datos'][$fila['alias']]['pautas_a_emitir_mes'] = $fila['pautas_a_emitir_mes'];
            $canales_uno[$fila['canal']]['css_titulo'] = 'fondo_titulo_'.$fila['canal'];
            $canales_uno[$fila['canal']]['descripcion'] = 'Reporte Canal - '.$fila['canal'];

        }

        /*$canales_dos  = array();
        $datos_BD_reportepautas = $this->_cargarFilasInformePautasDetallado( $fecha_seleccionada );
        foreach ( $datos_BD_reportepautas as $fila ){

            $canales_dos[$fila['canal']]['servicio'] = $fila['servicio'];
            $canales_dos[$fila['canal']]['datos'][] = $fila['fecha_hora'];
            $canales_dos[$fila['canal']]['css_titulo'] = 'fondo_titulo_'.$fila['canal'];
            $canales_dos[$fila['canal']]['descripcion'] = 'Reporte Canal - '.$fila['canal'];
        }*/
        $this->view->canales = $canales_uno;
        //$this->view->canales_dos = $canales_dos;

    }

    private function _cargarAliasMostrar(){

        global $db;
        $alias = array();

        //se incluye el php que contiene los metodos

        $config = new Zend_Config(array(
            'database' => array(
                'adapter' => 'Pdo_Pgsql',
                'params'  => array(
                    'host'     => '190.128.183.138',
                    'username' => 'konectagw',
                    'password' => 'konectagw2010',
                    'dbname'   => 'gw'
                )
            )
        ));

        $db = Zend_Db::factory($config->database);
        $db->getConnection();
        //traemos los datos de los contenidos que vamos a cargar para mirar
        $sql = 'select nombre_alias from contenidoalias';

        $rs = $db->fetchAll( $sql );

        foreach( $rs as $fila ){

            $alias[] = (array)$fila;

        }

        return $alias;
    }

    private function _cargarFilasInformeContenidosBD( $alias ){

        global $db;
        $datos = array();

        //se incluye el php que contiene los metodos
        //require_once( "Google_Spreadsheet.php" );

        $config = new Zend_Config(array(
            'database' => array(
                'adapter' => 'Pdo_Pgsql',
                'params'  => array(
                    'host'     => 'localhost',
                    'username' => 'postgres',
                    'password' => '',
                    'dbname'   => 'postgres'
                )
            )
        ));
        $db = Zend_Db::factory($config->database);
        $db->getConnection();
        //traemos los datos de los contenidos que vamos a COMPARAR
        $sql = 'select * from stockcontenidos where alias = ?';

        $rs = $db->fetchAll( $sql, $alias );

        foreach( $rs as $fila ){

            $datos[] = (array)$fila;

        }
        return $datos;
    }

    private function _cargarFilasInformeContenidos( $alias, $ss ){

        global $db;
        $datos = array();

        //se incluye el php que contiene los metodos
        //require_once( "Google_Spreadsheet.php" );

        $config = new Zend_Config(array(
            'database' => array(
                'adapter' => 'Pdo_Pgsql',
                'params'  => array(
                    'host'     => 'localhost',
                    'username' => 'postgres',
                    'password' => '',
                    'dbname'   => 'postgres'
                )
            )
        ));
        $db = Zend_Db::factory($config->database);
        $db->getConnection();
        //traemos los datos de los contenidos que vamos a cargar para mirar
        $sql = 'select * from contenidoalias where nombre_alias = ?';

        $rs = $db->fetchAll( $sql, $alias );

        foreach( $rs as $fila ){

            $datos[] = (array)$fila;

        }

        //el documento a leer
        foreach( $datos as $servicio ){

            //archivo a leer le pongo en una variable porque no le gusta sino
            $archivo = $servicio['archivo_alias'];
            $ss->useSpreadsheet( "$archivo" );
            //la hoja a leer
            $hoja = $servicio['hoja_alias'];
            $ss->useWorksheet( "$hoja" );
            //Se obtiene el contenido de la hoja por filas y se le asigna al ahora arreglo $rows
            $rows = $ss->getRows();
            //Se valida si el arreglo tiene datos antes de intentar imprimirlo.
            if( empty( $rows ) ){

                echo "El arreglo esta vacio\n";
            }
            else{

                $fp = fopen('ejemplo.txt', 'w');

                foreach( $rows as $indice=>$fila ) {

                    foreach( $fila as $clave=>$valor ){
                        //guardo las filas en archivo txt
                        $guardar =  $valor . "\n";
                        //echo $guardar;
                        fwrite( $fp, $guardar );
                    }
                }
                fclose($fp);
            }
        }

        return $rows;
    }
    //obtener los datos de los contenidos que se pasan como parametro
    public function informeContenidosAction(){

        $this->view->headScript()->appendFile('http://code.jquery.com/ui/1.10.0/jquery-ui.js', 'text/javascript');
        $this->view->headLink()->appendStylesheet('http://code.jquery.com/ui/1.10.0/themes/base/jquery-ui.css');
        //MODIFICAR
        $this->view->headScript()->appendFile('/js/reportes_informe_contenidos.js', 'text/javascript');
        $this->view->headLink()->appendStylesheet('/css/informe_contenidos.css', 'screen');

        $this->view->headTitle()->append('Informe Contenidos');
        $alias_contenidos = array();
        /*CARGO LOS ALIAS PARA FILTRAR*/
        $this->view->alias = $this->_cargarAliasMostrar();

        /*me logeo una vez nomas*/
        $username = "soporte2@entermovil.com.py";
        $password = "derlis360";

        //Se crea objeto de la clase Google_Spreadsheet para acceder
        $ss = new Google_Spreadsheet( $username,$password );

        //SI ALIAS SELECCIONADO ES NULO
        $alias_seleccionado = $this->_getParam('alias', null);

        if( is_null( $alias_seleccionado ) ) {
            //SE MUESTRA POR DEFECTO EL PRIMER ALIAS CARGADO
            $alias_seleccionado = 'PAREJA';//MODIFICFAR DESPUES PARA QUE SEA EL PRIMER ELEMENTO DEL ARRAY ALIAS

        }
        else{
            //SINO SE PASA COMO PARAMETRO EL ALIAS REQUERIDO Y SE CARGA
            //$this->view->nombre_alias = $alias_seleccionado;

        }
        $this->view->nombre_alias = $alias_seleccionado;
        $alias_contenidos = $this->_cargarFilasInformeContenidos($alias_seleccionado, $ss);
        $this->view->contenidos = $alias_contenidos;
        $alias_contenidos_BD = $this->_cargarFilasInformeContenidosBD( $alias_seleccionado );
        $this->view->contenidosBD = $alias_contenidos_BD;

    }//FIN INFORMECONTENIDOSACTION

    private function _cargarProgramacionPautas(){

        global $db;
        $programacion = array();

        //se incluye el php que contiene los metodos

        $bootstrap = $this->getInvokeArg('bootstrap');
        $options = $bootstrap->getOptions();

        $db = Zend_Db::factory(new Zend_Config($options['resources']['db']));
        $db->getConnection();
        /*$config = new Zend_Config(array(
            'database' => array(
                'adapter' => 'Pdo_Pgsql',
                'params'  => array(
                    'host'     => 'localhost',
                    'username' => 'postgres',
                    'password' => '',
                    'dbname'   => 'postgres'
                )
            )
        ));
        $db = Zend_Db::factory($config->database);
        $db->getConnection();*/

        //traemos los datos de los contenidos que vamos a cargar para mirar
        $sql = 'select * from pautas where pautas_encontradas = 0 order by fecha desc';

        $rs = $db->fetchAll( $sql );

        foreach( $rs as $fila ){

            $programacion[] = (array)$fila;

        }

        return $programacion;
    }

    private function _insertarProgramacion( $programacion ){

        global $db;
        //se incluye el php que contiene los metodos

        $bootstrap = $this->getInvokeArg('bootstrap');
        $options = $bootstrap->getOptions();

        $db = Zend_Db::factory(new Zend_Config($options['resources']['db']));
        $db->getConnection();

        /*$config = new Zend_Config(array(
            'database' => array(
                'adapter' => 'Pdo_Pgsql',
                'params'  => array(
                    'host'     => 'localhost',
                    'username' => 'postgres',
                    'password' => '',
                    'dbname'   => 'postgres'
                )
            )
        ));
        $db = Zend_Db::factory($config->database);
        $db->getConnection();*/

        $datos = array(

            'alias' => $programacion['alias'],
            'fecha' => $programacion['fecha'],
            'path_plantilla' => $programacion['path_plantilla'],
            'deltax' => $programacion['deltax'],
            'deltay' => $programacion['deltay'],
            'canal' => $programacion['canal'],
            'nro_pautas' => $programacion['nro_pautas'],
            'duracion' => $programacion['duracion'],
            'pautas_encontradas' => 0,

        );
        $db->insert('pautas',$datos);

    }

    private function _borrarProgramacion( $programación ){

        global $db;
        //se incluye el php que contiene los metodos

        $bootstrap = $this->getInvokeArg('bootstrap');
        $options = $bootstrap->getOptions();

        $db = Zend_Db::factory(new Zend_Config($options['resources']['db']));
        $db->getConnection();

        /*$config = new Zend_Config(array(
            'database' => array(
                'adapter' => 'Pdo_Pgsql',
                'params'  => array(
                    'host'     => 'localhost',
                    'username' => 'postgres',
                    'password' => '',
                    'dbname'   => 'postgres'
                )
            )
        ));
        $db = Zend_Db::factory($config->database);
        $db->getConnection();*/

        $where = array(
            'alias = ?' => $programación['alias'],
            'fecha = ?' => $programación['fecha'],
            'canal = ?' => $programación['canal'],
        );

        $db->delete('pautas',$where);

    }

    public function eliminarPautaAction(){

        $parametros = $this->_getAllParams('alias','fecha','canal',null);
        if(!is_null($parametros)){

            echo 'vamos a eliminar estos parametros';
            print_r($parametros);
            $this->_borrarProgramacion($parametros);
            echo "elementos borrados exitosamente";
            $this->_redirect('/reportes/cargar-bd-pautas');
        }
    }

    private function _cargarAlias(){

        global $db;

        $bootstrap = $this->getInvokeArg('bootstrap');
        $options = $bootstrap->getOptions();
        $db = Zend_Db::factory(new Zend_Config($options['resources']['db']));
        $db->getConnection();

        $sql = 'select * from alias';
        $rs = $db->fetchAll($sql);

        foreach( $rs as $fila ){

            $alias[] = (array)$fila;

        }
        return $alias;
    }

    public function cargarBdPautasAction(){

        $this->view->headLink()->appendStylesheet('/css/informe_pautas.css', 'screen');
        $this->view->headScript()->appendFile('http://code.jquery.com/ui/1.10.0/jquery-ui.js', 'text/javascript');
        $this->view->headLink()->appendStylesheet('http://code.jquery.com/ui/1.10.0/themes/base/jquery-ui.css');
        $this->view->headScript()->appendFile('/js/reportes_informe_pautas_cargar_bd_pautas.js', 'text/javascript');

        $alias_nuevo = $this->_cargarAlias();
        $alias = array();

        foreach( $alias_nuevo as $indice){

            $alias[$indice['servicio']]['nombre_alias'] = $indice['servicio'];
            $alias[$indice['servicio']]['canal'] = $indice['canal'];

        }

        $programacion = array(

            'alias' =>'',
            'fecha' =>'',
            'path_plantilla' =>'',
            'deltax' =>'',
            'deltay' =>'',
            'canal' =>'',
            'nro_pautas' =>'',
            'duracion' =>'',

        );
        $this->view->alias = $alias;

        $alias_seleccionado = $this->_getParam('alias', null);

        if( is_null( $alias_seleccionado ) ) {
            //SE MUESTRA POR DEFECTO EL PRIMER ALIAS CARGADO
            $alias_seleccionado = 'CUERNOS';//MODIFICFAR DESPUES PARA QUE SEA EL PRIMER ELEMENTO DEL ARRAY ALIAS

        }
        else{
            //SINO SE PASA COMO PARAMETRO EL ALIAS REQUERIDO, SE CARGA
            $this->view->nombre_alias = $alias_seleccionado;

        }

        $fecha_seleccionada = $this->_getParam('fecha', null);
        if(!is_null($fecha_seleccionada)) {

            list($anho, $mes, $dia) = explode('-', $fecha_seleccionada);
            $mes = (int)$mes;
            $dia = (int)$dia;
        }
        else{
            //SINO SE PASA COMO PARAMETRO EL ALIAS REQUERIDO Y SE CARGA
            $fecha_seleccionada = date('Y-m-d');

        }

        $this->view->nombre_alias = $alias_seleccionado;
        $this->view->fecha = $fecha_seleccionada;
        //plantilla que vamos a enviar
        $path_plantilla = 'plantilla_'.$alias_seleccionado.'_6767.jpg';
        $this->view->path_plantilla = 'plantilla_'.$alias_seleccionado.'_6767.jpg';
        //veo si anda el post
        $form = new Application_Form_CargarPautas();

        if($this->getRequest()->isPost()) {

            $formData = $this->getRequest()->getPost();

            if($form->isValid($formData)) {

                $deltax = $form->getValue('deltax');
                $deltay = $form->getValue('deltay');
                $nropautas = $form->getValue('nropautas');
                $duracion = $form->getValue('duracion');

                if(!empty($deltax) && !empty($deltay) && !empty($nropautas) && !empty($duracion)) {

                    $programacion['alias'] = $alias_seleccionado;
                    $programacion['fecha'] = $fecha_seleccionada;
                    $programacion['path_plantilla'] = 'plantilla_'.$alias_seleccionado.'_6767.jpg';
                    $programacion['deltax'] = $deltax;
                    $programacion['deltay'] = $deltay;
                    $programacion['canal'] = $alias[$alias_seleccionado]['canal'];
                    $programacion['nro_pautas'] = $nropautas;
                    $programacion['duracion'] = $duracion;
                    try{

                        $this->_insertarProgramacion($programacion);
                        $mensaje =  null;
                        $this->view->mensaje = $mensaje;

                    }catch(Exception $e){

                        $mensaje =  "Clave duplicada";
                        $this->view->mensaje = $mensaje;
                    }

                } else {

                    $this->_redirect('/reportes/cargar-bd-pautas');
                }

            } else {

                $this->_redirect('/reportes/cargar-bd-pautas/alias/'.$alias_seleccionado.'/fecha/'.$fecha_seleccionada);
            }
        }
        $this->view->programacion = $this->_cargarProgramacionPautas();

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

        $sql = "SELECT * FROM info_promociones WHERE numero IN('6767', '35500', '965', '10130') and id_carrier in(1,2) ORDER BY numero, id_promocion";
        $rs_info_promociones = $db->fetchAll($sql);
        foreach($rs_info_promociones as $info_promocion) {

            if($info_promocion['numero'] == '6767') {//ALERTAS DE TEXTO

                if($info_promocion['id_carrier'] == 2) {//TIGO
                    $porcentaje_enter = 0.35;//35%
                    //agregado
                    if($anho == 2013){
                        $porcentaje_enter = 0.30;//35%
                    }else if(($anho == 2012 && $mes >= 11) || $anho > 2012) {
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

            } else if($info_promocion['numero'] == '965') {
                if($info_promocion['id_carrier'] == 2) {//TIGO
                    $porcentaje_enter = 0.30;//30%
                } else if($info_promocion['id_carrier'] == 1) {//PERSONAL
                    $porcentaje_enter = 0.35;//35%
                }
            } else if($info_promocion['numero'] == '10130') {
                if($info_promocion['id_carrier'] == 2) {//TIGO
                    $porcentaje_enter = 0.30;//30%
                } else if($info_promocion['id_carrier'] == 1) {//PERSONAL
                    $porcentaje_enter = 0.35;//35%
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

        //print_r($promociones); exit;

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
                $porcentaje['965']['ENTER'] = 30;
                $porcentaje['10130']['ENTER'] = 30;

                if(($anho==2012 && $mes>=11) || $anho > 2012) {
                    $porcentaje['6767']['ENTER'] = 30;//este modifique
                }

            } else if($id_carrier == 1) {//PERSONAL

                $porcentaje['6767']['ENTER'] = 30;
                $porcentaje['35500']['ENTER'] = 40;
                $porcentaje['965']['ENTER'] = 35;
                $porcentaje['10130']['ENTER'] = 35;
            }

            $porcentaje['6767']['OTROS'] = 100 - $porcentaje['6767']['ENTER'];
            $porcentaje['35500']['OTROS'] = 100 - $porcentaje['35500']['ENTER'];
            $porcentaje['965']['OTROS'] = 100 - $porcentaje['965']['ENTER'];
            $porcentaje['10130']['OTROS'] = 100 - $porcentaje['10130']['ENTER'];

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

            $this->log->info('rs_cobros:[' . print_r($rs_cobros, true) . ']');




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

    public function resumenCobrosMexicoAction() {

        $this->view->headLink()->appendStylesheet('/css/reportes_resumen.css', 'screen');
        //agregado para probar
        $this->view->headScript()->appendFile('http://code.jquery.com/ui/1.10.0/jquery-ui.js', 'text/javascript');
        $this->view->headLink()->appendStylesheet('http://code.jquery.com/ui/1.10.0/themes/base/jquery-ui.css');
        $this->view->headScript()->appendFile('/js/reportes_resumen.js', 'text/javascript');

        $this->view->headTitle()->append('Resumen Cobros MEXICO');

        $this->view->id_pais = 5;
        $this->view->nombre_pais = "México";


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
            5 => 'TIGO_GUATEMALA', 6 => 'TIGO_BOLIVIA', 7 => 'TELCEL_MEXICO'
        );
        $namespace = new Zend_Session_Namespace("entermovil");

        if(isset($namespace->id_pais)) {

            $this->view->mostrar_lista_paises = false;
            $id_pais = $namespace->id_pais;
            $this->view->id_pais = $id_pais;
            $this->view->nombre_pais = "México";

        } else {

            $this->view->mostrar_lista_paises = true;
            $this->view->paises = $this->_cargarPaisesConPromociones();
            $id_pais = $this->_getParam('pais', 1);
            $this->view->id_pais = $id_pais;
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
        $this->_setupRangoSeleccion($anho, $mes);
        $this->view->nombre_mes = $this->meses[$mes-1];
        $this->view->cantidad_dias = date('t', mktime(0, 0, 0, $mes, 1, $anho));
        $this->view->anho = $anho;
        $this->view->mes = $mes;
        $this->view->dia_hoy = date('j');
        $this->view->dias_semana = $this->dias_semana;
        $this->view->nombres_dias_del_mes = $this->cargarNombresDiasDelMes($anho, $mes);
        $this->view->rango_seleccion = $this->rango_seleccion;
        $carriers_y_numeros = $this->_cargarCarriersYNumeros( $id_pais, $fecha_seleccionada );
        $this->view->carriers_numeros = $carriers_y_numeros;
        $promociones_carriers_numeros = $this->_cargarPromocionesCarrierNumero($id_pais);
        $this->view->promociones_carriers_numeros = $promociones_carriers_numeros;

        $total_sumatoria = array(

            'TOTAL_COBROS'=>0,
            'TOTAL_BRUTO'=>0,
            'TOTAL_NETO'=>0,
            'decimal' => false,
            'TOTAL_SUSCRIPTOS' => 0
        );

        $totales_promociones_carriers_numeros = array();

        foreach($carriers_y_numeros as $carrier_numero) {

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

                $totales[$carrier_numero['id_carrier']][$carrier_numero['numero']][$promocion_carrier_numero['id_promocion']]['costo'] = ($promocion_carrier_numero['costo_gs'] == 0 ? $promocion_carrier_numero['costo_usd'] : $promocion_carrier_numero['costo_gs']);
                $totales[$carrier_numero['id_carrier']][$carrier_numero['numero']][$promocion_carrier_numero['id_promocion']]['decimal'] = ($promocion_carrier_numero['costo_gs'] == 0 ? true : false);

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

            $total_sumatoria['TOTAL_COBROS'] += $sumatoria[$carrier_numero['id_carrier']][$carrier_numero['numero']]['TOTAL_COBROS'];

            $total_sumatoria['TOTAL_BRUTO'] += $sumatoria[$carrier_numero['id_carrier']][$carrier_numero['numero']]['TOTAL_BRUTO'];

            $total_sumatoria['TOTAL_NETO'] += $sumatoria[$carrier_numero['id_carrier']][$carrier_numero['numero']]['TOTAL_NETO'];

            $total_sumatoria['TOTAL_SUSCRIPTOS'] += $sumatoria[$carrier_numero['id_carrier']][$carrier_numero['numero']]['TOTAL_SUSCRIPTOS'];

            $total_sumatoria['decimal'] = $totales[$carrier_numero['id_carrier']][$carrier_numero['numero']][$promocion_carrier_numero['id_promocion']]['decimal'];

        }

        $this->view->total_sumatoria = $total_sumatoria;
        $this->view->totales = $totales;
        $this->view->sumatoria = $sumatoria;
        $this->log->info('totales:[' . print_r($totales, true) . ']');

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

    public function wapAction(){

        $this->view->headLink()->appendStylesheet('/css/reportes_wap.css', 'screen');
        //agregado para probar
        $this->view->headScript()->appendFile('http://code.jquery.com/ui/1.10.0/jquery-ui.js', 'text/javascript');
        $this->view->headLink()->appendStylesheet('http://code.jquery.com/ui/1.10.0/themes/base/jquery-ui.css');
        $this->view->headScript()->appendFile('/js/reportes_wap.js', 'text/javascript');
        $this->view->headTitle()->append('Wap');
        $this->_helper->_layout->setLayout('reporte-wap-layout');

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
                    foreach( $datos_nivel as $nivel => $suscriptos_x_nivel ){

                        $suma_suscriptos_id_carrier = $suma_suscriptos_id_carrier + $suscriptos_x_nivel['suscriptos'];
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
                    foreach( $datos_nivel as $nivel => $suscriptos_x_nivel ){

                        $suma_suscriptos_id_carrier = $suma_suscriptos_id_carrier + $suscriptos_x_nivel['suscriptos'];
                    }
                    $totales[$id_carrier] = $suma_suscriptos_id_carrier;
                    $total_general = $total_general + $suma_suscriptos_id_carrier;
                }

                $this->view->suscriptos = $suscriptos;
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
        }
    }

    public function contenidosWapAction(){

        $this->view->headLink()->appendStylesheet('/css/reportes_wap.css', 'screen');
        //agregado para probar
        $this->view->headScript()->appendFile('http://code.jquery.com/ui/1.10.0/jquery-ui.js', 'text/javascript');
        $this->view->headLink()->appendStylesheet('http://code.jquery.com/ui/1.10.0/themes/base/jquery-ui.css');
        $this->view->headScript()->appendFile('/js/reportes_wap.js', 'text/javascript');
        $this->view->headTitle()->append('Wap');
        $this->_helper->_layout->setLayout('reporte-wap-layout');

        $cantidad_contenidos = 0;
        $this->view->promociones = $this->promociones;

        $id_promocion = $this->_getParam( 'id-promocion', null );

        if( !is_null( $id_promocion ) ){

            $datos = array( 'id_promocion' => $id_promocion );
            $this->view->promocion = $id_promocion;

            $contenidos_x_nivel = $this->_consulta( 'OBTENER_CANTIDAD_CONTENIDOS_X_NIVEL',  $datos );

            if( !is_null( $contenidos_x_nivel ) ){

                /*foreach( $contenidos_x_nivel as $indice=> $datos ){

                    $cantidad_contenidos += $contenidos_x_nivel[$indice]['contenidos'];

                    if( $indice == 0 ){
                        //nivel 1
                        $contenidos_x_nivel[$indice]['contenidos']+= 0;
                    }else{
                        //niveles superiores
                        $contenidos_x_nivel[$indice]['contenidos'] += $contenidos_x_nivel[$indice-1]['contenidos'];
                    }
                }
                echo $cantidad_contenidos;
                print_r( $contenidos_x_nivel );exit;
                $this->view->cantidad_contenidos = $cantidad_contenidos;*/

                $this->view->contenidos_x_nivel = $contenidos_x_nivel;

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

    public function suscriptosXTipoAction(){

        $this->view->headLink()->appendStylesheet('/css/reportes_suscriptos_x_tipo.css', 'screen');
        $this->view->headScript()->appendFile('http://code.jquery.com/ui/1.10.0/jquery-ui.js', 'text/javascript');
        $this->view->headLink()->appendStylesheet('http://code.jquery.com/ui/1.10.0/themes/base/jquery-ui.css');
        $this->view->headScript()->appendFile('/js/reportes_wap.js', 'text/javascript');
        $this->view->headTitle()->append('suscriptos-x-tipo');
        //$this->_helper->_layout->setLayout('reporte-wap-layout');

        $this->view->promociones = $this->id_promocion_x_tipo;

        $id_promocion = $this->_getParam( 'id-promocion' , null );

        if( !is_null( $id_promocion ) ){

            $datos = array(

                'id_promocion' => $id_promocion,
            );

            $datos_a_mostrar = array();
            $cantidad_suscriptos_a_promociones = 0;

            $datos_a_obtenidos = $this->_consulta( 'GET_CANTIDAD_SUSCRIPTOS_X_TIPO' , $datos );

            foreach( $datos_a_obtenidos as $indice => $datos ){

                if( $indice != 0 ){

                    $datos_a_mostrar[] = $datos;
                    $cantidad_suscriptos_a_promociones += $datos_a_obtenidos[$indice]['cantidad'];

                }else{
                    //personas no suscriptas a ninunga promocion
                    $datos['texto'] = 'Ninguna Opcion Seleccionada';
                    $datos_a_mostrar[] = $datos;
                }

            }

            $total_suscriptos = $datos_a_mostrar[0]['cantidad'];
            $datos_a_mostrar[0]['cantidad'] -= $cantidad_suscriptos_a_promociones;

            $this->view->contenidos_x_nivel = $datos_a_mostrar;
            $this->view->total_suscriptos = $total_suscriptos;
            /*print_r( $datos_a_mostrar );
            exit;*/
        }else{

            $datos = array(

                'id_promocion' => 73,
            );

            $datos_a_mostrar = array();
            $cantidad_suscriptos_a_promociones = 0;

            $datos_a_obtenidos = $this->_consulta( 'GET_CANTIDAD_SUSCRIPTOS_X_TIPO' , $datos );

            foreach( $datos_a_obtenidos as $indice => $datos_cargar ){

                if( $indice != 0 ){

                    $datos_a_mostrar[] = $datos_cargar;
                    $cantidad_suscriptos_a_promociones += $datos_a_obtenidos[$indice]['cantidad'];

                }else{
                    //personas no suscriptas a ninunga promocion
                    $datos['texto'] = 'Ninguna Opcion Seleccionada';
                    $datos_a_mostrar[] = $datos_cargar;
                }

            }

            $total_suscriptos = $datos_a_mostrar[0]['cantidad'];
            $datos_a_mostrar[0]['cantidad'] -= $cantidad_suscriptos_a_promociones;

            $this->view->contenidos_x_nivel = $datos_a_mostrar;
            $this->view->total_suscriptos = $total_suscriptos;

            //otra tabla canales activados
            $datos_obtenidos = $this->_consulta( 'GET_CANTIDAD_SUCRIPTOS_X_OPCION', $datos );
            $this->view->contenidos_x_opcion = $datos_obtenidos;

        }
    }

    private function _consulta( $accion, $datos = null ){

        $bootstrap = $this->getInvokeArg('bootstrap');
        $options = $bootstrap->getOptions();

        $db = Zend_Db::factory(new Zend_Config($options['resources']['db']));
        $db->getConnection();
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

        if( $accion == 'GET_SUSCRIPTOS_POR_PROMOCION' ){

            $sql = "select count(*) as suscriptos, t1.id_carrier, t2.nivel from promosuscripcion.suscriptos as t1, wap.usuarios as t2
                    where t1.id_promocion = ? and t1.cel = t2.cel group by t1.id_carrier, t2.nivel order by nivel";
            $rs = $db->fetchAll( $sql, array( $datos['id_promocion'] ) );

            if( !empty( $rs ) ){

                $resultado = array();
                foreach( $rs as $fila ){

                    $resultado[$this->carriers_wap[$fila['id_carrier']]][$fila['nivel']] = (array)$fila;
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
        if( $accion == 'OBTENER_CANTIDAD_CONTENIDOS_X_NIVEL' ){

            $sql = "select suscriptos, T1.nivel, contenidos from (
                        select count(*)::integer as suscriptos, T.nivel from ( select S.cel, S.id_carrier, U.nivel
                        from promosuscripcion.suscriptos S
                        left join wap.usuarios U on U.cel= S.cel and U.id_carrier = S.id_carrier
                        where S.id_promocion = ?
                        and S.id_carrier in( 1,2,5 )
                        and U.cel is not null )
                        T group by T.nivel )
                    as T1 left join ( select count(*)::integer as contenidos, nivel from wap.contenidos C where C.id_promocion = ? group by C.nivel ) as T2
                    on T2.nivel = T1.nivel order by nivel, suscriptos asc";

            $rs = $db->fetchAll( $sql, array( $datos['id_promocion'], $datos['id_promocion'] ) );

            if( !empty( $rs ) ){

                $resultado = array();
                foreach( $rs as $fila ){

                    //$resultado[$fila['nivel']][] = (array)$fila;
                    $resultado[] = (array)$fila;
                }

                return $resultado;

            }else{

                return null;
            }
        }
        if( $accion == 'GET_CANTIDAD_SUSCRIPTOS_X_TIPO' ){

            /*$sql = "select count(*)::integer as cantidad, T1.tipo, T2.texto from (
                    select A.cel, A.tipo from ( select P.cel, P.tipo from promosuscripcion.promo_menu_multiple P where P.id_promocion = ?
                    and P.activo = 1 ) A
                    join ( select S.cel from promosuscripcion.suscriptos S where id_promocion = ? ) B on A.cel = B.cel order by A.cel, A.tipo
                    ) T1 left join
                    ( select P.nivel as tipo, P.opcion, P.texto from promosuscripcion.promo_menu_multiple_promociones P where id_promocion = ?
                    order by nivel ) T2
                    on T1.tipo = T2.tipo group by T1.tipo, T2.texto order by T1.tipo";*/
            $sql = "select T4.*, coalesce( T5.cantidad, 0 )::integer as cantidad
                    from (
                        select P.nivel as tipo, (P.opcion || ' - ' || P.texto)::varchar as nombre_canal
                        from promosuscripcion.promo_menu_multiple_promociones P
                        where P.id_promocion = ?
                        union
                        select 0::integer as tipo, 'Ningun Canal'::varchar as nombre_canal
                        order by 1
                    ) T4 left join (

                        select T1.tipo, count(*)::integer as cantidad
                        from (
                                select B.cel, B.tipo from (
                                select S.cel from promosuscripcion.suscriptos S where id_promocion = ?
                                ) A
                                left join (
                                select P.cel, P.tipo from promosuscripcion.promo_menu_multiple P where P.id_promocion = ?
                                and P.activo = 1
                                ) B on A.cel = B.cel order by B.cel, B.tipo
                            ) T1 group by 1 order by 2 desc

                    ) T5 on T5.tipo = T4.tipo order by 1";

            $rs = $db->fetchAll( $sql, array( $datos['id_promocion'], $datos['id_promocion'], $datos['id_promocion'] ) );

            if( !empty( $rs ) ){

                $resultado = array();
                foreach( $rs as $fila ){

                    //$resultado[$fila['nivel']][] = (array)$fila;
                    $resultado[] = (array)$fila;
                }

                return $resultado;

            }else{

                return null;
            }

        }
        if( $accion == 'GET_CANTIDAD_SUCRIPTOS_X_OPCION' ){

            $sql = "select T4.*, coalesce(T5.suscriptos, 0)::integer as suscriptos
                        from (
                        select P.nivel as tipo, (P.opcion || ' - ' || P.texto)::varchar as nombre_canal
                        from promosuscripcion.promo_menu_multiple_promociones P
                        where P.id_promocion = ?
                        union
                        select 0::integer as tipo, 'Ningun Canal'::varchar as nombre_canal
                        order by 1
                        ) T4 left join (

                        select T3.canales, count(*)::integer as suscriptos
                        from (
                        select T2.cel, T2.cantidad - 1 as canales
                        from (
                        select T1.cel, count(*)::integer as cantidad
                        from (
                            select B.cel, B.tipo from (
                                select S.cel from promosuscripcion.suscriptos S where id_promocion = ? --and id_carrier = 2
                            ) A
                            left join (
                                select P.cel, P.tipo from promosuscripcion.promo_menu_multiple P where P.id_promocion = ?
                                and P.activo = 1
                            ) B on A.cel = B.cel order by B.cel, B.tipo
                        ) T1
                        group by 1 order by 2 desc
                        ) T2
                        ) T3 group by 1 order by 1

                        ) T5 on T5.canales = T4.tipo";

            $rs = $db->fetchAll( $sql, array( $datos['id_promocion'], $datos['id_promocion'], $datos['id_promocion'] ) );

            if( !empty( $rs ) ){

                $resultado = array();
                foreach( $rs as $fila ){

                    //$resultado[$fila['nivel']][] = (array)$fila;
                    $resultado[] = (array)$fila;
                }

                return $resultado;

            }else{

                return null;
            }
        }
        if( $accion == 'GET_PROMOCIONES_X_TIPO' ){

            $carrier = array(

                'personal' => 'PERSONAL',
                'tigo_bolivia' => 'TIGO',
                'claro_dominicana' => 'CLARO',
                'telecel' => 'TIGO',

            );

            $sql = "select T7.*, coalesce(T8.envios,1) as envios from (
                    select T5.*, T6.codigo from (

                        select T3.*, T4.id_pais, T4.descripcion from (
                            select RS.numero, RS.id_promocion, RS.id_carrier, RS.alias, RS.tipo, RS.nivel, RS.suscriptos, RS.cargados, RS.enviados, RS.pendientes, RS.cantidad_receptores from
                            reporte_stock_contenidos RS where RS.numero = ? and RS.tipo = ? order by 1,2,3,6

                        ) T3 left join (

                            select T1.*,T2.descripcion from (

                            select CXP.id_pais, CXP.id_carrier from carriers_x_paises CXP order by 1,2

                            ) T1 left join (

                            select C.id_carrier, C.descripcion from carriers C order by 1,2

                            ) T2 on T1.id_carrier = T2.id_carrier

                        ) T4 on T3.id_carrier = T4.id_carrier
                    ) T5 left join (

                        select P.id_pais, P.codigo from paises P order by 1

                    ) T6 on T5.id_pais = T6.id_pais order by 1,2,3,5,6
                ) T7 left join (
                    select PED.id_promocion, PED.id_carrier, PED.envios from promosuscripcion.envios_x_dia PED group by 1,2,3 order by 1,2
                ) T8 on T7.id_promocion = T8.id_promocion and T7.id_carrier = T8.id_carrier";

            $rs = $db->fetchAll( $sql, array( $datos['numero'], $datos['tipo'] ) );

            if( !empty( $rs ) ){

                $resultado = array();
                foreach( $rs as $fila ){

                    $fila['descripcion'] = $carrier[$fila['descripcion']] .'_'. $fila['codigo'];//formateo la descripcion al necesitado
                    $fila['acabaran'] = round($fila['pendientes']/$fila['envios']);
                    $resultado[$fila['alias']][] = (array)$fila;
                }

                return $resultado;

            }else{

                return null;
            }
        }
        if( $accion == 'GET_MENSAJES_ENVIADOS_X_PROMOCION' ){

            $sql = "select (case when S4.id_contenido > ( select max( ME.id_mensaje_periodico_enviado ) as id_contenido
                from promosuscripcion.mensajes_enviados ME
                where ME.id_promocion = ?

                ) THEN 'contenidos_pendientes' ELSE 'contenidos_enviados' END)::varchar as estado, count(*)::integer as cantidad
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

            $rs = $db->fetchAll( $sql, array( $datos['id_promocion'], $datos['id_promocion'], $datos['id_promocion'] ) );

            if( !empty( $rs ) ){

                $resultado = array();
                foreach( $rs as $fila ){

                    $resultado[$fila['estado']] = $fila['cantidad'];
                }

                return $resultado;

            }else{

                return null;
            }
        }

        //cobros contenidos
        if( $accion == 'GET_COBROS_POR_PROMOCION' ){

            $sql = "select T5.*, T6.alias from (
                select T3.*, coalesce(T4.suscriptos,0) as suscriptos from (
                select T2.numero, T2.fecha, T2.dia_semana, T2.id_carrier, T2.id_promocion, sum(T2.total_cobros)::integer as total_cobros, sum(T2.total_bruto_gs)::integer as total_bruto_gs, sum(T2.total_bruto_usd)::numeric(10,2) as total_bruto_usd,
                sum(T2.total_neto_gs)::integer as total_neto_gs, sum(T2.total_neto_usd)::numeric(10,2) as total_neto_usd
                from ( -- sin id_servicio -> Total por Promocion, Por Carrier

                select T1.*, (T1.total_cobros * T1.costo_gs)::integer as total_bruto_gs, (T1.total_cobros * T1.costo_usd)::numeric(10,2) as total_bruto_usd,
                (T1.total_cobros * T1.costo_gs * T1.revenue)::integer as total_neto_gs, (T1.total_cobros * T1.costo_usd * T1.revenue)::numeric(10,2) as total_neto_usd
                from (-- Total discriminado por IdServicio

                select RM.*, CC.costo_gs, CC.costo_usd, RS.porcentaje_proveedor as revenue
                from reporte_mensual_cobros_con_id_servicio(?, ?) RM
                left join codigos_cobro CC on CC.id_servicio = RM.id_servicio and CC.id_carrier = RM.id_carrier and CC.numero = RM.numero
                left join revenue_share RS on RS.numero = RM.numero and RS.id_carrier = RM.id_carrier
                where --RM.numero = '6767'
                --and RM.id_promocion = 73
                --and RM.fecha = '2014-01-01'::date
                --and
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
                ) T6 on T5.id_promocion = T6.id_promocion order by 6 desc";
                //order by viejo 1,5,4,2
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
        if( $accion == 'GET_SUSCRIPTOS_X_TIPO' ){

            $promociones = array(

                '67' => 'SEMANA',
                '68' => 'MES',
                '73' => 'YA',
            );

            $sql = "select dia_mes,dia_semana,fecha,id_promocion,accion,sum(total)::integer as total from (
                select extract(day from ts_local)::integer as dia_mes, extract(dow from ts_local)::integer as dia_semana, ts_local::date as fecha, id_promocion, id_carrier, accion, count(*)::integer as total
                    from  promosuscripcion.log_suscriptos
                    where id_carrier in(1,2) and extract(year from ts_local)::integer = ? and extract(month from ts_local)::integer = ? and id_promocion in(73,68,67) and accion = 'ALTA'
                    group by 1,2,3,4,5,6
                    union
                    select extract(day from ts_local)::integer as dia_mes, extract(dow from ts_local)::integer as dia_semana, ts_local::date as fecha, id_promocion, id_carrier, accion, count(*)::integer as total
                    from promosuscripcion.log_suscriptos
                    where id_carrier in(1,2) and extract(year from ts_local)::integer = ? and extract(month from ts_local)::integer = ? and id_promocion in(73,68,67) and accion = 'BAJA'
                    group by 1,2,3,4,5,6
                    order by 1,2,3,4,5
            ) T1 group by 1,2,3,4,5 order by 3,2,5";

            $rs = $db->fetchAll( $sql, array( $datos['anho'], $datos['mes'], $datos['anho'], $datos['mes'] ) );

            if( !empty( $rs ) ){

                $resultado = array();
                foreach( $rs as $fila ){

                    $resultado[$promociones[$fila['id_promocion']]]['mes'][$fila['dia_mes']][$fila['accion']] = $fila['total'];
                }

                //print_r($resultado);exit;

                return $resultado;

            }else{

                return null;
            }
        }
        if( $accion == 'GET_COBROS_X_TIPO' ){

            $promociones = array(

                '67' => 'SEMANA',
                '68' => 'MES',
                '73' => 'YA',
            );

            $sql = "select T3.numero, T3.fecha, extract(day from T3.fecha)::integer as dia, T3.dia_semana, T3.id_promocion, sum(T3.total_cobros)::integer as total_cobros, sum(T3.total_bruto_gs)::integer as total_bruto_gs, sum(T3.total_bruto_usd)::numeric(10,2) as total_bruto_usd,
            sum(T3.total_neto_gs)::integer as total_neto_gs, sum(T3.total_neto_usd)::numeric(10,2) as total_neto_usd
            from (-- sin id_carrier -> Total Por Promocion

            select T2.numero, T2.fecha, T2.dia_semana, T2.id_carrier, T2.id_promocion, sum(T2.total_cobros)::integer as total_cobros, sum(T2.total_bruto_gs)::integer as total_bruto_gs, sum(T2.total_bruto_usd)::numeric(10,2) as total_bruto_usd,
            sum(T2.total_neto_gs)::integer as total_neto_gs, sum(T2.total_neto_usd)::numeric(10,2) as total_neto_usd
            from ( -- sin id_servicio -> Total por Promocion, Por Carrier

            select T1.*, (T1.total_cobros * T1.costo_gs)::integer as total_bruto_gs, (T1.total_cobros * T1.costo_usd)::numeric(10,2) as total_bruto_usd,
            (T1.total_cobros * T1.costo_gs * T1.revenue)::integer as total_neto_gs, (T1.total_cobros * T1.costo_usd * T1.revenue)::numeric(10,2) as total_neto_usd
            from (-- Total discriminado por IdServicio

            select RM.*, CC.costo_gs, CC.costo_usd, RS.porcentaje_proveedor as revenue
            from reporte_mensual_cobros_con_id_servicio(?, ?) RM
            left join codigos_cobro CC on CC.id_servicio = RM.id_servicio and CC.id_carrier = RM.id_carrier and CC.numero = RM.numero
            left join revenue_share RS on RS.numero = RM.numero and RS.id_carrier = RM.id_carrier
            where RM.numero = '6767'
            and RM.id_promocion in(73, 68, 67)  and
            --RM.fecha = '2013-12-01'::date
            --and
            RM.id_carrier in(
                select CxP.id_carrier
                from paises P
                left join carriers_x_paises CxP on CxP.id_pais = P.id_pais
                where P.id_pais = 1
            )

            ) T1

            ) T2 group by 1,2,3,4,5 order by 1,2,3,4,5

            ) T3 group by 1,2,3,4,5 order by 1,2,3,4";

            $rs = $db->fetchAll( $sql, array( $datos['anho'], $datos['mes']) );

            if( !empty( $rs ) ){

                $resultado = array();
                foreach( $rs as $fila ){

                    $resultado[$promociones[$fila['id_promocion']]]['mes'][$fila['dia']] = (array)$fila;
                }

                //print_r($resultado);exit;

                return $resultado;

            }else{

                return null;
            }
        }
        if( $accion == 'GET_SUSCRIPTOS_MES_ANTERIOR' ){

            $promociones = array(

                '67' => 'SEMANA',
                '68' => 'MES',
                '73' => 'YA',
            );

            if( $datos['mes'] == 1 ){

                $datos['mes'] = 12;
                $datos['anho'] -= 1;
            }else{

                $datos['mes'] -= 1;
            }

            $datos['cantidad_dias'] = date("d", mktime(0,0,0,$datos['mes']+1, 0, $datos['anho']));;

            $sql = "select id_promocion, (sum(altas)-sum(bajas))::integer as suscriptos from reportes_altas_bajas_cobros_x_dia
            where id_promocion in (68,73,67) and extract(year from fecha)::integer = ? and extract(month from fecha)::integer = ?
            and extract(day from fecha)::integer = ? group by 1";

            $rs = $db->fetchAll( $sql, array( $datos['anho'], $datos['mes'], $datos['cantidad_dias']) );

            if( !empty( $rs ) ){

                $resultado = array();
                foreach( $rs as $fila ){

                    $resultado[$promociones[$fila['id_promocion']]]['suscriptos'] = $fila['suscriptos'];
                }

                return $resultado;

            }else{

                return null;
            }
        }
        /*if( $accion == 'GET_SUSCRIPTOS_A_COBRAR_DIA' ){

            $promociones = array(

                '68' => 'MES',
                '73' => 'YA',
            );

            if( $datos['mes'] < 10 ){

                $datos['mes'] = '0'.(string)$datos['mes'];
            }

            $datos['anho'] = (string)$datos['anho'];
            $tabla_a_consultar = 'log_salientes_yy'.$datos['anho'].'mm'.$datos['mes'];

            $sql = "select T2.anho, T2.mes, T2.dia, T2.id_sc as id_promocion, count(*)::integer as total_intentos_suscriptos from (
                select T1.anho,T1.mes,T1.dia,T1.n_llamado,T1.id_sc,T1.id_carrier, T1.n_remitente from (
                    select extract(year from ts_local)::integer as anho,extract(month from ts_local)::integer as mes,
                    extract(day from ts_local)::integer as dia, n_llamado, id_sc, n_remitente,id_carrier,cmd_id,id_servicio
                    from ".$tabla_a_consultar." where
                    id_sc in(68,73) and id_servicio is not null
                    and id_servicio != ''
                    order by 6 asc
                  ) T1 group by 1,2,3,4,5,7,6 order by 7
            ) T2 group by 1,2,3,4 order by 1,2,3,4 asc";

            $rs = $db->fetchAll( $sql );

            if( !empty( $rs ) ){

                $resultado = array();
                foreach( $rs as $fila ){

                    $resultado[$promociones[$fila['id_promocion']]][$fila['dia']] = $fila['total_intentos_suscriptos'];
                }

                return $resultado;

            }else{

                return null;
            }
        }*/
        if( $accion == 'GET_SUSCRIPTOS_A_COBRAR_DIA' ){

            $promociones = array(

                '67' => 'SEMANA',
                '68' => 'MES',
                '73' => 'YA',
            );
            $consulta = array(

                '67' => '7 day',
                '68' => '1 month',
                '73' => '7 day',

            );
            $resultado = array();

            foreach( $promociones as $id_promocion => $nombre_promocion ){

                if( $id_promocion == '73' || $id_promocion == '67' ){

                    $sql = "select T4.id_promocion,T4.dia_semana,sum(T4.cantidad)::integer as cantidad from (
                        select T3.id_promocion, T3.dia_alta, T3.proximo_cobro, extract(dow from T3.proximo_cobro)::integer as dia_semana, count(*)::integer as cantidad from (

                            select  T1.id_promocion, T2.dia_alta, (T2.dia_alta + interval '".$consulta[$id_promocion]."')::date as proximo_cobro from (

                                select PS.id_promocion, PS.id_suscripto from promosuscripcion.suscriptos PS where PS.id_promocion = ? order by 2

                            )T1 join (

                                select PLS.id_promocion, PLS.ts_local::date as dia_alta, PLS.accion, PLS.id_suscripto from promosuscripcion.log_suscriptos PLS
                                where PLS.id_promocion = ? and PLS.accion = 'ALTA'
                                order by 4

                            ) T2 on T1.id_suscripto = T2.id_suscripto

                        ) T3 group by 1,2,3 order by 1,2,3
                    ) T4 group by 1,2 order by 2";

                    $rs = $db->fetchAll( $sql, array( $id_promocion,$id_promocion ) );


                    if( !empty( $rs ) ){

                        foreach( $rs as $fila ){

                            $resultado[$promociones[$fila['id_promocion']]][$fila['dia_semana']] = $fila['cantidad'];
                        }
                    }else{

                        for( $i=0; $i<=6; $i++ ){
                            $resultado[$promociones[$id_promocion]][$i] = 0;

                        }
                    }
                }else{

                    $sql = "select T4.id_promocion,T4.dia_semana,sum(T4.cantidad)::integer as cantidad from (
                        select T3.id_promocion, T3.dia_alta, T3.proximo_cobro, extract(day from T3.proximo_cobro)::integer as dia_semana, count(*)::integer as cantidad from (

                            select  T1.id_promocion, T2.dia_alta, (T2.dia_alta + interval '".$consulta[$id_promocion]."')::date as proximo_cobro from (

                                select PS.id_promocion, PS.id_suscripto from promosuscripcion.suscriptos PS where PS.id_promocion = ? order by 2

                            )T1 join (

                                select PLS.id_promocion, PLS.ts_local::date as dia_alta, PLS.accion, PLS.id_suscripto from promosuscripcion.log_suscriptos PLS
                                where PLS.id_promocion = ? and PLS.accion = 'ALTA'
                                order by 4

                            ) T2 on T1.id_suscripto = T2.id_suscripto

                        ) T3 group by 1,2,3 order by 1,2,3
                    ) T4 group by 1,2 order by 2";

                    $rs = $db->fetchAll( $sql, array( $id_promocion,$id_promocion ) );

                    if( !empty( $rs ) ){

                        foreach( $rs as $fila ){

                            $resultado[$promociones[$fila['id_promocion']]][$fila['dia_semana']] = $fila['cantidad'];
                        }
                    }else{
                        //falta arreglar
                        for( $i=0; $i<=6; $i++ ){

                            $resultado[$promociones[$id_promocion]][$i] = 0;
                        }
                    }
                }
            }
            //print_r($resultado);exit;
            return $resultado;
        }
        if( $accion == 'GET_SUSCRIPTOS_A_COBRAR_DIA_COBROS' ){

            $promociones = array(

                '67' => 'SEMANA',
                '68' => 'MES',
                '73' => 'YA',
            );
            $consulta = array(

                '68' => '1 month',
                '73' => '7 day',
                '67' => '7 day',
            );
            $resultado = array();

            foreach( $promociones as $id_promocion => $nombre_promocion ){

                if( $id_promocion == '73' || $id_promocion == '67' ){

                    $sql = "select T4.id_promocion,T4.dia_semana,sum(T4.cantidad)::integer as cantidad from (
                        select T3.id_promocion, T3.dia_cobro, T3.proximo_cobro, extract(dow from T3.proximo_cobro)::integer as dia_semana, count(*)::integer as cantidad from (

                        select T2.id_promocion, T2.dia_cobro, (T2.dia_cobro + interval '".$consulta[$id_promocion]."')::date as proximo_cobro from (
                            select PS.id_promocion, PS.cel from promosuscripcion.suscriptos PS where PS.id_promocion = ? order by 2
                        ) T1 join (
                            select PMC.id_promocion, PMC.cel, PMC.ts_local::date as dia_cobro from promosuscripcion.mensajes_cobrados_por_fecha PMC where PMC.id_promocion = ? order by 3
                        ) T2 on T1.cel = T2.cel order by 1,2

                        ) T3 group by 1,2,3 order by 1,2,3
                    ) T4 group by 1,2 order by 2";

                    $rs = $db->fetchAll( $sql, array( $id_promocion,$id_promocion ) );

                    if( !empty( $rs ) ){

                        foreach( $rs as $fila ){

                            $resultado[$promociones[$fila['id_promocion']]][$fila['dia_semana']] = $fila['cantidad'];
                        }
                    }else{

                        for( $i=0; $i<=6; $i++ ){
                            $resultado[$promociones[$id_promocion]][$i] = 0;

                        }
                    }
                }else{

                    $sql = "select T4.id_promocion,T4.dia_semana,sum(T4.cantidad)::integer as cantidad from (
                        select T3.id_promocion, T3.dia_cobro, T3.proximo_cobro, extract(day from T3.proximo_cobro)::integer as dia_semana, count(*)::integer as cantidad from (

                        select T2.id_promocion, T2.dia_cobro, (T2.dia_cobro + interval '".$consulta[$id_promocion]."')::date as proximo_cobro from (
                            select PS.id_promocion, PS.cel from promosuscripcion.suscriptos PS where PS.id_promocion = ? order by 2
                        ) T1 join (
                            select PMC.id_promocion, PMC.cel, PMC.ts_local::date as dia_cobro from promosuscripcion.mensajes_cobrados_por_fecha PMC where PMC.id_promocion = ? order by 3
                        ) T2 on T1.cel = T2.cel order by 1,2

                        ) T3 group by 1,2,3 order by 1,2,3
                    ) T4 group by 1,2 order by 2";

                    $rs = $db->fetchAll( $sql, array( $id_promocion,$id_promocion ) );

                    if( !empty( $rs ) ){

                        foreach( $rs as $fila ){

                            $resultado[$promociones[$fila['id_promocion']]][$fila['dia_semana']] = $fila['cantidad'];
                        }
                    }else{
                        //falta arreglar
                        for( $i=0; $i<=6; $i++ ){

                            $resultado[$promociones[$id_promocion]][$i] = 0;
                        }
                    }
                }
            }

            //print_r($resultado);exit;
            return $resultado;
        }

        if( $accion == 'GET_RANGO_SELECCION' ){

            $sql = "select extract (year from T1.fecha)::integer as anho, extract (month from T1.fecha)::integer as mes
            from (
                select MM.fecha from monto_cambio_x_mes MM group by 1 order by 1 asc
            ) T1";

            $rs = $db->fetchAll( $sql );

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
        if( $accion == 'GET_DATOS_BACKTONES' ){

            $carrier = array(
                '1' => 'PERSONAL',
                '2' => 'TIGO',
            );
            //1
            $sql = "select T12.id_carrier, T12.tipo, T12.descripcion,T12.costo_usd, sum(T12.cantidad)::integer as cantidad, sum(T12.total_bruto_gs)::integer as total_bruto_gs, sum(T12.total_neto_gs)::integer as total_neto_gs,
                sum(T12.total_monto_proveedor)::integer as total_monto_proveedor from (
                select T10.*, (T11.porcentaje*T10.total_neto_gs)::integer as total_monto_proveedor from (
                    select T8.*, (T9.porcentaje_proveedor*T8.total_bruto_gs)::integer as total_neto_gs from (
                        select T7.id_proveedor, T7.id_carrier, T7.tipo, T7.descripcion, T7.costo_usd, sum(T7.cantidad)::integer as cantidad, sum(T7.total_bruto_gs)::integer as total_bruto_gs from (
                            select T5.*, T6.monto_gs, (T5.cantidad*T5.costo_usd*T6.monto_gs)::integer as total_bruto_gs from (
                                select T3.*, T4.descripcion, T4.costo_usd from (
                                    select T2.id_proveedor, T2.tone_name, T1.id_carrier, T1.tipo, T1.cantidad  from (
                                        select RB.id_carrier, RB.tipo, RB.tone_name, sum(RB.cantidad)::integer as cantidad from reporte_backtones RB
                                            where extract(year from RB.fecha)::integer = ? and extract(month from RB.fecha)::integer = ?
                                            group by 1,2,3
                                    ) T1 join (
                                        --nombre de los tonos
                                        select T.id_proveedor, T.tone_name, T.hash from tonos T
                                    ) T2 on T1.tone_name = T2.hash order by 1,2,3,4
                                ) T3 join (
                                    select * from tipos_tonos TP
                                ) T4 on T3.tipo = T4.tipo and T3.id_carrier = T4.id_carrier
                            ) T5 join (
                                select RM.id_carrier, RM.monto_gs from monto_cambio_x_mes RM
                                    where extract(year from RM.fecha)::integer = ? and extract(month from RM.fecha)::integer = ?
                            ) T6 on T5.id_carrier = T6.id_carrier
                        ) T7 group by 1,2,3,4,5 order by 1
                    ) T8 join (
                        select RV.id_carrier, RV.porcentaje_proveedor from backtones_revenue_share RV
                    ) T9 on T8.id_carrier = T9.id_carrier order by 1,2,3,4
                ) T10 join (
                    select * from proveedor_rbt RP order by 1
                ) T11 on T10.id_proveedor = T11.id_proveedor
            ) T12 group by 1,2,3,4 order by 1,2";

            $rs = $db->fetchAll( $sql, array( $datos['anho'], $datos['mes'],$datos['anho'], $datos['mes'] ) );

            if( !empty( $rs ) ){

                $resultado = array();
                foreach( $rs as $fila ){

                    $resultado['datos'][$carrier[$fila['id_carrier']]]['tipos'][$fila['tipo']] = (array)$fila;
                }

                //print_r($resultado);exit;
                return $resultado;

            }else{

                return null;
            }
        }
        if( $accion == 'GET_BACKTONES_TOTALES' ){

            $sql = "select T13.id_proveedor,T13.artist_name, T13.id_carrier, T13.porcentaje_proveedor, sum( T13.cantidad )::integer as cantidad,sum(T13.monto_neto_enter)::integer as monto_neto_enter, sum(T13.monto_proveedor_rbt)::integer as monto_proveedor_rbt,
                sum(T13.neto_final_enter)::integer as neto_final_enter from (
                    select T12.*, (T12.monto_neto_enter - T12.monto_proveedor_rbt)::integer as neto_final_enter from (
                            select T10.*,T11.porcentaje_proveedor, (T10.cantidad*T10.costo_usd*T10.monto_gs*T11.porcentaje_proveedor)::integer as monto_neto_enter,
                            (T10.porcentaje*T10.cantidad*T10.costo_usd*T10.monto_gs*T11.porcentaje_proveedor)::integer as monto_proveedor_rbt from (
                                select T8.*,T9.monto_gs from (
                                    select T6.*, T7.descripcion,T7.costo_usd from (
                                        select T5.id_proveedor, T5.id_carrier, T5.artist_name, T5.tipo, T5.porcentaje, sum(T5.cantidad)::integer as cantidad from (
                                        select T4.id_proveedor,T4.artist_name,T3.tone_name,T3.id_carrier,T3.tipo,T3.cantidad,T4.porcentaje from (
                                            select T1.id_proveedor,T1.tone_name,T2.fecha,T2.id_carrier,T2.tipo,T2.cantidad from (
                                                select id_proveedor, tone_name, hash from tonos
                                            ) T1 join (
                                                select fecha,tone_name,id_carrier,tipo,cantidad from reporte_backtones
                                                where extract(year from fecha)::integer = ? and extract(month from fecha)::integer = ?
                                            ) T2 on T1.hash = T2.tone_name
                                        ) T3 join (
                                            select * from proveedor_rbt
                                        ) T4 on T3.id_proveedor = T4.id_proveedor
                                        ) T5 group by 1,2,3,4,5 order by 1
                                    ) T6 join (
                                    select * from tipos_tonos
                                    ) T7 on T6.tipo = T7.tipo
                                ) T8 join (
                                    select id_carrier, monto_gs from monto_cambio_x_mes where extract(year from fecha)::integer = ? and extract(month from fecha)::integer = ? and id_carrier = ?
                                ) T9 on T8.id_carrier = T9.id_carrier
                            ) T10 join (
                                select id_carrier,porcentaje_proveedor from backtones_revenue_share order by 1
                            ) T11 on T10.id_carrier = T11.id_carrier
                    ) T12
                ) T13 group by 1,2,3,4";

            $rs = $db->fetchAll( $sql, array( $datos['anho'], $datos['mes'],$datos['anho'], $datos['mes'], $datos['id_carrier'] ) );

            if( !empty( $rs ) ){

                $resultado = array();
                foreach( $rs as $fila ){

                    $resultado['proveedores'][$fila['artist_name']] = (array)$fila;
                }

                return $resultado;

            }else{

                return null;
            }
        }
        if( $accion == 'GET_BACKTONES_DETALLES' ){

            $sql = "select T9.*, T10.descripcion, T10.costo_usd, T9.neto_entermovil-T9.neto_proveedor as neto_enter_final from (
                    select T7.*,T8.porcentaje_proveedor,(T7.cantidad*T7.monto_gs*T8.porcentaje_proveedor)::integer as neto_entermovil,
                        (T7.cantidad*T7.monto_gs*T7.porcentaje*T8.porcentaje_proveedor)::integer as neto_proveedor from (
                        select T5.*,T6.monto_gs from (
                            select T5.artist_name, T5.tone_name,T5.id_carrier,T5.tipo, T5.porcentaje, sum(T5.cantidad)::integer as cantidad from (
                                select T4.artist_name,T3.tone_name,T3.id_carrier,T3.tipo,T3.cantidad,T4.porcentaje from (
                                    select T1.id_proveedor,T1.tone_name,T2.fecha,T2.id_carrier,T2.tipo,T2.cantidad from (
                                        select id_proveedor, tone_name, hash from tonos
                                    ) T1 join (
                                        select fecha,tone_name,id_carrier,tipo,cantidad from reporte_backtones
                                        where extract(year from fecha)::integer = ? and extract(month from fecha)::integer = ? and id_carrier = ?
                                    ) T2 on T1.hash = T2.tone_name order by 1
                                ) T3 join (
                                    select * from proveedor_rbt where id_proveedor = ?
                                ) T4 on T3.id_proveedor = T4.id_proveedor order by T3.cantidad desc
                            ) T5 group by 1,2,3,4,5
                        ) T5 join (
                            select id_carrier,monto_gs from monto_cambio_x_mes where extract(year from fecha)::integer = ? and extract(month from fecha)::integer = ? and id_carrier = ?
                        ) T6 on T5.id_carrier = T6.id_carrier
                    ) T7 join (
                        select id_carrier,porcentaje_proveedor from backtones_revenue_share
                    ) T8 on T7.id_carrier = T8.id_carrier
                ) T9 join (
                    select * from tipos_tonos
                ) T10 on T9.tipo = T10.tipo order by 1,2,3,4";

            $rs = $db->fetchAll( $sql, array( $datos['anho'], $datos['mes'],$datos['id_carrier'], $datos['id_proveedor'], $datos['anho'], $datos['mes'],$datos['id_carrier'] ) );

            if( !empty( $rs ) ){

                $resultado = array();
                foreach( $rs as $fila ){

                    $resultado[$fila['artist_name']]['datos'][] = (array)$fila;
                }

                return $resultado;

            }else{

                return null;
            }
        }
        if( $accion == 'GET_DETALLES_X_PROVEEDOR' ){

            $sql = "select T13.id_proveedor,T13.artist_name, T13.id_carrier, sum( T13.cantidad )::integer as cantidad, T13.porcentaje_proveedor,sum(T13.monto_neto_enter)::integer as monto_neto_enter, sum(T13.monto_proveedor_rbt)::integer as monto_proveedor_rbt,
                sum(T13.neto_final_enter)::integer as neto_final_enter from (
                    select T12.*, (T12.monto_neto_enter - T12.monto_proveedor_rbt)::integer as neto_final_enter from (
                            select T10.*,T11.porcentaje_proveedor, (T10.cantidad*T10.costo_usd*T10.monto_gs*T11.porcentaje_proveedor)::integer as monto_neto_enter,
                            (T10.porcentaje*T10.cantidad*T10.costo_usd*T10.monto_gs*T11.porcentaje_proveedor)::integer as monto_proveedor_rbt from (
                                select T8.*,T9.monto_gs from (
                                    select T6.*, T7.descripcion,T7.costo_usd from (
                                        select T5.id_proveedor, T5.id_carrier, T5.artist_name, T5.tipo, T5.porcentaje, sum(T5.cantidad)::integer as cantidad from (
                                        select T4.id_proveedor,T4.artist_name,T3.tone_name,T3.id_carrier,T3.tipo,T3.cantidad,T4.porcentaje from (
                                            select T1.id_proveedor,T1.tone_name,T2.fecha,T2.id_carrier,T2.tipo,T2.cantidad from (
                                                select id_proveedor, tone_name, hash from tonos
                                            ) T1 join (
                                                select fecha,tone_name,id_carrier,tipo,cantidad from reporte_backtones
                                                where extract(year from fecha)::integer = ? and extract(month from fecha)::integer = ?
                                            ) T2 on T1.hash = T2.tone_name
                                        ) T3 join (
                                            select * from proveedor_rbt where id_proveedor = ?
                                        ) T4 on T3.id_proveedor = T4.id_proveedor
                                        ) T5 group by 1,2,3,4,5 order by 1
                                    ) T6 join (
                                    select * from tipos_tonos
                                    ) T7 on T6.tipo = T7.tipo
                                ) T8 join (
                                    select id_carrier, monto_gs from monto_cambio_x_mes where extract(year from fecha)::integer = ? and extract(month from fecha)::integer = ?
                                ) T9 on T8.id_carrier = T9.id_carrier
                            ) T10 join (
                                select id_carrier,porcentaje_proveedor from backtones_revenue_share order by 1
                            ) T11 on T10.id_carrier = T11.id_carrier
                    ) T12
                ) T13 group by 1,2,3,5";

            $rs = $db->fetchAll( $sql, array( $datos['anho'], $datos['mes'],$datos['id_proveedor'], $datos['anho'], $datos['mes'] ) );

            if( !empty( $rs ) ){

                $resultado = array();
                foreach( $rs as $fila ){

                    $resultado[$fila['artist_name']]['datos'][$fila['id_carrier']] = (array)$fila;
                }

                return $resultado;

            }else{

                return null;
            }
        }
        if( $accion == 'GET_DATOS_PROVEEDORES_CONTENIDOS' ){

            $sql = "select T7.*, (T7.total_neto_enter_gs*T7.revenue)::integer as total_proveedor_gs, (T7.total_neto_enter_usd*T7.revenue)::integer as total_proveedor_usd from (

            select T5.*, T6.porcentaje_proveedor, (T6.porcentaje_proveedor*T5.total_bruto_gs)::integer as total_neto_enter_gs, (T6.porcentaje_proveedor*T5.total_bruto_usd)::integer as total_neto_enter_usd from (

                select T3.*, T4.costo_gs, T4.costo_usd, (T3.total_cobros * T4.costo_gs)::integer as total_bruto_gs, ( T3.total_cobros * T4.costo_usd )::integer as total_bruto_usd from (

                select T3.nombre_proveedor, T3.id_carrier, T3.revenue, T3.id_promocion, T3.id_servicio, sum(total_cobros)::integer as total_cobros from (

                    select T1.*, T2.fecha, T2.id_servicio, T2.total_cobros from (

                    select PC.nombre_proveedor, PCP.id_carrier, PCP.revenue, PCP.id_promocion from proveedores_contenidos PC join
                    proveedores_contenidos_x_promocion PCP on PC.id_proveedor = PCP.id_proveedor and PCP.id_proveedor = ?

                    ) T1 join (

                    select RM.fecha, RM.id_promocion, RM.id_servicio, RM.total_cobros from reporte_mensual_cobros_con_id_servicio(?,?) RM

                    ) T2 on T1.id_promocion = T2.id_promocion
                ) T3 group by 1,2,3,4,5 order by 5
                ) T3 join (

                select CC.id_carrier, CC.id_servicio, CC.costo_gs, CC.costo_usd from codigos_cobro CC where numero = '6767' order by 1,2

                ) T4 on T3.id_servicio = T4.id_servicio and T3.id_carrier = T4.id_carrier

            )  T5 left join (

                select * from revenue_share RS where numero = '6767' order by 1

             ) T6 on T5.id_carrier = T6.id_carrier

            ) T7 order by 1,2,3,4,5,6";

            $rs = $db->fetchAll( $sql, array( $datos['id_proveedor'], $datos['anho'], $datos['mes'] ) );

            if( !empty( $rs ) ){

                $resultado = array();
                foreach( $rs as $fila ){

                    $resultado['datos'][$fila['id_servicio']] = (array)$fila;
                }

                return $resultado;

            }else{

                return null;
            }
        }
        if( $accion == 'GET_PROVEEDORES_CONTENIDOS' ){

            $sql = "select * from proveedores_contenidos";

            $rs = $db->fetchAll( $sql );

            if( !empty( $rs ) ){

                $resultado = array();
                foreach( $rs as $fila ){

                    $resultado[$fila['id_proveedor']] = $fila['nombre_proveedor'];
                }

                return $resultado;

            }else{

                return null;
            }
        }
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

    public function contenidosNuevoAction(){

        $this->view->headLink()->appendStylesheet('/css/reportes_contenidos.css', 'screen');
        $this->view->headTitle()->append('Contenidos');

        $datos_vista = array();
        $fondos = array(

            0 => 'fondo_hierba',
            1 => 'fondo_celeste',
        );

        foreach( $this->numeros as $numero ){

            $resultado = $this->_cargarContenidosXNumero( $numero );
            if( !empty( $resultado ) ){

                $datos_vista[$numero] = $resultado;
            }
        }

        //print_r($datos_vista);exit;
        $this->view->numeros = $datos_vista;
        $this->view->fondos = $fondos;
        $this->view->contador_fondos = 0;
    }

    private function _cargarContenidosXNumero( $numero ){

        $promociones_x_tipo_completa = array();

        $tipos = array(

            'Secuencial' => 1,
            'Por Fecha' => 2,
            'Por Nivel' => 3,
            'Por Canales' => 11,
        );

        foreach( $tipos as $tipo=>$i ){

            $datos = array(

                'numero' => $numero,
                'tipo' => $i,
            );

            $promociones_x_tipo = $this->_consulta( 'GET_PROMOCIONES_X_TIPO', $datos );

            if( !is_null( $promociones_x_tipo ) ){

                foreach( $promociones_x_tipo as $indice=>$filas ){

                    $promociones_x_tipo_completa[$tipo] =  $promociones_x_tipo;
                }
            }

            //print_r($promociones_x_tipo_completa);exit;
        }

        return $promociones_x_tipo_completa;
    }

    public function salirAction() {

        $this->_forward('logout', 'auth');
    }
    //NUEVO
    public function cobrosPorCarrierAction() {

        $this->view->headScript()->appendFile('/js/reportes_cobros.js', 'text/javascript');
        $this->view->headLink()->appendStylesheet('/css/reportes_cobros.css', 'screen');

        $this->view->headTitle()->append('Cobros');

        $namespace = new Zend_Session_Namespace("entermovil");
        if(isset($namespace->numeros)) {
            $this->numeros = $namespace->numeros;
            $this->rango_seleccion = array(
                array('anho' => 2013, 'mes' => 6, 'descripcion' => '2013 - Junio')
            );
        }

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
        //$resultado = $this->_cargarCobrosPorCarrier(2013,12,1);

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
        //$totales_x_promocion_x_dia['suscriptos'] = 0;

        $datos['totales'] = $totales_x_dia;

        $numeros = array('6767', '35500', '965', '9330', '10130');


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


        //print_r( $datos['promociones'][$numero] );
        //print_r( $datos['totales'] );exit;

        //$this->log->info('Datos:[' . print_r($datos, true) . ']');
        //print_r($datos);exit;

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
    //reporte nuevo
    public function cobrosPorTipoAction() {

        $this->view->headScript()->appendFile('/js/reportes_cobros_por_tipo.js', 'text/javascript');
        $this->view->headLink()->appendStylesheet('/css/reportes_cobros.css', 'screen');

        $this->view->headTitle()->append('Cobros Por Tipo');

        $namespace = new Zend_Session_Namespace("entermovil");
        if(isset($namespace->numeros)) {
            $this->numeros = $namespace->numeros;
            $this->rango_seleccion = array(
                array('anho' => 2013, 'mes' => 6, 'descripcion' => '2013 - Junio')
            );
        }

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

        $datos = $this->_cargarSuscriptosPorDia($anho,$mes);

        $this->view->datos = $datos;

        $this->view->carriers = $this->carriers;

    }

    private function _cargarSuscriptosPorDia($anho, $mes){

        $datos = array(

            'anho' => $anho,
            'mes' => $mes,
        );

        $promociones = array(

            '67' => 'SEMANA',
            '68' => 'MES',
            '73' => 'YA',
        );


        $cantidad_dias = date('t', mktime(0, 0, 0, $mes, 1, $anho));

        $resultado = $this->_consulta('GET_SUSCRIPTOS_X_TIPO', $datos );

        foreach( $promociones as $id_promocion => $nombre_promocion  ){

            if( !isset( $resultado[$nombre_promocion]['mes'] ) ){

                for( $i = 1; $i <= $this->view->cantidad_dias; $i++ ){

                    $resultado[$nombre_promocion]['mes'][$i]['ALTA'] = 0;
                    $resultado[$nombre_promocion]['mes'][$i]['BAJA'] = 0;
                }
            }
        }

        $cobros = $this->_consulta('GET_COBROS_X_TIPO', $datos );

        //consulta para obtener los suscriptos del mes anterior
        $suscriptos = $this->_consulta( 'GET_SUSCRIPTOS_MES_ANTERIOR', $datos );
        //obtener suscriptos a cobrar basado en las altas
        $suscriptos_a_cobrar = $this->_consulta( 'GET_SUSCRIPTOS_A_COBRAR_DIA', $datos );
        //obtener suscriptos a cobrar basado en los cobros
        $suscriptos_a_cobrar_cobros = $this->_consulta( 'GET_SUSCRIPTOS_A_COBRAR_DIA_COBROS', $datos );

        foreach( $resultado as $id_promocion => $datos_promocion ){

            for( $i = 1; $i <= $this->view->cantidad_dias; $i++ ){

                if( !isset( $resultado[$id_promocion]['mes'][$i] ) ){

                    $resultado[$id_promocion]['mes'][$i]['ALTA'] = 0;
                    $resultado[$id_promocion]['mes'][$i]['BAJA'] = 0;

                    if( !isset( $cobros[$id_promocion]['mes'][$i] ) ){

                        $resultado[$id_promocion]['mes'][$i]['total_cobros'] = 0;
                        $resultado[$id_promocion]['mes'][$i]['total_neto_gs'] = 0;

                    }else{

                        $resultado[$id_promocion]['mes'][$i]['total_cobros'] = $cobros[$id_promocion]['mes'][$i]['total_cobros'];
                        $resultado[$id_promocion]['mes'][$i]['total_neto_gs'] = $cobros[$id_promocion]['mes'][$i]['total_neto_gs'];
                    }
                }else{

                    $resultado[$id_promocion]['mes'][$i]['total_cobros'] = isset( $cobros[$id_promocion]['mes'][$i]['total_cobros'] )? $cobros[$id_promocion]['mes'][$i]['total_cobros']: 0;
                    $resultado[$id_promocion]['mes'][$i]['total_neto_gs'] = isset( $cobros[$id_promocion]['mes'][$i]['total_neto_gs'] )? $cobros[$id_promocion]['mes'][$i]['total_neto_gs']: 0;
                }

                if( !isset( $resultado[$id_promocion]['mes'][$i]['ALTA'] ) ){

                    $resultado[$id_promocion]['mes'][$i]['ALTA'] = 0;

                }else if( !isset( $resultado[$id_promocion]['mes'][$i]['BAJA'] ) ){

                    $resultado[$id_promocion]['mes'][$i]['BAJA'] = 0;
                }
            }

            $resultado[$id_promocion]['cobros_x_dia_semana'] = $suscriptos_a_cobrar[$id_promocion];
            $resultado[$id_promocion]['cobros_x_dia_semana_cobros'] = $suscriptos_a_cobrar_cobros[$id_promocion];
        }

        $totales = array(

            'total_altas' => 0,
            'total_bajas' => 0,
            'total_suscriptos' => 0,
            'total_cobros' => 0,
            'total_neto' => 0,
        );

        $dia_actual = (int)date('d');
        $mes_actual = (int)date('m');

        foreach ( $resultado as $id_promocion => $datos_promocion ){

            $resultado[$id_promocion]['totales'] = $totales;
            $suscriptos_promocion = $suscriptos[$id_promocion]['suscriptos'];

            for( $i = 1; $i <= $cantidad_dias; $i++ ){

                if( ( $i > $dia_actual ) && ( $mes_actual == $mes ) ){

                    $suscriptos_promocion = 0;
                }else{

                    $suscriptos_promocion += $resultado[$id_promocion]['mes'][$i]['ALTA'] - $resultado[$id_promocion]['mes'][$i]['BAJA'];
                }

                $resultado[$id_promocion]['mes'][$i]['neto_suscriptos'] = $suscriptos_promocion;

                $resultado[$id_promocion]['totales']['total_altas'] += $resultado[$id_promocion]['mes'][$i]['ALTA'];
                $resultado[$id_promocion]['totales']['total_bajas'] += $resultado[$id_promocion]['mes'][$i]['BAJA'];

                $resultado[$id_promocion]['totales']['total_cobros'] += $resultado[$id_promocion]['mes'][$i]['total_cobros'];
                $resultado[$id_promocion]['totales']['total_neto'] += $resultado[$id_promocion]['mes'][$i]['total_neto_gs'];

                $resultado[$id_promocion]['mes'][$i]['intentos'] = isset( $suscriptos_a_cobrar[$id_promocion][$i] )? $suscriptos_a_cobrar[$id_promocion][$i] : 0;
            }

            if( $mes_actual == $mes ){

                $resultado[$id_promocion]['totales']['total_suscriptos'] = $resultado[$id_promocion]['mes'][$dia_actual]['neto_suscriptos'];

            }else{

                $resultado[$id_promocion]['totales']['total_suscriptos'] = $resultado[$id_promocion]['mes'][$i-1]['neto_suscriptos'];
            }
        }

        //print_r($resultado);exit;

        return $resultado;
    }
    //reporte backtones
    public function backtonesAction(){

        $this->view->headScript()->appendFile('/js/reportes_backtones.js', 'text/javascript');
        $this->view->headLink()->appendStylesheet('/css/reportes_backtones.css', 'screen');

        $this->view->headTitle()->append('Reporte Backtones');

        $namespace = new Zend_Session_Namespace("entermovil");
        if(isset($namespace->numeros)) {
            $this->numeros = $namespace->numeros;
            $this->rango_seleccion = array(
                array('anho' => 2013, 'mes' => 6, 'descripcion' => '2013 - Junio')
            );
        }

        $fecha_seleccionada = $this->_getParam('fecha', null);
        if(!is_null($fecha_seleccionada)) {
            list($anho, $mes) = explode('-', $fecha_seleccionada);
            $mes = (int)$mes;
        } else {
            $anho = date('Y');
            $mes = date('n');
            //tratamiento del mes anterior
            if( (int)$mes == 1 ){

                $anho--;
                $mes = 12;
            }else{

                $mes--;
            }
        }

        $rango = $this->_setearRangoSeleccion($anho, $mes);

        $this->view->nombre_mes = $this->meses[$mes-1];

        $this->view->cantidad_dias = date('t', mktime(0, 0, 0, $mes, 1, $anho));

        $this->view->anho = $anho;
        $this->view->mes = $mes;

        $this->view->dia_hoy = date('j');

        $this->view->rango_seleccion = $rango;

        $datos = $this->_cargarDatosBacktones($anho,$mes);
        //print_r($datos);exit;

        $this->view->datos = $datos;
        $this->view->carriers = $carrier = array(

            'PERSONAL' => 1,
            'TIGO' => 2
        );
    }

    private function _setearRangoSeleccion( $anho, $mes ){

    $rango = $this->_consulta( 'GET_RANGO_SELECCION' );
    if( !is_null($rango) ){

        $meses = array(
            1 => 'Enero',
            2 => 'Febrero',
            3 => 'Marzo',
            4 => 'Abril',
            5 => 'Mayo',
            6 => 'Junio',
            7 => 'Julio',
            8 => 'Agosto',
            9 => 'Setiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre',
        );

        foreach(  $rango as $indice => $datos ){

            $rango[$indice]['descripcion'] =  $datos['anho'] .' - ' . $meses[$datos['mes']];
            if( $datos['mes'] == $mes && $datos['anho'] == $anho ){

                $rango[$indice]['selected'] = 'selected';
            }else{

                $rango[$indice]['selected'] =  '';
            }
        }

        return $rango;
    }
}

    private function _cargarDatosBacktones( $anho, $mes ){

        $datos = array(

            'anho' => $anho,
            'mes' => $mes,
        );

        $datos_backtones = $this->_consulta( 'GET_DATOS_BACKTONES', $datos );

        if( !is_null( $datos_backtones ) ){

            $datos_backtones['totales_generales']['cantidad'] = 0;
            $datos_backtones['totales_generales']['total_bruto_gs'] = 0;
            $datos_backtones['totales_generales']['total_neto_gs'] = 0;
            $datos_backtones['totales_generales']['total_monto_proveedor'] = 0;

            foreach ( $datos_backtones['datos'] as $carrier => $estructura_carrier ){

                $datos_backtones['datos'][$carrier]['totales']['cantidad'] = 0;
                $datos_backtones['datos'][$carrier]['totales']['total_bruto_gs'] = 0;
                $datos_backtones['datos'][$carrier]['totales']['total_neto_gs'] = 0;
                $datos_backtones['datos'][$carrier]['totales']['total_monto_proveedor'] = 0;

                foreach( $estructura_carrier['tipos'] as $tipo => $datos_tipo ){

                    $datos_backtones['datos'][$carrier]['totales']['cantidad'] += $datos_tipo['cantidad'];
                    $datos_backtones['datos'][$carrier]['totales']['total_bruto_gs'] += $datos_tipo['total_bruto_gs'];
                    $datos_backtones['datos'][$carrier]['totales']['total_neto_gs'] += $datos_tipo['total_neto_gs'];
                    $datos_backtones['datos'][$carrier]['totales']['total_monto_proveedor'] += $datos_tipo['total_monto_proveedor'];

                }

                $datos_backtones['totales_generales']['cantidad'] += $datos_backtones['datos'][$carrier]['totales']['cantidad'];
                $datos_backtones['totales_generales']['total_bruto_gs'] += $datos_backtones['datos'][$carrier]['totales']['total_bruto_gs'];
                $datos_backtones['totales_generales']['total_neto_gs'] += $datos_backtones['datos'][$carrier]['totales']['total_neto_gs'];
                $datos_backtones['totales_generales']['total_monto_proveedor'] += $datos_backtones['datos'][$carrier]['totales']['total_monto_proveedor'];
            }
        }

        return $datos_backtones;
    }

    public function backtonesTotalesAction(){

        $this->view->headScript()->appendFile('/js/reportes_backtones_totales.js', 'text/javascript');
        $this->view->headLink()->appendStylesheet('/css/reportes_backtones.css', 'screen');

        $this->view->headTitle()->append('Reporte Backtones Totales');

        $datos = $this->_getAllParams( 'carrier', null, 'fecha', null );

        if( !is_null( $datos ) ) {
            list($anho, $mes) = explode('-', $datos['fecha']);
            $mes = (int)$mes;
            $id_carrier = $datos['carrier'];
        } else {
            $anho = date('Y');
            $mes = date('n');
            //tratamiento del mes anterior
            if( (int)$mes == 1 ){

                $anho--;
                $mes = 12;
            }else{

                $mes--;
            }
            $id_carrier = 2;
        }

        $datos_carrier_backtones = $this->_cargarDatosBacktonesTotales( $anho, $mes, $id_carrier );

        if( !is_null( $datos_carrier_backtones ) ){

            $this->view->datos = $datos_carrier_backtones;
            $this->view->mes = $mes;
            $this->view->anho = $anho;
            $this->view->id_carrier = $id_carrier;

            $this->view->carriers = $carriers = array(

                'PERSONAL' => 1,
                'TIGO' => 2
            );

            $rango = $this->_setearRangoSeleccion($anho, $mes);
            $this->view->rango_seleccion = $rango;

        }else{

            $this->view->datos = $datos_carrier_backtones;
            $this->view->mes = $mes;
            $this->view->anho = $anho;
            $this->view->id_carrier = $id_carrier;

            $this->view->carriers = $carriers = array(

                'PERSONAL' => 1,
                'TIGO' => 2
            );

            $rango = $this->_setearRangoSeleccion($anho, $mes);
            $this->view->rango_seleccion = $rango;

        }
    }

    private function _cargarDatosBacktonesTotales( $anho, $mes, $carrier ){

        $datos = array(

            'anho' => $anho,
            'mes' => $mes,
            'id_carrier' => $carrier,
        );

        $detalles_backtones = $this->_consulta( 'GET_BACKTONES_TOTALES', $datos );

        if( !is_null( $detalles_backtones ) ){

            $detalles_backtones['totales']['cantidad'] = 0;
            $detalles_backtones['totales']['monto_neto_enter'] = 0;
            $detalles_backtones['totales']['monto_proveedor_rbt'] = 0;
            $detalles_backtones['totales']['neto_final_enter'] = 0;

            foreach( $detalles_backtones['proveedores'] as $proveedor => $datos_proveedor ){

                $detalles_backtones['totales']['cantidad'] += $datos_proveedor['cantidad'];
                $detalles_backtones['totales']['monto_neto_enter'] += $datos_proveedor['monto_neto_enter'];
                $detalles_backtones['totales']['monto_proveedor_rbt'] += $datos_proveedor['monto_proveedor_rbt'];
                $detalles_backtones['totales']['neto_final_enter'] += $datos_proveedor['neto_final_enter'];
            }
        }

        return $detalles_backtones;
    }

    public function backtonesDetallesAction(){

        $this->view->headScript()->appendFile('/js/reportes_backtones.js', 'text/javascript');
        $this->view->headLink()->appendStylesheet('/css/reportes_backtones.css', 'screen');

        $this->view->headTitle()->append('Reporte Backtones Detalles');

        $datos = $this->_getAllParams( 'carrier', null, 'fecha', null, 'id-proveedor', null );

        if( !is_null( $datos ) ) {

            list($anho, $mes) = explode('-', $datos['fecha']);
            $mes = (int)$mes;
            $carrier = $datos['carrier'];
            $id_proveedor = $datos['id-proveedor'];
        } else {

            $anho = date('Y');
            $mes = date('n');
            //tratamiento del mes anterior
            if( (int)$mes == 1 ){

                $anho--;
                $mes = 12;
            }else{

                $mes--;
            }
            $carrier = 2;
            $id_proveedor = 1;
        }

        $datos_carrier_backtones = $this->_cargarDatosBacktonesDetalles( $anho, $mes, $carrier, $id_proveedor );

        $this->view->datos = $datos_carrier_backtones;

    }

    private function _cargarDatosBacktonesDetalles( $anho, $mes, $carrier, $id_proveedor ){

        $datos = array(

            'anho' => $anho,
            'mes' => $mes,
            'id_carrier' => $carrier,
            'id_proveedor' => $id_proveedor,
        );

        $detalles_backtones = $this->_consulta( 'GET_BACKTONES_DETALLES', $datos );

        foreach( $detalles_backtones as $proveedor => $estructura_proveedor)

            $detalles_backtones[$proveedor]['totales']['cantidad'] = 0;
            $detalles_backtones[$proveedor]['totales']['neto_entermovil'] = 0;
            $detalles_backtones[$proveedor]['totales']['neto_proveedor'] = 0;
            $detalles_backtones[$proveedor]['totales']['neto_enter_final'] = 0;

        foreach( $estructura_proveedor['datos'] as $indice => $datos_tipo ){

            $detalles_backtones[$proveedor]['totales']['cantidad'] += $datos_tipo['cantidad'];
            $detalles_backtones[$proveedor]['totales']['neto_entermovil'] += $datos_tipo['neto_entermovil'];
            $detalles_backtones[$proveedor]['totales']['neto_proveedor'] += $datos_tipo['neto_proveedor'];
            $detalles_backtones[$proveedor]['totales']['neto_enter_final'] += $datos_tipo['neto_enter_final'];

        }

        return $detalles_backtones;
    }

    public function backtonesDetallePorProveedorAction(){

        $this->view->headScript()->appendFile('/js/reportes_backtones_detalle.js', 'text/javascript');
        $this->view->headLink()->appendStylesheet('/css/reportes_backtones.css', 'screen');

        $this->view->headTitle()->append('Reporte Backtones');

        $namespace = new Zend_Session_Namespace("entermovil");
        if(isset($namespace->numeros)) {
            $this->numeros = $namespace->numeros;
            $this->rango_seleccion = array(
                array('anho' => 2013, 'mes' => 6, 'descripcion' => '2013 - Junio')
            );
        }

        $parametros = $this->_getAllParams( 'id-proveedor',null,'fecha', null );
        if(!is_null($parametros)) {
            list($anho, $mes) = explode('-', $parametros['fecha']);
            $mes = (int)$mes;
            $id_proveedor = $parametros['id-proveedor'];
        } else {
            $anho = date('Y');
            $mes = date('n');
            //tratamiento del mes anterior
            if( (int)$mes == 1 ){

                $anho--;
                $mes = 12;
            }else{

                $mes--;
            }
            $id_proveedor = 1;
        }

        $datos = $this->_cargarDatosBacktonesDetallesPorProveedor( $anho, $mes, $id_proveedor );

        if( !is_null( $datos ) ){

            $this->view->datos = $datos;
            $this->view->mes = $mes;
            $this->view->anho = $anho;

            $this->view->carriers = $carrier=array(

                1=>'PERSONAL',
                2=>'TIGO',
            );
        }else{

            $this->view->datos = null;
            $this->view->mes = $mes;
            $this->view->anho = $anho;

            $this->view->carriers = $carrier=array(

                1=>'PERSONAL',
                2=>'TIGO',
            );
        }

        $this->view->id_proveedor = $id_proveedor;
        $rango = $this->_setearRangoSeleccion($anho, $mes);
        $this->view->rango_seleccion = $rango;

    }

    private function _cargarDatosBacktonesDetallesPorProveedor( $anho, $mes, $id_proveedor ){

        $datos = array(

            'anho' => $anho,
            'mes' => $mes,
            'id_proveedor' => $id_proveedor,
        );

        $datos_backtones_proveedor = $this->_consulta( 'GET_DETALLES_X_PROVEEDOR', $datos );

        if( !is_null( $datos_backtones_proveedor ) ){

            foreach( $datos_backtones_proveedor as $proveedor => $estructura_proveedor ){

                $datos_backtones_proveedor[$proveedor]['totales']['cantidad'] = 0;
                $datos_backtones_proveedor[$proveedor]['totales']['monto_neto_enter'] = 0;
                $datos_backtones_proveedor[$proveedor]['totales']['monto_proveedor_rbt'] = 0;
                $datos_backtones_proveedor[$proveedor]['totales']['neto_final_enter'] = 0;

                foreach( $estructura_proveedor['datos'] as $id_carrier => $datos_proveedor ){

                    $datos_backtones_proveedor[$proveedor]['totales']['cantidad'] += $datos_proveedor['cantidad'];
                    $datos_backtones_proveedor[$proveedor]['totales']['monto_neto_enter'] += $datos_proveedor['monto_neto_enter'];
                    $datos_backtones_proveedor[$proveedor]['totales']['monto_proveedor_rbt'] += $datos_proveedor['monto_proveedor_rbt'];
                    $datos_backtones_proveedor[$proveedor]['totales']['neto_final_enter'] += $datos_proveedor['neto_final_enter'];

                }
            }
            //print_r($datos_backtones_proveedor);exit;
        }

        return $datos_backtones_proveedor;
    }

    public function proveedoresContenidosAction(){

        $this->view->headScript()->appendFile('/js/proveedores_contenidos.js', 'text/javascript');
        $this->view->headLink()->appendStylesheet('/css/reportes_proveedores_contenidos.css', 'screen');

        $this->view->headTitle()->append('Reporte Proveedores Contenidos');

        $namespace = new Zend_Session_Namespace("entermovil");

        $parametros = $this->_getAllParams( 'fecha', null, 'id-proveedor', null );

        if( isset( $parametros['fecha'] ) && isset( $parametros['id-proveedor'] ) ) {

            list($anho, $mes) = explode('-', $parametros['fecha']);
            $mes = (int)$mes;
            $id_proveedor = $parametros['id-proveedor'];

        } else {

            $anho = date('Y');
            $mes = date('n');
            $id_proveedor = 1;
        }

        $this->view->anho = $anho;
        $this->view->mes = $mes;
        $this->view->id_proveedor = $id_proveedor;

        $this->_setupRangoSeleccion($anho, $mes);

        $this->view->rango_seleccion = $this->rango_seleccion;

        $datos = $this->_cargarDatosProveedoresContenidos($anho,$mes,$id_proveedor);

        $this->view->datos = $datos;
        $this->view->carriers = $this->carriers;
        $this->view->proveedores = $this->_cargarProveedoresContenidos();

    }

    private function _cargarDatosProveedoresContenidos( $anho, $mes, $id_proveedor ){

        $datos = array(

            'anho' => $anho,
            'mes' => $mes,
            'id_proveedor' => $id_proveedor,
        );

        $datos_proveedores = $this->_consulta( 'GET_DATOS_PROVEEDORES_CONTENIDOS', $datos );

        if(!is_null($datos_proveedores)){

            $totales['totales'] = array(

                'total_cobros' => 0,
                'total_bruto_gs' => 0,
                'total_neto_enter_gs' => 0,
                'total_proveedor_gs' => 0,
            );

            foreach( $datos_proveedores['datos'] as $id_servicio => $datos_id_servicio ){

                $totales['totales']['total_cobros'] += $datos_id_servicio['total_cobros'];
                $totales['totales']['total_bruto_gs'] += $datos_id_servicio['total_bruto_gs'];
                $totales['totales']['total_neto_enter_gs'] += $datos_id_servicio['total_neto_enter_gs'];
                $totales['totales']['total_proveedor_gs'] += $datos_id_servicio['total_proveedor_gs'];
            }

            $datos_proveedores['totales'] = $totales['totales'];
        }

        //print_r($datos_proveedores);exit;
        return $datos_proveedores;

    }

    private function _cargarProveedoresContenidos(){

        $proveedores = $this->_consulta( 'GET_PROVEEDORES_CONTENIDOS' );

        return $proveedores;

    }
}

?>