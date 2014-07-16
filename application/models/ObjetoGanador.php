<?php
class ObjetoGanador{
    private $combinacion_ganadora = null;
    private $cel_ganador = null;
    private $nombre_juego = null;

    function __construct( $combinacion_ganadora='', $cel_ganador='', $nombre_juego='' ){

        $this->combinacion_ganadora = $combinacion_ganadora;
        $this->cel_ganador = $cel_ganador ;
        $this->juego = $nombre_juego;

    }
    /**
     * @return null
     */
    public function getCombinacionGanadora()
    {
        return $this->combinacion_ganadora;
    }

    /**
     * @param null $combinacion_ganadora
     */
    public function setCombinacionGanadora($combinacion_ganadora)
    {
        $this->combinacion_ganadora = $combinacion_ganadora;
    }

    /**
     * @return null
     */
    public function getCelGanador()
    {
        return $this->cel_ganador;
    }

    /**
     * @param null $cel_ganador
     */
    public function setCelGanador($cel_ganador)
    {
        $this->cel_ganador = $cel_ganador;
    }

    /**
     * @return null
     */
    public function getNombreJuego()
    {
        return $this->nombre_juego;
    }

    /**
     * @param null $nombre_juego
     */
    public function setNombreJuego($nombre_juego)
    {
        $this->nombre_juego = $nombre_juego;
    }
}
?>