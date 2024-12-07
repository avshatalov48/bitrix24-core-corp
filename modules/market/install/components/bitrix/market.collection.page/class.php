<?php

use Bitrix\Market\Rest\Actions;
use Bitrix\Market\Rest\Transport;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
	die();
}

class RestMarketCollectionPage extends CBitrixComponent
{
	public function executeComponent()
	{
		$batch = [
			Actions::METHOD_GET_FULL_COLLECTION => [
				Actions::METHOD_GET_FULL_COLLECTION,
				[
					'collection_id' => $this->arParams['COLLECTION'],
				],
			],
		];

		$response = Transport::instance()->batch($batch);
		if (is_array($response[Actions::METHOD_GET_FULL_COLLECTION])) {
			$this->arResult = $response[Actions::METHOD_GET_FULL_COLLECTION];
		}

		$this->includeComponentTemplate();
	}
}
