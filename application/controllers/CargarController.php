<?php
define('FFMPEG_LIBRARY', '/usr/bin/ffmpeg');

class CargarController extends Zend_Controller_Action
{
    var $logger;
    var $ua = array(
        'ancho' => '320'
    );
    var $solicitudValida = false;
    var $contenidos; //todos los contenidos
    var $formato;//formato a desplegar
    var $info_contenido;
    var $descripciones;
    var $servicio;
    var $preview;
    var $promociones = array(

        'PORTAL_PY' => '72',
        'PORTAL_SAL' => '74',
        'PORTAL_COL' => '82',
        'PORTAL_GT' => '58',
        'PORTAL_PY_ESCOBAR' => '77',
    );
    var $promociones2 = array(

        72 => 'PORTAL_PY',
        74 => 'PORTAL_SAL',
        82 => 'PORTAL_COL',
        58 => 'PORTAL_GT',
        77 => 'PORTAL_PY_ESCOBAR',
    );
    var $usuarios = array(

        'david' => array('clave' => 'david', 'nombre' => 'David Villalba'),
        'daas' => array('clave' => 'daas', 'nombre' => 'Derlis Argüello'),
    );
    var $tipos = array(
        'image/jpeg'=>'1',
        'audio/mpeg'=>'2',
        'video/3gpp'=>'3'
    );

    public function init()
    {
        $this->_configurarLogger();
        $this->_helper->_layout->setLayout('cargar-layout');
    }

    private function _configurarLogger() {

        //Creamos nuevo Logger para Mobile (ContentMagic)
        $logger = new Zend_Log();
        $writer = new Zend_Log_Writer_Stream(APPLICATION_PATH . '/../log/www.entermovil.com.py_interfaz_cargar_'.date('Y-m-d').'.log');
        $format = '%timestamp% %priorityName% - [%remoteAddr%]: %message%' . PHP_EOL;
        $formatter = new Zend_Log_Formatter_Simple($format);
        $writer->setFormatter($formatter);
        $logger->addWriter($writer);
        $logger->setEventItem('remoteAddr', $_SERVER['REMOTE_ADDR']); //CAMBIAR A SI O SI
        $this->logger = $logger;
    }

    public function indexAction() {

        $this->_forward('login','cargar');
        //$this->_forward('prueba','cargar');

        return;
    }

    public function loginAction() {

        $this->view->headLink()->setStylesheet('/css/reportes_base.css', 'screen');
        $this->view->headLink()->appendStylesheet('/css/acceso.css', 'screen');
        $this->view->headScript()->appendFile('/js/acceso.js', 'text/javascript');
        $this->view->ua = $this->ua;
        $this->_helper->_layout->disableLayout();

        $form = new Application_Form_Login();
        if($this->getRequest()->isPost()) {

            $formData = $this->getRequest()->getPost();

            if($form->isValid($formData)){

                $nick = $form->getValue('login_user');
                $clave = $form->getValue('login_pass');

                if(!empty($nick) && !empty($clave)) {

                    if(array_key_exists($nick, $this->usuarios) && $clave == $this->usuarios[$nick]['clave']) {

                        $this->logger->info('LOGIN:[' . $nick . ']');
                        $namespace = new Zend_Session_Namespace("entermovil-cargar-wap");
                        $namespace->usuario = $nick;
                        $namespace->nombre = $this->usuarios[$nick]['nombre'];
                        $namespace->accesos = array(
                            'FULL'
                        );
                        //$this->_forward('home', 'cargar');
                        $this->_redirect('/cargar/home');

                    } else {

                        $this->_redirect('/cargar/login');
                    }

                } else {

                    $this->_redirect('/cargar/login');
                }

            } else {

                $this->_redirect('/cargar/login');
            }
        }
    }

    public function logoutAction() {

        $namespace = new Zend_Session_Namespace("entermovil-cargar-wap");
        $this->logger->info('LOGOUT:[' . ( isset($namespace->usuario) ? $namespace->usuario : '')  . ']('.$namespace->nombre.')');

        unset($namespace->usuario);
        unset($namespace->nombre);

        $namespace->unsetAll();
        unset($namespace);

        $this->_redirect('/cargar/login');
    }

