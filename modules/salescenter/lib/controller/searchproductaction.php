<?php

namespace Bitrix\SalesCenter\Controller;

use Bitrix\Catalog\MeasureRatioTable;
use Bitrix\Main;

class SearchProductAction extends Main\Search\SearchAction
{
	/**
	 * BX.ajax.runAction("salescenter.api.product.search", { data: { searchQuery: "dress", options: {} } });
	 *
	 * @param string $searchQuery
	 * @param array|null $options
	 * @param Main\UI\PageNavigation|null $pageNavigation
	 *
	 * @return array|Main\Search\ResultItem[]
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function provideData($searchQuery, array $options = null, Main\UI\PageNavigation $pageNavigation = null)
	{
		$result = [];
		Main\Loader::includeModule('iblock');
		Main\Loader::includeModule('catalog');

		$filter = array(
			"CHECK_PERMISSIONS" => "N",
			"ACTIVE" => "Y",
			"?NAME" => $searchQuery
		);

		$catalogIblockId = Main\Config\Option::get('crm', 'default_product_catalog_id');
		if ((int)$catalogIblockId > 0)
		{
			$filter['IBLOCK_ID'] = (int)$catalogIblockId;
		}

		$catalogData = \CIBlockElement::GetList(
			[],
			$filter,
			false,
			['nTopCount' => 20],
			['ID', 'NAME']
		);

		$products = [];
		while ($product = $catalogData->Fetch())
		{
			$products[$product['ID']] = [
				'NAME' => $product['NAME'],
				'ID' => $product['ID'],
			];
		}

		if (!empty($products))
		{
			$productOffersMap = \CCatalogSKU::getOffersList(
				array_keys($products),
				0,
				['ACTIVE' => 'Y'],
				['NAME', 'ID']
			);

			$skuIblockList = [];
			if (is_array($productOffersMap))
			{
				foreach ($productOffersMap as $productId => $offersList)
				{
					if (empty($productId))
						continue;

					unset($products[$productId]);
					foreach ($offersList as $item)
					{
						$skuIblockList[$item['IBLOCK_ID']][$item['ID']] = [];
						$products[$item['ID']] = [
							'NAME' => $item['NAME'],
							'ID' => $item['ID'],
						];
					}
				}
			}

			if (!empty($skuIblockList))
			{
				foreach ($skuIblockList as $iblockId=>$skuMap)
				{
					if (!is_array($skuMap))
						continue;

					\CIblockElement::GetPropertyValuesArray(
						$skuMap, $iblockId, ['ID' => array_keys($skuMap)]
					);

					foreach ($skuMap as $elementId=>$properties)
					{
						if (!is_array($properties))
							continue;

						$formatedProperties = [];
						foreach ($properties as $property)
						{
							if ($property['CODE'] === 'CML2_LINK' || (empty($property['VALUE']) && $property['VALUE'] !== 0))
								continue;

							$formatedProperties[] = htmlspecialcharsbx($property['NAME']).": ".htmlspecialcharsbx($property['VALUE']);
						}

						$products[$elementId]['PROPERTIES'] = $formatedProperties;
					}
				}
			}
		}

		if (!empty($products))
		{
			$productIds = array_column($products, 'ID');
			$measureRatios = MeasureRatioTable::getCurrentRatio($productIds);

			foreach ($products as $product)
			{
				if (
					!empty($options['restrictedSearchIds'])
					&& is_array($options['restrictedSearchIds'])
					&& in_array($product['ID'], $options['restrictedSearchIds'])
				)
				{
					continue;
				}

				$resultItem = new Main\Search\ResultItem(
					$product['NAME'], '', (int)$product['ID']
				);
				if (!empty($product['PROPERTIES']) && is_array($product['PROPERTIES']))
				{
					$resultItem->setSubTitle(implode(', ',$product['PROPERTIES']));
				}
				$resultItem->setAttribute('measureRatio', $measureRatios[(int)$product['ID']]);
				$result[] = $resultItem;
			}
		}

		return $result;
	}
}