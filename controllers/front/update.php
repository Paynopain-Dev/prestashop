<?php

/**
 * Legacy endpoint (PaylandsConector flow), no longer used by the module.
 *
 * Old url_post receiver: it accepted unsigned notifications and set orders as paid.
 * The file is kept (instead of deleted) so that upgrading the module over an existing
 * installation overwrites the old code: a deleted file would stay on disk.
 */
class paylandsUpdateModuleFrontController extends ModuleFrontController
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
