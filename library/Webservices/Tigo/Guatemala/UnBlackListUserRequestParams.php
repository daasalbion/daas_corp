<?php
/**
 *
 */
class UnBlackListUserRequestParams
{

    /**
     * @var string phonenumber
     */
    var $phonenumber = '';
    /**
     * @var int transactionid
     */
    var $transactionid = 0;


    /**
     * @param string $phonenumber
     * @param int $transactionid
     */
    function __construct($phonenumber, $transactionid) {
        $this->phonenumber = $phonenumber;
        $this->transactionid = $transactionid;
    }
}
