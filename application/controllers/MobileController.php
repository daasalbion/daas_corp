<?php

class MobileController extends Zend_Controller_Action
{

    public function init()
    {
        /* Initialize action controller here */
        $this->_helper->layout->disableLayout();
    }

    public function indexAction()
    {

        $this->getResponse()
            ->setHeader('Cache-Control', 'no-cache')
            ->setHeader('Expires', '0');


        $this->_helper->viewRenderer->setNoRender(true);

        $bootstrap = $this->getInvokeArg('bootstrap');
        //echo 'bootstrap' . "\n";
        $userAgent = $bootstrap->getResource('useragent');
        //print_r($userAgent);
        $device = $userAgent->getDevice();

        //var_dump($device->hasFeature('is_mobile'));

        $header_names = array('HTTP_MSISDN', 'HTTP_X_UP_CALLING_LINE_ID', 'HTTP_X_MSISDN', 'HTTP_X_NOKIA_MSISDN');
        $nro_cel = 'NO-RECIBIDO';//En formato largo: 595 981 524 664
        $nombre_header = null;
        foreach($header_names as $header_name) {
            if(isset($_SERVER[$header_name]) && !empty($_SERVER[$header_name])) {
                $nro_cel = $_SERVER[$header_name];
                $nombre_header = $header_name;
                break;
            }
        }

        $is_mobile = ($device->hasFeature('is_mobile')) ? $device->getFeature('is_mobile') : false;
        echo '<h2>DispositivoMovil:['. ($is_mobile ? 'SI' : 'NO') .']</h2>';
        echo '<h2>'. $device->getFeature('brand_name') . ' - ' . $device->getFeature('model_name') . ' - ' . $device->getFeature('marketing_name') .'</h2>';
        echo '<h3>Navegador:['. $userAgent->getBrowserType().']</h3>';
        echo '<h3>Resolucion:['. $device->getPhysicalScreenWidth() . ' x ' . $device->getPhysicalScreenHeight() .']</h3>';
        exit;


        $is_wireless = (bool)$device->getFeature('is_wireless_device');
        if($is_wireless) {
            echo '<h2>Dispositivo MOVIL</h2>';
            echo '<h2>'. $device->getFeature('brand_name') . ' - ' . $device->getFeature('model_name') . ' - ' . $device->getFeature('marketing_name') .'</h2>';
            echo '<h2>CEL:['. $nro_cel.'] Header:['.$nombre_header.']</h2>';
            echo '<h3>Resolucion:['. $device->getFeature('resolution_width') . 'x' . $device->getFeature('resolution_height') .']</h3>';
            echo '<h3>Pantalla:['. $device->getFeature('columns') . 'x' . $device->getFeature('rows') .']</h3>';
            echo '<h3>Soporta Wap-Push:['. ($device->getFeature('wap_push_support') == "1" ? "SI" : "NO")  .']</h3>';
            echo '<h3>xhtml_support_level:['. $device->getFeature('xhtml_support_level')  .']</h3>';
            echo '<h3>preferred_markup:['. $device->getFeature('preferred_markup')  .']</h3>';
            echo '<h3>wml_1_1:['. ($device->getFeature('wml_1_1') == "1" ? "SI" : "NO")  .']</h3>';
            echo '<h3>wml_1_2:['. ($device->getFeature('wml_1_2') == "1" ? "SI" : "NO")  .']</h3>';
            echo '<h3>wml_1_3:['. ($device->getFeature('wml_1_3') == "1" ? "SI" : "NO")  .']</h3>';
        } else {
            echo '<h2>PC-DESKTOP:[' . $device->getFeature('product_name') . ']</h2>';
        }




        echo '<pre>';
        echo ($device->getFeature('is_wireless_device')) ? 'Wireless' : 'Desktop' . "\n";
        echo $device->getFeature('brand_name') . "\n";
        echo $device->getFeature('model_name') . "\n";
        echo $device->getFeature('product_name') . "\n\n";
        echo 'resolucion:[' . $device->getFeature('resolution_width') . 'x' . $device->getFeature('resolution_height') . ']' . "\n";
        echo 'columnas x filas:[' . $device->getFeature('columns') . 'x' . $device->getFeature('rows') . ']' . "\n";
        echo 'pantalla(mm):[' . $device->getFeature('physical_screen_width') . 'x' . $device->getFeature('physical_screen_height') . ']' . "\n";
        echo '</pre>';

        //print_r($device);


        //echo trim($device->getFeature('brand_name') . ' ' . $device->getFeature('model_name') . ' ' . $device->getFeature('marketing_name') . ' ' . $device->getFeature('model_extra_info'));
        exit;
    }


}