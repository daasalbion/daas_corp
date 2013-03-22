var intervalo = null;

//1 -> cesta regalos
//2 -> regalo movil
var nro_banner = 1;

function iniciarCambioBanner() {
    intervalo = setInterval("cambiarBanner()", 4000);
}

function cambiarBanner() {

    $('#bloque_banner').attr('class', '');
    switch(nro_banner) {
        case 1:
            nro_banner = 2;
            $('#bloque_banner').addClass('banner_regalo_movil');
            break;
        case 2:
            nro_banner = 1;
            $('#bloque_banner').addClass('banner_cesta_regalos');
            break;
    }
}


var myListener = new Object();
var reproduciendo = false;
var activarPrevNext = false;

var tonos_path = new Array("TONO_01.mp3", "TONO_02.mp3", "TONO_04.mp3", "TONO_05.mp3", "TONO_06.mp3", "TONO_07.mp3", "TONO_09.mp3", "TONO_10.mp3", "TONO_11.mp3", "TONO_12.mp3");
var tonos_numero = new Array("001", "002", "003", "004", "005", "006", "007", "008", "009", "010");
var tonos_tipo = new Array("LLAMADA", "MENSAJE", "LLAMADA", "MENSAJE", "LLAMADA", "MENSAJE", "LLAMADA", "MENSAJE", "LLAMADA", "MENSAJE");
var indice_tono = 0;
var num_tonos = 10;

var mascotas_path = new Array("MASCOTA_01.png", "MASCOTA_02.png", "MASCOTA_03.png", "MASCOTA_04.png", "MASCOTA_05.png");
var mascotas_numero = new Array("001", "002", "003", "004", "005");
var mascotas_tipo = new Array("FONDO", "FONDO", "FONDO", "FONDO", "FONDO");
var mascotas_precargadas = new Array(5);
var mascotas_cargadas = new Array(false, false, false, false, false);
var indice_mascota = 0;
var num_mascotas = 5;

var siglas_path = new Array("SIGLAS-14.jpg", "SIGLAS-17.jpg", "SIGLAS-22.jpg", "SIGLAS-28.jpg", "SIGLAS-46.jpg");
var siglas_precargadas = new Array(5);
var siglas_cargadas = new Array(false, false, false, false, false);
var indice_siglas = 0;
var num_siglas = 5;

var preloader;

var enReproduccion = false;

function cargarSiglasSiguiente() {
    indice_siglas++;
    if(indice_siglas >= num_siglas) {
        indice_siglas = 0;

        $("#bloque_portafolio").hide();
        $(".nivel_categoria").hide();
        $(".nivel_sub_categoria").hide();
        $("#bloque_categoria_apps_alias_siglas").show();

        $("#bloque_flechas_siglas .flecha_izquierda").hide();
        $("#bloque_flechas_siglas .flecha_derecha").hide();

        $("#form_siglas_iniciales").focus();
        $("#form_siglas_iniciales").select();

    } else {
        cargarSiglas(indice_siglas);
    }

}

function cargarSiglasAnterior() {
    indice_siglas--;
    if(indice_siglas < 0) {
        indice_siglas = num_siglas - 1;

        $("#bloque_portafolio").hide();
        $(".nivel_categoria").hide();
        $(".nivel_sub_categoria").hide();
        $("#bloque_categoria_apps_alias_siglas").show();

        $("#form_siglas_iniciales").focus();
        $("#form_siglas_iniciales").select();

    } else {
        cargarSiglas(indice_siglas);
    }

}

function cargarMascotaSiguiente() {
    indice_mascota++;
    if(indice_mascota >= num_mascotas) {
        indice_mascota = 0;
    }
    cargarMascota(indice_mascota);
}

function cargarMascotaAnterior() {
    indice_mascota--;
    if(indice_mascota < 0) {
        indice_mascota = num_mascotas - 1;
    }
    cargarMascota(indice_mascota);
}

function cargarTonoSiguiente() {
    indice_tono++;
    if(indice_tono >= num_tonos) {
        indice_tono = 0;
    }
    cargarTono(indice_tono);
}

function cargarTonoAnterior() {
    indice_tono--;
    if(indice_tono < 0) {
        indice_tono = num_tonos - 1;
    }
    cargarTono(indice_tono);
}

