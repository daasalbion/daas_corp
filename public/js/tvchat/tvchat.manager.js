var tvchat = null;
var tvhot = null;
var tragamonedas_elementos_ganadores = [];
var tragamonedas_sexy_combinacion_ganadora = [];
var tragamonedas_numeros_ganadores = [];
var tombola_elementos_ganadores = [];
var tombola_numeros_ganadores = [];
//buffer
var tragamonedas_buffer = [];
var tragamonedas_sexy_buffer = [];
var tombola_buffer = [];
var piropos_buffer = [];
var piropos2_buffer = [];
var mensajero_buffer = [];
//elemento ganador
var nuevoGanador = null;
//premios
var premio_tragamonedas = {
    'premio_gs': 0,
    'premio_texto': null
};
var premio_tragamonedas_sexy = {
    'premio_gs': 0,
    'premio_texto': null
};
var premio_tombola = {
    'premio_gs': 0,
    'premio_texto': null
};
var premio_piropo = {
    'premio_gs': 0,
    'premio_texto': null
};
var premio_piropo2 = {
    'premio_gs': 0,
    'premio_texto': null
};

function ObjetoGanador( combinacion_ganadora, cel_ganador, nombre_juego, premio, combinacion_ganadora_list ) {
    this.combinacion_ganadora = combinacion_ganadora;
    this.cel_ganador = cel_ganador;
    this.nombre_juego = nombre_juego;
    this.premio = premio;
    this.combinacion_ganadora_list = combinacion_ganadora_list;
};

//elementos ganadores
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
var piropos2 =[];
var mensajero = null;
var total_guaranies = 0;
var total_sorteos = 0;

