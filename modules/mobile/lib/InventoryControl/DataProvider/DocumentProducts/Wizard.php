<?php

namespace Bitrix\Mobile\InventoryControl\DataProvider\DocumentProducts;

use Bitrix\Catalog\ProductTable;
use Bitrix\Catalog\v2\IoC\ServiceContainer;
use Bitrix\Catalog\v2\Product\BaseProduct;
use Bitrix\Main\Loader;
use Bitrix\Mobile\Integration\Catalog\Repository\MeasureRepository;
use Bitrix\Mobile\InventoryControl\Dto\DocumentProductRecord;
use Bitrix\Mobile\InventoryControl\Dto\ProductFromWizard;
use Bitrix\Mobile\InventoryControl\UrlBuilder;
use Bitrix\Mobile\UI\File;

Loader::includeModule('catalog');

final class Wizard
{
	public static function buildProductModel(ProductFromWizard $product, ?int $documentId = null): DocumentProductRecord
	{
		$document = Document::load($documentId);
		$currency = $product->documentCurrency ?? $document->currency;
		$sku = self::loadProduct((int)$product->id);

		$record = new DocumentProductRecord([
			'id' => 'unsaved_' . $product->wizardUniqid,
			'type' => $sku ? $sku->getType() : ProductTable::TYPE_PRODUCT,
			'documentId' => $documentId,
			'productId' => (int)$product->id,
			'desktopUrl' => UrlBuilder::getProductDetailUrl((int)$product->id),
			'name' => $product->name,
			'barcode' => $product->barcode,
			'amount' => (float)$product->amount,
			'price' => [
				'purchase' => [
					'amount' => (float)$product->purchasingPrice['amount'],
					'currency' => $product->purchasingPrice['currency'] ?? $currency,
				],
				'sell' => [
					'amount' => (float)$product->basePrice['amount'],
					'currency' => $product->basePrice['currency'] ?? $currency,
				]
			]
		]);

		if (!empty($product->storeFrom))
		{
			$record->storeFrom = $product->storeFrom;
			$record->storeFromId = (int)$record->storeFrom->id;
		}

		if (!empty($product->storeTo))
		{
			$record->storeTo = $product->storeTo;
			$record->storeToId = (int)$record->storeTo->id;
		}

		if (!empty($product->section))
		{
			$record->sections[] = [
				'id' => $product->section['id'],
				'name' => $product->section['title'],
			];
		}

		if (!empty($product->morePhoto))
		{
			foreach ($product->morePhoto as $file)
			{
				$fileId = (int)$file['fileid'];
				$record->gallery[] = $fileId;
				$record->galleryInfo[$fileId] = File::loadWithPreview($fileId);
			}
		}

		$record->measure = !empty($product->measureCode)
			? MeasureRepository::findByCode($product->measureCode)
			: MeasureRepository::getDefaultMeasure();

		return $record;
	}

	private static function loadProduct(int $skuId): ?BaseProduct
	{
		$repositoryFacade = ServiceContainer::getRepositoryFacade();

		if (!$repositoryFacade || $skuId <= 0)
		{
			return null;
		}

		$sku = $repositoryFacade->loadVariation($skuId);
		if (!$sku)
		{
			return null;
		}

		/** @var \Bitrix\Catalog\v2\Product\BaseProduct $product */
		$product = $sku->getParent();

		return $product;
	}
}
