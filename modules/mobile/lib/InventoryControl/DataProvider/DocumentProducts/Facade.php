<?php

namespace Bitrix\Mobile\InventoryControl\DataProvider\DocumentProducts;

use Bitrix\Mobile\Integration\Catalog\Catalog;
use Bitrix\Mobile\Integration\Catalog\PermissionsProvider;
use Bitrix\Mobile\Integration\Catalog\Repository\MeasureRepository;
use Bitrix\Mobile\InventoryControl\Dto\DocumentProductRecord;
use Bitrix\Mobile\InventoryControl\Dto\ProductFromWizard;
use Bitrix\Mobile\InventoryControl\UrlBuilder;

class Facade
{
	public static function loadByDocumentId(?int $documentId = null, ?string $documentType = null): array
	{
		$document = Document::load($documentId, $documentType);
		$items = Product::loadByDocumentId($documentId);
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

		return [
			'document' => $document,
			'items' => $items,
			'catalog' => $catalog,
			'measures' => $measures,
			'permissions' => PermissionsProvider::getInstance()->getPermissions(),
		];
	}

	public static function loadProductModel(int $productId, ?int $documentId = null): DocumentProductRecord
	{
		return Product::loadProductModel($productId, $documentId);
	}

	public static function buildProductModelFromWizard(ProductFromWizard $product, ?int $documentId = null): DocumentProductRecord
	{
		return Wizard::buildProductModel($product, $documentId);
	}
}
