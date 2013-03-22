<?php

class Service
{
    /**
     * @var float price
     */
    var $price;
    /**
     * @var string servicename
     */
    var $servicename;
    /**
     * @var string description
     */
    var $description;
    /**
     * @var string subscribecode
     */
    var $subscribecode;
    /**
     * @var string subscribeshortnumber
     */
    var $subscribeshortnumber;
    /**
     * @var string unsubscribeshortnumber
     */
    var $unsubscribeshortnumber;

    /**
     * @param float $price
     * @param string $servicename
     * @param string $description
     * @param string $subscribecode
     * @param string $subscribeshortnumber
     * @param string $unsubscribeshortnumber
     */
    function __construct($price, $servicename, $description, $subscribecode, $subscribeshortnumber, $unsubscribeshortnumber) {
        $this->price = $price;
        $this->servicename = $servicename;
        $this->description = $description;
        $this->subscribecode = $subscribecode;
        $this->subscribeshortnumber = $subscribeshortnumber;
        $this->unsubscribeshortnumber = $unsubscribeshortnumber;
    }
}