$(document).ready(function(){

    //abrir ventana principal
    $('#abrir_ventana_principal a').click(function(){

        if( tvchat == null ){

            tvchat = window.open("/tvchat/tv",
                "_blank", "width=800, height=600, menubar=no, toolbar=no, location=no, directories=no, status=no, scrollbars=auto, fullscreen=yes");

            $('#abrir_ventana_principal').addClass('disabled');
        }

        habilitarBotones( 1, null );
    });
    $('#abrir_ventana_tvhot').click(function(){

        $('#abrir_ventana_tvhot').attr('disabled', 'true');
        tvhot = window.open("/tvchat/tvhot",
            "_blank", "width=720, height=576, menubar=no, toolbar=no, location=no, directories=no, status=no, scrollbars=auto, fullscreen=yes");

    });

    //cerrar ventana principal
    $('#cerrar_ventana_principal').click(function(){

        $('#abrir_ventana_principal').removeClass('disabled');
        tvchat.close();
        tvchat = null;
        deshabilitarBotones();
    });
    $('#cerrar_ventana_tvhot').click(function(){
        $('#cerrar_ventana_tvhot').removeAttr('disabled');
        tvhot.close();
        deshabilitarBotones();
    });

    //cargar juegos
    $('#cargar_tragamonedas').click(function(){

        //valores por defecto
        var params = {
            "juego": "tragamonedas"
        };

        tvchat.cargarJuego(params);

        habilitarBotones( 2, 'tragamonedas' );
    });
    $('#cargar_tragamonedas_sexy').click(function(){

        //valores por defecto
        var params = {
            "juego": "tragamonedas_sexy"
        };

        tvchat.cargarJuego(params);

        habilitarBotones( 2, 'tragamonedas_sexy' );
    });
    $('#cargar_tombola').click(function(){

        var params = {

            "juego": "tombola"
        };

        tvchat.cargarJuego(params);

        habilitarBotones( 2, 'tombola' );
    });
    $('#cargar_piropo').click(function(){

        var params = {
            "juego": "piropo"
        };

        tvchat.cargarJuego(params);

        habilitarBotones( 2, 'piropo' );
    });
    $('#cargar_piropo2').click(function(){

        var params = {
            "juego": "piropo2"
        };

        tvchat.cargarJuego(params);

        habilitarBotones( 2, 'piropo' );
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

        nuevoGanador = tragamonedas_buffer.pop();
        nuevoGanador.premio = premio_tragamonedas.premio_texto;

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

        actualizarTotalGuaranies( premio_tragamonedas.premio_gs );

        habilitarBotones( 4, 'tragamonedas' );

    });
    $('#jugarTragamonedasSexy').click(function(){

        nuevoGanador = tragamonedas_sexy_buffer.pop();
        nuevoGanador.premio = premio_tragamonedas_sexy.premio_texto;
        nuevoGanador.combinacion_ganadora = tragamonedas_sexy_combinacion_ganadora.toString();
        nuevoGanador.combinacion_ganadora_list = tragamonedas_sexy_combinacion_ganadora;

        var params = {

            "jugar": "tragamonedas_sexy",
            "objeto_ganador": nuevoGanador
        }

        //borrar elemento sorteado
        $('#WinElementsTragamonedasSexy').empty();

        var tragamonedas_historial = $('#historial_tragamonedas_sexy');
        tragamonedas_historial.empty();

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

        actualizarTotalGuaranies( premio_tragamonedas_sexy.premio_gs );

        habilitarBotones( 4, 'tragamonedas_sexy' );

    });
    $('#jugarTombola').click(function(){

        nuevoGanador = tombola_buffer.pop();
        nuevoGanador.premio = premio_tombola.premio_texto;

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

        actualizarTotalGuaranies( premio_tombola.premio_gs );

        habilitarBotones( 4, 'tombola' );
    });
    $('#jugarPiropo').click(function(){

        nuevoGanador.premio = premio_piropo.premio_texto;
        //borrar elemento sorteado
        var sorteo = $('#WinElementsPiropo');
        sorteo.empty();

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
        actualizarTotalGuaranies( premio_piropo.premio_gs );

        habilitarBotones( 4, 'piropo' );
    });
    $('#jugarPiropo2').click(function(){

        nuevoGanador = piropos2_buffer.pop();

        var sorteo = $('#WinElementsPiropo2');
        sorteo.empty();

        var params = {

            "jugar": "piropo2",
            "objeto_ganador": nuevoGanador
        }

        var tragamonedas_historial = $('#jugador_piropo2');
        $('#jugador_piropo2').empty();

        piropos2.push(nuevoGanador);

        tragamonedas_historial.append(
            $(document.createElement("p"))
                .append( nuevoGanador.cel_ganador )
                .addClass("numeros_sorteados")
        )

        tvchat.jugarJuego(params);

        //habilitarBotones( 4, 'piropo' );
    });

    $('#jugarPiropo2Premio').click(function(){

        nuevoGanador = piropos2.pop();
        nuevoGanador.premio = premio_piropo2.premio_texto;

        var sorteo = $('#jugador_piropo2');
        sorteo.empty();

        var params = {

            "jugar": "piropo2",
            "objeto_ganador": nuevoGanador
        }

        var tragamonedas_historial = $('#historial_piropo2');
        $('#historial_piropo2 p').remove();

        piropos2.push(nuevoGanador);

        $.each(piropos2, function(i, objetoGanador) {

            tragamonedas_historial.append(
                $(document.createElement("p"))
                    .append( 'Piropo al aire - ' + objetoGanador.cel_ganador + ' - ' + objetoGanador.premio )
                    .addClass("numeros_sorteados")
            )
        });

        tvchat.jugarJuego(params);
        actualizarTotalGuaranies( premio_piropo2.premio_gs );

        //habilitarBotones( 4, 'piropo' );
    });
    $('#jugarPiropo2SinPremio').click(function(){

        nuevoGanador = piropos2.pop();
        nuevoGanador.premio = "Sin premio";

        var sorteo = $('#jugador_piropo2');
        sorteo.empty();

        var params = {

            "jugar": "piropo2",
            "objeto_ganador": nuevoGanador
        }

        var tragamonedas_historial = $('#historial_piropo2');
        $('#historial_piropo2 p').remove();

        piropos2.push(nuevoGanador);

        $.each(piropos2, function(i, objetoGanador) {

            tragamonedas_historial.append(
                $(document.createElement("p"))
                    .append( 'Piropo al aire - ' + objetoGanador.cel_ganador + ' - ' + objetoGanador.premio )
                    .addClass("numeros_sorteados")
            )
        });

        tvchat.jugarJuego(params);
        actualizarTotalGuaranies( 0 );

        //habilitarBotones( 4, 'piropo' );
    });

    //parar juegos
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
    $('#stopTombola').click(function(){

        var params = {

            "stop": "tombola"
        }

        tvchat.pararJuego(params);
    });

    //obtener elementos con ganadores
    $('#getWinElementsTragamonedas').click(function(){

        console.log("getWinElementsTragamonedas");
        $.get("/tvchat/get-win-elements-tragamonedas", { premio : true }, cargarNumerosGanadores, "json");
        //habilitarBotones( 3, 'tragamonedas' );

        return;
    });
    $('#getWinElementsPiropo').click(function(){

        $.get("/tvchat/get-win-elements-piropo", { premio: true }, cargarNumerosGanadores, "json");
        //habilitarBotones( 3, 'tragamonedas_sexy' );

        return;
    });
    $('#getWinElementsTragamonedasSexy').click(function(){

        console.log("getWinElementsTragamonedas");
        $.get("/tvchat/get-win-elements-tragamonedas-sexy", { premio : true }, cargarNumerosGanadores, "json");
        habilitarBotones( 3, 'tragamonedas_sexy' );

        return;
    });
    $('#getWinElementsTombola').click(function(){

        $("#stopTombola").text("Girar");
        console.log("getWinElementsTombola");
        $.get("/tvchat/get-win-elements-tombola", { premio : true }, cargarNumerosGanadores, "json");
        habilitarBotones( 3, 'tombola' );

        return;
    });

    //obtener elementos sin ganadores
    $('#getElementsTragamonedas').click(function(){

        $('#premios_tragamonedas').hide();
        premio_tragamonedas.premio_texto = "Sin Premio";
        premio_tragamonedas.premio_gs = 0;
        console.log("getElementsTragamonedas");
        $.get("/tvchat/get-win-elements-tragamonedas", { premio : false }, cargarNumerosGanadores, "json");
        habilitarBotones( 3, 'tragamonedas' );

        return;
    });
    $('#getElementsTombola').click(function(){

        $('#premios_tombola').hide();
        $("#stopTombola").text("Girar");
        premio_tombola.premio_texto = "Sin Premio";
        premio_tombola.premio_gs = 0;
        console.log("getElementsTombola");
        $.get("/tvchat/get-win-elements-tombola", { premio : false }, cargarNumerosGanadores, "json");
        habilitarBotones( 3, 'tombola' );

        return;
    });

    //obtener el mensaje con el mejor piropo
    $('#mensajes').on('click', '.seleccionar', function() {

        mensaje_seleccionado = $(this).data('mensaje');
        cel_ganador = '0982313289';

        //se aprovecha el campo cadena de combinacion_ganadora
        nuevoGanador = new ObjetoGanador( mensaje_seleccionado, cel_ganador, 'piropo', '', '' );
        //ocultamos el modal
        $('#opciones_mensajes').modal('hide');

        cargarNumerosGanadores(nuevoGanador);

        habilitarBotones( 3, 'piropo' );

    });

    //seleccionar premio
    $("#premios_tragamonedas").change(function(){

        premio_tragamonedas.premio_texto = $( "#premios_tragamonedas option:selected" ).text();
        premio_tragamonedas.premio_gs = $( "#premios_tragamonedas option:selected" ).val();
    });
    $("#premios_tragamonedas_sexy").change(function(){

        premio_tragamonedas_sexy.premio_texto = $( "#premios_tragamonedas_sexy option:selected" ).text();
        premio_tragamonedas_sexy.premio_gs = $( "#premios_tragamonedas_sexy option:selected" ).val();
    });
    $("#premios_tombola").change(function(){

        premio_tombola.premio_texto = $( "#premios_tombola option:selected" ).text();
        premio_tombola.premio_gs = $( "#premios_tombola option:selected" ).val();
    });
    $("#premios_piropos").change(function(){

        premio_piropo.premio_texto = $( "#premios_piropos option:selected" ).text();
        premio_piropo.premio_gs = $( "#premios_piropos option:selected" ).val();
    });
    $("#premios_piropos2").change(function(){

        premio_piropo2.premio_texto = $( "#premios_piropos2 option:selected" ).text();
        premio_piropo2.premio_gs = $( "#premios_piropos2 option:selected" ).val();
    });

    //mostrar ganador
    $("#mostrarGanadorTragamonedas").click(function(){

        var params = {

            "juego": "tragamonedas"
        }
        tvchat.mostrarGanador( params );
    });
    $("#mostrarGanadorTragamonedasSexy").click(function(){

        var params = {

            "juego": "tragamonedas_sexy"
        }
        tvchat.mostrarGanador( params );
    });

    //mostrar lineas punteadas para la conductora
    $("#ocultar_lineas_referencia_conductora").click(function(){

        var params = {

            "accion": "ocultar"
        }

        tvchat.lineasReferencia( params );
    });
    $("#mostrar_lineas_referencia_conductora").click(function(){

        var params = {

            "accion": "mostrar"
        }

        tvchat.lineasReferencia( params );
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
    /*function deshabilitarBotones(){
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
        $('#premios_piropos').hide();
    };*/

    /*function habilitarBotones( nivel, juego ){

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
            *//*if( tragamonedas_buffer.length > 0 ){
                $('#jugarTragamonedas').removeAttr('disabled');
            }*//*
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
        else if( nivel == 2 && juego == "tragamonedas_sexy" ){

            deshabilitarBotones();
            habilitarBotones( 1, null );
            $('#cerrar_tragamonedas_sexy').removeAttr('disabled');
            $('#getWinElementsTragamonedasSexy').removeAttr('disabled');
        }
        else if( nivel == 3 && juego == "tragamonedas_sexy" ){

            habilitarBotones( 2, "tragamonedas_sexy" );
            $('#premios_tragamonedas_sexy').show();
            $('#selectWinElements').show();
            $('#jugarTragamonedasSexy').removeAttr('disabled');
        }
        else if( nivel == 4 && juego == "tragamonedas_sexy" ){

            deshabilitarBotones();
            habilitarBotones( 2, "tragamonedas_sexy" );
            $('#stopRouletteSexy1').removeAttr('disabled');
            $('#stopRouletteSexy2').removeAttr('disabled');
            $('#stopRouletteSexy3').removeAttr('disabled');
            $('#cerrar_tragamonedas_sexy').removeAttr('disabled');
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
            $('#stopTombola').removeAttr('disabled');
            $('#cerrar_tombola').removeAttr('disabled');
        }
        else if( nivel == 2 && juego == "piropo" ){

            deshabilitarBotones();
            habilitarBotones( 1, null );
            $('#seleccionar_piropo').removeAttr('disabled');
        }
        else if( nivel == 3 && juego == "piropo" ){

            deshabilitarBotones();
            habilitarBotones( 2, null );
            $('#premios_piropos').show();
            $('#jugarPiropo').removeAttr('disabled');
        }
        else if( nivel == 4 && juego == "piropo" ){

            deshabilitarBotones();
            habilitarBotones( 2, "piropo" );
            $('#cerrar_piropo').removeAttr('disabled');
        }
    };*/

    function cargarNumerosGanadores( respuesta ){

        if( respuesta.juego == "tragamonedas" ){

            nuevoGanador = new ObjetoGanador( '', '', respuesta.juego, '', '' );

            $('#WinElementsTragamonedas').empty();

            $('#WinElementsTragamonedas')
                .append(
                    $(document.createElement("p"))
                );

            var WinElementsTragamonedas = $('#WinElementsTragamonedas p');
            //cargo los elementos ganadores del sorteo
            $.each( respuesta.sorteo, function( i, item ) {

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

                /*if( $('#WinElementsPiropo p').length > 0 )
                    $('#WinElementsPiropo p').remove();
                */

                $('#WinElementsPiropo').empty();

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
        else if( respuesta.juego == "piropo2" ){

            console.log("mierda");
            nuevoGanador = new ObjetoGanador( '', '', respuesta.juego, '', '' );

            $('#WinElementsPiropo2').empty();

            $('#WinElementsPiropo2')
                .append(
                    $(document.createElement("p"))
                );

            var WinElementsPiropo = $('#WinElementsPiropo2 p');
            WinElementsPiropo
                .append( respuesta.cel_ganador )
                .addClass("numeros_sorteados")

            piropos2_buffer.push(respuesta);
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
    };

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
    };

    function actualizarTotalGuaranies( premio ){

        total_guaranies += Number( premio );
        $('#total_guaranies').empty();
        $('#total_guaranies').append(total_guaranies);
        total_sorteos++ ;
        $('#total_sorteos').empty();
        $('#total_sorteos').append(total_sorteos);
    };

    function habilitarGirarTombola(){

        alert("mierda");
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

