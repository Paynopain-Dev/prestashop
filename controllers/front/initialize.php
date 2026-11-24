<?php

/**
 * Legacy endpoint (PaylandsConector flow), no longer used by the module.
 *
 * It created a Paylands payment order on every unauthenticated request and sent a card uuid
 * taken from $_GET without checking its owner.
 * The file is kept (instead of deleted) so that upgrading the module over an existing
 * installation overwrites the old code: a deleted file would stay on disk.
 */
class paylandsInitializeModuleFrontController extends ModuleFrontController
{
	public function postProcess()
	{
		header('HTTP/1.1 410 Gone');
		header('Content-Type: application/json');
		die(json_encode(array(
			'success' => false,
			'data' => array('message' => 'This endpoint is no longer available.'),
		)));
	}

	public function initContent()
	{
		$this->postProcess();
	}

	public function displayAjax()
	{
		$this->postProcess();
	}
}
