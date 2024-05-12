<?php

namespace Bitrix\CatalogMobile\Controller;

use Bitrix\Catalog\Access\AccessController;
use Bitrix\Catalog\Access\ActionDictionary;
use Bitrix\Catalog\StoreBarcodeTable;
use Bitrix\CatalogMobile\InventoryControl\DataProvider\DocumentProducts\SkuCollectionQuery;
use Bitrix\Main\Error;
use Bitrix\CatalogMobile\InventoryControl\DataProvider\DocumentProducts;
use Bitrix\Main\Loader;
use Bitrix\CatalogMobile\InventoryControl\DataProvider\DocumentProducts\Product\ConvertCurrency;
use Bitrix\CatalogMobile\InventoryControl\Dto\DocumentProductRecord;
use Bitrix\CatalogMobile\InventoryControl\Dto\ProductFromWizard;

Loader::requireModule('catalog');

class StoreDocumentProduct extends \Bitrix\Main\Engine\Controller
{
	use CatalogPermissions;

	public function loadProductModelAction(int $productId, ?int $documentId = null, ?string $documentType = null)
	{
		if (!$this->hasReadPermissions())
		{
			$this->addError(new Error($this->getInsufficientPermissionsError()));

			return null;
		}

		return DocumentProducts\Facade::loadProductModel($productId, $documentId, $documentType);
	}

	public function loadSkuCollectionAction(int $variationId): array
	{
		return (new SkuCollectionQuery($variationId))->execute();
	}

	public function buildProductModelFromWizardAction(array $fields, ?int $documentId = null, ?string $documentType = null)
	{
		if (!$this->hasReadPermissions())
		{
			$this->addError(new Error($this->getInsufficientPermissionsError()));

			return null;
		}

		$product = ProductFromWizard::make($fields);

		return DocumentProducts\Facade::buildProductModelFromWizard($product, $documentId, $documentType);
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
			$result[] = DocumentProductRecord::make((array)$item);
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
