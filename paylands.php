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
        $this->version = '3.0.0';
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
            && Configuration::updateValue('PNP_FORM_LANG', '')
            && Configuration::updateValue('PNP_SERVICE_NAME', 'Pay with Card Safely')
            && Configuration::updateValue('PNP_BIZUM_ENABLED', 0)
            && Configuration::updateValue('PNP_BIZUM_SERVICE', '')
            && Configuration::updateValue('PNP_BIZUM_NAME', 'Pay with Bizum')
            && Configuration::updateValue('PNP_MODE', 0)
            && Configuration::updateValue('PNP_CHECKOUT_UUID', '')
            && Configuration::updateValue('PNP_CHECKOUT_NAME', 'Pay Securely')
            && Configuration::updateValue('PNP_CHECKOUT_METHODS_CARD', 1)
            && Configuration::updateValue('PNP_CHECKOUT_METHODS_BIZUM', 0)
            && Configuration::updateValue('PNP_CHECKOUT_METHODS_SOFORT', 0)
            && Configuration::updateValue('PNP_CHECKOUT_METHODS_IDEAL', 0)
            && Configuration::updateValue('PNP_CHECKOUT_METHODS_CRYPTO', 0)
            && Configuration::updateValue('PNP_CHECKOUT_METHODS_PIX', 0)
            && Configuration::updateValue('PNP_CHECKOUT_METHODS_VIACASH', 0)
            && Configuration::updateValue('PNP_CHECKOUT_METHODS_KLARNA', 0)
            && Configuration::updateValue('PNP_CHECKOUT_METHODS_GIROPAY', 0)
            && Configuration::updateValue('PNP_CHECKOUT_METHODS_GPAY', 0)
            && Configuration::updateValue('PNP_CHECKOUT_METHODS_APAY', 0)
            && Configuration::updateValue('PNP_CHECKOUT_METHODS_C2PAY', 0)
            && Configuration::updateValue('PNP_CHECKOUT_DEFAULT_METHOD', 'PAYMENT_CARD')
            && Configuration::updateValue('PNP_CHECKOUT_THEME', 'LIGHT')
            && Configuration::updateValue('PNP_CHECKOUT_FONT', '')
            && Configuration::updateValue('PNP_CHECKOUT_DESCRIPTION', '')
            && Configuration::updateValue('PNP_KEY_PROD', '')
            && Configuration::updateValue('PNP_KEY_SANDBOX', '')
            && Configuration::updateValue('PNP_SIGNATURE_PROD', '')
            && Configuration::updateValue('PNP_SIGNATURE_SANDBOX', '')
            && Configuration::updateValue('PNP_SERVICE_PROD', '')
            && Configuration::updateValue('PNP_SERVICE_SANDBOX', '')
            && Configuration::updateValue('PNP_SERVICE_NAME_PROD', 'Pay with Card Safely')
            && Configuration::updateValue('PNP_SERVICE_NAME_SANDBOX', 'Pay with Card Safely')
            && Configuration::updateValue('PNP_BIZUM_SERVICE_PROD', '')
            && Configuration::updateValue('PNP_BIZUM_SERVICE_SANDBOX', '')
            && Configuration::updateValue('PNP_BIZUM_NAME_PROD', 'Pay with Bizum')
            && Configuration::updateValue('PNP_BIZUM_NAME_SANDBOX', 'Pay with Bizum')
            && Configuration::updateValue('PNP_CHECKOUT_UUID_PROD', '')
            && Configuration::updateValue('PNP_CHECKOUT_UUID_SANDBOX', '')
            && Configuration::updateValue('PNP_CHECKOUT_NAME_PROD', 'Pay Securely')
            && Configuration::updateValue('PNP_CHECKOUT_NAME_SANDBOX', 'Pay Securely');
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
            && Configuration::deleteByName('PNP_FORM_LANG')
            && Configuration::deleteByName('PNP_SERVICE_NAME')
            && Configuration::deleteByName('PNP_BIZUM_ENABLED')
            && Configuration::deleteByName('PNP_BIZUM_SERVICE')
            && Configuration::deleteByName('PNP_BIZUM_NAME')
            && Configuration::deleteByName('PNP_MODE')
            && Configuration::deleteByName('PNP_CHECKOUT_UUID')
            && Configuration::deleteByName('PNP_CHECKOUT_NAME')
            && Configuration::deleteByName('PNP_CHECKOUT_METHODS_CARD')
            && Configuration::deleteByName('PNP_CHECKOUT_METHODS_BIZUM')
            && Configuration::deleteByName('PNP_CHECKOUT_METHODS_SOFORT')
            && Configuration::deleteByName('PNP_CHECKOUT_METHODS_IDEAL')
            && Configuration::deleteByName('PNP_CHECKOUT_METHODS_CRYPTO')
            && Configuration::deleteByName('PNP_CHECKOUT_METHODS_PIX')
            && Configuration::deleteByName('PNP_CHECKOUT_METHODS_VIACASH')
            && Configuration::deleteByName('PNP_CHECKOUT_METHODS_KLARNA')
            && Configuration::deleteByName('PNP_CHECKOUT_METHODS_GIROPAY')
            && Configuration::deleteByName('PNP_CHECKOUT_METHODS_GPAY')
            && Configuration::deleteByName('PNP_CHECKOUT_METHODS_APAY')
            && Configuration::deleteByName('PNP_CHECKOUT_METHODS_C2PAY')
            && Configuration::deleteByName('PNP_CHECKOUT_DEFAULT_METHOD')
            && Configuration::deleteByName('PNP_CHECKOUT_THEME')
            && Configuration::deleteByName('PNP_CHECKOUT_FONT')
            && Configuration::deleteByName('PNP_CHECKOUT_DESCRIPTION')
            && Configuration::deleteByName('PNP_KEY_PROD')
            && Configuration::deleteByName('PNP_KEY_SANDBOX')
            && Configuration::deleteByName('PNP_SIGNATURE_PROD')
            && Configuration::deleteByName('PNP_SIGNATURE_SANDBOX')
            && Configuration::deleteByName('PNP_SERVICE_PROD')
            && Configuration::deleteByName('PNP_SERVICE_SANDBOX')
            && Configuration::deleteByName('PNP_SERVICE_NAME_PROD')
            && Configuration::deleteByName('PNP_SERVICE_NAME_SANDBOX')
            && Configuration::deleteByName('PNP_BIZUM_SERVICE_PROD')
            && Configuration::deleteByName('PNP_BIZUM_SERVICE_SANDBOX')
            && Configuration::deleteByName('PNP_BIZUM_NAME_PROD')
            && Configuration::deleteByName('PNP_BIZUM_NAME_SANDBOX')
            && Configuration::deleteByName('PNP_CHECKOUT_UUID_PROD')
            && Configuration::deleteByName('PNP_CHECKOUT_UUID_SANDBOX')
            && Configuration::deleteByName('PNP_CHECKOUT_NAME_PROD')
            && Configuration::deleteByName('PNP_CHECKOUT_NAME_SANDBOX');
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
            $envSuffix = (int)Tools::getValue('PNP_ENVIRONMENT') ? '_SANDBOX' : '_PROD';
            if (!Tools::getValue('PNP_SERVICE' . $envSuffix)) {
                $this->_postErrors[] = $this->l('The "Service" field is required.');
            } elseif (!Tools::getValue('PNP_SIGNATURE' . $envSuffix)) {
                $this->_postErrors[] = $this->l('The "Signature" field is required.');
            }
        }
    }

    private function _postProcess()
    {
        if (Tools::isSubmit('btnSubmit')) {
            Configuration::updateValue('PNP_ENVIRONMENT', Tools::getValue('PNP_ENVIRONMENT'));
            Configuration::updateValue('PNP_SECURE', Tools::getValue('PNP_SECURE'));
            Configuration::updateValue('PNP_SAVE_CARD', Tools::getValue('PNP_SAVE_CARD'));
            Configuration::updateValue('PNP_ORDER_WAITING_STATE', Tools::getValue('PNP_ORDER_WAITING_STATE'));
            Configuration::updateValue('PNP_ORDER_SUCCESS_STATE', Tools::getValue('PNP_ORDER_SUCCESS_STATE'));
            Configuration::updateValue('PNP_LOGO', Tools::getValue('PNP_LOGO'));
            Configuration::updateValue('PNP_FACE_COLOR', Tools::getValue('PNP_FACE_COLOR'));
            Configuration::updateValue('PNP_FORM_LANG', Tools::getValue('PNP_FORM_LANG'));
            Configuration::updateValue('PNP_BIZUM_ENABLED', Tools::getValue('PNP_BIZUM_ENABLED') ? 1 : 0);
            Configuration::updateValue('PNP_MODE', (int)Tools::getValue('PNP_MODE'));
            // Per-environment credentials
            Configuration::updateValue('PNP_KEY_PROD',            Tools::getValue('PNP_KEY_PROD'));
            Configuration::updateValue('PNP_KEY_SANDBOX',         Tools::getValue('PNP_KEY_SANDBOX'));
            Configuration::updateValue('PNP_SIGNATURE_PROD',      Tools::getValue('PNP_SIGNATURE_PROD'));
            Configuration::updateValue('PNP_SIGNATURE_SANDBOX',   Tools::getValue('PNP_SIGNATURE_SANDBOX'));
            Configuration::updateValue('PNP_SERVICE_PROD',        Tools::getValue('PNP_SERVICE_PROD'));
            Configuration::updateValue('PNP_SERVICE_SANDBOX',     Tools::getValue('PNP_SERVICE_SANDBOX'));
            Configuration::updateValue('PNP_SERVICE_NAME_PROD',   Tools::getValue('PNP_SERVICE_NAME_PROD'));
            Configuration::updateValue('PNP_SERVICE_NAME_SANDBOX',Tools::getValue('PNP_SERVICE_NAME_SANDBOX'));
            Configuration::updateValue('PNP_BIZUM_SERVICE_PROD',   Tools::getValue('PNP_BIZUM_SERVICE_PROD'));
            Configuration::updateValue('PNP_BIZUM_SERVICE_SANDBOX',Tools::getValue('PNP_BIZUM_SERVICE_SANDBOX'));
            Configuration::updateValue('PNP_BIZUM_NAME_PROD',     Tools::getValue('PNP_BIZUM_NAME_PROD'));
            Configuration::updateValue('PNP_BIZUM_NAME_SANDBOX',  Tools::getValue('PNP_BIZUM_NAME_SANDBOX'));
            Configuration::updateValue('PNP_CHECKOUT_UUID_PROD',  Tools::getValue('PNP_CHECKOUT_UUID_PROD'));
            Configuration::updateValue('PNP_CHECKOUT_UUID_SANDBOX',Tools::getValue('PNP_CHECKOUT_UUID_SANDBOX'));
            Configuration::updateValue('PNP_CHECKOUT_NAME_PROD',  Tools::getValue('PNP_CHECKOUT_NAME_PROD'));
            Configuration::updateValue('PNP_CHECKOUT_NAME_SANDBOX',Tools::getValue('PNP_CHECKOUT_NAME_SANDBOX'));
            // Copy active environment's credentials to the base keys used by runtime code
            $envSuffix = (int)Tools::getValue('PNP_ENVIRONMENT') ? '_SANDBOX' : '_PROD';
            Configuration::updateValue('PNP_KEY',          Tools::getValue('PNP_KEY'          . $envSuffix));
            Configuration::updateValue('PNP_SIGNATURE',    Tools::getValue('PNP_SIGNATURE'    . $envSuffix));
            Configuration::updateValue('PNP_SERVICE',      Tools::getValue('PNP_SERVICE'      . $envSuffix));
            Configuration::updateValue('PNP_SERVICE_NAME', Tools::getValue('PNP_SERVICE_NAME' . $envSuffix));
            Configuration::updateValue('PNP_BIZUM_SERVICE',Tools::getValue('PNP_BIZUM_SERVICE'. $envSuffix));
            Configuration::updateValue('PNP_BIZUM_NAME',   Tools::getValue('PNP_BIZUM_NAME'   . $envSuffix));
            Configuration::updateValue('PNP_CHECKOUT_UUID',Tools::getValue('PNP_CHECKOUT_UUID'. $envSuffix));
            Configuration::updateValue('PNP_CHECKOUT_NAME',Tools::getValue('PNP_CHECKOUT_NAME'. $envSuffix));
            Configuration::updateValue('PNP_CHECKOUT_METHODS_CARD',    Tools::getValue('PNP_CHECKOUT_METHODS_CARD')    ? 1 : 0);
            Configuration::updateValue('PNP_CHECKOUT_METHODS_BIZUM',   Tools::getValue('PNP_CHECKOUT_METHODS_BIZUM')   ? 1 : 0);
            Configuration::updateValue('PNP_CHECKOUT_METHODS_SOFORT',  Tools::getValue('PNP_CHECKOUT_METHODS_SOFORT')  ? 1 : 0);
            Configuration::updateValue('PNP_CHECKOUT_METHODS_IDEAL',   Tools::getValue('PNP_CHECKOUT_METHODS_IDEAL')   ? 1 : 0);
            Configuration::updateValue('PNP_CHECKOUT_METHODS_CRYPTO',  Tools::getValue('PNP_CHECKOUT_METHODS_CRYPTO')  ? 1 : 0);
            Configuration::updateValue('PNP_CHECKOUT_METHODS_PIX',     Tools::getValue('PNP_CHECKOUT_METHODS_PIX')     ? 1 : 0);
            Configuration::updateValue('PNP_CHECKOUT_METHODS_VIACASH', Tools::getValue('PNP_CHECKOUT_METHODS_VIACASH') ? 1 : 0);
            Configuration::updateValue('PNP_CHECKOUT_METHODS_KLARNA',  Tools::getValue('PNP_CHECKOUT_METHODS_KLARNA')  ? 1 : 0);
            Configuration::updateValue('PNP_CHECKOUT_METHODS_GIROPAY', Tools::getValue('PNP_CHECKOUT_METHODS_GIROPAY') ? 1 : 0);
            Configuration::updateValue('PNP_CHECKOUT_METHODS_GPAY',    Tools::getValue('PNP_CHECKOUT_METHODS_GPAY')    ? 1 : 0);
            Configuration::updateValue('PNP_CHECKOUT_METHODS_APAY',    Tools::getValue('PNP_CHECKOUT_METHODS_APAY')    ? 1 : 0);
            Configuration::updateValue('PNP_CHECKOUT_METHODS_C2PAY',   Tools::getValue('PNP_CHECKOUT_METHODS_C2PAY')   ? 1 : 0);
            Configuration::updateValue('PNP_CHECKOUT_DEFAULT_METHOD', Tools::getValue('PNP_CHECKOUT_DEFAULT_METHOD'));
            Configuration::updateValue('PNP_CHECKOUT_THEME', Tools::getValue('PNP_CHECKOUT_THEME'));
            Configuration::updateValue('PNP_CHECKOUT_FONT', Tools::getValue('PNP_CHECKOUT_FONT'));
            Configuration::updateValue('PNP_CHECKOUT_DESCRIPTION', Tools::getValue('PNP_CHECKOUT_DESCRIPTION'));
        }
        $this->_html .= $this->displayConfirmation($this->l('Settings updated'));
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

    /**
     * Verify the validation_hash carried by a Paylands notification.
     *
     * The hash is the sha256 of the JSON encoded {order, client, extra_data} block
     * concatenated with the service signature. extra_data is only part of the signed
     * block when the notification actually carries it.
     *
     * @see https://docs.paylands.com/en/docs/notification-validation/
     *
     * @param string $raw_body raw request body exactly as received from Paylands
     * @return bool
     */
    public function verifyNotification($raw_body)
    {
        $signature = Configuration::get('PNP_SIGNATURE');
        if (empty($signature)) {
            \Logger::error('PNP_NOTIFICATION#REJECTED:no signature configured');
            return false;
        }

        $payload = json_decode($raw_body);
        if (!is_object($payload) || empty($payload->validation_hash)) {
            \Logger::error('PNP_NOTIFICATION#REJECTED:missing validation_hash');
            return false;
        }

        $signed = array(
            'order' => isset($payload->order) ? $payload->order : null,
            'client' => isset($payload->client) ? $payload->client : null,
        );
        if (property_exists($payload, 'extra_data')) {
            $signed['extra_data'] = $payload->extra_data;
        }

        $expected = hash(
            'sha256',
            json_encode($signed, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . $signature
        );

        if (!hash_equals($expected, (string)$payload->validation_hash)) {
            \Logger::error('PNP_NOTIFICATION#REJECTED:validation_hash mismatch');
            return false;
        }

        return true;
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
        $errors = array();
        $success = false;

        if (Tools::isSubmit('btnSubmit')) {
            $this->_postValidation();
            $errors = $this->_postErrors;
            if (empty($errors)) {
                $this->_postProcess();
                $success = true;
            }
        }

        $this->context->smarty->assign(array(
            'form_action' => $this->context->link->getAdminLink('AdminModules', false)
                . '&configure=' . $this->name
                . '&tab_module=' . $this->tab
                . '&module_name=' . $this->name,
            'token'          => Tools::getAdminTokenLite('AdminModules'),
            'config'         => $this->getConfigFieldsValues(),
            'order_statuses' => $this->getOrderStatuses(),
            'success'        => $success,
            'errors'         => $errors,
            'module_dir'     => $this->_path,
            'form_languages' => array(
                array('value' => 'store', 'label' => $this->l('Store language')),
                array('value' => 'en',    'label' => $this->l('English')),
                array('value' => 'es',    'label' => $this->l('Spanish')),
                array('value' => 'ca',    'label' => $this->l('Catalan')),
                array('value' => 'de',    'label' => $this->l('German')),
                array('value' => 'pt',    'label' => $this->l('Portuguese')),
                array('value' => 'fr',    'label' => $this->l('French')),
            ),
            'payment_methods' => array(
                array('config_key' => 'PNP_CHECKOUT_METHODS_CARD',    'api_value' => 'PAYMENT_CARD', 'label' => $this->l('Payment Card'),  ),
                array('config_key' => 'PNP_CHECKOUT_METHODS_GPAY',    'api_value' => 'GOOGLE_PAY',   'label' => $this->l('Google Pay'),    ),
                array('config_key' => 'PNP_CHECKOUT_METHODS_APAY',    'api_value' => 'APPLE_PAY',    'label' => $this->l('Apple Pay'),     ),
                array('config_key' => 'PNP_CHECKOUT_METHODS_BIZUM',   'api_value' => 'BIZUM',        'label' => $this->l('Bizum'),         ),
                array('config_key' => 'PNP_CHECKOUT_METHODS_MBWAY',  'api_value' => 'MBWAY',       'label' => $this->l('MB WAY'),        ),
                array('config_key' => 'PNP_CHECKOUT_METHODS_MULTIBANCO',  'api_value' => 'MULTIBANCO',       'label' => $this->l('Multibanco'),        ),
                array('config_key' => 'PNP_CHECKOUT_METHODS_BANKTRANSFER',  'api_value' => 'TRANSFER',       'label' => $this->l('Bank Transfer'),        ),
                array('config_key' => 'PNP_CHECKOUT_METHODS_PAYPAL',  'api_value' => 'PAYPAL',       'label' => $this->l('PayPal'),        ),
                array('config_key' => 'PNP_CHECKOUT_METHODS_KLARNA',  'api_value' => 'KLARNA',       'label' => $this->l('Klarna'),        ),
                array('config_key' => 'PNP_CHECKOUT_METHODS_REVOLUT_PAY',  'api_value' => 'REVOLUT_PAY',       'label' => $this->l('Revolut Pay'),        ),
                array('config_key' => 'PNP_CHECKOUT_METHODS_C2PAY',   'api_value' => 'CLICKTOPAY',   'label' => $this->l('Click to Pay'),  ),
            ),
        ));

        return $this->display(__FILE__, 'views/templates/admin/config.tpl');
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
        if (!$this->active || !$this->checkCurrency($params['cart'])) {
            return;
        }

        $options = array();
        $isCheckoutMode = (int)Configuration::get('PNP_MODE') === 1;

        if ($isCheckoutMode) {
            // Checkout mode: show a single unified payment button (no separate Bizum)
            $checkoutName = Configuration::get('PNP_CHECKOUT_NAME') ?: $this->l('Pay Securely');
            $checkout = new \PrestaShop\PrestaShop\Core\Payment\PaymentOption();
            $checkout->setModuleName($this->name)
                ->setCallToActionText($checkoutName)
                ->setAction($this->context->link->getModuleLink($this->name, 'validation', array(), true))
                ->setLogo('https://paylands-web-assets.s3.eu-west-1.amazonaws.com/logos/visa-mc-small.png');
            $options[] = $checkout;
        } else {
            // Service UUID mode: card button + optional Bizum button
            $cardName = Configuration::get('PNP_SERVICE_NAME') ?: $this->l('Pay with Card Safely');
            $card = new \PrestaShop\PrestaShop\Core\Payment\PaymentOption();
            $card->setModuleName($this->name)
                ->setCallToActionText($cardName)
                ->setAction($this->context->link->getModuleLink($this->name, 'validation', array(), true))
                ->setLogo('https://paylands-web-assets.s3.eu-west-1.amazonaws.com/logos/visa-mc-small.png');
            $options[] = $card;

            if ((int)Configuration::get('PNP_BIZUM_ENABLED') && Configuration::get('PNP_BIZUM_SERVICE')) {
                $bizumName = Configuration::get('PNP_BIZUM_NAME') ?: $this->l('Pay with Bizum');
                $bizum = new \PrestaShop\PrestaShop\Core\Payment\PaymentOption();
                $bizum->setModuleName($this->name)
                    ->setCallToActionText($bizumName)
                    ->setAction($this->context->link->getModuleLink($this->name, 'validation', array('method' => 'bizum'), true))
                    ->setLogo('https://paylands-web-assets.s3.eu-west-1.amazonaws.com/logos/bizum.png');
                $options[] = $bizum;
            }
        }

        return $options;
    }

    /**
     * Prestashop 1.6 hook payment
     */
    public function hookPayment($params)
    {
        if (!$this->active || !$this->checkCurrency($params['cart'])) {
            return;
        }

        $entries = array();
        $isCheckoutMode = (int)Configuration::get('PNP_MODE') === 1;

        if ($isCheckoutMode) {
            // Checkout mode: single unified payment button (no separate Bizum)
            $entries[] = array(
                'id'   => 'checkout',
                'name' => Configuration::get('PNP_CHECKOUT_NAME') ?: $this->l('Pay Securely'),
                'url'  => $this->context->link->getModuleLink($this->name, 'payment', array(), true),
                'logo' => $this->_path . 'logo.png',
            );
        } else {
            // Service UUID mode: card + optional Bizum
            $entries[] = array(
                'id'   => 'card',
                'name' => Configuration::get('PNP_SERVICE_NAME') ?: $this->l('Pay with Card Safely'),
                'url'  => $this->context->link->getModuleLink($this->name, 'payment', array(), true),
                'logo' => $this->_path . 'logo.png',
            );

            if ((int)Configuration::get('PNP_BIZUM_ENABLED') && Configuration::get('PNP_BIZUM_SERVICE')) {
                $entries[] = array(
                    'id'   => 'bizum',
                    'name' => Configuration::get('PNP_BIZUM_NAME') ?: $this->l('Pay with Bizum'),
                    'url'  => $this->context->link->getModuleLink($this->name, 'payment', array('method' => 'bizum'), true),
                    'logo' => $this->_path . 'views/img/bizum.png',
                );
            }
        }

        $this->context->smarty->assign('pnp_payment_entries', $entries);
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

    /**
     * ISO code the hosted payment form should be rendered in.
     *
     * @return string
     */
    public function getFormLanguage()
    {
        $lang = Configuration::get('PNP_FORM_LANG');
        if (empty($lang) || $lang == 'store') {
            $lang = Context::getContext()->language->iso_code;
        }

        return $lang;
    }

    public function getCheckoutExtraData()
    {
        if (!(int)Configuration::get('PNP_MODE')) {
            return array();
        }

        $method_map = array(
            'PNP_CHECKOUT_METHODS_CARD'    => 'PAYMENT_CARD',
            'PNP_CHECKOUT_METHODS_BIZUM'   => 'BIZUM',
            'PNP_CHECKOUT_METHODS_SOFORT'  => 'SOFORT',
            'PNP_CHECKOUT_METHODS_IDEAL'   => 'IDEAL',
            'PNP_CHECKOUT_METHODS_CRYPTO'  => 'CRYPTO',
            'PNP_CHECKOUT_METHODS_PIX'     => 'PIX',
            'PNP_CHECKOUT_METHODS_VIACASH' => 'VIACASH',
            'PNP_CHECKOUT_METHODS_KLARNA'  => 'KLARNA',
            'PNP_CHECKOUT_METHODS_GIROPAY' => 'GIROPAY',
            'PNP_CHECKOUT_METHODS_GPAY'    => 'GOOGLE_PAY',
            'PNP_CHECKOUT_METHODS_APAY'    => 'APPLE_PAY',
            'PNP_CHECKOUT_METHODS_C2PAY'   => 'CLICKTOPAY',
        );

        $payment_methods = array();
        foreach ($method_map as $config_key => $api_value) {
            if ((int)Configuration::get($config_key)) {
                $payment_methods[] = $api_value;
            }
        }

        $checkout = array('uuid' => Configuration::get('PNP_CHECKOUT_UUID'));

        if (!empty($payment_methods)) {
            $checkout['payment_methods'] = $payment_methods;
        }

        $default = Configuration::get('PNP_CHECKOUT_DEFAULT_METHOD');
        if (!empty($default)) {
            $checkout['default_payment_method'] = $default;
        }

        $customization = array('language' => $this->getFormLanguage());
        $theme = Configuration::get('PNP_CHECKOUT_THEME');
        if (!empty($theme)) {
            $customization['theme_type'] = $theme;
        }
        $font = Configuration::get('PNP_CHECKOUT_FONT');
        if (!empty($font)) {
            $customization['font'] = $font;
        }
        $desc = Configuration::get('PNP_CHECKOUT_DESCRIPTION');
        if (!empty($desc)) {
            $customization['description'] = $desc;
        }
        if (!empty($customization)) {
            $customization['payment_details'] = new \stdClass();
            $checkout['customization'] = $customization;
        }

        return array('checkout' => $checkout);
    }

    public function getConfigFieldsValues()
    {
        return array(
            'PNP_ENVIRONMENT' => Tools::getValue('PNP_ENVIRONMENT', Configuration::get('PNP_ENVIRONMENT')),
            'PNP_KEY_PROD'            => Configuration::get('PNP_KEY_PROD'),
            'PNP_KEY_SANDBOX'         => Configuration::get('PNP_KEY_SANDBOX'),
            'PNP_SIGNATURE_PROD'      => Configuration::get('PNP_SIGNATURE_PROD'),
            'PNP_SIGNATURE_SANDBOX'   => Configuration::get('PNP_SIGNATURE_SANDBOX'),
            'PNP_SERVICE_PROD'        => Configuration::get('PNP_SERVICE_PROD'),
            'PNP_SERVICE_SANDBOX'     => Configuration::get('PNP_SERVICE_SANDBOX'),
            'PNP_SERVICE_NAME_PROD'   => Configuration::get('PNP_SERVICE_NAME_PROD'),
            'PNP_SERVICE_NAME_SANDBOX'=> Configuration::get('PNP_SERVICE_NAME_SANDBOX'),
            'PNP_BIZUM_SERVICE_PROD'  => Configuration::get('PNP_BIZUM_SERVICE_PROD'),
            'PNP_BIZUM_SERVICE_SANDBOX'=> Configuration::get('PNP_BIZUM_SERVICE_SANDBOX'),
            'PNP_BIZUM_NAME_PROD'     => Configuration::get('PNP_BIZUM_NAME_PROD'),
            'PNP_BIZUM_NAME_SANDBOX'  => Configuration::get('PNP_BIZUM_NAME_SANDBOX'),
            'PNP_CHECKOUT_UUID_PROD'  => Configuration::get('PNP_CHECKOUT_UUID_PROD'),
            'PNP_CHECKOUT_UUID_SANDBOX'=> Configuration::get('PNP_CHECKOUT_UUID_SANDBOX'),
            'PNP_CHECKOUT_NAME_PROD'  => Configuration::get('PNP_CHECKOUT_NAME_PROD'),
            'PNP_CHECKOUT_NAME_SANDBOX'=> Configuration::get('PNP_CHECKOUT_NAME_SANDBOX'),
            'PNP_SECURE' => Tools::getValue('PNP_SECURE', Configuration::get('PNP_SECURE')),
            'PNP_SAVE_CARD' => Tools::getValue('PNP_SAVE_CARD', Configuration::get('PNP_SAVE_CARD')),
            'PNP_ORDER_WAITING_STATE' => Tools::getValue('PNP_ORDER_WAITING_STATE', Configuration::get('PNP_ORDER_WAITING_STATE')),
            'PNP_ORDER_SUCCESS_STATE' => Tools::getValue('PNP_ORDER_SUCCESS_STATE', Configuration::get('PNP_ORDER_SUCCESS_STATE')),
            'PNP_LOGO' => Tools::getValue('PNP_LOGO', Configuration::get('PNP_LOGO')),
            'PNP_FACE_COLOR' => Tools::getValue('PNP_FACE_COLOR', Configuration::get('PNP_FACE_COLOR')),
            'PNP_FORM_LANG'     => Tools::getValue('PNP_FORM_LANG', Configuration::get('PNP_FORM_LANG')),
            'PNP_SERVICE_NAME'  => Tools::getValue('PNP_SERVICE_NAME', Configuration::get('PNP_SERVICE_NAME')),
            'PNP_BIZUM_ENABLED' => Configuration::get('PNP_BIZUM_ENABLED'),
            'PNP_BIZUM_SERVICE' => Tools::getValue('PNP_BIZUM_SERVICE', Configuration::get('PNP_BIZUM_SERVICE')),
            'PNP_BIZUM_NAME'    => Tools::getValue('PNP_BIZUM_NAME', Configuration::get('PNP_BIZUM_NAME')),
            'PNP_MODE' => Tools::getValue('PNP_MODE', Configuration::get('PNP_MODE')),
            'PNP_CHECKOUT_UUID' => Tools::getValue('PNP_CHECKOUT_UUID', Configuration::get('PNP_CHECKOUT_UUID')),
            'PNP_CHECKOUT_NAME' => Tools::getValue('PNP_CHECKOUT_NAME', Configuration::get('PNP_CHECKOUT_NAME')),
            'PNP_CHECKOUT_METHODS_CARD'    => Configuration::get('PNP_CHECKOUT_METHODS_CARD'),
            'PNP_CHECKOUT_METHODS_BIZUM'   => Configuration::get('PNP_CHECKOUT_METHODS_BIZUM'),
            'PNP_CHECKOUT_METHODS_SOFORT'  => Configuration::get('PNP_CHECKOUT_METHODS_SOFORT'),
            'PNP_CHECKOUT_METHODS_IDEAL'   => Configuration::get('PNP_CHECKOUT_METHODS_IDEAL'),
            'PNP_CHECKOUT_METHODS_CRYPTO'  => Configuration::get('PNP_CHECKOUT_METHODS_CRYPTO'),
            'PNP_CHECKOUT_METHODS_PIX'     => Configuration::get('PNP_CHECKOUT_METHODS_PIX'),
            'PNP_CHECKOUT_METHODS_VIACASH' => Configuration::get('PNP_CHECKOUT_METHODS_VIACASH'),
            'PNP_CHECKOUT_METHODS_KLARNA'  => Configuration::get('PNP_CHECKOUT_METHODS_KLARNA'),
            'PNP_CHECKOUT_METHODS_GIROPAY' => Configuration::get('PNP_CHECKOUT_METHODS_GIROPAY'),
            'PNP_CHECKOUT_METHODS_GPAY'    => Configuration::get('PNP_CHECKOUT_METHODS_GPAY'),
            'PNP_CHECKOUT_METHODS_APAY'    => Configuration::get('PNP_CHECKOUT_METHODS_APAY'),
            'PNP_CHECKOUT_METHODS_C2PAY'   => Configuration::get('PNP_CHECKOUT_METHODS_C2PAY'),
            'PNP_CHECKOUT_DEFAULT_METHOD'  => Tools::getValue('PNP_CHECKOUT_DEFAULT_METHOD', Configuration::get('PNP_CHECKOUT_DEFAULT_METHOD')),
            'PNP_CHECKOUT_THEME'           => Tools::getValue('PNP_CHECKOUT_THEME', Configuration::get('PNP_CHECKOUT_THEME')),
            'PNP_CHECKOUT_FONT'            => Tools::getValue('PNP_CHECKOUT_FONT', Configuration::get('PNP_CHECKOUT_FONT')),
            'PNP_CHECKOUT_DESCRIPTION'     => Tools::getValue('PNP_CHECKOUT_DESCRIPTION', Configuration::get('PNP_CHECKOUT_DESCRIPTION')),
        );
    }

    public function saveCartPaylandsId($cart_id, $paylands_uuid)
    {
        $now = date('Y-m-d H:i:s');
        $existing = Db::getInstance()->getValue(
            'SELECT id FROM `' . _DB_PREFIX_ . 'pnp_paylands_orders` WHERE ps_cart_id = ' . (int)$cart_id
        );
        if ($existing) {
            return Db::getInstance()->update(
                'pnp_paylands_orders',
                array('order_uuid' => pSQL($paylands_uuid), 'updated_at' => $now),
                'ps_cart_id = ' . (int)$cart_id
            );
        }
        return Db::getInstance()->insert('pnp_paylands_orders', array(
            'customer_id' => '',
            'order_uuid' => pSQL($paylands_uuid),
            'client_uuid' => '',
            'ps_cart_id' => (int)$cart_id,
            'order_token' => '',
            'amount' => 0,
            'currency' => '',
            'paid' => 0,
            'safe' => 0,
            'raw_order' => '',
            'created_at' => $now,
            'updated_at' => $now,
        ));
    }

    public function getCartPaylandsId($cart_id)
    {
        return Db::getInstance()->getValue(
            'SELECT order_uuid FROM `' . _DB_PREFIX_ . 'pnp_paylands_orders` WHERE ps_cart_id = ' . (int)$cart_id
        );
    }

    public function getCartByPaylandsId($paylands_uuid)
    {
        return Db::getInstance()->getValue(
            'SELECT ps_cart_id FROM `' . _DB_PREFIX_ . 'pnp_paylands_orders` WHERE order_uuid = "' . pSQL($paylands_uuid) . '"'
        );
    }
}
