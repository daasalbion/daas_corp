$(document).ready(function(){

    $("#cargar_suscriptos").click(function() {

        //alert('value:[' + this.value + ']');
        var id_promocion = $("#id_promocion").val();
        var fecha = $("#mes").val();
        var hr_desde=$("#timeFrom").val();
        var hr_hasta=$("#timeTo").val();
        // Se envia como parametro fecha YYYY-MM-DD
        // Se envia la hora de inicio y fin en formato HH:mm
        var dia = $("#datepicker").val();
        window.location = '/reportes/suscriptos-por-minuto/fecha/' + dia + '/id_promocion/' + id_promocion +
            '/desde/' + hr_desde + '/hasta/' + hr_hasta;
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
    $('#tiempo').timeEntry({show24Hours: true});

    $('.timeRange').timeEntry({beforeShow: customRange, show24Hours: true});

    function customRange(input) {
        return {minTime: (input.id == 'timeTo' ?
            $('#timeFrom').timeEntry('getTime') : null),
            maxTime: (input.id == 'timeFrom' ?
                $('#timeTo').timeEntry('getTime') : null)};
    }
});

