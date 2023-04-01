<?php

use Bitrix\Main\Loader;
define("SALE_DEBUG", false); // Debug

IncludeModuleLangFile(__FILE__);

$GLOBALS["SALE_FIELD_TYPES"] = array(
	"TEXT" => GetMessage("SALE_TYPE_TEXT"),
	"CHECKBOX" => GetMessage("SALE_TYPE_CHECKBOX"),
	"SELECT" => GetMessage("SALE_TYPE_SELECT"),
	"MULTISELECT" => GetMessage("SALE_TYPE_MULTISELECT"),
	"TEXTAREA" => GetMessage("SALE_TYPE_TEXTAREA"),
	"LOCATION" => GetMessage("SALE_TYPE_LOCATION"),
	"RADIO" => GetMessage("SALE_TYPE_RADIO"),
	"FILE" => GetMessage("SALE_TYPE_FILE")
);

if (!Loader::includeModule('currency'))
	return false;

// Number of processed recurring records at one time
define("SALE_PROC_REC_NUM", 3);
// Number of recurring payment attempts
define("SALE_PROC_REC_ATTEMPTS", 3);
// Time between recurring payment attempts (in seconds)
define("SALE_PROC_REC_TIME", 43200);

define("SALE_PROC_REC_FREQUENCY", 7200);
// Owner ID base name used by CSale<etnity_name>ReportHelper clases for managing the reports.
define("SALE_REPORT_OWNER_ID", 'sale');
//cache orders flag for real-time exhange with 1C
define("CACHED_b_sale_order", 3600*24);

global $SALE_TIME_PERIOD_TYPES;
$SALE_TIME_PERIOD_TYPES = array(
	"H" => GetMessage("I_PERIOD_HOUR"),
	"D" => GetMessage("I_PERIOD_DAY"),
	"W" => GetMessage("I_PERIOD_WEEK"),
	"M" => GetMessage("I_PERIOD_MONTH"),
	"Q" => GetMessage("I_PERIOD_QUART"),
	"S" => GetMessage("I_PERIOD_SEMIYEAR"),
	"Y" => GetMessage("I_PERIOD_YEAR")
);

define("SALE_VALUE_PRECISION", 4);
define("SALE_WEIGHT_PRECISION", 3);

define('BX_SALE_MENU_CATALOG_CLEAR', 'Y');

