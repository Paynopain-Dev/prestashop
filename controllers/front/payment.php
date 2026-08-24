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
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2007-2020 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

use PaylandsSDK\Services\PaymentOrder;

class paylandsPaymentModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    /**
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        parent::initContent();

        $cart = Context::getContext()->cart;
        if (!$this->module->checkCurrency($cart)) {
            Tools::redirect('index.php?controller=order');
        }

        $customer = new Customer($cart->id_customer);

        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        $sql = new DbQuery();
        $sql->select('id, status')
            ->from('pnp_paylands_orders')
            ->where('ps_cart_id = ' . (int)$cart->id);

        $db_result = Db::getInstance()->getRow($sql);
        if (!empty($db_result)) {
            if ($db_result['status'] == 'SUCCESS') {
                return Tools::redirect(Context::getContext()->link->getModuleLink('paylands', 'landingok', array('id' => $cart->id), true));
            }

            return Tools::redirect(Context::getContext()->link->getModuleLink('paylands', 'landingko', array('id' => $cart->id), true));
        }

        /****** Validate settings *******/
        $secure_payment = (int)Configuration::get('PNP_SECURE');
        $save_cards = Configuration::get('PNP_SAVE_CARD');

        $lang = Configuration::get('PNP_FORM_LANG');
        if ($lang == 'store') {
            $lang = $this->context->language->iso_code;
        }

        $customer_cards = array();
        if (empty($customer->is_guest)) { // customer->is_guest = 1 is a guest user
            $sql = new DbQuery();
            $sql->select('id, card_uuid, type, card_token, last4, brand, expire_month, expire_year')
                ->from('pnp_paylands_cards')
                ->where('customer_id = ' . (int)$cart->id_customer);

            $customer_cards = Db::getInstance()->executeS($sql);

        }

        if (
            empty($save_cards)
            || !count($customer_cards)
            || !empty($customer->is_guest)
        ) {
            $env = (int)Configuration::get('PNP_ENVIRONMENT');
            $environment = !empty($env) ? 'sandbox' : 'prod';
            $total = $cart->getOrderTotal(true, Cart::BOTH);
            $pnpOrder = new PaymentOrder(Configuration::get('PNP_KEY'), Configuration::get('PNP_SIGNATURE'), Configuration::get('PNP_SERVICE'), $environment);

            $params = [
                'id_cart' => (int) $cart->id,
                'id_module' => (int) $this->module->id,
                'key' => $customer->secure_key
            ];

            $url_ok = Context::getContext()->link->getPageLink('order-confirmation', true, null, $params, false);

            $order = $pnpOrder->createOrder(
                $total,
                "AUTHORIZATION",
                $cart->id_customer,
                "PSCartId:" . $cart->id,
                (string)$cart->id,
                $secure_payment || !empty($customer->is_guest), // anonymous user will use 3ds
                Context::getContext()->link->getModuleLink('paylands', 'paymentcallback', array('id' => $cart->id), true),
                //Context::getContext()->link->getModuleLink('paylands', 'landingok', array('id' => $cart->id), true),
                $url_ok,
                Context::getContext()->link->getModuleLink('paylands', 'landingko', array('id' => $cart->id), true),
                ''
            );

            if (empty($order) || $order['code'] != 200) {
                $this->module->validateOrder((int)$cart->id, Configuration::get('PS_OS_ERROR'), $total, $this->module->name, null, array(), (int)$cart->id_currency, false, $customer->secure_key);
                \Logger::error("cartId:" .$cart->id. "#PaylandsOrder:" . json_encode($order, JSON_PRETTY_PRINT));
                return Tools::redirect(Context::getContext()->link->getModuleLink('paylands', 'ko', array('id' => $cart->id), true));
            }

            \Logger::log("cartId:" .$cart->id. "#PaylandsOrder:" . json_encode($order, JSON_PRETTY_PRINT));

            $order_details = array(
                'method' => $this->module->name,
                'currency' => $this->context->currency->iso_code,
                'transaction_id' => pSQL($order['order']['uuid']),
                'payment_status' => pSQL($order['order']['status']),
                'payment_method' => $this->module->name,
                'id_payment' => pSQL($order['order']['token']),
                'capture' => false,
                'date_transaction' => date("Y-m-d H:i:s")
            );

            $this->module->validateOrder((int)$cart->id, Configuration::get('PNP_ORDER_WAITING_STATE'), $total, $this->module->name, null, $order_details, (int)$cart->id_currency, false, $customer->secure_key);
            $this->module->createPaylandsOrder($order, $cart->id, $this->module->currentOrder);
            $redirect_url = $pnpOrder->getRedirectUrl() . $order["order"]["token"] . "?lang=".$lang;
            return Tools::redirect($redirect_url);
        }

        $logo = trim(Configuration::get('PNP_LOGO'));
        if (empty($logo)) {
            $logo = $this->module->getModuleUrl() . '/views/img/paylands-logo-dark.png';
        }

        $this->context->smarty->assign(array(
            'logo' => $logo,
            'cart_id' => $cart->id,
            'customer_cards' => $customer_cards
        ));

        if (_PS_VERSION_ >= 1.7) {
            $this->setTemplate('module:paylands/views/templates/front/payment.tpl');
        } else {
            die($this->context->smarty->fetch(_PS_MODULE_DIR_.$this->module->name . '/views/templates/front/payment.tpl'));
        }
    }
}
