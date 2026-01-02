<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Integration\Catalog;

use Bitrix\Main\Loader;
use Bitrix\Catalog;
use Bitrix\Crm;

class ServiceSkuProvider
{
	/**
	 * @param int[] $ids
	 * @return Sku[]
	 */
	public function get(array $ids, SkuProviderConfig|null $skuProviderConfig = null): array
	{
		if (empty($ids))
		{
			return [];
		}

		if (
			!(
				Loader::includeModule('catalog')
				&& Loader::includeModule('crm')
				&& Loader::includeModule('iblock')
			)
		)
		{
			return [];
		}

		$productIblockId = Crm\Product\Catalog::getDefaultId();
		if (!$productIblockId)
		{
			return [];
		}

		$productRepository = Catalog\v2\IoC\ServiceContainer::getProductRepository($productIblockId);
		if (!$productRepository)
		{
			return [];
		}

		$result = [];

		$products = $productRepository->getEntitiesBy([
			'filter' => [
				'TYPE' => Catalog\ProductTable::TYPE_SERVICE,
				'ID' => $ids,
			],
		]);

		$product2SectionMap = [];
		/** @var Catalog\v2\Product\Product $product */
		foreach ($products as $product)
		{
			$productId = $product->getId();
			$productName = $product->getName();

			if ($productId === null || $productName === null)
			{
				continue;
			}

			$resultItem = new Sku($productId, $productName);

			$image = $product->getImageCollection()->getFrontImage();
			if ($image)
			{
				$resultItem->setImage($image->getSource());
			}

			$sku = $product->getSkuCollection()->getFirst();
			if ($sku)
			{
				$basePrice = $sku->getPriceCollection()->findBasePrice();
				if ($basePrice)
				{
					$resultItem
						->setPrice($basePrice->getPrice())
						->setCurrency($basePrice->getCurrency())
					;
				}
			}

			if ($skuProviderConfig?->loadSections)
			{
				$section = $product->getSectionCollection()->getFirst();
				if ($section)
				{
					$sectionId = $section->getValue();
					if ($sectionId)
					{
						$product2SectionMap[$productId] = $sectionId;
					}
				}
			}

			$result[] = $resultItem;
		}

		if ($skuProviderConfig?->loadSections && !empty($product2SectionMap))
		{
			$this->setSectionsData($result, $product2SectionMap);
		}

		return $result;
	}

	/**
	 * @param Sku[] $skus
	 * @param array $product2SectionMap
	 * @return void
	 */
	private function setSectionsData(array $skus, array $product2SectionMap): void
	{
		$sectionData = [];
		if (!empty($product2SectionMap))
		{
			$sectionsList = \CIBlockSection::getList(
				['SORT' => 'ASC'],
				['ID' => array_unique($product2SectionMap)],
				false,
				[
					'ID',
					'NAME',
				]
			);
			while ($sectionItem = $sectionsList->fetch())
			{
				$sectionData[$sectionItem['ID']] = $sectionItem['NAME'];
			}
		}

		foreach ($skus as $sku)
		{
			$sectionId = $product2SectionMap[$sku->getId()] ?? null;
			if (!$sectionId)
			{
				continue;
			}

			if (!isset($sectionData[$sectionId]))
			{
				continue;
			}

			$sku->setSection((string)$sectionData[$sectionId]);
		}
	}
}