    private function _consulta($accion, $datos) {

        /*$config = new Zend_Config(array(
            'database' => array(
                'adapter' => 'Pdo_Pgsql',
                'params'  => array(
                    'host'     => 'localhost',//'190.128.201.42',//
                    'username' => 'postgres',
                    'password' => 'enter4589',
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


        if($accion == 'GET_CATEGORIAS'){

            $sql = "select  id_categoria, id_categoria_padre, nombre_categoria, ultimo_hijo, id_promocion from wap.categoria where estado = 1 and id_promocion = ? order by id_categoria desc";
            $rs = $db->fetchAll( $sql, array( $datos['id_promocion'] ) );

            $resultado = array();
            foreach($rs as $fila)
            {
                if( ( $fila['ultimo_hijo'] == 'true' ) ){
                    $datos['id_promocion'] = $fila['id_promocion'];
                    $datos['id_categoria'] = $fila['id_categoria'];
                    $fila['contenidos'] = $this->_consulta( 'GET_NRO_CONTENIDO', $datos );
                    $resultado[] = (array) $fila;
                }else{

                    $resultado[] = (array) $fila;
                }
            }
            //$this->logger->info('GET_CATEGORIAS:[' . print_r($resultado, true) . ']');

            return $resultado;
        }

        if($accion == 'GET_PREVIEW'){

            $sql = "SELECT * FROM wap.contenidos where id_categoria = ? and nivel <= ? and id_promocion = ? --and prioridad is null
                    ORDER BY id_contenido desc
                    LIMIT 3"; //cambie antes era 2 debo manejar las resoluciones para poder modificar
            $rs = $db->fetchAll( $sql, array( $datos['id_categoria'], $datos['nivel_acceso'], $datos['id_promocion'] ) );

            $resultado = array();
            foreach($rs as $fila){

                $resultado[] = (array) $fila;

            }

            $this->logger->info('GET_PREVIEW:[' . print_r($resultado, true) . ']');

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

        if($accion == 'GET_CONTENIDO') {

            $sql = "select t1.categoria, t1.id_contenido,t1.nombre_contenido,t1.estado,t1.nivel,t1.id_promocion,t2.nombre_categoria, t1.path, t1.tipo
                    from (select id_categoria as categoria,id_contenido,id_promocion,nivel, nombre_contenido, estado,path, tipo from
                    wap.contenidos where nivel = ? and id_promocion= ? and estado = 1) as t1,
                    (select nombre_categoria,id_promocion,id_categoria from wap.categoria where id_categoria = ? and id_promocion = ?) as t2
                    where t1.categoria = t2.id_categoria order by id_contenido desc";

            $rs = $db->fetchAll($sql, array( $datos['nivel'], $datos['id_promocion'], $datos['categoria'], $datos['id_promocion']));
            //si la categoria es audio no es necesario formatear
            $resultado = array();
            foreach($rs as $fila){

                $resultado[] = (array)$fila;
            }
            $this->logger->info('GET_CONTENIDO:[' . print_r($resultado, true) . ']');

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
            }
            //$this->logger->info('resultado:[' . print_r($resultado, true) . ']');
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
            }
            //$this->logger->info('resultado:[' . print_r($resultado, true) . ']');
            return $resultado;
        }

        if($accion == 'GET_SUSCRIPTO_CONTENIDOS') {

            $sql = "SELECT * FROM wap.usuarios WHERE cel = ?";
            $rs = $db->fetchRow($sql, array($datos['cel']));
            $resultado = array();
            if($rs) {

                $resultado = (array) $rs;
            }
            //$this->logger->info('resultado:[' . print_r($resultado, true) . ']');
            return $resultado;
        }

        if($accion == 'INSERTAR_SUSCRIPTO') {

            $status = $db->insert('promosuscripcion.suscriptos', $datos);
            //$this->logger->info('INSERTAR_SUSCRIPTO -> status:[' . $status . ']');
            return $status;
        }

        if($accion == 'CREAR_CATEGORIA') {

            $db = $db->insert('wap.categoria', $datos);
            $this->logger->info('CREAR_CATEGORIA -> status:[' . $db . ']');
            return $db;
        }

        if($accion == 'GET_INFO_RED'){

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


            $sql = "SELECT id_promocion FROM wap.promociones_x_paises WHERE id_carrier = ? and id_pais = ? and alias =?";
            $rs = $db->fetchRow( $sql, array( $datos['id_carrier'], $datos['id_pais'], $datos['alias'] ) );
            $resultado = array();
            if($rs) {

                $resultado = (array) $rs;
            }
            //$this->logger->info('GET_ID_PROMOCION:[' . print_r($resultado, true) . ']');
            return $resultado['id_promocion'];
        }

        if($accion == 'ELIMINAR_CATEGORIA'){

            $where = array(

                'id_categoria= ?' => $datos['id_categoria'],
            );
            $status = $db->delete('wap.categoria', $where);
            $this->logger->info('ELIMINAR_CATEGORIA -> status:[' . $status . ']');
            return $status;
        }

        if($accion == 'MODIFICAR_CATEGORIA'){

            $data = array(

                'nombre_categoria' => $datos['nombre_categoria'],
                'descripcion' => $datos['descripcion'],
            );
            $where = array(

                'id_categoria= ?' => $datos['id_categoria'],
                'id_promocion = ?' => $datos['id_promocion'],
            );
            $status = $db->update('wap.categoria', $data, $where);
            $this->logger->info('MODIFICAR_CATEGORIA -> status:[' . $status . ']');

            return;
        }

        if($accion == 'MODIFICAR_CONTENIDO'){

            $data = array(

                'nombre_contenido' => $datos['nombre_contenido'],
                'descripcion' => $datos['descripcion'],
            );
            $where = array(

                'id_contenido= ?' => $datos['id_contenido'],
                'id_promocion = ?' => $datos['id_promocion'],
            );
            $status = $db->update('wap.contenidos', $data, $where);
            $this->logger->info('MODIFICAR_CONTENIDO -> status:[' . $status . ']');

            return;
        }

        if($accion == 'GET_CATEGORIA'){

            $sql = "select * from wap.categoria where id_categoria = ?";
            $rs = $db->fetchRow($sql, array( $datos['id_categoria'] ));
            $resultado = array();
            if($rs){

                $resultado = (array)$rs;
            }

            return $resultado;
        }

        if($accion == 'INSERT_CONTENIDO'){

            $status = $db->insert('wap.contenidos', $datos);
            $newID = null;//$db->lastSequenceId('wap.contenidos_id_contenido_seq');
            $this->logger->info('INSERT_CONTENIDO' . $status);

            return $newID;
        }

        if($accion == 'UPDATE_CATEGORIA'){

            $data = array(

                'ultimo_hijo' => 'false',
            );
            $where = array(

                'id_categoria = ?' => $datos['id_categoria'],
            );
            $status = $db->update('wap.categoria', $data, $where);
            $this->logger->info('UPDATE_CATEGORIA' . $status);

            return $status;
        }

        if($accion == 'UPDATE_CATEGORIA_ELIMINAR'){

            $data = array(

                'ultimo_hijo' => 'true',
            );
            $where = array(

                'id_categoria = ?' => $datos['id_categoria'],
            );
            $status = $db->update('wap.categoria', $data, $where);
            $this->logger->info('UPDATE_CATEGORIA_ELIMINAR' . $status);

            return $status;
        }

        if( $accion == 'GET_NRO_CONTENIDO' ){

            $sql = 'select count(*) as nro_contenidos from wap.contenidos where id_promocion = ? and id_categoria = ?';
            $rs = $db->fetchRow( $sql, array( $datos['id_promocion'], $datos['id_categoria'] ) );
            $resultado = array();
            if( $rs ){

                $resultado = (array)$rs;
            }
            $this->logger->info('GET_NRO_CONTENIDO: ' . $resultado );

            return $resultado['nro_contenidos'];
        }

        if( $accion == 'GET_PROMOCIONES'){

            $sql = "select id_promocion, nombre, promocion, id_carrier from (select * from (select * from info_promociones left join (select id_promocion as id_promocion_pais,id_pais as id_pais_pais from promociones_x_pais ) as t1 on info_promociones.id_promocion = t1.id_promocion_pais) as t2 where alias = 'PORTAL') as t3 right join paises as t4 on t3.id_pais_pais = t4.id_pais where t3.numero is not null";
            $rs = $db->fetchAll($sql);
            $resultado = array();
            foreach($rs as $fila){

                $resultado[$fila['promocion'].'_'.$fila['nombre']] = (array)$fila;
            }
            $this->logger->info( 'GET_PROMOCIONES: ' . print_r( $resultado, true ) );

            return $resultado;
        }

        if( $accion == 'GET_BANNERS' ){

            $sql = "select * from wap.banners where id_promocion =? and id_categoria =? and nivel = ? order by orden asc";
            $rs = $db->fetchAll( $sql, array( $datos['id_promocion'], $datos['id_categoria'], $datos['nivel'] ) );
            $resultado = array();
            foreach( $rs as $fila ){

                $resultado[] = (array)$fila;
            }
            $this->logger->info('GET_BANNERS: ' . $resultado );

            return $resultado;
        }

        if( $accion == 'INSERT_BANNER' ){

            if( !isset( $datos['aparece']) ){

                //unset($datos['aparece']);
                for( $i = 0; $i<4; $i++ ){

                    $datos['id_categoria'] = $i;
                    $status = $db->insert('wap.banners', $datos);
                    $this->logger->info('INSERT_BANNER: ' . $status);
                }
            }else{

                unset($datos['aparece']);
                $status = $db->insert('wap.banners', $datos);
                $this->logger->info('INSERT_BANNER: ' . $status);
            }

            return $status;

        }

        if( $accion == 'ELIMINAR_BANNER' ){

            $where = array(

                'id = ?' => $datos['id'],
            );
            $status = $db->delete('wap.banners', $where );
            $this->logger->info('ELIMINAR_BANNER -> status:[' . $status . ']');

            return $status;

        }

        if( $accion == 'ELIMINAR_CONTENIDO' ){

            $where = array(

                'id_contenido = ?' => $datos['id_contenido'],
                'id_promocion =?' => $datos['id_promocion'],
            );
            $status = $db->delete('wap.contenidos', $where );
            $this->logger->info('ELIMINAR_CONTENIDO -> status:[' . $status . ']');

            return $status;
        }

        if( $accion == 'GET_BANNER' ){

            $sql = "select * from wap.banners where id = ?";
            $rs = $db->fetchRow( $sql, array( $datos['id'] ) );
            $resultado = array();
            if($rs){

                $resultado = (array)$rs;
            }
            $this->logger->info( 'GET_BANNER: ' . $resultado );

            return $resultado;
        }

        if($accion == 'MODIFICAR_BANNER'){

            $data = array(

                'orden' => $datos['orden'],
            );
            $where = array(

                'id= ?' => $datos['id'],
            );
            $status = $db->update('wap.banners', $data, $where);
            $this->logger->info('MODIFICAR_BANNER -> status:[' . $status . ']');

            return;
        }

        if( $accion == 'GET_BANNER_WAP_PREVIEW' ){

            $sql = "select * from wap.banners where nivel = ? and id_promocion = ? and id_categoria = ?";
            $rs = $db->fetchAll( $sql, array( $datos['nivel'], $datos['id_promocion'], $datos['id_categoria'] ) );
            $resultado = array();
            foreach( $rs as $fila ){

                $resultado[] = (array)$fila;
            }
            //$this->logger->info( 'GET_BANNER: '. print_r($resultado, true) );

            return $resultado;

        }

        if( $accion == 'GET_NIVELES' ){

            $sql = 'select nivel from wap.contenidos where id_promocion = ? group by nivel order by nivel asc';
            $rs = $db->fetchAll( $sql, array( $datos['id_promocion'] ) );
            $resultado = array();

            foreach( $rs as $fila ){

                $resultado[] = (array)$fila;
            }

            return $resultado;
        }

        if( $accion == 'ULTIMO_NIVEL_BANNER' ){

            $sql = "select orden from wap.banners where id_promocion = ? and id_categoria = ? and nivel = ? order by orden desc limit 1";
            $rs = $db->fetchRow($sql, array( $datos['id_promocion'], $datos['id_categoria'], $datos['nivel'] ));
            $resultado = array();
            if( $rs ){

                $resultado = (array)$rs;
            }
            $this->logger->info('ULTIMO_NIVEL_BANNER: ' . $resultado);

            return $resultado;
        }

        if( $accion == 'GET_CONTENIDOS_DUPLICAR' ){

            $sql = "select * from wap.contenidos where id_promocion = ?";
            $rs = $db->fetchAll( $sql, array( $datos['id_promocion'] ) );
            $resultado = array();
            foreach( $rs as $fila ){

                $resultado[] = (array)$fila;
            }

            return $resultado;
        }

        if( $accion == 'GET_BANNER_DUPLICAR' ){

            $sql = "select * from wap.banners where id_promocion = ?";
            $rs = $db->fetchAll( $sql, array( $datos['id_promocion'] ) );
            $resultado = array();
            foreach( $rs as $fila ){

                $resultado[] = (array)$fila;
            }
            //$this->logger->info( 'GET_BANNER: '. print_r($resultado, true) );

            return $resultado;

        }

        if( $accion == 'GET_CONTENIDO_PREVIEW_VIDEO' ){

            $sql = "select * from wap.contenidos where id_promocion = ? and id_contenido = ?";
            $rs = $db->fetchRow( $sql, array( $datos['id_promocion'], $datos['id_contenido'] ) );
            if( !empty( $rs ) ){
                $resultado = array();
                $resultado = (array)$rs;
                return $resultado;
            }else{
                return null;
            }
        }

        if( $accion == 'ASIGNAR_IMAGEN_PREVIEW_VIDEO' ){

            $data = array(

                'descripcion' => $datos['descripcion'],
            );
            $where = array(

                'id_contenido = ?' => $datos['id_contenido'],
                'id_promocion = ?' => $datos['id_promocion'],
            );

            $status = $db->update( 'wap.contenidos', $data, $where );
            $this->logger->info('MODIFICAR_BANNER -> status:[' . $status . ']');
        }
    }

    private function _buildTree(Array $data, $parent = 0) {

        $tree = array();
        foreach ($data as $d) {

            if ($d['id_categoria_padre'] == $parent) {

                $children = $this->_buildTree($data, $d['id_categoria']);

                if (!empty($children)) {

                    $d['hijos'] = $children;
                }
                $tree[$d['id_categoria']] = $d;
            }
        }

        return $tree;
    }
    //principal
    public function homeAction(){

        $this->view->headLink()->appendStylesheet('/css/reportes_pautas.css', 'screen');
        //$promociones = $this->_consulta('GET_PROMOCIONES', '');
        $this->view->promociones = $this->promociones;
        //$this->view->promociones = $promociones;

        //por defecto le envio porta_py
        $this->view->servicio = 'PORTAL_PY';

        if($this->getRequest()->isPost()) {

            $formData = $this->getRequest()->getPost();

            if(isset($formData['categorias'])) {

                $this->_redirect('/cargar/wap-preview-cargar/id-p/'.$formData['servicio'].'/l/1');
            }else if(isset($formData['contenidos'])){

                $this->_redirect('/cargar/categorias/id/' . $formData['servicio']);
            }
        }
    }

    public function cargarAction(){

        if($this->getRequest()->isPost()) {

            $formData = $this->getRequest()->getPost();

            if(isset($formData)) {

                print_r( $formData);
                $this->servicio = $formData['servicio'];
                $this->_redirect('cargar/home/id/' . $this->promociones[$formData['servicio']]);
            }
        }
    }
    //principal
    public function categoriasAction(){

        $namespace = new Zend_Session_Namespace("entermovil-cargar-wap");
        if(!isset($namespace->usuario) || empty($namespace->usuario)) {

            $this->_redirect('/cargar/login');
        }
        $this->view->headLink()->appendStylesheet('/css/cargar.css', 'screen');
        $parametros = $this->_getAllParams('id', 'id-categoria' , null);

        if(isset($parametros['id'])){

            if(isset($parametros['id-categoria'])){

                $datos = array(

                    'id_categoria' => $parametros['id-categoria'],
                );
                $nombre_categoria = $this->_consulta('GET_CATEGORIA', $datos);
                $this->view->id_categoria = $parametros['id-categoria'];
                $this->view->nombre_categoria = $nombre_categoria['nombre_categoria'];
                $this->view->descripcion = $nombre_categoria['descripcion'];
            }
            $this->view->id_promocion = $parametros['id'];
            $this->view->servicio = $this->promociones2[$parametros['id']];
            $datos = array(

                'id_promocion' => $parametros['id'],
            );
        }

        $this->view->categorias = $this->_consulta('GET_CATEGORIAS', $datos);
        /*  print_r($this->view->categorias);
          exit;*/
        if(!empty($this->view->categorias)){

            $this->view->categorias = $this->_buildTree($this->view->categorias);

            echo "<table id='categorias' cellspacing='3' cellpadding ='3' class='zebra-striped'>";
            $this->_printTree($this->view->categorias);
            echo "</table>";

        }else{

            $this->view->mensaje =  "<h2>No existen categorias cargadas para esta promocion</h2>";
        }
        //datos estaticos
        //id_categoria = 1 -> imagenes
        //id_categoria = 2 -> audios
        //id_categoria = 3 -> videos
    }

