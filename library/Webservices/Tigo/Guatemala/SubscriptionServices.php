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

        $front = Zend_Controller_Front::getInstance();
        $bootstrap = $front->getParam("bootstrap");
        $logger = $bootstrap->getResource('Logger');
        $this->_validateParams($requestParams, 'SubscribeToServiceRequestParams');

        $options = $bootstrap->getOptions();

        $db = Zend_Db::factory(new Zend_Config($options['resources']['db']));
        $db->getConnection();
        $phoneSinPais = $this->_getFormatoCorto($requestParams->phonenumber);

        $sqlBl="select * from tigo_guatemala_blacklist where cel = ?";
        $rsBl = $db->fetchAll($sqlBl, array($phoneSinPais));
        if($rsBl)
        {
            $responseParams = new SubscribeToServiceResponseParams(
                'Error',
                $requestParams->transactionid,
                 sprintf('El numero %s se encuentra en el Blacklist', $phoneSinPais)
            );

        return $responseParams;
        }


        $sql = "select ip.id_promocion, mensaje_confirmacion, mensaje_ya_suscripto, numero from info_promociones ip
                join  promosuscripcion.mensajes men on (men.id_promocion=ip.id_promocion and men.id_carrier=ip.id_carrier)
                where alias = ? and ip.id_carrier=5";

        if($requestParams->shortcodenumber)
         {
         $logger->info('Posee shortcodenumber');
         $sql .=" and numero= ?";
         $rs = $db->fetchAll($sql, array($requestParams->servicename,$requestParams->shortcodenumber));
         $logger->info('CONSULTA con shortcodenumber realizada');
         }
        else
         {
         $logger->info('No posee shortcodenumber');
         $rs = $db->fetchAll($sql, array($requestParams->servicename));
         $logger->info('CONSULTA sin shortcodenumber realizada');
         }



        $logger->info('rs:[' . print_r($rs, true) . ']');

        //$rs si devuelve algo insertar en suscriptos, sin devolver error
        if($rs)
         {
         $logger->info('id promo '.$rs[0]['id_promocion']);

         //comprobar si ya esta suscripto
         $sql2 = "select * from promosuscripcion.suscriptos where id_carrier=5 and id_promocion= ? and cel= ?";
         $rs2 = $db->fetchAll($sql2, array($rs[0]['id_promocion'],$phoneSinPais));
         $logger->info('comprobar si ya esta suscripto');

         if($rs2)
          {
          $logger->info(' Ya esta suscripto');
          $responseParams = new SubscribeToServiceResponseParams(
          'Error',
          $requestParams->transactionid,
          $rs[0]['mensaje_ya_suscripto']
          );
          return $responseParams;
          }

          try {
             $logger->info('PREPARANDO PARA INSERCION');
             $status = $db->insert('promosuscripcion.suscriptos', array('cel' => $phoneSinPais,'id_promocion' =>$rs[0]['id_promocion'],'id_carrier' => 5));
             $logger->info('status:[' . $status . ']');
             $responseParams = new SubscribeToServiceResponseParams(
                 'Exito',
                 $requestParams->transactionid,
                 $rs[0]['mensaje_confirmacion']
             );

            //insertar en salientes
            if($requestParams->shortcodenumber)
              $this->_insertarSmsSaliente($phoneSinPais,$requestParams->shortcodenumber,$rs[0]['mensaje_confirmacion']);
            else
              $this->_insertarSmsSaliente($phoneSinPais,$rs[0]['numero'],$rs[0]['mensaje_confirmacion']);

             } catch(Zend_Db_Exception $e) {
                $logger->err($e);

                $responseParams = new SubscribeToServiceResponseParams(
                'Error',
                $requestParams->transactionid,
                'Error al intentar suscribirse'
                );
             }

         }
        else
         {
         $responseParams = new SubscribeToServiceResponseParams(
                 'Error',
                 $requestParams->transactionid,
                'Alias incorrecto o no existe'
             );
         }

        //Webservices_Tigo_Guatemala_

        return $responseParams;
    }

    /**
     * @param UnSubscribeToServiceRequestParams $requestParams
     * @return UnSubscribeToServiceResponseParams
     */
    public function unsubscribeToService(UnSubscribeToServiceRequestParams $requestParams) {

        $front = Zend_Controller_Front::getInstance();
        $bootstrap = $front->getParam("bootstrap");
        $logger = $bootstrap->getResource('Logger');
        $this->_validateParams($requestParams, 'UnSubscribeToServiceRequestParams');

        $options = $bootstrap->getOptions();

        $db = Zend_Db::factory(new Zend_Config($options['resources']['db']));
        $db->getConnection();

        $logger->info('Consulta: CANCELAR_SUSCRIPCION');
        $phoneSinPais = $this->_getFormatoCorto($requestParams->phonenumber);
        $logger->info('cel:[' . $phoneSinPais . '] alias:[' . $requestParams->servicename . ']');

        $sql="select ip.id_promocion,mensaje_baja,mensaje_no_suscripto, numero from info_promociones ip
              join promosuscripcion.mensajes men on men.id_promocion=ip.id_promocion
              where ip.id_carrier=5 and ip.alias=?";

        $rs = $db->fetchAll($sql, array($requestParams->servicename));

        if($rs)
         {
         try {
                 $status = $db->delete('promosuscripcion.suscriptos', array('cel = ?' =>  $phoneSinPais , 'id_carrier = ?' => 5));
                 $logger->info('status:[' . $status . ']');
                 $responseParams = new UnSubscribeToServiceResponseParams(
                 'Exito',
                 $requestParams->transactionid,
                 $rs[0]['mensaje_baja']
             );

              $this->_insertarSmsSaliente($phoneSinPais,$rs[0]['numero'],$rs[0]['mensaje_baja']);

             } catch(Zend_Db_Exception $e) {
                 $logger->err($e);
                 $responseParams = new UnSubscribeToServiceResponseParams(
                 'Error',
                 $requestParams->transactionid,
                 'Error al intentar desubscribirse'
                  );

             }
         }
        else
         {
         $responseParams = new UnSubscribeToServiceResponseParams(
             'Error',
             $requestParams->transactionid,
             $rs[0]['mensaje_no_suscripto']
             );

         $this->_insertarSmsSaliente($phoneSinPais,$rs[0]['numero'],$rs[0]['mensaje_no_suscripto']);
         }

        return $responseParams;
    }

    /**
     * @param UnSubscribeUserRequestParams $requestParams
     * @return UnSubscribeUserResponseParams
     */
    public function unsubscribeUser(UnSubscribeUserRequestParams $requestParams) {

        $front = Zend_Controller_Front::getInstance();
        $bootstrap = $front->getParam("bootstrap");
        $logger = $bootstrap->getResource('Logger');
        $this->_validateParams($requestParams, 'UnSubscribeUserRequestParams');

        $options = $bootstrap->getOptions();

        $db = Zend_Db::factory(new Zend_Config($options['resources']['db']));
        $db->getConnection();

        $logger->info('Consulta: CANCELAR_ TODAS LAS SUSCRIPCIONES');
        $phoneSinPais = $this->_getFormatoCorto($requestParams->phonenumber);
        $logger->info('cel:[' . $phoneSinPais . ']');

        $sql="select numero  from info_promociones ip
             join promosuscripcion.suscriptos  S on (ip.id_promocion=S.id_promocion and ip.id_carrier=S.id_carrier)
             where S.id_carrier = 5 and S.cel= ?
             group by numero";

        $rs = $db->fetchAll($sql, array($phoneSinPais));

        if($rs)
        {
            try {
                $status = $db->delete('promosuscripcion.suscriptos', array('cel = ?' =>  $phoneSinPais , 'id_carrier = ?' => 5));
                $logger->info('status:[' . $status . ']');
                $logger->info('SE ELIMINO DE SUSCRIPTOS');
                $responseParams = new UnSubscribeUserResponseParams(
                    'Exito',
                    $requestParams->transactionid,
                    'Cancelaste todas tus suscripciones '
                );

                foreach($rs as $fila)
                {
                $this->_insertarSmsSaliente($phoneSinPais,$fila{'numero'},'Cancelaste todas tus suscripciones del numero '. $fila{'numero'});
                }



            } catch(Zend_Db_Exception $e) {
                $logger->err($e);
                $responseParams = new UnSubscribeUserResponseParams(
                    'Error',
                    $requestParams->transactionid,
                    'Error al intentar desubscribirse'
                );

            }
        }
        else
        {
            $responseParams = new UnSubscribeUserResponseParams(
                'Error',
                $requestParams->transactionid,
                'No esta suscripto a ningun servicio'
            );
        }



        return $responseParams;
    }

    private function _unsubscribeUserBlackListedToAllServices($phoneSinPais)
    {
        $front = Zend_Controller_Front::getInstance();
        $bootstrap = $front->getParam("bootstrap");
        $logger = $bootstrap->getResource('Logger');

        $options = $bootstrap->getOptions();

        $db = Zend_Db::factory(new Zend_Config($options['resources']['db']));
        $db->getConnection();

        $logger->info('Consulta: CANCELAR_ TODAS LAS SUSCRIPCIONES POR BLACKLISTED');

        $logger->info('cel:[' . $phoneSinPais . ']');

            try {
                $status = $db->delete('promosuscripcion.suscriptos', array('cel = ?' =>  $phoneSinPais , 'id_carrier = ?' => 5));
                $logger->info('status:[' . $status . ']');
                $logger->info('SE ELIMINO DE SUSCRIPTOS');

            } catch(Zend_Db_Exception $e) {
                $logger->err($e);
            }
    }

    private function _insertarSmsSaliente($phone,$n_llamado,$sms){
        $front = Zend_Controller_Front::getInstance();
        $bootstrap = $front->getParam("bootstrap");
        $logger = $bootstrap->getResource('Logger');

        $options = $bootstrap->getOptions();

        $db = Zend_Db::factory(new Zend_Config($options['resources']['db']));
        $db->getConnection();

        try {
            $logger->info('PREPARANDO PARA INSERCION EN SMS_SALIENTES');                                                                           // 'current_timestamp'
            $status = $db->insert('sms_salientes', array('id_carrier' => 5,'n_llamado' =>$n_llamado,'n_remitente'=>$phone,'sms' =>$sms,'ts_local'=> 'now()','id_cliente'=> 3,'estado'=>0,'pendiente_billing'=>0,'tipo_mensaje' => 1,'id_contenido'=> 0));
            $logger->info('status:[' . $status . ']');
            $logger->info('INSERCION EXITOSA ....');

        } catch(Zend_Db_Exception $e) {
            $logger->err($e);
        }

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
        where  ip.id_carrier=5 and S.cel=? "; //and  numero = ?
        $phoneSinPais = $this->_getFormatoCorto($requestParams->phonenumber);

        if($requestParams->shortcodenumber)
         {
         $logger->info('Posee shortcodenumber');
         $sql .=" and numero= ?";
         $rs = $db->fetchAll($sql, array($phoneSinPais,$requestParams->shortcodenumber));
         $logger->info('CONSULTA con shortcodenumber realizada');
         }
        else
         {
         $logger->info('No posee shortcodenumber');
         $rs = $db->fetchAll($sql, array($phoneSinPais));
         $logger->info('CONSULTA sin shortcodenumber realizada');
         }

        if($rs)
        {
        $logger->info('rs:[' . print_r($rs, true) . ']');

        $servicios=array();
        foreach($rs as $fila)
         {
         $servicio = new Service($fila{'costo_usd'},$fila{'alias'},$fila{'promocion'},$fila{'alias'},$fila{'numero'},'SALIR '.$fila{'alias'});
         $servicios[]=$servicio;
         }

        $responseParams = new GetUserServicesResponseParams(
            'Exito',
            $requestParams->transactionid,
            'Lista de servicios suscriptos',
            $servicios
            );
        }
        else
        {
          $responseParams = new GetUserServicesResponseParams(
                'Error',
                $requestParams->transactionid,
                'No esta suscripto a ningun servicio',
                array()
                );
        }
    return $responseParams;
    }

    /**
     * @param GetAvailableServicesRequestParams $requestParams
     * @return GetAvailableServicesResponseParams
     */
    public function getAvailableServices(GetAvailableServicesRequestParams $requestParams) {

        $front = Zend_Controller_Front::getInstance();
        $bootstrap = $front->getParam("bootstrap");

        $logger = $bootstrap->getResource('Logger');

        $this->_validateParams($requestParams, 'GetAvailableServicesRequestParams');


        $logger->info('despues de validar parametros...');


        $options = $bootstrap->getOptions();

        $db = Zend_Db::factory(new Zend_Config($options['resources']['db']));
        $db->getConnection();

        $logger->info('despues de conectar');

        $sql = "select info_promociones.costo_usd,alias, promocion,alias,numero, alias from info_promociones where id_carrier=5
                and id_promocion not in (select ip.id_promocion from info_promociones ip
                join promosuscripcion.suscriptos  suscrip  ON (ip.id_promocion=suscrip.id_promocion and ip.id_carrier=suscrip.id_carrier)
                where  suscrip.cel=? and ip.id_carrier=5)"; //and  numero = ?
        $phoneSinPais = $this->_getFormatoCorto($requestParams->phonenumber);

        if($requestParams->shortcodenumber)
        {
            $logger->info('Posee shortcodenumber');
            $sql .=" and numero= ?";
            $rs = $db->fetchAll($sql, array($phoneSinPais,$requestParams->shortcodenumber));
            $logger->info('CONSULTA con shortcodenumber realizada');
        }
        else
        {
            $logger->info('No posee shortcodenumber');
            $rs = $db->fetchAll($sql, array($phoneSinPais));
            $logger->info('CONSULTA sin shortcodenumber realizada');
        }

        $logger->info('rs:[' . print_r($rs, true) . ']');

        $servicios=array();
        foreach($rs as $fila)
        {
            $servicio = new Service($fila{'costo_usd'},$fila{'alias'},$fila{'promocion'},$fila{'alias'},$fila{'numero'},'SALIR '.$fila{'alias'});
            $servicios[]=$servicio;
        }

        $responseParams = new GetUserServicesResponseParams(
            'Exito',
            $requestParams->transactionid,
            print_r($requestParams, true),
            $servicios
        );


        return $responseParams;
    }

    /**
     * @param RequestUserServiceRequestParams $requestParams
     * @return RequestUserServiceResponseParams
     */
    public function requestUserService(RequestUserServiceRequestParams $requestParams) {

        $this->_validateParams($requestParams, 'RequestUserServiceRequestParams');

        //si todavia no se envio, enviar

        //si ya envio volver a enviar



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

        $front = Zend_Controller_Front::getInstance();
        $bootstrap = $front->getParam("bootstrap");
        $logger = $bootstrap->getResource('Logger');
        $this->_validateParams($requestParams, 'BlackListUserRequestParams');

        $options = $bootstrap->getOptions();

        $db = Zend_Db::factory(new Zend_Config($options['resources']['db']));
        $db->getConnection();

        $sql = "select * from tigo_guatemala_blacklist where cel = ?";
        $phoneSinPais = $this->_getFormatoCorto($requestParams->phonenumber);

        $rs = $db->fetchAll($sql, array($phoneSinPais));

        if($rs)
        {
            $responseParams = new BlackListUserResponseParams(
                'Error',
                $requestParams->transactionid,
                sprintf('El celular %s ya esta agregado al BlackList', $phoneSinPais)
            );
        }
        else
        {
            try {
                $logger->info('PREPARANDO PARA INSERCION AL BLACKLIST');
                $status = $db->insert('tigo_guatemala_blacklist', array('cel' => $phoneSinPais));
                $logger->info('status:[' . $status . ']');
                $responseParams = new BlackListUserResponseParams(
                    'Exito',
                    $requestParams->transactionid,
                    sprintf('El usuario %s fue agregado al BlackList', $phoneSinPais)
                );

             //borrar de todos los servicios
             $this->_unsubscribeUserBlackListedToAllServices($phoneSinPais);

            } catch(Zend_Db_Exception $e) {
                $logger->err($e);
                $responseParams = new BlackListUserResponseParams(
                    'Error',
                    $requestParams->transactionid,
                    'Error al intentar agregar al blacklist'
                );
            }
        }

        return $responseParams;
    }

    /**
     * @param UnBlackListUserRequestParams $requestParams
     * @return UnBlackListUserResponseParams
     */
    public function unBlackListUser(UnBlackListUserRequestParams $requestParams) {

        $front = Zend_Controller_Front::getInstance();
        $bootstrap = $front->getParam("bootstrap");
        $logger = $bootstrap->getResource('Logger');
        $this->_validateParams($requestParams, 'UnBlackListUserRequestParams');

        $options = $bootstrap->getOptions();

        $db = Zend_Db::factory(new Zend_Config($options['resources']['db']));
        $db->getConnection();

        $sql = "select * from tigo_guatemala_blacklist where cel = ?";
        $phoneSinPais = $this->_getFormatoCorto($requestParams->phonenumber);

        $rs = $db->fetchAll($sql, array($phoneSinPais));

        if($rs)
        {
            try {
                $logger->info('PREPARANDO PARA REMOVER DEL BLACKLIST');
                $status = $db->delete('tigo_guatemala_blacklist', array('cel = ?' => $phoneSinPais));
                $logger->info('status:[' . $status . ']');
                $responseParams = new UnBlackListUserResponseParams(
                    'Exito',
                    $requestParams->transactionid,
                    sprintf('El usuario %s fue eliminado del BlackList', $phoneSinPais)
                );

            } catch(Zend_Db_Exception $e) {
                $logger->err($e);
                $responseParams = new UnBlackListUserResponseParams(
                    'Error',
                    $requestParams->transactionid,
                    'Error al intentar remover del blacklist'
                );
            }

        }
        else
        {
            $responseParams = new UnBlackListUserResponseParams(
                'Error',
                $requestParams->transactionid,
                sprintf('El celular %s no esta en el BlackList', $phoneSinPais)
            );
        }

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

            $logger->info('Parametros validos');
        }

        return $isValid;
    }
}
