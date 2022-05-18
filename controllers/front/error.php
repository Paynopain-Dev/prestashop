<?php

class paylandsErrorModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    /**
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        parent::initContent();
        Tools::redirect('index.php?controller=order&step=2&paylands-error=1');
    }
}
