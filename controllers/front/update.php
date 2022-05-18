<?php

include_once(_PS_MODULE_DIR_ . 'paylands/classes/paylands.php');

class paylandsUpdateModuleFrontController extends ModuleFrontController
{
	public $ssl = true;

	/**
	 * @see FrontController::initContent()
	 */
	public function initContent()
	{
		parent::initContent();

		Tools::redirect('index.php?controller=order');
	}

	public function postProcess()
	{
		$json = file_get_contents('php://input');
		$data = json_decode($json);
		$order = $data->order;
		if ($order && $order->status == 'SUCCESS') {
			$paylandsId = $order->uuid;
			$cartId = $this->module->getCartByPaylandsId($paylandsId);
			$cart = new Cart($cartId);
			if ($cart->id) {
				$orderId = Order::getIdByCartId((int)($cart->id));
				$orderTemp=new Order($orderId);
				$history = new OrderHistory();
				$history->id_order = (int)$orderId;
				$history->changeIdOrderState(2, (int)($orderTemp->id));
				$history->save();
				return true;
			}
		}
		return false;
	}

}
