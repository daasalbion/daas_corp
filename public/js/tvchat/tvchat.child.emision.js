//variables globales
var textarray_buffer =  window.opener.mensajero_buffer;
var textarray = window.opener.mensajero;
var elementos_ganadores = [];
var $mwo = null;

$(document).ready(function(){

    $mwo = $('.marquee');

    $('.marquee').marquee({
        //speed in milliseconds of the marquee
        duration: 20000,
        //gap in pixels between the tickers
        gap: 50,
        //time in milliseconds before the marquee will start animating
        delayBeforeStart: 0,
        //'left' or 'right'
        direction: 'left',
        //true or false - should the marquee be duplicated to show an effect of continues flow
        duplicated: false,
        //on hover pause the marquee - using jQuery plugin https://github.com/tobia/Pause
        pauseOnHover: true
    });

    //pause and resume links
    $('.pause').click(function(e){
        e.preventDefault();
        //$mwo.trigger('pause');
        var eliminar = $('.js-marquee-wrapper');
        eliminar.remove();
        $mwo.marquee('destroy');
    });

    cargarModuloPorDefecto();

    mostrarMensajesMarquee();

});

function mostrarMensajesMarquee() {

    if( textarray != null ){

        var length = textarray.length;

        if( length == 0 ){

            var mensajes_nuevos = obtenerMensajesNuevos();
            textarray = mensajes_nuevos;
            mostrarMensajesMarquee();

            return;
        }

        var texto = textarray.pop();
        $mwo
            .marquee('destroy')
            .bind('finished', mostrarMensajesMarquee)
            .html(texto)
            .marquee({duration: 20000, duplicated:false, gap:10, delayBeforeStart:0});
    }else{

        return;
    }
};

function setearDom(){

    console.log('setearDom()');
    rouletter1 = null;
    rouletter2 = null;
    rouletter3 = null;
    textarray_buffer =  window.opener.mensajero;
    textarray = [];
    elementos_ganadores = [];
    wheel = null;
    tombola = null;
    intervalo = 0;
    mostrar = 0;
    //$mwo = null;
    p = null;
};

/*
 $(window).bind( 'beforeunload', function(){

 window.opener.$("#cerrar_ventana_principal").trigger('click');
 return 'Esta seguro?';
 });*/
