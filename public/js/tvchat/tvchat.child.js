//variables globales
var rouletter1 = null;
var rouletter2 = null;
var rouletter3 = null;
//var textarray_buffer = ['Saludos desde Bella Vista Norte/// Besos a mi hermana desde Colombia', 'Saludos desde Bella Vista Norte/// Besos a mi hermana desde Panama', 'Saludos desde Bella Vista Norte/// Besos a mi hermana desde Hamas', 'Saludos desde Bella Vista Norte/// Besos a mi hermana desde Turquia', 'Saludos desde Bella Vista Norte/// Besos a mi hermana desde Palestina', 'Saludos desde Bella Vista Norte/// Besos a mi hermana desde Corea', 'Saludos desde Bella Vista Norte/// Besos a mi hermana desde Japon', 'Saludos desde Bella Vista Norte/// Besos a mi hermana desde Brazil', 'Saludos desde Bella Vista Norte/// Besos a mi hermana desde Italia', 'Saludos desde Bella Vista Norte/// Besos a mi hermana desde Espanha''Saludos desde Bella Vista Norte/// Besos a mi hermana desde Colombia', 'Saludos desde Bella Vista Norte/// Besos a mi hermana desde Panama', 'Saludos desde Bella Vista Norte/// Besos a mi hermana desde Hamas', 'Saludos desde Bella Vista Norte/// Besos a mi hermana desde Turquia', 'Saludos desde Bella Vista Norte/// Besos a mi hermana desde Palestina', 'Saludos desde Bella Vista Norte/// Besos a mi hermana desde Corea', 'Saludos desde Bella Vista Norte/// Besos a mi hermana desde Japon', 'Saludos desde Bella Vista Norte/// Besos a mi hermana desde Brazil', 'Saludos desde Bella Vista Norte/// Besos a mi hermana desde Italia', 'Saludos desde Bella Vista Norte/// Besos a mi hermana desde Espanha'];
var textarray_buffer =  window.opener.mensajero;
//var textarray = ['Saludos desde Bella Vista Norte/// Besos a mi hermana desde Colombia', 'Saludos desde Bella Vista Norte/// Besos a mi hermana desde Panama', 'Saludos desde Bella Vista Norte/// Besos a mi hermana desde Hamas', 'Saludos desde Bella Vista Norte/// Besos a mi hermana desde Turquia', 'Saludos desde Bella Vista Norte/// Besos a mi hermana desde Palestina', 'Saludos desde Bella Vista Norte/// Besos a mi hermana desde Corea', 'Saludos desde Bella Vista Norte/// Besos a mi hermana desde Japon', 'Saludos desde Bella Vista Norte/// Besos a mi hermana desde Brazil', 'Saludos desde Bella Vista Norte/// Besos a mi hermana desde Italia', 'Saludos desde Bella Vista Norte/// Besos a mi hermana desde Espanha'];
var textarray = [];
var elementos_ganadores = [];
var wheel;
var tombola;
var intervalo = 0;
var mostrar = 0;
var $mwo = null;

