<?php

//SubscribeToService
include_once APPLICATION_PATH . '/../library/Webservices/Tigo/Guatemala/SubscribeToServiceRequestParams.php';
include_once APPLICATION_PATH . '/../library/Webservices/Tigo/Guatemala/SubscribeToServiceResponseParams.php';
//UnsubscribeToService
include_once APPLICATION_PATH . '/../library/Webservices/Tigo/Guatemala/UnSubscribeToServiceRequestParams.php';
include_once APPLICATION_PATH . '/../library/Webservices/Tigo/Guatemala/UnSubscribeToServiceResponseParams.php';
//UnsubscribeUser
include_once APPLICATION_PATH . '/../library/Webservices/Tigo/Guatemala/UnSubscribeUserRequestParams.php';
include_once APPLICATION_PATH . '/../library/Webservices/Tigo/Guatemala/UnSubscribeUserResponseParams.php';
//GetUserServices
include_once APPLICATION_PATH . '/../library/Webservices/Tigo/Guatemala/Service.php';
include_once APPLICATION_PATH . '/../library/Webservices/Tigo/Guatemala/GetUserServicesRequestParams.php';
include_once APPLICATION_PATH . '/../library/Webservices/Tigo/Guatemala/GetUserServicesResponseParams.php';
//GetAvailableServices
include_once APPLICATION_PATH . '/../library/Webservices/Tigo/Guatemala/GetAvailableServicesRequestParams.php';
include_once APPLICATION_PATH . '/../library/Webservices/Tigo/Guatemala/GetAvailableServicesResponseParams.php';
//RequestUserService
include_once APPLICATION_PATH . '/../library/Webservices/Tigo/Guatemala/RequestUserServiceRequestParams.php';
include_once APPLICATION_PATH . '/../library/Webservices/Tigo/Guatemala/RequestUserServiceResponseParams.php';
//BlackListUser
include_once APPLICATION_PATH . '/../library/Webservices/Tigo/Guatemala/BlackListUserRequestParams.php';
include_once APPLICATION_PATH . '/../library/Webservices/Tigo/Guatemala/BlackListUserResponseParams.php';
//UnBlackListUser
include_once APPLICATION_PATH . '/../library/Webservices/Tigo/Guatemala/UnBlackListUserRequestParams.php';
include_once APPLICATION_PATH . '/../library/Webservices/Tigo/Guatemala/UnBlackListUserResponseParams.php';

//Constantes
define('PHONENUMBER', 'phonenumber');
define('SERVICENAME', 'servicename');
define('TRANSACTION_ID', 'transactionid');
define('SHORTCODE_NUMBER', 'shortcodenumber');
define('RESPONSE', 'response');
define('MESSAGE', 'message');
define('SERVICES', 'services');
/**
 *
 */
class SubscriptionServices
{
    /**
     * @param SubscribeToServiceRequestParams $requestParams
     * @return SubscribeToServiceResponseParams
     */
    public function subscribeToService(SubscribeToServiceRequestParams $requestParams) {

        $responseParams = new SubscribeToServiceResponseParams(
            'SubscribeToServiceRequest recibido',
            $requestParams->transactionid,
            print_r($requestParams, true)
        );
        //Webservices_Tigo_Guatemala_

        return $responseParams;
    }

    /**
     * @param UnSubscribeToServiceRequestParams $requestParams
     * @return UnSubscribeToServiceResponseParams
     */
    public function unsubscribeToService(UnSubscribeToServiceRequestParams $requestParams) {

        $this->_validateParams($requestParams, 'UnSubscribeToServiceRequestParams');

        $responseParams = new UnSubscribeToServiceResponseParams(
            'UnSubscribeToServiceRequest recibido',
            $requestParams->transactionid,
            print_r($requestParams, true)
        );

        return $responseParams;
    }

    /**
     * @param UnSubscribeUserRequestParams $requestParams
     * @return UnSubscribeUserResponseParams
     */
    public function unsubscribeUser(UnSubscribeUserRequestParams $requestParams) {

        $this->_validateParams($requestParams, 'UnSubscribeUserRequestParams');

        $responseParams = new UnSubscribeUserResponseParams(
            'UnSubscribeUserRequest recibido',
            $requestParams->transactionid,
            print_r($requestParams, true)
        );

        return $responseParams;
    }

    private function _getFormatoCorto($nro_largo) {

        //Verificamos que el nro recibido este en formato largo
        //Ejemplo: 502 40009752
        if(strlen($nro_largo) == 11 && substr($nro_largo, 0, 3) == '502') {//esta en formato largo
            return   substr($nro_largo, 3);
        }

        return $nro_largo;
    }

