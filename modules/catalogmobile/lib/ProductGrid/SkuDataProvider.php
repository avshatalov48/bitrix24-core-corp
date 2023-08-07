<?php

namespace Bitrix\CatalogMobile\ProductGrid;

use Bitrix\Catalog\v2\IoC\ServiceContainer;
use Bitrix\Main\Loader;
use Bitrix\CatalogMobile\Repository\MeasureRepository;

Loader::requireModule('catalog');

final class SkuDataProvider
{
	public static function load(array $productIds): array
	{
		$repositoryFacade = ServiceContainer::getRepositoryFacade();

		if (!$repositoryFacade || empty($productIds))
		{
			return [];
		}

		$productInfo = [];
		$productSkuIblockMap = [];
		foreach ($productIds as $skuId)
		{
			$sku = $repositoryFacade->loadVariation($skuId);
			if (!$sku)
			{
				continue;
			}

			/** @var \Bitrix\Catalog\v2\Product\BaseProduct $product */
			$product = $sku->getParent();
			if (!$product)
			{
				continue;
			}

			$fields = $sku->getFields();
			$basePrice = $sku->getPriceCollection()->findBasePrice();
			$fields['BASE_PRICE'] = $basePrice ? $basePrice->getFields() : null;
			$fields['PRODUCT_ID'] = $product->getId();
			$fields['SKU_ID'] = $skuId;
			$fields['OFFERS_IBLOCK_ID'] = 0;
			$fields['SKU_TREE'] = [];

			$fields['SECTION_IDS'] = $product->getSectionCollection()->getValues();

			$fields['GALLERY'] = $sku->getFrontImageCollection()->toArray();

			$fields['MEASURE'] = MeasureRepository::findById($sku->getField('MEASURE'));

			$fields['BARCODES'] = $sku->getBarcodeCollection()->toArray();

			if (!$product->isSimple())
			{
				$fields['OFFERS_IBLOCK_ID'] = $fields['IBLOCK_ID'];
				$fields['IBLOCK_ID'] = $product->getIblockId();
				$productSkuIblockMap[$product->getIblockId()] = $productSkuIblockMap[$product->getIblockId()] ?? [];
				$productSkuIblockMap[$product->getIblockId()][$product->getId()][] = $sku->getId();
			}

			$productInfo[$skuId] = $fields;
		}

		if ($productSkuIblockMap)
		{
			foreach ($productSkuIblockMap as $iblockId => $productMap)
			{
				$skuTree = ServiceContainer::make('sku.tree', ['iblockId' => $iblockId]);
				if ($skuTree)
				{
					$skuTreeItems = $skuTree->loadWithSelectedOffers($productMap);
					foreach ($skuTreeItems as $productId => $offers)
					{
						foreach ($offers as $skuId => $skuTreeItem)
						{
							if (isset($productInfo[$skuId]))
							{
								$productInfo[$skuId]['SKU_TREE'] = $skuTreeItem;
							}
						}
					}
				}
			}
		}

		return $productInfo;
	}
}
