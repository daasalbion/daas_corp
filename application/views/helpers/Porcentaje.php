<?php

class Zend_View_Helper_Porcentaje extends Zend_View_Helper_Abstract {

    public function porcentaje($valor=0) {
        if($valor == 0 || $valor == '') {
            return '';
        }

        return (number_format($valor*100, 2, ',', '.')) . ' %';
    }
}
