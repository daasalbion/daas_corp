<?php

class Application_Form_Login extends Zend_Form
{

    public function init()
    {
        /* Form Elements & Other Definitions Here ... */
        $this->setName('form_login');

        $nick = new Zend_Form_Element_Text('login_user');
        $nick->setRequired(true)->addFilter('StripTags')->addFilter('StringTrim')->addFilter('StringToLower')->addValidator('NotEmpty');


        $clave = new Zend_Form_Element_Password('login_pass');
        $clave->setRequired(true)->addFilter('StripTags')->addFilter('StringTrim')->addValidator('NotEmpty');

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setAttrib('id', 'submitbutton');

        $this->addElements(
        	array(
        		$nick,
        		$clave,
        		$submit)
        );
    }


}

