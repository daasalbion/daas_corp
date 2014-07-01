function cargarJuego(params){

    alert("juego: " + params["juego"]);
    console.log("juego: " + params["juego"]);
    var juego = $('#game_wrapper');

    if(params["juego"] == "tombola"){

        juego.append(
            //cargo los elementos
            $(document.createElement("div"))
                .attr('id', 'wheel')
                .append(
                    $(document.createElement("canvas"))
                        .attr('width', '300')
                        .attr('height', '200')
                        .attr('id', 'canvas')
            )
        )
        juego.append(

            $(document.createElement("div"))
            .attr('id', 'numeros_sorteados')
            .append(
                $(document.createElement("p"))
            )
        )

        wheel.init();
        //array asociativo para cargar los valores estaticamente en la tombola
        var venues = {
            "116208"  : "0",
            "66271"   : "1",
            "5518"    : "2",
            "392360"  : "3",
            "2210952" : "4",
            "207306"  : "5",
            "41457"   : "6",
            "101161"  : "7",
            "257424"  : "8",
            "512060"  : "9"
        };
        var segments = new Array();

        $.each(venues, function(key, value) {
            segments.push( value );
        });

        wheel.segments = segments;
        wheel.update();

    }else if( params['juego'] == "tragamonedas" ){

        alert("aun no cargado");
        console.log("aun no cargado");
    }else if( params['juego'] == "tvchat" ){

        alert("aun no cargado");
        console.log("aun no cargado");
    }
}

function descargarJuego(param){

    if(param['juego'] == "tombola"){

        var tombola = $('#wheel');
        var numeros_ganadores = $('#numeros_sorteados');
        tombola.remove();
        numeros_ganadores.remove();
    }else if( param['juego'] == "tragamonedas" ){

        var juego = $('#tragamonedas');
        juego.remove();
    }else if(param['juego'] == "tvchat" ){

        var juego = $('#ventana');
        juego.remove();
    }

    console.log("juego descargado!!");
}

var textarray;

function obtenerMensajesNuevos(){

    textarray = window.opener.obtenerMensajesNuevosTvchat();
}

$(document).ready(function(){

    function mostrarMensajesMarquee() {

        var length = textarray.length;
        console.log(textarray);
        console.log(textarray.length);
        if( textarray.length == 0 ){
            var eliminar = $( '.js-marquee-wrapper' );
            eliminar.remove();
            //coloco una cadena vacia
            obtenerMensajesNuevos();
            console.log( textarray );
            mostrarMensajesMarquee();
            return;
        }
        var texto = textarray.pop();
        $mwo
            .marquee('destroy')
            .bind('finished', mostrarMensajesMarquee)
            .html(texto)
            .marquee({duration: 7000, duplicated:false});
    }

    var $mwo = $('.marquee');
    var cadena = '';

    $('.marquee').marquee({
        //speed in milliseconds of the marquee
        speed: 10000,
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

    //obtenerMensajesNuevos();

    console.log("mostrar_mensajes");
    //mostrarMensajesMarquee();
});