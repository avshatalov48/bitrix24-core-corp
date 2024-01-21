<?php

namespace Bitrix\Crm\Integration\Main\UISelector;

use Bitrix\Main\DB;
use Bitrix\Main\Localization\Loc;
use CCrmProduct;
use CDBResult;
use CFile;

class CrmProducts extends CrmBase
{
	public const PREFIX_SHORT = 'PROD_';
	public const PREFIX_FULL = 'CRMPRODUCT';

	protected static function getHandlerType(): string
	{
		return Handler::ENTITY_TYPE_CRMPRODUCTS;
	}

	protected static function prepareEntity($data, $options = []): array
	{
		$img = '';
		$imgId = $data['PREVIEW_PICTURE'] ?? $data['DETAIL_PICTURE'];
		if ($imgId)
		{
			$img = CFile::ResizeImageGet($imgId, ['width' => 100, 'height' => 100], BX_RESIZE_IMAGE_EXACT)['src'];
		}

		$prefix = static::getPrefix($options);

		return [
			'id' => $prefix . $data['ID'],
			'entityType' => 'products',
			'entityId' => $data['ID'],
			'name' => htmlspecialcharsbx($data['NAME']),
			'avatar' => $img,
			'desc' => CCrmProduct::formatPrice($data)
		];
	}

	public function getData($params = []): array
	{
		$options = (!empty($params['options']) ? $params['options'] : []);

		$entityType = static::getHandlerType();

		$result = [
			'ITEMS' => [],
			'ITEMS_LAST' => [],
			'ITEMS_HIDDEN' => [],
			'ADDITIONAL_INFO' => [
				'GROUPS_LIST' => [
					'crmproducts' => [
						'TITLE' => Loc::getMessage('MAIN_UI_SELECTOR_TITLE_CRMPRODUCTS'),
						'TYPE_LIST' => [ $entityType ],
						'DESC_LESS_MODE' => 'N',
						'SORT' => 70,
					],
				],
				'SORT_SELECTED' => 400,
			],
		];

		$entityOptions = (!empty($params['options']) ? $params['options'] : []);
		$prefix = static::getPrefix($entityOptions);

		$lastItems = (!empty($params['lastItems']) ? $params['lastItems'] : []);
		$selectedItems = (!empty($params['selectedItems']) ? $params['selectedItems'] : []);

		$lastProductsIdList = [];
		if(!empty($lastItems[$entityType]))
		{
			$result["ITEMS_LAST"] = array_map(
				function($code) use ($prefix)
				{
					return preg_replace('/^'.self::PREFIX_FULL . '(\d+)$/', $prefix . '$1', $code);
				},
				array_values($lastItems[$entityType])
			);
			foreach ($lastItems[$entityType] as $value)
			{
				$lastProductsIdList[] = str_replace(self::PREFIX_FULL, '', $value);
			}
		}

		$selectedProductsIdList = [];

		if(!empty($selectedItems[$entityType]))
		{
			foreach ($selectedItems[$entityType] as $value)
			{
				$selectedProductsIdList[] = str_replace($prefix, '', $value);
			}
		}

		$productsIdList = array_merge($selectedProductsIdList, $lastProductsIdList);
		$productsIdList = array_slice($productsIdList, 0, max(count($selectedProductsIdList), 50));
		$productsIdList = array_filter($productsIdList);
		$productsIdList = array_unique($productsIdList);

		$productsList = [];

		$filter = ['ACTIVE' => 'Y'];
		$order = [ 'ID' => 'DESC' ];

		$select = $this->getSearchSelect();
		$pricesSelect = $vatSelect = [];
		$select = CCrmProduct::distributeProductSelect($select, $pricesSelect, $vatSelect);

		if ($options['addTab'] == 'Y')
		{
			$limit = 100;
			$order = ['SHOW_COUNTER' => 'DESC', 'ID' => 'DESC'];
			$extraLimit = $limit - count($productsIdList);
			if ($extraLimit > 0)
			{
				$extraRes = CCrmProduct::getList(
					$order,
					$filter,
					['ID'],
					$extraLimit
				);
				while ($product = $extraRes->fetch())
				{
					$productsIdList[] = $product['ID'];
				}
			}
			$filter['ID'] = $productsIdList;
			$limit = false;
		}
		else
		{
			if (!empty($productsIdList))
			{
				$filter['ID'] = $productsIdList;
				$limit = false;
			}
			else
			{
				$limit = 10;
			}
		}

		$res = CCrmProduct::getList(
			$order,
			$filter,
			$select,
			$limit
		);

		$products = [];
		$realProductsIdList = [];
		$itemsLastIdList = [];
		while ($productFields = $res->fetch())
		{
			foreach ($pricesSelect as $fieldName)
			{
				$productFields[$fieldName] = null;
			}
			foreach ($vatSelect as $fieldName)
			{
				$productFields[$fieldName] = null;
			}
			$realProductsIdList[] = $productFields['ID'];
			$products[$productFields['ID']] = $productFields;
			if (in_array($productFields['ID'], $productsIdList) || empty($productsIdList))
			{
				$itemsLastIdList[] = $prefix . $productFields['ID'];
			}
		}
		CCrmProduct::obtainPricesVats($products, $realProductsIdList, $pricesSelect, $vatSelect);
		unset($productsIdList, $pricesSelect, $vatSelect);

		foreach ($products as $product)
		{
			$productsList[$prefix . $product['ID']] = static::prepareEntity($product, $entityOptions);
		}

		if (empty($lastProductsIdList))
		{
			$result["ITEMS_LAST"] = array_slice($itemsLastIdList, 0, 10);
		}

		$result['ITEMS'] = $productsList;

		return $result;
	}

