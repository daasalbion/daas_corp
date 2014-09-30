//v1.2
//ventanas
var tvchat = null;
var tvmensajero = null;
//elementos ganadores vamos a cambiar
var tragamonedas_elementos_ganadores = [];
var tragamonedas_sexy_combinacion_ganadora = [];
var tragamonedas_numeros_ganadores = [];
var tombola_elementos_ganadores = [];
var tombola_numeros_ganadores = [];
//buffer
var piropos_buffer = [];
var mensajero_buffer = [];
//elemento ganador
//var nuevoGanador = null;
//premios
var premio_tragamonedas = {
    'id_premio': 0,
    'premio_gs': 0,
    'premio_texto': null
};
var premio_tragamonedas_sexy = {
    'id_premio': 0,
    'premio_gs': 0,
    'premio_texto': null
};
var premio_tombola = {
    'id_premio': 0,
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

var valor_premios = [ 0, 500000, 200000, 100000, 50000 ];

function ObjetoGanador( combinacion_ganadora, cel_ganador, nombre_juego, premio, combinacion_ganadora_list ) {
    this.combinacion_ganadora = combinacion_ganadora;
    this.cel_ganador = cel_ganador;
    this.nombre_juego = nombre_juego;
    this.premio = premio;
    this.combinacion_ganadora_list = combinacion_ganadora_list;
};

function Sorteo(cel, codigo, premio, juego, id_sorteo){
    this.cel = cel;
    this.codigo = codigo;
    this.premio = premio;
    this.juego = juego;
    this.id_sorteo = id_sorteo;
};

var sorteoTragamonedas = null;
var sorteoTragamonedasSexy = null;
var sorteoTombola = null;
var sorteoPiropoMensajes = null;
var sorteoPiropoLlamadas = null;

function Premio( id_premio, premio_gs, premio_texto ){
    this.id_premio = id_premio;
    this.premio_gs = premio_gs;
    this.premio_texto = premio_texto;
};

//elementos ganadores
var tragamonedas = [];
var tragamonedas_sexy = [];
var tombola = [];
var mensajes = {};
var piropos =[];
var piropos2 =[];

//variables intermedias
var mensajero = null;
var total_guaranies = 0;
var total_sorteos = 0;
var siguiente_id_solicitar = -1;
var flujo_piropo = true;
var cantidad_sms_concatenar = 6;
var intervalo_mensajero = null;

//$(document).ready(function(){

//abrir ventana principal
$('#abrir_ventana_principal a').click(function(){
    if( tvchat == null ){
        tvchat = window.open("/tvchat/tv",
            "_blank", "width=800, height=600, menubar=no, toolbar=no, location=no, directories=no, " +
                "status=no, scrollbars=auto, fullscreen=yes");

        $('#abrir_ventana_principal').addClass('disabled');
        $('#cerrar_ventana_principal').removeClass('disabled');
    }
    habilitarBotones( 1, "tvchat" );
}).addClass('active');
$('#cerrar_ventana_principal a').click(function(){

    $('#abrir_ventana_principal').removeClass('disabled');
    if ( tvchat != null ){

        tvchat.close();
        tvchat = null;
    }

    //deshabilitarBotones();
});
$('#abrir_ventana_principal_emision a').click(function(){
    //if( tvchat == null ){

    tvmensajero = window.open("/tvchat/afortunados",
        "_blank", "width=800, height=600, menubar=no, toolbar=no, location=no, directories=no, " +
            "status=no, scrollbars=auto, fullscreen=yes");


    $('#abrir_ventana_principal').addClass('disabled');
    //}

    //habilitarBotones( 1, "tvchat" );

});
$('#cerrar_ventana_principal_emision a').click(function(){

    $('#abrir_ventana_principal').removeClass('disabled');
    if ( tvmensajero != null ){

        tvmensajero.close();
        tvmensajero = null;
    }

    //deshabilitarBotones();
});

//tragamonedas
$('#cargar_tragamonedas').click(function(){

    //valores por defecto
    var params = {

        juego: 'tragamonedas'
    };

    tvchat.cargarJuego(params);

    removerCss();
    $('#mostrar_bloque_tragamonedas a').css('background-color', 'gold');

    habilitarBotones( 2, 'tragamonedas' );

});
$('#cerrar_tragamonedas').click(function(){

    ////habilitarBotones( 1, null );
    //valores por defecto
    var params = {
        "juego": "tragamonedas"
    };

    tvchat.descargarJuego(params);

    removerCss();
    cargarModuloPorDefecto();
});
$('#getWinElementsTragamonedas').click(function(){

    var btn = $(this)
    btn.button('loading')
    $.get("/tvchat/get-win-elements-tragamonedas", { premio : true }, cargarNumerosGanadores, "json").always(function () {
        btn.button('reset');
        $('#premios_tragamonedas').show();
    }).fail(
        function() {
            alert( "Se perdio la conexion!!!" );
        });

    habilitarBotones( 3, "tragamonedas" );

    return;
});
$('#getElementsTragamonedas').click(function(){

    //$('#premios_tragamonedas').hide();

    var btn = $(this)
    btn.button('loading')
    $.get("/tvchat/get-win-elements-tragamonedas", { premio : false }, cargarNumerosGanadores, "json").always(function () {
        btn.button('reset')
        $('#premios_tragamonedas').hide()
    }).fail(
        function() {
            alert( "Se perdio la conexion!!!" );
        });

    habilitarBotones( 4, 'tragamonedas' );

    return;
});
$("#premios_tragamonedas").change(function(){

    premio_tragamonedas.premio_texto = $( "#premios_tragamonedas option:selected" ).text();
    premio_tragamonedas.id_premio = $( "#premios_tragamonedas option:selected" ).val();
    premio_tragamonedas.premio_gs = valor_premios[premio_tragamonedas.id_premio];

    var premio_texto = $( "#premios_tragamonedas option:selected" ).text();
    var id_premio = $( "#premios_tragamonedas option:selected" ).val();
    var premio_gs = valor_premios[id_premio];

    sorteoTragamonedas.premio = new Premio(id_premio, premio_gs, premio_texto);
});
$('#jugarTragamonedas').click(function(){

    $('#jugarTragamonedas').attr('disabled', 'true');

    var params = {

        juego: 'tragamonedas'
    };

    tvchat.cargarJuego(params);

    if( ( sorteoTragamonedas.cel == "Sin Ganador" ) || ( sorteoTragamonedas.premio == null ) ){

        var premio_texto = 'Sin Premio';
        var id_premio = 0;
        var premio_gs = valor_premios[id_premio];

        sorteoTragamonedas.premio = new Premio(id_premio, premio_gs, premio_texto);
    }

    var nuevoGanador = sorteoTragamonedas;
    console.log( 'id_sorteo' + nuevoGanador.id_sorteo);

    var params = {

        jugar: "tragamonedas",
        objeto_ganador: nuevoGanador
    }

    //borrar elemento sorteado
    $('#WinElementsTragamonedas p').remove();
    $('#WinPhoneNumberTragamonedas p').remove();

    var tragamonedas_historial = $('#historial_tragamonedas');
    $('#historial_tragamonedas p').remove();

    console.log("combinacion_ganadora_list: " + nuevoGanador.codigo);
    console.log("cel_ganador: " + nuevoGanador.cel);
    console.log("premio: " + nuevoGanador.premio);
    console.log("nombre_juego: " + nuevoGanador.juego);

    //se obtiene efectivamente un nuevo ganador
    tragamonedas.push(nuevoGanador);

    $.each(tragamonedas, function(i, objetoGanador) {

        tragamonedas_historial.append(
            $(document.createElement("p"))
                .append(objetoGanador.codigo + ' - ' + objetoGanador.cel + ' - ' + objetoGanador.premio.premio_texto)
                .addClass("numeros_sorteados")
        )
    });

    tvchat.jugarJuego(params);

    actualizarTotalGuaranies( nuevoGanador.premio.premio_gs );

    var parametros = {

        id_sorteo : nuevoGanador.id_sorteo,
        id_premio : nuevoGanador.premio.id_premio

    };

    console.log('hago null el sorteo tragamonedas');
    sorteoTragamonedas = null;

    guardarSorteo( parametros );

    habilitarBotones( 5, 'tragamonedas' );

});
$('#stopRoulette1').click(function(){

    ////habilitarBotones( 4, 'tragamonedas' );

    var params = {

        "stop": "tragamonedas",
        "roulette": "1"
    }

    tvchat.pararJuego(params);
});
$('#stopRoulette2').click(function(){

    ////habilitarBotones( 4, 'tragamonedas' );

    var params = {

        "stop": "tragamonedas",
        "roulette": "2"
    }

    tvchat.pararJuego(params);
});
$('#stopRoulette3').click(function(){

    ////habilitarBotones( 4, 'tragamonedas' );

    var params = {

        "stop": "tragamonedas",
        "roulette": "3"
    }

    tvchat.pararJuego(params);
});
$('#mostrarGanadorTragamonedas').click(function(){

    var params = {

        "juego": "tragamonedas"
    }

    tvchat.mostrarGanador( params );

});

//tragamonedas sexy
$('#cargar_tragamonedas_sexy').click(function(){

    //valores por defecto
    var params = {

        juego: 'tragamonedas_sexy'
    };

    tvchat.cargarJuego(params);

    removerCss();
    $('#mostrar_bloque_tragamonedas_sexy a').css('background-color', 'gold');

    habilitarBotones( 2, 'tragamonedas_sexy' );

});
$('#cerrar_tragamonedas_sexy').click(function(){

    ////habilitarBotones( 1, null );
    //valores por defecto
    var params = {
        "juego": "tragamonedas_sexy"
    };

    tvchat.descargarJuego(params);

    removerCss();

    cargarModuloPorDefecto();
});
$('#getWinElementsTragamonedasSexy').click(function(){

    var btn = $(this)
    btn.button('loading')
    $.get("/tvchat/get-win-elements-tragamonedas-sexy", { premio : true }, cargarNumerosGanadores, "json").always(function () {
        btn.button('reset')
        $('#selectWinElements').show()
    }).fail(
        function() {
            alert( "Se perdio la conexion!!!" );
        });

    habilitarBotones( 3, 'tragamonedas_sexy' );

    return;
});
$("#premios_tragamonedas_sexy").change(function(){

    premio_tragamonedas_sexy.premio_texto = $( "#premios_tragamonedas_sexy option:selected" ).text();
    premio_tragamonedas_sexy.premio_gs = $( "#premios_tragamonedas_sexy option:selected" ).val();

    var premio_texto = $( "#premios_tragamonedas_sexy option:selected" ).text();
    var id_premio = $( "#premios_tragamonedas_sexy option:selected" ).val();
    var premio_gs = valor_premios[id_premio];

    sorteoTragamonedasSexy.premio = new Premio(id_premio, premio_gs, premio_texto);
});
$('#jugarTragamonedasSexy').click(function(){

    $('#jugarTragamonedasSexy').attr('disabled', 'true');
    //valores por defecto
    var params = {

        juego: 'tragamonedas_sexy'
    };

    tvchat.cargarJuego(params);

    sorteoTragamonedasSexy.premio = new Premio(4, 50000, '50.000 Gs en Saldo');

    var nuevoGanador = sorteoTragamonedasSexy;

    var params = {

        jugar: "tragamonedas_sexy",
        objeto_ganador: nuevoGanador
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
                .append(objetoGanador.codigo + ' - ' + objetoGanador.cel + ' - ' + objetoGanador.premio.premio_texto)
                .addClass("numeros_sorteados")
        )
    });

    tvchat.jugarJuego(params);

    actualizarTotalGuaranies( sorteoTragamonedasSexy.premio.premio_gs );

    var parametros = {

        id_sorteo : nuevoGanador.id_sorteo,
        id_premio : nuevoGanador.premio.id_premio

    };

    guardarSorteo( parametros );

    console.log('hago null el sorteo tragamonedas');
    sorteoTragamonedasSexy = null;

    $('#selectWinElements').hide();

    habilitarBotones( 4, 'tragamonedas_sexy' );

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
$("#mostrarGanadorTragamonedasSexy").click(function(){

    var params = {

        "juego": "tragamonedas_sexy"
    }
    tvchat.mostrarGanador( params );
});
$('#image_sample1').children().click(function(){
    var stopImageNumber = $(this).attr('data-value');
    updateStopImageNumber1(stopImageNumber);
});
var updateStopImageNumber1 = function(stopImageNumber) {
    $('.image_sample1').children().css('opacity' , 0.2);
    $('.image_sample1').children().filter('[data-value="' + stopImageNumber + '"]').css('opacity' , 1);
    $('.stop_image_number_param1').text(stopImageNumber);
    updateParamater1();
};
var updateParamater1 = function(){
    var stopImageNumber = Number($('.stop_image_number_param1').eq(0).text());
    tragamonedas_sexy_combinacion_ganadora[0] = stopImageNumber;
    sorteoTragamonedasSexy.codigo[0] = stopImageNumber;
};
$('#image_sample2').children().click(function(){
    var stopImageNumber = $(this).attr('data-value');
    updateStopImageNumber2(stopImageNumber);
});
var updateStopImageNumber2 = function(stopImageNumber) {
    $('.image_sample2').children().css('opacity' , 0.2);
    $('.image_sample2').children().filter('[data-value="' + stopImageNumber + '"]').css('opacity' , 1);
    $('.stop_image_number_param2').text(stopImageNumber);
    updateParamater2();
};
var updateParamater2 = function(){
    var stopImageNumber = Number($('.stop_image_number_param2').eq(0).text());
    tragamonedas_sexy_combinacion_ganadora[1] = stopImageNumber;
    sorteoTragamonedasSexy.codigo[1] = stopImageNumber;
};
$('#image_sample3').children().click(function(){
    var stopImageNumber = $(this).attr('data-value');
    updateStopImageNumber3(stopImageNumber);
});
var updateStopImageNumber3 = function(stopImageNumber) {
    $('.image_sample3').children().css('opacity' , 0.2);
    $('.image_sample3').children().filter('[data-value="' + stopImageNumber + '"]').css('opacity' , 1);
    $('.stop_image_number_param3').text(stopImageNumber);
    updateParamater3();
};
var updateParamater3 = function(){
    var stopImageNumber = Number($('.stop_image_number_param3').eq(0).text());
    tragamonedas_sexy_combinacion_ganadora[2] = stopImageNumber;
    sorteoTragamonedasSexy.codigo[2] = stopImageNumber;
};

//tombola
$('#cargar_tombola').click(function(){

    var params = {

        "juego": "tombola"
    };

    tvchat.cargarJuego(params);

    removerCss();
    $('#mostrar_bloque_tombola a').css('background-color', 'gold');

    habilitarBotones( 2, 'tombola' );
});
$('#cerrar_tombola').click(function(){

    ////habilitarBotones( 1, null );
    //valores por defecto
    var params = {
        "juego": "tombola"
    };

    tvchat.descargarJuego(params);

    removerCss();

    cargarModuloPorDefecto();
});
$('#getWinElementsTombola').click(function(){

    $("#stopTombola").text("Girar");

    var btn = $(this)
    btn.button('loading')
    $.get("/tvchat/get-win-elements-tombola", { premio : true }, cargarNumerosGanadores, "json").always(function () {
        btn.button('reset')
        $("#premios_tombola").show()
    });

    habilitarBotones( 3, 'tombola' );

    return;
});
$('#getElementsTombola').click(function(){

    $('#premios_tombola').hide();
    $("#stopTombola").text("Girar");

    var btn = $(this)
    btn.button('loading')
    $.get("/tvchat/get-win-elements-tombola", { premio : false }, cargarNumerosGanadores, "json").always(function () {
        btn.button('reset')
    });

    habilitarBotones( 4, 'tombola' );

    return;
});
$("#premios_tombola").change(function(){

    premio_tombola.premio_texto = $( "#premios_tombola option:selected" ).text();
    premio_tombola.id_premio = $( "#premios_tombola option:selected" ).val();
    premio_tombola.premio_gs = valor_premios[premio_tombola.id_premio];

    var premio_texto = $( "#premios_tombola option:selected" ).text();
    var id_premio = $( "#premios_tombola option:selected" ).val();
    var premio_gs = valor_premios[id_premio];

    sorteoTombola.premio = new Premio(id_premio, premio_gs, premio_texto);
});
$('#jugarTombola').click(function(){

    if( ( sorteoTombola.cel == "Sin Ganador" ) || ( sorteoTombola.premio == null ) ){

        var premio_texto = 'Sin Premio';
        var id_premio = 0;
        var premio_gs = valor_premios[id_premio];

        sorteoTombola.premio = new Premio( id_premio, premio_gs, premio_texto );
    }

    var nuevoGanador = sorteoTombola;
    //nuevoGanador.premio = premio_tombola.premio_texto;

    var params = {

        jugar: "tombola",
        objeto_ganador: nuevoGanador
    }

    $('#WinElementsTombola').empty();
    $('#WinPhoneNumberTombola').empty();

    var tombola_historial = $('#historial_tombola');
    $('#historial_tombola p').remove();
    tombola.push(nuevoGanador);

    $.each(tombola, function(i, objetoGanador) {

        tombola_historial.append(
            $(document.createElement("p"))
                .append(objetoGanador.codigo + ' - ' + objetoGanador.cel + ' - ' + objetoGanador.premio.premio_texto)
                .addClass("numeros_sorteados")
        )
    });

    tvchat.jugarJuego( params );

    actualizarTotalGuaranies( nuevoGanador.premio.premio_gs );

    var parametros = {

        id_sorteo : nuevoGanador.id_sorteo,
        id_premio : nuevoGanador.premio.id_premio

    };

    console.log('hago null el sorteo tragamonedas');
    sorteoTombola = null;

    guardarSorteo( parametros );

    habilitarBotones( 5, 'tombola' );
});
$('#stopTombola').click(function(){

    var params = {

        stop: "tombola"
    }

    tvchat.pararJuego(params);
});

//piropo mensajes
$('#cargar_piropo').click(function(){
    var params = {
        juego: "piropo"
    };
    tvchat.cargarJuego(params);
    removerCss();
    $('#mostrar_bloque_piropo1 a').css('background-color', 'gold');
    habilitarBotones( 2, 'piropo' );
});
$('#cerrar_piropo').click(function(){

    //valores por defecto
    var params = {

        juego: "piropo"
    };

    tvchat.descargarJuego(params);

    removerCss();

    cargarModuloPorDefecto();
});
$('#seleccionar_piropo').click(function(){
    $('#mensajes').empty();
    cargarOpcionesMensajes(mensajes);
});
$('#mensajes').on('click', '.seleccionar', function() {

    var mensaje = $(this).data('mensaje');
    var cel = $(this).data('cel');
    var id_mensaje = $(this).data('id');

    var parametros = {

        id_mensaje : id_mensaje,
        id_juego : 4,
        cel : cel

    };

    //cel, codigo, premio, juego, id_sorteo
    //seteamos la variable
    sorteoPiropoMensajes = new Sorteo( cel, mensaje, '', 'piropo', '' );
    sorteoPiropoMensajes.id_mensaje = id_mensaje;
    //luego seteamos id_sorteo
    obtenerSorteoPiropoMensajes( parametros );

    //ocultamos el modal
    $('#opciones_mensajes').modal('hide');

    cargarNumerosGanadores( sorteoPiropoMensajes );

    habilitarBotones( 3, 'piropo' );

});
$('#WinElementsPiropo').on('click', '.eliminar_piropo', function(){
    var indice = $(this).data('id');
    piropos_buffer.splice(indice, 1);
    refrescarPiroposGanadores();
});
$("#premios_piropos").change(function(){

    premio_piropo.premio_texto = $( "#premios_piropos option:selected" ).text();
    premio_piropo.premio_gs = $( "#premios_piropos option:selected" ).val();

    var premio_texto = $( "#premios_piropos option:selected" ).text();
    var id_premio = $( "#premios_piropos option:selected" ).val();
    var premio_gs = valor_premios[id_premio];

    sorteoPiropoMensajes.premio = new Premio(id_premio, premio_gs, premio_texto);

});
$('#jugarPiropo').click(function(){

    sorteoPiropoMensajes = piropos_buffer[0];
    piropos_buffer.shift();

    refrescarPiroposGanadores();

    sorteoPiropoMensajes.premio = new Premio(4, 50000, '50.000 Gs en Saldo');
    var nuevoGanador = sorteoPiropoMensajes;

    var params = {

        jugar: 'piropo',
        objeto_ganador: nuevoGanador
    }

    var tragamonedas_historial = $('#historial_piropo');
    $('#historial_piropo p').remove();

    piropos.push(nuevoGanador);

    $.each(piropos, function(i, objetoGanador) {

        tragamonedas_historial.append(

            $(document.createElement("p"))

                .append( objetoGanador.codigo + ' - ' + objetoGanador.cel + ' - ' + objetoGanador.premio.premio_texto )
                .addClass("numeros_sorteados")
        )
    });

    tvchat.jugarJuego(params);

    actualizarTotalGuaranies( nuevoGanador.premio.premio_gs );

    var parametros = {

        id_sorteo : nuevoGanador.id_sorteo,
        id_premio : nuevoGanador.premio.id_premio

    };

    console.log('seteo bien la variable '+sorteoPiropoMensajes.id_mensaje);
    mensajes[sorteoPiropoMensajes.id_mensaje].ya_sorteado = true;

    console.log('hago null el sorteo tragamonedas');
    sorteoPiropoMensajes = null;

    guardarSorteo( parametros );

    //habilitarBotones( 4, 'piropo' );
});

//piropo llamadas
$('#cargar_piropo2').click(function(){

    var params = {

        juego: "piropo2"
    };

    tvchat.cargarJuego(params);

    removerCss();
    $('#mostrar_bloque_piropo2 a').css('background-color', 'gold');

    //habilitarBotones( 2, 'piropo' );
});
$('#cerrar_piropo2').click(function(){

    //valores por defecto
    var params = {

        juego: "piropo2"
    };

    tvchat.descargarJuego(params);

    removerCss();

    cargarModuloPorDefecto();
});
$('#getWinElementsPiropo').click(function(){

    var btn = $(this)
    btn.button('loading')
    $.get("/tvchat/get-win-elements-piropo", { premio: true }, cargarNumerosGanadores, "json").always(function () {
        btn.button('reset')
    });

    ////habilitarBotones( 3, 'tragamonedas_sexy' );

    return;
});
$("#premios_piropos2").change(function(){

    premio_piropo2.premio_texto = $( "#premios_piropos2 option:selected" ).text();
    premio_piropo2.premio_gs = $( "#premios_piropos2 option:selected" ).val();

    var premio_texto = $( "#premios_piropos2 option:selected" ).text();
    var id_premio = $( "#premios_piropos2 option:selected" ).val();
    var premio_gs = valor_premios[id_premio];

    sorteoPiropoLlamadas.premio = new Premio(id_premio, premio_gs, premio_texto);

});
$('#jugarPiropo2').click(function(){

    sorteoPiropoLlamadas.premio = new Premio(0,0,'Sin premio');

    var nuevoGanador = sorteoPiropoLlamadas;

    $('#WinElementsPiropo2').empty();

    var params = {

        jugar: "piropo2",
        objeto_ganador: nuevoGanador
    }

    var tragamonedas_historial = $('#jugador_piropo2');
    $('#jugador_piropo2').empty();

    tragamonedas_historial.append(
        $(document.createElement("p"))
            .append( nuevoGanador.cel )
            .addClass("numeros_sorteados")
    )

    tvchat.jugarJuego(params);

    ////habilitarBotones( 4, 'piropo' );
});
$('#jugarPiropo2Premio').click(function(){

    sorteoPiropoLlamadas.premio = new Premio(4, 50000, '50.000 Gs en Saldo');

    var nuevoGanador = sorteoPiropoLlamadas;

    piropos2.push(nuevoGanador);

    var sorteo = $('#jugador_piropo2');
    sorteo.empty();

    var params = {

        jugar: "piropo2",
        objeto_ganador: nuevoGanador
    }

    var tragamonedas_historial = $('#historial_piropo2');
    $('#historial_piropo2 p').remove();

    $.each(piropos2, function(i, objetoGanador) {

        tragamonedas_historial.append(
            $(document.createElement("p"))
                .append( 'Piropo al aire - ' + objetoGanador.cel + ' - ' + objetoGanador.premio.premio_texto )
                .addClass("numeros_sorteados")
        )
    });

    tvchat.jugarJuego(params);

    actualizarTotalGuaranies( nuevoGanador.premio.premio_gs );

    var parametros = {

        id_sorteo : nuevoGanador.id_sorteo,
        id_premio : nuevoGanador.premio.id_premio

    };

    sorteoPiropoLlamadas = null;

    guardarSorteo( parametros );

    var params = {

        "juego": "piropo_llamada",
        "mensaje": "Ganaste!!!"
    }

    tvchat.mostrarGanador( params );
    ////habilitarBotones( 4, 'piropo' );

});
$('#jugarPiropo2SinPremio').click(function(){

    var nuevoGanador = sorteoPiropoLlamadas;

    piropos2.push(nuevoGanador);

    var sorteo = $('#jugador_piropo2');
    sorteo.empty();

    var params = {

        jugar: "piropo2",
        objeto_ganador: nuevoGanador
    }

    var tragamonedas_historial = $('#historial_piropo2');
    $('#historial_piropo2 p').remove();

    $.each(piropos2, function(i, objetoGanador) {

        tragamonedas_historial.append(
            $(document.createElement("p"))
                .append( 'Piropo al aire - ' + objetoGanador.cel + ' - ' + objetoGanador.premio.premio_texto )
                .addClass("numeros_sorteados")
        )
    });

    tvchat.jugarJuego(params);

    actualizarTotalGuaranies( nuevoGanador.premio.premio_gs );

    var parametros = {

        id_sorteo : nuevoGanador.id_sorteo,
        id_premio : nuevoGanador.premio.id_premio

    };

    sorteoPiropoLlamadas = null;
    guardarSorteo( parametros );

    var params = {

        "juego": "piropo_llamada",
        "mensaje": "Perdiste!!!"
    }

    tvchat.mostrarGanador( params );
    ////habilitarBotones( 4, 'piropo' );

});

//video
$('#cargar_video').click(function(){

    var params = {

        juego: "video"
    };

    tvchat.cargarJuego(params);

    removerCss();
    $('#mostrar_bloque_video a').css('background-color', 'gold');

    ////habilitarBotones( 2, 'piropo' );
});
$('#cerrar_video').click(function(){

    //valores por defecto
    var params = {

        juego: "video"
    };

    tvchat.descargarJuego(params);

    removerCss();

    cargarModuloPorDefecto();
});

//mensajero
$('#cargar_tvmensajero').click(function(){

    removerCss();
    $('#mostrar_bloque_tvmensajero a').css('background-color', 'gold');

    mensajero_buffer = [];
    mensajero = [];

    siguiente_id_solicitar = 0;

    obtenerMensajesMarquee();

    tvchat.procesarMensajes();

    var params = {

        modulo: "tvmensajero",
        accion: "mostrar"
    };

    tvchat.cargarModulo(params);

    intervalo_mensajero = setInterval( obtenerMensajesMarquee, 30*1000 );

});
$('#cerrar_tvmensajero').click(function(){

    var params = {

        modulo: "tvmensajero",
        accion: "ocultar"
    };

    tvchat.cargarModulo(params);

    removerCss();
    cargarModuloPorDefecto();

    flujo_piropo = true;

});
$('#marquee').on('click', '.seleccionar_marquee', function() {

    var id_mensaje = $(this).data('id');
    mensajero_buffer.splice(id_mensaje,1);

    //ocultamos el modal
    $('#filtro_mensajajero').modal('hide');


});
$('#seleccionar_marquee').click(function(){

    $('#marquee').empty();
    filtrosMensajes(tvchat.textarray_buffer);
});
$('#parar_marquee').click(function(){

    var params = {

        accion: "ocultar",
        modulo: "marquee"
    }

    tvchat.cargarModulo( params );
});
$('#mover_arriba').click(function(){
    console.log("mirar");
    tvchat.$('.marquee_habilitado').css( "top", "-=1" );
});
$('#mover_abajo').click(function(){

    tvchat.$('.marquee_habilitado').css( "top", "+=1" );
});

//tvhot
$('#abrir_ventana_tvhot').click(function(){

    clearInterval(intervalo_mensajero);

    mensajero_buffer = [];
    mensajero = [];

    tvchat.textarray_buffer = [];

    siguiente_id_solicitar = 0;

    obtenerMensajesMarquee();

    removerCss();
    $('#mostrar_bloque_tvhot a').css('background-color', 'gold');

    var params = {

        accion: "mostrar",
        modulo: "tvhot"
    }

    intervalo_mensajero = setInterval( obtenerMensajesMarquee, 30*1000 );

    tvchat.cargarModulo( params );
});
$('#cerrar_ventana_tvhot').click(function(){

    //$('#abrir_ventana_tvhot').removeAttr('disabled');

    var params = {

        accion: "ocultar",
        modulo: "tvhot"
    }

    tvchat.cargarModulo( params );

});
$('#arriba').click(function(){

    tvchat.$('#tvhot_mensajero').css( "top", "-=1" );
});
$('#abajo').click(function(){

    tvchat.$('#tvhot_mensajero').css( "top", "+=1" );
});
$('#izquierda').click(function(){

    tvchat.$('#tvhot_mensajero').css( "left", "-=1" );
});
$('#derecha').click(function(){

    tvchat.$('#tvhot_mensajero').css( "left", "+=1" );
});
$('#alto_mas').click(function(){

    tvchat.$('#tvhot_mensajero').css( "height", "+=1" );
    tvchat.$('.ver').css( "height", "+=1" );
});
$('#alto_menos').click(function(){

    tvchat.$('#tvhot_mensajero').css( "height", "-=1" );
    tvchat.$('.ver').css( "height", "-=1" );
});
$('#ancho_mas').click(function(){

    tvchat.$('.ver').css( "width", "+=1" );
    tvchat.$('#tvhot_mensajero').css( "width", "+=1" );
});
$('#ancho_menos').click(function(){

    tvchat.$('.ver').css( "width", "-=1" );
    tvchat.$('#tvhot_mensajero').css( "width", "-=1" );
});

//mostrar lineas punteadas para la conductora
$("#ocultar_lineas_referencia_conductora a").click(function(){

    var params = {

        accion: "ocultar",
        modulo: "lineas_referencia"
    }

    tvchat.cargarModulo( params );
});
$("#mostrar_lineas_referencia_conductora a").click(function(){

    var params = {

        accion: "mostrar",
        modulo: "lineas_referencia"
    }

    tvchat.cargarModulo( params );

});

$('#vaciar_localstorage').click(function(){

    localStorage.clear();
});

//pestanhas
$('#mostrar_bloque_tragamonedas').click(function(){
    ocultarModulos();
    $('#tragamonedas').removeClass('ocultar');
    $('#mostrar_bloque_tragamonedas').addClass('active');
});
$('#mostrar_bloque_tombola').click(function(){
    ocultarModulos();
    $('#tombola').removeClass('ocultar');
    $('#mostrar_bloque_tombola').addClass('active');
});
$('#mostrar_bloque_tragamonedas_sexy').click(function(){
    ocultarModulos();
    $('#tragamonedas_sexy').removeClass('ocultar');
    $('#mostrar_bloque_tragamonedas_sexy').addClass('active');
});
$('#mostrar_bloque_piropo1').click(function(){
    ocultarModulos();
    $('#piropo1').removeClass('ocultar');
    $('#mostrar_bloque_piropo1').addClass('active');
});
$('#mostrar_bloque_piropo2').click(function(){
    ocultarModulos();
    $('#piropo2').removeClass('ocultar');
    $('#mostrar_bloque_piropo2').addClass('active');
});
$('#mostrar_bloque_video').click(function(){
    ocultarModulos();
    $('#video').removeClass('ocultar');
    $('#mostrar_bloque_video').addClass('active');
});
$('#mostrar_bloque_tvhot').click(function(){
    ocultarModulos();
    $('#tvhot').removeClass('ocultar');
    $('#mostrar_bloque_tvhot').addClass('active');
});
$('#mostrar_bloque_tvmensajero').click(function(){
    ocultarModulos();
    $('#tvmensajero').removeClass('ocultar');
    $('#mostrar_bloque_tvmensajero').addClass('active');
});
$('#mostrar_modulo_por_defecto a').click(function(){
    cargarModuloPorDefecto();
});

$('#selectWinElements').hide();
$('#premios_tragamonedas_sexy').hide();

//setInterval( obtenerMensajes, 1000*9*60 );

//cada 2 min
obtenerMensajesPiropos();
siguiente_id_solicitar = 0;
setInterval( obtenerMensajesPiropos, 60*1000 );

//setInterval( testearConexion, 10000);

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
    $('#mostrarGanadorTragamonedas').attr('disabled', 'true');
    $('#mostrarGanadorTragamonedasSexy').attr('disabled', 'true');
    $('#ocultar_lineas_referencia_conductora').addClass('disabled');
    $('#mostrar_lineas_referencia_conductora').addClass('disabled');
    $('#mostrar_modulo_por_defecto').addClass('disabled');
    $('#cerrar_ventana_principal').addClass('disabled');
    //$('#cerrar_ventana_tvhot').addClass('disabled');
    $('#parar_marquee').addClass('disabled');
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
    $('#premios_piropos2').hide();
};

