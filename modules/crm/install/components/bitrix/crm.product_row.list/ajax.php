<?
define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);
if (!CModule::IncludeModule('crm'))
{
	return;
}

global $USER, $APPLICATION;

if(!function_exists('__CrmPropductRowListEndResponse'))
{
	function __CrmPropductRowListEndResponse($result)
	{
		$GLOBALS['APPLICATION']->RestartBuffer();
		header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
		if(!empty($result))
		{
			echo CUtil::PhpToJSObject($result);
		}
		require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
		die();
	}
}

/*
 * ONLY 'POST' SUPPORTED
 * SUPPORTED MODES:
 * 'SET_OPTION' - save state of checkboxes
 * 'CALC_PRODUCT_PRICES' - product prices calculations
 * 'CONVERT_MONEY' - convert sum to destination currency
 * 'ADD_PRODUCT' - add product (with immediate saving of changes)
 * 'UPDATE_PRODUCT' - update product (with immediate saving of changes)
 * 'SAVE_PRODUCTS' - save all product rows
 * 'REMOVE_PRODUCT' - remove product (with immediate saving of changes)
 * 'CALCULATE_TOTALS' - calculate totals values
 */

if (!$USER->IsAuthorized() || !check_bitrix_sessid() || $_SERVER['REQUEST_METHOD'] != 'POST')
{
	return;
}

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

CUtil::JSPostUnescape();
$APPLICATION->RestartBuffer();
header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);

// Recalcutation of product prices after currency change

$mode = isset($_POST['MODE']) ? $_POST['MODE'] : '';
if(!isset($mode[0]))
{
	__CrmPropductRowListEndResponse(array());
}

$ownerType = isset($_POST['OWNER_TYPE']) ? $_POST['OWNER_TYPE'] : '';
if(!isset($ownerType[0]))
{
	__CrmPropductRowListEndResponse(array('ERROR'=>'OWNER_TYPE_NOT_FOUND'));
}

$ownerName = CCrmOwnerTypeAbbr::ResolveName($ownerType);
$ownerTypeID = CCrmOwnerType::ResolveID($ownerName);
$ownerID = isset($_POST['OWNER_ID']) ? intval($_POST['OWNER_ID']) : 0;

$siteID = isset($_POST['SITE_ID']) && $_POST['SITE_ID'] !== '' ? $_POST['SITE_ID'] : SITE_ID;
$perms = new CCrmPerms($USER->GetID());