//funciones
function cargarJuego( params ){

    console.log("juego: " + params["juego"]);
    console.log("valores_ganadores: " + params["valores_ganadores"]);

    elementos_ganadores = params["valores_ganadores"];
    clearInterval(mostrar);

    //elimino cualquier juego creado antes
    var juego = $('#game_wrapper');
    juego.empty();

    /*$('#interactive').append(
        $(document.createElement("div"))
            .attr('id', 'game_wrapper')
            .addClass('juegos')
    );*/

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
        /*var imagenes_tiras1 = ['0', '1', '2', '3', '4', '5', '6', '7', 'coin', 'chomp', 'flower', 'star'];
        var imagenes_tiras2 = ['0', '1', '2', '3', '4', '5', '6', '7', 'coin', 'chomp', 'flower', 'star'];
        var imagenes_tiras3 = ['0', '1', '2', '3', '4', '5', '6', '7', 'coin', 'chomp', 'flower', 'star'];*/
        var imagenes_tiras1 = [
            '0', '1', '2', '3', '4', '5', '6', '7', '8', '9',
            '10', '11', '12', '13', '14', '15', '16', '17', '18', '19',
            '20', '21', '22', '23', '24', '25', '26', '27', '28', '29',
            '30', '31', '32', '33', '34', '35', '36', '37', '38', '39',
            '40', '41', '42', '43', '44', '45', '46', '47', '48', '49',
            '50', '51', '52', '53', '54', '55', '56', '57', '58', '59',
            '60', '61', '62', '63', '64', '65', '66', '67', '68', '69',
            '70', '71', '72', '73', '74', '75', '76', '77', '78', '79',
            '80', '81', '82', '83', '84', '85', '86', '87', '88', '89',
            '90', '91', '92', '93', '94', '95', '96', '97', '98', '99',
            'coin', 'chomp', 'flower', 'star'];
        var imagenes_tiras2 = [
            '0', '1', '2', '3', '4', '5', '6', '7', '8', '9',
            '10', '11', '12', '13', '14', '15', '16', '17', '18', '19',
            '20', '21', '22', '23', '24', '25', '26', '27', '28', '29',
            '30', '31', '32', '33', '34', '35', '36', '37', '38', '39',
            '40', '41', '42', '43', '44', '45', '46', '47', '48', '49',
            '50', '51', '52', '53', '54', '55', '56', '57', '58', '59',
            '60', '61', '62', '63', '64', '65', '66', '67', '68', '69',
            '70', '71', '72', '73', '74', '75', '76', '77', '78', '79',
            '80', '81', '82', '83', '84', '85', '86', '87', '88', '89',
            '90', '91', '92', '93', '94', '95', '96', '97', '98', '99',
            'coin', 'chomp', 'flower', 'star'];
        var imagenes_tiras3 = [
            '0', '1', '2', '3', '4', '5', '6', '7', '8', '9',
            '10', '11', '12', '13', '14', '15', '16', '17', '18', '19',
            '20', '21', '22', '23', '24', '25', '26', '27', '28', '29',
            '30', '31', '32', '33', '34', '35', '36', '37', '38', '39',
            '40', '41', '42', '43', '44', '45', '46', '47', '48', '49',
            '50', '51', '52', '53', '54', '55', '56', '57', '58', '59',
            '60', '61', '62', '63', '64', '65', '66', '67', '68', '69',
            '70', '71', '72', '73', '74', '75', '76', '77', '78', '79',
            '80', '81', '82', '83', '84', '85', '86', '87', '88', '89',
            '90', '91', '92', '93', '94', '95', '96', '97', '98', '99',
            'coin', 'chomp', 'flower', 'star'];

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

        //creo los 3 elementos ruletas
        rouletter1 = $('div.roulette1');
        rouletter2 = $('div.roulette2');
        rouletter3 = $('div.roulette3');

        rouletter1.roulette( );
        rouletter2.roulette( );
        rouletter3.roulette( );

    }
    else if( params['juego'] == "tragamonedas_sexy" ){

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
                                        .attr('id', 'container_ganador_sexy')
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
        var imagenes_tiras1 = ['zapato', 'bombacha', 'portacena'];
        var imagenes_tiras2 = ['zapato', 'bombacha', 'portacena'];
        var imagenes_tiras3 = ['zapato', 'bombacha', 'portacena'];

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

        //creo los 3 elementos ruletas
        rouletter1 = $('div.roulette1');
        rouletter2 = $('div.roulette2');
        rouletter3 = $('div.roulette3');

        rouletter1.roulette( );
        rouletter2.roulette( );
        rouletter3.roulette( );

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
                        .append('Piropo Elegido')
                        .addClass('centrar titulo_piropo')
                )
                .append(
                    $(document.createElement("div"))
                        .attr('id', 'mensaje_seleccionado')
                        .addClass('centrar')
                )
                .addClass('mensaje_wrapper')
        )
    }
    else if( params["juego"] == "piropo2" ){

        juego.append(
            //cargo los elementos
            $(document.createElement("div"))
                .attr('id', 'mensaje_wrapper')
                .append(
                    $(document.createElement("h2"))
                        .append('Piropo al Aire')
                        .addClass('centrar titulo_piropo')
                )
                .append(
                    $(document.createElement("div"))
                        .attr('id', 'mensaje_seleccionado')
                        .addClass('centrar')
                )
                .addClass('mensaje_wrapper')
        )
    }
    else if( params["juego"] == "video" ){

        juego.append(
            //cargo los elementos
            $(document.createElement("div"))
                .attr('id', 'video_wrapper')
                .append(
                    $(document.createElement("video"))
                        .attr('width', '360')
                        .attr('height', '360')
                        .attr('autoplay', 'true')
                        .attr('loop', 'loop')
                        .attr('src', "/video/Dados-Demo.mp4")
                )
        );
    }

};

function descargarJuego( params ){

    if( params['juego'] == "tragamonedas" ){

        var juego = $('#tragamonedas_wrapper');
        juego.remove();
        $('#game_wrapper').empty();
    }
    else if( params['juego'] == "tragamonedas_sexy" ){

        var juego = $('#tragamonedas_wrapper');
        juego.remove();
        $('#game_wrapper').empty();
    }
    else if( params['juego'] == "tombola" ){

        var juego = $('#tombola_wheel_wrapper');
        juego.remove();
        $('#game_wrapper').empty();
    }
    else if( params['juego'] == "piropo" ){

        var juego = $('#mensaje_wrapper');
        juego.remove();
        $('#game_wrapper').empty();
    }
    else if( params['juego'] == "tvchat" ){

        var juego = $('#ventana');
        juego.remove();
        $('#game_wrapper').empty();
    }
    else if( params['juego'] == "video" ){

        var juego = $('#video_wrapper');
        juego.remove();
        $('#game_wrapper').empty();
    }

    console.log("juego descargado!!");
};

