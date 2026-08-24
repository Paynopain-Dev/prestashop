<?php
/**
 * 2007-2020 PrestaShop
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
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2020 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

include_once(_PS_MODULE_DIR_ . 'paylands/vendor/autoload.php');
include_once(_PS_MODULE_DIR_ . 'paylands/libs/PaymentOrder.php');
include_once(_PS_MODULE_DIR_ . 'paylands/libs/Logger.php');


class Paylands extends \PaymentModule
{
    private $_html = '';
    private $_postErrors = array();
    public $address;

    public function __construct()
    {
        $this->name = 'paylands';
        $this->tab = 'payments_gateways';
        $this->version = '2.0.0';
        $this->author = 'Paylands';
//        $this->controllers = array('payment', 'validation');

        $this->currencies = true;
        $this->currencies_mode = 'checkbox';
        $this->is_eu_compatible = 1;

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('Paylands Gateway');
        $this->description = $this->l('Payment Gateway by https://paynopain.com');
        $this->confirmUninstall = $this->l('Are you sure you want to delete this Payment Gateway?');
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);

        if (!count(Currency::checkPaymentCurrencies($this->id))) {
            $this->warning = $this->l('No currency has been set for this module.');
        }

        if (!Configuration::get('PNP_SERVICE')) {
            $this->warning = $this->l('Service field must be configured to use this module');
        }

        if (!Configuration::get('PNP_SIGNATURE')) {
            $this->warning = $this->l('Signature field must be configured to use this module');
        }

        if (!Configuration::get('PNP_KEY')) {
            $this->warning = $this->l('Api key field must be configured to use this module');
        }
    }

    public function install()
    {
        return parent::install()
            && (_PS_VERSION_ >= 1.7 ? $this->registerHook('paymentOptions') : $this->registerHook('payment'))
            && $this->installTables()
            && $this->installOrderState()
            && Configuration::updateValue('PNP_ENVIRONMENT', '1') //0 PROD , 1 SANDBOX
            && Configuration::updateValue('PNP_SERVICE', '')
            && Configuration::updateValue('PNP_SIGNATURE', '')
            && Configuration::updateValue('PNP_KEY', '')
            && Configuration::updateValue('PNP_SECURE', 0) //0 NO, 1 YES
            && Configuration::updateValue('PNP_SAVE_CARD', 0) // 0 NO, 1 YES
            && Configuration::updateValue('PNP_ORDER_WAITING_STATE', Configuration::get('PAYLANDS_OS_WAITING')) // Awaiting for paylands payment
            && Configuration::updateValue('PNP_ORDER_SUCCESS_STATE', '2') // Payment Accepted
            && Configuration::updateValue('PNP_LOGO', $this->getModuleUrl() . '/views/img/paylands-logo-dark.png') // Media::getMediaPath(_PS_MODULE_DIR_.$this->name.'/paylands-logo-dark.png')) // Context::getContext()->shop->getBaseURL(true))
            && Configuration::updateValue('PNP_FACE_COLOR', "#ef0643")
            && Configuration::updateValue('PNP_FORM_LANG', '');
    }

    public function uninstall()
    {
        return parent::uninstall()
            && $this->uninstallTables()
            && Configuration::deleteByName('PNP_ENVIRONMENT')
            && Configuration::deleteByName('PNP_SERVICE')
            && Configuration::deleteByName('PNP_SIGNATURE')
            && Configuration::deleteByName('PNP_KEY')
            && Configuration::deleteByName('PNP_SECURE')
            && Configuration::deleteByName('PNP_SAVE_CARD')
            && Configuration::deleteByName('PNP_ORDER_WAITING_STATE')
            && Configuration::deleteByName('PNP_ORDER_SUCCESS_STATE')
            && Configuration::deleteByName('PNP_LOGO')
            && Configuration::deleteByName('PNP_FACE_COLOR')
            && Configuration::deleteByName('PNP_FORM_LANG');
    }

    private function installTables()
    {
        $table_orders = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "pnp_paylands_orders` (
			  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
			  `customer_id` VARCHAR(100) NOT NULL,
			  `additional` VARCHAR(500),
			  `order_uuid` VARCHAR(100) NOT NULL,
			  `client_uuid` VARCHAR(100) NOT NULL,
			  `ps_cart_id` INT UNSIGNED NOT NULL,
			  `ps_order_id` INT UNSIGNED,
			  `refunded` VARCHAR(100),
			  `antifraud` VARCHAR(100),
			  `order_token` VARCHAR(500) NOT NULL,
			  `ip` VARCHAR(50),
			  `amount` DECIMAL (10, 2) NOT NULL,
			  `currency` VARCHAR(10) NOT NULL,
			  `status` VARCHAR(11) NOT NULL DEFAULT 'CREATED',
			  `paid` TINYINT(1) NOT NULL,
			  `service` VARCHAR(15) NOT NULL DEFAULT 'REDSYS',
			  `safe` TINYINT(1) NOT NULL,
			  `raw_order` TEXT NOT NULL,
			  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
		    )";

        $table_cards = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "pnp_paylands_cards` (
				`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
				`ps_cart_id` INT UNSIGNED NOT NULL,
				`ps_order_id` INT UNSIGNED,
				`customer_id` INT UNSIGNED NOT NULL,
				`card_uuid` VARCHAR(100) NOT NULL,
				`client_uuid` VARCHAR(100) NOT NULL,
				`type` VARCHAR(10) NOT NULL DEFAULT 'CREDIT',
				`card_token` VARCHAR(500) NOT NULL,
				`last4` VARCHAR(4) NOT NULL,
				`brand` VARCHAR(15) NOT NULL,
				`holder` VARCHAR(50) NOT NULL,
				`bin` INT UNSIGNED NOT NULL,
				`expire_month` VARCHAR(2) NOT NULL,
				`expire_year` VARCHAR(2) NOT NULL,
				`bank` VARCHAR(100),
				`raw_card` TEXT NOT NULL,
				`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
			    )";

        return Db::getInstance()->execute($table_orders) && Db::getInstance()->execute($table_cards);
    }

    private function uninstallTables()
    {
        $table_orders = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'pnp_paylands_orders`';
        $table_cards = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'pnp_paylands_cards`';

        return Db::getInstance()->execute($table_orders) && Db::getInstance()->execute($table_cards);
    }

    /**
     * Create order state
     * @return boolean
     */
    public function installOrderState()
    {
        if (!Configuration::get('PAYLANDS_OS_WAITING')
            || !Validate::isLoadedObject(new OrderState(Configuration::get('PAYLANDS_OS_WAITING')))) {
            $order_state = new OrderState();
            $order_state->name = array();
            foreach (Language::getLanguages() as $language) {
                if (Tools::strtolower($language['iso_code']) == 'fr') {
                    $order_state->name[$language['id_lang']] = 'En attente de paiement Paylands';
                } elseif (Tools::strtolower($language['iso_code']) == 'es') {
                    $order_state->name[$language['id_lang']] = 'Esperando por Pago Paylands';
                } else {
                    $order_state->name[$language['id_lang']] = 'Awaiting for Paylands Payment';
                }
            }
            $order_state->send_email = false;
            $order_state->color = '#4169E1';
            $order_state->hidden = false;
            $order_state->delivery = false;
            $order_state->logable = false;
            $order_state->invoice = false;
            $order_state->module_name = $this->name;
            if ($order_state->add()) {
                $source = _PS_MODULE_DIR_ . 'paylands/views/img/paylands_logo_mini.gif';
                $destination = _PS_ROOT_DIR_ . '/img/os/' . (int)$order_state->id . '.gif';
                copy($source, $destination);
            }

            if (Shop::isFeatureActive()) {
                $shops = Shop::getShops();
                foreach ($shops as $shop) {
                    Configuration::updateValue('PAYLANDS_OS_WAITING', (int)$order_state->id, false, null, (int)$shop['id_shop']);
                }
            } else {
                Configuration::updateValue('PAYLANDS_OS_WAITING', (int)$order_state->id);
            }
        }

        return true;
    }

    private function _postValidation()
    {
        if (Tools::isSubmit('btnSubmit')) {
            if (!Tools::getValue('PNP_SERVICE')) {
                $this->_postErrors[] = $this->l('The "Service" field is required.');
            } elseif (!Tools::getValue('PNP_SIGNATURE')) {
                $this->_postErrors[] = $this->l('The "Signature" field is required.');
            }
        }
    }

    private function _postProcess()
    {
        if (Tools::isSubmit('btnSubmit')) {
            Configuration::updateValue('PNP_ENVIRONMENT', Tools::getValue('PNP_ENVIRONMENT'));
            Configuration::updateValue('PNP_SERVICE', Tools::getValue('PNP_SERVICE'));
            Configuration::updateValue('PNP_SIGNATURE', Tools::getValue('PNP_SIGNATURE'));
            Configuration::updateValue('PNP_KEY', Tools::getValue('PNP_KEY'));
            Configuration::updateValue('PNP_SECURE', Tools::getValue('PNP_SECURE'));
            Configuration::updateValue('PNP_SAVE_CARD', Tools::getValue('PNP_SAVE_CARD'));
            Configuration::updateValue('PNP_ORDER_WAITING_STATE', Tools::getValue('PNP_ORDER_WAITING_STATE'));
            Configuration::updateValue('PNP_ORDER_SUCCESS_STATE', Tools::getValue('PNP_ORDER_SUCCESS_STATE'));
            Configuration::updateValue('PNP_LOGO', Tools::getValue('PNP_LOGO'));
            Configuration::updateValue('PNP_FACE_COLOR', Tools::getValue('PNP_FACE_COLOR'));
            Configuration::updateValue('PNP_FORM_LANG', Tools::getValue('PNP_FORM_LANG'));
        }
        $this->_html .= $this->displayConfirmation($this->l('Settings updated'));
    }

    private function _displayCheck()
    {
        return $this->display(__FILE__, './views/templates/hook/infos.tpl');
    }

    /**
     * @return string
     */
    public function getRealIpAddr()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP']))   //check ip from share internet
        {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))   //to check ip is pass from proxy
        {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return $ip;
    }

    public function createPaylandsOrder($order, $id_cart, $id_order = null)
    {
        $now = date("Y-m-d H:i:s");
        $db = \Db::getInstance();
        return $db->insert("pnp_paylands_orders", array(
            'customer_id' => pSQL($order['order']['customer']),
            'additional' => pSQL($order['order']['additional']),
            'order_uuid' => pSQL($order['order']['uuid']),
            'client_uuid' => pSQL($order['client']['uuid']),
            'ps_cart_id' => (int)$id_cart,
            'ps_order_id' => (int)$id_order,
            'refunded' => pSQL($order['order']['refunded']),
            'antifraud' => !empty($order['order']['antifraud']) ? pSQL($order['order']['antifraud']) : null,
            'order_token' => pSQL($order['order']['token']),
            'ip' => pSQL($order['order']['ip']),
            'amount' => $order['order']['amount'],
            'currency' => pSQL($order['order']['currency']),
            'status' => pSQL($order['order']['status']),
            'paid' => (int)$order['order']['paid'],
            'service' => pSQL($order['order']['service']),
            'safe' => (int)$order['order']['safe'],
            'raw_order' => pSQL(json_encode($order)),
            'created_at' => pSQL($now),
            'updated_at' => pSQL($now)
        ));
    }

    public function createPaylandsUserCard($data, $id_cart, $id_order)
    {
        $now = date("Y-m-d H:i:s");
        $card = $data['order']['transactions'][0]['source'];

        $sql = new DbQuery();
        $sql->select('card_token')
            ->from('pnp_paylands_cards')
            ->where('card_token = "' . pSQL($card['token']) . '"');
        $user_card = \Db::getInstance()->getRow($sql);

        if (!empty($user_card)) {
            return;
        }

        $db = \Db::getInstance();
        return $db->insert("pnp_paylands_cards", array(
            'ps_cart_id' => (int)$id_cart,
            'ps_order_id' => (int)$id_order,
            'customer_id' => pSQL($data['order']['customer']),
            'card_uuid' => pSQL($card['uuid']),
            'client_uuid' => pSQL($data['client']['uuid']),
            'type' => pSQL($card['type']),
            'card_token' => pSQL($card['token']),
            'last4' => pSQL($card['last4']),
            'brand' => pSQL($card['brand']),
            'holder' => pSQL($card['holder']),
            'bin' => (int)$card['bin'],
            'expire_month' => pSQL($card['expire_month']),
            'expire_year' => pSQL($card['expire_year']),
            'bank' => pSQL($card['bank']),
            'raw_card' => pSQL(json_encode($card)),
            'created_at' => pSQL($now),
            'updated_at' => pSQL($now)
        ));
    }

    public function getContent()
    {
        $this->_html = '';

        if (Tools::isSubmit('btnSubmit')) {
            $this->_postValidation();
            if (!count($this->_postErrors)) {
                $this->_postProcess();
            } else {
                foreach ($this->_postErrors as $err) {
                    $this->_html .= $this->displayError($err);
                }
            }
        }

        $this->_html .= $this->_displayCheck();
        $this->_html .= $this->renderForm();

        return $this->_html;
    }

    public function getOrderStatuses()
    {
        $prestashopOrderStatuses = OrderState::getOrderStates($this->context->language->id);

        $orderStatuses = array();
        foreach ($prestashopOrderStatuses as $prestashopOrderStatus) {
            $orderStatuses[] = array(
                'id_option' => $prestashopOrderStatus['id_order_state'],
                'name' => $prestashopOrderStatus['name']
            );
        }

        return $orderStatuses;
    }

    public function getModuleUrl()
    {
        return Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/' . $this->name;
    }

    /**
     * Prestashop 1.7 hook payment
     */
    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return;
        }

        if (!$this->checkCurrency($params['cart'])) {
            return;
        }

        $newOption = new \PrestaShop\PrestaShop\Core\Payment\PaymentOption();
        $newOption->setModuleName($this->name)
            ->setCallToActionText($this->l('Pay with Card Safely'))
            ->setAction($this->context->link->getModuleLink($this->name, 'validation', array(), true))
            ->setLogo('https://paylands-web-assets.s3.eu-west-1.amazonaws.com/logos/visa-mc-small.png');

        return array($newOption);
    }

    /**
     * Prestashop 1.6 hook payment
     */
    public function hookPayment($params)
    {
        if (!$this->active) {
            return;
        }

        if (!$this->checkCurrency($params['cart'])) {
            return;
        }

        return $this->display(__FILE__, 'payment.tpl');
    }

    public function checkCurrency($cart)
    {
        $currency_order = new Currency((int)($cart->id_currency));
        $currencies_module = $this->getCurrency((int)$cart->id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }
        return false;
    }

    public function renderForm()
    {
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Paylands Configuration'),
                    'image' => '../modules/paylands/views/img/paylands-logo.png'
                ),
                'input' => array(
                    array(
                        'type' => 'radio',
                        'label' => $this->l('Environment'),
                        'name' => 'PNP_ENVIRONMENT',
                        'required' => true,
                        'values' => array(
                            array(
                                'id' => 'prod_mod',
                                'value' => 0,
                                'label' => $this->l('Production Mode')
                            ),
                            array(
                                'id' => 'sandbox_mode',
                                'value' => 1,
                                'label' => $this->l('Sandbox Mode')
                            )
                        ),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Service'),
                        'name' => 'PNP_SERVICE',
                        'required' => true
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Signature'),
                        'name' => 'PNP_SIGNATURE',
                        'required' => true
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Api key'),
                        'name' => 'PNP_KEY',
                        'required' => true
                    ),
                    array(
                        'type' => 'radio',
                        'label' => $this->l('Secure Payment'),
                        'name' => 'PNP_SECURE',
                        'required' => true,
                        'values' => array(
                            array(
                                'id' => 'secure_yes',
                                'value' => 0,
                                'label' => $this->l('No')
                            ),
                            array(
                                'id' => 'secure_no',
                                'value' => 1,
                                'label' => $this->l('Yes')
                            )
                        )
                    ),
                    array(
                        'type' => 'radio',
                        'label' => $this->l('Remember Cards'),
                        'name' => 'PNP_SAVE_CARD',
                        'required' => true,
                        'values' => array(
                            array(
                                'id' => 'save_cards_yes',
                                'value' => 0,
                                'label' => $this->l('No')
                            ),
                            array(
                                'id' => 'save_cards_no',
                                'value' => 1,
                                'label' => $this->l('Yes')
                            )
                        )
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Order Waiting State'),
                        'name' => 'PNP_ORDER_WAITING_STATE',
                        'required' => true,
                        'options' => array(
                            'query' => $this->getOrderStatuses(),
                            'id' => 'id_option',
                            'name' => 'name'
                        )
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Order Success State'),
                        'name' => 'PNP_ORDER_SUCCESS_STATE',
                        'required' => true,
                        'options' => array(
                            'query' => $this->getOrderStatuses(),
                            'id' => 'id_option',
                            'name' => 'name'
                        )
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Payment Form Logo'),
                        'name' => 'PNP_LOGO',
                        'required' => true
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Payment Text Color'),
                        'name' => 'PNP_FACE_COLOR',
                        'required' => true
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Payment Form Language'),
                        'name' => 'PNP_FORM_LANG',
                        'required' => true,
                        'options' => array(
                            'query' => array(
                                array(
                                    'id_option' => 'store',
                                    'name' => $this->l('Store Lang')
                                ),
                                array(
                                    'id_option' => 'en',
                                    'name' => $this->l('English')
                                ),
                                array(
                                    'id_option' => 'es',
                                    'name' => $this->l('Spanish')
                                ),
                                array(
                                    'id_option' => 'de',
                                    'name' => $this->l('Dutch')
                                ),
                                array(
                                    'id_option' => 'pt',
                                    'name' => $this->l('Portuguese')
                                ),
                                array(
                                    'id_option' => 'fr',
                                    'name' => $this->l('French')
                                ),
                            ),
                            'id' => 'id_option',
                            'name' => 'name'
                        )
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                )
            ),
        );

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->module = $this;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'btnSubmit';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
        );

        $this->fields_form = array();

        return $helper->generateForm(array($fields_form));
    }

    public function getConfigFieldsValues()
    {
        return array(
            'PNP_ENVIRONMENT' => Tools::getValue('PNP_ENVIRONMENT', Configuration::get('PNP_ENVIRONMENT')),
            'PNP_SERVICE' => Tools::getValue('PNP_SERVICE', Configuration::get('PNP_SERVICE')),
            'PNP_SIGNATURE' => Tools::getValue('PNP_SIGNATURE', Configuration::get('PNP_SIGNATURE')),
            'PNP_KEY' => Tools::getValue('PNP_KEY', Configuration::get('PNP_KEY')),
            'PNP_SECURE' => Tools::getValue('PNP_SECURE', Configuration::get('PNP_SECURE')),
            'PNP_SAVE_CARD' => Tools::getValue('PNP_SAVE_CARD', Configuration::get('PNP_SAVE_CARD')),
            'PNP_ORDER_WAITING_STATE' => Tools::getValue('PNP_ORDER_WAITING_STATE', Configuration::get('PNP_ORDER_WAITING_STATE')),
            'PNP_ORDER_SUCCESS_STATE' => Tools::getValue('PNP_ORDER_SUCCESS_STATE', Configuration::get('PNP_ORDER_SUCCESS_STATE')),
            'PNP_LOGO' => Tools::getValue('PNP_LOGO', Configuration::get('PNP_LOGO')),
            'PNP_FACE_COLOR' => Tools::getValue('PNP_FACE_COLOR', Configuration::get('PNP_FACE_COLOR')),
            'PNP_FORM_LANG' => Tools::getValue('PNP_FORM_LANG', Configuration::get('PNP_FORM_LANG')),
        );
    }
}
