$(document).ready(function(){

    $("#mes").change(function() {

        //alert('value:[' + this.value + ']');
        window.location = '/reportes/cobros-por-carrier/fecha/' + this.value;
    });

});

