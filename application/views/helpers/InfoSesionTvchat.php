<?php

class Zend_View_Helper_InfoSesionTvchat extends Zend_View_Helper_Abstract {

    public function infoSesionTvchat() {

        return $this;
    }

    public function usuario() {

        $namespace = new Zend_Session_Namespace("entermovil-tvchat-reportes");
        $datos_user = array(
            'usuario' => $namespace->usuario,
            'nombre' => $namespace->nombre
        );
        return '<strong>Usuario:</strong> ' . $datos_user['usuario'] . ' (' . $datos_user['nombre'] . ')';
    }

    public function accesoPermitido($controlador) {

        $namespace = new Zend_Session_Namespace("entermovil-tvchat-reportes");
        $accesos = $namespace->accesos;

        if( array_key_exists( 'admin', $accesos ) ) {
            return true;
        }

        return array_key_exists($controlador, $accesos);
    }
}
