(function($) {

    var Wheel =  function( options ){

        var defaultSettings = {

            timerHandle : 0,
            iterationsHandle: 0,
            timerDelay : 100,

            initialAngle : 0,//Math.PI*3/2,
            angleCurrent : 0,
            angleDelta : 0,

            size : 124,

            canvasContext : null,
                        //rojo                         //naranja  //azul
            //colors : [ '#ED8282', '#A1FAA1', '#FCAAFC', '#FFA970', '#C4DAEF' ],
            colors : [ '#ff0000', '#00FF00', '#FF00FF', '#FF6600', '#6699CC' ],

            segments : [],

            angles : [],

            seg_colors : [], // Cache of segments to colors

            maxSpeed : Math.PI / 16,

            upTime : 0,// +5*100, // How long to spin up for (in ms)
            downTime : 0,//+5*600, // How long to slow down for (in ms)

            spinStart : 0,

            frames : 0,

            centerX : 150,
            centerY : 150,

            valoresEsperados : [],
            contadorIterations : 0,
            angulos: [],

            delta_numero_ganador: 0,
            numero_vueltas: 4,

            stopCallback : function() {},
            startCallback : function() {},
            slowDownCallback : function() {},
            stopNumberCallback : function() {}

        };

        var defaultProperty = {};

        var q = $.extend( {}, defaultSettings, options, defaultProperty );

        var spin = function() {

            q.contadorIterations++;
            console.log("numero de iteraciones: " + q.contadorIterations);
            // Start the q only if it's not already spinning
            if(q.contadorIterations > q.valoresEsperados.length){

                clearInterval(q.iterationsHandle);
                q.contadorIterations = 0;
                q.stopCallback(0);

            }else{

                if (q.timerHandle == 0) {
                    //q.spinStart = new Date().getTime();
                    //q.maxSpeed = Math.PI / (16 + Math.random()); // Randomly vary how hard the spin is
                    q.maxSpeed = Math.PI/5;
                    q.frames = 0;

                    console.log("angulo actual: " + q.angleCurrent);
                    console.log("angulos iniciales: " + q.angles);

                    calcularTiempo();
                    q.timerHandle = setInterval( onTimerTick, q.timerDelay );
                }
            }
        };

        var onTimerTick = function() {

            console.log("angulos en movimiento: " + q.angles);
            q.frames++;
            draw();

            var progress;
            var finished = false;
            //var duration = (new Date().getTime() - q.spinStart);
            var duration = (q.timerDelay + q.spinStart);//avanza de manera constante
            q.spinStart = duration;
            console.log("duration: " + duration);

            progress = duration / (q.upTime + q.downTime);
            //q.delta_numero_ganador = Math.PI/225;

            if (duration < q.upTime) {

                //console.log("acelerando...");
                //q.angleDelta = q.maxSpeed * Math.sin(progress * Math.PI / 2);
                //mirar
                //q.delta_numero_ganador = (Math.random() * (Math.PI/125)) +  q.angleCurrent;

                //console.log( "RAMDOM: " + q.delta_numero_ganador);

                q.angleDelta = q.maxSpeed + q.delta_numero_ganador;// * progress;

            } else {

                //finished = true;
                //console.log("desacelerando............................");
                //q.angleDelta = q.maxSpeed * Math.sin(progress * Math.PI / 2 + Math.PI / 2);

                q.angleDelta =  q.maxSpeed - q.delta_numero_ganador;//*(1-progress);

                if (progress >= 1)
                    finished = true;
            }

            //console.log("angleDelta: " + q.angleDelta);

            q.angleCurrent += q.angleDelta;

            while (q.angleCurrent >= Math.PI * 2){

                q.angleCurrent -= Math.PI * 2;
                console.log("angleCurrent ajustado: " + q.angleCurrent);
            }

            if (finished) {

                clearInterval(q.timerHandle);
                //seteo bien el ultimo valor que aumenta al pedo
                q.angleCurrent -= q.angleDelta;
                q.timerHandle = 0;
                q.angleDelta = 0;
                q.spinStart = 0;
                $("#counter").html((q.frames / duration * 1000) + " FPS");

                //avisar cuando una iteracion termina y el llamador pueda hacer algo
                q.stopNumberCallback(q.valoresEsperados[q.contadorIterations-1]);
            }

            // Display RPM
            var rpm = (q.angleDelta * (1000 / q.timerDelay) * 60) / (Math.PI * 2);
            $("#counter2").html( Math.round(rpm) + " RPM" );
        };

        var init = function( optionList ) {
            try {
                initWheel();
                initCanvas();
                draw();

                $.extend(q, optionList);

            } catch (exceptionData) {
                //alert('Wheel is not loaded ' + exceptionData);
            }

        };

        var initCanvas = function() {
            var canvas = $('#wheel #canvas').get(0);

            if ($.browser.msie) {
                canvas = document.createElement('canvas');
                $(canvas).attr('width', 300).attr('height', 300).attr('id', 'canvas').appendTo('.wheel');
                canvas = G_vmlCanvasManager.initElement(canvas);
            }

            //canvas.addEventListener("click", initIterations, false);
            q.canvasContext = canvas.getContext("2d");
        };

        var initIterations = function() {
            q.iterationsHandle = setInterval( spin, 8000 );
        };

        var initWheel = function() {
            shuffle(q.colors);
        };

        var update = function() {
            // Ensure we start mid way on a item
            //var r = Math.floor(Math.random() * q.segments.length);
            var r = q.initialAngle;
            //q.angleCurrent = ((r + 0.5) / q.segments.length) * Math.PI * 2;
            q.angleCurrent = r;

            var segments = q.segments;
            var len      = segments.length;
            var colors   = q.colors;
            var colorLen = colors.length;

            // Generate a color cache (so we have consistant coloring)
            var seg_color = new Array();
            for (var i = 0; i < len; i++)
                seg_color.push( colors [ segments[i].hashCode().mod(colorLen) ] );

            q.seg_color = seg_color;

            draw();

            console.log( "angulos iniciales: " + q.angles );
            console.log( "angulos indices: " + q.angulos );
        };

        var draw = function() {
            clear();
            drawWheel();
            drawNeedle();
        };

        var clear = function() {
            var ctx = q.canvasContext;
            ctx.clearRect(0, 0, 1000, 800);
        };

        var calcularTiempo = function() {

            var angulo_esperado = q.angulos[q.valoresEsperados[q.contadorIterations-1]];
            console.log("mirar el angulo esperado: " + angulo_esperado);
            console.log("angulo actual: " + q.angleCurrent);
            var tiempo = (angulo_esperado - q.angleCurrent)/q.maxSpeed + 1;
            //si algo requerido es el mismo entonces doy 4 vueltas para caer en el mismo
            if(tiempo == 0){

                tiempo = q.numero_vueltas*10;
            }

            console.log("tiempo: " + tiempo);
            //le agrego siempre 2 vueltas
            q.upTime = (tiempo)*q.timerDelay + Math.floor(q.numero_vueltas/2)*10*q.timerDelay;
            //le agrego siempre 2 vueltas
            q.downTime = Math.floor(q.numero_vueltas/2)*10*q.timerDelay;
        };

        var drawWheel = function() {
            var ctx = q.canvasContext;

            var angleCurrent = q.angleCurrent;
            var lastAngle    = angleCurrent;

            var segments  = q.segments;
            var len       = q.segments.length;
            var colors    = q.colors;
            var colorsLen = q.colors.length;

            var centerX = q.centerX;
            var centerY = q.centerY;
            var size    = q.size;

            var PI2 = Math.PI * 2;

            ctx.lineWidth    = 1;
            ctx.strokeStyle  = '#000000';
            ctx.textBaseline = "middle";
            ctx.textAlign    = "center";
            ctx.font         = "1.4em Arial";

            for (var i = 1; i <= len; i++) {
                var angle = PI2 * (i / len) + angleCurrent;
                drawSegment(i - 1, lastAngle, angle);
                q.angles[i-1] = lastAngle;
                //guardo el orden de los angulos guardados
                var j = q.segments.length - Math.floor((lastAngle / (Math.PI * 2))	* q.segments.length) - 3;
                if( j < 0 )
                    j = len - (-1)*j;
                q.angulos[j] = q.angles[i-1];

                lastAngle = angle;
            }

            // Draw a center circle
            ctx.beginPath();
            ctx.arc(centerX, centerY, 20, 0, PI2, false);
            ctx.closePath();

            ctx.fillStyle   = '#ffffff';
            ctx.strokeStyle = '#000000';
            ctx.fill();
            ctx.stroke();

            // Draw outer circle
            ctx.beginPath();
            ctx.arc(centerX, centerY, size, 0, PI2, false);
            ctx.closePath();

            ctx.lineWidth   = 4;
            ctx.strokeStyle = '#000000';
            ctx.stroke();
        };

        var drawSegment = function(key, lastAngle, angle) {
            var ctx = q.canvasContext;
            var centerX = q.centerX;
            var centerY = q.centerY;
            var size = q.size;

            var segments = q.segments;
            var len = q.segments.length;
            var colors = q.seg_color;

            var value = segments[key];

            ctx.save();
            ctx.beginPath();

            // Start in the centre
            ctx.moveTo(centerX, centerY);
            ctx.arc(centerX, centerY, size, lastAngle, angle, false); // Draw a arc around the edge
            ctx.lineTo(centerX, centerY); // Now draw a line back to the centre
            ctx.lineWidth = 2;

            // Clip anything that follows to this area
            //ctx.clip(); // It would be best to clip, but we can double performance without it
            ctx.closePath();

            ctx.fillStyle = colors[key];
            ctx.fill();
            ctx.strokeStyle = 'white';
            ctx.stroke();

            // Now draw the text
            ctx.save(); // The save ensures this works on Android devices
            ctx.translate(centerX, centerY);
            ctx.rotate((lastAngle + angle) / 2);

            ctx.fillStyle = '#000000';
            ctx.font = 'bold 27px Arial';
            ctx.fillText(value.substr(0, 20), size / 2 + 20, 0);
            ctx.restore();

            ctx.restore();
        };

        var drawNeedle = function() {

            var ctx = q.canvasContext;
            var centerX = q.centerX;
            var centerY = q.centerY;
            var size = q.size;

            ctx.lineWidth = 1;
            ctx.strokeStyle = '#000000';
            ctx.fileStyle = '#ffffff';

            ctx.beginPath();

            /*ctx.moveTo(centerX + size - 40, centerY);
             ctx.lineTo(centerX + size + 20, centerY - 10);
             ctx.lineTo(centerX + size + 20, centerY + 10);*/

            ctx.moveTo(centerX, centerY - size + 35);
            ctx.lineTo(centerX - 15, centerY - size - 20);
            ctx.lineTo(centerX + 15, centerY - size - 20);

            ctx.closePath();

            ctx.stroke();
            ctx.fill();

            //a ver que pasa
            /*var angleCurrent = q.angleCurrent - q.initialAngle;
             if( angleCurrent < 0 )
             angleCurrent = q.initialAngle + q.angleCurrent;
             alert(angleCurrent);*/
            console.log('angleCurrent: '+q.angleCurrent);
            // Which segment is being pointed to?
            var i = q.segments.length - Math.floor((q.angleCurrent / (Math.PI * 2))	* q.segments.length) - 3;

            // Now draw the winning name
            ctx.textAlign = "center";
            ctx.textBaseline = "middle";
            ctx.fillStyle = '#000000';
            ctx.font = "2em Arial";
            ctx.fillText( q.segments[i], centerX, centerY - (size + 40) );
        };

        var iniciar = function(){

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

            init();

            var segments = new Array();

            $.each(venues, function(key, value) {
                segments.push( value );
            });

            q.segments = segments;
            update();

        };

        var option = function( options ) {
            q = $.extend(q, options);
        };

        var start = function(){

            var canvas = $('#wheel #canvas').get(0);
            console.log('empezar');
            initIterations();
            q.canvasContext = canvas.getContext("2d");
        }

        var ret = {
            start : start,
            //init : init,
            iniciar : iniciar,
            option : option
        }

        return ret;

    };

    var pluginName = 'wheel';
    $.fn[pluginName] = function( method, options ) {
        //por cada funcion que encuentra dentro del dom le asigna las propiedades del objeto
        return this.each(function() {
            var self = $(this);
            var wheel = self.data('plugin_' + pluginName);

            if ( wheel ) {
                if ( wheel[method] ) {
                    wheel[method](options);
                } else {
                    console && console.error('Method ' + method + ' does not exist on jQuery.wheel');
                }
            } else {
                wheel = new Wheel( method );
                wheel.iniciar();
                $(this).data( 'plugin_' + pluginName, wheel );
            }
        });
    }

})(jQuery);