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

class paylandsPaymentcallbackModuleFrontController extends ModuleFrontController
{

    public $ssl = true;

    public function postProcess()
    {
        $cart = new Cart((int)Tools::getValue('id'));

        if (!$this->module->checkCurrency($cart)) {
            die('ko');
        }
        $raw_body = Tools::file_get_contents('php://input');
        $data = json_decode($raw_body, true);

        \Logger::log("PNP_CALLBACK#CartId:" . $cart->id . "#paylandsCallbackData:" . json_encode(\Logger::redact($data), JSON_PRETTY_PRINT));

        if (!empty($data)) {
            // Never trust the notification body: it must belong to the Paylands order
            // created for this cart and be authenticated by Paylands.
            $data = $this->getVerifiedNotification($cart, $raw_body, $data);
            if (empty($data)) {
                die('ko');
            }

            $customer = new Customer($cart->id_customer);

            $order_ps = null;
            if (_PS_VERSION_ >= 1.7) {
                $order_ps = Order::getByCartId($cart->id);
            } else {
                $order_id = Order::getOrderByCartId($cart->id);
                $order_ps = new Order($order_id);
            }

            if (!Validate::isLoadedObject($order_ps)) {
                \Logger::error("PNP_CALLBACK#CartId:" . $cart->id . "#PrestaShop order not found");
                die('ko');
            }

            $new_state = $data['order']['status'] == 'SUCCESS'
                ? (int)Configuration::get('PNP_ORDER_SUCCESS_STATE')
                : (int)Configuration::get('PS_OS_ERROR');

            if ((int)$order_ps->getCurrentState() !== $new_state) {
                $order_ps->setCurrentState($new_state);
            }

            \Db::getInstance()->update('pnp_paylands_orders', array(
                'status' => pSQL($data['order']['status']),
                'paid' => (int)$data['order']['paid'],
                'raw_order' => pSQL(json_encode($data)),
                'updated_at' => date('Y-m-d H:i:s'),
            ), 'ps_cart_id = ' . (int)$cart->id . ' AND order_uuid = "' . pSQL($data['order']['uuid']) . '"', 1, true);

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

    /**
     * Returns trusted order data for this cart, or null if the notification cannot be verified.
     *
     * 1. The order uuid must match a Paylands order this module created for the cart
     *    (prevents replaying a genuine notification of another order on this cart).
     * 2. The validation_hash must match the merchant signature. If it is missing or does
     *    not match (e.g. notifications signed with another API key's signature, or float
     *    encoding differences on old PHP versions), the order is read from the Paylands
     *    API instead and only that response is used.
     *
     * @param Cart $cart
     * @param string $raw_body
     * @param array $data
     * @return array|null
     */
    private function getVerifiedNotification($cart, $raw_body, $data)
    {
        $log_prefix = "PNP_CALLBACK#CartId:" . (int)$cart->id;

        if (empty($data['order']['uuid']) || !is_string($data['order']['uuid'])) {
            \Logger::error($log_prefix . "#Rejected: missing order uuid");
            return null;
        }

        $sql = new DbQuery();
        $sql->select('order_uuid')
            ->from('pnp_paylands_orders')
            ->where('ps_cart_id = ' . (int)$cart->id)
            ->where('order_uuid = "' . pSQL($data['order']['uuid']) . '"');
        $paylands_order = Db::getInstance()->getRow($sql);

        if (empty($paylands_order['order_uuid'])) {
            \Logger::error($log_prefix . "#Rejected: order uuid does not belong to this cart");
            return null;
        }
        $order_uuid = $paylands_order['order_uuid'];

        if (PaymentOrder::isValidNotification($raw_body, Configuration::get('PNP_SIGNATURE'))) {
            return $data;
        }

        \Logger::log($log_prefix . "#validation_hash missing or invalid, checking order with Paylands API");

        $env = (int)Configuration::get('PNP_ENVIRONMENT');
        $environment = !empty($env) ? 'sandbox' : 'prod';
        $pnpOrder = new PaymentOrder(
            Configuration::get('PNP_KEY'),
            Configuration::get('PNP_SIGNATURE'),
            Configuration::get('PNP_SERVICE'),
            $environment
        );
        $api_data = $pnpOrder->getOrder($order_uuid);

        if (empty($api_data['order']['uuid'])
            || !isset($api_data['order']['status'])
            || strcasecmp($api_data['order']['uuid'], $order_uuid) !== 0
        ) {
            \Logger::error($log_prefix . "#Rejected: order could not be verified with Paylands API");
            return null;
        }

        return $api_data;
    }
}
