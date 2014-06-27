<?php

class Zend_View_Helper_InfoSesionCargar extends Zend_View_Helper_Abstract {

    public function infoSesionCargar() {

        return $this;
    }

    public function usuario() {

        $namespace = new Zend_Session_Namespace("entermovil-cargar-wap");
        $datos_user = array(
            'usuario' => $namespace->usuario,
            'nombre' => $namespace->nombre
        );
        return '<strong>Usuario:</strong> ' . $datos_user['usuario'] . ' (' . $datos_user['nombre'] . ')';
    }

    public function accesoPermitido($controlador) {

        $namespace = new Zend_Session_Namespace("entermovil-cargar-wap");
        $accesos = $namespace->accesos;

        if(in_array('FULL', $accesos)) {
            return true;
        }

        return in_array($controlador, $accesos);
    }
}