function habilitarBotones( nivel, juego ){

    if( nivel == 1 && juego == "tvchat" ){
        //deshabilitarBotones();
        $('#cargar_tragamonedas').removeAttr('disabled');
        $('#cerrar_tragamonedas').removeAttr('disabled');
        $('#cargar_tragamonedas_sexy').removeAttr('disabled');
        $('#cerrar_tragamonedas_sexy').removeAttr('disabled');
        $('#cargar_tombola').removeAttr('disabled');
        $('#cerrar_tombola').removeAttr('disabled');
        $('#cargar_piropo').removeAttr('disabled');
        $('#cerrar_piropo').removeAttr('disabled');

        $('#ocultar_lineas_referencia_conductora').removeClass('disabled');
        $('#mostrar_lineas_referencia_conductora').removeClass('disabled');
        $('#mostrar_modulo_por_defecto').removeClass('disabled');
        $('#parar_marquee').removeClass('disabled');
    }
    else if( nivel == 2 && juego == 'tragamonedas' ){
        habilitarBotones( 1, "tvchat" );
        $('#getWinElementsTragamonedas').removeAttr('disabled');
        $('#getElementsTragamonedas').removeAttr('disabled');
    }
    else if( nivel == 3 && juego == 'tragamonedas' ){
        habilitarBotones( 2, 'tragamonedas' );
        $('#premios_tragamonedas').show();
        $('#jugarTragamonedas').removeAttr('disabled');
    }
    else if( nivel == 4 && juego == 'tragamonedas' ){
        habilitarBotones( 2, 'tragamonedas' );
        $('#jugarTragamonedas').removeAttr('disabled');
    }
    else if( nivel == 5 && juego == 'tragamonedas' ){
        habilitarBotones( 2, 'tragamonedas' );
        $('#premios_tragamonedas').hide();
        $('#stopRoulette1').removeAttr('disabled');
        $('#stopRoulette2').removeAttr('disabled');
        $('#stopRoulette3').removeAttr('disabled');
        $('#mostrarGanadorTragamonedas').removeAttr('disabled');
    }
    else if( nivel == 2 && juego == "tragamonedas_sexy" ){
        habilitarBotones( 1, "tvchat" );
        $('#getWinElementsTragamonedasSexy').removeAttr('disabled');
    }
    else if( nivel == 3 && juego == "tragamonedas_sexy" ){
        habilitarBotones( 2, "tragamonedas_sexy" );
        //$('#premios_tragamonedas_sexy').show();
        $('#selectWinElements').show();
        $('#jugarTragamonedasSexy').removeAttr('disabled');
    }
    else if( nivel == 4 && juego == "tragamonedas_sexy" ){
        habilitarBotones( 2, "tragamonedas_sexy" );
        $('#stopRouletteSexy1').removeAttr('disabled');
        $('#stopRouletteSexy2').removeAttr('disabled');
        $('#stopRouletteSexy3').removeAttr('disabled');
        $('#mostrarGanadorTragamonedasSexy').removeAttr('disabled');
    }
    else if( nivel == 2 && juego == "tombola" ){
        //deshabilitarBotones();
        habilitarBotones( 1, "tvchat" );
        $('#getWinElementsTombola').removeAttr('disabled');
        $('#getElementsTombola').removeAttr('disabled');
    }
    else if( nivel == 3 && juego == "tombola" ){
        habilitarBotones( 2, "tombola" );
        $('#jugarTombola').removeAttr('disabled');
    }
    else if( nivel == 4 && juego == "tombola" ){
        habilitarBotones( 2, "tombola" );
        $('#jugarTombola').removeAttr('disabled');
    }
    else if( nivel == 5 && juego == "tombola" ){
        //deshabilitarBotones();
        //habilitarBotones( 2, "tombola" );
        $('#premios_tombola').hide();
        //$('#stopTombola').removeAttr('disabled');
    }
    else if( nivel == 2 && juego == "piropo" ){
        //deshabilitarBotones();
        //habilitarBotones( 1, null );
        $('#seleccionar_piropo').removeAttr('disabled');
    }
    else if( nivel == 3 && juego == "piropo" ){
        //habilitarBotones( 2, null );
        //$('#premios_piropos').show();
        $('#jugarPiropo').removeAttr('disabled');
    }
    else if( nivel == 4 && juego == "piropo" ){

        //deshabilitarBotones();
        //habilitarBotones( 2, "piropo" );
        $('#cerrar_piropo').removeAttr('disabled');
    }
};

