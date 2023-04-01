<?php

namespace Bitrix\Crm\Invoice\Compatible;

use Bitrix\Main\Localization\Loc;
use Bitrix\Sale;

Loc::loadMessages(__FILE__);

class BasketHelper
{
	public static function doGetUserShoppingCart($siteId, $userId, $shoppingCart, &$errors, $orderId = 0,
		$enableCustomCurrency = false)
	{
		$siteId = is_string($siteId) ? trim($siteId) : '';
		if ($siteId == '')
		{
			$errors[] = [
				'CODE' => 'PARAM',
				'TEXT' => Loc::getMessage('CRM_INVOICE_COMPAT_BASKET_HELPER_NO_SITE_ID')
			];
			return null;
		}

		$userId = intval($userId);

		if (!is_array($shoppingCart))
		{
			if (intval($shoppingCart).'|' != $shoppingCart.'|')
			{
				$errors[] = [
					'CODE' => 'PARAM',
					'TEXT' => Loc::getMessage('CRM_INVOICE_COMPAT_BASKET_HELPER_NO_BASKET_ID')
				];
				return null;
			}
			$shoppingCart = intval($shoppingCart);

			$dbShoppingCartItems = static::getList(
				array('NAME' => 'ASC'),
				array(
					'FUSER_ID' => $shoppingCart,
					'LID' => $siteId,
					'ORDER_ID' => 'NULL',
					'DELAY' => 'N',
				),
				false,
				false,
				array(
					'ID', 'LID', 'CALLBACK_FUNC', 'MODULE', 'PRODUCT_ID', 'QUANTITY', 'DELAY',
					'CAN_BUY', 'PRICE', 'WEIGHT', 'NAME', 'CURRENCY', 'CATALOG_XML_ID',
					'VAT_RATE', 'NOTES', 'DISCOUNT_PRICE', 'DETAIL_PAGE_URL', 'PRODUCT_PROVIDER_CLASS',
					'RESERVED', 'DEDUCTED', 'RESERVE_QUANTITY', 'DIMENSIONS', 'TYPE', 'SET_PARENT_ID'
				)
			);
			$arTmp = array();
			while ($arShoppingCartItem = $dbShoppingCartItems->Fetch())
			{
				$arTmp[] = $arShoppingCartItem;
			}

			$shoppingCart = $arTmp;
		}

		// for existing basket we need old data to calculate quantity delta for availability checking
		$arOldShoppingCart = array();
		if ($orderId != 0)
		{
			$dbs = Basket::getList(
				array('NAME' => 'ASC'),
				array(
					'LID' => $siteId,
					'ORDER_ID' => $orderId,
					'DELAY' => 'N',
				),
				false,
				false,
				array(
					'ID', 'LID', 'CALLBACK_FUNC', 'MODULE', 'PRODUCT_ID', 'PRODUCT_PRICE_ID', 'PRICE',
					'QUANTITY', 'DELAY', 'CAN_BUY', 'PRICE', 'WEIGHT', 'NAME', 'CURRENCY',
					'CATALOG_XML_ID', 'VAT_RATE', 'NOTES', 'DISCOUNT_PRICE', 'DETAIL_PAGE_URL', 'PRODUCT_PROVIDER_CLASS',
					'RESERVED', 'DEDUCTED', 'BARCODE_MULTI', 'DIMENSIONS', 'TYPE', 'SET_PARENT_ID'
				)
			);
			while ($arOldShoppingCartItem = $dbs->Fetch())
			{
				$arOldShoppingCart[$arOldShoppingCartItem['ID']] = $arOldShoppingCartItem;
			}
		}

		if (\CSaleHelper::IsAssociativeArray($shoppingCart))
		{
			$shoppingCart = array($shoppingCart);
		}

		if(!is_bool($enableCustomCurrency))
		{
			$enableCustomCurrency = false;
		}

		$result = array();
		$emptyID = 1;

		foreach ($shoppingCart as $itemIndex => $arShoppingCartItem)
		{
			if ((array_key_exists('CALLBACK_FUNC', $arShoppingCartItem)
					&& !empty($arShoppingCartItem['CALLBACK_FUNC']))
				|| (array_key_exists('PRODUCT_PROVIDER_CLASS', $arShoppingCartItem)
					&& !empty($arShoppingCartItem['PRODUCT_PROVIDER_CLASS'])))
			{
				// get quantity difference to check its availability
				if ($orderId != 0)
				{
					$quantity = $arShoppingCartItem['QUANTITY'] -
						$arOldShoppingCart[$arShoppingCartItem['ID_TMP']]['QUANTITY'];
				}
				else
				{
					$quantity = $arShoppingCartItem['QUANTITY'];
				}

				$customPrice = (isset($arShoppingCartItem['CUSTOM_PRICE'])
					&& $arShoppingCartItem['CUSTOM_PRICE'] == 'Y');
				$existBasketID = (isset($arShoppingCartItem['ID']) && (int)$arShoppingCartItem['ID'] > 0);
				/** @var $productProvider \IBXSaleProductProvider */
				if ($productProvider = \CSaleBasket::GetProductProvider($arShoppingCartItem))
				{
					if ($existBasketID)
					{
						$basketID = $arShoppingCartItem['ID'];
					}
					elseif (isset($arShoppingCartItem['ID_TMP']))
					{
						$basketID = $arShoppingCartItem['ID_TMP'];
					}
					else
					{
						$basketID = 'tmp_'.$emptyID;
						$emptyID++;
					}
					$checkCoupons = ('Y' == $arShoppingCartItem['CAN_BUY']
						&& (!array_key_exists('DELAY', $arShoppingCartItem)
							|| 'Y' != $arShoppingCartItem['DELAY']));
					$providerParams = array(
						'PRODUCT_ID' => $arShoppingCartItem['PRODUCT_ID'],
						'QUANTITY'   => ($quantity > 0) ? $quantity : $arShoppingCartItem['QUANTITY'],
						'RENEWAL'    => 'N',
						'USER_ID'    => $userId,
						'SITE_ID'    => $siteId,
						'BASKET_ID' => $basketID,
						'CHECK_QUANTITY' => ($quantity > 0) ? 'Y' : 'N',
						'CHECK_COUPONS' => $checkCoupons ? 'Y' : 'N',
						'CHECK_PRICE' => ($customPrice ? 'N' : 'Y')
					);
					unset($checkCoupons);
					if (isset($arShoppingCartItem['NOTES']))
					{
						$providerParams['NOTES'] = $arShoppingCartItem['NOTES'];
					}
					$arFieldsTmp = $productProvider::GetProductData($providerParams);
					unset($providerParams);
				}
				else
				{
					$arFieldsTmp = \CSaleBasket::ExecuteCallbackFunction(
						$arShoppingCartItem['CALLBACK_FUNC'],
						$arShoppingCartItem['MODULE'],
						$arShoppingCartItem['PRODUCT_ID'],
						$quantity,
						'N',
						$userId,
						$siteId
					);
					if (!empty($arFieldsTmp) && is_array($arFieldsTmp))
					{
						if ($customPrice)
							unset($arFieldsTmp['PRICE'], $arFieldsTmp['CURRENCY']);
					}
				}

				if (!empty($arFieldsTmp) && is_array($arFieldsTmp))
				{
					$arFieldsTmp['CAN_BUY'] = 'Y';
					$arFieldsTmp['SUBSCRIBE'] = 'N';
					$arFieldsTmp['TYPE'] = (int)$arShoppingCartItem['TYPE'];
					$arFieldsTmp['SET_PARENT_ID'] = $arShoppingCartItem['SET_PARENT_ID'];
					$arFieldsTmp['LID'] = $siteId;
				}
				else
				{
					$arFieldsTmp = array('CAN_BUY' => 'N');
				}

				// TODO: ... [DISCOUNT_001] - delete or revert if needed
				/*if (!Sale\Compatible\DiscountCompatibility::isInited())
				{
					Sale\Compatible\DiscountCompatibility::init();
				}
				$basketCode = (Sale\Compatible\DiscountCompatibility::usedByClient() ?
					$arShoppingCartItem['ID'] : $itemIndex);
				Sale\Compatible\DiscountCompatibility::setBasketItemData($basketCode, $arFieldsTmp);*/

				if ($existBasketID)
				{
					$arFieldsTmp['IGNORE_CALLBACK_FUNC'] = 'Y';

					static::update($arShoppingCartItem['ID'], $arFieldsTmp);

					$dbTmp = static::getList(
						array(),
						array('ID' => $arShoppingCartItem['ID']),
						false,
						false,
						array(
							'ID', 'CALLBACK_FUNC', 'MODULE', 'PRODUCT_ID', 'QUANTITY', 'DELAY', 'CAN_BUY', 'PRICE',
							'TYPE', 'SET_PARENT_ID', 'WEIGHT', 'NAME', 'CURRENCY', 'CATALOG_XML_ID', 'VAT_RATE',
							'NOTES', 'DISCOUNT_PRICE', 'DETAIL_PAGE_URL', 'PRODUCT_PROVIDER_CLASS', 'DIMENSIONS'
						)
					);
					$arTmp = $dbTmp->Fetch();

					foreach ($arTmp as $key => $val)
						$arShoppingCartItem[$key] = $val;
				}
				else
				{
					foreach ($arFieldsTmp as $key => $val)
					{
						// update returned quantity for the product if quantity difference is available
						if ($orderId != 0 && $key == 'QUANTITY'
							&& $arOldShoppingCart[$arShoppingCartItem['ID_TMP']]['RESERVED'] == 'Y'
							&& $quantity > 0)
						{
							$arShoppingCartItem[$key] = $val +
								$arOldShoppingCart[$arShoppingCartItem['ID_TMP']]['QUANTITY'];
						}
						else
						{
							$arShoppingCartItem[$key] = $val;
						}
					}
				}
			}

			if ($arShoppingCartItem['CAN_BUY'] == 'Y')
			{
				if(!$enableCustomCurrency)
				{
					$baseLangCurrency = Sale\Internals\SiteCurrencyTable::getSiteCurrency($siteId);
					if ($baseLangCurrency != $arShoppingCartItem['CURRENCY'])
					{
						$arShoppingCartItem['PRICE'] = \CCurrencyRates::ConvertCurrency(
							$arShoppingCartItem['PRICE'],
							$arShoppingCartItem['CURRENCY'],
							$baseLangCurrency
						);
						if (is_set($arShoppingCartItem, 'DISCOUNT_PRICE'))
							$arShoppingCartItem['DISCOUNT_PRICE'] = \CCurrencyRates::ConvertCurrency(
								$arShoppingCartItem['DISCOUNT_PRICE'],
								$arShoppingCartItem['CURRENCY'],
								$baseLangCurrency
							);
						$arShoppingCartItem['CURRENCY'] = $baseLangCurrency;
					}
				}

				$arShoppingCartItem['PRICE'] = Sale\PriceMaths::roundPrecision($arShoppingCartItem['PRICE']);

				$arShoppingCartItem['QUANTITY'] = floatval($arShoppingCartItem['QUANTITY']);
				$arShoppingCartItem['WEIGHT'] = floatval($arShoppingCartItem['WEIGHT'] ?? null);
				$arShoppingCartItem['DIMENSIONS'] = unserialize(($arShoppingCartItem['DIMENSIONS'] ?? null), ['allowed_classes' => false]);
				$arShoppingCartItem['VAT_RATE'] = floatval($arShoppingCartItem['VAT_RATE'] ?? null);
				$arShoppingCartItem['DISCOUNT_PRICE'] = roundEx(($arShoppingCartItem['DISCOUNT_PRICE'] ?? null), SALE_VALUE_PRECISION);

				if ($arShoppingCartItem['VAT_RATE'] > 0)
				{
					$arShoppingCartItem['VAT_VALUE'] = Sale\PriceMaths::roundPrecision(
						($arShoppingCartItem['PRICE'] / ($arShoppingCartItem['VAT_RATE'] + 1)) *
						$arShoppingCartItem['VAT_RATE']
					);
				}

				if ($arShoppingCartItem['DISCOUNT_PRICE'] > 0)
				{
					$arShoppingCartItem['DISCOUNT_PRICE_PERCENT'] = 0.0;
					if ($arShoppingCartItem['DISCOUNT_PRICE'] + $arShoppingCartItem['PRICE'] != 0)
					{
						$arShoppingCartItem['DISCOUNT_PRICE_PERCENT'] = $arShoppingCartItem['DISCOUNT_PRICE'] * 100 /
							($arShoppingCartItem['DISCOUNT_PRICE'] + $arShoppingCartItem['PRICE']);
					}
				}
				$result[$itemIndex] = $arShoppingCartItem;
			}
		}

		return $result;
	}

