<?php

namespace Bitrix\CatalogMobile\Controller;

use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Pull\Event;
use Bitrix\Main\Engine\Controller;

class BarcodeScanner extends Controller
{
	/**
	 * @param string $id
	 * @param string $barcode
	 * @return array|null
	 */
	public function sendBarcodeScannedEventAction(string $id, string $barcode): ?array
	{
		if (!Loader::includeModule('pull'))
		{
			$this->addError(new Error('Pull module has not been installed'));
			return null;
		}

		Event::add($this->getCurrentUser()->getId(), [
			'module_id' => 'catalog',
			'command' => 'HandleBarcodeScanned',
			'params' => [
				'id' => $id,
				'barcode' => $barcode,
			]
		]);

		return [];
	}
}
