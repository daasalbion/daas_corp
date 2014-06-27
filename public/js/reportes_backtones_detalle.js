$(document).ready(function(){

    $("#bloque_mes").change(function() {

        var mes = $("#mes").val();
        var id_proveedor = $("#id_proveedor").val();
        window.location = '/reportes/backtones-detalle-por-proveedor/id-proveedor/'+id_proveedor+'/fecha/'+mes;
    });
});

