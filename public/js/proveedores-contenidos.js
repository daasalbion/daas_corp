$(document).ready(function(){

    $("#fecha").change(function() {

        var id_proveedor = $("#id_proveedor").val();
        window.location = '/reportes/proveedores-contenidos/id-proveedor/'+id_proveedor+'/fecha/'+this.value;
    });
    $("#id_proveedor").change(function() {

        var fecha = $("#fecha").val();
        window.location = '/reportes/proveedores-contenidos/id-proveedor/'+this.value +'/fecha/'+fecha;
    });

});

