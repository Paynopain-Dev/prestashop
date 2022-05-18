<?php


if (!defined('_PS_VERSION_')) {
	exit;
}

include_once(_PS_MODULE_DIR_ . 'paylands/vendor/autoload.php');
include_once(_PS_MODULE_DIR_ . 'paylands/libs/PaymentOrder.php');
include_once(_PS_MODULE_DIR_ . 'paylands/libs/Logger.php');
include_once(_PS_MODULE_DIR_ . 'paylands/classes/paylands.php');

class Paylands extends \PaymentModule
{
	private $_html = '';
	private $_postErrors = array();
	public $address;

	public function __construct()
	{
		$this->name = 'paylands';
		$this->tab = 'payments_gateways';
		$this->version = '2.0.0';
		$this->author = 'Paylands';

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

	/**
	 * Install module dependencies
	 * @return boolean
	 */
	public function install()
	{
		return parent::install()
			&& (_PS_VERSION_ >= 1.7 ? $this->registerHook('paymentOptions') : $this->registerHook('payment'))
			&& $this->registerHook('displayHeader')
			&& $this->updateTables()
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
			&& Configuration::updateValue('PNP_FORM_LANG', '');
	}

	/**
	 * Uninstall module dependency
	 * @return boolean
	 */
	public function uninstall()
	{
		return parent::uninstall()
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
			&& Configuration::deleteByName('PNP_FORM_LANG');
	}

	/**
	 * Update cart table to save paylands id
	 * @return boolean
	 */
	private function updateTables(): bool
	{
		$sql = "ALTER TABLE " . _DB_PREFIX_ . "cart ADD COLUMN paylands_id VARCHAR(200);";
		return Db::getInstance()->execute($sql);
	}

	/**
	 * Create order state
	 * @return boolean
	 */
	public function installOrderState(): bool
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
			if (!Tools::getValue('PNP_SERVICE')) {
				$this->_postErrors[] = $this->l('The "Service" field is required.');
			} elseif (!Tools::getValue('PNP_SIGNATURE')) {
				$this->_postErrors[] = $this->l('The "Signature" field is required.');
			}
		}
	}

	private function _postProcess()
	{
		if (Tools::isSubmit('btnSubmit')) {
			Configuration::updateValue('PNP_ENVIRONMENT', Tools::getValue('PNP_ENVIRONMENT'));
			Configuration::updateValue('PNP_SERVICE', Tools::getValue('PNP_SERVICE'));
			Configuration::updateValue('PNP_SIGNATURE', Tools::getValue('PNP_SIGNATURE'));
			Configuration::updateValue('PNP_KEY', Tools::getValue('PNP_KEY'));
			Configuration::updateValue('PNP_SECURE', Tools::getValue('PNP_SECURE'));
			Configuration::updateValue('PNP_MIT', Tools::getValue('PNP_MIT'));
			Configuration::updateValue('PNP_SAVE_CARD', Tools::getValue('PNP_SAVE_CARD'));
			Configuration::updateValue('PNP_ORDER_WAITING_STATE', Tools::getValue('PNP_ORDER_WAITING_STATE'));
			Configuration::updateValue('PNP_ORDER_SUCCESS_STATE', Tools::getValue('PNP_ORDER_SUCCESS_STATE'));
			Configuration::updateValue('PNP_LOGO', Tools::getValue('PNP_LOGO'));
			Configuration::updateValue('PNP_FACE_COLOR', Tools::getValue('PNP_FACE_COLOR'));
			Configuration::updateValue('PNP_FORM_LANG', Tools::getValue('PNP_FORM_LANG'));
		}
		$this->_html .= $this->displayConfirmation($this->l('Settings updated'));
	}

	private function _displayCheck()
	{
		return $this->display(__FILE__, './views/templates/hook/infos.tpl');
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
	 * Return paylands id
	 * @param $idCart
	 * @return string
	 */
	public function getCartPaylandsId($idCart): string
	{
		$sql = new DbQuery();
		$sql->select('paylands_id')
			->from('cart')
			->where('id_cart = ' . pSQL($idCart));

		return Db::getInstance()->getValue($sql);
	}

	/**
	 * Return cart id by paylands id
	 * @param $paylandsId
	 * @return int
	 */
	public function getCartByPaylandsId($paylandsId): int
	{
		$sql = new DbQuery();
		$sql->select('id_cart')
			->from('cart')
			->where('paylands_id = \'' . $paylandsId . '\'');

		return Db::getInstance()->getValue($sql);
	}

	/**
	 * Save paylands id
	 * @param $idCart
	 * @param $paylandsId
	 * @return string
	 */
	public function saveCartPaylandsId($idCart, $paylandsId)
	{
		return Db::getInstance()->update("cart", ["paylands_id" => $paylandsId], 'id_cart = ' . (int)$idCart);
	}

	public function getContent()
	{
		$this->_html = '';

		if (Tools::isSubmit('btnSubmit')) {
			$this->_postValidation();
			if (!count($this->_postErrors)) {
				$this->_postProcess();
			} else {
				foreach ($this->_postErrors as $err) {
					$this->_html .= $this->displayError($err);
				}
			}
		}

		$this->_html .= $this->_displayCheck();
		$this->_html .= $this->renderForm();

		return $this->_html;
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
	 * Add css and js
	 */
	public function hookdisplayHeader($params)
	{
		if ($this->context->controller->php_self == 'order') {
			$this->context->controller->registerJavascript('PAYLANDS-js', 'modules/' . $this->name . '/views/js/paylands.js');
			$this->context->controller->registerStylesheet('PAYLANDS-js', 'modules/' . $this->name . '/views/css/paylands.css');
		}
	}

	/**
	 * Prestashop 1.7 hook payment
	 */
	public function hookPaymentOptions($params)
	{
		if (!$this->active) {
			return;
		}

		if (!$this->checkCurrency($params['cart'])) {
			return;
		}

		$newOption = new \PrestaShop\PrestaShop\Core\Payment\PaymentOption();
		$env = (int)Configuration::get('PNP_ENVIRONMENT');
		$paylandsModel = new PaylandsConector();
		$cart = Context::getContext()->cart;
		$customerId = $paylandsModel->getCustomerId($cart);

		$cards = [];
		if ($cart->id_customer) {
			$cards = $paylandsModel->getCustomerCards($customerId);
		}
		$configurations = [
			"token" => $paylandsModel->createCustomer($customerId),
			"mode" => !empty($env) ? 'sandbox' : '',
			"hasCards" => count($cards) > 0,
			"template" => Configuration::get('PNP_SERVICE'),
			'is_secure' => (bool)Configuration::get('PNP_SECURE'),
			"save_card" => (bool)Configuration::get('PNP_SAVE_CARD'),
			"place_url" => Context::getContext()->link->getModuleLink('paylands', 'place', ['ajax' => 1]),
			"secure_url" => Context::getContext()->link->getModuleLink('paylands', 'initialize'),
			"translations" => [
				"error" => $this->l('Please review the card information.'),
				"errorServer" => $this->l('Please review the card information.'),
				"payment_error" => $this->l('There was an error while processing the payment.'),
				'select_card' => $this->l('Please select a card option')
			]
		];

		$this->context->smarty->assign([
			"configurations" => $configurations,
			"cards" => $cards,
			"save_card" => (bool)Configuration::get('PNP_SAVE_CARD'),
			"loader" => $this->context->link->getMediaLink('/modules/' . $this->name . '/views/img/loader.gif')
		]);
		$newOption->setModuleName($this->name)
			->setCallToActionText($this->l('Pay with Card Safely'))
			->setAdditionalInformation($this->fetch('module:paylands/views/templates/hook/paylandsjs.tpl'))
			->setAction($this->context->link->getModuleLink($this->name, 'validation', array(), true));

		return array($newOption);
	}

	/**
	 * Prestashop 1.6 hook payment
	 */
	public function hookPayment($params)
	{
		if (!$this->active) {
			return;
		}

		if (!$this->checkCurrency($params['cart'])) {
			return;
		}

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

	public function renderForm()
	{
		$fields_form = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('Paylands Configuration'),
					'image' => '../modules/paylands/views/img/paylands-logo.png'
				),
				'input' => array(
					array(
						'type' => 'radio',
						'label' => $this->l('Environment'),
						'name' => 'PNP_ENVIRONMENT',
						'required' => true,
						'values' => array(
							array(
								'id' => 'prod_mod',
								'value' => 0,
								'label' => $this->l('Production Mode')
							),
							array(
								'id' => 'sandbox_mode',
								'value' => 1,
								'label' => $this->l('Sandbox Mode')
							)
						),
					),
					array(
						'type' => 'text',
						'label' => $this->l('Service'),
						'name' => 'PNP_SERVICE',
						'required' => true
					),
					array(
						'type' => 'text',
						'label' => $this->l('Signature'),
						'name' => 'PNP_SIGNATURE',
						'required' => true
					),
					array(
						'type' => 'text',
						'label' => $this->l('Api key'),
						'name' => 'PNP_KEY',
						'required' => true
					),
					array(
						'type' => 'radio',

						'label' => $this->l('Send RECURRING for MIT payments'),
						'name' => 'PNP_MIT',
						'required' => true,
						'values' => array(
							array(
								'id' => 'secure_yes',
								'value' => 0,
								'label' => $this->l('No')
							),
							array(
								'id' => 'secure_no',
								'value' => 1,
								'label' => $this->l('Yes')
							)
						)
					),
					array(
						'type' => 'radio',
						'label' => $this->l('Secure Payment'),
						'name' => 'PNP_SECURE',
						'required' => true,
						'values' => array(
							array(
								'id' => 'secure_yes',
								'value' => 0,
								'label' => $this->l('No')
							),
							array(
								'id' => 'secure_no',
								'value' => 1,
								'label' => $this->l('Yes')
							)
						)
					),
					array(
						'type' => 'radio',
						'label' => $this->l('Remember Cards'),
						'name' => 'PNP_SAVE_CARD',
						'required' => true,
						'values' => array(
							array(
								'id' => 'save_cards_yes',
								'value' => 0,
								'label' => $this->l('No')
							),
							array(
								'id' => 'save_cards_no',
								'value' => 1,
								'label' => $this->l('Yes')
							)
						)
					),
					array(
						'type' => 'select',
						'label' => $this->l('Order Waiting State'),
						'name' => 'PNP_ORDER_WAITING_STATE',
						'required' => true,
						'options' => array(
							'query' => $this->getOrderStatuses(),
							'id' => 'id_option',
							'name' => 'name'
						)
					),
					array(
						'type' => 'select',
						'label' => $this->l('Order Success State'),
						'name' => 'PNP_ORDER_SUCCESS_STATE',
						'required' => true,
						'options' => array(
							'query' => $this->getOrderStatuses(),
							'id' => 'id_option',
							'name' => 'name'
						)
					),
				),
				'submit' => array(
					'title' => $this->l('Save'),
				)
			),
		);

		$helper = new HelperForm();
		$helper->show_toolbar = false;
		$helper->module = $this;
		$helper->identifier = $this->identifier;
		$helper->submit_action = 'btnSubmit';
		$helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->tpl_vars = array(
			'fields_value' => $this->getConfigFieldsValues(),
		);

		$this->fields_form = array();

		return $helper->generateForm(array($fields_form));
	}

	public function getConfigFieldsValues()
	{
		return array(
			'PNP_ENVIRONMENT' => Tools::getValue('PNP_ENVIRONMENT', Configuration::get('PNP_ENVIRONMENT')),
			'PNP_SERVICE' => Tools::getValue('PNP_SERVICE', Configuration::get('PNP_SERVICE')),
			'PNP_SIGNATURE' => Tools::getValue('PNP_SIGNATURE', Configuration::get('PNP_SIGNATURE')),
			'PNP_KEY' => Tools::getValue('PNP_KEY', Configuration::get('PNP_KEY')),
			'PNP_SECURE' => Tools::getValue('PNP_SECURE', Configuration::get('PNP_SECURE')),
			'PNP_MIT' => Tools::getValue('PNP_MIT', Configuration::get('PNP_MIT')),
			'PNP_SAVE_CARD' => Tools::getValue('PNP_SAVE_CARD', Configuration::get('PNP_SAVE_CARD')),
			'PNP_ORDER_WAITING_STATE' => Tools::getValue('PNP_ORDER_WAITING_STATE', Configuration::get('PNP_ORDER_WAITING_STATE')),
			'PNP_ORDER_SUCCESS_STATE' => Tools::getValue('PNP_ORDER_SUCCESS_STATE', Configuration::get('PNP_ORDER_SUCCESS_STATE')),
			'PNP_LOGO' => Tools::getValue('PNP_LOGO', Configuration::get('PNP_LOGO')),
			'PNP_FACE_COLOR' => Tools::getValue('PNP_FACE_COLOR', Configuration::get('PNP_FACE_COLOR')),
			'PNP_FORM_LANG' => Tools::getValue('PNP_FORM_LANG', Configuration::get('PNP_FORM_LANG')),
		);
	}
}
