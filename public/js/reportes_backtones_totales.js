$(document).ready(function(){

    $("#fecha").change(function() {

        var id_carrier = $("#id_carrier").val();
        window.location = '/reportes/backtones-totales/carrier/'+id_carrier+'/fecha/'+this.value;
    });
    $("#id_carrier").change(function() {

        var fecha = $("#fecha").val();
        window.location = '/reportes/backtones-totales/carrier/'+this.value +'/fecha/'+fecha;
    });

});

