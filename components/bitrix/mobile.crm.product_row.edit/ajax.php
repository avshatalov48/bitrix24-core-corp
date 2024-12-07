<?php
define('NO_KEEP_STATISTIC', true);
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('NOT_CHECK_PERMISSIONS', true);
define('NO_LANG_FILES', true);
define('DisableEventsCheck', true);
define('BX_STATISTIC_BUFFER_USED', false);
define('BX_PUBLIC_TOOLS', true);
define('PUBLIC_AJAX_MODE', true);

if (isset($_REQUEST['site_id']) && is_string($_REQUEST['site_id']))
{
	$siteID = $_REQUEST['site_id'];
	//Prevent LFI in prolog_before.php
	if($siteID !== '' && preg_match('/^[a-z0-9_]{2}$/i', $siteID) === 1)
	{
		define('SITE_ID', $siteID);
	}
}

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/bx_root.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

if (!defined('LANGUAGE_ID') )
{
	$dbSite = CSite::GetByID(SITE_ID);
	$arSite = $dbSite ? $dbSite->Fetch() : null;
	define('LANGUAGE_ID', $arSite ? $arSite['LANGUAGE_ID'] : 'en');
}

//session_write_close();

if (!CModule::IncludeModule('crm'))
{
	die();
}

global $APPLICATION, $DB;
$curUser = CCrmSecurityHelper::GetCurrentUser();
if (!$curUser || !$curUser->IsAuthorized() || !check_bitrix_sessid() || !CCrmPerms::IsAccessEnabled() || $_SERVER['REQUEST_METHOD'] != 'POST')
{
	die();
}

//$langID = isset($_REQUEST['lang_id'])? $_REQUEST['lang_id']: LANGUAGE_ID;
//__IncludeLang(dirname(__FILE__).'/lang/'.$langID.'/'.basename(__FILE__));

if(!function_exists('__CrmMobileProductRowEditEndResponse'))
{
	function __CrmMobileProductRowEditEndResponse($result)
	{
		$GLOBALS['APPLICATION']->RestartBuffer();
		Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);

		if(!empty($result))
		{
			echo CUtil::PhpToJSObject($result);
		}
		require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php');
		die();
	}
}

$userPerms = CCrmPerms::GetCurrentUserPermissions();
$action = isset($_REQUEST['ACTION']) ? $_REQUEST['ACTION'] : '';
if($action === 'FORMAT_MONEY')
{
	$currencyID = isset($_REQUEST['CURRENCY_ID']) ? $_REQUEST['CURRENCY_ID'] : '';
	if($currencyID === '')
	{
		$currencyID = CCrmCurrency::GetBaseCurrencyID();
	}

	$sum = isset($_REQUEST['SUM']) ? doubleval($_REQUEST['SUM']) : 0.0;
	if($sum > 0)
	{
		__CrmMobileProductRowEditEndResponse(
			array(
				'CURRENCY_ID' => $currencyID,
				'FORMATTED_SUM' => CCrmCurrency::MoneyToString($sum, $currencyID)
			)
		);
	}
	else
	{
		$quantity = isset($_REQUEST['QUANTITY']) ? intval($_REQUEST['QUANTITY']) : 0;
		$price = isset($_REQUEST['PRICE']) ? doubleval($_REQUEST['PRICE']) : 0.0;

		__CrmMobileProductRowEditEndResponse(
			array(
				'QUANTITY' => $quantity,
				'PRICE' => $price,
				'CURRENCY_ID' => $currencyID,
				'FORMATTED_PRICE' => CCrmCurrency::MoneyToString($price, $currencyID),
				'FORMATTED_SUM' => CCrmCurrency::MoneyToString($price * $quantity, $currencyID)
			)
		);
	}
}
elseif($action === 'CONVERT')
{
	$ownerType = isset($_REQUEST['OWNER_TYPE']) ? $_REQUEST['OWNER_TYPE'] : '';
	$ownerTypeName = CCrmOwnerTypeAbbr::ResolveName($ownerType);

	if(!CCrmAuthorizationHelper::CheckReadPermission($ownerTypeName, 0, $userPerms))
	{
		die();
	}

	$srcCurrencyID = isset($_REQUEST['SRC_CURRENCY_ID']) ? $_REQUEST['SRC_CURRENCY_ID'] : CCrmCurrency::GetBaseCurrencyID();
	$dstCurrencyID = isset($_REQUEST['DST_CURRENCY_ID']) ? $_REQUEST['DST_CURRENCY_ID'] : CCrmCurrency::GetBaseCurrencyID();
	$items = isset($_REQUEST['ITEMS']) && is_array($_REQUEST['ITEMS']) ? $_REQUEST['ITEMS'] : array();
	$result = array();
	$sumTotal = 0.0;

	if(!empty($items))
	{
		foreach($items as &$item)
		{
			$quantity = isset($item['QUANTITY']) ? intval($item['QUANTITY']) : 1;
			$price = isset($item['PRICE']) ? doubleval($item['PRICE']) : 0.0;
			$price = $price > 0.0 ? CCrmCurrency::ConvertMoney($price, $srcCurrencyID, $dstCurrencyID) : 0.0;
			$sum = $price * $quantity;
			$sumTotal += $sum;

			$result[] = array(
				'QUANTITY' => $quantity,
				'PRICE' => $price,
				'CURRENCY_ID' => $dstCurrencyID,
				'FORMATTED_PRICE' => CCrmCurrency::MoneyToString($price, $dstCurrencyID),
				'FORMATTED_SUM' => CCrmCurrency::MoneyToString($sum, $dstCurrencyID)
			);
		}
		unset($item);
	}

	__CrmMobileProductRowEditEndResponse(
		array(
			'ITEMS'=> $result,
			'SUM_TOTAL' => $sumTotal,
			'FORMATTED_SUM_TOTAL' => CCrmCurrency::MoneyToString($sumTotal, $dstCurrencyID)

		)
	);
}
else
{
	__CrmMobileProductRowEditEndResponse(array('ERROR' => 'Action is not supported in current context.'));
}




