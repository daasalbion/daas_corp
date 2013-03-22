<?php
/**
 *
 */
class GetUserServicesRequestParams
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
     * @var string shortcodenumber
     */
    var $shortcodenumber;

    /**
     * @param string $phonenumber
     * @param int $transactionid
     * @param string $shortcodenumber
     */
    function __construct($phonenumber, $transactionid, $shortcodenumber = null) {
        $this->phonenumber = $phonenumber;
        $this->transactionid = $transactionid;
        $this->shortcodenumber = $shortcodenumber;
    }
}