function cargarTono(indice) {

    var path_tono = "/swf/" + tonos_path[indice];

    myListener.position = 0;
    getFlashObject().SetVariable("method:setUrl", path_tono);
    //getFlashObject().SetVariable("method:play", "");
    //getFlashObject().SetVariable("enabled", "true");

    $("#telefono_titulo_tono span").html(tonos_numero[indice]);
    if(tonos_tipo[indice] == "LLAMADA") {
        $("#telefono_tipo_tono span").html("llamadas");
    } else {
        $("#telefono_tipo_tono span").html("mensajes");
    }

    if(indice+1 >= num_tonos) {//estamos en el ultimo contenido
        //Ultimo contenido
        $("#bloque_flechas_tonos .flecha_izquierda").show();
        $("#bloque_flechas_tonos .flecha_derecha").hide();
    } else if(indice-1 < 0) {//estamos en el primer contenido
        //Primer contenido
        $("#bloque_flechas_tonos .flecha_izquierda").hide();
        $("#bloque_flechas_tonos .flecha_derecha").show();
    } else {
        $("#bloque_flechas_tonos .flecha_izquierda").show();
        $("#bloque_flechas_tonos .flecha_derecha").show();
    }
}

function cargarMascota(indice) {

    var path_imagen = "/img/" + mascotas_path[indice];
    //$('#previewMascota').css("background-image", "url('"+path_imagen+"')");

    if(mascotas_cargadas[indice]) {
        $('#previewMascota').html( mascotas_precargadas[indice] );
    } else {
        cargar('MASCOTA', indice);
    }

    $("#telefono_titulo_mascota span").html(mascotas_numero[indice]);
    if(indice+1 >= num_mascotas) {//estamos en el ultimo contenido
        //Ultimo contenido
        $("#bloque_flechas_mascotas .flecha_izquierda").show();
        $("#bloque_flechas_mascotas .flecha_derecha").hide();
    } else if(indice-1 < 0) {//estamos en el primer contenido
        //Primer contenido
        $("#bloque_flechas_mascotas .flecha_izquierda").hide();
        $("#bloque_flechas_mascotas .flecha_derecha").show();
    } else {
        $("#bloque_flechas_mascotas .flecha_izquierda").show();
        $("#bloque_flechas_mascotas .flecha_derecha").show();
    }
}

function cargarSiglas(indice) {

    var path_imagen = "/img/" + siglas_path[indice];

    /*if(siglas_cargadas[indice]) {
        //$('#previewSiglas').html( siglas_precargadas[indice] );
    } else {
        precargar('SIGLAS', indice);
    }*/
    /*var iniciales = $("#form_siglas_iniciales").val();
    var cantidad_letras = iniciales.length;
    var atributo_top = "100px";
    var atributo_fontSize = "1px";
    if(cantidad_letras == 4) {
        atributo_top = "100px";
        atributo_fontSize = "40px";
    } else if(cantidad_letras == 3) {
        atributo_top = "96px";
        atributo_fontSize = "45px";
    } else if(cantidad_letras == 2) {
        atributo_top = "90px";
        atributo_fontSize = "65px";
    } else if(cantidad_letras == 1) {
        atributo_top = "80px";
        atributo_fontSize = "100px";
    }*/

    var atributo_top = "120px";
    var atributo_fontSize = "10px";
    $('#previewSiglas').html("<span>"+ "Generando fondo..." +"</span>");
    //$('#previewSiglas').html(preloader);
    $('#previewSiglas span').css("top", atributo_top).css("font-size", atributo_fontSize);

    //$('#previewSiglas').css("background-image", "url('"+ path_imagen +"')").html("<span>"+ iniciales +"</span>");
    //$('#previewSiglas span').css("top", atributo_top).css("font-size", atributo_fontSize);

    /*$.get("http://www.entermovil.com.py/index/siglas/indice/0/iniciales/FF", function(data) {
        console.log("respuesta:[" + data + "]");
    }, 'txt');*/

    var iniciales = $("#form_siglas_iniciales").val();

    var url_siglas = "http://www.entermovil.com.py/index/siglas/indice/"+indice+"/iniciales/" + iniciales;
    var imagendemo = $('<img />').attr('src', url_siglas).load(function(){
        $('#previewSiglas').html(imagendemo);
    });

    //pruebas
    //path_imagen = "http://www.sound.com.py/fo/iniciales/0981999999_FO_SIGLAS-17.jpg";
    //$('#previewSiglas').css("background-image", "url('http://www.sound.com.py/fo/iniciales/demosiglas.php')");


    if(indice+1 >= num_siglas) {//estamos en el ultimo contenido
        //Ultimo contenido
        $("#bloque_flechas_siglas .flecha_izquierda").show();
        $("#bloque_flechas_siglas .flecha_derecha").show();
    } else if(indice-1 < 0) {//estamos en el primer contenido
        //Primer contenido
        $("#bloque_flechas_siglas .flecha_izquierda").hide();
        $("#bloque_flechas_siglas .flecha_derecha").show();
    } else {
        $("#bloque_flechas_siglas .flecha_izquierda").show();
        $("#bloque_flechas_siglas .flecha_derecha").show();
    }
}

