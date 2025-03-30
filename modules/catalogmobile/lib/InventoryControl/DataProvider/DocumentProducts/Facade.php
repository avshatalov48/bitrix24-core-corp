<?php

namespace Bitrix\CatalogMobile\InventoryControl\DataProvider\DocumentProducts;

use Bitrix\Catalog\Config\State;
use Bitrix\Catalog\Store\EnableWizard\TariffChecker;
use Bitrix\Catalog\StoreDocumentTable;
use Bitrix\Catalog\Access\AccessController;
use Bitrix\Catalog\StoreTable;
use Bitrix\CatalogMobile\Catalog;
use Bitrix\CatalogMobile\PermissionsProvider;
use Bitrix\CatalogMobile\Repository\MeasureRepository;
use Bitrix\CatalogMobile\InventoryControl\Dto\DocumentProductRecord;
use Bitrix\CatalogMobile\InventoryControl\Dto\ProductFromWizard;
use Bitrix\CatalogMobile\InventoryControl\UrlBuilder;
use Bitrix\CatalogMobile\InventoryControl\DataProvider\DocumentProducts\Product\RealizationProduct;
use Bitrix\CatalogMobile\InventoryControl\DataProvider\DocumentProducts\Product\StoreProduct;

class Facade
{
	public static function loadByDocumentId(?int $documentId = null, ?string $documentType = null, array $context = []): array
	{
		$document = Document::load($documentId, $documentType);
		if ($documentType === StoreDocumentTable::TYPE_SALES_ORDERS)
		{
			$items = RealizationProduct::load($documentId, $context);
			if (!$documentId && !empty($items))
			{
				$total = 0.0;
				foreach ($items as $item)
				{
					$total += $item->price['vat']['priceWithVat'] * $item->amount;
				}
				$document->total['amount'] = $total;
				$document->total['currency'] = $items[0]->price['sell']['currency'];
			}
		}
		else
		{
			$items = StoreProduct::load($documentId);
		}
		$catalog = [
			'id' => Catalog::getDefaultId(),
			'base_price_id' => Catalog::getBasePrice(),
			'restricted_product_types' => Catalog::getStoreDocumentRestrictedProductTypes(),
			'currency_id' => Catalog::getBaseCurrency(),
			'url' => [
				'create_product' => UrlBuilder::getProductDetailUrl(0),
			]
		];
		$measures = MeasureRepository::findAll();

		$config = [
			'isCatalogHidden' => State::isExternalCatalog(),
			'isOnecRestrictedByPlan' => TariffChecker::isOnecInventoryManagementRestricted(),
		];
		$defaultStoreId = AccessController::getCurrent()->getAllowedDefaultStoreId();
		if ($defaultStoreId)
		{
			$defaultStoreTitle = StoreTable::getRow(['select' => ['TITLE'], 'filter' => ['=ID' => $defaultStoreId]])['TITLE'];
			$config['defaultStore'] = [
				'id' => $defaultStoreId,
				'title' => $defaultStoreTitle,
			];
		}

		return [
			'document' => $document,
			'items' => $items,
			'catalog' => $catalog,
			'measures' => $measures,
			'permissions' => PermissionsProvider::getInstance()->getPermissions(),
			'config' => $config,
		];
	}

	public static function loadProductModel(int $productId, ?int $documentId = null, ?string $documentType = null): DocumentProductRecord
	{
		if ($documentType === StoreDocumentTable::TYPE_SALES_ORDERS)
		{
			return RealizationProduct::loadProductModel($productId, $documentId, $documentType);
		}

		return StoreProduct::loadProductModel($productId, $documentId, $documentType);
	}

	public static function buildProductModelFromWizard(ProductFromWizard $product, ?int $documentId = null, ?string $documentType = null): DocumentProductRecord
	{
		return Wizard::buildProductModel($product, $documentId, $documentType);
	}
}
