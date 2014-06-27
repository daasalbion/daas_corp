//VARIABLE GLOBAL QUE NUNCA CAMBIA
var url = '/reportes/cargar-bd-pautas/alias/';
$(document).ready(function(){
    //guardo como una variable para usar despues en la fecha el alias
    var alias = $("#alias").change(function() {

        //window.location = url + this.value;
    });
    //con change puede hacer que cambie directamente
    var dia = $("#datepicker").change(function() {

        //alert('alias:[' + alias.val() + ']');
        var dia = $("#datepicker").val();
        window.location = url + alias.val()+'/fecha/' + dia;
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