function refrescarPiroposGanadores(){

    $('#WinElementsPiropo').empty();
    $.each( piropos_buffer, function( i, piropo ){

        $('#WinElementsPiropo')
            .append(
                $(document.createElement("tr"))
                    .append(
                        $(document.createElement("td"))
                            .append(
                                $(document.createElement("p"))
                                    .append( piropo.cel + ': ' + piropo.codigo )
                                    .addClass("numeros_sorteados")
                            ),
                        $(document.createElement("td"))
                            .append(
                                $(document.createElement("button"))
                                    .attr('data-id',  i )
                                    .append( 'Eliminar' )
                                    .addClass("btn btn-primary eliminar_piropo")
                            )
                    )
            );
    });
}

function cargarNumerosGanadores( respuesta ){

    /*"id_sorteo" => $id_sorteo,
     "sorteo" => $elementos_ganadores,
     "cel_ganador" => $cel_ganador,
     "juego" => "tragamonedas"*/

    if( respuesta.juego == "tragamonedas" ){

        var nuevoGanador = new ObjetoGanador( '', '', respuesta.juego, '', '' );
        sorteoTragamonedas = new Sorteo(respuesta.cel_ganador, respuesta.sorteo, null, respuesta.juego, respuesta.id_sorteo);

        if( premio_tragamonedas.premio_texto != null ){

            sorteoTragamonedas.premio = new Premio(premio_tragamonedas.id_premio, premio_tragamonedas.premio_gs, premio_tragamonedas.premio_texto);

        }

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

        $('#WinPhoneNumberTragamonedas').empty();

        $('#WinPhoneNumberTragamonedas').append(

            $(document.createElement("p"))
                .append(respuesta.cel_ganador)
                .addClass("numeros_sorteados")
        );

        tragamonedas_numeros_ganadores.push(respuesta.cel_ganador);

        nuevoGanador.cel_ganador = respuesta.cel_ganador;
        nuevoGanador.combinacion_ganadora_list = tragamonedas_elementos_ganadores;

        //buffer donde voy guardando los sorteos
        //tragamonedas_buffer.push(sorteo);

    }
    else if( respuesta.juego == "tragamonedas_sexy" ){

        var nuevoGanador = new ObjetoGanador( '', '', respuesta.juego, '', '' );
        sorteoTragamonedasSexy = new Sorteo(respuesta.cel_ganador, respuesta.sorteo, null, respuesta.juego, respuesta.id_sorteo);
        var premio_texto = '50.000 Gs en Saldo';
        var id_premio = 4;
        var premio_gs = valor_premios[id_premio];

        sorteoTragamonedasSexy.premio = new Premio(id_premio, premio_gs, premio_texto);

        $('#WinElementsTragamonedasSexy').empty();

        $('#WinElementsTragamonedasSexy').append(

            $(document.createElement("p"))
                .append(respuesta.cel_ganador)
                .addClass("numeros_sorteados")
        );

        nuevoGanador.cel_ganador = respuesta.cel_ganador;
        nuevoGanador.combinacion_ganadora_list = tragamonedas_sexy_combinacion_ganadora;
        sorteoTragamonedasSexy.codigo = new Array();

        //buffer donde voy guardando los sorteos
        //tragamonedas_sexy_buffer.push(nuevoGanador);

    }
    else if( respuesta.juego == "tombola" ){

        nuevoGanador = new ObjetoGanador( '', '', respuesta.juego, '', '' );

        sorteoTombola = new Sorteo(respuesta.cel_ganador, respuesta.sorteo, null, respuesta.juego, respuesta.id_sorteo);

        if( premio_tombola.premio_texto != null ){

            sorteoTombola.premio = new Premio(premio_tombola.id_premio, premio_tombola.premio_gs, premio_tombola.premio_texto);

        }

        $('#WinElementsTombola').empty();

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
        $('#WinPhoneNumberTombola').empty();

        $('#WinPhoneNumberTombola').append(
            $(document.createElement("p"))
                .append(respuesta.cel_ganador)
                .addClass("numeros_sorteados")
        );

        tombola_numeros_ganadores.push(respuesta.cel_ganador);
        nuevoGanador.cel_ganador = respuesta.cel_ganador;
        nuevoGanador.combinacion_ganadora_list = tombola_elementos_ganadores;

        //tombola_buffer.push(nuevoGanador);
    }
    else if( respuesta.juego == "piropo" ){

        piropos_buffer.push(respuesta);
        refrescarPiroposGanadores();
    }
    else if( respuesta.juego == "piropo2" ){

        sorteoPiropoLlamadas = new Sorteo(respuesta.cel_ganador, respuesta.sorteo, null, respuesta.juego, respuesta.id_sorteo);

        $('#WinElementsPiropo2').empty();

        $('#WinElementsPiropo2')
            .append(
                $(document.createElement("p"))
            );

        var WinElementsPiropo = $('#WinElementsPiropo2 p');
        WinElementsPiropo
            .append( respuesta.cel_ganador )
            .addClass("numeros_sorteados")

        //piropos2_buffer.push(respuesta);
    }
};

