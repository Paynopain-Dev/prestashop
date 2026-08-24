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

class paylandsPaymentcallbackModuleFrontController extends ModuleFrontController
{

    public $ssl = true;

    public function postProcess()
    {
        $cart = new Cart((int)Tools::getValue('id'));

        if (!$this->module->checkCurrency($cart)) {
            die('ko');
        }
        $data = json_decode(Tools::file_get_contents('php://input'), true);

        \Logger::log("PNP_CALLBACK#CartId:" . $cart->id . "#paylandsCallbackData:" . json_encode($data, JSON_PRETTY_PRINT));

        if (!empty($data)) {
            $customer = new Customer($cart->id_customer);

            $order_ps = '';
            if (_PS_VERSION_ >= 1.7) {
                $order_ps = Order::getByCartId($cart->id);
            } else {
                $order_id = Order::getOrderByCartId($cart->id);
                $order_ps = new Order($order_id);
            }

            if ($data['order']['status'] == 'SUCCESS') {
                $order_ps->setCurrentState(Configuration::get('PNP_ORDER_SUCCESS_STATE'));
            } else {
                $order_ps->setCurrentState(Configuration::get('PS_OS_ERROR'));
            }

            \Db::getInstance()->update('pnp_paylands_orders', array(
                'status' => pSQL($data['order']['status']),
                'paid' => (int)$data['order']['paid'],
                'raw_order' => pSQL(json_encode($data)),
                'updated_at' => date('Y-m-d H:i:s'),
            ), 'ps_cart_id = ' . $cart->id, 1, true);

            $save_card = (int)Configuration::get('PNP_SAVE_CARD');
            if ($data['order']['status'] == 'SUCCESS' &&
                !empty($data['order']['transactions']) &&
                count($data['order']['transactions']) > 0 &&
                empty($customer->is_guest) &&
                $save_card
            ) {
                $this->module->createPaylandsUserCard($data, $cart->id, $order_ps->id);
            }
        }
        die('ok');
    }
}
