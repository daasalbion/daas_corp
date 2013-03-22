<?php
/**
 *
 */
class UnSubscribeUserRequestParams
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
     * @param string $phonenumber
     * @param int $transactionid
     */
    function __construct($phonenumber, $transactionid) {
        $this->phonenumber = $phonenumber;
        $this->transactionid = $transactionid;
    }
}
