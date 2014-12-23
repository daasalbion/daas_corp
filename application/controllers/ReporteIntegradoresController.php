<?php

class ReporteIntegradoresController extends Zend_Controller_Action{

    public $logger;
    var $usuarios = array(

        'dotgo' => array('clave' => 'dotgo', 'nombre' => 'Dotgo', 'id_carrier' => 206),
        'pmovil' => array('clave' => 'pmovil', 'nombre' => 'Pmovil', 'id_carrier' => 207)

    );
    var $meses = array(
        'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Setiembre', 'Octubre', 'Noviembre', 'Diciembre'
    );
    var $dias_semana = array(
        'Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'
    );
    var $rango_seleccion = array(
        array('anho' => 2014, 'mes' => 10, 'descripcion' => '2014 - Octubre')
    );
    var $numeros = array(
        206 => array( '2828' ),
        207 => array( '9650', '48850' )
    );
    var $carriers = array(
        206 => 'Dotgo',
        207 => 'Pmovil'
    );

    public function init(){
        /* Initialize action controller here */
        $this->logger = $this->getLog();
        $writer = new Zend_Log_Writer_Stream(APPLICATION_PATH . '/../log/reporte_integradores_'.date('Y-m-d').'.log');
        $format = '%timestamp% %priorityName% - [%remoteAddr%]: %message%' . PHP_EOL;
        $formatter = new Zend_Log_Formatter_Simple($format);
        $writer->setFormatter($formatter);
        $this->logger->addWriter($writer);
        $this->logger->setEventItem('remoteAddr', $_SERVER['REMOTE_ADDR']);

        //Habilitar layout
        $this->_helper->_layout->setLayout('reporte-integradores-layout');
        ini_set('memory_limit', '1024M');
        ini_set('max_execution_time', 300);

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

        $this->logger->info("login");
        $this->_helper->layout->disableLayout();

        $form = new Application_Form_Login();

        if( $this->getRequest()->isPost() ) {

            $formData = $this->getRequest()->getPost();

            if( $form->isValid( $formData ) ){

                $nick = $form->getValue('login_user');
                $clave = $form->getValue('login_pass');

                if( !empty( $nick ) && !empty( $clave ) ) {

                    if( array_key_exists($nick, $this->usuarios) && $clave == $this->usuarios[$nick]['clave'] ) {

                        $this->logger->info( 'LOGIN:[' . $nick . ']' );
                        $namespace = new Zend_Session_Namespace( "entermovil-reporte-integradores" );
                        $namespace->usuario = $nick;
                        $namespace->nombre = $this->usuarios[$nick]['nombre'];
                        $namespace->id_carrier = $this->usuarios[$nick]['id_carrier'];
                        $namespace->numeros = $this->numeros[$namespace->id_carrier];

                        $namespace->accesos = array(
                            'FULL'
                        );

                        $this->_redirect('/reporte-integradores/resumen/');

                    } else {

                        $this->_redirect('/reporte-integradores/login');
                    }

                } else {

                    $this->_redirect('/reporte-integradores/login');
                }

            } else {

                $this->_redirect('/reporte-integradores/login');
            }
        }
    }

    public function logoutAction() {

        $namespace = new Zend_Session_Namespace("entermovil-reporte-integradores");
        $this->logger->info('logout:[' . ( isset($namespace->usuario) ? $namespace->usuario : '') . ']('.$namespace->nombre.')');

        unset($namespace->usuario);
        unset($namespace->nombre);

        $namespace->unsetAll();
        unset($namespace);

        $this->_redirect('/reporte-integradores/login');
    }

    public function resumenAction(){

        $namespace = new Zend_Session_Namespace("entermovil-reporte-integradores");
        
        if( !isset( $namespace->usuario ) ){

            $this->_redirect('/reporte-integradores/login');
        }

        $this->view->headLink()->setStylesheet('/css/reportes_base.css', 'screen');
        $this->view->headScript()->appendFile('/js/reporte_integradores_resumen.js', 'text/javascript');
        $this->view->headLink()->appendStylesheet('/css/reportes_contenidos.css', 'screen');
        $this->view->headTitle()->append('Resumen');

        $fecha_seleccionada = $this->_getParam( 'fecha', null );

        if(!is_null($fecha_seleccionada)) {

            list($anho, $mes) = explode('-', $fecha_seleccionada);
            $mes = (int)$mes;

        } else {

            $anho = date('Y');
            $mes = date('n');
        }

        $datos = array();
        $id_carrier = $namespace->id_carrier;
        $total_general = array();
        $suscriptos_acumulados = array();

        $this->_setupRangoSeleccion( $anho, $mes );

        $this->view->nombre_mes = $this->meses[$mes-1];

        $this->view->anho = $anho;

        $this->view->mes = $mes;

        $this->view->dia_hoy = date('j');

        $this->view->rango_seleccion = $this->rango_seleccion;

        $numeros = $namespace->numeros;
        $total = 0;

        $datos = $this->_consulta( 'GET_COBROS_MES', array( 'anho' => $anho, 'mes' => $mes, 'id_carrier' => $id_carrier ) );

        foreach( $datos as $fila ){

            $total += $fila['cantidad'];
        }

        $this->view->numeros = $numeros;
        $this->view->total = $total;
        $this->view->datos = $datos;
        $this->view->total_general = $total_general;
        $this->view->carriers = $this->carriers;
        $this->view->suscriptos_acumulados = $suscriptos_acumulados;

    }

