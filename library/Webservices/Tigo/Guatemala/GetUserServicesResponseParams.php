<?php
/**
 *
 */
class GetUserServicesResponseParams
{
    //Webservices_Tigo_Guatemala_
    /**
     * @var string response
     */
    var $response = '';
    /**
     * @var int transactionid
     */
    var $transactionid = 0;
    /**
     * @var string message
     */
    var $message;
    /**
     * @var array services
     */
    var $services = array();

    /**
     * @param string $response
     * @param int $transactionid
     * @param string $message
     * @param array $services
     */
    function __construct($response, $transactionid, $message, $services) {
        $this->response = $response;
        $this->transactionid = $transactionid;
        $this->message = $message;
        $this->services = $services;
    }
}
