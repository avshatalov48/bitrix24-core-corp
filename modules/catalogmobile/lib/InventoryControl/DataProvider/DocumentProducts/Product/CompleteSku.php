<?php

namespace Bitrix\CatalogMobile\InventoryControl\DataProvider\DocumentProducts\Product;

use Bitrix\Main\Loader;
use Bitrix\CatalogMobile\ProductGrid\SkuDataProvider;
use Bitrix\CatalogMobile\InventoryControl\Dto\DocumentProductRecord;
use Bitrix\CatalogMobile\InventoryControl\UrlBuilder;
use Bitrix\Mobile\UI\File;

Loader::includeModule('catalog');

final class CompleteSku implements Enricher
{
	/**
	 * @param DocumentProductRecord[] $records
	 * @return DocumentProductRecord[]
	 */
	public function enrich(array $records): array
	{
		$productIds = $this->extractProductIds($records);

		if (empty($productIds))
		{
			return $records;
		}

		$result = [];
		$productInfo = SkuDataProvider::load($productIds);

		foreach ($records as $origRecord)
		{
			$record = clone $origRecord;

			if (!$record->productId)
			{
				$result[] = $record;
				continue;
			}

			$record->desktopUrl = UrlBuilder::getProductDetailUrl($record->productId);
			if ($data = $productInfo[$record->productId])
			{
				$record->skuTree = $data['SKU_TREE'];
				$record->type = $data['TYPE'];

				if (empty($record->name))
				{
					$record->name = $data['NAME'];
				}

				if (!empty($data['SECTION_IDS']))
				{
					foreach ($data['SECTION_IDS'] as $sectionId)
					{
						$record->sections[] = [
							'id' => $sectionId,
							'name' => null,
						];
					}
				}

				if (!empty($data['GALLERY']))
				{
					foreach ($data['GALLERY'] as $file)
					{
						$record->gallery[] = $file['ID'];
						$record->galleryInfo[$file['ID']] = File::loadWithPreview($file['ID']);
					}
				}

				$record->measure = $data['MEASURE'];

				if (!empty($data['SKU_TREE']))
				{
					$record->properties = $this->convertSkuProps($data['SKU_TREE']);
				}
			}

			$result[] = $record;
		}

		return $result;
	}

	private function convertSkuProps(array $skuTree): array
	{
		if (empty($skuTree['OFFERS_PROP']))
		{
			return [];
		}

		$convertedProperties = [];

		foreach ($skuTree['OFFERS_PROP'] as $propertyCode => $property)
		{
			$propertyId = $property['ID'];
			$propertyValue = null;
			$displayValue = [];
			if (isset($skuTree['SELECTED_VALUES'][$propertyId]))
			{
				$propertyValue = $skuTree['SELECTED_VALUES'][$propertyId];
			}

			if (empty($propertyValue) || !is_array($property['VALUES']) || empty($property['VALUES']))
			{
				continue;
			}

			foreach ($property['VALUES'] as $singleValue)
			{
				$match = is_array($propertyValue)
					? in_array($singleValue['ID'], $propertyValue)
					: $singleValue['ID'] === $propertyValue;

				if ($match)
				{
					$displayValue[] = $singleValue['NAME'];
				}
			}

			if (!empty($displayValue))
			{
				$convertedProperties[] = [
					'name' => $property['NAME'],
					'value' => join(', ', $displayValue),
				];
			}
		}

		return $convertedProperties;
	}

	/**
	 * @param DocumentProductRecord[] $records
	 * @return int[]
	 */
	private function extractProductIds(array $records): array
	{
		$productIds = [];
		foreach ($records as $record)
		{
			if ($record->productId)
			{
				$productIds[] = (int)$record->productId;
			}
		}
		return array_unique($productIds);
	}
}
