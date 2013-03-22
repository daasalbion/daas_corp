$(document).ready(function(){

    if(!Modernizr.input.placeholder) {
        $('input[placeholder], textarea[placeholder]').placeholder();
    }

    /*var render_bloque_contacto = Tempo.prepare("bloque_contacto");

    var render_bloque_acceso = Tempo.prepare("bloque_acceso").notify(function(event){
        if(event.type === TempoEvent.Types.RENDER_COMPLETE) {

            $(".link_cancelar").click(function() {

                var comando = this.href;
                var partes = comando.split(',');
                //console.log(partes);
                var url = '/callcenter/cancelar';
                var cel = partes[1];
                var idPromocion = partes[2];

                $.post(url,	{ nro_linea: cel, id_promocion: idPromocion }, function(data, statusText) {

                    if(statusText == 'success') {
                        if(data.status == 'OK') {
                            //Servicio Cancelado para Nro de Linea
                            alert("Servicio Cancelado");
                            $("#link_cancelar_" + idPromocion).remove();

                        } else {
                            alert('Error de Sistema');
                        }
                    } else {
                        alert('Error de Comunicación');
                    }
                }, 'json');

                return false;
            });
        }
    });*/

    $('#form_autorizar').submit(function() {

        /*$("#nro_de_linea").html("");
        $("#bloque_servicios").html("");
        $("#bloque_historial").html("");*/

        // inside event callbacks 'this' is the DOM element so we first
        // wrap it in a jQuery object and then invoke ajaxSubmit
        $(this).ajaxSubmit({
            /*beforeSubmit: onSubmitBusquedaSuscriptos,*/
            success:  onResponseVerificarEnvioAutorizacion,
            dataType:  'json'
            //resetForm: true
        });
        // !!! Important !!!
        // always return false to prevent standard browser submit and page navigation
        return false;
    });

    /*function onSubmitBusquedaSuscriptos(formData, jqForm, options) {

     $("#bloque_servicios").html("");
     $("#bloque_historial").html("");

     return true;
     }*/

    function onResponseVerificarEnvioAutorizacion(data, statusText)  {//, xhr, $form

        if(statusText == 'success') {

            $("#nro_de_linea").html(data.cel);
            if(data.status == 'OK') {

                var url = '/index/suscripcion/autorizacion/';
                $.post(url,	{ nro_linea: data.cel }, function(datos, statusText) {

                    if(statusText == 'success') {
                        if(datos.status == 'OK') {
                            if(datos.cantidad > 0) {
                                render_bloque_contacto.render(datos.historial);
                            }
                        } else {
                            alert(data.error);
                        }
                    } else {
                        alert('Error de Comunicación');
                    }
                }, 'json');

                if(data.cantidad > 0) {
                    render_bloque_acceso.render(data.suscripciones);
                }

            } else {
                alert(data.error);
            }

        } else {
            alert('Error de Comunicación');
        }


    }

});