function testearConexion(){

    $.get("/tvchat/testear-conexion", { }, procesarRespuesta, "json");
}

function procesarRespuesta( respuesta ){

    alert('ok');
    if( respuesta == 0 )
        $('#conexion').addClass('conectado')
    else
        $('#conexion').addClass('desconectado')
}

function obtenerMensajes(){

    console.log("solicito mensajes nuevos");
    $.get("/tvchat/obtener-mensajes", {}, cargarOpcionesMensajes, "json");
    return;
};

function cargarOpcionesMensajes( piropos_opciones ){

    var opciones_mensajes = $('#mensajes');
    $.each( piropos_opciones, function( i, mensaje ) {

        if( mensaje.ya_sorteado == false ){

            opciones_mensajes.append(

                $(document.createElement("tr"))
                    .append(
                        $(document.createElement("td"))
                            .append(
                                mensaje.cel
                            ),
                        $(document.createElement("td"))
                            .append(
                                mensaje.mensaje
                            ),
                        $(document.createElement("td"))
                            .append(
                                $(document.createElement("button"))
                                    .addClass("seleccionar btn btn-primary")
                                    .attr('data-id', mensaje.id_mensaje)
                                    .attr('data-cel', mensaje.cel)
                                    .attr('data-mensaje', mensaje.mensaje)
                                    .attr( 'id', i )
                                    .append("Seleccionar")
                            )
                    )
            )
        }else{

            opciones_mensajes.append(
                $(document.createElement("tr"))
                    .append(
                        $(document.createElement("td"))
                            .append(
                                mensaje.cel
                            ),
                        $(document.createElement("td"))
                            .append(
                                mensaje.mensaje
                            ),
                        $(document.createElement("td"))
                            .append(
                                'Ya sorteado'
                            )
                    )
            )
        }
    });
};

