<?php

namespace Bitrix\Mobile\Controller\Catalog;

use Bitrix\Catalog\Access\AccessController;
use Bitrix\Catalog\Access\ActionDictionary;
use Bitrix\Catalog\StoreBarcodeTable;
use Bitrix\Main\Error;
use Bitrix\Mobile\InventoryControl\DataProvider\DocumentProducts;
use Bitrix\Main\Loader;
use Bitrix\Mobile\InventoryControl\DataProvider\DocumentProducts\Product\ConvertCurrency;
use Bitrix\Mobile\InventoryControl\Dto\DocumentProductRecord;
use Bitrix\Mobile\InventoryControl\Dto\ProductFromWizard;

Loader::requireModule('catalog');

class StoreDocumentProduct extends \Bitrix\Main\Engine\Controller
{
	use CatalogPermissions;

	public function loadProductModelAction(int $productId, ?int $documentId = null)
	{
		if (!$this->hasReadPermissions())
		{
			$this->addError(new Error($this->getInsufficientPermissionsError()));

			return null;
		}

		return DocumentProducts\Facade::loadProductModel($productId, $documentId);
	}

	public function buildProductModelFromWizardAction(array $fields, ?int $documentId = null)
	{
		if (!$this->hasReadPermissions())
		{
			$this->addError(new Error($this->getInsufficientPermissionsError()));

			return null;
		}

		$product = new ProductFromWizard($fields);

		return DocumentProducts\Facade::buildProductModelFromWizard($product, $documentId);
	}

	/**
	 * @param string $barcode
	 * @return array
	 */
	public function findProductByBarCodeAction(string $barcode)
	{
		if (!$this->hasReadPermissions())
		{
			$this->addError(new Error($this->getInsufficientPermissionsError()));

			return null;
		}

		$item = StoreBarcodeTable::getList([
			'select' => ['PRODUCT_ID'],
			'filter' => [
				'=BARCODE' => $barcode,
			],
		])->fetch();

		return [
			'id' => $item ? (int)$item['PRODUCT_ID'] : null,
		];
	}

	public function convertProductsCurrencyAction(string $currencyId, array $items = [])
	{
		if (!$this->hasReadPermissions())
		{
			$this->addError(new Error($this->getInsufficientPermissionsError()));

			return null;
		}

		$result = [];
		foreach ($items as $item)
		{
			$result[] = new DocumentProductRecord((array)$item);
		}

		return (new ConvertCurrency($currencyId))->enrich($result);
	}

	/**
	 * @return bool
	 */
	private function hasReadPermissions(): bool
	{
		return AccessController::getCurrent()->check(ActionDictionary::ACTION_CATALOG_READ);
	}
}