	/**
	 * @param array $order
	 * @param array $filter
	 * @param bool $group
	 * @param bool $navStartParams
	 * @param array $select
	 *
	 * @return Sale\Compatible\CDBResult
	 */
	public static function getList($order = array(), $filter = array(), $group = false, $navStartParams = false,
		$select = array())
	{
		$result = Basket::getList($order, $filter, $group, $navStartParams, $select);
		if ($result instanceof Sale\Compatible\CDBResult)
			$result->addFetchAdapter(new Sale\Compatible\BasketFetchAdapter());

		return $result;
	}

	/**
	 * @param $id
	 * @param $fields
	 *
	 * @return bool
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentTypeException
	 * @throws \Bitrix\Main\ObjectNotFoundException
	 */
	public static function update($id, $fields)
	{
		global $APPLICATION;

		if (isset($fields["ID"]))
		{
			unset($fields["ID"]);
		}

		$id = (int)$id;
		$basket = new \CSaleBasket();
		$basket->Init();
		unset($basket);

		if (is_set($fields, "QUANTITY") && floatval($fields["QUANTITY"]) <= 0)
		{
			return static::delete($id);
		}

		/** @var Sale\Result $r */
		$r = Basket::update($id, $fields);
		if (!$r->isSuccess())
		{
			foreach($r->getErrorMessages() as $error)
			{
				$APPLICATION->ThrowException($error);
			}

			return false;
		}

		return true;
	}


	/**
	 * @param $id
	 *
	 * @return bool
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ObjectNotFoundException
	 */
	public static function delete($id)
	{
		global $APPLICATION;

		$id = (int)$id;
		if ($id <= 0)
		{
			return false;
		}

		/** @var Sale\Result $r */
		$r = Basket::delete($id);
		if (!$r->isSuccess(true))
		{
			foreach($r->getErrorMessages() as $error)
			{
				$APPLICATION->ThrowException($error);
			}

			return false;
		}

		return true;
	}
}