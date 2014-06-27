<?php
/**
 *
 */
class notification{
    /**
     * @var string tid
     */
    public $tid;
    /**
     * @var int tipoEvento
     */
    public $tipoEvento;
    /**
     * @var string datoEvento
     */
    public $datoEvento;

    /**
     * @param string $tid
     * @param int $tipoEvento
     * @param string $datoEvento
     */
    function __construct($tid, $tipoEvento, $datoEvento) {
        $this->tid = $tid;
        $this->tipoEvento = $tipoEvento;
        $this->datoEvento = $datoEvento;
    }
}
