$(document).ready(function(){

    $("#mes").change(function() {

        //alert('value:[' + this.value + ']');
        window.location = '/reportes/suscriptos/fecha/' + this.value;
    });

});

