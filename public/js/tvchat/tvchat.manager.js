//var tvchat = null;
var tragamonedas_elementos_ganadores = [];
var tragamonedas_sexy_combinacion_ganadora = [];
var tragamonedas_numeros_ganadores = [];
var tombola_elementos_ganadores = [];
var tombola_numeros_ganadores = [];
var tragamonedas_buffer = [];
var tragamonedas_sexy_buffer = [];
var tombola_buffer = [];
var piropos_buffer = [];
var nuevoGanador = null;
var premio_tragamonedas;
var premio_tragamonedas;
var premio_tombola;
var premio_piropo;
var mensajero_buffer;
var mensajero;

function ObjetoGanador( combinacion_ganadora, cel_ganador, nombre_juego, premio, combinacion_ganadora_list ) {
    this.combinacion_ganadora = combinacion_ganadora;
    this.cel_ganador = cel_ganador;
    this.nombre_juego = nombre_juego;
    this.premio = premio;
    this.combinacion_ganadora_list = combinacion_ganadora_list;
};

var tragamonedas = JSON.parse(localStorage.getItem('tragamonedas'));
var tragamonedas_sexy = JSON.parse(localStorage.getItem('tragamonedas_sexy'));
var tombola = JSON.parse(localStorage.getItem('tombola'));
var mensajes = JSON.parse(localStorage.getItem('mensajes'));

if (tragamonedas == null)
    var tragamonedas = [];
if (tragamonedas_sexy == null)
    var tragamonedas_sexy = [];
if (tombola == null)
    var tombola = [];
if (mensajes == null)
    var mensajes = [];

var piropos =[];