function filtrosMensajes(  marquee ){

    var opciones_mensajes = $('#marquee');
    $.each( marquee, function( i, mensaje ) {

        opciones_mensajes.append(

            $(document.createElement("tr"))
                .append(
                    $(document.createElement("td"))
                        .append(
                            mensaje.cel
                        ),
                    $(document.createElement("td"))
                        .append(
                            mensaje.mensaje
                        ),
                    $(document.createElement("td"))
                        .append(
                            $(document.createElement("button"))
                                .addClass("seleccionar_marquee btn btn-primary")
                                .attr('data-id',  i )
                                .attr('data-cel', mensaje.cel)
                                .attr('data-mensaje', mensaje.mensaje)
                                .append("Seleccionar")
                        )
                )
        )
    });
};

function obtenerMensajesPiropos(){

    console.log('piropo');
    if (flujo_piropo == true){
        $.get("/tvchat/obtener-mensajes", { solicitud: 'marquee', id_mensaje: siguiente_id_solicitar }, cargarMensajes, "json");
        return;
    }else{
        return;
    }
};

function obtenerMensajesMarquee(){

    flujo_piropo = false;
    console.log('marquee');

    $.get("/tvchat/obtener-mensajes", { solicitud: 'marquee', id_mensaje: siguiente_id_solicitar }, cargarMensajes1, "json").fail(
        function() {
            alert( "Se perdio la conexion!!!" );
        });

    return;

};

