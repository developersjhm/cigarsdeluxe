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

    public function hookPaymentOptions($params) {

        if (!$this->active) {
            return;
        }

        //Paiement Standard sans passerelle
        $standardPayment = new PaymentOption();

        //Inputs supplémentaires (utilisé idéalement pour des champs cachés )
        $inputs = [
            [
                'name' => 'custom_hidden_value',
                'type' => 'hidden',
                'value' => '30'
            ],
            [
                'name' => 'id_customer',
                'type' => 'hidden',
                'value' => $this->context->customer->id,
            ],
        ];
        $standardPayment->setModuleName($this->name)
            //Logo de paiement
            ->setLogo($this->context->link->getBaseLink().'/modules/suncash/views/img/logo.png')
            ->setInputs($inputs)
            //->setBinary() Utilisé si une éxécution de binaire est nécessaires ( module atos par ex )
            //Texte de description
            ->setCallToActionText($this->l('Suncash Payment Example'))
            ->setAction($this->context->link->getModuleLink($this->name, 'validation', array(), true))
            //Texte informatif supplémentaire
            ->setAdditionalInformation($this->fetch('module:suncash/views/templates/hook/displayPayment.tpl'));


        //Paiement API type bancaire

        //Variables pour paiement API
        $this->smarty->assign(
            $this->getPaymentApiVars()
        );

        $apiPayement = new PaymentOption();
        $apiPayement->setModuleName($this->name)
            ->setCallToActionText($this->l('Suncash Sample payement module (like CB )'))
            //Définition d'un formulaire personnalisé
            ->setForm($this->fetch('module:suncash/views/templates/hook/payment_api_form.tpl'))
            ->setAdditionalInformation($this->fetch('module:suncash/views/templates/hook/displayPaymentApi.tpl'));

        return [$standardPayment, $apiPayement];
    }
    public function getPaymentApiVars()
    {
        return  [
            'merchant_name' => Configuration::get('MERCHANT_NAME'),
            'test_mode' => Configuration::get('TEST_MODE'),
            'merchant_key' => Configuration::get('MERCHANT_KEY'),
            'id_cart' => $this->context->cart->id,
            'cart_total' =>  $this->context->cart->getOrderTotal(true, Cart::BOTH),
            'id_customer' => $this->context->cart->id_customer,
        ];
    }
    public function hookDisplayPaymentReturn($params)
    {
        if (!$this->active) {
            return;
        }

        $this->smarty->assign(
            $this->getTemplateVars()
        );
        return $this->fetch('module:suncash/views/templates/hook/payment_return.tpl');
    }

    /**
     * Configuration admin du module
     */
    public function getContent()
    {
        $this->_html .=$this->postProcess();
        $this->_html .= $this->renderForm();

        return $this->_html;

    }

    /**
     * Traitement de la configuration BO
     * @return type
     */
    public function postProcess()
    {
        if ( Tools::isSubmit('SubmitPaymentConfiguration'))
        {
            Configuration::updateValue('MERCHANT_NAME', Tools::getValue('MERCHANT_NAME'));
            Configuration::updateValue('TEST_MODE', Tools::getValue('TEST_MODE'));
            Configuration::updateValue('MERCHANT_KEY', Tools::getValue('MERCHANT_KEY'));
        }
        return $this->displayConfirmation($this->l('Configuration updated with success'));
    }

    /**
     * Formulaire de configuration admin
     */
    public function renderForm()
    {
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Suncash Payment Configuration'),
                    'icon' => 'icon-cogs'
                ],
                'description' => $this->l('Configuration form'),
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->l('Merchant Name'),
                        'name' => 'MERCHANT_NAME',
                        'required' => true,
                        'empty_message' => $this->l('Please fill the merchant name'),

                    ],
                    [
                        'type' => 'checkbox',
                        'label' => $this->l('Enable Test Mode'),
                        'name' => 'TEST_MODE',
                        'required' => false,
                        'empty_message' => $this->l('Please fill the payment api success url'),
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Merchant Key'),
                        'name' => 'MERCHANT_KEY',
                        'required' => true,
                        'empty_message' => $this->l('Please fill the merchant key'),
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                    'class' => 'button btn btn-default pull-right',
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->default_form_language = (int)Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->id = 'hhpayment';
        $helper->identifier = 'hhpayment';
        $helper->submit_action = 'SubmitPaymentConfiguration';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        ];

        return $helper->generateForm(array($fields_form));
    }

    /**
     * Récupération des variables de configuration du formulaire admin
     */
    public function getConfigFieldsValues()
    {
        return [
            'MERCHANT_NAME' => Tools::getValue('MERCHANT_NAME', Configuration::get('MERCHANT_NAME')),
            'TEST_MODE' => Tools::getValue('TEST_MODE', Configuration::get('TEST_MODE')),
            'MERCHANT_KEY' => Tools::getValue('MERCHANT_KEY', Configuration::get('MERCHANT_KEY')),
        ];
    }


    /**
     * Récupération des informations du template
     * @return array
     */
    public function getTemplateVars()
    {
        return [
            'shop_name' => $this->context->shop->name,
            'custom_var' => $this->l('My custom var value'),
            'payment_details' => $this->l('custom details'),
        ];
    }
}
