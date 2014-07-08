$(document).ready(function(){

    //abrir ventanas
    $('#abrir_ventana1').click(function(){

        tvchat = window.open("/tvchat/tv",
            "_blank", "width=800, height=600, menubar=no, toolbar=no, location=no, directories=no, status=no, scrollbars=auto, fullscreen=yes");

    })
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

    //cerrar ventanas
    $('#cerrar_ventana1').click(function(){
        tvchat.close();
    })
    $('#cerrar_ventana2').click(function(){
        ventana1.close();
    })
    $('#cerrar_ventana3').click(function(){
        ventana2.close();
    })

    $('#cargar_tombola').click(function(){

        //valores por defecto
        var params = {
            "juego": "tombola",
            "valores_ganadores": [1,2,3,4,5]
        };

        tvchat.cargarJuego(params);
    })
    $('#cargar_tragamonedas').click(function(){

        //valores por defecto
        var params = {
            "juego": "tragamonedas",
            "valores_ganadores": [1,2,3]
        };

        tvchat.cargarJuego(params);
    })
    $('#cargar_tvchat').click(function(){

        //valores por defecto
        var params = {
            "juego": "tvchat"
        };

        tvchat.cargarJuego(params);
    })

    $('#cerrar_tragamonedas').click(function(){

        //valores por defecto
        var params = {
            "juego": "tragamonedas"
        };

        tvchat.descargarJuego(params);
    })
    $('#cerrar_tombola').click(function(){

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
});