<?php
/**
 * Created by PhpStorm.
 * User: soporte
 * Date: 17/03/14
 * Time: 09:57 AM
 */

class RadioController  extends Zend_Controller_Action {

    public $logger = null;

    public function init()
    {
        /* Initialize action controller here */
        $this->logger = $this->getLog();
        if($this->logger) {
            $this->logger->info('IndexController -> Request');
        }

        $this->_helper->layout->disableLayout();
        //$this->_helper->viewRenderer->setNoRender(true);

    }

    public function  indexAction() {
        echo '<h1>Acceso Denegado</h1>';
    }

    public function disneyAction() {

        $fecha = $this->_getParam('fecha', date('Y-m-d'));

        $mensajes_ondemand = $this->_consultarMensajesOnDemand(57, $fecha);
        $this->view->titulo = "Radio Disney - Mensajero";
        $this->view->encabezado = "Mensajero Radio Disney 965";
        $this->view->mensajes = $mensajes_ondemand;
        $this->view->cantidad_de_mensajes = count($mensajes_ondemand);
        $this->view->fecha_seleccionada = $fecha . (($fecha == date('Y-m-d')) ? '(HOY)' : '');
        $this->view->fecha_anterior = $this->_fechaAnterior($fecha);
        $this->view->fecha_siguiente = $this->_fechaSiguiente($fecha);
        $this->view->url_fecha_anterior = '/radio/disney/fecha/' . $this->view->fecha_anterior;
        $this->view->url_fecha_siguiente = '/radio/disney/fecha/' . $this->view->fecha_siguiente;
        $this->_helper->viewRenderer('listado');
    }

    public function farraAction() {

        $fecha = $this->_getParam('fecha', date('Y-m-d'));

        $mensajes_ondemand = $this->_consultarMensajesOnDemand(66, $fecha);
        $this->view->titulo = "Radio Farra - Mensajero";
        $this->view->encabezado = "Mensajes Radio FARRA 101.30";
        $this->view->mensajes = $mensajes_ondemand;
        $this->view->cantidad_de_mensajes = count($mensajes_ondemand);
        $this->view->fecha_seleccionada = $fecha . (($fecha == date('Y-m-d')) ? '(HOY)' : '');
        $this->view->fecha_anterior = $this->_fechaAnterior($fecha);
        $this->view->fecha_siguiente = $this->_fechaSiguiente($fecha);
        $this->view->url_fecha_anterior = '/radio/farra/fecha/' . $this->view->fecha_anterior;
        $this->view->url_fecha_siguiente = '/radio/farra/fecha/' . $this->view->fecha_siguiente;
        $this->_helper->viewRenderer('listado');
    }

    private function _fechaAnterior($fechaActual) {
        list($Y, $M, $D) = explode("-", $fechaActual);
        $ts = mktime(0,0,0,$M, $D-1, $Y);
        return date('Y-m-d', $ts);
    }
    private function _fechaSiguiente($fechaActual) {

        if($fechaActual == date('Y-m-d')) return $fechaActual;

        list($Y, $M, $D) = explode("-", $fechaActual);
        $ts = mktime(0,0,0,$M, $D+1, $Y);
        return date('Y-m-d', $ts);
    }

    private function _consultarMensajesOnDemand($idPromocion, $fecha=null) {

        $bootstrap = $this->getInvokeArg('bootstrap');
        $options = $bootstrap->getOptions();

        $db = Zend_Db::factory(new Zend_Config($options['resources']['db']));
        $db->getConnection();

        if(is_null($fecha)) {
            $fecha = date('Y-m-d');
        }

        $mensajes_ondemand = array();
        $sql = "SELECT * FROM promosuscripcion.on_demand_mensaje WHERE id_promocion = ? AND ts_local::date = ? ORDER BY id";
        $rs_mensajes = $db->fetchAll($sql, array($idPromocion, $fecha));
        foreach($rs_mensajes as $mensaje) {
            $mensajes_ondemand[] = (array)$mensaje;
        }

        /*print_r($mensajes_ondemand);
        exit;*/

        return $mensajes_ondemand;
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
} 