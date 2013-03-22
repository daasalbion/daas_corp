<?php

class Zend_View_Helper_Numero extends Zend_View_Helper_Abstract {

    public function numero($valor=0, $decimal=false) {
        if($valor == 0 || $valor == '') {
            return '';
        }
        if($decimal){
            return (number_format($valor, 2, ',', '.'));
        }

        return number_format($valor, 0, ',', '.');
    }
}
