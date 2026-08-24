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

class paylandsPaywithcardModuleFrontController extends ModuleFrontController
{

    public $ssl = true;
    private $ajaxResponse;

    public function init() {
        parent::init();
        $this->ajax = true;
    }

    public function postProcess()
    {
        $cart = new Cart((int)Tools::getValue('id'));

        if (!$this->module->checkCurrency($cart)) {
            Tools::redirect('index.php?controller=order');
        }

        $customer = new Customer($cart->id_customer);
        $sql = new DbQuery();
        $sql->select('card_uuid')
            ->from('pnp_paylands_cards')
            ->where('card_uuid = "' . pSQL(Tools::getValue('card_uuid')) . '"');
        $user_card = \Db::getInstance()->getRow($sql);

        $secure_payment = (int)Configuration::get('PNP_SECURE');
        $env = (int)Configuration::get('PNP_ENVIRONMENT');
        $environment = !empty($env) ? 'sandbox' : 'prod';
        $card_source_id = $secure_payment ? $user_card['card_uuid'] : '';

        $lang = Configuration::get('PNP_FORM_LANG');
        if ($lang == 'store') {
            $lang = $this->context->language->iso_code;
        }

        $total = $cart->getOrderTotal(true, Cart::BOTH);
        $pnpOrder = new PaymentOrder(Configuration::get('PNP_KEY'), Configuration::get('PNP_SIGNATURE'), Configuration::get('PNP_SERVICE'), $environment);
        $order = $pnpOrder->createOrder(
            $total,
            "AUTHORIZATION",
            $cart->id_customer,
            "PSCartId:" . $cart->id,
            (string)$cart->id,
            $secure_payment,
            Context::getContext()->link->getModuleLink('paylands', 'paymentcallback', array('id' => $cart->id), true),
            Context::getContext()->link->getModuleLink('paylands', 'landingok', array('id' => $cart->id), true),
            Context::getContext()->link->getModuleLink('paylands', 'landingko', array('id' => $cart->id), true),
            $card_source_id
        );

        $url_redirect = Context::getContext()->link->getModuleLink('paylands', 'landingko', array('id' => $cart->id), true);

        if (!empty($order) && $order['code'] == 200) {
            \Logger::log("cartId:" . $cart->id . "#PaylandsOrder:" . json_encode($order, JSON_PRETTY_PRINT));

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

            $order_ps = '';
            if (_PS_VERSION_ >= 1.7) {
                $order_ps = Order::getByCartId($cart->id);
            } else {
                $order_id = Order::getOrderByCartId($cart->id);
                $order_ps = new Order($order_id);
            }
            $this->module->validateOrder((int)$cart->id, Configuration::get('PNP_ORDER_WAITING_STATE'), $total, $this->module->name, null, $order_details, (int)$cart->id_currency, false, $customer->secure_key);
            $this->module->createPaylandsOrder($order, $cart->id, $order_ps->id);

            if ($secure_payment) { // Tokenized payment
                $url_redirect = $pnpOrder->getTokenized3DSUrl() . $order["order"]["token"] . "?lang=" . $lang;
            } else { // Direct Payment
                $customer_ip = $this->module->getRealIpAddr();
                $directPayment = $pnpOrder->directPayment($customer_ip, $order['order']['uuid'], $user_card['card_uuid']);

                if (!empty($directPayment) && $directPayment['order']['status'] == 'SUCCESS') {
                    $url_redirect = Context::getContext()->link->getModuleLink('paylands', 'landingok', array('id' => $cart->id), true);
                    \Logger::log("PNP_DIRECT_PAYMENT:cartId:" . $cart->id . "#PaylandsOrder:" . json_encode($directPayment, JSON_PRETTY_PRINT));
                } else {
                    $order_ps->setCurrentState(Configuration::get('PS_OS_ERROR'));
                    \Logger::error("PNP_DIRECT_PAYMENT:cartId:" . $cart->id . "#PaylandsOrder:" . json_encode($directPayment, JSON_PRETTY_PRINT));
                }
            }
        } else {
            $this->module->validateOrder((int)$cart->id, Configuration::get('PS_OS_ERROR'), $total, $this->module->name, null, array(), (int)$cart->id_currency, false, $customer->secure_key);
            \Logger::error("cartId:" . $cart->id . "#PaylandsOrder:" . json_encode($order, JSON_PRETTY_PRINT));
        }

        $response = array();
        $response['redirect'] = $url_redirect;

        $this->ajaxResponse = $response;
    }

    public function displayAjax()
    {
        $this->ajaxDie(Tools::jsonEncode($this->ajaxResponse));
    }
}
