<?php

/**
 * 2007-2017 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    Rosmel Torres <contact@h-hennes.fr>
 *  @copyright 2013-2017 RosmelTorres
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  http://www.h-hennes.fr/blog/
 */

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Suncash extends PaymentModule
{
    protected $_html;

    public function __construct()
    {
        $this->author    = 'aquiel';
        $this->name      = 'Suncash';
        $this->tab       = 'payment_gateways';
        $this->version   = '0.1.0';
        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('SunCash Payment');
        $this->description = $this->l('suncash sample Payment for PS 1.7');
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

    /**
     * Affichage du paiement dans le checkout
     * PS 17 
     * @param type $params
     * @return type
     */
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
                ->setLogo($this->context->link->getBaseLink().'/modules/Suncash/views/img/logo.png')
                ->setInputs($inputs)
                //->setBinary() Utilisé si une éxécution de binaire est nécessaires ( module atos par ex )
                //Texte de description
                ->setCallToActionText($this->l('Suncash Payment Example'))
                ->setAction($this->context->link->getModuleLink($this->name, 'validation', array(), true))
                //Texte informatif supplémentaire
                ->setAdditionalInformation($this->fetch('module:Suncash/views/templates/hook/displayPayment.tpl'));

        
        //Paiement API type bancaire

        //Variables pour paiement API
        $this->smarty->assign(
                $this->getPaymentApiVars()
        );

        $apiPayement = new PaymentOption();
        $apiPayement->setModuleName($this->name)
                ->setCallToActionText($this->l('Suncash Sample payement module (like CB )'))
                //Définition d'un formulaire personnalisé
                ->setForm($this->fetch('module:Suncash/views/templates/hook/payment_api_form.tpl'))
                ->setAdditionalInformation($this->fetch('module:Suncash/views/templates/hook/displayPaymentApi.tpl'));

        return [$standardPayment, $apiPayement];
    }

    /**
     * Information de paiement api
     * @return array
     */
    public function getPaymentApiVars()
    {
        return  [
             'payment_url' => Configuration::get('PAYMENT_API_URL'),
             'success_url' => Configuration::get('PAYMENT_API_URL_SUCESS'),
             'error_url' => Configuration::get('PAYMENT_API_URL_ERROR'),
             'id_cart' => $this->context->cart->id,
             'cart_total' =>  $this->context->cart->getOrderTotal(true, Cart::BOTH),
             'id_customer' => $this->context->cart->id_customer,
        ];
    }
    
    /**
     * Affichage du message de confirmation de la commande
     * @param type $params
     * @return type
     */
    public function hookDisplayPaymentReturn($params) 
    {
        if (!$this->active) {
            return;
        }
        
        $this->smarty->assign(
            $this->getTemplateVars()
            );
        return $this->fetch('module:Suncash/views/templates/hook/payment_return.tpl');
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
            Configuration::updateValue('PAYMENT_API_URL', Tools::getValue('PAYMENT_API_URL'));
            Configuration::updateValue('PAYMENT_API_URL_SUCESS', Tools::getValue('PAYMENT_API_URL_SUCESS'));
            Configuration::updateValue('PAYMENT_API_URL_ERROR', Tools::getValue('PAYMENT_API_URL_ERROR'));
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
                    'title' => $this->l('Payment Configuration'),
                    'icon' => 'icon-cogs'
                ],
                'description' => $this->l('Sample configuration form'),
                'input' => [
                   [
                        'type' => 'text',
                        'label' => $this->l('Payment api url'),
                        'name' => 'PAYMENT_API_URL',
                        'required' => true,
                        'empty_message' => $this->l('Please fill the payment api url'),
                   ],
                   [
                        'type' => 'text',
                        'label' => $this->l('Payment api success url'),
                        'name' => 'PAYMENT_API_URL_SUCESS',
                        'required' => true,
                        'empty_message' => $this->l('Please fill the payment api success url'),
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Payment api error url'),
                        'name' => 'PAYMENT_API_URL_ERROR',
                        'required' => true,
                        'empty_message' => $this->l('Please fill the payment api error url'),
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
        $helper->id = 'Suncash';
        $helper->identifier = 'Suncash';
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
            'PAYMENT_API_URL' => Tools::getValue('PAYMENT_API_URL', Configuration::get('PAYMENT_API_URL')),
            'PAYMENT_API_URL_SUCESS' => Tools::getValue('PAYMENT_API_URL_SUCESS', Configuration::get('PAYMENT_API_URL_SUCESS')),
            'PAYMENT_API_URL_ERROR' => Tools::getValue('PAYMENT_API_URL_ERROR', Configuration::get('PAYMENT_API_URL_ERROR')),
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