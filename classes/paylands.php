<?php


class PaylandsConector
{

	/**
	 *
	 * @param array $data
	 * @return array
	 */
	public function getPayment($source_id, $order)
	{
		$data = $this->getPaymentData($source_id, $order);
		return $this->request("payment", $data);
	}

	/**
	 * Create customer to pay
	 * @param array $data
	 * @return string
	 */
	public function createCustomer($customerId = 'user1024')
	{
		$data["customer_ext_id"] = $customerId;
		$response = $this->request("customer", $data, true);
		return $response->Customer->token;
	}

	/**
	 * Create customer to pay
	 * @param array $data
	 * @return string
	 */
	public function getCustomerCards($customerId)
	{
		$params['status'] = 'VALIDATED';
		$params['unique'] = true;
		$path = 'customer/' . $customerId . '/cards';
		$response = $this->request($path, $params, false);
		return $response->cards;
	}

	/**
	 *
	 * @param array $data
	 * @return string
	 */
	public function charge($authData)
	{
		return $this->request("payment/direct", $authData);
	}

	/**
	 * Call a ws service
	 * @param string $urlPath
	 * @param array|null $data
	 * @param boolean $post
	 * @return array
	 */
	protected function request($urlPath, $data = null, $post = true)
	{
		$url = $this->getPaylandUrl() . $urlPath;
		$data['signature'] = $this->getConfigValue('PNP_SIGNATURE');
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Content-Length:' . strlen(json_encode($data)),
			'Authorization: Basic ' . base64_encode($this->getConfigValue("PNP_KEY") . ": ")
		));
		if ($post) {
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
		} else {
			curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($data));
		}
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$server_output = curl_exec($ch);
		$response = json_decode($server_output);
		curl_close($ch);
		return $response;
	}

	/**
	 * Request a simple get
	 * @param $url
	 * @return mixed
	 */
	public function requestCleanGET($url)
	{
		$this->curl->get($url);
		return $this->curl->getBody();
	}

	/**
	 * return paylands redirect url
	 * @param $url
	 * @return string
	 */
	public function getRedirectUrl($url)
	{
		return $this->getPaylandUrl() . $url;
	}

	/**
	 * Return paylands url based on the configuration
	 * @return string
	 */
	public function getPaylandUrl()
	{
		if ((int)$this->getConfigValue("PNP_ENVIRONMENT") == 1) {
			return "https://api.paylands.com/v1/sandbox/";
		} else {
			return "https://api.paylands.com/v1/";
		}
	}

	/**
	 * @param type $config
	 * Return config module value
	 * @return string
	 */
	public function getConfigValue($config)
	{
		return Configuration::get($config);
	}


	/**
	 * @param type $order
	 * @return array
	 */
	public function getPaymentData($source_id, $order)
	{
		$customerId = $this->getCustomerId($order);
		$data = array(
			'secure' => (bool)Configuration::get('PNP_SECURE'),
			'save_card' => (bool)Configuration::get('PNP_SAVE_CARD'),
			'url_ok' => 'prestashop/' . basename(dirname(__FILE__)) . '/controllers/success.php',
			'url_ko' => 'prestashop/' . basename(dirname(__FILE__)) . '/controllers/error.php',
			'signature' => $this->getConfigValue('PNP_SIGNATURE'),
			'amount' => $order->getOrderTotal(true, Cart::BOTH) * 100,
			'operative' => 'AUTHORIZATION',
			'additional' => 'usuario',
			'customer_ext_id' => $customerId,
			'service' => $this->getConfigValue('PNP_SERVICE'),
			"url_post" => 'prestashop/' . basename(dirname(__FILE__)) . '/controllers/update.php',
			"template_uuid" => $this->getConfigValue('PNP_SERVICE'),
			"dcc_template_uuid" => "ea0d5f53-5901-4c6b-9d4a-7e7c9b0eeb7e",
			"description" => "Order No. " . $order->id,
			'extra_data' => $this->getExtraData($order)
		);
		if (!is_null($source_id)) {
			$data['source_uuid'] = $source_id;
		}
		return $data;
	}

	/**
	 * Return customer id
	 * @param $cart
	 * @return string
	 */
	public function getCustomerId($cart): string
	{
		return $cart->id_customer ? 'ps-user-' . $cart->id_customer : 'ps-user-quote-' . $cart->id;
	}

	/**
	 * @param type $quote
	 * @return array
	 */
	public function getExtraData($quote)
	{
		$extraData = array(
			'profile' => $this->getProfileData($quote),
			"address" => $this->getAddressData($quote),
			"shipping_address" => $this->getAddressData($quote),
			"billing_address" => $this->getAddressData($quote)
		);

		if($this->getConfigValue('PNP_MIT')) {
			$extraData["cof"]["reason"] = "RECURRING";
		}

		return $extraData;
	}

	/**
	 * @param type $order
	 * @return array
	 */
	public function getProfileData($quote)
	{
		$customerId = $this->getCustomerId($quote);
		$address = new Address(intval($quote->id_address_delivery));
		$customer= new Customer((int)$quote->id_customer);
		$profile = array(
			"first_name" => $address->firstname,
			"last_name" => $address->lastname,
			'external_id' => $customerId,
			"cardholder_name" => $address->firstname,
			"email" => $customer->email,
			"phone" => array(
				"number" => $address->phone,
			),
			"home_phone" => array(
				"number" => $address->phone,
			),
			"work_phone" => array(
				"number" => $address->phone,
			),
			"mobile_phone" => array(
				"number" => $address->phone,
			),
		);
		return $profile;
	}

	/**
	 *
	 * @param type $address
	 * @return array
	 */
	public function getAddressData($address)
	{
		$address = new Address($address->id_address_delivery);
		$addressData = array(
			"city" => $address->city,
			"country" => 'MEX',
			"address1" => $address->address1,
			"address2" => $address->address2,
			"address3" => $address->address2,
			"zip_code" => $address->postcode,
			"state_code" => $address->id_state,
		);

		return $addressData;
	}

	/**
	 * @param type $street
	 * @param type $index
	 * @return string
	 */
	public function getAddressIndex($street, $index)
	{
		if (is_array($street) && !empty($street)) {
			return isset($street[$index]) ? $street[$index] : '';
		}
		return '';
	}

}
