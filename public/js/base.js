$(document).ready(function(){

    if(!Modernizr.input.placeholder) {
        $('input[placeholder], textarea[placeholder]').placeholder();
    }


});

