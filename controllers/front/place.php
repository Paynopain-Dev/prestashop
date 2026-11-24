<?php

/**
 * Legacy endpoint (PaylandsConector flow), no longer used by the module.
 *
 * It charged a card_uuid taken from the request without checking that the card belonged
 * to the customer. The file is kept (instead of deleted) so that upgrading the module over
 * an existing installation overwrites the old code: a deleted file would stay on disk.
 */
class paylandsPlaceModuleFrontController extends ModuleFrontController
{
	public function displayAjax()
	{
		header('Content-Type: application/json');
		die(json_encode(array(
			'success' => false,
			'data' => array('message' => 'This endpoint is no longer available.'),
		)));
	}

	public function initContent()
	{
		$this->displayAjax();
	}
}
