<?php
/**
 *
 */
class UnSubscribeUserResponseParams
{
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
     * @param string $response
     * @param int $transactionid
     * @param string $message
     */
    function __construct($response, $transactionid, $message) {
        $this->response = $response;
        $this->transactionid = $transactionid;
        $this->message = $message;
    }
}
