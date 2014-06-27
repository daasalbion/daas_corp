//variables globales
var mensajes = [
    "Saludo a todos los que me conocen",
    "no anda tu equipo",
    "que hermosa conductora, si me das tu numero"
];
var marquesina;
var siguiente_id_solicitar = 10;

function obtenerMensajesNuevosTvchat(){

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

function cargarMensajes(respuesta){
    $("#mensajes_nuevos_obtenidos").html("Mensajes Nuevos: " + respuesta.mensajes_marquee);
    mensajes.push(respuesta.mensajes_marquee);
    siguiente_id_solicitar = respuesta.siguiente_id_solicitar;
    console.log(mensajes);
}

function obtenerMensajesBD(){

    console.log("llamada ajax");
    $.get("http://www.entermovil.com.py.localserver/tvchat/obtener-mensajes", {solicitud: true, id_mensaje:siguiente_id_solicitar}, cargarMensajes, "json");
    return;
}

//modulo de obtencion de mensajes
setInterval(obtenerMensajesBD(), 5000);

$(document).ready(function(){

    $("#cargar_mensajes_nuevos").click(function(){
        obtenerMensajesBD();
    })

    $('#cargar').click(function(){
        var nuevo_mensaje = $('#cargar_normal').val();
        mensajes.push(nuevo_mensaje);
        console.log(mensajes);
    })
});