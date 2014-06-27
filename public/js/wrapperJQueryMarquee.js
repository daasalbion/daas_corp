/**
 * Created by USER on 15/04/14.
 */
$(document).ready(function(){

    var $mwo = $('.marquee-with-options');
    var cadena = '';
    var textarray = [
       /*"hola que tal quiero que el texto sea mas largo para probar",
        "no me gusta tu programa me gusta mas el otro programa"*/
    ];

    $('.marquee').marquee();
    $('.marquee-with-options').marquee({
        //speed in milliseconds of the marquee
        speed: 5000,
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

    //Direction upward
    $('.marquee-vert').marquee({
        direction: 'up',
        speed: 1500
    });

    //pause and resume links
    $('.pause').click(function(e){
        e.preventDefault();
        $mwo.trigger('pause');
    });
    $('.resume').click(function(e){
        e.preventDefault();
        $mwo.trigger('resume');
    });
    //toggle
    $('.toggle').hover(function(e){
        $mwo.trigger('pause');
    },function(){
        $mwo.trigger('resume');
    })
    .click(function(e){
        e.preventDefault();
    })

    //llamada ajax agregar ak
    $('#cargar').click(function(){
        cadena = $('#cargar_normal').val();
        textarray.push(cadena);
    })

    $("#coniva").click(function(){
        $.get("recibe-parametros-devuelve-json.php", {pais: "ES", precio: 20}, muestraPrecioFinal, "json");
    })
    $("#siniva").click(function(){
        $.get("recibe-parametros-devuelve-json.php", {pais: "BR", precio: 300}, muestraPrecioFinal, "json");
    })

    function showRandomMarquee() {
        var length = textarray.length;
        var rannum = Math.floor(Math.random()*length);
        $mwo
            .marquee('destroy')
            .bind('finished', showRandomMarquee)
            .html(textarray[rannum])
            .marquee({duration: 7000});
    }

    showRandomMarquee();

});
