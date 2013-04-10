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

    private function _estaSuscripto($phone,$alias,$shortcodenumber)
    {
        $front = Zend_Controller_Front::getInstance();
        $bootstrap = $front->getParam("bootstrap");
        $logger = $bootstrap->getResource('Logger');

        $options = $bootstrap->getOptions();

        $db = Zend_Db::factory(new Zend_Config($options['resources']['db']));
        $db->getConnection();

        $sql = "select cel from promosuscripcion.suscriptos S
                join info_promociones ip on (S.id_promocion=ip.id_promocion and S.id_carrier=5 AND S.cel= ? and ip.alias=?  and S.id_carrier=ip.id_carrier)";

        if($shortcodenumber)
         {
         $logger->info('Posee shortcodenumber');
         $sql .=" where ip.numero= ?";
         $rs = $db->fetchAll($sql, array($phone,$alias,$shortcodenumber));
         $logger->info('CONSULTA con shortcodenumber realizada');
         }
        else
         $rs = $db->fetchAll($sql, array($phone,$alias));

        $existe=false;
        if($rs)
         $existe=true;

     return $existe;
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

        $options = $bootstrap->getOptions();

        $db = Zend_Db::factory(new Zend_Config($options['resources']['db']));
        $db->getConnection();

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

        /*
          1. si ya se le envio y cobro todos los mensajes del dia: se reenvian todos sin volver a cobrar (confirmar)
          2. se envia (o reenvia) e intenta  cobrar todos los no cobrados.
         */

        $front = Zend_Controller_Front::getInstance();
        $bootstrap = $front->getParam("bootstrap");
        $logger = $bootstrap->getResource('Logger');
        $this->_validateParams($requestParams, 'RequestUserServiceRequestParams');

        $options = $bootstrap->getOptions();

        $db = Zend_Db::factory(new Zend_Config($options['resources']['db']));
        $db->getConnection();

        $phoneSinPais = $this->_getFormatoCorto($requestParams->phonenumber);
        $logger->info('cel:[' . $phoneSinPais . '] alias:[' . $requestParams->servicename . ']');

        $existeSuscripcion = $this->_estaSuscripto($phoneSinPais,$requestParams->servicename,$requestParams->shortcodenumber);

        if($existeSuscripcion)
        {
          //obtengo el id de la promocion de acuerdo a los parametros de entrada
          $sql="select id_promocion,numero from info_promociones
              where id_promocion=(select id_promocion from promosuscripcion.suscriptos where cel= ? and id_carrier=5)
              and id_carrier=5 and alias=?";
             //--and numero = '4550' //si pasa el shortcodenumber

          if($requestParams->shortcodenumber)
            {
            $sql .=" and numero= ?";
            $rs = $db->fetchAll($sql, array($phoneSinPais,$requestParams->servicename,$requestParams->shortcodenumber));
            }
          else
            {
            $rs = $db->fetchAll($sql, array($phoneSinPais,$requestParams->servicename));
            }
          $idPromocion= $rs[0]['id_promocion'];
          $numero=$rs[0]['numero'];
          $logger->info('id promo = '.$idPromocion);
          $logger->info('numero = '.$numero);
/*
          //cuenta la cantidad de mensajes que se envio hoy (estado 4 = envio de mensaje ok) y se cobro
           $sql2 ="select count(*)::integer as total from promosuscripcion.tigo_guatemala_billingcheck where id_carrier = 5 and estado = 4
                   and ts_proximo_chequeo::date = current_date and cel = ? and id_promocion = ? ";

           $rs2 = $db->fetchAll($sql2, array($phoneSinPais,$idPromocion));

           $envios_hoy=$rs2[0]['total'];
           $logger->info('enviados hoy = '.$envios_hoy);

           $sql3="select envios from promosuscripcion.envios_x_dia where id_carrier=5 and id_promocion = ? and dia = extract(dow from current_date)::integer";

           $rs3=  $db->fetchAll($sql3, array($idPromocion));
           $max_envios_hoy=$rs3[0]['envios'];
           $logger->info('max envios hoy = '.$max_envios_hoy);

           //1. si ya se le envio y cobro los 2: se reenvia los 2 sin volver a cobrar (confirmar)
            if($enviados_hoy = $max_envios_hoy)
            {
            try {
                for($i=0;$i<$max_envios_hoy;$i++)
                 {
                 $status = $db->insert('promosuscripcion.tigo_guatemala_billingcheck', array('id_carrier' => 5,'id_promocion' =>$idPromocion,'numero'=>$numero,'cel'=>$phoneSinPais,'estado'=>7,'ts_local'=>'now()','cantidad_chequeos'=>0,'ts_proximo_chequeo'=>'now()'));
                 $logger->info('status:[' . $status . ']');
                 }
                } catch(Zend_Db_Exception $e) {
                    $logger->err($e);
                }
            }
            else if($enviados_hoy < $max_envios_hoy)
            {
            $a_enviar= $max_envios_hoy - $enviados_hoy;

            }
*/
            //controla q no se intente reenviar un men con los mismos parametros con diferencia de al menos 3 minutos (ts_local)
          /*  $sql2="select *,(hora_actual - hora_ts_local ) as dif_hora, (minuto_actual - minuto_ts_local) as dif_min from
                  (select ts_local, (select extract(hour from ts_local)) as hora_ts_local, (select extract(minute from ts_local)) as minuto_ts_local,
                  (select extract(hour from timestamp 'now()')) as hora_actual, (select extract(minute from timestamp 'now()')) as minuto_actual
                  from promosuscripcion.tigo_guatemala_reenvios
                  where id_carrier=5 and id_promocion= ? and cel=? and ts_local::date=current_date) S2
                  where (hora_actual - hora_ts_local) =0 and (minuto_actual - minuto_ts_local) < 3";
           */
            if($requestParams->shortcodenumber)
             {
             $sql2="select coalesce((EXTRACT(epoch FROM (current_timestamp -
                   (select ts_local from  promosuscripcion.tigo_guatemala_reenvios where id_carrier=5 and id_promocion= ? and cel=? and numero=?
                   and ts_local::date=current_date limit 1))::interval)/60)::integer,-1) as dif_minutos";

             $rs2 = $db->fetchAll($sql2, array($idPromocion,$phoneSinPais,$requestParams->shortcodenumber));
             }
            else
             {
             $sql2="select coalesce((EXTRACT(epoch FROM (current_timestamp -
                   (select ts_local from  promosuscripcion.tigo_guatemala_reenvios where id_carrier=5 and id_promocion= ? and cel=?
                   and ts_local::date=current_date limit 1))::interval)/60)::integer,-1) as dif_minutos";

             $rs2 = $db->fetchRow($sql2, array($idPromocion,$phoneSinPais));
             }

            $logger->info('Extrae minutos:[' . print_r($rs2, true) . ']');

            if($rs2['dif_minutos'] != -1)
             {
             if( $rs2['dif_minutos'] < 5)
              {
                  $responseParams = new RequestUserServiceResponseParams(
                      'ERROR',
                      $requestParams->transactionid,
                      'Ya realizo un intento de reenvio hace menos de 5 minutos, espere unos minutos y luego intente nuevamente'
                  );
              }

             return $responseParams;
             }


           // id_carrier, id_promocion, numero, cel, monto, estado, id_contenido, tipo_contenido
            try {
                $logger->info('PREPARANDO PARA INSERCION EN promosuscripcion.tigo_guatemala_reenvios');
                $status = $db->insert('promosuscripcion.tigo_guatemala_reenvios', array('id_carrier' => 5,'id_promocion' =>$idPromocion,'numero'=>$numero,'cel'=>$phoneSinPais,'estado'=>0,'ts_local'=>'now()'));
                $logger->info('status:[' . $status . ']');
                $logger->info('INSERCION EXITOSA ....');

            } catch(Zend_Db_Exception $e) {
                $logger->err($e);
            }

          $responseParams = new RequestUserServiceResponseParams(
                'Exito',
                $requestParams->transactionid,
                sprintf('En breve recibira el siguiente contenido correspondiente al Servicio %s del %s', $requestParams->servicename, $requestParams->shortcodenumber)
          );
        }
        else
        {
            $responseParams = new RequestUserServiceResponseParams(
                'ERROR',
                $requestParams->transactionid,
                sprintf('Usted no se encuentra suscripto al Servicio %s del %s', $requestParams->servicename, $requestParams->shortcodenumber)
            );
        }



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