    public function nuevaCategoriaAction(){

        $namespace = new Zend_Session_Namespace("entermovil-cargar-wap");

        if(!isset($namespace->usuario) || empty($namespace->usuario)) {

            $this->_redirect('/cargar/login');
        }

        if($this->getRequest()->isPost()){

            $formData = $this->getRequest()->getPost();

            /*print_r($formData);
            exit;*/

            if( isset( $formData ) ) {

                $datos=array();
                $datos['nombre_categoria'] = $formData['nombre_categoria'];
                $datos['descripcion'] = '';
                $datos['id_categoria_padre'] = $formData['id_categoria'];
                $datos['ultimo_hijo'] = $formData['ultimo_hijo'];
                $datos['estado'] = $formData['estado'];
                $datos['id_promocion'] = $formData['id_promocion'];
                $this->_consulta('CREAR_CATEGORIA', $datos);
                //$this->_redirect('/cargar/categorias/id/' . $datos['id_promocion']);
                $this->_redirect('cargar/wap-preview-cargar/id-p/' . $datos['id_promocion'] .'/l/' . $formData['nivel'] );


            }
        }
    }

    public function modificarCategoriaAction(){

        if($this->getRequest()->isPost()){

            $formData = $this->getRequest()->getPost();

            if(isset($formData)) {

                $datos=array();
                $datos['id_categoria'] = $formData['id-categoria'];
                $datos['nombre_categoria'] = $formData['nombre-categoria'];
                $datos['descripcion'] = '';
                $datos['id_promocion'] = $formData['id-promocion'];

                $this->_consulta('MODIFICAR_CATEGORIA', $datos);
                //$this->_redirect('/cargar/categorias/id/' . $datos['id_promocion']);
                $this->_redirect('/cargar/wap-preview-cargar/id-p/' . $datos['id_promocion'].'/l/' . $formData['nivel']);
                //cargar/wap-preview-cargar/id-p/72/l/1

            }
        }
    }

    public function modificarContenidoAction(){

        if($this->getRequest()->isPost()){

            $formData = $this->getRequest()->getPost();

            if( isset( $formData ) ) {

                $datos=array();
                $datos['id_contenido'] = $formData['id_contenido'];
                $datos['nombre_contenido'] = $formData['nombre_contenido'];
                $datos['descripcion'] = '';
                $datos['id_promocion'] = $formData['id_promocion'];

                $this->_consulta('MODIFICAR_CONTENIDO', $datos);
                //$this->_redirect('/cargar/categorias/id/' . $datos['id_promocion']);
                $this->_redirect('/cargar/wap-preview-cargar/id-p/' . $formData['id_promocion'].'/l/' . $formData['nivel']);
                //cargar/wap-preview-cargar/id-p/72/l/1

            }
        }
    }

    public function modificarContenido2Action(){

        if($this->getRequest()->isPost()){

            $formData = $this->getRequest()->getPost();

            if( isset( $formData ) ) {

                $datos=array();
                $datos['id_contenido'] = $formData['id_contenido'];
                $datos['nombre_contenido'] = $formData['nombre_contenido'];
                $datos['descripcion'] = '';
                $datos['id_promocion'] = $formData['id_promocion'];

                $this->_consulta('MODIFICAR_CONTENIDO', $datos);
                //$this->_redirect('/cargar/categorias/id/' . $datos['id_promocion']);
                $this->_redirect('/cargar/contenidos-cargados/id-p/' . $formData['id_promocion'].'/id/'. $formData['id_categoria']  . '/l/' . $formData['nivel']);
            }
        }
    }

    public function eliminarCategoriaAction(){

        $datos = array();
        $parametros = $this->_getAllParams('id', 'id-p', 'idp', null);
        if(!is_null($parametros)){

            $datos['id_categoria'] = $parametros['id'];
            $this->_consulta('ELIMINAR_CATEGORIA', $datos);
            if($parametros['idp'] != '0'){

                $actualizar= array();
                $actualizar['id_categoria'] = $parametros['idp'];
                $this->_consulta('UPDATE_CATEGORIA_ELIMINAR', $actualizar );
            }
            $this->_redirect('/cargar/categorias/id/' . $parametros['id-p']);
        }
    }
    //principal
    public function contenidosAction(){

        $namespace = new Zend_Session_Namespace("entermovil-cargar-wap");
        if(!isset($namespace->usuario) || empty($namespace->usuario)) {

            $this->_redirect('/cargar/login');
        }

        $datos = array();
        $parametros = $this->_getAllParams('id', 'id-p', null);
        if(!is_null($parametros)){

            if(isset($parametros['id']) && isset($parametros['id-p'])){

                $this->view->servicio = $this->promociones2[$parametros['id-p']];
                $this->view->id_promocion = $parametros['id-p'];
                $datos['categoria'] = $parametros['id'];
                $contenidos = $this->_consulta('GET_CONTENIDO', $datos);
                $this->view->contenidos = $contenidos;
                $this->view->id_categoria = $parametros['id'];
                if(!empty($contenidos)){

                    $this->view->nombre_categoria = $contenidos[0]['nombre_categoria'];

                }else{

                    $datos['id_categoria'] = $parametros['id'];
                    $nombre_categoria = $this->_consulta('GET_CATEGORIA', $datos);
                    $this->view->nombre_categoria = $nombre_categoria['nombre_categoria'];

                }

            }else{

                $this->view->servicio = $this->promociones2[$parametros['id']];
                $this->view->id_promocion = $parametros['id'];
                $datos['id_promocion'] = $parametros['id'];
                $this->view->categorias = $this->_consulta('GET_CATEGORIAS', $datos);
                $this->view->categorias = $this->_buildTree($this->view->categorias);

                echo "<form action='/cargar/contenidos-intermedio' method='post' id='contenidos-intermedio'>";
                echo "<input type='hidden' id= 'id_promocion' name ='id_promocion' value='".$parametros['id']."'>";
                echo "Categorias: <select id='id_contenido' name='id_contenido'>";
                $this->_crearArbol($this->view->categorias, 0, null, 8);
                echo "</select><input type='submit' value='confirmar'>";
                echo "</form>";
            }
        }else{

            echo "<h1>Ocurrio un error inesperado. Por favor vuelva a la pagina anterior</h1>";
            exit;
        }
    }

