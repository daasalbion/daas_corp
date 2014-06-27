$(document).ready(function(){

    $("#mes").change(function() {

        window.location = '/reportes/cobros-por-tipo/fecha/' + this.value;
    });

});

