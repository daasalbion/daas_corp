//variables globales
var rouletter1 = null;
var rouletter2 = null;
var rouletter3 = null;
var textarray = [];
var elementos_ganadores = [];
var wheel;
var tombola;

//funciones
function cargarJuego( params ){

    console.log("juego: " + params["juego"]);
    console.log("valores_ganadores: " + params["valores_ganadores"]);

    elementos_ganadores = params["valores_ganadores"];

    //elimino cualquier juego creado antes
    var juego = $('#game_wrapper');
    juego.empty();

    if( params['juego'] == "tragamonedas" ){

        juego.append(
            //cargo los elementos
            $(document.createElement("div"))
                .attr('id','tragamonedas_wrapper' )
                .addClass('tragamonedas_wrapper')
                .append(
                    $(document.createElement("div"))
                        .attr('id','tragamonedas_luces_verticales_izq' )
                        .addClass('tragamonedas_luces_verticales_izq'),
                    $(document.createElement("div"))
                        .attr('id','tragamonedas_luces_verticales_der' )
                        .addClass('tragamonedas_luces_verticales_der'),
                    $(document.createElement("div"))
                        .attr('id','tragamonedas_titulo' )
                        .addClass('tragamonedas_titulo')
                        .append(
                            $(document.createElement("img"))
                                .attr('src', "/img/tragamonedas_titulo.gif")
                        ),
                    $(document.createElement("div"))
                        .attr('id','tragamonedas_tiras' )
                        .addClass('tragamonedas_tiras')
                        .append(
                            $(document.createElement("div"))
                                .attr('id', 'tragamonedas_luces_horizontales_arriba')
                                .addClass('tragamonedas_luces_horizontales_arriba'),
                            $(document.createElement("div"))
                                .attr('id', 'tragamonedas_luces_horizontales_abajo')
                                .addClass('tragamonedas_luces_horizontales_abajo'),
                            $(document.createElement("div"))
                                .attr('id', 'tira1')
                                .addClass('tira1')
                                .append(
                                    $(document.createElement("div"))
                                        .attr('id', 'tira_imagenes1')
                                        .addClass('roulette1')
                                ),
                            $(document.createElement("div"))
                                .attr('id', 'tira2')
                                .addClass('tira2')
                                .append(
                                    $(document.createElement("div"))
                                        .attr('id', 'tira_imagenes2')
                                        .addClass('roulette2')
                                ),
                            $(document.createElement("div"))
                                .attr('id', 'tira3')
                                .addClass('tira3')
                                .append(
                                    $(document.createElement("div"))
                                        .attr('id', 'tira_imagenes3')
                                        .addClass('roulette3')
                                )
                        ),
                    $(document.createElement("div"))
                        .attr('id','tragamonedas_numeros_ganadores' )
                        .addClass('tragamonedas_numeros_ganadores')
                        .append(
                            $(document.createElement("div"))
                                .attr('id', 'linea_ganadora')
                                .addClass('linea_ganadora')
                                .append(
                                    $(document.createElement("div"))
                                        .attr('id', 'container_ganador')
                                        .addClass('container_ganador')
                                        .append(
                                            $(document.createElement("div"))
                                                .attr('id', 'premio')
                                                .addClass('premio')
                                            ,
                                            $(document.createElement("div"))
                                                .attr('id', 'linea')
                                                .addClass('linea')
                                        )
                                )
                        )
                )
        )
                              //0    1    2    3    4    5    6    7     8        9        10       11
        var imagenes_tiras1 = ['0', '1', '2', '3', '4', '5', '6', '7', 'coin', 'chomp', 'flower', 'star'];
        var imagenes_tiras2 = ['0', '1', '2', '3', '4', '5', '6', '7', 'coin', 'chomp', 'flower', 'star'];
        var imagenes_tiras3 = ['0', '1', '2', '3', '4', '5', '6', '7', 'coin', 'chomp', 'flower', 'star'];

        var tira_imagenes1 = $('#tira_imagenes1');
        var tira_imagenes2 = $('#tira_imagenes2');
        var tira_imagenes3 = $('#tira_imagenes3');
        var resultado = 0;

        for( var i= 0; i < imagenes_tiras1.length; i++ ){

            tira_imagenes1.append(
                $(document.createElement("img"))
                    .attr('src', "/img/tvchat/tragamonedas/" +imagenes_tiras1[i]+'.png')
            );
            tira_imagenes2.append(
                $(document.createElement("img"))
                    .attr('src', "/img/tvchat/tragamonedas/" +imagenes_tiras2[i]+'.png')
            );
            tira_imagenes3.append(
                $(document.createElement("img"))
                    .attr('src', "/img/tvchat/tragamonedas/" +imagenes_tiras3[i]+'.png')
            );
        }

        $('.roulette').find('img').hover(function(){

            console.log($(this).height());
        });
        var appendLogMsg = function(msg) {

            $('#msg')
                .append('<p class="muted">' + msg + '</p>')
                .scrollTop(100000000);
        }


        //creo los 3 elementos ruletas
        rouletter1 = $('div.roulette1');
        rouletter2 = $('div.roulette2');
        rouletter3 = $('div.roulette3');

        rouletter1.roulette( );
        rouletter2.roulette( );
        rouletter3.roulette( );

        //funcion para desplegar los resultados
        function cargar_elementos_sorteados(indice){

            var numeros_sorteados = $('#numeros_sorteados p');
            numeros_sorteados.append(
                //cargo los elementos
                $(document.createElement("div"))
                    .append(
                        $(document.createElement("img"))
                            .attr('src', imagenes_tiras1[indice]+'_prueba.png')
                    )
                    .addClass("numero_ganador")
            )
        }

    }
    else if( params["juego"] == "tombola" ){

        juego.append(
            //cargo los elementos
            $(document.createElement("div"))
                .attr('id', 'tombola_wheel_wrapper')
                .addClass( 'tombola_wheel_wrapper' )
                .append(
                    $(document.createElement("div"))
                        .addClass('tombola_luces'),
                    $(document.createElement("div"))
                        .addClass('tombola_aguja'),
                    $(document.createElement("div"))
                        .addClass('tombola_circulo_central'),
                    $(document.createElement("div"))
                        .attr('id', 'tombola_wrapper')
                        .addClass('tombola_wrapper')
                        .append(
                            $(document.createElement("div"))
                                .attr('id','wheel' )
                                .addClass('wheel')
                                .append(
                                    $(document.createElement("canvas"))
                                        .attr("width", "300")
                                        .attr("height", "300")
                                        .attr('id', 'canvas')
                                        .addClass('canvas')
                                )
                        ),
                    $(document.createElement("div"))
                        .attr('id','tombola_numeros_ganadores' )
                        .addClass('tombola_numeros_ganadores')
                )
        )

        tombola = $('.wheel');
        tombola.wheel( 'iniciar' );

    }
    else if( params["juego"] == "piropo" ){

        juego.append(
            //cargo los elementos
            $(document.createElement("div"))
                .attr('id', 'mensaje_wrapper')
                .append(
                    $(document.createElement("h2"))
                        .append('Piropo Seleccionado')
                        .addClass('centrar')
                )
                .append(
                    $(document.createElement("div"))
                        .attr('id', 'mensaje_seleccionado')
                        .addClass('centrar')
                )
                .addClass('mensaje_wrapper')
        )
    }
}