	public function getTabList($params = []): array
	{
		$result = [];

		$options = (!empty($params['options']) ? $params['options'] : []);

		if (
			isset($options['addTab'])
			&& $options['addTab'] == 'Y'
		)
		{
			$result = [
				[
					'id' => 'products',
					'name' => Loc::getMessage('MAIN_UI_SELECTOR_TAB_CRMPRODUCTS'),
					'sort' => 70,
				],
			];
		}

		return $result;
	}

	public function search($params = []): array
	{
		$result = [
			'ITEMS' => [],
			'ADDITIONAL_INFO' => [],
		];

		$entityOptions = (!empty($params['options']) ? $params['options'] : []);
		$requestFields = (!empty($params['requestFields']) ? $params['requestFields'] : []);
		$search = $requestFields['searchString'];
		$prefix = static::getPrefix($entityOptions);

		if (
			$search <> ''
			&& (
				empty($entityOptions['enableSearch'])
				|| $entityOptions['enableSearch'] != 'N'
			)
		)
		{
			$filter = $this->getSearchFilter($search, $entityOptions);

			if ($filter === false)
			{
				return $result;
			}

			$select = $this->getSearchSelect();
			$pricesSelect = $vatSelect = [];
			$select = CCrmProduct::distributeProductSelect($select, $pricesSelect, $vatSelect);
			$res = CCrmProduct::getList(
				$this->getSearchOrder(),
				$filter,
				$select,
				50
			);

			$products = $productsIdList = [];
			while ($productFields = $res->fetch())
			{
				foreach ($pricesSelect as $fieldName)
				{
					$productFields[$fieldName] = null;
				}
				foreach ($vatSelect as $fieldName)
				{
					$productFields[$fieldName] = null;
				}
				$productsIdList[] = $productFields['ID'];
				$products[$productFields['ID']] = $productFields;
			}

			CCrmProduct::obtainPricesVats($products, $productsIdList, $pricesSelect, $vatSelect);
			unset($productsIdList, $pricesSelect, $vatSelect);

			$resultItems = [];
			foreach ($products as $product)
			{
				$resultItems[$prefix . $product['ID']] = static::prepareEntity($product, $entityOptions);
			}

			$resultItems = $this->appendItemsByIds($resultItems, $search, $entityOptions);

			$resultItems = $this->processResultItems($resultItems, $entityOptions);

			$result["ITEMS"] = $resultItems;
		}

		return $result;
	}

	protected function getSearchOrder(): array
	{
		return [ 'ID' => 'DESC' ];
	}

	protected function getSearchSelect(): array
	{
		return [
			'ID',
			'NAME',
			'PRICE',
			'CURRENCY_ID',
			'DETAIL_PICTURE',
			'PREVIEW_PICTURE',
		];
	}

	protected function getSearchFilter(string $search, array $options)
	{
		$filter = [
			'%NAME' => $search,
			'ACTIVE' => 'Y'
		];

		return $this->prepareOptionalFilter($filter, $options);
	}

	protected function getByIdsFilter(array $ids, array $options): array
	{
		return $this->prepareOptionalFilter(['ID' => $ids], $options);
	}

	protected function getByIdsRes(array $ids, array $options)
	{
		return null;
	}

	protected function getByIdsResultItems(array $ids, array $options): array
	{
		$result = [];

		$prefix = static::getPrefix($options);
		$select = $this->getSearchSelect();
		$pricesSelect = [];
		$vatSelect = [];
		$select = CCrmProduct::distributeProductSelect($select, $pricesSelect, $vatSelect);
		$res = CCrmProduct::getList(
			$this->getByIdsOrder(),
			$this->getByIdsFilter($ids, $options),
			$select
		);

		$products = [];
		while ($productFields = $res->fetch())
		{
			foreach ($pricesSelect as $fieldName)
			{
				$productFields[$fieldName] = null;
			}
			foreach ($vatSelect as $fieldName)
			{
				$productFields[$fieldName] = null;
			}
			$products[$productFields['ID']] = $productFields;
		}

		CCrmProduct::obtainPricesVats($products, $ids, $pricesSelect, $vatSelect);
		unset($pricesSelect, $vatSelect);

		foreach ($products as $product)
		{
			$result[$prefix . $product['ID']] = static::prepareEntity($product, $options);
		}

		return $result;
	}
}