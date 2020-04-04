<?
namespace Bitrix\Crm\Integration\Main\UISelector;

use Bitrix\Main\Localization\Loc;

class CrmProducts extends \Bitrix\Main\UI\Selector\EntityBase
{
	const PREFIX_SHORT = 'PROD_';
	const PREFIX_FULL = 'CRMPRODUCT';

	private static function getPrefix($options = [])
	{
		return (
			is_array($options)
			&& isset($options['prefixType'])
			&& strtolower($options['prefixType']) == 'short'
				? self::PREFIX_SHORT
				: self::PREFIX_FULL
		);
	}

	private static function prepareEntity($data, $options = [])
	{
		$img = '';
		$imgId = $data['PREVIEW_PICTURE'] ? $data['PREVIEW_PICTURE'] : $data['DETAIL_PICTURE'];
		if ($imgId)
		{
			$img = \CFile::ResizeImageGet($imgId, ['width' => 100, 'height' => 100], BX_RESIZE_IMAGE_EXACT)['src'];
		}

		$prefix = self::getPrefix($options);
		$result = [
			'id' => $prefix.$data['ID'],
			'entityType' => 'products',
			'entityId' => $data['ID'],
			'name' => htmlspecialcharsbx($data['NAME']),
			'avatar' => $img,
			'desc' => \CCrmProduct::formatPrice($data)
		];

		return $result;
	}

	public function getData($params = array())
	{
		$options = (!empty($params['options']) ? $params['options'] : []);

		$entityType = Handler::ENTITY_TYPE_CRMPRODUCTS;

		$result = array(
			'ITEMS' => array(),
			'ITEMS_LAST' => array(),
			'ITEMS_HIDDEN' => array(),
			'ADDITIONAL_INFO' => array(
				'GROUPS_LIST' => array(
					'crmproducts' => array(
						'TITLE' => Loc::getMessage('MAIN_UI_SELECTOR_TITLE_CRMPRODUCTS'),
						'TYPE_LIST' => [ $entityType ],
						'DESC_LESS_MODE' => 'N',
						'SORT' => 70
					)
				),
				'SORT_SELECTED' => 400
			)
		);

		$entityOptions = (!empty($params['options']) ? $params['options'] : array());
		$prefix = self::getPrefix($entityOptions);

		$lastItems = (!empty($params['lastItems']) ? $params['lastItems'] : array());
		$selectedItems = (!empty($params['selectedItems']) ? $params['selectedItems'] : array());

		$lastProductsIdList = [];
		if(!empty($lastItems[$entityType]))
		{
			$result["ITEMS_LAST"] = array_map(function($code) use ($prefix) { return preg_replace('/^'.self::PREFIX_FULL.'(\d+)$/', $prefix.'$1', $code); }, array_values($lastItems[$entityType]));
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
		$productsIdList = array_slice($productsIdList, 0, count($selectedProductsIdList) > 50 ? count($selectedProductsIdList) : 50);
		$productsIdList = array_unique($productsIdList);

		$productsList = [];

		$filter = [
			'ACTIVE' => 'Y'
		];
		$order = [ 'ID' => 'DESC' ];

		$select = [ 'ID', 'NAME', 'PRICE', 'CURRENCY_ID', 'DETAIL_PICTURE', 'PREVIEW_PICTURE' ];
		$pricesSelect = $vatSelect = [];
		$select = \CCrmProduct::distributeProductSelect($select, $pricesSelect, $vatSelect);

		if ($options['addTab'] == 'Y')
		{
			$limit = 100;
			$order = ['SHOW_COUNTER' => 'DESC', 'ID' => 'DESC'];
			$extraLimit = $limit - count($productsIdList);
			if ($extraLimit > 0)
			{
				$extraRes = \CCrmProduct::getList(
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

		$res = \CCrmProduct::getList(
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
				$itemsLastIdList[] = $prefix.$productFields['ID'];
			}
		}
		\CCrmProduct::obtainPricesVats($products, $realProductsIdList, $pricesSelect, $vatSelect);
		unset($productsIdList, $pricesSelect, $vatSelect);

		foreach ($products as $product)
		{
			$productsList[$prefix.$product['ID']] = self::prepareEntity($product, $entityOptions);
		}

		if (empty($lastProductsIdList))
		{
			$result["ITEMS_LAST"] = array_slice($itemsLastIdList, 0, 10);
		}

		$result['ITEMS'] = $productsList;

		return $result;
	}

	public function getTabList($params = [])
	{
		$result = [];

		$options = (!empty($params['options']) ? $params['options'] : []);

		if (
			isset($options['addTab'])
			&& $options['addTab'] == 'Y'
		)
		{
			$result = array(
				array(
					'id' => 'products',
					'name' => Loc::getMessage('MAIN_UI_SELECTOR_TAB_CRMPRODUCTS'),
					'sort' => 70
				)
			);
		}

		return $result;
	}

	public function search($params = array())
	{
		$result = array(
			'ITEMS' => array(),
			'ADDITIONAL_INFO' => array()
		);

		$entityOptions = (!empty($params['options']) ? $params['options'] : array());
		$requestFields = (!empty($params['requestFields']) ? $params['requestFields'] : array());
		$search = $requestFields['searchString'];
		$prefix = self::getPrefix($entityOptions);

		if (
			strlen($search) > 0
			&& (
				empty($entityOptions['enableSearch'])
				|| $entityOptions['enableSearch'] != 'N'
			)
		)
		{
			$filter = [
				'%NAME' => $search,
				'ACTIVE' => 'Y'
			];

			$select = array('ID', 'NAME', 'PRICE', 'CURRENCY_ID');
			$pricesSelect = $vatSelect = [];
			$select = \CCrmProduct::distributeProductSelect($select, $pricesSelect, $vatSelect);
			$res = \CCrmProduct::getList(
				[ 'ID' => 'DESC' ],
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

			if (
				!empty($entityOptions['searchById'])
				&& $entityOptions['searchById'] == 'Y'
				&& intval($search) == $search
				&& intval($search) > 0
			)
			{
				$res = \CCrmProduct::getList(
					[ 'ID' => 'DESC' ],
					[
						'=ID' => intval($search)
					],
					$select,
					1
				);

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
			}

			\CCrmProduct::obtainPricesVats($products, $productsIdList, $pricesSelect, $vatSelect);
			unset($productsIdList, $pricesSelect, $vatSelect);

			foreach ($products as $product)
			{
				$result["ITEMS"][$prefix.$product['ID']] = self::prepareEntity($product, $entityOptions);
			}
		}

		return $result;
	}
}