function cargarMensajes( respuesta ){

    if( respuesta.mensajes_operador != null ){

        mensajes = $.extend( mensajes, respuesta.mensajes_operador );
    }

    siguiente_id_solicitar = respuesta.siguiente_id_solicitar;
};

function cargarMensajes1( respuesta ){

    if( respuesta.mensajes_operador != null ){

        $.each(respuesta.mensajes_marquee, function(i, objetoGanador) {

            tvchat.textarray_buffer.push(objetoGanador);
            tvchat.sms_nuevos++;
        })
    }

    siguiente_id_solicitar = respuesta.siguiente_id_solicitar;
};

function obtenerMensajesNuevosMensajero(){

    console.log("obtener nuevos mensajes");
    console.log(mensajero_buffer);

    return mensajero_buffer;
};

function actualizarTotalGuaranies( premio ){

    total_guaranies += Number( premio );
    $('#total_guaranies').empty();
    $('#total_guaranies').append(total_guaranies);
    total_sorteos++ ;
    $('#total_sorteos').empty();
    $('#total_sorteos').append(total_sorteos);
};

function cargarModuloPorDefecto(){

    console.log('mirar');
    var params = {

        accion: "mostrar",
        modulo: "fotos"
    };

    tvchat.cargarModulo( params );
};

