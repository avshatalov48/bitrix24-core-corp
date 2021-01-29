<?php

namespace Bitrix\SalesCenter;

use Bitrix\Catalog\MeasureRatioTable;
use Bitrix\Catalog\MeasureTable;
use Bitrix\Catalog\Model\Price;
use Bitrix\Catalog\Model\Product;
use Bitrix\Catalog\ProductTable;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Web\Json;

/**
 * Class ProductCreatorService
 * @package Bitrix\SalesCenter
 */
class ProductCreatorService
{
	/**
	 * @param array $fields
	 * @return bool|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\ObjectNotFoundException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function createProduct(array $fields)
	{
		$basketFields = Json::decode($fields['encodedFields']);

		if (empty($basketFields['CURRENCY']) || empty($basketFields['PRICE']))
		{
			return null;
		}

		$catalogIblockId = Option::get('crm', 'default_product_catalog_id');
		if (!$catalogIblockId)
		{
			return null;
		}

		$iblockElementFields = [
			'NAME' => $basketFields['NAME'],
			'ACTIVE' => 'Y',
			'IBLOCK_ID' => $catalogIblockId
		];

		if (!empty($fields['image']))
		{
			$files = [];
			foreach ($fields['image'] as $image)
			{
				$files[] = \CAllIBlock::makeFilePropArray($image['data']);
			}

			$propertyData = \CIBlock::GetProperties($catalogIblockId, [], ['CODE'=>'MORE_PHOTO'])->Fetch();
			$isMorePhoto = $propertyData ? true : false;
			if ($isMorePhoto)
			{
				$iblockElementFields['PROPERTY_VALUES'] = [
					'MORE_PHOTO' => $files,
				];
			}
		}

		$this->prepareProductCode($iblockElementFields);

		$elementObject = new \CIBlockElement();
		$productId = $elementObject->Add($iblockElementFields);

		if ((int)$productId <= 0)
		{
			return null;
		}

		$addFields = [
			'ID' => $productId,
			'QUANTITY_TRACE' => ProductTable::STATUS_DEFAULT,
			'CAN_BUY_ZERO' => ProductTable::STATUS_DEFAULT,
			'WEIGHT' => 0,
		];

		if (!empty($basketFields['MEASURE_CODE']))
		{
			$measureRaw = MeasureTable::getList(array(
				'select' => array('ID'),
				'filter' => ['CODE' => $basketFields['MEASURE_CODE']],
				'limit' => 1
			));

			if ($measure = $measureRaw->fetch())
			{
				$addFields['MEASURE'] = $measure['ID'];
			}
		}

		if (
			Option::get('catalog', 'default_quantity_trace') === 'Y'
			&& Option::get('catalog', 'default_can_buy_zero') !== 'Y'
		)
		{
			$addFields['QUANTITY'] = $basketFields['QUANTITY'];
		}

		$r = Product::add($addFields);
		if (!$r->isSuccess())
			return null;

		MeasureRatioTable::add(array(
			'PRODUCT_ID' => $productId,
			'RATIO' => 1
		));

		$priceBaseGroup = \CCatalogGroup::GetBaseGroup();
		$r = Price::add([
			'PRODUCT_ID' => $productId,
			'CATALOG_GROUP_ID' => $priceBaseGroup['ID'],
			'CURRENCY' => $basketFields['CURRENCY'],
			'PRICE' => $basketFields['PRICE'],
		]);

		if (!$r->isSuccess())
		{
			return null;
		}

		return $productId;
	}

	private function prepareProductCode(&$fields): void
	{
		$productName = $fields['NAME'] ?? '';

		if ($productName !== '')
		{
			$fields['CODE'] = mb_strtolower(\CUtil::translit(
					$productName,
					LANGUAGE_ID,
					[
						'replace_space' => '_',
						'replace_other' => '',
					]
				)).'_'.random_int(0, 1000);
		}
	}
}