/**
 * Initialisation
 */
myListener.onInit = function()
{
    this.position = 0;
};


/**
 * Update
 */
myListener.onUpdate = function()
{
    var posicion = parseInt(this.position);
    var duracion = parseInt(this.duration);
    //console.log("duracion:[" + duracion + "] posicion:[" + posicion + "] reproduciendo:[" + (reproduciendo ? "SI" :"NO") + "] cargado:[" + this.bytesPercent + "]");

    reproduciendo = (this.isPlaying == "true");

    if(!reproduciendo) {
        $("#telefono_player_play_pause").css('background-image', "url('../img/boton-play.png')");
    }
};

function getFlashObject()
{
    return document.getElementById("myFlash");
}

function play_pause() {

    if(reproduciendo) {//pause

        $("#telefono_player_play_pause").css('background-image', "url('../img/boton-play.png')");
        reproduciendo = false;
        getFlashObject().SetVariable("method:pause", "");

    } else {//play

        $("#telefono_player_play_pause").css('background-image', "url('../img/boton-pause.png')");
        if(myListener.position == 0) {
            cargarTono(indice_tono);
        }
        getFlashObject().SetVariable("method:play", "");
        getFlashObject().SetVariable("enabled", "true");
        reproduciendo = true;
    }
}

function stop()
{
    reproduciendo = false;
    getFlashObject().SetVariable("method:stop", "");
    $("#telefono_player_play_pause").css('background-image', "../img/boton-play.png");
}

function ingresar(alias) {

    if(alias == 'TONO') {

        $("#bloque_categoria_musica_alias_tono").hide();
        $("#bloque_categoria_musica_alias_tono_player").show();
        activarPrevNext = true;

        indice_tono = 0;
        cargarTono(0);

    } else if(alias == 'MASCOTA') {

        $("#bloque_categoria_imagenes_alias_mascota").hide();
        $("#bloque_categoria_imagenes_alias_mascota_player").show();
        activarPrevNext = true;

        indice_mascota = 0;
        cargarMascota(0);

    } else if(alias == 'SIGLAS') {

        //alert("INGRESAR SIGLAS");

        $("#bloque_categoria_apps_alias_siglas").hide();
        $("#bloque_categoria_apps_alias_siglas_player").show();
        activarPrevNext = true;

        indice_siglas = 0;
        cargarSiglas(0);
    }
}

function anterior(contenido) {

    if(contenido == 'TONO') {
        if(activarPrevNext) {
            stop();
            cargarTonoAnterior();
        }
    } else if(contenido == 'MASCOTA') {
        if(activarPrevNext) {
            cargarMascotaAnterior();
        }
    } else if(contenido == 'SIGLAS') {
        if(activarPrevNext) {
            cargarSiglasAnterior();
        }
    }


}

function siguiente(contenido) {

    if(contenido == 'TONO') {
        if(activarPrevNext) {
            stop();
            cargarTonoSiguiente();
        }
    } else if(contenido == 'MASCOTA') {
        if(activarPrevNext) {
            cargarMascotaSiguiente();
        }
    } else if(contenido == 'SIGLAS') {
        if(activarPrevNext) {
            cargarSiglasSiguiente();
        }
    }

}

function cargar(alias, indice) {

    if(alias == 'MASCOTA') {
        mascotas_precargadas[indice] = $('<img />').attr('src', "/img/" + mascotas_path[indice]).load(function(){
            mascotas_cargadas[indice] = true;
            $('#previewMascota').html(mascotas_precargadas[indice]);
        });
    } else if(alias == 'SIGLAS') {
        siglas_precargadas[indice] = $('<img />').attr('src', "/img/" + siglas_path[indice]).load(function(){
            siglas_cargadas[indice] = true;
            $('#previewSiglas').html(siglas_precargadas[indice]);
        });
    }
}

function precargar(alias, indice) {

    if(alias == 'MASCOTA') {
        mascotas_precargadas[indice] = $('<img />').attr('src', "/img/" + mascotas_path[indice]).load(function(){
            mascotas_cargadas[indice] = true;
        });
    } else if(alias == 'SIGLAS') {
        siglas_precargadas[indice] = $('<img />').attr('src', "/img/" + siglas_path[indice]).load(function(){
            siglas_cargadas[indice] = true;
        });
    }
}

