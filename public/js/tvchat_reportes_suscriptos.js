$(document).ready(function(){

    $("#mes").change(function() {

        window.location = '/tvchat-reportes/reporte/fecha/' + this.value;
    });

});

