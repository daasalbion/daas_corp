$(document).ready(function(){

    $("#mes").change(function() {

        window.location = '/tvchat-reportes/altas-bajas-chat/fecha/' + this.value;
    });

});