if($mode === 'SET_OPTION')
{
	$arResponse = array('MODE' => 'SET_OPTION');
	
	$data = isset($_POST['DATA']) && is_array($_POST['DATA']) ? $_POST['DATA'] : array();
	if(count($data) == 0)
	{
		$arResponse['ERROR'] = 'SOURCE_DATA_NOT_FOUND';
	}
	else
	{
		$bSuccess = true;
		if (empty($ownerType) || $ownerID <= 0)
			$bSuccess = false;

		if ($bSuccess)
		{
			$settings = array();

			if (isset($data['SHOW_DISCOUNT']))
			{
				$settings['ENABLE_DISCOUNT'] = ($data['SHOW_DISCOUNT'] === 'Y');
				if (!is_array($arResponse['DATA']))
					$arResponse['DATA'] = array();
				$arResponse['DATA']['SHOW_DISCOUNT'] = ($settings['ENABLE_DISCOUNT'] ? 'Y' : 'N');
			}

			if (isset($data['SHOW_TAX']))
			{
				$settings['ENABLE_TAX'] = ($data['SHOW_TAX'] === 'Y');
				if (!is_array($arResponse['DATA']))
					$arResponse['DATA'] = array();
				$arResponse['DATA']['SHOW_TAX'] = ($settings['ENABLE_TAX'] ? 'Y' : 'N');
			}

			if (count($settings) > 0)
			{
				$arSettings = CCrmProductRow::LoadSettings($ownerType, $ownerID);
				foreach ($settings as $k => $v)
					$arSettings[$k] = $v;
				CCrmProductRow::SaveSettings($ownerType, $ownerID, $arSettings);
				unset($arSettings);
			}
		}
		else
		{
			$arResponse['ERROR'] = 'OWNER_NOT_FOUND';
		}
	}

	__CrmPropductRowListEndResponse($arResponse);
}
elseif($mode === 'CALC_PRODUCT_PRICES')
{
	if(!\Bitrix\Crm\Security\EntityAuthorization::checkReadPermission($ownerTypeID, $ownerID, $perms))
	{
		__CrmPropductRowListEndResponse(array('ERROR'=>'PERMISSION_DENIED'));
	}

	$data = isset($_POST['DATA']) && is_array($_POST['DATA']) ? $_POST['DATA'] : array();
	if(count($data) == 0)
	{
		__CrmPropductRowListEndResponse(array('ERROR'=>'SOURCE_DATA_NOT_FOUND'));
	}

//{ SRC_CURRENCY_ID:'RUB', SRC_EXCH_RATE:1, DST_CURRENCY_ID:'USD', PRODUCTS:[ { ID:1, PRICE:1.0 }...] }

// National currency is default currency
	$srcCurrencyID = isset($data['SRC_CURRENCY_ID']) && strlen(strval($data['SRC_CURRENCY_ID'])) > 0 ? strval($data['SRC_CURRENCY_ID']) : CCrmCurrency::GetBaseCurrencyID();
//	$srcExchRate = 0.0;
//	if(isset($data['SRC_EXCH_RATE']))
//	{
//		$srcExchRate = doubleval($data['SRC_EXCH_RATE']);
//	}
//
//	if($srcExchRate <= 0.0)
//	{
//		$srcExchRate = ($srcCurrency = CCrmCurrency::GetByID($srcCurrencyID)) ? $srcCurrency['EXCH_RATE'] : 1.0;
//	}

	$dstCurrencyID = isset($data['DST_CURRENCY_ID']) && strlen(strval($data['DST_CURRENCY_ID'])) > 0 ? strval($data['DST_CURRENCY_ID']) : CCrmCurrency::GetBaseCurrencyID();
//	$dstExchRate = ($dstCurrency = CCrmCurrency::GetByID($dstCurrencyID)) ? $dstCurrency['EXCH_RATE'] : 1.0;

	$arProducts = isset($data['PRODUCTS']) && is_array($data['PRODUCTS']) ? $data['PRODUCTS'] : array();
	if(count($arProducts) > 0)
	{
		foreach($arProducts as &$arProduct)
		{
			$arProduct['PRICE'] =
				CCrmCurrency::ConvertMoney(
					isset($arProduct['PRICE']) ? $arProduct['PRICE'] : 1.0,
					$srcCurrencyID,
					$dstCurrencyID
				);
			if (isset($arProduct['DISCOUNT_TYPE_ID']) && isset($arProduct['DISCOUNT_VALUE'])
				&& intval($arProduct['DISCOUNT_TYPE_ID']) === \Bitrix\Crm\Discount::MONETARY
				&& abs(doubleval($arProduct['DISCOUNT_VALUE'])) > 0)
			{
				$arProduct['DISCOUNT_VALUE'] =
					CCrmCurrency::ConvertMoney(
						isset($arProduct['DISCOUNT_VALUE']) ? $arProduct['DISCOUNT_VALUE'] : 0.0,
						$srcCurrencyID,
						$dstCurrencyID
					);
			}
		}
	}

	__CrmPropductRowListEndResponse(
		array(
			'CURRENCY_ID'=> $dstCurrencyID,
			'CURRENCY_FORMAT' => CCrmCurrency::GetCurrencyFormatString($dstCurrencyID),
			//'EXCH_RATE' => $dstExchRate,
			'EXCH_RATE' => CCrmCurrency::GetExchangeRate($dstCurrencyID),
			'PRODUCTS'=> $arProducts,
			'PRODUCT_POPUP_ITEMS' => CCrmProductHelper::PreparePopupItems($dstCurrencyID)
		)
	);
}
elseif($mode === 'CONVERT_MONEY')
{
	if(!\Bitrix\Crm\Security\EntityAuthorization::checkReadPermission($ownerTypeID, $ownerID, $perms))
	{
		__CrmPropductRowListEndResponse(array('ERROR'=>'PERMISSION_DENIED'));
	}

	$data = isset($_POST['DATA']) && is_array($_POST['DATA']) ? $_POST['DATA'] : array();
	if(count($data) == 0)
	{
		__CrmPropductRowListEndResponse(array('ERROR'=>'SOURCE_DATA_NOT_FOUND'));
	}

	$srcSum = isset($data['SRC_SUM']) ? doubleval($data['SRC_SUM']) : 0.0;
	$srcCurrencyID = isset($data['SRC_CURRENCY_ID']) && strlen(strval($data['SRC_CURRENCY_ID'])) > 0 ? strval($data['SRC_CURRENCY_ID']) : CCrmCurrency::GetBaseCurrencyID();
	$dstCurrencyID = isset($data['DST_CURRENCY_ID']) && strlen(strval($data['DST_CURRENCY_ID'])) > 0 ? strval($data['DST_CURRENCY_ID']) : CCrmCurrency::GetBaseCurrencyID();

	__CrmPropductRowListEndResponse(array('SUM' => CCrmCurrency::ConvertMoney($srcSum, $srcCurrencyID, $dstCurrencyID)));
}
elseif($mode === 'ADD_PRODUCT')
{
	// 'OWNER_TYPE':'D', 'OWNER_ID':7 'PRODUCT_ID':100, 'QTY':1, 'CURRENCY_ID':1 'PRICE':100.50
	if($ownerID <= 0)
	{
		__CrmPropductRowListEndResponse(array('ERROR'=>'OWNER_ID_NOT_FOUND'));
	}

	if(!\Bitrix\Crm\Security\EntityAuthorization::checkUpdatePermission($ownerTypeID, $ownerID, $perms))
	{
		__CrmPropductRowListEndResponse(array('ERROR'=>'PERMISSION_DENIED'));
	}

	// 'OWNER_TYPE':'D', 'OWNER_ID':7 'PRODUCT_ID':100, 'QTY':1, 'CURRENCY_ID':1 'PRICE':100.50
	$fields = array(
		'OWNER_TYPE' => $ownerType,
		'OWNER_ID' => $ownerID
	);

	// Custom products are allowed (PRODUCT_ID === 0)
	$fields['PRODUCT_ID'] = isset($_POST['PRODUCT_ID']) ? intval($_POST['PRODUCT_ID']) : 0;
	if($fields['PRODUCT_ID'] < 0)
	{
		$fields['PRODUCT_ID'] = 0;
	}

	// Custom product must have name
	if($fields['PRODUCT_ID'] === 0)
	{
		$fields['PRODUCT_NAME'] = isset($_POST['PRODUCT_NAME']) ? trim($_POST['PRODUCT_NAME']) : '';
		if($fields['PRODUCT_NAME'] === '')
		{
			__CrmPropductRowListEndResponse(array('ERROR' => 'CUSTOM_PRODUCT_NAME_NOT_ASSIGNED'));
		}
	}

	// Zero and negative quantity are not allowed
	$fields['QUANTITY'] = isset($_POST['QUANTITY']) ? intval($_POST['QUANTITY']) : 1;
	if($fields['QUANTITY'] <= 0)
	{
		$fields['QUANTITY'] = 1;
	}

	//$fields['CURRENCY_ID'] = isset($_POST['CURRENCY_ID']) ? intval($_POST['CURRENCY_ID']) : 0;
	//if($fields['CURRENCY_ID'] <= 0)
	//{
	//	$fields['CURRENCY_ID'] = CCrmCurrency::GetNationalCurrencyID();
	//}

	// Zero and negative prices are allowed
	$fields['PRICE'] = isset($_POST['PRICE']) ? doubleval($_POST['PRICE']) : 1.0;

	if(isset($_POST['DISCOUNT_TYPE_ID']))
	{
		$fields['DISCOUNT_TYPE_ID'] = isset($_POST['DISCOUNT_TYPE_ID'])
			? intval($_POST['DISCOUNT_TYPE_ID']) : \Bitrix\Crm\Discount::UNDEFINED;

		if(!\Bitrix\Crm\Discount::isDefined($fields['DISCOUNT_TYPE_ID']))
		{
			$fields['DISCOUNT_TYPE_ID'] = \Bitrix\Crm\Discount::PERCENTAGE;
		}
	}

	if(isset($_POST['DISCOUNT_RATE']))
	{
		$fields['DISCOUNT_RATE'] = round(doubleval($_POST['DISCOUNT_RATE']), 2);
	}

	if(isset($_POST['DISCOUNT_SUM']))
	{
		$fields['DISCOUNT_SUM'] = round(doubleval($_POST['DISCOUNT_SUM']), 2);
	}

	if(isset($_POST['MEASURE_CODE']))
	{
		$fields['MEASURE_CODE'] = intval($_POST['MEASURE_CODE']);
	}

	if(isset($_POST['MEASURE_NAME']))
	{
		$fields['MEASURE_NAME'] = $_POST['MEASURE_NAME'];
	}

	if(isset($_POST['TAX_RATE']))
	{
		$fields['TAX_RATE'] = round(doubleval($_POST['TAX_RATE']), 2);;
	}

	if(isset($_POST['TAX_INCLUDED']))
	{
		$fields['TAX_INCLUDED'] = strtoupper($_POST['TAX_INCLUDED']) === 'Y' ? 'Y' : 'N';
	}

	//Is always enabled for disable requests to product catalog
	$fields['CUSTOMIZED'] = 'Y';
	$fields['SORT'] = isset($_POST['SORT']) ? intval($_POST['SORT']) : 0;

	$ID = CCrmProductRow::Add($fields);
	if(!$ID)
	{
		__CrmPropductRowListEndResponse(array('ERROR' => CCrmProductRow::GetLastError()));
	}
	else
	{
		__CrmPropductRowListEndResponse(array('PRODUCT_ROW' => array('ID' => $ID)));
	}
}
elseif($mode === 'UPDATE_PRODUCT')
{
	$hasPermission = $ownerID > 0
		? \Bitrix\Crm\Security\EntityAuthorization::checkUpdatePermission($ownerTypeID, $ownerID, $perms)
		: \Bitrix\Crm\Security\EntityAuthorization::checkCreatePermission($ownerTypeID, $perms);

	if(!$hasPermission)
	{
		__CrmPropductRowListEndResponse(array('ERROR'=>'PERMISSION_DENIED'));
	}

	//'ID':1 'PRODUCT_ID':100, 'QUANTITY':1, 'CURRENCY_ID':1 'PRICE':100.50
	$fields = array();

	$ID = isset($_POST['ID']) ? intval($_POST['ID']) : 0;
	if($ID <= 0)
	{
		__CrmPropductRowListEndResponse(array('ERROR'=>'ID_NOT_FOUND'));
	}

	// Custom products are allowed (PRODUCT_ID === 0)

	$productID = isset($_POST['PRODUCT_ID']) ? intval($_POST['PRODUCT_ID']) : 0;
	if($productID > 0)
	{
		$fields['PRODUCT_ID'] = $productID;
	}

	$productName = isset($_POST['PRODUCT_NAME']) ? trim($_POST['PRODUCT_NAME']) : '';
	if($productName !== '')
	{
		$fields['PRODUCT_NAME'] = $productName;
	}

	$fields['QUANTITY'] = isset($_POST['QUANTITY']) ? doubleval($_POST['QUANTITY']) : 1.0;
	if($fields['QUANTITY'] <= 0) // Zero and negative quantity are not allowed
	{
		$fields['QUANTITY'] = 1;
	}
	else
	{
		$fields['QUANTITY'] = round($fields['QUANTITY'], 4);
	}

	// Zero and negative prices are allowed
	$fields['PRICE'] = isset($_POST['PRICE']) ? round(doubleval($_POST['PRICE']), 2) : 1.0;

	if(isset($_POST['DISCOUNT_TYPE_ID']))
	{
		$fields['DISCOUNT_TYPE_ID'] = isset($_POST['DISCOUNT_TYPE_ID'])
			? intval($_POST['DISCOUNT_TYPE_ID']) : \Bitrix\Crm\Discount::UNDEFINED;

		if(!\Bitrix\Crm\Discount::isDefined($fields['DISCOUNT_TYPE_ID']))
		{
			$fields['DISCOUNT_TYPE_ID'] = \Bitrix\Crm\Discount::PERCENTAGE;
		}
	}

	if(isset($_POST['DISCOUNT_RATE']))
	{
		$fields['DISCOUNT_RATE'] = round(doubleval($_POST['DISCOUNT_RATE']), 2);
	}

	if(isset($_POST['DISCOUNT_SUM']))
	{
		$fields['DISCOUNT_SUM'] = round(doubleval($_POST['DISCOUNT_SUM']), 2);
	}

	if(isset($_POST['MEASURE_CODE']))
	{
		$fields['MEASURE_CODE'] = intval($_POST['MEASURE_CODE']);
	}

	if(isset($_POST['MEASURE_NAME']))
	{
		$fields['MEASURE_NAME'] = $_POST['MEASURE_NAME'];
	}

	if(isset($_POST['TAX_RATE']))
	{
		$fields['TAX_RATE'] = round(doubleval($_POST['TAX_RATE']), 2);;
	}

	if(isset($_POST['TAX_INCLUDED']))
	{
		$fields['TAX_INCLUDED'] = strtoupper($_POST['TAX_INCLUDED']) === 'Y' ? 'Y' : 'N';
	}

	if(isset($_POST['CUSTOMIZED']))
	{
		$fields['CUSTOMIZED'] = strtoupper($_POST['CUSTOMIZED']) === 'Y' ? 'Y' : 'N';
	}

	if(!CCrmProductRow::Update($ID, $fields))
	{
		__CrmPropductRowListEndResponse(array('ERROR' => CCrmProductRow::GetLastError()));
	}
	else
	{
		__CrmPropductRowListEndResponse(array('PRODUCT_ROW' => array('ID' => $ID)));
	}
}
elseif($mode === 'SAVE_PRODUCTS')
{
	if($ownerID <= 0)
	{
		__CrmPropductRowListEndResponse(array('ERROR'=>'OWNER_ID_NOT_FOUND'));
	}

	if (!empty($ownerType) && $ownerID > 0)
	{
		$enableDiscount = false;
		$enableTax = false;
		if (isset($_POST['PRODUCT_ROW_SETTINGS']) && is_array($_POST['PRODUCT_ROW_SETTINGS']))
		{
			$settings = $_POST['PRODUCT_ROW_SETTINGS'];
			$enableDiscount = isset($settings['ENABLE_DISCOUNT']) ? $settings['ENABLE_DISCOUNT'] === 'Y' : false;
			$enableTax = isset($settings['ENABLE_TAX']) ? $settings['ENABLE_TAX'] === 'Y' : false;
		}
		$settings = CCrmProductRow::LoadSettings($ownerType, $ownerID);
		$settings['ENABLE_DISCOUNT'] = $enableDiscount;
		$settings['ENABLE_TAX'] = $enableTax;
		CCrmProductRow::SaveSettings($ownerType, $ownerID, $settings);
		unset($settings);
	}

	$prodJson = isset($_POST['PRODUCT_ROW_DATA']) ? strval($_POST['PRODUCT_ROW_DATA']) : '';
	$arProducts = $arResult['PRODUCT_ROWS'] = strlen($prodJson) > 0 ? CUtil::JsObjectToPhp($prodJson) : array();

	if (!\Bitrix\Crm\Security\EntityAuthorization::checkUpdatePermission($ownerTypeID, $ownerID, $perms))
	{
		__CrmPropductRowListEndResponse(array('ERROR'=>'PERMISSION_DENIED'));
	}

	$arProductRows = array();
	foreach ($arProducts as $arProduct)
	{
		$fields = array();

		$ID = isset($arProduct['ID']) ? intval($arProduct['ID']) : 0;
		$fields['ID'] = $ID;

		// Custom products are allowed (PRODUCT_ID === 0)

		$productID = isset($arProduct['PRODUCT_ID']) ? intval($arProduct['PRODUCT_ID']) : 0;
		if($productID > 0)
		{
			$fields['PRODUCT_ID'] = $productID;
		}

		$productName = isset($arProduct['PRODUCT_NAME']) ? trim($arProduct['PRODUCT_NAME']) : '';
		if($productName !== '')
		{
			$fields['PRODUCT_NAME'] = $productName;
		}

		$fields['QUANTITY'] = isset($arProduct['QUANTITY']) ? round(doubleval($arProduct['QUANTITY']), 4) : 0.0;

		// Zero and negative prices are allowed
		$fields['PRICE'] = isset($arProduct['PRICE']) ? round(doubleval($arProduct['PRICE']), 2) : 0.0;
		$fields['PRICE_EXCLUSIVE'] = isset($arProduct['PRICE_EXCLUSIVE']) ? round(doubleval($arProduct['PRICE_EXCLUSIVE']), 2) : 0.0;
		$fields['PRICE_NETTO'] = isset($arProduct['PRICE_NETTO']) ? round(doubleval($arProduct['PRICE_NETTO']), 2) : 0.0;
		$fields['PRICE_BRUTTO'] = isset($arProduct['PRICE_BRUTTO']) ? round(doubleval($arProduct['PRICE_BRUTTO']), 2) : 0.0;

		if(isset($arProduct['DISCOUNT_TYPE_ID']))
		{
			$fields['DISCOUNT_TYPE_ID'] = isset($arProduct['DISCOUNT_TYPE_ID'])
				? intval($arProduct['DISCOUNT_TYPE_ID']) : \Bitrix\Crm\Discount::UNDEFINED;

			if(!\Bitrix\Crm\Discount::isDefined($fields['DISCOUNT_TYPE_ID']))
			{
				$fields['DISCOUNT_TYPE_ID'] = \Bitrix\Crm\Discount::PERCENTAGE;
			}
		}

		if(isset($arProduct['DISCOUNT_RATE']))
		{
			$fields['DISCOUNT_RATE'] = round(doubleval($arProduct['DISCOUNT_RATE']), 2);
		}

		if(isset($arProduct['DISCOUNT_SUM']))
		{
			$fields['DISCOUNT_SUM'] = round(doubleval($arProduct['DISCOUNT_SUM']), 2);
		}

		if(isset($arProduct['MEASURE_CODE']))
		{
			$fields['MEASURE_CODE'] = intval($arProduct['MEASURE_CODE']);
		}

		if(isset($arProduct['MEASURE_NAME']))
		{
			$fields['MEASURE_NAME'] = $arProduct['MEASURE_NAME'];
		}

		if(isset($arProduct['TAX_RATE']))
		{
			$fields['TAX_RATE'] = round(doubleval($arProduct['TAX_RATE']), 2);;
		}

		if(isset($arProduct['TAX_INCLUDED']))
		{
			$fields['TAX_INCLUDED'] = strtoupper($arProduct['TAX_INCLUDED']) === 'Y' ? 'Y' : 'N';
		}

		if(isset($arProduct['CUSTOMIZED']))
		{
			$fields['CUSTOMIZED'] = strtoupper($arProduct['CUSTOMIZED']) === 'Y' ? 'Y' : 'N';
		}

		if(isset($arProduct['SORT']))
		{
			$fields['SORT'] = intval($arProduct['SORT']);
		}

		$arProductRows[] = $fields;
	}

	$bSuccess = CCrmProductRow::SaveRows($ownerType, $ownerID, $arProductRows);
	if(!$bSuccess)
	{
		__CrmPropductRowListEndResponse(array('ERROR'=>'PRODUCT_ROWS_SAVING_ERROR'));
	}
	else
	{
		$arProductRowIDs = array();
		foreach(CCrmProductRow::LoadRows($ownerType, $ownerID) as $arProductRow)
		{
			$arProductRowIDs[] = $arProductRow['ID'];
		}

		__CrmPropductRowListEndResponse(array('PRODUCT_ROW_IDS' => $arProductRowIDs));
	}
}
elseif($mode === 'REMOVE_PRODUCT')
{
	if($ownerID <= 0)
	{
		__CrmPropductRowListEndResponse(array('ERROR'=>'OWNER_ID_NOT_FOUND'));
	}

	if (!\Bitrix\Crm\Security\EntityAuthorization::checkUpdatePermission($ownerTypeID, $ownerID, $perms))
	{
		__CrmPropductRowListEndResponse(array('ERROR'=>'PERMISSION_DENIED'));
	}

	$ID = isset($_POST['ID']) ? intval($_POST['ID']) : 0;
	if($ID <= 0)
	{
		__CrmPropductRowListEndResponse(array('ERROR'=>'ID_NOT_FOUND'));
	}

	if(!CCrmProductRow::Delete($ID))
	{
		__CrmPropductRowListEndResponse(array('ERROR' => CCrmProductRow::GetLastError()));
	}
	else
	{
		__CrmPropductRowListEndResponse(array('DELETED_PRODUCT_ID' => $ID));
	}
}
elseif($mode === 'CALCULATE_TOTALS')
{
	$hasPermission = $ownerID > 0
		? \Bitrix\Crm\Security\EntityAuthorization::checkUpdatePermission($ownerTypeID, $ownerID, $perms)
		: \Bitrix\Crm\Security\EntityAuthorization::checkCreatePermission($ownerTypeID, $perms);

	if(!$hasPermission)
	{
		__CrmPropductRowListEndResponse(array('ERROR'=>'PERMISSION_DENIED'));
	}

	$productRows = isset($_POST['PRODUCTS']) && is_array($_POST['PRODUCTS']) ? $_POST['PRODUCTS'] : array();
	$totalDiscount = 0.0;
	foreach($productRows as &$productRow)
	{
		$productRow['ID'] = isset($productRow['ID']) ? intval($productRow['ID']) : 0;
		$productRow['PRODUCT_ID'] = isset($productRow['PRODUCT_ID']) ? intval($productRow['PRODUCT_ID']) : 0;
		$productRow['PRODUCT_NAME'] = isset($productRow['PRODUCT_NAME']) ? $productRow['PRODUCT_NAME'] : '';
		$productRow['QUANTITY'] = isset($productRow['QUANTITY']) ? doubleval($productRow['QUANTITY']) : 1.0;
		$productRow['DISCOUNT_TYPE_ID'] =
			(isset($productRow['DISCOUNT_TYPE_ID'])
				&& \Bitrix\Crm\Discount::isDefined(intval($productRow['DISCOUNT_TYPE_ID']))) ?
				intval($productRow['DISCOUNT_TYPE_ID']) : \Bitrix\Crm\Discount::PERCENTAGE;
		$productRow['DISCOUNT_RATE'] = isset($productRow['DISCOUNT_RATE']) ? doubleval($productRow['DISCOUNT_RATE']) : 0.0;
		$productRow['DISCOUNT_SUM'] = isset($productRow['DISCOUNT_SUM']) ? doubleval($productRow['DISCOUNT_SUM']) : 0.0;
		$productRow['PRICE'] = isset($productRow['PRICE']) ? doubleval($productRow['PRICE']) : 1.0;
		$productRow['PRICE_EXCLUSIVE'] = isset($productRow['PRICE_EXCLUSIVE']) ? doubleval($productRow['PRICE_EXCLUSIVE']) : 1.0;
		$productRow['CUSTOMIZED'] = isset($productRow['CUSTOMIZED'])
			&& strtoupper($productRow['CUSTOMIZED']) === 'Y' ? 'Y' : 'N';
		if($productRow['CUSTOMIZED'] === 'Y')
		{
			$productRow['TAX_RATE'] = isset($productRow['TAX_RATE']) ? doubleval($productRow['TAX_RATE']) : 0.0;
		}
		$productRow['TAX_INCLUDED'] = isset($productRow['TAX_INCLUDED'])
			&& strtoupper($productRow['TAX_INCLUDED']) === 'Y' ? 'Y' : 'N';
		$totalDiscount += round($productRow['DISCOUNT_SUM'] * $productRow['QUANTITY'], 2);
	}
	unset($productRow);

	$currencyID = isset($_POST['CURRENCY_ID']) && $_POST['CURRENCY_ID'] !== ''
		? $_POST['CURRENCY_ID'] : CCrmCurrency::GetBaseCurrencyID();

	$clientTypeName = isset($_POST['CLIENT_TYPE_NAME']) && $_POST['CLIENT_TYPE_NAME'] !== ''
		? strtoupper($_POST['CLIENT_TYPE_NAME']) : 'CONTACT';

	$personTypeIDs = CCrmPaySystem::getPersonTypeIDs();
	if(empty($personTypeIDs))
	{
		__CrmPropductRowListEndResponse(array('ERROR' => 'PERSON_TYPES_NOT_DEFINED'));
	}

	$personTypeID = isset($personTypeIDs[$clientTypeName]) ? intval($personTypeIDs[$clientTypeName]) : 0;
	if($personTypeID <= 0)
	{
		__CrmPropductRowListEndResponse(array('ERROR' => 'COULD_NOT_FIND_PERSON_TYPE'));
	}

	$enableSaleDiscount = isset($_POST['ENABLE_ADDITIONAL_DISCOUNT']) && strtoupper($_POST['ENABLE_ADDITIONAL_DISCOUNT']) === 'Y';

	$calculateOptions = array();
	$isLDTaxAllowed = isset($_POST['ALLOW_LD_TAX']) ? $_POST['ALLOW_LD_TAX'] === 'Y' : CCrmTax::isTaxMode();
	if ($isLDTaxAllowed)
	{
		$calculateOptions['ALLOW_LD_TAX'] = 'Y';
	}
	if ($isLDTaxAllowed && isset($_POST['LOCATION_ID']))
		$calculateOptions['LOCATION_ID'] = $_POST['LOCATION_ID'];
	$result = CCrmSaleHelper::Calculate($productRows, $currencyID, $personTypeID, $enableSaleDiscount, $siteID, $calculateOptions);

	if (!is_array($result))
		$result = array();
	$totalSum = isset($result['PRICE']) ? round(doubleval($result['PRICE']), 2) : 0.0;
	$totalTax = isset($result['TAX_VALUE']) ? round(doubleval($result['TAX_VALUE']), 2) : 0.0;
	$totalBeforeTax = round($totalSum - $totalTax, 2);
	$totalBeforeDiscount = round($totalBeforeTax + $totalDiscount, 2);


	$arResponse = array(
		'TOTALS' => array(
			'TOTAL_SUM' => $totalSum,
			'TOTAL_TAX' => $totalTax,
			'TOTAL_BEFORE_TAX' => $totalBeforeTax,
			'TOTAL_DISCOUNT' => $totalDiscount,
			'TOTAL_BEFORE_DISCOUNT' => $totalBeforeDiscount,
			'TOTAL_SUM_FORMATTED' => CCrmCurrency::MoneyToString($totalSum, $currencyID),
			'TOTAL_SUM_FORMATTED_SHORT' => CCrmCurrency::MoneyToString($totalSum, $currencyID, '#'),
			'TOTAL_TAX_FORMATTED' => CCrmCurrency::MoneyToString($totalTax, $currencyID),
			'TOTAL_BEFORE_TAX_FORMATTED' => CCrmCurrency::MoneyToString($totalBeforeTax, $currencyID),
			'TOTAL_DISCOUNT_FORMATTED' => CCrmCurrency::MoneyToString($totalDiscount, $currencyID),
			'TOTAL_BEFORE_DISCOUNT_FORMATTED' => CCrmCurrency::MoneyToString($totalBeforeDiscount, $currencyID)
		)
	);

	if ($isLDTaxAllowed)
	{
		$taxes = (is_array($result['TAX_LIST'])) ? $result['TAX_LIST'] : null;
		$LDTaxes = array();
		if (!is_array($taxes) || count($taxes) === 0)
		{
			$LDTaxes = array(
				array(
					'TAX_NAME' => GetMessage('CRM_PRODUCT_TOTAL_TAX'),
					'TAX_VALUE' => CCrmCurrency::MoneyToString($totalTax, $currencyID)
				)
			);
		}
		$LDTaxPrecision = isset($_POST['LD_TAX_PRECISION']) ? intval($_POST['LD_TAX_PRECISION']) : 2;
		if (is_array($taxes))
		{
			foreach ($taxes as $taxInfo)
			{
				$LDTaxes[] = array(
					'TAX_NAME' => sprintf(
						"%s%s%s",
						($taxInfo["IS_IN_PRICE"] == "Y") ? GetMessage('CRM_PRODUCT_TAX_INCLUDING')." " : "",
						$taxInfo["NAME"],
						(/*$vat <= 0 &&*/ $taxInfo["IS_PERCENT"] == "Y")
							? sprintf(' (%s%%)', roundEx($taxInfo["VALUE"], $LDTaxPrecision))
							: ""
					),
					'TAX_VALUE' => CCrmCurrency::MoneyToString(
							$taxInfo['VALUE_MONEY'], $currencyID
						)
				);
			}
		}
		$arResponse['LD_TAXES'] = $LDTaxes;
	}

	__CrmPropductRowListEndResponse($arResponse);
}
__CrmPropductRowListEndResponse(array());
