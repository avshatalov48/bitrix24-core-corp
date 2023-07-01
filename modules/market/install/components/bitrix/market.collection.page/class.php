<?php

use Bitrix\Rest\Marketplace\Transport;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
	die();
}

class RestMarketCollectionPage extends CBitrixComponent
{
	public function executeComponent()
	{
		$batch = [
			Transport::METHOD_GET_FULL_COLLECTION => [
				Transport::METHOD_GET_FULL_COLLECTION,
				[
					'collection_id' => $this->arParams['COLLECTION'],
				],
			],
		];

		$response = Transport::instance()->batch($batch);
		if (is_array($response[Transport::METHOD_GET_FULL_COLLECTION])) {
			$this->arResult = $response[Transport::METHOD_GET_FULL_COLLECTION];
		}

		$this->includeComponentTemplate();
	}
}
