<?php

class GameController extends Zend_Controller_Action
{
    public function init()
    {
        $this->_helper->layout->disableLayout();
        //$this->_helper->viewRenderer->setNoRender(true);
    }

    public function indexAction() {

        //$this->logger->info('--->INDEX_ACTION');

        return;
    }
}
