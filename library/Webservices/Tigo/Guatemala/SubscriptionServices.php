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

    /**
     * @param GetUserServicesRequestParams $requestParams
     * @return GetUserServicesResponseParams
     */
    public function getUserServices(GetUserServicesRequestParams $requestParams) {

        $this->_validateParams($requestParams, 'GetUserServicesRequestParams');

        $responseParams = new GetUserServicesResponseParams(
            'GetUserServicesRequest recibido',
            $requestParams->transactionid,
            print_r($requestParams, true),
            array(
                new Service(0.97, 'Servicio1', 'Descripcion Servicio1', 'SERVICIO1', '4550', '4550'),
                new Service(1.95, 'Servicio2', 'Descripcion Servicio2', 'SERVICIO2', '35500', '35500')
            )
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