function guardarSorteo( parametros ){

    $.get("/tvchat/guardar-sorteo/", { id_sorteo : parametros.id_sorteo, id_premio : parametros.id_premio }, {}, "json");
    return;
};

function obtenerSorteoPiropoMensajes( parametros ){

    $.get("/tvchat/obtener-sorteo-piropo-mensajes/", { id_juego : parametros.id_juego, cel : parametros.cel, id_mensaje : parametros.id_mensaje }, crearSorteoPiropo, "json");
    return;

};

ocultarModulos();

function removerCss(){

    $('#mostrar_bloque_tragamonedas a').css( 'background-color', '' );
    $('#mostrar_bloque_tombola a').css( 'background-color', '' );
    $('#mostrar_bloque_tragamonedas_sexy a').css( 'background-color', '' );
    $('#mostrar_bloque_piropo1 a').css( 'background-color', '' );
    $('#mostrar_bloque_piropo2 a').css( 'background-color', '' );
    $('#mostrar_bloque_video a').css( 'background-color', '' );
    $('#mostrar_bloque_tvhot a').css( 'background-color', '' );
    $('#mostrar_bloque_tvmensajero a').css( 'background-color', '' );
};

function ocultarModulos(){

    $('#tragamonedas').addClass('ocultar');
    $('#tombola').addClass('ocultar');
    $('#piropo1').addClass('ocultar');
    $('#piropo2').addClass('ocultar');
    $('#tragamonedas_sexy').addClass('ocultar');
    $('#video').addClass('ocultar');
    $('#tvhot').addClass('ocultar');
    $('#tvmensajero').addClass('ocultar');

    $('#mostrar_bloque_tragamonedas').removeClass('active');
    $('#mostrar_bloque_tombola').removeClass('active');
    $('#mostrar_bloque_tragamonedas_sexy').removeClass('active');
    $('#mostrar_bloque_piropo1').removeClass('active');
    $('#mostrar_bloque_piropo2').removeClass('active');
    $('#mostrar_bloque_video').removeClass('active');
    $('#mostrar_bloque_tvhot').removeClass('active');
    $('#mostrar_bloque_tvmensajero').removeClass('active');
};

function crearSorteoPiropo( respuesta ){

    sorteoPiropoMensajes.id_sorteo = respuesta.id_sorteo;
}

$('#mostrar_bloque_piropo1').trigger('click');

//});

$(window).bind( 'beforeunload', function(){

    //save info somewhere
    if( tvchat != null )
        tvchat.close();
    if( tvmensajero != null )
        tvmensajero.close();

    //return 'Esta seguro?';
    return;
});

