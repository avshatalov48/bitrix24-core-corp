<?
use Bitrix\Main\Loader;

class CSaleBasketHelper
{
	/*
	* Checks if basket item belongs to set (is parent or item of the set)
	*
	* @param $arItem - basket item fields array with SET_PARENT_ID and TYPE fields
	* @return bool
	*/
	public static function isInSet($arItem)
	{
		if (!empty($arItem["SET_PARENT_ID"]))
			return true;

		return false;
	}

	/*
	* Checks if basket item is set item (belongs to set, but not parent)
	*
	* @param $arItem - basket item fields array with SET_PARENT_ID and TYPE fields
	* @return bool
	*/
	public static function isSetItem($arItem)
	{
		$adminSection = (defined('ADMIN_SECTION') && ADMIN_SECTION === true);

		if (isset($arItem["SET_PARENT_ID"]) && empty($arItem["TYPE"]))
		{
			if (
				($adminSection && $arItem["SET_PARENT_ID"] != '' && $arItem["SET_PARENT_ID"] != 0)
				||
				intval($arItem["SET_PARENT_ID"]) > 0
			)
				return true;
		}

		return false;
	}

	/*
	* Checks if basket item is parent of the set
	*
	* @param $arItem - basket item fields array with SET_PARENT_ID and TYPE fields
	* @return bool
	*/
	public static function isSetParent($arItem)
	{
		return (isset($arItem["TYPE"]) && (int)$arItem["TYPE"] == CSaleBasket::TYPE_SET);
	}

	/*
	* Checks if ALL set items are deducted (to update DEDUCTED = Y of the set parent)
	*
	* @param int $setParentID - set parent id
	* @return bool
	*/
	public static function isSetDeducted($setParentID)
	{
		global $DB;

		$setParentID = intval($setParentID);
		$bItemFound = false;

		if ($setParentID <= 0)
			return false;

		$dbres = $DB->Query("SELECT ID, DEDUCTED FROM b_sale_basket WHERE SET_PARENT_ID = ".$setParentID." AND (TYPE IS NULL OR TYPE = '0')", true);

		while ($arItem = $dbres->GetNext())
		{
			$bItemFound = true;
			if ($arItem["DEDUCTED"] == "N")
				return false;
		}

		if ($bItemFound)
			return true;
		else
			return false;
	}

	/**
	 * Helper method. Is used to re-sort basket items data so Set parents will be added before Set items
	 * @param $arBasketItemA
	 * @param $arBasketItemB
	 * @return int
	 */
	public static function cmpSetData($arBasketItemA, $arBasketItemB)
	{
		if ($arBasketItemA["SET_PARENT_ID"] == "")
			return 0;

		if ($arBasketItemA["TYPE"] == CSaleBasket::TYPE_SET)
			return -1;
		else
			return 1;
	}


	public static function cmpBySort($array1, $array2)
	{
		if (!isset($array1["SORT"])
			|| !isset($array2["SORT"])
			|| ($array1["SORT"] < $array2["SORT"]))
			return -1;

		if ($array1["SORT"] > $array2["SORT"])
			return 1;

		if ($array1["SORT"] == $array2["SORT"])
			return 0;
	}



	public static function filterFields($field)
	{
		if ($field === false || $field === null)
		{
			return false;
		}

		return true;
	}