    /**
     * @param GetUserServicesRequestParams $requestParams
     * @return GetUserServicesResponseParams
     */
    public function getUserServices(GetUserServicesRequestParams $requestParams) {

        $front = Zend_Controller_Front::getInstance();
        $bootstrap = $front->getParam("bootstrap");



        $logger = $bootstrap->getResource('Logger');

        $this->_validateParams($requestParams, 'GetUserServicesRequestParams');

        $logger->info('despues de validar parametros...');
        /*
         * select infopromos.costo_usd,alias, promocion,alias,numero,'SALIR' || numero as unsusbribednum  from promosuscripcion.suscriptos  suscrip
join info_promociones  infopromos ON (infopromos.id_promocion=suscrip.id_promocion and infopromos.id_carrier=suscrip.id_carrier)
where  suscrip.cel='0984100058' -- and suscrip.id_carrier=2
         */


        $options = $bootstrap->getOptions();

        $db = Zend_Db::factory(new Zend_Config($options['resources']['db']));
        $db->getConnection();

        $logger->info('despues de conectar');

        $sql = "select ip.costo_usd,alias, promocion,alias,numero, alias  from promosuscripcion.suscriptos  S
        join info_promociones  ip ON (ip.id_promocion=S.id_promocion and ip.id_carrier=S.id_carrier)
        where  S.cel=? "; //and  numero = ?
        $phoneSinPais = $this->_getFormatoCorto($requestParams->phonenumber);

        $rs = $db->fetchAll($sql, array($phoneSinPais));

        $logger->info('rs:[' . print_r($rs, true) . ']');

        $servicios=array();
        foreach($rs as $fila)
         {
         $servicio = new Service($fila{'costo_usd'},$fila{'alias'},$fila{'promocion'},$fila{'alias'},$fila{'numero'},'SALIR '.$fila{'alias'});
         $servicios[]=$servicio;
         }

        $responseParams = new GetUserServicesResponseParams(
            'GetUserServicesRequest recibido',
            $requestParams->transactionid,
            print_r($requestParams, true),
            $servicios
        );

        return $responseParams;
    }

    /**
     * @param GetAvailableServicesRequestParams $requestParams
     * @return GetAvailableServicesResponseParams
     */
    public function getAvailableServices(GetAvailableServicesRequestParams $requestParams) {

        $this->_validateParams($requestParams, 'GetAvailableServicesRequestParams');




        $responseParams = new GetAvailableServicesResponseParams(
            'GetAvailableServicesRequest recibido',
            $requestParams->transactionid,
            print_r($requestParams, true),
            array(
                new Service(1.95, 'Servicio2', 'Descripcion Servicio2', 'SERVICIO2', '35500', '35500')
            )
        );

        return $responseParams;
    }

    /**
     * @param RequestUserServiceRequestParams $requestParams
     * @return RequestUserServiceResponseParams
     */
    public function requestUserService(RequestUserServiceRequestParams $requestParams) {

        $this->_validateParams($requestParams, 'RequestUserServiceRequestParams');

        $responseParams = new RequestUserServiceResponseParams(
            'RequestUserServiceRequest recibido',
            $requestParams->transactionid,
            sprintf('En breve recibira el siguiente contenido correspondiente al Servicio %s del %s', $requestParams->servicename, $requestParams->shortcodenumber)
        );

        return $responseParams;
    }

    /**
     * @param BlackListUserRequestParams $requestParams
     * @return BlackListUserResponseParams
     */
    public function blackListUser(BlackListUserRequestParams $requestParams) {

        $this->_validateParams($requestParams, 'BlackListUserRequestParams');

        $responseParams = new BlackListUserResponseParams(
            'BlackListUserRequest recibido',
            $requestParams->transactionid,
            sprintf('El usuario %s fue agregado al BlackList', $requestParams->phonenumber)
        );

        return $responseParams;
    }

    /**
     * @param UnBlackListUserRequestParams $requestParams
     * @return UnBlackListUserResponseParams
     */
    public function unBlackListUser(UnBlackListUserRequestParams $requestParams) {

        $this->_validateParams($requestParams, 'UnBlackListUserRequestParams');

        $responseParams = new UnBlackListUserResponseParams(
            'UnBlackListUserRequest recibido',
            $requestParams->transactionid,
            sprintf('El usuario %s fue eliminado del BlackList', $requestParams->phonenumber)
        );

        return $responseParams;
    }


    private function _validateParams($requestParams, $nombreClase) {

        $front = Zend_Controller_Front::getInstance();
        $bootstrap = $front->getParam("bootstrap");
        $logger = $bootstrap->getResource('Logger');

        $isValid = false;

        if(!is_object($requestParams)) {
            $isValid = false;
        } else {
            $logger->info('Clase:[' . $nombreClase . ']');

            $parametros_obligatorios = array(
                'SubscribeToServiceRequestParams' => array(PHONENUMBER, SERVICENAME, TRANSACTION_ID),
                'UnSubscribeToServiceRequestParams' => array(PHONENUMBER, SERVICENAME, TRANSACTION_ID),
                'UnSubscribeUserRequestParams' => array(PHONENUMBER, TRANSACTION_ID),
                'GetUserServicesRequestParams' => array(PHONENUMBER, SHORTCODE_NUMBER, TRANSACTION_ID),
                'GetAvailableServicesRequestParams' => array(PHONENUMBER, SERVICENAME, TRANSACTION_ID),
                'RequestUserServiceRequestParams' => array(PHONENUMBER, SERVICENAME, TRANSACTION_ID),
                'BlackListUserRequestParams' => array(PHONENUMBER, TRANSACTION_ID),
                'UnBlackListUserRequestParams' => array(PHONENUMBER, TRANSACTION_ID)
            );

            $logger->info('ParametrosObligatorios:[' . print_r($parametros_obligatorios[$nombreClase], true) . ']');

            $lista_parametros_obligatorios = $parametros_obligatorios[$nombreClase];
            foreach($lista_parametros_obligatorios as $parametro) {
                if(!isset($requestParams->$parametro)) {
                    $logger->err('Parametro:[' . $parametro . '] no recibido');
                    $isValid = false;
                    break;
                }
            }
        }

        return $isValid;
    }
}