function precargarRestante(alias) {
    if(alias == 'MASCOTA') {
        $(mascotas_path).each(function(indice, valor) {
            if(!mascotas_cargadas[indice]) {
                precargar(alias, indice);
            }
        });
    } else if(alias == 'SIGLAS') {
        $(siglas_path).each(function(indice, valor) {
            if(!siglas_cargadas[indice]) {
                precargar(alias, indice);
            }
        });
    }
}

$(document).ready(function(){


    $('<img />').attr('src', '../img/banner_cesta_regalos.png').load(function(){
        nro_banner = 1;
        $('#bloque_banner').addClass('banner_cesta_regalos');
    });

    $('<img />').attr('src', '../img/banner_regalo_movil.png').load(function(){
        iniciarCambioBanner();
    });

    $("#categoria_tonos").change(function(data) {

        var valor = $(this).val();
        if(valor == 'ALIAS_TONO') {

            $("#categoria_fotos").prop('selectedIndex', 0);
            $("#categoria_apps").prop('selectedIndex', 0);

            $("#bloque_portafolio").hide();
            $(".nivel_categoria").hide();
            $(".nivel_sub_categoria").hide();
            //$("#bloque_categoria_musica_alias_tono_player").hide();
            $("#bloque_categoria_musica_alias_tono").show();
            activarPrevNext = false;

        } else if(valor == 'CATEGORIA_TONOS') {

            //$("#bloque_categoria_musica_alias_tono").hide();
            //$("#bloque_categoria_musica_alias_tono_player").hide();
            $(".nivel_categoria").hide();
            $(".nivel_sub_categoria").hide();
            $("#bloque_portafolio").show();
            activarPrevNext = false;

        }

    });

    $("#categoria_fotos").change(function(data) {

        var valor = $(this).val();
        if(valor == 'ALIAS_MASCOTA') {

            $("#categoria_tonos").prop('selectedIndex', 0);
            $("#categoria_apps").prop('selectedIndex', 0);

            $("#bloque_portafolio").hide();
            $(".nivel_categoria").hide();
            $(".nivel_sub_categoria").hide();
            $("#bloque_categoria_imagenes_alias_mascota").show();

            activarPrevNext = false;

            precargarRestante('MASCOTA');

        } else if(valor == 'CATEGORIA_FOTOS') {

            $(".nivel_categoria").hide();
            $(".nivel_sub_categoria").hide();
            $("#bloque_portafolio").show();
            activarPrevNext = false;

        }

    });

    $("#categoria_apps").change(function(data) {

        var valor = $(this).val();
        if(valor == 'ALIAS_SIGLAS') {

            $("#categoria_tonos").prop('selectedIndex', 0);
            $("#categoria_fotos").prop('selectedIndex', 0);

            $("#bloque_portafolio").hide();
            $(".nivel_categoria").hide();
            $(".nivel_sub_categoria").hide();
            $("#bloque_categoria_apps_alias_siglas").show();

            $("#form_siglas_iniciales").focus();
            $("#form_siglas_iniciales").select();

            activarPrevNext = false;

            //precargarRestante('SIGLAS');

        } else if(valor == 'CATEGORIA_APPS') {

            $(".nivel_categoria").hide();
            $(".nivel_sub_categoria").hide();
            $("#bloque_portafolio").show();
            activarPrevNext = false;

        }

    });

    $("#form_siglas").submit(function() {

        var iniciales = $("#form_siglas_iniciales").val();
        if(iniciales.length > 0) {
            ingresar('SIGLAS');
        } else {
            alert("Cargar Iniciales");
            $("#form_siglas_iniciales").focus();
            $("#form_siglas_iniciales").select();
        }

        return false;
    });


    //Cargamos imagen de inicio en el telefono
    //$("#bloque_pantalla").html($('<div />').attr('id', 'bloque_portafolio'));
    //$("#bloque_categoria_musica_alias_tono").hide();
    //$("#bloque_categoria_musica_alias_tono_player").hide();

    $(".nivel_categoria").hide();
    $(".nivel_sub_categoria").hide();


    //solo para pruebas local
    /*$("#bloque_portafolio").hide();
    $("#bloque_categoria_musica_alias_tono_player").show();*/

    //$("#telefono_player_pause").hide();

    //precargar('MASCOTA', 0);
    //precargar('SIGLAS', 0);

    preloader = $('<img />').attr('src', "/img/preloader.gif");

});

