<?php

namespace Bitrix\Mobile\InventoryControl\DataProvider\DocumentProducts;

use Bitrix\Mobile\InventoryControl\Dto\DocumentProductRecord;
use Bitrix\Mobile\InventoryControl\Dto\ProductFromWizard;

class Facade
{
	public static function loadByDocumentId(?int $documentId = null, ?string $documentType = null): array
	{
		$document = Document::load($documentId, $documentType);
		$items = Product::loadByDocumentId($documentId);
		$catalog = Catalog::load();
		$measures = Measures::load();

		return [
			'document' => $document,
			'items' => $items,
			'catalog' => $catalog,
			'measures' => $measures,
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
