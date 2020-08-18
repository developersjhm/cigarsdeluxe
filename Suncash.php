<?php
use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Suncash extends PaymentModule
{

    public function __construct()
    {
        $this->author    = 'Aquiel';
        $this->name      = 'suncash';
        $this->tab       = 'payment_gateways';
        $this->version   = '0.1.0';
        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('Suncash Payment');
        $this->description = $this->l('Suncash Payment for PS 1.7');
    }

    public function install()
    {
        if (!parent::install()
            || !$this->registerHook('paymentOptions')
            || !$this->registerHook('paymentReturn')
        ) {
            return false;
        }
        return true;
    }

}
