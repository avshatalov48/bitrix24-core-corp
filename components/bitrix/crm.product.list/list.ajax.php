<?
define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if(!function_exists('__CrmProductListEndResponse'))
{
	function __CrmProductListEndResponse($result)
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

if (!CModule::IncludeModule('crm'))
{
	__CrmProductListEndResponse(null);
}

if (!CCrmSecurityHelper::IsAuthorized() || $_REQUEST['MODE'] != 'SEARCH')
{
	__CrmProductListEndResponse(null);
}

$bResultWithValue = (isset($_REQUEST['RESULT_WITH_VALUE']) && $_REQUEST['RESULT_WITH_VALUE'] === 'Y');

$CrmPerms = CCrmPerms::GetCurrentUserPermissions();
if (!(CCrmPerms::IsAccessEnabled($CrmPerms) && $CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'READ')))
{
	__CrmProductListEndResponse(null);
}

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

CUtil::JSPostUnescape();
$GLOBALS['APPLICATION']->RestartBuffer();

$search = trim($_REQUEST['VALUE']);
$multi = isset($_REQUEST['MULTI']) && $_REQUEST['MULTI'] == 'Y'? true: false;
$arData = array();

$enableSearchByID = true;
if(isset($_REQUEST['ENABLE_SEARCH_BY_ID']))
{
	$enableSearchByID = mb_strtoupper($_REQUEST['ENABLE_SEARCH_BY_ID']) === 'Y';
}

if ($enableSearchByID && is_numeric($search))
{
	$arFilter['ID'] = (int)$search;
}
elseif (preg_match('/(.*)\[(\d+?)\]/i'.BX_UTF_PCRE_MODIFIER, $search, $arMatches))
{
	$arFilter['ID'] = intval($arMatches[2]);
	$arFilter['ACTIVE'] = 'Y';
}
else
{
	if (mb_strlen($search) < 3)
		__CrmProductListEndResponse(null);

	$arFilter['ACTIVE'] = 'Y';
	$arFilter['%NAME'] = $search;
}

$dstCurrencyID = isset($_REQUEST['CURRENCY_ID']) ? trim($_REQUEST['CURRENCY_ID']) : '';
$dstCurrency = $dstCurrencyID <> '' ? CCrmCurrency::GetByID($dstCurrencyID) : CCrmCurrency::GetBaseCurrency();

$enableRawPrices = (isset($_REQUEST['ENABLE_RAW_PRICES']) && mb_strtoupper($_REQUEST['ENABLE_RAW_PRICES']) === 'Y');
$limit = isset($_REQUEST['LIMIT']) ? intval($_REQUEST['LIMIT']) : 5;

$arNavStartParams = false;

if ($limit > 0)
	$arNavStartParams = array('nTopCount' => $limit);

$arSelect = array('ID', 'NAME', 'PRICE', 'CURRENCY_ID');
$arPricesSelect = $arVatsSelect = array();
$arSelect = CCrmProduct::DistributeProductSelect($arSelect, $arPricesSelect, $arVatsSelect);
$obRes = CCrmProduct::GetList(
	array('NAME' => 'ASC', 'ID' => 'ASC'),
	$arFilter,
	$arSelect,
	$arNavStartParams
);
$arProducts = $arProductId = array();
$pos = 0;
$searchUpper = ToUpper($search);
$nameUpper = '';
$arSort = array('RANK1' => array(), 'NAME' => array(), 'ID' => array());
while ($arRes = $obRes->Fetch())
{
	foreach ($arPricesSelect as $fieldName)
		$arRes[$fieldName] = null;
	foreach ($arVatsSelect as $fieldName)
		$arRes[$fieldName] = null;
	$nameUpper = ToUpper($arRes['NAME']);
	$pos = mb_strpos($nameUpper, $searchUpper);
	$arRes['RANK1'] = ($pos === false) ? 0 : $pos + 1;
	$arProductId[] = $arRes['ID'];
	$arProducts[$arRes['ID']] = $arRes;
	$arSort['RANK1'][] = $arRes['RANK1'];
	$arSort['NAME'][] = $nameUpper;
	$arSort['ID'][] = $arRes['ID'];
}
array_multisort(
	$arSort['RANK1'], SORT_NUMERIC, SORT_ASC,
	$arSort['NAME'], SORT_STRING, SORT_ASC,
	$arSort['ID'], SORT_NUMERIC, SORT_ASC
);
unset($arSort['RANK1'], $arSort['NAME']);
CCrmProduct::ObtainPricesVats($arProducts, $arProductId, $arPricesSelect, $arVatsSelect, $enableRawPrices);
$measureInfos = \Bitrix\Crm\Measure::getProductMeasures($arProductId);
$productVatInfos = CCrmProduct::PrepareCatalogProductFields($arProductId);
unset($arProductId);
$defaultMeasureInfo = \Bitrix\Crm\Measure::getDefaultMeasure();

$i = 0;
foreach ($arSort['ID'] as $id)
{
	$arRes = $arProducts[$id];
	$srcCurrencyID = isset($arRes['CURRENCY_ID']) ? $arRes['CURRENCY_ID'] : 0;
	if($dstCurrencyID <> '' && $srcCurrencyID <> ''  && $dstCurrencyID != $srcCurrencyID)
	{
		$arRes['PRICE'] = CCrmCurrency::ConvertMoney($arRes['PRICE'], $srcCurrencyID, $dstCurrencyID);
		$arRes['CURRENCY_ID'] = $dstCurrencyID;
	}

	$productID = $arRes['ID'];
	$customData = array('price' => $arRes['PRICE']);
	if(isset($measureInfos[$productID]) && !empty($measureInfos[$productID]))
	{
		$measureIfo = $measureInfos[$productID][0];
		$customData['measure'] = array(
			'code' => $measureIfo['CODE'],
			'name' => $measureIfo['SYMBOL']
		);
	}
	elseif($defaultMeasureInfo !== null)
	{
		$customData['measure'] = array(
			'code' => $defaultMeasureInfo['CODE'],
			'name' => $defaultMeasureInfo['SYMBOL']
		);
	}

	if(isset($productVatInfos[$productID]))
	{
		$productVatInfo = $productVatInfos[$productID];
		$customData['tax'] = array(
			'id' => $productVatInfo['TAX_ID'],
			'included' => $enableRawPrices && $productVatInfo['TAX_INCLUDED']
		);
	}

	$arData[] = array(
		'id' => $multi? 'PROD_'.$arRes['ID']: $arRes['ID'],
		'url' => CComponentEngine::MakePathFromTemplate(
			COption::GetOptionString('crm', 'path_to_product_show'),
			array('product_id' => $arRes['ID'])
		),
		'title' => $arRes['NAME'],
		'desc_html' => CCrmProduct::FormatPrice($arRes),
		'type' => 'product',
		'customData' => &$customData
	);
	unset($customData);

	if ($limit > 0 && ++$i === $limit)
		break;
}
unset($arProducts);

if ($bResultWithValue)
{
	$arResponse = array(
		'searchValue' => $_REQUEST['VALUE'],
		'data' => $arData
	);
	__CrmProductListEndResponse($arResponse);
}
else
{
	__CrmProductListEndResponse($arData);
}
?>