	/**
	 * resorting of elements in the parent
	 * @param $basketItems
	 * @param bool $setIndexAsId
	 * @return array
	 */
	public static function reSortItems($basketItems, $setIndexAsId = false)
	{
		$basketItemsTmp = $basketItems;
		$parentItems = array();
		$parentItemFound = false;
		foreach ($basketItemsTmp as $basketItemKey => $basketItem)
		{
			if (CSaleBasketHelper::isSetParent($basketItem) || CSaleBasketHelper::isSetItem($basketItem))
			{
				$parentItemFound = true;
				if (!array_key_exists($basketItem['SET_PARENT_ID'], $parentItems))
				{
					$parentItems[$basketItem['SET_PARENT_ID']] = array();
				}

				if (CSaleBasketHelper::isSetItem($basketItem))
				{
					$parentItems[$basketItem['SET_PARENT_ID']][] = $basketItem;
					unset($basketItemsTmp[$basketItemKey]);
				}
			}
		}

		if ($parentItemFound === true && !empty($basketItemsTmp) && is_array($basketItemsTmp)
			&& !empty($parentItems) && is_array($parentItems))
		{
			$basketItems = array();
			foreach ($basketItemsTmp as $basketItem)
			{
				if ($setIndexAsId === true)
				{
					$basketItems[$basketItem['ID']] = $basketItem;
				}
				else
				{
					$basketItems[] = $basketItem;
				}

				if (array_key_exists($basketItem['ID'], $parentItems))
				{
					foreach ($parentItems[$basketItem['ID']] as $childItem)
					{
						if ($setIndexAsId === true)
						{
							$basketItems[$childItem['ID']] = $childItem;
						}
						else
						{
							$basketItems[] = $childItem;
						}
					}
				}
			}
		}

		return $basketItems;
	}


	/**
	 * @param array $basketItemData
	 *
	 * @return float|int
	 */
	public static function getVat(array $basketItemData)
	{
		if (empty($basketItemData['VAT_RATE']) || $basketItemData['VAT_RATE'] == 0)
			return 0;

		if (isset($basketItemData['VAT_INCLUDED']) && $basketItemData['VAT_INCLUDED'] === 'N')
		{
			$vat = \Bitrix\Sale\PriceMaths::roundPrecision(($basketItemData['PRICE'] * $basketItemData['QUANTITY'] * $basketItemData['VAT_RATE']));
		}
		else
		{
			$vat = \Bitrix\Sale\PriceMaths::roundPrecision(($basketItemData['PRICE'] * $basketItemData['QUANTITY'] * $basketItemData['VAT_RATE'] / ($basketItemData['VAT_RATE'] + 1)));
		}

		return $vat;
	}

	public static function getPriceTypeName(array $basketItemData): string
	{
		static $priceTypeList = null;

		if ($priceTypeList === null)
		{
			$priceTypeList = [];
			if (Loader::includeModule('catalog'))
			{
				$priceTypeList = \CCatalogGroup::GetListArray();
			}
		}

		$result = '';
		if (isset($basketItemData['NOTES']) && $basketItemData['NOTES'] !== '')
		{
			$result = $basketItemData['NOTES'];
		}
		elseif (isset($basketItemData['PRICE_TYPE_ID']))
		{
			$typeid = (int)$basketItemData['PRICE_TYPE_ID'];
			if (isset($priceTypeList[$typeid]))
			{
				$result = $priceTypeList[$typeid]['NAME_LANG'] ?: $priceTypeList[$typeid]['NAME'];
			}
		}

		return $result;
	}

	/**
	 * @param array $basketItemData
	 *
	 * @return float|int
	 */
	public static function getFinalPrice(array $basketItemData)
	{
		$price = \Bitrix\Sale\PriceMaths::roundPrecision($basketItemData['PRICE'] * $basketItemData['QUANTITY']);
		if (isset($basketItemData['VAT_INCLUDED']) && $basketItemData['VAT_INCLUDED'] === 'N')
		{
			$price += static::getVat($basketItemData);
		}
		return $price;
	}

	/**
	 * @param $quantity
	 *
	 * @return float
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	public static function formatQuantity($quantity)
	{
		$isOrderConverted = \Bitrix\Main\Config\Option::get("main", "~sale_converted_15", 'Y');

		if ($isOrderConverted != 'N')
		{
			return \Bitrix\Sale\BasketItem::formatQuantity($quantity);
		}

		return roundEx($quantity, SALE_VALUE_PRECISION);
	}

}
