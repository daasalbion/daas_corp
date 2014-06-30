$(function(){

    var imagenes_tiras1 = ['coin', 'chomp', 'flower', 'star'];
    var imagenes_tiras2 = ['coin', 'chomp', 'star', 'flower'];
    var imagenes_tiras3 = [ 'flower', 'coin', 'chomp', 'star'];

    var tira_imagenes1 = $('#tira_imagenes1');
    var tira_imagenes2 = $('#tira_imagenes2');
    var tira_imagenes3 = $('#tira_imagenes3');

    for(var i= 0; i<imagenes_tiras1.length; i++){
        tira_imagenes1.append(
            $(document.createElement("img"))
                .attr('src', "/img/tragamonedas/" +imagenes_tiras1[i]+'_prueba.png')
        );
        tira_imagenes2.append(
            $(document.createElement("img"))
                .attr('src', "/img/tragamonedas/" +imagenes_tiras2[i]+'_prueba.png')
        );
        tira_imagenes3.append(
            $(document.createElement("img"))
                .attr('src', "/img/tragamonedas/" +imagenes_tiras3[i]+'_prueba.png')
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
			appendLogMsg('start');
			$('#speed, #duration').slider('disable');
			$('#stopImageNumber').spinner('disable');
			$('.start').attr('disabled', 'true');
			$('.stop').removeAttr('disabled');
		},
		slowDownCallback : function() {
			appendLogMsg('slowdown');
			$('.stop').attr('disabled', 'true');
		},
		stopCallback : function($stopElm) {
            appendLogMsg('stop');
            console.log("stop" + $stopElm);
            cargar_elementos_sorteados($stopElm);
			$('#speed, #duration').slider('enable');
			$('#stopImageNumber').spinner('enable');
			$('.start').removeAttr('disabled');
			$('.stop').attr('disabled', 'true');
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

    console.log("mierda: " + p.duration);
});

