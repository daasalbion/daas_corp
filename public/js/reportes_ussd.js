$(document).ready(function(){

    $("#boton_ussd").click(function() {

        var fecha = $("#mes").val();
        var dia = $("#datepicker").val();
        window.location = '/reportes/ussd/fecha/' + dia;
    });
    $( "#datepicker" ).datepicker({
        dateFormat: 'yy-mm-dd',
        firstDay: 1,
        dayNamesMin: ["Do", "Lu", "Ma", "Mi", "Ju", "Vi", "Sa"],
        dayNamesShort: ["Dom", "Lun", "Mar", "Mie", "Jue", "Vie", "Sab"],
        monthNames:
            ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio",
                "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"],
        monthNamesShort:
            ["Ene", "Feb", "Mar", "Abr", "May", "Jun", "Jul",
                "Ago", "Sep", "Oct", "Nov", "Dic"]
    });


});