$GLOBALS["AVAILABLE_ORDER_FIELDS"] = array(
	"ID" => array("COLUMN_NAME" => "ID", "NAME" => GetMessage("SI_ORDER_ID"), "SELECT" => "ID,DATE_INSERT", "CUSTOM" => "Y", "SORT" => "ID"),
	"LID" => array("COLUMN_NAME" => GetMessage("SI_SITE"), "NAME" => GetMessage("SI_SITE"), "SELECT" => "LID", "CUSTOM" => "N", "SORT" => "LID"),
	"PERSON_TYPE" => array("COLUMN_NAME" => GetMessage("SI_PAYER_TYPE"), "NAME" => GetMessage("SI_PAYER_TYPE"), "SELECT" => "PERSON_TYPE_ID", "CUSTOM" => "Y", "SORT" => "PERSON_TYPE_ID"),
	"PAYED" => array("COLUMN_NAME" => GetMessage("SI_PAID"), "NAME" => GetMessage("SI_PAID_ORDER"), "SELECT" => "PAYED,DATE_PAYED,EMP_PAYED_ID", "CUSTOM" => "Y", "SORT" => "PAYED"),
	"PAY_VOUCHER_NUM" => array("COLUMN_NAME" => GetMessage("SI_NO_PP"), "NAME" => GetMessage("SI_NO_PP_DOC"), "SELECT" => "PAY_VOUCHER_NUM", "CUSTOM" => "N", "SORT" => "PAY_VOUCHER_NUM"),
	"PAY_VOUCHER_DATE" => array("COLUMN_NAME" => GetMessage("SI_DATE_PP"), "NAME" => GetMessage("SI_DATE_PP_DOC"), "SELECT" => "PAY_VOUCHER_DATE", "CUSTOM" => "N", "SORT" => "PAY_VOUCHER_DATE"),
	"DELIVERY_DOC_NUM" => array("COLUMN_NAME" => GetMessage("SI_DATE_PP_DELIVERY_DOC_NUM"), "NAME" => GetMessage("SI_DATE_PP_DOC_DELIVERY_DOC_NUM"), "SELECT" => "DELIVERY_DOC_NUM", "CUSTOM" => "N", "SORT" => "DELIVERY_DOC_NUM"),
	"DELIVERY_DOC_DATE" => array("COLUMN_NAME" => GetMessage("SI_DATE_PP_DELIVERY_DOC_DATE"), "NAME" => GetMessage("SI_DATE_PP_DOC_DELIVERY_DOC_DATE"), "SELECT" => "DELIVERY_DOC_DATE", "CUSTOM" => "N", "SORT" => "DELIVERY_DOC_DATE"),
	"CANCELED" => array("COLUMN_NAME" => GetMessage("SI_CANCELED"), "NAME" => GetMessage("SI_CANCELED_ORD"), "SELECT" => "CANCELED,DATE_CANCELED,EMP_CANCELED_ID", "CUSTOM" => "Y", "SORT" => "CANCELED"),
	"STATUS" => array("COLUMN_NAME" => GetMessage("SI_STATUS"), "NAME" => GetMessage("SI_STATUS_ORD"), "SELECT" => "STATUS_ID,DATE_STATUS,EMP_STATUS_ID", "CUSTOM" => "Y", "SORT" => "STATUS_ID"),
	"PRICE_DELIVERY" => array("COLUMN_NAME" => GetMessage("SI_DELIVERY"), "NAME" => GetMessage("SI_DELIVERY"), "SELECT" => "PRICE_DELIVERY,CURRENCY", "CUSTOM" => "Y", "SORT" => "PRICE_DELIVERY"),
	"ALLOW_DELIVERY" => array("COLUMN_NAME" => GetMessage("SI_ALLOW_DELIVERY"), "NAME" => GetMessage("SI_ALLOW_DELIVERY1"), "SELECT" => "ALLOW_DELIVERY,DATE_ALLOW_DELIVERY,EMP_ALLOW_DELIVERY_ID", "CUSTOM" => "Y", "SORT" => "ALLOW_DELIVERY"),
	"PRICE" => array("COLUMN_NAME" => GetMessage("SI_SUM"), "NAME" => GetMessage("SI_SUM_ORD"), "SELECT" => "PRICE,CURRENCY", "CUSTOM" => "Y", "SORT" => "PRICE"),
	"SUM_PAID" => array("COLUMN_NAME" => GetMessage("SI_SUM_PAID"), "NAME" => GetMessage("SI_SUM_PAID1"), "SELECT" => "SUM_PAID,CURRENCY", "CUSTOM" => "Y", "SORT" => "SUM_PAID"),
	"USER" => array("COLUMN_NAME" => GetMessage("SI_BUYER"), "NAME" => GetMessage("SI_BUYER"), "SELECT" => "USER_ID", "CUSTOM" => "Y", "SORT" => "USER_ID"),
	"PAY_SYSTEM" => array("COLUMN_NAME" => GetMessage("SI_PAY_SYS"), "NAME" => GetMessage("SI_PAY_SYS"), "SELECT" => "PAY_SYSTEM_ID", "CUSTOM" => "Y", "SORT" => "PAY_SYSTEM_ID"),
	"DELIVERY" => array("COLUMN_NAME" => GetMessage("SI_DELIVERY_SYS"), "NAME" => GetMessage("SI_DELIVERY_SYS"), "SELECT" => "DELIVERY_ID", "CUSTOM" => "Y", "SORT" => "DELIVERY_ID"),
	"DATE_UPDATE" => array("COLUMN_NAME" => GetMessage("SI_DATE_UPDATE"), "NAME" => GetMessage("SI_DATE_UPDATE"), "SELECT" => "DATE_UPDATE", "CUSTOM" => "N", "SORT" => "DATE_UPDATE"),
	"PS_STATUS" => array("COLUMN_NAME" => GetMessage("SI_PAYMENT_PS"), "NAME" => GetMessage("SI_PS_STATUS"), "SELECT" => "PS_STATUS,PS_RESPONSE_DATE", "CUSTOM" => "N", "SORT" => "PS_STATUS"),
	"PS_SUM" => array("COLUMN_NAME" => GetMessage("SI_PS_SUM"), "NAME" => GetMessage("SI_PS_SUM1"), "SELECT" => "PS_SUM,PS_CURRENCY", "CUSTOM" => "Y", "SORT" => "PS_SUM"),
	"TAX_VALUE" => array("COLUMN_NAME" => GetMessage("SI_TAX"), "NAME" => GetMessage("SI_TAX_SUM"), "SELECT" => "TAX_VALUE,CURRENCY", "CUSTOM" => "Y", "SORT" => "TAX_VALUE"),
	"BASKET" => array("COLUMN_NAME" => GetMessage("SI_ITEMS"), "NAME" => GetMessage("SI_ITEMS_ORD"), "SELECT" => "", "CUSTOM" => "Y", "SORT" => "")
);

