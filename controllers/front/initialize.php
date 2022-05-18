<?php

include_once(_PS_MODULE_DIR_ . 'paylands/classes/paylands.php');

class paylandsInitializeModuleFrontController extends ModuleFrontController
{
	/**
	 * Execute Ajax Request
	 */
	public function displayAjax()
	{
		$uuid = $_GET['uuid'];
		$cart = Context::getContext()->cart;
		$paylandsModel = new PaylandsConector();
		$response = $paylandsModel->getPayment($uuid, $cart);
		$this->module->saveCartPaylandsId($cart->id,$response->order->uuid);
		$tokenResponse = $response->order->token;
		die(json_encode(array('url' => $paylandsModel->getRedirectUrl('payment/tokenized/' . $tokenResponse))));
	}
}
