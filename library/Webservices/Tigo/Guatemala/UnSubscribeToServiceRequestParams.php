<?php
/**
 *
 */
class UnSubscribeToServiceRequestParams
{
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
     * @param string $phonenumber
     * @param string $servicename
     * @param int $transactionid
     */
    function __construct($phonenumber, $servicename, $transactionid) {
        $this->phonenumber = $phonenumber;
        $this->servicename = $servicename;
        $this->transactionid = $transactionid;
    }
}
