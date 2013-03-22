<?php
/**
 *
 */
class SubscribeToServiceRequestParams
{
    //Webservices_Tigo_Guatemala_

    /**
     * @var string phonenumber
     */
    var $phonenumber = '';
    /**
     * @var string servicename
     */
    var $servicename = '';
    /**
     * @var int transactionid
     */
    var $transactionid = 0;
    /**
     * @var string shortcodenumber
     */
    var $shortcodenumber;

    /**
     * @param string $phonenumber
     * @param string $servicename
     * @param int $transactionid
     * @param string $shortcodenumber
     */
    function __construct($phonenumber, $servicename, $transactionid, $shortcodenumber = null) {
        $this->phonenumber = $phonenumber;
        $this->servicename = $servicename;
        $this->transactionid = $transactionid;
        $this->shortcodenumber = $shortcodenumber;
    }
}