    public function contenidosIntermedioAction(){

        if($this->getRequest()->isPost()){

            $formData = $this->getRequest()->getPost();
            if(isset($formData)){

                $this->_redirect('/cargar/contenidos/id/'.$formData['id_contenido'].'/id-p/'.$formData['id_promocion']);
            }
            else{

                echo "<h1>Ocurrio un error inesperado</h1>";
            }
        }
    }

    private function _printTree($tree, $r = 0, $p = null ) {

        foreach ($tree as $i => $t) {

            $dash = ($t['id_categoria_padre'] == 0) ? '' : str_repeat('-', $r) .' ';

            if($t['id_categoria_padre'] == 0){

                $eliminar = ( isset( $t['hijos'] ) )? '': "<td><a href=". "/cargar/eliminar-categoria/id/". $t['id_categoria'] . "/id-p/". $t['id_promocion']."/idp/".$t['id_categoria_padre']. ">" ."Eliminar</a></td>";
                echo "<tr>
                        <td class='categoria_padre'>
                            <h2>".$dash. strtoupper($t['nombre_categoria'])."</h2>
                        </td>
                        <td><a href="."/cargar/categorias/id/".$t['id_promocion']."/id-categoria/".$t['id_categoria'].">Editar</a></td>".$eliminar."
                        <td class='ultima_celda'><a href=". "/cargar/sub-categoria/id/". $t['id_categoria'] . "/id-p/". $t['id_promocion'].">Agregar Subcategoria</a></td>
                    </tr>";

            }else if($t['id_categoria_padre'] != 0 && $t['ultimo_hijo'] == 'false'){

                $eliminar = (isset($t['hijos']))? "<td class='ultima_celda'><a href=". "/cargar/sub-categoria/id/". $t['id_categoria'] . "/id-p/". $t['id_promocion'] ."/idp/".$t['id_categoria_padre'].">Agregar Subcategoria</a></td></tr>  ": "<td><a href=". "/cargar/eliminar-categoria/id/". $t['id_categoria'] . "/id-p/". $t['id_promocion']. ">" ."Eliminar</a></td>"."
                <td class='ultima_celda'><a href=". "/cargar/sub-categoria/id/". $t['id_categoria'] . "/id-p/". $t['id_promocion'].">Agregar Subcategoria</a></td>
                </tr>";
                echo "<tr>
                        <td class='ultima_celda'>
                            <h3>".$dash. strtoupper($t['nombre_categoria'])."</h3>
                        </td>
                        <td><a href="."/cargar/categorias/id/".$t['id_promocion']."/id-categoria/".$t['id_categoria'].">Editar</a></td>".$eliminar;

            }else if( $t['id_categoria_padre'] != 0 && !isset($t['hijos']) && $t['ultimo_hijo'] == true ){

                $eliminar = ( $t['contenidos'] > 0 )? '': "<td><a href=". "/cargar/eliminar-categoria/id/". $t['id_categoria'] . "/id-p/". $t['id_promocion']."/idp/".$t['id_categoria_padre']. ">" ."Eliminar</a></td>";
                echo "<tr>
                        <td class='ultima_celda'>
                            <p>".$dash. strtoupper($t['nombre_categoria'])."</p>
                        </td>
                        <td><a href="."/cargar/categorias/id/".$t['id_promocion']."/id-categoria/".$t['id_categoria'].">Editar</a></td>"."<td class='ultima_celda'><a href=". "/cargar/sub-categoria/id/". $t['id_categoria'] . "/id-p/". $t['id_promocion'] ."/p/1".">Agregar Subcategoria</a></td>".
                    $eliminar."
                        <td class='ultima_celda'><a href=". "/cargar/contenidos/id/". $t['id_categoria'] . "/id-p/". $t['id_promocion'].">Agregar Contenido</a></td>
                    </tr>";
            }

            if ($t['id_categoria_padre'] == $p) {
                // reset $r
                $r = 0;
            }
            if (isset($t['hijos'])) {

                $this->_printTree($t['hijos'], ++$r, $t['id_categoria_padre'] );
            }
        }
    }

    private function _crearArbol($tree, $r = 0, $p = null, $id) {

        foreach ($tree as $i => $t) {
            $dash = ($t['id_categoria_padre'] == 0) ? '' : str_repeat('-', $r) .' ';
            if($t['id_categoria']== $id){

                printf("\t<option value='%d' selected='selected'>%s%s</option>\n", $t['id_categoria'], $dash, $t['nombre_categoria']);
            }else{
                printf("\t<option value='%d'>%s%s</option>\n", $t['id_categoria'], $dash, $t['nombre_categoria']);
            }
            if ($t['id_categoria_padre'] == $p) {
                // reset $r
                $r = 0;
            }
            if (isset($t['hijos'])) {

                $this->_crearArbol($t['hijos'], ++$r, $t['id_categoria_padre'], $id);
            }
        }
    }

    public function nuevoContenidoAction(){

        if( $this->getRequest()->isPost() ){

            $formData = $this->getRequest()->getPost();

            if( isset( $formData ) ){

                //$target_path = 'C:/ENTERMOVIL/web/www.entermovil.com.py/public/img/wap-imagenes-previews-videos/promociones/' . $formData['id_promocion']; //cambiar
                //ENTERMOVIL
                $target_path = '/home/entermovil/web/www.entermovil.com.py/data/portal-movil/promociones/' . $formData['id_promocion'];//online
                //si $target_path no es una direccion
                $path_carpeta_trabajo = $target_path;
                $this->logger->info( 'carpeta de trabajo: ' . $path_carpeta_trabajo );

                if( !is_dir( $target_path ) ){

                    mkdir($target_path);
                    $this->logger->info( 'Creamos la carpeta' . $target_path );
                }

                $target_path = $target_path .'/'. basename( $_FILES['userFile1']['name'] );

                $this->logger->info( 'target_path: ' . $path_carpeta_trabajo );
                //para la extension
                $contenido_a_insertar = pathinfo( $_FILES['userFile1']['name'] );

                //generar content type:
                $content_type = array(

                    'mp3' => 'audio/mpeg',
                    '3gp' => 'video/3gpp',
                    'mp4' => 'video/mp4',
                    'jpg' => 'image/jpeg'
                );

                $formData['tipo'] = $content_type[$contenido_a_insertar['extension']];
                $formData['tamanho'] = filesize($_FILES['userFile1']['tmp_name']);
                $formData['path'] = $target_path;
                $formData['descargas'] = '0';
                $this->logger->info( 'FormDAta: '. print_r($formData, true) );

                if( $formData['tipo'] != 'video/3gpp' ){

                    if(move_uploaded_file( $_FILES['userFile1']['tmp_name'], $target_path ) ) {

                        $insertar = $this->_consulta( 'INSERT_CONTENIDO', $formData );

                        $this->logger->info( "El archivo ".  basename( $_FILES['userFile1']['name'])." ha sido cargado exitosamente" );
                        //$this->_redirect('/cargar/contenidos/id/'.$formData['id_categoria'].'/id-p/' .$formData['id_promocion']);
                        /*if( $formData['tipo'] == 'video/3gpp' || $formData['tipo'] == 'video/mp4' ){

                            //ANTES DE REDIRECCIONAR GENERO LOS POSIBLES PREVIEWS
                            $duracion = $this->_duracion_video( $target_path );
                            $this->logger->info( 'duracion: ' . $duracion );
                            //GENERAR SECUENCIA DE IMAGENES
                            $this->_generarSecuenciaImagenes( $target_path, $path_carpeta_trabajo, $duracion );
                        }*/
                        $this->_redirect( '/cargar/wap-preview-cargar/id-p/'.$formData['id_promocion'].'/l/' .$formData['nivel']);
                        //$this->_forward('wap-preview-cargar','cargar','id-p', $formData['id_promocion'],'l',$formData['nivel']);

                    } else{

                        echo "<h2>Ha habido un problema al cargar el archivo!</h2>";
                        $this->logger->info( "Ha habido un problema al cargar el archivo!" );
                    }
                }else{

                    if( move_uploaded_file( $_FILES['userFile1']['tmp_name'], $target_path ) ){

                        $this->logger->info('ARCHIVO DE VIDEO - GENERAR PREVIEW');
                        $id_contenido_a_generar_preview = $this->_consulta('INSERT_CONTENIDO', $formData);
                        $this->_redirect('/cargar/previews-disponibles/id-promocion/'.$formData['id_promocion'].'/id-contenido/' .$id_contenido_a_generar_preview .'/nivel/'. $formData['nivel'] );
                    }
                }
            }
        }
    }

