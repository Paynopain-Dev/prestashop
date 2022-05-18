<?php

include_once(_PS_MODULE_DIR_ . 'paylands/classes/paylands.php');

class paylandsPlaceModuleFrontController extends ModuleFrontController
{
	/**
	 * Execute Ajax Request
	 */
	public function displayAjax()
	{
		try {
			$uuid = Tools::getValue('uuid');
			$cart = Context::getContext()->cart;
			$paylandsModel = new PaylandsConector();
			$paylandsOrder = $paylandsModel->getPayment(null, $cart);
			$this->module->saveCartPaylandsId($cart->id,$paylandsOrder->order->uuid);
			$authData = array(
				"card_uuid" => $uuid,
				"order_uuid" => $paylandsOrder->order->uuid
			);
			$plOrder = $paylandsModel->charge($authData);

			if(in_array($plOrder->order->status,["SUCCESS"])) {
				$customer = new Customer($cart->id_customer);
				$total = $cart->getOrderTotal(true, Cart::BOTH);
				$order_details = array(
					'method' => $this->module->name,
					'currency' => $this->context->currency->iso_code,
					'transaction_id' => pSQL($paylandsOrder->order->uuid),
					'payment_method' => $this->module->name,
					'date_transaction' => date("Y-m-d H:i:s")
				);

				if (_PS_VERSION_ >= 1.7) {
					$order_ps = Order::getByCartId($cart->id);
				} else {
					$order_id = Order::getOrderByCartId($cart->id);
					$order_ps = new Order($order_id);
				}
				$this->module->validateOrder((int)$cart->id, Configuration::get('PNP_ORDER_WAITING_STATE'), $total, $this->module->name, null, $order_details, (int)$cart->id_currency, false, $customer->secure_key);
				$params = [
					'id_cart' => (int) $cart->id,
					'id_module' => (int) $this->module->id,
					'id_order' => $this->module->currentOrder,
					'key' => $customer->secure_key
				];
				$response['url'] = Context::getContext()->link->getPageLink('order-confirmation', true, null, $params, false);
				$this->response(true,$response);
			} else {
				throw new Exception(sprintf($this->l('The order has been %s'),$plOrder->order->status));
			}
		} catch (Exception $e) {
			$response['message'] = $e->getMessage();
			$this->response(false,$response);
		}

	}

	/**
	 * Send json response
	 * @param $success
	 * @param $data
	 */
	private function response($success, $data) {
		header('Content-Type: application/json');
		die(json_encode([
			'success' => $success,
			'data' => $data
		]));
	}
}