function jugarJuego( params ){

    console.log("juego a jugar: " + params['jugar']);
    console.log("valores_ganadores: " + params["objeto_ganador"].combinacion_ganadora);
    elementos_ganadores = params["objeto_ganador"].combinacion_ganadora_list;
    var ganador = params['objeto_ganador'].cel_ganador;
    var premio = params['objeto_ganador'].premio;
    clearInterval(mostrar);

    if( params['jugar'] == "tragamonedas" ){

        var resultado = 0;
        var p = {
            duration: 60,
            stopCallback : function($stopElm) {
                resultado++;
                if( resultado == 3 ){

                    $('#premio').append( premio );
                    if(ganador != "Sin Ganador"){

                        $('#linea').append( "Ganador: " + ganador.substr(0,8) +"XX" );
                    }
                    else{
                        $('#linea').append( "Ganador: " + ganador );
                    }
                }
            }
        }

        $('#premio').empty();
        $('#linea').empty();

        p['stopImageNumber'] = Number(elementos_ganadores[0]);
        p['speed'] = 20;
        rouletter1.roulette( 'option', p );
        p['stopImageNumber'] = Number(elementos_ganadores[1]);
        p['speed'] = 30;
        rouletter2.roulette( 'option', p );
        p['stopImageNumber'] = Number(elementos_ganadores[2]);
        p['speed'] = 40;
        rouletter3.roulette( 'option', p );

        rouletter1.roulette('start');
        rouletter2.roulette('start');
        rouletter3.roulette('start');

    }
    else if( params['jugar'] == "tragamonedas_sexy" ){

        var resultado = 0;
        var p = {

            duration: 60,
            stopCallback : function($stopElm) {
                console.log("mierda stop " + $stopElm);
                resultado++;
                if( resultado == 3 ){

                    $('#premio').append( premio );
                    if(ganador != "Sin Ganador"){
                        $('#linea').append( "Ganador: " + ganador.substr(0,8) +"XX" );
                    }
                    else{
                        $('#linea').append( "Ganador: " + ganador );
                    }
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

        var contador = 0;
        var q = {

            valoresEsperados: elementos_ganadores,
            stopCallback : function( $stopElm ) {

                if(ganador != "Sin Ganador"){

                    ganador = ganador.substr(0,8) +"XX";
                }

                $('#tombola_numeros_ganadores #tombola_panel_ganador').hide();
                $('#tombola_numeros_ganadores').append(
                    $(document.createElement("div"))
                           .attr( 'id', 'tombola_premio_ganador' )
                           .append(
                                $(document.createElement("div"))
                                    .attr('id', 'premio_tombola')
                                    .addClass('premio_tombola')
                                    .append( premio )
                                ,
                                $(document.createElement("div"))
                                    .attr('id', 'linea_tombola')
                                    .addClass('linea_tombola')
                                    .append( "Ganador: " + ganador )
                           )
                );

                intervalo = 0;
                mostrar = setInterval( function parpadeo(){
                    if( intervalo % 2 == 0 ){

                        $('#tombola_numeros_ganadores #tombola_panel_ganador').hide();
                        $('#tombola_numeros_ganadores #tombola_premio_ganador').show();

                    }else{

                        $('#tombola_numeros_ganadores #tombola_premio_ganador').hide();
                        $('#tombola_numeros_ganadores #tombola_panel_ganador').show();
                    }

                    intervalo++;
                    console.log( "mirar: " + intervalo );
                }, 2000);

            },
            stopNumberCallback : function( $stopElement ){

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

                contador++;
                console.log( "contador: " + contador );
                if( elementos_ganadores.length == contador  ){

                    window.opener.$("#stopTombola").removeAttr('disabled');
                    window.opener.$("#stopTombola").text("Mostrar");
                }else{

                    window.opener.$("#stopTombola").removeAttr('disabled');
                }
            }
        }

        //por si se vuelve a sortear y vaciar el numero ya sorteado
        $('#tombola_numeros_ganadores div').empty();
        $('#tombola_numeros_ganadores div').remove();
        $('#tombola_numeros_ganadores')
            .append(
                $(document.createElement("div"))
                    .attr( 'id', 'tombola_panel_ganador' )
                    .addClass( 'tombola_panel_ganador' )
            )

        tombola.wheel('option', q);
        tombola.wheel('start', q);

        window.opener.$("#stopTombola").attr('disabled', 'true');

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
        ).addClass('centrar mensaje_piropo')
        $('#mensaje_seleccionado h4').append(
            "Ganador: " + ganador.substr(0,8) +"XX"
        ).addClass('centrar mensaje_cel_ganador')
    }
    else if( params['jugar'] == "piropo2" ){

        console.log("combinacion_ganadora: " + params["objeto_ganador"].combinacion_ganadora);
        console.log("combinacion_ganadora_list: " + params["objeto_ganador"].combinacion_ganadora_list);
        console.log("cel_ganador: " + params["objeto_ganador"].cel_ganador);
        console.log("nombre_juego: " + params["objeto_ganador"].nombre_juego);

        $('#mensaje_seleccionado').empty();

        $('#mensaje_seleccionado')
            .append(
                $(document.createElement("h3"))
            )
            .append(
                $(document.createElement("h4"))
            );

        $('#mensaje_seleccionado h3').append(
           premio
        ).addClass('centrar mensaje_piropo')
        $('#mensaje_seleccionado h4').append(
            "Jugador: " + ganador.substr(0,8) +"XX"
        ).addClass('centrar mensaje_cel_ganador')
    }
};

function pararJuego( params ){

    console.log("juego a jugar: " + params);

    if( params['stop'] == "tragamonedas" ){

        if( params['roulette'] == "1" ){

            rouletter1.roulette('stop');
        }
        else if( params['roulette'] == "2" ){

            rouletter2.roulette('stop');
        }
        else if( params['roulette'] == "3" ){

            rouletter3.roulette('stop');
        }
    }
    else if( params['stop'] == "tragamonedas_sexy" ){

        if( params['roulette'] == "1" ){

            rouletter1.roulette('stop');
        }
        else if( params['roulette'] == "2" ){

            rouletter2.roulette('stop');
        }
        else if( params['roulette'] == "3" ){

            rouletter3.roulette('stop');
        }
    }
    else if( params['stop'] == "tombola" ){

        tombola.wheel( 'start' );
        window.opener.$("#stopTombola").attr('disabled', 'true');
    }
};

function mostrarGanador( params ){

    console.log("juego a mostrar ganador: " + params);

    if( params['juego'] == "tragamonedas" ){

        $("#container_ganador").removeClass('container_ganador');
    }
    else if( params['juego'] == "tragamonedas_sexy" ){

        $("#container_ganador_sexy").removeClass('container_ganador');
    }
};

function obtenerMensajesNuevos(){

    textarray_buffer = window.opener.mensajero;
    textarray = textarray_buffer;
    return textarray;
};

function cargarModulo( params ){

    console.log(params);
    if( params.accion == "ocultar" && params.modulo == "lineas_referencia" ){

        $('#conductora').hide();
        $('#margenes').hide();
    }
    else if( params.accion == "mostrar" && params.modulo == "lineas_referencia" ){

        $('#conductora').show();
    }
    else if( params.accion == "mostrar" && params.modulo == "fotos" ){

        var juego = $('#game_wrapper');
        juego.empty();

        juego.append(
            //cargo los elementos
            $(document.createElement("div"))
                .attr('id', 'interactive_fotos')
                .addClass('scroll-img')
                .append(
                    $(document.createElement("ul"))
                ),
            $(document.createElement("div"))
                .append(
                    $(document.createElement("img"))
                        .attr('src', '/img/tvchat/fotos/interactive_fotos_titulo.png')
                        .addClass('interactive_fotos_titulo')
                )
        )

        for( var i= 0; i < 2; i++ ){

            $('#interactive_fotos ul')
                .append(
                    $(document.createElement("li"))
                        .append(
                            $(document.createElement("img"))
                                .attr('src', '/img/tvchat/fotos/foto_1.png')
                        )
                );
        }

        $('#interactive_fotos').scrollbox({
            direction: 'h',
            distance: 361,
            speed: 150
        });
    }
    else if( params.accion == "ocultar" && params.modulo == "marquee" ){

        console.log('llego ak');
        //$mwo.trigger('pause');
        var eliminar = $('.js-marquee-wrapper');
        eliminar.remove();
        $mwo.marquee('destroy');
    }
};

$(document).ready(function(){

    function mostrarMensajesMarquee() {

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
            .marquee({duration: 15000, duplicated:false});
    }

    $mwo = $('.marquee');

    $('.marquee').marquee({
        //speed in milliseconds of the marquee
        duration: 15000,
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

    //obtenerMensajesNuevos();

    //setInterval( obtenerMensajesNuevos, 30000 );

    mostrarMensajesMarquee();

});

function cargarModuloPorDefecto(){

    console.log('mirar');
    var params = {

        accion: "mostrar",
        modulo: "fotos"
    };

    cargarModulo( params );
};

/*
$(window).bind( 'beforeunload', function(){

    window.opener.$("#cerrar_ventana_principal").trigger('click');
    return 'Esta seguro?';
});*/