$(document).ready(function(){

    //abrir ventana principal
    $('#abrir_ventana_principal').click(function(){

        $('#abrir_ventana_principal').attr('disabled', 'true');
        habilitarBotones( 1, null );
        tvchat = window.open("/tvchat/tv",
            "_blank", "width=800, height=600, menubar=no, toolbar=no, location=no, directories=no, status=no, scrollbars=auto, fullscreen=yes");

    });
    //cerrar ventana principal
    $('#cerrar_ventana_principal').click(function(){
        $('#abrir_ventana_principal').removeAttr('disabled');
        tvchat.close();
        deshabilitarBotones();
    });

    //cargar juegos
    $('#cargar_tragamonedas').click(function(){

        habilitarBotones( 2, 'tragamonedas' );
        //valores por defecto
        var params = {
            "juego": "tragamonedas"
        };

        tvchat.cargarJuego(params);
    });
    $('#cargar_tragamonedas_sexy').click(function(){

        habilitarBotones( 2, 'tragamonedas' );
        //valores por defecto
        var params = {
            "juego": "tragamonedas_sexy"
        };

        tvchat.cargarJuego(params);
    });
    $('#cargar_tombola').click(function(){

        habilitarBotones( 2, 'tombola' );
        //valores por defecto
        var params = {
            "juego": "tombola"
        };


        tvchat.cargarJuego(params);
    });
    $('#cargar_piropo').click(function(){

        habilitarBotones( 2, 'piropo' );
        var params = {
            "juego": "piropo"
        };

        tvchat.cargarJuego(params);
    });
    $('#cargar_tvchat').click(function(){

        //valores por defecto
        var params = {
            "juego": "tvchat"
        };

        tvchat.cargarJuego(params);
    });

    //descargar juegos
    $('#cerrar_tragamonedas').click(function(){

        //habilitarBotones( 1, null );
        //valores por defecto
        var params = {
            "juego": "tragamonedas"
        };

        tvchat.descargarJuego(params);
    });
    $('#cerrar_tragamonedas_sexy').click(function(){

        //habilitarBotones( 1, null );
        //valores por defecto
        var params = {
            "juego": "tragamonedas_sexy"
        };

        tvchat.descargarJuego(params);
    });
    $('#cerrar_tombola').click(function(){

        //habilitarBotones( 1, null );
        //valores por defecto
        var params = {
            "juego": "tombola"
        };

        tvchat.descargarJuego(params);
    });
    $('#cerrar_tvchat').click(function(){

        //valores por defecto
        var params = {
            "juego": "tvchat"
        };

        tvchat.descargarJuego(params);
    });
    $('#cerrar_piropo').click(function(){

        //valores por defecto
        var params = {
            "juego": "piropo"
        };

        tvchat.descargarJuego(params);
    });

    //jugar juegos
    $('#jugarTragamonedas').click(function(){

        habilitarBotones( 4, 'tragamonedas' );
        nuevoGanador = tragamonedas_buffer.pop();
        nuevoGanador.premio = premio_tragamonedas;

        var params = {

            "jugar": "tragamonedas",
            "objeto_ganador": nuevoGanador
        }

        //borrar elemento sorteado
        var sorteo = $('#WinElementsTragamonedas p');
        sorteo.remove();

        var tragamonedas_historial = $('#historial_tragamonedas');
        $('#historial_tragamonedas p').remove();

        console.log("combinacion_ganadora: " + nuevoGanador.combinacion_ganadora);
        console.log("combinacion_ganadora_list: " + nuevoGanador.combinacion_ganadora_list);
        console.log("cel_ganador: " + nuevoGanador.cel_ganador);
        console.log("premio: " + nuevoGanador.premio);
        console.log("nombre_juego: " + nuevoGanador.nombre_juego);

        //se obtiene efectivamente un nuevo ganador
        tragamonedas.push(nuevoGanador);

        $.each(tragamonedas, function(i, objetoGanador) {

            tragamonedas_historial.append(
                $(document.createElement("p"))
                    .append(objetoGanador.combinacion_ganadora + ' - ' + objetoGanador.cel_ganador + ' - ' + objetoGanador.premio)
                    .addClass("numeros_sorteados")
            )
        });

        tvchat.jugarJuego(params);
    });
    $('#stopRoulette1').click(function(){

        //habilitarBotones( 4, 'tragamonedas' );
        //nuevoGanador = tragamonedas_buffer.pop();
        //nuevoGanador.premio = premio_tragamonedas;

        var params = {

            "stop": "tragamonedas",
            "roulette": "1"
        }

        tvchat.pararJuego(params);
    });
    $('#stopRoulette2').click(function(){

        //habilitarBotones( 4, 'tragamonedas' );
        //nuevoGanador = tragamonedas_buffer.pop();
        //nuevoGanador.premio = premio_tragamonedas;

        var params = {

            "stop": "tragamonedas",
            "roulette": "2"
        }

        tvchat.pararJuego(params);
    });
    $('#stopRoulette3').click(function(){

        //habilitarBotones( 4, 'tragamonedas' );
        //nuevoGanador = tragamonedas_buffer.pop();
        //nuevoGanador.premio = premio_tragamonedas;

        var params = {

            "stop": "tragamonedas",
            "roulette": "3"
        }

        tvchat.pararJuego(params);
    });
    $('#jugarTragamonedasSexy').click(function(){

        //habilitarBotones( 4, 'tragamonedas' );
        nuevoGanador = tragamonedas_sexy_buffer.pop();
        nuevoGanador.premio = premio_tragamonedas;
        nuevoGanador.combinacion_ganadora_list = tragamonedas_sexy_combinacion_ganadora;

        var params = {

            "jugar": "tragamonedas_sexy",
            "objeto_ganador": nuevoGanador
        }

        //borrar elemento sorteado
        var sorteo = $('#WinElementsTragamonedasSexy p');
        sorteo.remove();

        var tragamonedas_historial = $('#historial_tragamonedas_sexy');
        $('#historial_tragamonedas_sexy p').remove();

        console.log("combinacion_ganadora: " + nuevoGanador.combinacion_ganadora);
        console.log("combinacion_ganadora_list: " + nuevoGanador.combinacion_ganadora_list);
        console.log("cel_ganador: " + nuevoGanador.cel_ganador);
        console.log("premio: " + nuevoGanador.premio);
        console.log("nombre_juego: " + nuevoGanador.nombre_juego);

        //se obtiene efectivamente un nuevo ganador
        tragamonedas_sexy.push(nuevoGanador);

        $.each(tragamonedas_sexy, function(i, objetoGanador) {

            tragamonedas_historial.append(
                $(document.createElement("p"))
                    .append(objetoGanador.combinacion_ganadora + ' - ' + objetoGanador.cel_ganador + ' - ' + objetoGanador.premio)
                    .addClass("numeros_sorteados")
            )
        });

        tvchat.jugarJuego(params);
    });
    $('#stopRouletteSexy1').click(function(){

        var params = {

            "stop": "tragamonedas_sexy",
            "roulette": "1"
        }

        tvchat.pararJuego(params);
    });
    $('#stopRouletteSexy2').click(function(){

        var params = {

            "stop": "tragamonedas_sexy",
            "roulette": "2"
        }

        tvchat.pararJuego(params);
    });
    $('#stopRouletteSexy3').click(function(){

        var params = {

            "stop": "tragamonedas_sexy",
            "roulette": "3"
        }

        tvchat.pararJuego(params);
    });
    $('#jugarTombola').click(function(){

        habilitarBotones( 4, 'tombola' );
        nuevoGanador = tombola_buffer.pop();
        nuevoGanador.premio = premio_tombola;

        var params = {

            "jugar": "tombola",
            "objeto_ganador": nuevoGanador
        }

        var sorteo = $('#WinElementsTombola p');
        sorteo.remove();
        var tombola_historial = $('#historial_tombola');
        $('#historial_tombola p').remove();
        tombola.push(nuevoGanador);

        $.each(tombola, function(i, objetoGanador) {

            tombola_historial.append(
                $(document.createElement("p"))
                    .append(objetoGanador.combinacion_ganadora + ' - ' + objetoGanador.cel_ganador + ' - ' + objetoGanador.premio)
                    .addClass("numeros_sorteados")
            )
            tombola_numeros_ganadores.pop();
        });

        tvchat.jugarJuego(params);
    });
    $('#stopTombola').click(function(){

        var params = {

            "stop": "tombola"
        }

        tvchat.pararJuego(params);
    });
    $('#jugarPiropo').click(function(){
        //habilitarBotones( 4, 'tombola' );

        //borrar elemento sorteado
        var sorteo = $('#WinElementsPiropo p');
        sorteo.remove();

        var params = {

            "jugar": "piropo",
            "objeto_ganador": nuevoGanador
        }

        var tragamonedas_historial = $('#historial_piropo');
        $('#historial_piropo p').remove();

        piropos.push(nuevoGanador);

        $.each(piropos, function(i, objetoGanador) {

            tragamonedas_historial.append(
                $(document.createElement("p"))
                    .append(objetoGanador.combinacion_ganadora + ' - ' + objetoGanador.cel_ganador)
                    .addClass("numeros_sorteados")
            )
        });

        tvchat.jugarJuego(params);
    });

    //obtener elementos con ganadores
    $('#getWinElementsTragamonedas').click(function(){

        habilitarBotones( 3, 'tragamonedas' );
        console.log("getWinElementsTragamonedas");
        $.get("/tvchat/get-win-elements-tragamonedas", { premio : true }, cargarNumerosGanadores, "json");
        return;
    });
    $('#getWinElementsTragamonedasSexy').click(function(){

        habilitarBotones( 3, 'tragamonedas' );
        console.log("getWinElementsTragamonedas");
        $.get("/tvchat/get-win-elements-tragamonedas-sexy", { premio : true }, cargarNumerosGanadores, "json");
        return;
    });
    $('#getWinElementsTombola').click(function(){

        habilitarBotones( 3, 'tombola' );
        console.log("getWinElementsTombola");
        $.get("/tvchat/get-win-elements-tombola", { premio : true }, cargarNumerosGanadores, "json");
        return;
    });

    //obtener elementos sin ganadores
    $('#getElementsTragamonedas').click(function(){

        habilitarBotones( 3, 'tragamonedas' );
        console.log("getElementsTragamonedas");
        $.get("/tvchat/get-win-elements-tragamonedas", { premio : false }, cargarNumerosGanadores, "json");
        return;
    });
    $('#getElementsTombola').click(function(){

        habilitarBotones( 3, 'tombola' );
        console.log("getElementsTombola");
        $.get("/tvchat/get-win-elements-tombola", { premio : false }, cargarNumerosGanadores, "json");
        return;
    });

    //obtener el mensaje con el mejor piropo
    $('#mensajes').on('click', '.seleccionar', function() {

        habilitarBotones( 3, 'piropo' );

        mensaje_seleccionado = $(this).data('mensaje');
        cel_ganador = '0982313289';

        //se aprovecha el campo cadena de combinacion_ganadora
        nuevoGanador = new ObjetoGanador( mensaje_seleccionado, cel_ganador, 'piropo', '', '' );
        //ocultamos el modal
        $('#opciones_mensajes').modal('hide');

        cargarNumerosGanadores(nuevoGanador);

    });

    //seleccionar premio
    $("#premios_tragamonedas").change(function(){

        premio_tragamonedas = $( "#premios_tragamonedas option:selected" ).text();
    });
    $("#premios_tombola").change(function(){

        premio_tombola = $( "#premios_tombola option:selected" ).text();
    });

    //tragamonedas sexy
    $('#image_sample1').children().click(function(){
        var stopImageNumber = $(this).attr('data-value');
        updateStopImageNumber1(stopImageNumber);
    });
    var updateStopImageNumber1 = function(stopImageNumber) {
        $('.image_sample1').children().css('opacity' , 0.2);
        $('.image_sample1').children().filter('[data-value="' + stopImageNumber + '"]').css('opacity' , 1);
        $('.stop_image_number_param1').text(stopImageNumber);
        updateParamater1();
    }
    var updateParamater1 = function(){
        var stopImageNumber = Number($('.stop_image_number_param1').eq(0).text());
        tragamonedas_sexy_combinacion_ganadora[0] = stopImageNumber;
    }
    $('#image_sample2').children().click(function(){
        var stopImageNumber = $(this).attr('data-value');
        updateStopImageNumber2(stopImageNumber);
    });
    var updateStopImageNumber2 = function(stopImageNumber) {
        $('.image_sample2').children().css('opacity' , 0.2);
        $('.image_sample2').children().filter('[data-value="' + stopImageNumber + '"]').css('opacity' , 1);
        $('.stop_image_number_param2').text(stopImageNumber);
        updateParamater2();
    }
    var updateParamater2 = function(){
        var stopImageNumber = Number($('.stop_image_number_param2').eq(0).text());
        tragamonedas_sexy_combinacion_ganadora[1] = stopImageNumber;
    }
    $('#image_sample3').children().click(function(){
        var stopImageNumber = $(this).attr('data-value');
        updateStopImageNumber3(stopImageNumber);
    });
    var updateStopImageNumber3 = function(stopImageNumber) {
        $('.image_sample3').children().css('opacity' , 0.2);
        $('.image_sample3').children().filter('[data-value="' + stopImageNumber + '"]').css('opacity' , 1);
        $('.stop_image_number_param3').text(stopImageNumber);
        updateParamater3();
    }
    var updateParamater3 = function(){
        var stopImageNumber = Number($('.stop_image_number_param3').eq(0).text());
        tragamonedas_sexy_combinacion_ganadora[2] = stopImageNumber;
    }

    setInterval( obtenerMensajes, 1000*9*60 );

    obtenerMensajes();

    deshabilitarBotones();

    //funciones
    function deshabilitarBotones(){
        //tragamonedas
        $('#getWinElementsTragamonedas').attr('disabled', 'true');
        $('#getWinElementsTragamonedasSexy').attr('disabled', 'true');
        $('#getElementsTragamonedas').attr('disabled', 'true');
        $('#cargar_tragamonedas').attr('disabled', 'true');
        $('#cerrar_tragamonedas').attr('disabled', 'true');
        $('#cargar_tragamonedas_sexy').attr('disabled', 'true');
        $('#cerrar_tragamonedas_sexy').attr('disabled', 'true');
        $('#jugarTragamonedas').attr('disabled', 'true');
        $('#jugarTragamonedasSexy').attr('disabled', 'true');
        $('#stopRoulette1').attr('disabled', 'true');
        $('#stopRoulette2').attr('disabled', 'true');
        $('#stopRoulette3').attr('disabled', 'true');
        $('#stopRouletteSexy1').attr('disabled', 'true');
        $('#stopRouletteSexy2').attr('disabled', 'true');
        $('#stopRouletteSexy3').attr('disabled', 'true');
        $('#premios_tragamonedas_sexy').hide();
        $('#premios_tragamonedas').hide();
        $('#selectWinElements').hide();

        //tombola
        $('#getWinElementsTombola').attr('disabled', 'true');
        $('#getElementsTombola').attr('disabled', 'true');
        $('#cargar_tombola').attr('disabled', 'true');
        $('#cerrar_tombola').attr('disabled', 'true');
        $('#jugarTombola').attr('disabled', 'true');
        $('#stopTombola').attr('disabled', 'true');
        $('#premios_tombola').hide();

        //piropo
        $('#seleccionar_piropo').attr('disabled', 'true');
        $('#cargar_piropo').attr('disabled', 'true');
        $('#cerrar_piropo').attr('disabled', 'true');
        $('#jugarPiropo').attr('disabled', 'true');
    };

    function habilitarBotones( nivel, juego ){

        if( nivel == 1 && juego == null ){

            //deshabilitarBotones();
            $('#cargar_tragamonedas').removeAttr('disabled');
            $('#cargar_tragamonedas_sexy').removeAttr('disabled');
            $('#cargar_tombola').removeAttr('disabled');
            $('#cargar_piropo').removeAttr('disabled');
        }
        else if( nivel == 2 && juego == "tragamonedas" ){

            deshabilitarBotones();
            habilitarBotones( 1, null );
            $('#cerrar_tragamonedas').removeAttr('disabled');
            $('#getWinElementsTragamonedas').removeAttr('disabled');
            $('#getElementsTragamonedas').removeAttr('disabled');
            /*if( tragamonedas_buffer.length > 0 ){
                $('#jugarTragamonedas').removeAttr('disabled');
            }*/
        }
        else if( nivel == 3 && juego == "tragamonedas" ){

            habilitarBotones( 2, "tragamonedas" );
            $('#premios_tragamonedas').show();
            $('#jugarTragamonedas').removeAttr('disabled');
        }
        else if( nivel == 4 && juego == "tragamonedas" ){

            deshabilitarBotones();
            habilitarBotones( 2, "tragamonedas" );
            $('#stopRoulette1').removeAttr('disabled');
            $('#stopRoulette2').removeAttr('disabled');
            $('#stopRoulette3').removeAttr('disabled');
            $('#cerrar_tragamonedas').removeAttr('disabled');
        }
        else if( nivel == 2 && juego == "tombola" ){

            deshabilitarBotones();
            habilitarBotones( 1, null );
            $('#getWinElementsTombola').removeAttr('disabled');
            $('#getElementsTombola').removeAttr('disabled');
        }
        else if( nivel == 3 && juego == "tombola" ){

            habilitarBotones( 2, "tombola" );
            $('#premios_tombola').show();
            $('#jugarTombola').removeAttr('disabled');
        }
        else if( nivel == 4 && juego == "tombola" ){

            deshabilitarBotones();
            habilitarBotones( 2, "tombola" );
            $('#cerrar_tombola').removeAttr('disabled');
        }
        else if( nivel == 2 && juego == "piropo" ){

            deshabilitarBotones();
            habilitarBotones( 1, null );
            $('#seleccionar_piropo').removeAttr('disabled');
        }
        else if( nivel == 3 && juego == "piropo" ){

            deshabilitarBotones();
            habilitarBotones( 1, null );
            $('#jugarPiropo').removeAttr('disabled');
        }

    };

    function cargarNumerosGanadores( respuesta ){

        if( respuesta.juego == "tragamonedas" ){

            nuevoGanador = new ObjetoGanador( '', '', respuesta.juego, '', '' );

            if( $('#WinElementsTragamonedas p').length > 0 )
                $('#WinElementsTragamonedas p').remove();

            $('#WinElementsTragamonedas')
                .append(
                    $(document.createElement("p"))
                );

            var WinElementsTragamonedas = $('#WinElementsTragamonedas p');
            //cargo los elementos ganadores del sorteo
            $.each(respuesta.sorteo, function( i, item ) {

                if( i > 0 ){

                    WinElementsTragamonedas
                        .append(" - " + item)
                        .addClass("numeros_sorteados")
                }else{

                    WinElementsTragamonedas
                        .append(item)
                        .addClass("numeros_sorteados")
                }

                //cargar los elementos ganadores a pasar
                tragamonedas_elementos_ganadores[i] = item;
                nuevoGanador.combinacion_ganadora += item;
            });

            console.log("tragamonedas_elementos_ganadores: " + tragamonedas_elementos_ganadores);
            $('#WinElementsTragamonedas').append(

                $(document.createElement("p"))
                    .append(respuesta.cel_ganador)
                    .addClass("numeros_sorteados")
            );

            tragamonedas_numeros_ganadores.push(respuesta.cel_ganador);

            nuevoGanador.cel_ganador = respuesta.cel_ganador;
            nuevoGanador.combinacion_ganadora_list = tragamonedas_elementos_ganadores;

            //buffer donde voy guardando los sorteos
            tragamonedas_buffer.push(nuevoGanador);

        }
        else if( respuesta.juego == "tragamonedas_sexy" ){

            nuevoGanador = new ObjetoGanador( '', '', respuesta.juego, '', '' );

            if( $('#WinElementsTragamonedasSexy p').length > 0 )
                $('#WinElementsTragamonedasSexy p').remove();

            $('#WinElementsTragamonedasSexy')
                .append(
                    $(document.createElement("p"))
                );

            $('#WinElementsTragamonedasSexy').append(

                $(document.createElement("p"))
                    .append(respuesta.cel_ganador)
                    .addClass("numeros_sorteados")
            );

            nuevoGanador.cel_ganador = respuesta.cel_ganador;
            nuevoGanador.combinacion_ganadora_list = tragamonedas_sexy_combinacion_ganadora;

            //buffer donde voy guardando los sorteos
            tragamonedas_sexy_buffer.push(nuevoGanador);

        }
        else if( respuesta.juego == "tombola" ){

            nuevoGanador = new ObjetoGanador( '', '', respuesta.juego, '', '' );

            console.log("tombola");
            if( $('#WinElementsTombola p').length > 0 )
                $('#WinElementsTombola p').remove();

            $('#WinElementsTombola')
                .append(
                    $(document.createElement("p"))
                );

            var WinElementsTombola = $('#WinElementsTombola p');
            //cargo los elementos ganadores del sorteo
            $.each(respuesta.sorteo, function(i, item) {
                if( i > 0 ){

                    WinElementsTombola
                        .append( " - " + item )
                        .addClass("numeros_sorteados")
                }else{
                    WinElementsTombola
                        .append(item)
                        .addClass("numeros_sorteados")
                }

                //cargar los elementos ganadores a pasar
                tombola_elementos_ganadores[i] = item;
                nuevoGanador.combinacion_ganadora += item;
            });

            console.log("tombola_elementos_ganadores: "+ tombola_elementos_ganadores);

            $('#WinElementsTombola').append(
                $(document.createElement("p"))
                    .append(respuesta.cel_ganador)
                    .addClass("numeros_sorteados")
            );

            tombola_numeros_ganadores.push(respuesta.cel_ganador);
            nuevoGanador.cel_ganador = respuesta.cel_ganador;
            nuevoGanador.combinacion_ganadora_list = tombola_elementos_ganadores;

            tombola_buffer.push(nuevoGanador);
        }
        else if( respuesta.nombre_juego == "piropo" ){

            if( $('#WinElementsPiropo p').length > 0 )
                $('#WinElementsPiropo p').remove();

            console.log("piropo");
            $('#WinElementsPiropo')
                .append(
                    $(document.createElement("p"))
                );

            var WinElementsPiropo = $('#WinElementsPiropo p');
            WinElementsPiropo
                        .append( respuesta.cel_ganador + ' - ' + respuesta.combinacion_ganadora )
                        .addClass("numeros_sorteados")

            piropos_buffer.push(respuesta);
        }
    };

    function obtenerMensajes(){

        console.log("solicito mensajes nuevos");
        $.get("/tvchat/obtener-mensajes", {}, cargarOpcionesMensajes, "json");
        return;
    };

    function cargarOpcionesMensajes( respuesta ){

        var opciones_mensajes = $('#mensajes');
        $.each( respuesta.mensajes, function( i, mensaje ) {

            opciones_mensajes.append(

                $(document.createElement("tr"))
                    .append(
                        $(document.createElement("td"))
                            .append(
                                mensaje
                            ),
                        $(document.createElement("td"))
                            .append(
                                $(document.createElement("button"))
                                    .addClass("seleccionar btn btn-primary")
                                    .attr('data-mensaje', mensaje)
                                    .attr( 'id', i )
                                    .append("Seleccionar")
                            )
                    )
            )

            mensajes.push(mensaje);
        });
    };

    function obtenerMensajesBD(){

        console.log("llamada ajax");
        $.get("/tvchat/obtener-mensajes", {solicitud: true, id_mensaje:siguiente_id_solicitar}, cargarMensajes, "json");
        return;
    };

    function cargarMensajes(respuesta){
        $("#mensajes_nuevos_obtenidos").html("Mensajes Nuevos: " + respuesta.mensajes_marquee);
        mensajero_buffer.push(respuesta.mensajes_marquee);
        siguiente_id_solicitar = respuesta.siguiente_id_solicitar;
        console.log(mensajero_buffer);
    }

    function obtenerMensajesNuevosMensajero(){

        console.log("obtener nuevos mensajes");
        console.log(mensajes);
//    if( mensajes.length == 0 ){
//
//        console.log("mierda");
//        mensajes.push("ok");
//        console.log(mensajes);
//    }

        return mensajes;
    }

    $('#vaciar_localstorage').click(function(){

        localStorage.clear();
    });

});

$(window).bind( 'beforeunload', function(){

    //save info somewhere
    if( tvchat != null )
        tvchat.close();
    //return 'Esta seguro?';
    return;
});