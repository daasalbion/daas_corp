//valores venues para mostrar
venues = {
    "116208"  : "0",
    "66271"   : "1",
    "5518"    : "2",
    "392360"  : "3",
    "2210952" : "4",
    "207306"  : "5",
    "41457"   : "6",
    "101161"  : "7",
    "257424"  : "8",
    "512060"  : "9"
};

$(function() {
    //inicializo el elemento que va a contener la lista de elementos cargados
    var venueContainer = $('#venues ul');
    $.each(venues, function(key, item) {
        venueContainer.append(
            //cargo los elementos
            $(document.createElement("li"))
                .append(
                    $(document.createElement("input")).attr({
                        id:    'venue-' + key,
                        name:  item,
                        value: item,
                        type:  'checkbox',
                        checked: true
                    })
                        //si algun elemento no se encuentra checkeado lo actualiza y lo quita
                        .change( function() {
                            var cbox = $(this)[0];
                            var segments = wheel.segments;
                            var i = segments.indexOf(cbox.value);

                            if (cbox.checked && i == -1) {
                                segments.push(cbox.value);

                            } else if ( !cbox.checked && i != -1 ) {
                                segments.splice(i, 1);
                            }

                            segments.sort();
                            wheel.update();
                        } )

                ).append(
                    $(document.createElement('label')).attr({
                        'for':  'venue-' + key
                    })
                        .text( item )
                )
        )
    });
    //ordena los elementos a desplegar de acuerdo a la funcion de abajo
    $('#venues ul>li').tsort("input", {attr: "value"});
});