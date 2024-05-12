<?php

namespace Bitrix\CatalogMobile\InventoryControl\DataProvider\DocumentProducts;

use Bitrix\Catalog\ProductTable;
use Bitrix\Catalog\v2\IoC\ServiceContainer;
use Bitrix\Catalog\v2\Sku\BaseSku;
use Bitrix\Main\Loader;
use Bitrix\CatalogMobile\Repository\MeasureRepository;
use Bitrix\CatalogMobile\InventoryControl\Dto\DocumentProductRecord;
use Bitrix\CatalogMobile\InventoryControl\Dto\ProductFromWizard;
use Bitrix\CatalogMobile\InventoryControl\UrlBuilder;
use Bitrix\Mobile\UI\File;

Loader::includeModule('catalog');

final class Wizard
{
	public static function buildProductModel(ProductFromWizard $product, ?int $documentId = null, ?string $documentType = null): DocumentProductRecord
	{
		$document = Document::load($documentId, $documentType);
		$currency = $product->documentCurrency ?? $document->currency;
		$sku = self::loadProduct((int)$product->id);

		$record = DocumentProductRecord::make([
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

		$imageCollection = array_values(array_map(
			fn($item) => File::loadWithPreview($item['ID']),
			$sku->getFrontImageCollection()->toArray()
		));
		if (!empty($imageCollection))
		{
			foreach ($imageCollection as $image)
			{
				$fileId = $image->getId();
				$record->gallery[] = $fileId;
				$record->galleryInfo[$fileId] = $image;
			}
		}

		$record->measure = !empty($product->measureCode)
			? MeasureRepository::findByCode($product->measureCode)
			: MeasureRepository::getDefaultMeasure();

		return $record;
	}

	private static function loadProduct(int $skuId): ?BaseSku
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

		return $sku;
	}
}
