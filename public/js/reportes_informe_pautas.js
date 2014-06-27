$(document).ready(function(){
    //debe coincidi con el boton del phtml si o si para linkear
    $("#cargar_informe_pautas").click(function() {

        var fecha = $("#mes").val();
        // Se envia como parametro fecha YYYY-MM-DD
        var dia = $("#datepicker").val();
        window.location = '/reportes/informe-pautas/fecha/' + dia;
    });
    $( "#datepicker" ).datepicker({
        dateFormat: "yy-mm-dd",
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