    public function subCategoriaAction(){

        if(!$this->getRequest()->isPost()){
            $parametros = $this->_getAllParams('id', 'id-p', 'p', null);

            if(!is_null($parametros)){

                $this->view->id_promocion = $parametros['id-p'];
                $this->view->servicio = $this->promociones2[$parametros['id-p']];
                $this->view->id_categoria_padre = $parametros['id'];
                if(isset($parametros['p'])){

                    $this->view->ultimo_hijo = 'true';
                    $this->view->actualizar = 'true';

                }else{

                    $this->view->ultimo_hijo = 'true';
                    $this->view->actualizar = 'false';
                }
                $datos['id_categoria'] = $parametros['id'];
                $nombre_categoria = $this->_consulta('GET_CATEGORIA', $datos);
                $this->view->categoria_padre = $nombre_categoria['nombre_categoria'];
            }
        }else{

            $formData = $this->getRequest()->getPost();
            if(isset($formData)){

                $datos=array();
                $datos['nombre_categoria'] = $formData['nombre_categoria'];
                $datos['descripcion'] = $formData['descripcion'];
                $datos['id_categoria_padre'] = $formData['id_categoria_padre'];
                $datos['ultimo_hijo'] = $formData['ultimo_hijo'];
                $datos['estado'] = $formData['estado'];
                $datos['id_promocion'] = $formData['id_promocion'];
                $this->_consulta('CREAR_CATEGORIA', $datos);
                if($formData['actualizar'] == 'true'){

                    $actualizar= array();
                    $actualizar['id_categoria'] = $formData['id_categoria_padre'];
                    $this->_consulta('UPDATE_CATEGORIA', $actualizar );
                }
                $this->_redirect('/cargar/categorias/id/' . $datos['id_promocion']);
            }
        }
    }

    //preview
    public function wapPreviewAction(){

        $namespace = new Zend_Session_Namespace("entermovil-cargar-wap");
        if(!isset($namespace->usuario) || empty($namespace->usuario)) {

            $this->_redirect('/cargar/login');
        }
        //$this->_helper->_layout->setLayout('wap-layout');
        if(!$this->getRequest()->isPost()){

            $id_promocion = $this->_getParam('id-p', null);
            if( !is_null( $id_promocion ) ){

                $this->view->id_promocion = $id_promocion;
                $this->view->servicio = $this->promociones2[$id_promocion];

            }
        }else{

            $formData = $this->getRequest()->getPost();

            if( isset( $formData ) ){

                $es_numerico = (int)$formData['nivel'];

                if( is_numeric( $es_numerico ) ){

                    $this->view->id_promocion =  $formData['id_promocion'];
                    $this->view->servicio = $this->promociones2[$formData['id_promocion']];
                    $this->view->nivel_seleccionado = $formData['nivel'];
                    $datos['id_promocion'] = $formData['id_promocion'];
                    $categorias = $this->_consulta('GET_CATEGORIAS', $datos);
                    $categorias = $this->_generarArbol($categorias, 0, $formData['nivel']);
                    $this->_preview($categorias);
                    $contenidos = $this->preview;
                    $this->view->contenidos = $this->_generarPreview();
                    $this->view->contenidos['categorias']  = $categorias;
                    $this->view->contenidos_video = $categorias['3']['hijos'];
                    $this->view->controller = $this;
                    $this->view->nivel  = $formData['nivel'];
                }else{

                    echo "<h3>El nivel introducido no es numerico</h3>";
                }
                /*print_r($this->view->contenidos['categorias']);
                exit;*/
                //$this->_redirect('/cargar/wap-preview/id-p/'.$formData['id_promocion']);
            }
        }
    }