function descargarJuego(params){

    if( params['juego'] == "tragamonedas" ){

        var juego = $('#tragamonedas_wrapper');
        juego.remove();
    }
    else if( params['juego'] == "tombola" ){

        var juego = $('#tombola_wheel_wrapper');
        wheel.clear();
        juego.remove();
    }
    else if( params['juego'] == "piropo" ){

        var juego = $('#mensaje_wrapper');
        juego.remove();
    }
    else if( params['juego'] == "tvchat" ){

        var juego = $('#ventana');
        juego.remove();
    }

    console.log("juego descargado!!");
}

function jugarJuego(params){

    console.log("juego a jugar: " + params['jugar']);
    console.log("valores_ganadores: " + params["objeto_ganador"].combinacion_ganadora);
    elementos_ganadores = params["objeto_ganador"].combinacion_ganadora_list;
    var ganador = params['objeto_ganador'].cel_ganador;

    if( params['jugar'] == "tragamonedas" ){

        var resultado = 0;
        var p = {
            stopCallback : function($stopElm) {
                console.log("mierda stop " + $stopElm);
                resultado++;
                if( resultado == 3 ){

                    $('#premio').append( "Saldo para tu celular" );
                    $('#linea').append( "Ganador: " + ganador.substr(0,8) +"XX" );
                }
            }
        }

        $('#premio').empty();
        $('#linea').empty();

        p['stopImageNumber'] = Number(elementos_ganadores[0]);
        rouletter1.roulette( 'option', p );
        p['stopImageNumber'] = Number(elementos_ganadores[1]);
        rouletter2.roulette( 'option', p );
        p['stopImageNumber'] = Number(elementos_ganadores[2]);
        rouletter3.roulette( 'option', p );

        rouletter1.roulette('start');
        rouletter2.roulette('start');
        rouletter3.roulette('start');

    }
    else if( params['jugar'] == "tombola" ){

        var q = {

            valoresEsperados: elementos_ganadores,
            stopCallback : function($stopElm) {

                $('#tombola_numeros_ganadores #tombola_panel_ganador').hide();
                $('#tombola_numeros_ganadores').append(
                    $(document.createElement("div"))
                           .attr( 'id', 'tombola_premio_ganador' )
                           .append(
                                $(document.createElement("div"))
                                    .attr('id', 'premio_tombola')
                                    .addClass('premio_tombola')
                                    .append( "Saldo para tu celular" )
                                ,
                                $(document.createElement("div"))
                                    .attr('id', 'linea_tombola')
                                    .addClass('linea_tombola')
                                    .append( "Ganador: " + ganador.substr(0,8) +"XX" )
                           )
                )

                /*var $divs = $("#tombola_numeros_ganadores div").hide(),
                current = 0;

                $divs.eq(0).show();

                function showNext() {
                    if (current < $divs.length - 1) {
                        $divs.eq(current).delay(2000).fadeOut('fast', function() {
                            current++;
                            $divs.eq(current).fadeIn('fast');
                            showNext();
                        });
                    }
                }
                showNext();*/

            },
            stopNumberCallback : function( $stopElement ){

                console.log( 'mirar bien: ' + $stopElement );
                $('#tombola_numeros_ganadores #tombola_panel_ganador')
                    .append(
                        $(document.createElement("div"))
                            .append(
                                $(document.createElement("h4"))
                                    .append($stopElement)
                                    .addClass("numero")
                            )
                            .addClass("numero_ganador")
                    );
            }
        }

        //por si se vuelve a sortear y vaciar el numero ya sorteado
        $('#tombola_numeros_ganadores div').empty();
        $('#tombola_numeros_ganadores')
            .append(
                $(document.createElement("div"))
                    .attr( 'id', 'tombola_panel_ganador' )
                    .addClass( 'tombola_panel_ganador' )
            )

        tombola.wheel('option', q);
        tombola.wheel('start', q);

    }
    else if( params['jugar'] == "piropo" ){

        console.log("combinacion_ganadora: " + params["objeto_ganador"].combinacion_ganadora);
        console.log("combinacion_ganadora_list: " + params["objeto_ganador"].combinacion_ganadora_list);
        console.log("cel_ganador: " + params["objeto_ganador"].cel_ganador);
        console.log("nombre_juego: " + params["objeto_ganador"].nombre_juego);

        if( $('#mensaje_seleccionado h3').length > 0 )
            $('#mensaje_seleccionado h3').remove();

        if( $('#mensaje_seleccionado h4').length > 0 )
            $('#mensaje_seleccionado h4').remove();

        $('#mensaje_seleccionado')
            .append(
                $(document.createElement("h3"))
            )
            .append(
                $(document.createElement("h4"))
            );

        $('#mensaje_seleccionado h3').append(
            params['objeto_ganador'].combinacion_ganadora
        ).addClass('centrar')
        $('#mensaje_seleccionado h4').append(
            params['objeto_ganador'].cel_ganador
        ).addClass('centrar')
    }
}

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