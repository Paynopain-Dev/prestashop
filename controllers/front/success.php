<?php


class paylandsSuccessModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    /**
     * @see FrontController::postProcess()
     */
    public function postProcess()
    {
		$cart = Context::getContext()->cart;
		$paylandsId = $this->module->getCartPaylandsId($cart->id);
		if (!$paylandsId) {
			Tools::redirect('index.php?controller=order');
		}
		$customer = new Customer($cart->id_customer);

		$total = $cart->getOrderTotal(true, Cart::BOTH);
		$order_details = array(
			'method' => $this->module->name,
			'currency' => $this->context->currency->iso_code,
			'transaction_id' => pSQL($paylandsId),
			'payment_method' => $this->module->name,
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
		Tools::redirect('index.php?controller=order-confirmation&id_cart=' . (int) $cart->id . '&id_module=' . (int) $this->module->id . '&id_order=' . $this->module->currentOrder . '&key=' . $customer->secure_key);
    }
}
