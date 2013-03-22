$(document).ready(function(){

    $("#cargar_suscriptos").click(function() {

        //alert('value:[' + this.value + ']');
        var id_promocion = $("#id_promocion").val();
        var fecha = $("#mes").val();
        window.location = '/reportes/suscriptos-por-hora/fecha/' + fecha + '/id_promocion/' + id_promocion;
    });

});

