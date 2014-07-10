function cargarJuego(params){

    console.log("juego: " + params["juego"]);
    var juego = $('#game_wrapper');

    if(params["juego"] == "tombola"){

        juego.append(
            //cargo los elementos
            $(document.createElement("div"))
                .attr('id', 'tombola_wheel_wrapper')
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

        wheel.init();

        var segments = new Array();

        $.each(venues, function(key, value) {
            segments.push( value );
        });

        wheel.segments = segments;
        wheel.update();

    }else if( params['juego'] == "tragamonedas" ){

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
                                        .addClass('roulette')
                                ),
                            $(document.createElement("div"))
                                .attr('id', 'tira2')
                                .addClass('tira2')
                                .append(
                                    $(document.createElement("div"))
                                        .attr('id', 'tira_imagenes2')
                                        .addClass('roulette')
                                ),
                            $(document.createElement("div"))
                                .attr('id', 'tira3')
                                .addClass('tira3')
                                .append(
                                    $(document.createElement("div"))
                                        .attr('id', 'tira_imagenes3')
                                        .addClass('roulette')
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

            var imagenes_tiras1 = ['coin', 'chomp', 'flower', 'star', '0', '1', '2', '4', '5', '6', '7'];
            var imagenes_tiras2 = ['coin', 'chomp', 'star', 'flower', '0', '1', '2', '4', '5', '6', '7'];
            var imagenes_tiras3 = [ 'flower', 'coin', 'chomp', 'star', '0', '1', '2', '4', '5', '6', '7'];

            var tira_imagenes1 = $('#tira_imagenes1');
            var tira_imagenes2 = $('#tira_imagenes2');
            var tira_imagenes3 = $('#tira_imagenes3');

            for(var i= 0; i<imagenes_tiras1.length; i++){
                tira_imagenes1.append(
                    $(document.createElement("img"))
                        .attr('src', "/img/tragamonedas/" +imagenes_tiras1[i]+'.png')
                );
                tira_imagenes2.append(
                    $(document.createElement("img"))
                        .attr('src', "/img/tragamonedas/" +imagenes_tiras2[i]+'.png')
                );
                tira_imagenes3.append(
                    $(document.createElement("img"))
                        .attr('src', "/img/tragamonedas/" +imagenes_tiras3[i]+'.png')
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
            var p = {
                startCallback : function() {
                },
                slowDownCallback : function() {
                    appendLogMsg('slowdown');
                    $('.stop').attr('disabled', 'true');
                },
                stopCallback : function($stopElm) {
                    appendLogMsg('stop');
                    console.log("stop " + $stopElm);
                    /*cargar_elementos_sorteados($stopElm);
                    $('#speed, #duration').slider('enable');
                    $('#stopImageNumber').spinner('enable');
                    $('.start').removeAttr('disabled');
                    $('.stop').attr('disabled', 'true');*/
                }
            }

            //creo los 3 elementos ruletas
            var rouletter = $('div.roulette');
            var rouletter1 = $('div.roulette1');
            var rouletter2 = $('div.roulette2');

            rouletter.roulette(p);
            rouletter1.roulette(p);
            rouletter2.roulette(p);

            $('.stop').click(function(){
                var stopImageNumber = $('.stopImageNumber').val();
                if(stopImageNumber == "") {
                    stopImageNumber = null;
                }
                //paran los 3
                rouletter.roulette('stop');
                rouletter1.roulette('stop');
                rouletter2.roulette('stop');
            });
            $('.stop').attr('disabled', 'true');
            $('.start').click(function(){
                //giran los 3
                rouletter.roulette('start');
                rouletter1.roulette('start');
                rouletter2.roulette('start');
            });

            //para el uno
            var updateParamater = function(){
                p['speed'] = Number($('.speed_param').eq(0).text());
                p['duration'] = Number($('.duration_param').eq(0).text());
                p['stopImageNumber'] = Number($('.stop_image_number_param').eq(0).text());
                rouletter.roulette('option', p);
            }
            var updateSpeed = function(speed){
                $('.speed_param').text(speed);
            }
            $('#speed').slider({
                min: 1,
                max: 30,
                value : 10,
                slide: function( event, ui ) {
                    updateSpeed(ui.value);
                    updateParamater();
                }
            });
            updateSpeed($('#speed').slider('value'));

            var updateDuration = function(duration){
                $('.duration_param').text(duration);
            }
            $('#duration').slider({
                min: 1,
                max: 30,
                value : 10,
                slide: function( event, ui ) {
                    updateDuration(ui.value);
                    updateParamater();
                }
            });
            updateDuration($('#duration').slider('value'));

            var updateStopImageNumber = function(stopImageNumber) {
                $('.image_sample').children().css('opacity' , 0.2);
                $('.image_sample').children().filter('[data-value="' + stopImageNumber + '"]').css('opacity' , 1);
                $('.stop_image_number_param').text(stopImageNumber);
                updateParamater();
            }

            $('#stopImageNumber').spinner({
                spin: function( event, ui ) {
                    var imageNumber = ui.value;
                    if ( ui.value > 4 ) {
                        $( this ).spinner( "value", -1 );
                        imageNumber = 0;
                        updateStopImageNumber(-1);
                        return false;
                    } else if ( ui.value < -1 ) {
                        $( this ).spinner( "value", 4 );
                        imageNumber = 4;
                        updateStopImageNumber(4);
                        return false;
                    }
                    updateStopImageNumber(imageNumber);
                }
            });
            $('#stopImageNumber').spinner('value', 0);
            updateStopImageNumber($('#stopImageNumber').spinner('value'));
            $('.image_sample').children().click(function(){
                var stopImageNumber = $(this).attr('data-value');
                $('#stopImageNumber').spinner('value', stopImageNumber);
                updateStopImageNumber(stopImageNumber);
            });

            //para el dos
            var updateParamater1 = function(){
                p['speed'] = Number($('.speed_param').eq(0).text());
                p['duration'] = Number($('.duration_param').eq(0).text());
                p['stopImageNumber'] = Number($('.stop_image_number_param1').eq(0).text());
                rouletter1.roulette('option', p);
            }
            var updateSpeed1 = function(speed){
                $('.speed_param').text(speed);
            }
            $('#speed').slider({
                min: 1,
                max: 30,
                value : 10,
                slide: function( event, ui ) {
                    updateSpeed1(ui.value);
                    updateParamater1();
                }
            });
            updateSpeed1($('#speed').slider('value'));

            var updateDuration1 = function(duration){
                $('.duration_param').text(duration);
            }
            $('#duration').slider({
                min: 2,
                max: 10,
                value : 3,
                slide: function( event, ui ) {
                    updateDuration1(ui.value);
                    updateParamater1();
                }
            });

            updateDuration1($('#duration').slider('value'));

            var updateStopImageNumber1 = function(stopImageNumber) {
                $('.image_sample1').children().css('opacity' , 0.2);
                $('.image_sample1').children().filter('[data-value="' + stopImageNumber + '"]').css('opacity' , 1);
                $('.stop_image_number_param1').text(stopImageNumber);
                updateParamater1();
            }

            $('#stopImageNumber1').spinner({
                spin: function( event, ui ) {
                    var imageNumber = ui.value;
                    if ( ui.value > 4 ) {
                        $( this ).spinner( "value", -1 );
                        imageNumber = 0;
                        updateStopImageNumber1(-1);
                        return false;
                    } else if ( ui.value < -1 ) {
                        $( this ).spinner( "value", 4 );
                        imageNumber = 4;
                        updateStopImageNumber1(4);
                        return false;
                    }
                    updateStopImageNumber1(imageNumber);
                }
            });
            $('#stopImageNumber1').spinner('value', 0);
            updateStopImageNumber1($('#stopImageNumber1').spinner('value'));
            $('.image_sample1').children().click(function(){
                var stopImageNumber = $(this).attr('data-value');
                $('#stopImageNumber1').spinner('value', stopImageNumber);
                updateStopImageNumber1(stopImageNumber);
            });

            //para el tres
            var updateParamater2 = function(){
                p['speed'] = Number($('.speed_param').eq(0).text());
                p['duration'] = Number($('.duration_param').eq(0).text());
                p['stopImageNumber'] = Number($('.stop_image_number_param2').eq(0).text());
                rouletter2.roulette('option', p);
            }
            var updateSpeed2 = function(speed){
                $('.speed_param').text(speed);
            }
            $('#speed').slider({
                min: 1,
                max: 30,
                value : 10,
                slide: function( event, ui ) {
                    updateSpeed2(ui.value);
                    updateParamater2();
                }
            });
            updateSpeed2($('#speed').slider('value'));

            var updateDuration2 = function(duration){
                $('.duration_param').text(duration);
            }
            $('#duration').slider({
                min: 1,
                max: 30,
                value : 10,
                slide: function( event, ui ) {
                    updateDuration2(ui.value);
                    updateParamater2();
                }
            });
            updateDuration2($('#duration').slider('value'));

            var updateStopImageNumber2 = function(stopImageNumber) {
                $('.image_sample2').children().css('opacity' , 0.2);
                $('.image_sample2').children().filter('[data-value="' + stopImageNumber + '"]').css('opacity' , 1);
                $('.stop_image_number_param2').text(stopImageNumber);
                updateParamater2();
            }

            $('#stopImageNumber2').spinner({
                spin: function( event, ui ) {
                    var imageNumber = ui.value;
                    if ( ui.value > 4 ) {
                        $( this ).spinner( "value", -1 );
                        imageNumber = 0;
                        updateStopImageNumber2(-1);
                        return false;
                    } else if ( ui.value < -1 ) {
                        $( this ).spinner( "value", 4 );
                        imageNumber = 4;
                        updateStopImageNumber2(4);
                        return false;
                    }
                    updateStopImageNumber2(imageNumber);
                }
            });
            $('#stopImageNumber2').spinner('value', 0);
            updateStopImageNumber2($('#stopImageNumber2').spinner('value'));

            $('.image_sample2').children().click(function(){
                var stopImageNumber = $(this).attr('data-value');
                $('#stopImageNumber2').spinner('value', stopImageNumber);
                updateStopImageNumber2(stopImageNumber);
            });

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

    }else if( params['juego'] == "tvchat" ){

        alert("aun no cargado");
        console.log("aun no cargado");
    }
}

function descargarJuego(params){

    if(params['juego'] == "tombola"){

        var juego = $('#tombola_wheel_wrapper');
        juego.remove();
    }else if( params['juego'] == "tragamonedas" ){

        var juego = $('#tragamonedas_wrapper');
        juego.remove();
    }else if(params['juego'] == "tvchat" ){

        var juego = $('#ventana');
        juego.remove();
    }

    console.log("juego descargado!!");
}

function jugarJuego(params){
    console.log("jugar que " +params['jugar']);
    if(params['jugar'] == "tombola"){
        var canvas = $('#canvas');
        canvas.trigger( "click" );
    }else if(params['jugar'] == "tragamonedas"){
        //creo los 3 elementos ruletas
        var p = {
            startCallback : function() {
            },
            slowDownCallback : function() {
                appendLogMsg('slowdown');
                $('.stop').attr('disabled', 'true');
            },
            stopCallback : function($stopElm) {
                appendLogMsg('stop');
                console.log("stop" + $stopElm);
                /*cargar_elementos_sorteados($stopElm);
                $('#speed, #duration').slider('enable');
                $('#stopImageNumber').spinner('enable');
                $('.start').removeAttr('disabled');
                $('.stop').attr('disabled', 'true');*/
            }
        }

        //creo los 3 elementos ruletas
        var rouletter = $('div.roulette');
        var rouletter1 = $('div.roulette1');
        var rouletter2 = $('div.roulette2');

        rouletter.roulette(p);
        rouletter1.roulette(p);
        rouletter2.roulette(p);

        rouletter.roulette('start');
        rouletter1.roulette('start');
        rouletter2.roulette('start');
    }
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