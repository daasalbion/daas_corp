<?php

class Application_Form_CargarPautas extends Zend_Form
{

    public function init()
    {
        /* Form Elements & Other Definitions Here ... */
        $this->setName('form_cargar_pautas');

        $deltax = new Zend_Form_Element_Text('deltax');
        $deltax->setRequired(true)->addFilter('StripTags')->addFilter('StringTrim')->addFilter('StringToLower')->addValidator('NotEmpty');

        $deltay = new Zend_Form_Element_Text('deltay');
        $deltay->setRequired(true)->addFilter('StripTags')->addFilter('StringTrim')->addFilter('StringToLower')->addValidator('NotEmpty');

        $nropautas = new Zend_Form_Element_Text('nropautas');
        $nropautas->setRequired(true)->addFilter('StripTags')->addFilter('StringTrim')->addFilter('StringToLower')->addValidator('NotEmpty');

        $duracion = new Zend_Form_Element_Text('duracion');
        $duracion->setRequired(true)->addFilter('StripTags')->addFilter('StringTrim')->addFilter('StringToLower')->addValidator('NotEmpty');

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setAttrib('id', 'submitbutton');

        $this->addElements(
        	array(
        		$deltax,
        		$deltay,
                $nropautas,
                $duracion,
        		$submit)
        );
    }

}