    private function _generarArbol(Array $data, $parent = 0, $nivel) {

        $tree = array();

        foreach ($data as $d) {

            if ($d['id_categoria_padre'] == $parent) {

                $children = $this->_generarArbol($data, $d['id_categoria'], $nivel);

                if (!empty($children)) {

                    $d['hijos'] = $children;
                }
                $tree[$d['id_categoria']] = $d;
                $datos = array(

                    'id_promocion' => $d['id_promocion'],
                    'id_categoria' => $d['id_categoria'],
                    'nivel_acceso' => $nivel,
                );

                $preview = $this->_consulta('GET_PREVIEW', $datos);

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

    private function _convertirImagenes( $path ){

        require_once APPLICATION_PATH . '/models/phMagick.php';

        $nombre_logo_original_jpg = $path;

        $nombre_archivo = basename($path);

        $this->logger->info('IMAGEN-ORIGINAL:[' . $nombre_logo_original_jpg . ']');

        $ancho_imagen = round( (63*(320))/320 );

        $this->logger->info('IMAGEN-ANCHO:[' . $ancho_imagen . ']');

        $nombre_logo_max_ancho_jpg = 'img/wap-imagenes/cache/imagen_w_' .$ancho_imagen.'x'.$ancho_imagen.'_px_' . $nombre_archivo;
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
                    if( $id_hijo != '39' ){
                        foreach( $contenidos as $i => $datos ){
                            if( !is_null( $datos['prioridad'] ) ){
                                $preview_contenidos[$id_padre][] = $datos;
                                $estructura_auxliar[$id_padre]['cantidad_contenidos']++;
                            }

                            $estructura_auxliar[$id_padre]['cantidad_contenidos_categoria']++;
                        }
                    }
                }
            }

            foreach ( $this->preview as $id_padre => $datos_hijos ){
                $indice = 0;

                while( $estructura_auxliar[$id_padre]['cantidad_contenidos'] < 6 && $estructura_auxliar[$id_padre]['cantidad_contenidos'] != $estructura_auxliar[$id_padre]['cantidad_contenidos_categoria'] ){
                    foreach( $datos_hijos as $id_hijo => $contenidos ){
                        if( $id_hijo != '39' ){
                            if( isset( $this->preview[$id_padre][$id_hijo][$indice]) && $this->preview[$id_padre][$id_hijo][$indice]['prioridad'] == null ){
                                $preview_contenidos[$id_padre][] = $this->preview[$id_padre][$id_hijo][$indice];
                                $estructura_auxliar[$id_padre]['cantidad_contenidos']++;
                            }
                        }
                    }
                    $indice++;
                }
            }

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

    public function pruebaAction(){

        $this->_helper->layout->disableLayout();

    }

    public function cargarBannersAction(){

        $namespace = new Zend_Session_Namespace("entermovil-cargar-wap");
        if(!isset($namespace->usuario) || empty($namespace->usuario)) {

            $this->_redirect('/cargar/login');
        }

        $datos = array();
        $parametros = $this->_getAllParams( 'id-p', 'id', null );
        if( !is_null( $parametros ) ){

            if( isset( $parametros['id-p'] ) ){

                if( isset( $parametros['id'] ) ){

                    $datos = array(

                        'id' => $parametros['id'],
                    );
                    $banner = $this->_consulta( 'GET_BANNER', $datos );

                    $this->view->nombre = $banner['nombre'];
                    $this->view->descripcion = $banner['descripcion'];
                    $this->view->orden = $banner['orden'];
                    $this->view->nivel = $banner['nivel'];
                    $this->view->id_banner = $parametros['id'];

                }
                $this->view->servicio = $this->promociones2[$parametros['id-p']];
                $this->view->id_promocion = $parametros['id-p'];
                $contenidos = $this->_consulta('GET_BANNERS', '');
                /*print_r($contenidos);*/
                $this->view->contenidos = $contenidos;

            }else{


            }
        }else{

            echo "<h1>Ocurrio un error inesperado. Por favor vuelva a la pagina anterior</h1>";
            exit;
        }
    }

    public function nuevoBannerAction(){

        if($this->getRequest()->isPost()){

            $formData = $this->getRequest()->getPost();
            if(isset($formData)){

                //$target_path = '/home/Server/ENTERMOVIL/web/www.entermovil.com.py/data/portal-movil/promociones/' . $formData['id_promocion']; //cambiar
                $target_path = '/home/entermovil/web/www.entermovil.com.py/data/portal-movil/banners/' . $formData['id_promocion'];//online

                if(!is_dir($target_path)){

                    mkdir($target_path);
                    $this->logger->info('Creamos la carpeta' . $target_path);
                }

                $target_path = $target_path .'/'. basename( $_FILES['userFile']['name']);

                //para la extension
                $contenido_a_insertar = pathinfo($_FILES['userFile']['name']);

                //generar content type:
                $content_type = array(

                    'jpg' => 'image/jpeg'
                );
                $formData['tipo'] = $content_type[$contenido_a_insertar['extension']];
                $formData['tamanho'] = filesize($_FILES['userFile']['tmp_name']);
                $formData['path'] = $target_path;
                $formData['semana'] = '1';

                //procesar orden
                $datos['id_categoria'] = $formData['id_categoria'];
                $datos['nivel'] = $formData['nivel'];
                $datos['id_promocion'] = $formData['id_promocion'];
                $ultimo_nivel = $this->_consulta('ULTIMO_NIVEL_BANNER', $datos );

                /*print_r($ultimo_nivel);
                exit;*/
                if( empty( $ultimo_nivel ) ){

                    $formData['orden'] = 5;
                }else{

                    $formData['orden'] = $ultimo_nivel['orden'] + 5;
                }
                /*print_r($formData);
                exit;*/

                $this->logger->info( 'FormDAta: '. print_r($formData, true) );

                if(move_uploaded_file($_FILES['userFile']['tmp_name'], $target_path)) {

                    $insertar = $this->_consulta('INSERT_BANNER', $formData);
                    $this->logger->info( "El archivo ".  basename( $_FILES['userFile']['name'])." ha sido cargado exitosamente" );
                    //$this->_redirect('/cargar/cargar-banners/id-p/' .$formData['id_promocion']);
                    $this->_redirect('/cargar/wap-preview-cargar/id-p/' .$formData['id_promocion'].'/l/'.$formData['nivel']);
                } else{
                    echo "<h2>Ha habido un problema al cargar el archivo!</h2>";
                    $this->logger->info( "Ha habido un problema al cargar el archivo!" );
                }
            }
        }
    }

    public function eliminarBannerAction(){

        $id_banner = $this->_getAllParams( 'id-p', 'id-c', 'id', 'l', null );


        if( !is_null( $id_banner ) ){

            $datos['id'] = $id_banner['id'];

            $status = $this->_consulta('ELIMINAR_BANNER', $datos );

            $this->_redirect( '/cargar/banners-cargados/id-p/' . $id_banner['id-p'] . '/id-c/'.$id_banner['id-c'].'/l/'. $id_banner['l'] );
        }
    }

    public function modificarBannerAction(){

        $parametros = $this->_getAllParams('id-p','id-c', 'id', 'l', 'a', null);
        if( !is_null( $parametros ) ){

            $datos['id_promocion'] = $parametros['id-p'];
            $datos['id_categoria'] = $parametros['id-c'];
            $datos['nivel'] = $parametros['l'];
            $lista_banners = $this->_consulta( 'GET_BANNERS', $datos );

            print_r( $lista_banners );
            $lista = array();
            foreach($lista_banners as $indice=>$valor){

                $lista[$valor['id']] = $indice;

            }
            print_r($lista);

            printf("valor actual: (%s)\n", $lista[$parametros['id']]);
            printf("valor a cambiar: (%s)\n", $lista[$parametros['id']]+$parametros['a']);

            //actual
            $tmp = $lista_banners[$lista[$parametros['id']]]['orden'];
            $datos_mod['id'] = $parametros['id'];
            $datos_mod['orden'] = $lista_banners[$lista[$parametros['id']]+$parametros['a']]['orden'];

            printf("nuevo orden actual: (%s)\n",  $datos_mod['orden']);
            print_r($datos_mod);
            printf("id: (%s)\n", $lista_banners[$lista[$parametros['id']]]['id']);
            $status = $this->_consulta('MODIFICAR_BANNER', $datos_mod);

            //modificado
            $datos_mod['id'] = $lista_banners[$lista[$parametros['id']]+$parametros['a']]['id'];
            $datos_mod['orden'] = $tmp;
            printf("nuevo orden del siguiente: (%s)\n", $tmp);
            print_r($datos_mod);
            printf("id: (%s)\n",$lista_banners[$lista[$parametros['id']]+$parametros['a']]['id']);
            $status = $this->_consulta('MODIFICAR_BANNER', $datos_mod);

            //exit;

            $this->_redirect('/cargar/banners-cargados/id-p/' . $parametros['id-p']. '/id-c/' . $parametros['id-c'] . '/l/' . $parametros['l']);
        }
    }

    public function obtenerBanner( $id_promocion, $nivel, $n ){

        $banner = array();
        $datos['nivel'] = $nivel;
        $datos['id_promocion'] = $id_promocion;
        $datos['id_categoria'] = $n;
        $banners = $this->_consulta('GET_BANNER_WAP_PREVIEW', $datos);
        if( !empty( $banners ) ){
            foreach( $banners as $indice=>$banner ){

                $banners_mostrar[] = $this->_convertirImagenesBanners( $banner['path'] );
            }
        }else{
            $banners_mostrar['0'] = '';
        }


        return $banners_mostrar;
    }

    private function _convertirImagenesBanners( $path ){

        require_once APPLICATION_PATH . '/models/phMagick.php';

        $nombre_logo_original_jpg = $path;

        $nombre_archivo = basename($path);

        $this->logger->info('IMAGEN-ORIGINAL:[' . $nombre_logo_original_jpg . ']');

        $ancho_imagen = round( ( 320 )*0.85 );
        //$alto_imagen = 50;

        $this->logger->info('IMAGEN-ANCHO:[' . $ancho_imagen . ']');

        $nombre_logo_max_ancho_jpg = 'img/wap-imagenes/cache/imagen_banner_w' . $ancho_imagen . $nombre_archivo;
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

    public function wapPreviewCargarAction(){

        $namespace = new Zend_Session_Namespace("entermovil-cargar-wap");
        if(!isset($namespace->usuario) || empty($namespace->usuario)) {

            $this->_redirect('/cargar/login');
        }

        $id_promocion = $this->_getAllParams('id-p', 'l', null);

        if( !is_null( $id_promocion ) ){
            $this->view->ua = array(
                'ancho_imagen'=>'63',
            );
            $this->view->id_promocion = $id_promocion['id-p'];
            $this->view->servicio = $this->promociones2[$id_promocion['id-p']];
            $datos['id_promocion'] = $id_promocion['id-p'];
            $this->view->categorias = $this->_consulta('GET_CATEGORIAS', $datos);
            $this->view->controller  = $this;
            $datos['id_promocion'] = $id_promocion['id-p'];
            $this->view->niveles = $this->_consulta('GET_NIVELES', $datos);

            $this->view->siguiente_nivel = count($this->view->niveles) + 1;

            if( !isset( $id_promocion['l'] ) ){

                $this->view->nivel = '1';
                $categorias = $this->_generarArbol($this->view->categorias, 0, $this->view->nivel);

                $this->_preview($categorias);
                $contenidos = $this->preview;

                $this->view->contenidos = $this->_generarPreview();
                $this->view->contenidos['categorias']  = $categorias;
                $this->view->contenidos_video = $categorias['3']['hijos'];
                $this->view->contenidos_imagenes = $categorias['1']['hijos'];

            }else{

                $this->view->nivel = $id_promocion['l'];
                $categorias = $this->_generarArbol( $this->view->categorias, 0, $this->view->nivel );

                $this->_preview($categorias);
                $contenidos = $this->preview;

                $this->view->contenidos = $this->_generarPreview();
                $this->view->contenidos['categorias']  = $categorias;
                $this->view->contenidos_video = $categorias['3']['hijos'];
                $this->view->contenidos_imagenes = $categorias['1']['hijos'];
                $this->view->promociones =  $this->promociones;

            }
        }
    }

    public function obtenerCategorias( $id_promocion ){


        $id_promocion =  '72';
        $this->view->id_promocion = $id_promocion;
        $this->view->servicio = $this->promociones2[$id_promocion];
        $datos['id_promocion'] = $id_promocion;
        $this->view->categorias = $this->_consulta('GET_CATEGORIAS', $datos);
        $this->view->categorias = $this->_buildTree($this->view->categorias);
        echo "<form action='/cargar/contenidos-intermedio' method='post' id='contenidos-intermedio'>";
        echo "<input type='hidden' id= 'id_promocion' name ='id_promocion' value='".$id_promocion."'>";
        echo "Categorias: <select id='id_contenido' name='id_contenido'>";
        $this->_crearArbol($this->view->categorias, 0, null, 5);
        echo "</select><input type='submit' value='confirmar'>";
        echo "</form>";

    }

    public function contenidosCargadosAction(){

        $this->_helper->_layout->setLayout('cargar-layout');

        $parametros = $this->_getAllParams( 'id-p', 'id', 'l', null );

        if( !is_null( $parametros ) ){

            $this->view->servicio = $this->promociones2[$parametros['id-p']];
            $this->view->id_promocion = $parametros['id-p'];
            $datos['categoria'] = $parametros['id'];
            $datos['id_promocion'] = $parametros['id-p'];
            $datos['nivel']= $parametros['l'];
            $contenidos = $this->_consulta('GET_CONTENIDO', $datos);
            if( !empty( $contenidos ) ){
                if($contenidos['0']['tipo'] == 'image/jpeg'){

                    foreach( $contenidos as $indice=>$contenido ){

                        $contenidos[$indice]['path'] = '/' . $this->_convertirImagenes( $contenido['path'] );
                    }
                    $this->view->tipo = '1';
                }else{

                    $this->view->tipo = '0';
                }
            }
            $this->view->contenidos = $contenidos;
            $this->view->id_categoria = $parametros['id'];

            if( !empty( $contenidos ) ){

                $this->view->nombre_categoria = $contenidos[0]['nombre_categoria'];

            }else{

                $datos['id_categoria'] = $parametros['id'];
                $nombre_categoria = $this->_consulta('GET_CATEGORIA', $datos);
                $this->view->nombre_categoria = $nombre_categoria['nombre_categoria'];

            }
        }
    }

    public function eliminarContenidoAction(){

        $parametros = $this->_getAllParams( 'id-p', 'id-c', 'id', 'l', null );

        if( !is_null( $parametros ) ){

            $datos['id_contenido'] = $parametros['id'];
            $datos['id_promocion'] = $parametros['id-p'];
            $status = $this->_consulta( 'ELIMINAR_CONTENIDO', $datos );
            $this->_redirect('/cargar/contenidos-cargados/id-p/'.$parametros['id-p'].'/id/' .$parametros['id-c'] .'/l/' . $parametros['l']);
        }
    }

    public function bannersCargadosAction(){

        $namespace = new Zend_Session_Namespace("entermovil-cargar-wap");
        if(!isset($namespace->usuario) || empty($namespace->usuario)) {

            $this->_redirect('/cargar/login');
        }

        $datos = array();
        $parametros = $this->_getAllParams( 'id-p', 'id-c', 'id', 'l', null );
        if( !is_null( $parametros ) ){

            if( isset( $parametros['id-p'] ) ){

                $this->view->id_categoria = $parametros['id-c'];

                if( isset( $parametros['id'] ) ){

                    $datos = array(

                        'id' => $parametros['id'],
                    );
                    $banner = $this->_consulta( 'GET_BANNER', $datos );

                    $this->view->nombre = $banner['nombre'];
                    $this->view->descripcion = $banner['descripcion'];
                    $this->view->orden = $banner['orden'];
                    $this->view->nivel = $banner['nivel'];
                    $this->view->id_banner = $parametros['id'];

                }
                $this->view->servicio = $this->promociones2[$parametros['id-p']];
                $this->view->id_promocion = $parametros['id-p'];
                $datos['id_promocion'] = $parametros['id-p'];
                $datos['id_categoria'] = $parametros['id-c'];
                $datos['nivel'] = $parametros['l'];
                $contenidos = $this->_consulta( 'GET_BANNERS', $datos );
                foreach( $contenidos as $indice=>$contenido ){

                    $contenidos[$indice]['path'] = $this->_convertirImagenesBanners( $contenido['path'] );
                }

                $this->view->contenidos = $contenidos;

            }else{


            }
        }else{

            echo "<h1>Ocurrio un error inesperado. Por favor vuelva a la pagina anterior</h1>";
            exit;
        }
    }

    public function pruebaAjaxAction(){

        if($this->getRequest()->isPost()){

            $formData = $this->getRequest()->getPost();


            if( isset( $formData ) ){

                $resultado = $formData['valor1'] + $formData['valor2'];
                $datos['id_promocion'] = '72';
                $resultado = $this->_consulta('GET_CATEGORIAS', $datos );
                echo $resultado;

            }
        }

    }

    public function sincronizarAction(){

        $parametros = $this->_getAllParams( 'id-promocion-1', 'id-promocion-2', null );
        if( !is_null( $parametros ) ){

            if($parametros['id-promocion-1'] !== $parametros['id-promocion-2']){

                $datos=array();
                $datos['id_promocion'] = $parametros['id-promocion-1'];
                $categorias1 = $this->_consulta( 'GET_CATEGORIAS', $datos );
                $datos['id_promocion'] = $parametros['id-promocion-2'];
                $categorias2 = $this->_consulta( 'GET_CATEGORIAS', $datos );
                /*print_r($categorias1);
                print_r($categorias2);
                exit;*/
                $datos = array();

                foreach ( $categorias1 as $i => $datos_categoria_i ){

                    if( isset( $categorias2[$i] ) ){

                        if( $datos_categoria_i['nombre_categoria'] !== $categorias2[$i]['nombre_categoria'] ){

                            $datos['id_categoria'] = $datos_categoria_i['id_categoria'];
                            $datos['nombre_categoria'] = $datos_categoria_i['nombre_categoria'];
                            $datos['descripcion'] = '';
                            $datos['id_categoria_padre'] = $datos_categoria_i['id_categoria_padre'];
                            $datos['ultimo_hijo'] = $datos_categoria_i['ultimo_hijo'];
                            $datos['estado'] = '1';
                            $datos['id_promocion'] = $parametros['id-promocion-2'];
                            $cola[] = $datos;

                            $this->_consulta('CREAR_CATEGORIA', $datos);
                        }else{

                            echo $categorias2[$i]['nombre_categoria'];
                            //exit;
                        }
                    }else{

                        $datos['id_categoria'] = $datos_categoria_i['id_categoria'];
                        $datos['nombre_categoria'] = $datos_categoria_i['nombre_categoria'];
                        $datos['descripcion'] = '';
                        $datos['id_categoria_padre'] = $datos_categoria_i['id_categoria_padre'];
                        $datos['ultimo_hijo'] = $datos_categoria_i['ultimo_hijo'];
                        $datos['estado'] = '1';
                        $datos['id_promocion'] = $parametros['id-promocion-2'];
                        $cola[] = $datos;

                        $this->_consulta('CREAR_CATEGORIA', $datos);
                    }
                }
                //contenidos
                $datos2 = array();
                $datos2['id_promocion'] = $parametros['id-promocion-1'];
                $contenidos1 = $this->_consulta( 'GET_CONTENIDOS_DUPLICAR', $datos2 );
                $datos2['id_promocion'] = $parametros['id-promocion-2'];
                $contenidos2 = $this->_consulta( 'GET_CONTENIDOS_DUPLICAR', $datos2 );

                $datos = array();
                foreach ( $contenidos1 as $i=>$datos_categoria_i ){

                    if( isset( $contenidos2[$i] ) ){

                        if( $datos_categoria_i['nombre_contenido'] != $contenidos2[$i]['nombre_contenido'] ){

                            $datos['id_categoria'] = $datos_categoria_i['id_categoria'];
                            $datos['id_contenido'] = $datos_categoria_i['id_contenido'];
                            $datos['nombre_contenido'] = $datos_categoria_i['nombre_contenido'];
                            $datos['descripcion'] = '';
                            $datos['path'] = $datos_categoria_i['path'];
                            $datos['tamanho'] = $datos_categoria_i['tamanho'];
                            $datos['tipo'] = $datos_categoria_i['tipo'];
                            $datos['descargas'] = 0;
                            $datos['estado'] = '1';
                            $datos['nivel'] = $datos_categoria_i['nivel'];
                            $datos['id_promocion'] = $parametros['id-promocion-2'];
                            $cola[] = $datos;

                            $this->_consulta('INSERT_CONTENIDO', $datos);
                        }
                    }else{

                        $datos['id_categoria'] = $datos_categoria_i['id_categoria'];
                        $datos['id_contenido'] = $datos_categoria_i['id_contenido'];
                        $datos['nombre_contenido'] = $datos_categoria_i['nombre_contenido'];
                        $datos['descripcion'] = '';
                        $datos['path'] = $datos_categoria_i['path'];
                        $datos['tamanho'] = $datos_categoria_i['tamanho'];
                        $datos['tipo'] = $datos_categoria_i['tipo'];
                        $datos['descargas'] = 0;
                        $datos['estado'] = '1';
                        $datos['nivel'] = $datos_categoria_i['nivel'];
                        $datos['id_promocion'] = $parametros['id-promocion-2'];
                        $cola[] = $datos;

                        $this->_consulta('INSERT_CONTENIDO', $datos);
                    }
                }
                //banner
                $datos3=array();
                $datos3['id_promocion'] = $parametros['id-promocion-1'];
                $banners1 = $this->_consulta( 'GET_BANNER_DUPLICAR', $datos3 );
                $datos3['id_promocion'] = $parametros['id-promocion-2'];
                $banners2 = $this->_consulta( 'GET_BANNER_DUPLICAR', $datos3 );
                $datos = array();

                foreach ( $banners1 as $i=>$datos_categoria_i ){

                    if( isset( $banners2[$i] ) ){

                        if( $datos_categoria_i['nombre'] != $banners2[$i]['nombre'] ){

                            $datos['nombre'] = $datos_categoria_i['nombre'];
                            $datos['semana'] = $datos_categoria_i['semana'];
                            $datos['path'] = $datos_categoria_i['path'];
                            $datos['orden'] = $datos_categoria_i['orden'];
                            $datos['nivel'] = $datos_categoria_i['nivel'];
                            $datos['id_promocion'] = $parametros['id-promocion-2'];
                            $datos['tamanho'] = $datos_categoria_i['tamanho'];
                            $datos['descripcion'] = '';
                            $datos['tipo'] = $datos_categoria_i['tipo'];
                            $datos['id_categoria'] = $datos_categoria_i['id_categoria'];
                            $datos['aparece'] = '1';
                            $cola[] = $datos;

                            $this->_consulta('INSERT_BANNER', $datos);
                        }
                    }else{

                        $datos['nombre'] = $datos_categoria_i['nombre'];
                        $datos['semana'] = $datos_categoria_i['semana'];
                        $datos['path'] = $datos_categoria_i['path'];
                        $datos['orden'] = $datos_categoria_i['orden'];
                        $datos['nivel'] = $datos_categoria_i['nivel'];
                        $datos['id_promocion'] = $parametros['id-promocion-2'];
                        $datos['tamanho'] = $datos_categoria_i['tamanho'];
                        $datos['descripcion'] = '';
                        $datos['tipo'] = $datos_categoria_i['tipo'];
                        $datos['id_categoria'] = $datos_categoria_i['id_categoria'];
                        $datos['aparece'] = '1';
                        $cola[] = $datos;

                        $this->_consulta('INSERT_BANNER', $datos);
                    }
                }

                $this->_redirect('/cargar/wap-preview-cargar/id-p/'.$parametros['id-promocion-2'].'/l/1');
            }else{

                $this->_redirect('/cargar/wap-preview-cargar/id-p/'.$parametros['id-promocion-1'].'/l/1');
            }
        }
    }

    public function previewsDisponiblesAction(){

        $this->logger->info('---> previewsDisponiblesAction');

        if( !$this->getRequest()->isPost() ){

            //$path_carpeta_trabajo = 'C:\ENTERMOVIL\web\www.entermovil.com.py\public\img\wap-imagenes-previews-videos\promociones\77\previews';
            $path_carpeta_trabajo = '/home/entermovil/web/www.entermovil.com.py/public/img/wap-imagenes-previews-videos/promociones/77/previews';

            $datos_preview = $this->_getAllParams( 'id-promocion', 'id-contenido' , 'nivel',  null );

            if( !is_null( $datos_preview ) ){

                $datos = array(
                    'id_promocion' => $datos_preview['id-promocion'],
                    'id_contenido' => $datos_preview['id-contenido'],
                );
                $datos_contenido_preview = $this->_consulta('GET_CONTENIDO_PREVIEW_VIDEO', $datos);
                $this->logger->info('GET_CONTENIDO_PREVIEW_VIDEO' . print_r( $datos_contenido_preview, true ));

                //print_r($datos_contenido_preview);
                //ANTES DE REDIRECCIONAR GENERO LOS POSIBLES PREVIEWS
                $target_path = $datos_contenido_preview['path'];
                $duracion = $this->_duracion_video( $target_path );
                $this->logger->info( 'duracion: ' . $duracion );
                //GENERAR SECUENCIA DE IMAGENES
                $imagenes = $this->_generarSecuenciaImagenes( $target_path, $path_carpeta_trabajo, $duracion );

                //print_r($imagenes);
                $this->view->nombre_categoria = 'posibles previews de contenidos';
                $imagenes_mostrar = array();

                foreach($imagenes as $indice => $imagen ){

                    $imagenes_mostrar[$imagen] = '/' . $this->_convertirImagenes($imagen); //tiene sentido en el servidor

                }
                $this->logger->info( print_r($imagenes_mostrar,true));

                $this->view->contenidos = $imagenes_mostrar;
                $this->view->nivel = $datos_preview['nivel'];
                $this->view->id_promocion = $datos_preview['id-promocion'];
                $this->view->id_contenido = $datos_preview['id-contenido'];
            }
        }else{

            $formData = $this->getRequest()->getPost();

            if( isset( $formData ) ){

                //cambiar nombre del archivo seleccionado para borrar los demas no seleccionados
                //$path_con_dominio = 'C:/ENTERMOVIL/web/www.entermovil.com.py/public';
                $path_con_dominio = '/home/entermovil/web/www.entermovil.com.py/public';
                $path_fijo = $path_con_dominio . '/img/wap-imagenes-previews-videos/promociones/77/previews_seleccionados/';

                $preview_seleccionado = $path_fijo  .basename( $formData['path'] );

                if( rename( $formData['path'], $preview_seleccionado ) ){

                    $datos['id_promocion'] = $formData['id_promocion'];
                    $datos['id_contenido'] = $formData['id_contenido'];
                    $datos['descripcion'] = $preview_seleccionado;
                    $actualizar = $this->_consulta( 'ASIGNAR_IMAGEN_PREVIEW_VIDEO', $datos );
                    $this->eliminarPreviewsGenerados();

                    $this->_redirect( '/cargar/wap-preview-cargar/id-p/' . $formData['id_promocion'] .'/l/'. $formData['nivel'] );

                }
            }
        }
    }

    private function _generarSecuenciaImagenes( $path_archivo, $path_carpeta_trabajo, $duracion_pautas ) {

        $this->logger->info( '_generarSecuenciaImagenes' );

        $imagenes = array();
        for($i = 0.0; $i <= $duracion_pautas; $i = $i + 3){

            $plantilla_comando_convertir = '{PATH_FFMPEG} -itsoffset -'.$i.' -i {PATH_ARCHIVO} -vcodec mjpeg -vframes 1 -an -f rawvideo -s 576x576 {PATH_ARCHIVO_DESTINO}';//2>&1
            //ffmpeg -itsoffset -0.0 -i 6767_INGLES.mpg -vcodec mjpeg -vframes 1 -an -f rawvideo -s 720x480 salida_0-0.jpg
            //printf("Convirtiendo archivo:[%s]\n", basename($path_archivo));
            $path_archivo_destino = $path_carpeta_trabajo . '/' . basename($path_archivo, ".3gp") .'_' . $i . '.jpg';
            $imagenes[] = $path_archivo_destino;
            $traduccion = array(
                '{PATH_FFMPEG}' => FFMPEG_LIBRARY,
                '{PATH_ARCHIVO}' => $path_archivo,
                '{PATH_ARCHIVO_DESTINO}' => $path_archivo_destino,
                '{PATH_LOG_CONVERSION}' => basename($path_archivo) . '__conversion.log'
            );
            $comando_convertir = strtr($plantilla_comando_convertir, $traduccion);
            //printf("comando_convertir:[%s]\n", $comando_convertir);
            $resultado_convertir = $this->_ejecutar_comando($comando_convertir);
        }
        $this->logger->info('imagenes:['.print_r( $imagenes,true).']' );

        return $imagenes;
    }

    private function _duracion_video( $path_archivo ) {

        $plantilla_comando_duracion = '{PATH_FFMPEG} -i {PATH_ARCHIVO} 2>&1';
        $traduccion = array(
            '{PATH_FFMPEG}' => FFMPEG_LIBRARY,
            '{PATH_ARCHIVO}' => $path_archivo
        );
        $comando_duracion = strtr($plantilla_comando_duracion, $traduccion);

        $resultado = $this->_ejecutar_comando($comando_duracion);

        $datos_duracion = array(
            'duracion_segundos' => 0,
            'duracion_hms' => 0
        );
        if(!empty($resultado)) {

            preg_match('/Duration: (.*?),/', $resultado, $matches);
            $duracion_hms = substr($matches[1], 0, 8);
            //printf("archivo:[%s]\n", $path_archivo);
            //printf("duracion_hms:[%s]\n", $duracion_hms);
            $duracion_segundos = $this->_hms2segundos($duracion_hms);
            //printf("duracion_segundos:[%d]\n", $duracion_segundos);

            $datos_duracion['duracion_segundos'] = $duracion_segundos;
            $datos_duracion['duracion_hms'] = $duracion_hms;

            /*$datos_resumen[basename($path_archivo)]['duracion_segundos'] = $duracion_segundos;
            $datos_resumen[basename($path_archivo)]['duracion_hms'] = $duracion_hms;*/
        }

        return $duracion_segundos;
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

    private function _hms2segundos($hms) {

        $partes = explode(":", $hms);
        $h = (int)$partes[0];
        $m = (int)$partes[1];
        $s = (int)$partes[2];

        return (int)($h*3600 + $m*60 + $s);
    }

    private function eliminarPreviewsGenerados(){


        $path_fijo = '/home/entermovil/web/www.entermovil.com.py/public/img/wap-imagenes-previews-videos/promociones/77/previews';
        $lista_archivos_eliminar = $this->filtrar_directorio($path_fijo, 'jpg', true);
        foreach( $lista_archivos_eliminar as $indice => $preview_a_eliminar ){

            if( unlink($preview_a_eliminar) ){

                $this->logger->info('ARCHIVO:['. $preview_a_eliminar .'] ELIMINADO CON EXITO');
            }else{

                $this->logger->err('ARCHIVO:['. $preview_a_eliminar .'] NO SE PUDO ELIMINAR');
            }
        }
        return;
    }

    private function filtrar_directorio($dir, $extension, $debug=false) {

        $lista_archivos = array();
        if($debug) printf("directorio:[%s] extension:[%s]\n", $dir, $extension);
        if (is_dir($dir)) {
            if ($dh = opendir($dir)) {
                while (($file = readdir($dh)) !== false) {
                    if($file != "." && $file != "..") {
                        $info_archivo = pathinfo($dir . '/' . $file);
                        //printf("info:[%s]\n", print_r($info_archivo, true));
                        if($info_archivo['extension'] == $extension) {
                            $lista_archivos[] = $dir . '/' . $file;
                        }
                    }
                }
                closedir($dh);
            }
        }
        if($debug) printf("Archivos(%s)[%s]\n", $extension, print_r($lista_archivos, true));

        return $lista_archivos;
    }

}
