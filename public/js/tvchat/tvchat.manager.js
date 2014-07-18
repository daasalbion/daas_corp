//var tvchat = null;
var tragamonedas_elementos_ganadores = [];
var tragamonedas_numeros_ganadores = [];
var tombola_elementos_ganadores = [];
var tombola_numeros_ganadores = [];
var nuevoGanador = null;

function ObjetoGanador(combinacion_ganadora, cel_ganador, nombre_juego, combinacion_ganadora_list) {
    this.combinacion_ganadora = combinacion_ganadora;
    this.cel_ganador = cel_ganador;
    this.nombre_juego = nombre_juego;
    this.combinacion_ganadora_list = combinacion_ganadora_list;
}

var tragamonedas = JSON.parse(localStorage.getItem('tragamonedas'));
var tombola = JSON.parse(localStorage.getItem('tombola'));
var mensajes = JSON.parse(localStorage.getItem('mensajes'));

if (tragamonedas == null)
    var tragamonedas = [];
if (tombola == null)
    var tombola = [];
if (mensajes == null)
    var mensajes = [];

var piropos =[];


$(document).ready(function(){

    //abrir ventana principal
    $('#abrir_ventana_principal').click(function(){

        $('#abrir_ventana_principal').attr('disabled', 'true');
        //habilitarBotones(1,null);
        tvchat = window.open("/tvchat/tv",
            "_blank", "width=800, height=600, menubar=no, toolbar=no, location=no, directories=no, status=no, scrollbars=auto, fullscreen=yes");

    })
    //cerrar ventana principal
    $('#cerrar_ventana_principal').click(function(){
        $('#abrir_ventana_principal').removeAttr('disabled');
        tvchat.close();
        //deshabilitarBotones();
    })

    //abrir ventanas demos
    $('#abrir_ventana2').click(function(){

        ventana1 = window.open("/tvchat/demo2",
            "_blank", "width=800, height=600, menubar=no, toolbar=no, location=no, directories=no, status=no, scrollbars=auto, fullscreen=yes");

    })
    $('#abrir_ventana3').click(function(){

        ventana2 = window.open("/tvchat/demo1",
            "_blank", "width=800, height=600, menubar=no, toolbar=no, location=no, directories=no, status=no, scrollbars=auto, fullscreen=yes");

    })
    $('#abrir_ventana4').click(function(){

        ventana4= window.open("/tvchat/demo-galgos",
            "_blank", "width=800, height=600, menubar=no, toolbar=no, location=no, directories=no, status=no, scrollbars=auto, fullscreen=yes");

    })
    $('#abrir_ventana5').click(function(){

        ventana4= window.open("/tvchat/demo-dados",
            "_blank", "width=800, height=600, menubar=no, toolbar=no, location=no, directories=no, status=no, scrollbars=auto, fullscreen=yes");

    })
    $('#abrir_ventana6').click(function(){

        ventana4= window.open("/tvchat/demo",
            "_blank", "width=800, height=600, menubar=no, toolbar=no, location=no, directories=no, status=no, scrollbars=auto, fullscreen=yes");

    })
    $('#abrir_ventana7').click(function(){

        ventana4= window.open("/tvchat/demo-slot",
            "_blank", "width=800, height=600, menubar=no, toolbar=no, location=no, directories=no, status=no, scrollbars=auto, fullscreen=yes");

    })
    //cerrar ventanas demos
    $('#cerrar_ventana2').click(function(){
        ventana1.close();
    })
    $('#cerrar_ventana3').click(function(){
        ventana2.close();
    })

    //cargar juegos
    $('#cargar_tragamonedas').click(function(){

        //habilitarBotones( 3, 'tragamonedas' );
        //valores por defecto
        var params = {
            "juego": "tragamonedas"
        };

        tvchat.cargarJuego(params);
    })
    $('#cargar_tombola').click(function(){

        //habilitarBotones( 3, 'tombola' );
        //valores por defecto
        var params = {
            "juego": "tombola",
            "valores_ganadores": tombola_elementos_ganadores
        };

        var sorteo = $('#WinElementsTombola p');
        sorteo.remove();
        var tombola_historial = $('#historial_tombola');
        $('#historial_tombola p').remove();

        $.each(tombola, function(i, objetoGanador) {

            tombola_historial.append(
                $(document.createElement("p"))
                    .append(objetoGanador.combinacion_ganadora + ' - ' + objetoGanador.cel_ganador)
                    .addClass("numeros_sorteados")
            )
            tombola_numeros_ganadores.pop();
        });

        tvchat.cargarJuego(params);
    })
    $('#cargar_tvchat').click(function(){

        //valores por defecto
        var params = {
            "juego": "tvchat"
        };

        tvchat.cargarJuego(params);
    })
    $('#cargar_piropo').click(function(){

        //valores por defecto
        var params = {
            "juego": "piropo"
        };

        tvchat.cargarJuego(params);
    })

    //descargar juegos
    $('#cerrar_tragamonedas').click(function(){

        //habilitarBotones( 1, null );
        //valores por defecto
        var params = {
            "juego": "tragamonedas"
        };

        tvchat.descargarJuego(params);
    })
    $('#cerrar_tombola').click(function(){

        //habilitarBotones( 1, null );
        //valores por defecto
        var params = {
            "juego": "tombola"
        };

        tvchat.descargarJuego(params);
    })
    $('#cerrar_tvchat').click(function(){

        //valores por defecto
        var params = {
            "juego": "tvchat"
        };

        tvchat.descargarJuego(params);
    })
    $('#cerrar_piropo').click(function(){

        //valores por defecto
        var params = {
            "juego": "piropo"
        };

        tvchat.descargarJuego(params);
    })

    //jugar juegos
    $('#jugarTragamonedas').click(function(){

        //habilitarBotones( 4, 'tragamonedas' );
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
        console.log("nombre_juego: " + nuevoGanador.nombre_juego);

        //se obtiene efectivamente un nuevo ganador
        tragamonedas.push(nuevoGanador);

        $.each(tragamonedas, function(i, objetoGanador) {

            tragamonedas_historial.append(
                $(document.createElement("p"))
                    .append(objetoGanador.combinacion_ganadora + ' - ' + objetoGanador.cel_ganador)
                    .addClass("numeros_sorteados")
            )
        });

        tvchat.jugarJuego(params);
    });
    $('#jugarTombola').click(function(){

        //habilitarBotones( 4, 'tombola' );
        var params = {

            "jugar": "tombola"
        }
        tvchat.jugarJuego(params);
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

    //obtener elementos sorteados
    $('#getWinElementsTragamonedas').click(function(){

        //habilitarBotones(2,'tragamonedas');
        console.log("getWinElementsTragamonedas");
        $.get("/tvchat/get-win-elements-tragamonedas", {}, cargarNumerosGanadores, "json");
        return;
    })
    $('#getWinElementsTombola').click(function(){

        //habilitarBotones(2,'tombola');
        console.log("getWinElementsTombola");
        $.get("/tvchat/get-win-elements-tombola", {}, cargarNumerosGanadores, "json");
        return;
    })

    setInterval( obtenerMensajes, 1000*9*60 );

    obtenerMensajes();

    //deshabilitarBotones();

    //funciones
    function deshabilitarBotones(){
        //tragamonedas
        $('#getWinElementsTragamonedas').attr('disabled', 'true');
        $('#cargar_tragamonedas').attr('disabled', 'true');
        $('#cerrar_tragamonedas').attr('disabled', 'true');
        $('#jugarTragamonedas').attr('disabled', 'true');

        //tombola
        $('#getWinElementsTombola').attr('disabled', 'true');
        $('#cargar_tombola').attr('disabled', 'true');
        $('#cerrar_tombola').attr('disabled', 'true');
        $('#jugarTombola').attr('disabled', 'true');

        //piropo
        $('#getWinElementsPiropos').attr('disabled', 'true');
        $('#cargar_piropo').attr('disabled', 'true');
        $('#cerrar_piropo').attr('disabled', 'true');
        $('#jugarPiropo').attr('disabled', 'true');
    }

    function habilitarBotones( nivel, juego ){

        if( nivel == 1 && juego == null ){

            deshabilitarBotones();
            $('#getWinElementsTragamonedas').removeAttr('disabled');
            $('#getWinElementsTombola').removeAttr('disabled');
            $('#getWinElementsPiropos').removeAttr('disabled');
        }if ( nivel == 2 && juego == "tragamonedas" ){

            deshabilitarBotones();
            $('#cargar_tragamonedas').removeAttr('disabled');
        }if( nivel == 3 && juego == "tragamonedas" ){

            deshabilitarBotones();
            $('#jugarTragamonedas').removeAttr('disabled');
        }if( nivel == 4 && juego == "tragamonedas" ){

            deshabilitarBotones();
            $('#cerrar_tragamonedas').removeAttr('disabled');
        }if ( nivel == 2 && juego == "tombola" ){

            deshabilitarBotones();
            $('#cargar_tombola').removeAttr('disabled');
        }if( nivel == 3 && juego == "tombola" ){

            deshabilitarBotones();
            $('#jugarTombola').removeAttr('disabled');
        }if( nivel == 4 && juego == "tombola" ){

            deshabilitarBotones();
            $('#cerrar_tombola').removeAttr('disabled');
        }

    }

    function cargarNumerosGanadores( respuesta ){

        if( respuesta.juego == "tragamonedas" ){

            nuevoGanador = new ObjetoGanador( '', '', respuesta.juego, '' );

            if( $('#WinElementsTragamonedas p').length > 0 )
                $('#WinElementsTragamonedas p').remove();

            $('#WinElementsTragamonedas')
                .append(
                    $(document.createElement("p"))
                );

            var WinElementsTragamonedas = $('#WinElementsTragamonedas p');
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

        }
        else if( respuesta.juego == "tombola" ){

            nuevoGanador = new ObjetoGanador( '', '', respuesta.juego, '' );

            console.log("tombola");
            var WinElementsTombola = $('#WinElementsTombola p');
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
            tombola.push(nuevoGanador);
            localStorage.setItem('tombola', JSON.stringify(tombola));
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
        }
    }

    function obtenerMensajes(){

        console.log("solicito mensajes nuevos");
        $.get("/tvchat/obtener-mensajes", {}, cargarOpcionesMensajes, "json");
        return;
    }

    function cargarOpcionesMensajes( respuesta ){

        var opciones_mensajes = $('#mensajes');
        $.each( respuesta.mensajes, function( i, mensaje ) {

            opciones_mensajes.append(

                $(document.createElement("p"))
                    .append(mensaje)
                    .append(
                        $(document.createElement("button"))
                            .addClass("seleccionar btn btn-primary")
                            .attr('data-id-mensaje', i)
                            .attr('data-mensaje', mensaje)
                            .attr( 'id', i )
                            .append("Seleccionar")
                    )
            )

            mensajes.push(mensaje);
            prueba.push(mensaje);
            //localStorage.setItem('mensajes', JSON.stringify(mensajes));
        });

        console.log("mirar: " + prueba.length);
    }

    $('.seleccionar').click(function(){

        console.log("mierda");
        mensaje_seleccionado = $(this).data('mensaje');
        cel_ganador = '0982313289';

        //se aprovecha el campo cadena de combinacion_ganadora
        nuevoGanador = new ObjetoGanador( mensaje_seleccionado, cel_ganador, 'piropo', '' );

        $('#opciones_mensajes').modal('hide');

        cargarNumerosGanadores(nuevoGanador);

        //localStorage.setItem("mensaje_seleccionado", mensaje_seleccionado);
    })
    $('#vaciar_localstorage').click(function(){

        localStorage.clear();
    })
})

$(window).bind('beforeunload',function(){

    //save info somewhere
    if( tvchat != null )
        tvchat.close();
    //return 'Esta seguro?';
    return;
});