require_once __DIR__.'/autoload.php';

$psConverted = \Bitrix\Main\Config\Option::get('main', '~sale_paysystem_converted');
if ($psConverted == '')
{
	CAdminNotify::Add(
		array(
			"MESSAGE" => GetMessage("SALE_PAYSYSTEM_CONVERT_ERROR", array('#LANG#' => LANGUAGE_ID)),
			"TAG" => "SALE_PAYSYSTEM_CONVERT_ERROR",
			"MODULE_ID" => "sale",
			"ENABLE_CLOSE" => "Y",
			"PUBLIC_SECTION" => "N"
		)
	);
}

function GetBasketListSimple($bSkipFUserInit = true)
{
	$fUserID = (int)CSaleBasket::GetBasketUserID($bSkipFUserInit);
	if ($fUserID > 0)
		return CSaleBasket::GetList(
			array("NAME" => "ASC"),
			array("FUSER_ID" => $fUserID, "LID" => SITE_ID, "ORDER_ID" => "NULL")
		);
	else
		return False;
}

function GetBasketList($bSkipFUserInit = true)
{
	$fUserID = (int)CSaleBasket::GetBasketUserID($bSkipFUserInit);
	$arRes = array();
	if ($fUserID > 0)
	{
		$basketID = array();
		$db_res = CSaleBasket::GetList(
			array(),
			array("FUSER_ID" => $fUserID, "LID" => SITE_ID, "ORDER_ID" => false),
			false,
			false,
			array('ID', 'CALLBACK_FUNC', 'PRODUCT_PROVIDER_CLASS', 'MODULE', 'PRODUCT_ID', 'QUANTITY', 'NOTES')
		);
		while ($res = $db_res->Fetch())
		{
			$res['CALLBACK_FUNC'] = (string)$res['CALLBACK_FUNC'];
			$res['PRODUCT_PROVIDER_CLASS'] = (string)$res['PRODUCT_PROVIDER_CLASS'];
			if ($res['CALLBACK_FUNC'] != '' || $res['PRODUCT_PROVIDER_CLASS'] != '')
				CSaleBasket::UpdatePrice($res["ID"], $res["CALLBACK_FUNC"], $res["MODULE"], $res["PRODUCT_ID"], $res["QUANTITY"], 'N', $res["PRODUCT_PROVIDER_CLASS"], $res['NOTES']);
			$basketID[] = $res['ID'];
		}
		unset($res, $db_res);
		if (!empty($basketID))
		{
			$basketIterator = CSaleBasket::GetList(
				array('NAME' => 'ASC'),
				array('ID' => $basketID)
			);
			while ($basket = $basketIterator->GetNext())
				$arRes[] = $basket;
			unset($basket, $basketIterator);
		}
		unset($basketID);
	}
	return $arRes;
}

function SaleFormatCurrency($fSum, $strCurrency, $OnlyValue = false, $withoutFormat = false)
{
	if ($withoutFormat === true)
	{
		if ($fSum === '')
			return '';

		$currencyFormat = CCurrencyLang::GetFormatDescription($strCurrency);
		if ($currencyFormat === false)
		{
			$currencyFormat = CCurrencyLang::GetDefaultValues();
		}

		$fSum = (float)$fSum;

		$intDecimals = $currencyFormat['DECIMALS'];
		if (round($fSum, $currencyFormat["DECIMALS"]) == round($fSum, 0))
			$intDecimals = 0;

		return number_format($fSum, $intDecimals, '.','');
	}

	return CCurrencyLang::CurrencyFormat($fSum, $strCurrency, !($OnlyValue === true));
}