    public function reporteXDiaAction(){

        $namespace = new Zend_Session_Namespace("entermovil-reporte-integradores");

        if( !isset( $namespace->usuario ) ){

            $this->_redirect('/reporte-integradores/login');
        }

        $this->view->headLink()->setStylesheet('/css/reportes_base.css', 'screen');
        $this->view->headLink()->appendStylesheet('/css/reportes_contenidos.css', 'screen');
        $this->view->headScript()->appendFile('http://code.jquery.com/ui/1.10.0/jquery-ui.js', 'text/javascript');
        $this->view->headLink()->appendStylesheet('http://code.jquery.com/ui/1.10.0/themes/base/jquery-ui.css');
        $this->view->headScript()->appendFile('/js/reporte_integradores_reporte_x_dia.js', 'text/javascript');
        $this->view->headTitle()->append('Reporte x dia');

        $fecha_seleccionada = $this->_getParam( 'fecha', null );

        if( !is_null( $fecha_seleccionada ) ) {

            list($anho, $mes, $dia) = explode( '-', $fecha_seleccionada );
            $mes = (int)$mes;
            $dia = (int)$dia;

        } else {

            $fecha_seleccionada = date("Y-m-d", mktime(0, 0, 0, date("m"),date("d")-1,date("Y")));
        }

        $datos = array();
        $id_carrier = $namespace->id_carrier;

        $numeros = $namespace->numeros;
        $total = 0;

        $datos = $this->_consulta( 'GET_REPORTE_X_DIA', array(
            'fecha' => $fecha_seleccionada,
            'id_carrier' => $id_carrier )
        );

        foreach( $datos as $fila ){

            $total += $fila['cantidad'];
        }

        $this->view->fecha = $fecha_seleccionada;
        $this->view->numeros = $numeros;
        $this->view->total = $total;
        $this->view->datos = $datos;
        $this->view->carriers = $this->carriers;

    }

    public function generarCsvResumenAction(){

        $namespace = new Zend_Session_Namespace("entermovil-reporte-integradores");

        if( !isset( $namespace->usuario ) ){

            $this->_redirect('/reporte-integradores/login');
        }

        $fecha_seleccionada = $this->_getParam( 'fecha', null );

        if( !is_null( $fecha_seleccionada ) ) {

            list($anho, $mes) = explode( '-', $fecha_seleccionada );
            $mes = (int)$mes;

        } else {

            $anho = date('Y');
            $mes = date('n');
        }

        $anho_actual = date('Y');
        $mes_actual = date('n');
        $dia_actual = date('d');

        $nombres_archivos = array(

            206 => 'DotgoSmsGwTX.log',
            207 => 'PmovilSmsGwTX.log'
        );

        $datos = array();
        $archivo = null;
        $id_carrier = $namespace->id_carrier;
        $path  = "/home/entermovil/Web/www.entermovil.com.py/public/files/reporte-integradores/$id_carrier/$anho-$mes/varios";
        $this->logger->info( "archivo : $path" );

        if( $mes == $mes_actual ){
            //generar
            $archivo = $path . "/" . $nombres_archivos[$id_carrier] .".$anho-$mes-hasta-$dia_actual.tar.gz";
            $this->logger->info( "archivo :" . basename( $archivo ) );
            if( !is_file( $archivo ) ){
                $ruta_directorio = "/home/entermovil/Web/www.entermovil.com.py/public/files/reporte-integradores/$id_carrier/$anho-$mes";
                $comando = "cd $ruta_directorio; tar -zcvf $archivo dia";
                $this->logger->info( "ejecutamos el comando : $comando" );
                $ejecutar = $this->_ejecutar_comando( $comando );
            }else{

                $this->logger->info( "ya existe el archivo : " . basename( $archivo ) );
            }
        }else{

            $archivo = $path . "/". $nombres_archivos[$id_carrier] .".$anho-$mes.tar.gz";
            $this->logger->info( "el archivo solicitado es : $archivo" );
            if( !is_file( $archivo ) ){
                $ruta_directorio = "/home/entermovil/Web/www.entermovil.com.py/public/files/reporte-integradores/$id_carrier/$anho-$mes";
                $comando = "cd $ruta_directorio; tar -zcvf $archivo dia";
                $this->logger->info( "ejecutamos el comando : $comando" );
                $ejecutar = $this->_ejecutar_comando( $comando );
            }else{

                $this->logger->info( "ya existe el archivo : " . basename( $archivo ) );
            }
        }

        // various headers, those with # are mandatory
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header("Content-Type: application/force-download");
        header('Content-Disposition: attachment; filename=' . urlencode(basename($archivo)));
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . filesize($archivo));
        ob_clean();
        flush();
        readfile($archivo);

