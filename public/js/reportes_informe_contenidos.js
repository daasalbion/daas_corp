$(document).ready(function(){

    $("#alias").change(function() {

        //alert('value:[' + this.value + ']');
        window.location = '/reportes/informe-contenidos/alias/' + this.value;
    });

});

