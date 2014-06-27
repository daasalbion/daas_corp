$(document).ready( function(){

    $("#promocion").change(function() {

        window.location = '/reportes/wap/id-promocion/' + this.value;
    });

});

$(document).ready( function(){

    $("#id-promocion").change(function() {

        window.location = '/reportes/contenidos-wap/id-promocion/' + this.value;
    });

});
