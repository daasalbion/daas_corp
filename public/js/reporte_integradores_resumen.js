$(document).ready(function(){

    $("#mes").change(function() {

        window.location = '/reporte-integradores/resumen/fecha/' + this.value;
    });

    /*$("#generar_csv_resumen").click(function(){

        window.location = '/reporte-integradores/resumen/';
    });*/

});

