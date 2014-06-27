$(document).ready(function(){

    $("#cargar_resumen").click(function() {

        var id_pais = $("#id_pais").val();
        var fecha = $("#mes").val();
        var dia = $("#datepicker").val();
        if(typeof id_pais != 'undefined') {
            window.location = '/reportes/resumen-cobros/pais/' + id_pais + '/fecha/' + dia;
        } else {
            window.location = '/reportes/resumen-cobros/fecha/' + dia;
        }
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