        exit;
    }

    public function generarCsvDiaAction(){

        $namespace = new Zend_Session_Namespace("entermovil-reporte-integradores");

        if( !isset( $namespace->usuario ) ){

            $this->_redirect('/reporte-integradores/login');
        }

        $nombres_archivos = array(

            206 => 'DotgoSmsGwTX.log',
            207 => 'PmovilSmsGwTX.log'
        );

        $fecha_seleccionada = $this->_getParam( 'fecha', null );

        if( !is_null( $fecha_seleccionada ) ) {

            list($anho, $mes, $dia) = explode( '-', $fecha_seleccionada );
            $mes = (int)$mes;
            $dia = (int)$dia;

        } else {

            $anho = date('Y');
            $mes = date('n');
            $dia = date('d');
        }

        $datos = array();
        $archivo = null;
        $id_carrier = $namespace->id_carrier;
        $path  = "/home/entermovil/Web/www.entermovil.com.py/public/files/reporte-integradores/$id_carrier/$anho-$mes/dia/";
        $nombre_archivo = $nombres_archivos[$id_carrier]. "." . $fecha_seleccionada . ".csv.gz";

        $archivo .= $path . $nombre_archivo;

        $this->logger->info( "archivo : $archivo" );

        //aun no fue comprimido
        if( is_file( $archivo ) ){

            // various headers, those with # are mandatory
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header("Content-Type: application/force-download");
            header('Content-Disposition: attachment; filename=' . urlencode( basename( $archivo ) ) );
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: no-cache');
            header('Content-Length: ' . filesize( $archivo ) );
            ob_clean();
            flush();
            readfile( $archivo );

        }else{

            $this->logger->info( "no existe el archivo: [$archivo]" );
        }

        exit;
    }

    private function _consulta( $accion, $datos = null ){

        $bootstrap = $this->getInvokeArg('bootstrap');
        $config = new Zend_Config(array(
            'database' => array(
                'adapter' => 'Pdo_Pgsql',
                'params'  => array(
                    'host'     => '10.0.2.8',
                    'username' => 'konectagw',
                    'password' => 'konectagw2006',
                    'dbname'   => 'gw'
                )
            )
        ));

        $db = Zend_Db::factory($config->database);
        $db->getConnection();
        $resultado = null;

        $namespace = new Zend_Session_Namespace("entermovil-reporte-integradores");

        if( $accion == 'GET_COBROS_MES' ){

            $sql = "select STR.fecha, STR.source_address, STR.command_status, STR.total as cantidad
                    from smpp_tx_resumen STR
                    where extract(year from fecha)::integer = ? and extract(month from fecha)::integer = ?
                    and id_carrier = ? and STR.source_address like '%@%' and STR.command_status = 0";

            $rs = $db->fetchAll( $sql, array( $datos['anho'], $datos['mes'], $datos['id_carrier'] ) );

            if( !empty( $rs ) ){

                foreach( $rs as $fila ){

                    $resultado[] = (array)$fila;
                }
            }
        }
        else if( $accion == 'GET_REPORTE_X_DIA' ){

            $sql = "select STR.fecha, STR.source_address, STR.command_status, STR.total as cantidad
                    from smpp_tx_resumen STR
                    where id_carrier = ? and fecha = ?";

            $rs = $db->fetchAll( $sql, array( $datos['id_carrier'], $datos['fecha'] ) );

            if( !empty( $rs ) ){

                foreach( $rs as $fila ){

                    $resultado[] = (array)$fila;
                }
            }
        }
        else if( $accion == 'GET_CSV_MES' ){

            $sql = "select ts_submit, source_address, destination_address, service_type, command_status,
                    coalesce( message_id, 'null') as message_id
                    from smpp_tx
                    where id_carrier = ? and extract(year from ts_submit) = ? and extract(month from ts_submit) = ?
                    order by 1,2,3";

            $rs = $db->fetchAll( $sql, array( $datos['id_carrier'], $datos['anho'], $datos['mes'] ) );

            if( !empty( $rs ) ){

                foreach( $rs as $fila ){

                    $resultado[] = (array)$fila;
                }
            }
        }

        $db->closeConnection();

        return $resultado;
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

    /**
     * GZIPs a file on disk (appending .gz to the name)
     *
     * From http://stackoverflow.com/questions/6073397/how-do-you-create-a-gz-file-using-php
     * Based on function by Kioob at:
     * http://www.php.net/manual/en/function.gzwrite.php#34955
     *
     * @param string $source Path to file that should be compressed
     * @param integer $level GZIP compression level (default: 9)
     * @return string New filename (with .gz appended) if success, or false if operation fails
     */
    private function _gzCompressFile($source, $level = 9){
        $dest = $source . '.gz';
        $mode = 'wb' . $level;
        $error = false;
        if ($fp_out = gzopen($dest, $mode)) {
            if ($fp_in = fopen($source,'rb')) {
                while (!feof($fp_in))
                    gzwrite($fp_out, fread($fp_in, 1024 * 512));
                fclose($fp_in);
            } else {
                $error = true;
            }
            gzclose($fp_out);
        } else {
            $error = true;
        }
        if ($error)
            return false;
        else
            return $dest;
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