function AutoPayOrder($ORDER_ID)
{
	$ORDER_ID = (int)$ORDER_ID;
	if ($ORDER_ID <= 0)
		return false;

	$arOrder = CSaleOrder::GetByID($ORDER_ID);
	if (!$arOrder)
		return false;
	if ($arOrder["PS_STATUS"] != "Y")
		return false;
	if ($arOrder["PAYED"] != "N")
		return false;

	if ($arOrder["CURRENCY"] == $arOrder["PS_CURRENCY"]
		&& DoubleVal($arOrder["PRICE"]) == DoubleVal($arOrder["PS_SUM"]))
	{
		if (CSaleOrder::PayOrder($arOrder["ID"], "Y", true, false))
			return true;
	}

	return false;
}

function CurrencyModuleUnInstallSale()
{
	$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SALE_INCLUDE_CURRENCY"), "SALE_DEPENDES_CURRENCY");
	return false;
}

if (file_exists($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/ru/include.php"))
	include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/ru/include.php");

function PayUserAccountDeliveryOrderCallback($productID, $userID, $bPaid, $orderID, $quantity = 1)
{
	global $DB;

	$productID = intval($productID);
	$userID = intval($userID);
	$bPaid = ($bPaid ? True : False);
	$orderID = intval($orderID);

	if ($userID <= 0)
		return False;

	if ($orderID <= 0)
		return False;

	if (!($arOrder = CSaleOrder::GetByID($orderID)))
		return False;

	$baseLangCurrency = CSaleLang::GetLangCurrency($arOrder["LID"]);
	$arAmount = unserialize(
		COption::GetOptionString(
			"sale",
			"pay_amount",
			'a:4:{i:1;a:2:{s:6:"AMOUNT";s:2:"10";s:8:"CURRENCY";s:3:"EUR";}i:2;a:2:{s:6:"AMOUNT";s:2:"20";s:8:"CURRENCY";s:3:"EUR";}i:3;a:2:{s:6:"AMOUNT";s:2:"30";s:8:"CURRENCY";s:3:"EUR";}i:4;a:2:{s:6:"AMOUNT";s:2:"40";s:8:"CURRENCY";s:3:"EUR";}}'
		),
		['allowed_classes' => false]
	);
	if (!array_key_exists($productID, $arAmount))
		return False;

	$currentPrice = $arAmount[$productID]["AMOUNT"] * $quantity;
	$currentCurrency = $arAmount[$productID]["CURRENCY"];
	if ($arAmount[$productID]["CURRENCY"] != $baseLangCurrency)
	{
		$currentPrice = CCurrencyRates::ConvertCurrency($arAmount[$productID]["AMOUNT"], $arAmount[$productID]["CURRENCY"], $baseLangCurrency) * $quantity;
		$currentCurrency = $baseLangCurrency;
	}

	if (!CSaleUserAccount::UpdateAccount($userID, ($bPaid ? $currentPrice : -$currentPrice), $currentCurrency, "MANUAL", $orderID, "Payment to user account"))
		return False;

	return True;
}

/*
* Formats user name. Used everywhere in 'sale' module
*
*/
function GetFormatedUserName($userId, $bEnableId = true, $createEditLink = true)
{
	static $formattedUsersName = array();
	static $siteNameFormat = '';

	$result = (!is_array($userId)) ? '' : array();
	$newUsers = array();

	if (is_array($userId))
	{
		foreach ($userId as $id)
		{
			if (!isset($formattedUsersName[$id]))
				$newUsers[] = $id;
		}
	}
	else if(!isset($formattedUsersName[$userId]))
	{
		$newUsers[] = $userId;
	}

	if (count($newUsers) > 0)
	{
		$resUsers = \Bitrix\Main\UserTable::getList(
			array(
				'select' => array('ID', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'LOGIN', 'EMAIL'),
				'filter' => array('ID' => $newUsers)
			)
		);
		while ($arUser = $resUsers->Fetch())
		{
			if ($siteNameFormat == '')
				$siteNameFormat = CSite::GetNameFormat(false);
			$formattedUsersName[$arUser['ID']] = CUser::FormatName($siteNameFormat, $arUser, true, true);
		}
	}

	$publicMode = (defined("PUBLIC_MODE") && PUBLIC_MODE == 1);
	$selfFolderUrl = (defined("SELF_FOLDER_URL") ? SELF_FOLDER_URL : "/bitrix/admin/");
	if ($publicMode)
	{
		$bEnableId = false;
		global $adminSidePanelHelper;
		if (!is_object($adminSidePanelHelper))
		{
			require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/interface/admin_lib.php");
			$adminSidePanelHelper = new CAdminSidePanelHelper();
		}
	}
	if (is_array($userId))
	{
		foreach ($userId as $uId)
		{
			if (CBXFeatures::IsFeatureEnabled('SaleAccounts') && !$createEditLink)
			{
				$userUrl = $selfFolderUrl."sale_buyers_profile.php?USER_ID=".$uId."&lang=".LANGUAGE_ID;
			}
			else
			{
				$userUrl = $selfFolderUrl."user_edit.php?ID=".$uId."&lang=".LANGUAGE_ID;
			}
			if ($publicMode)
			{
				$userUrl = $adminSidePanelHelper->editUrlToPublicPage($userUrl);
			}
			$formatted = '';
			if ($bEnableId)
				$formatted = '[<a href="/bitrix/admin/user_edit.php?ID='.$uId.'&lang='.LANGUAGE_ID.'">'.$uId.'</a>] ';

			$formatted .= '<a href="'.$userUrl.'">';
			$formatted .= $formattedUsersName[$uId];

			$formatted .= '</a>';

			$result[$uId] = $formatted;
		}
	}
	else
	{
		if ($bEnableId)
			$result .= '[<a href="/bitrix/admin/user_edit.php?ID='.$userId.'&lang='.LANGUAGE_ID.'">'.$userId.'</a>] ';

		if (CBXFeatures::IsFeatureEnabled('SaleAccounts') && !$createEditLink)
		{
			$userUrl = $selfFolderUrl."sale_buyers_profile.php?USER_ID=".$userId."&lang=".LANGUAGE_ID;
		}
		else
		{
			$userUrl = $selfFolderUrl."user_edit.php?ID=".$userId."&lang=".LANGUAGE_ID;
		}
		if ($publicMode)
		{
			$userUrl = $adminSidePanelHelper->editUrlToPublicPage($userUrl);
		}

		$result .= '<a href="'.$userUrl.'">';

		$result .= $formattedUsersName[$userId];

		$result .= '</a>';
	}

	return $result;
}

/*
 * Updates basket item arrays with information about measures from catalog
 * Basically adds MEASURE_TEXT field with the measure name to each basket item array
 *
 * @param array $arBasketItems - array of basket items' arrays
 * @return array|bool
 */
function getMeasures($arBasketItems)
{
	static $measures = array();
	$newMeasure = array();
	if (Loader::includeModule('catalog'))
	{
		$arDefaultMeasure = CCatalogMeasure::getDefaultMeasure(true, true);
		$arElementId = array();
		$basketLinks = array();
		foreach ($arBasketItems as $keyBasket => $arItem)
		{
			if (isset($arItem['MEASURE_NAME']) && $arItem['MEASURE_NAME'] <> '')
			{
				$measureText = $arItem['MEASURE_NAME'];
				$measureCode = intval($arItem['MEASURE_CODE']);
			}
			else
			{
				$productID = (int)$arItem["PRODUCT_ID"];
				if (!isset($basketLinks[$productID]))
					$basketLinks[$productID] = array();
				$basketLinks[$productID][] = $keyBasket;
				$arElementId[] = $productID;

				$measureText = $arDefaultMeasure['~SYMBOL_RUS'];
				$measureCode = 0;
			}

			$arBasketItems[$keyBasket]['MEASURE_TEXT'] = $measureText;
			$arBasketItems[$keyBasket]['MEASURE'] = $measureCode;
		}
		unset($productID, $keyBasket, $arItem);

		if (!empty($arElementId))
		{
			$arBasket2Measure = array();
			$dbres = CCatalogProduct::GetList(
				array(),
				array("ID" => $arElementId),
				false,
				false,
				array("ID", "MEASURE")
			);
			while ($arRes = $dbres->Fetch())
			{
				$arRes['ID'] = (int)$arRes['ID'];
				$arRes['MEASURE'] = (int)$arRes['MEASURE'];
				if ($arRes['MEASURE'] <= 0)
					continue;
				if (!isset($arBasket2Measure[$arRes['MEASURE']]))
					$arBasket2Measure[$arRes['MEASURE']] = array();
				$arBasket2Measure[$arRes['MEASURE']][] = $arRes['ID'];

				if (!isset($measures[$arRes['MEASURE']]) && !in_array($arRes['MEASURE'], $newMeasure))
					$newMeasure[] = $arRes['MEASURE'];
			}
			unset($arRes, $dbres);

			if (!empty($newMeasure))
			{
				$dbMeasure = CCatalogMeasure::GetList(
					array(),
					array("ID" => array_values($newMeasure)),
					false,
					false,
					array('ID', 'SYMBOL_RUS', 'CODE')
				);
				while ($arMeasure = $dbMeasure->Fetch())
					$measures[$arMeasure['ID']] = $arMeasure;
			}

			foreach ($arBasket2Measure as $measureId => $productIds)
			{
				if (!isset($measures[$measureId]))
					continue;
				foreach ($productIds as $productId)
				{
					if (isset($basketLinks[$productId]) && !empty($basketLinks[$productId]))
					{
						foreach ($basketLinks[$productId] as $keyBasket)
						{
							$arBasketItems[$keyBasket]['MEASURE_TEXT'] = $measures[$measureId]['SYMBOL_RUS'];
							$arBasketItems[$keyBasket]['MEASURE'] = $measures[$measureId]['ID'];
						}
					}
				}
			}
		}
	}
	return $arBasketItems;
}

/*
 * Updates basket items' arrays with information about ratio from catalog
 * Basically adds MEASURE_RATIO field with the ratio coefficient to each basket item array
 *
 * @param array $arBasketItems - array of basket items' arrays
 * @return mixed
 */
function getRatio($arBasketItems)
{
	if (Loader::includeModule('catalog'))
	{
		static $cacheRatio = array();

		$helperCacheRatio = \Bitrix\Sale\BasketComponentHelper::getRatioDataCache();
		if (is_array($helperCacheRatio) && !empty($helperCacheRatio))
		{
			$cacheRatio = array_merge($cacheRatio, $helperCacheRatio);
		}

		$map = array();
		$arElementId = array();
		foreach ($arBasketItems as $key => $arItem)
		{
			if (
				(isset($arBasketItems[$key]['MEASURE_RATIO_VALUE']) && (float)$arBasketItems[$key]['MEASURE_RATIO_VALUE'] > 0)
				&& (isset($arBasketItems[$key]['MEASURE_RATIO_ID']) && (int)$arBasketItems[$key]['MEASURE_RATIO_ID'] > 0)
			)
				continue;

			$hash = md5((!empty($arItem['PRODUCT_PROVIDER_CLASS']) ? $arItem['PRODUCT_PROVIDER_CLASS']: "")."|".(!empty($arItem['MODULE']) ? $arItem['MODULE']: "")."|".$arItem["PRODUCT_ID"]);

			if (isset($cacheRatio[$hash]))
			{
				if (isset($cacheRatio[$hash]['RATIO']))
				{
					$arBasketItems[$key]["MEASURE_RATIO"] = $cacheRatio[$hash]['RATIO']; // old key
					$arBasketItems[$key]["MEASURE_RATIO_VALUE"] = $cacheRatio[$hash]["RATIO"];
				}

				if (isset($cacheRatio[$hash]['ID']))
				{
					$arBasketItems[$key]["MEASURE_RATIO_ID"] = $cacheRatio[$hash]["ID"];
				}

			}
			else
			{
				$arElementId[$arItem["PRODUCT_ID"]] = $arItem["PRODUCT_ID"];
			}

			if (!isset($map[$arItem["PRODUCT_ID"]]))
			{
				$map[$arItem["PRODUCT_ID"]] = array();
			}

			$map[$arItem["PRODUCT_ID"]][] = $key;
		}

		if (!empty($arElementId))
		{
			$dbRatio = \Bitrix\Catalog\MeasureRatioTable::getList(array(
				'select' => array('*'),
				'filter' => array('@PRODUCT_ID' => $arElementId, '=IS_DEFAULT' => 'Y')
			));
			while ($arRatio = $dbRatio->fetch())
			{
				if (empty($map[$arRatio["PRODUCT_ID"]]))
					continue;

				foreach ($map[$arRatio["PRODUCT_ID"]] as $key)
				{
					$arBasketItems[$key]["MEASURE_RATIO"] = $arRatio["RATIO"]; // old key
					$arBasketItems[$key]["MEASURE_RATIO_ID"] = $arRatio["ID"];
					$arBasketItems[$key]["MEASURE_RATIO_VALUE"] = $arRatio["RATIO"];

					$itemData = $arBasketItems[$key];

					$hash = md5((!empty($itemData['PRODUCT_PROVIDER_CLASS']) ? $itemData['PRODUCT_PROVIDER_CLASS']: "")."|".(!empty($itemData['MODULE']) ? $itemData['MODULE']: "")."|".$itemData["PRODUCT_ID"]);

					$cacheRatio[$hash] = $arRatio;
				}
				unset($key);
			}
			unset($arRatio, $dbRatio);
		}
		unset($arElementId, $map);
	}
	return $arBasketItems;
}

/*
 * Creates an array of iblock properties for the elements with certain IDs
 *
 * @param array $arElementId - array of element id
 * @param array $arSelect - properties to select
 * @return array - array of properties' values in the form of array("ELEMENT_ID" => array of props)
 */
function getProductProps($arElementId, $arSelect)
{
	if (!Loader::includeModule("iblock"))
		return array();

	if (empty($arElementId))
		return array();

	$arSelect = array_filter($arSelect, 'checkProductPropCode');
	foreach (array_keys($arSelect) as $index)
	{
		if (mb_substr($arSelect[$index], 0, 9) === 'PROPERTY_')
		{
			if (mb_substr($arSelect[$index], -6) === '_VALUE')
				$arSelect[$index] = mb_substr($arSelect[$index], 0, -6);
		}
	}
	unset($index);

	$arProductData = array();
	$arElementData = array();
	$res = CIBlockElement::GetList(
		array(),
		array("=ID" => array_unique($arElementId)),
		false,
		false,
		array("ID", "IBLOCK_ID")
	);
	while ($arElement = $res->Fetch())
		$arElementData[$arElement["IBLOCK_ID"]][] = $arElement["ID"]; // two getlists are used to support 1 and 2 type of iblock properties

	foreach ($arElementData as $iblockId => $arElemId) // todo: possible performance bottleneck
	{
		$res = CIBlockElement::GetList(
			array(),
			array("IBLOCK_ID" => $iblockId, "=ID" => $arElemId),
			false,
			false,
			$arSelect
		);
		while ($arElement = $res->GetNext())
		{
			$id = $arElement["ID"];
			foreach ($arElement as $key => $value)
			{
				if (!isset($arProductData[$id]))
					$arProductData[$id] = array();

				if (isset($arProductData[$id][$key])
					&& !is_array($arProductData[$id][$key])
					&& $value !== $arProductData[$id][$key]
					&& !in_array($value, explode(", ", $arProductData[$id][$key]))
				) // if we have multiple property value
				{
					$arProductData[$id][$key] .= ", ".$value;
				}
				elseif (empty($arProductData[$id][$key]))
				{
					$arProductData[$id][$key] = $value;
				}
			}
		}
	}

	return $arProductData;
}

function checkProductPropCode($selectItem)
{
	return ($selectItem !== null && $selectItem !== '' && $selectItem !== 'PROPERTY_');
}

function updateBasketOffersProps($oldProps, $newProps)
{
	if (!is_array($oldProps) || !is_array($newProps))
		return false;

	$result = array();
	if (empty($newProps))
		return $oldProps;
	if (empty($oldProps))
		return $newProps;
	foreach ($oldProps as &$oldValue)
	{
		$found = false;
		$key = false;
		$propId = (isset($oldValue['CODE']) ? (string)$oldValue['CODE'] : '').':'.$oldValue['NAME'];
		foreach ($newProps as $newKey => $newValue)
		{
			$newId = (isset($newValue['CODE']) ? (string)$newValue['CODE'] : '').':'.$newValue['NAME'];
			if ($newId == $propId)
			{
				$key = $newKey;
				$found = true;
				break;
			}
		}
		if ($found)
		{
			$oldValue['VALUE'] = $newProps[$key]['VALUE'];
			unset($newProps[$key]);
		}
		$result[] = $oldValue;
	}
	unset($oldValue);
	if (!empty($newProps))
	{
		foreach ($newProps as &$newValue)
		{
			$result[] = $newValue;
		}
		unset($newValue);
	}
	return $result;
}
