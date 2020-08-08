<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;

if ($arParams['SHOW_ORDER_PAGE'] !== 'Y')
{
	LocalRedirect($arParams['SEF_FOLDER']);
}

global $USER;
if ($arParams['USE_PRIVATE_PAGE_TO_AUTH'] === 'Y' && !$USER->IsAuthorized())
{
	LocalRedirect($arResult['PATH_TO_AUTH_PAGE']);
}

if ($arParams["MAIN_CHAIN_NAME"] <> '')
{
	$APPLICATION->AddChainItem(htmlspecialcharsbx($arParams["MAIN_CHAIN_NAME"]), $arResult['SEF_FOLDER']);
}
$APPLICATION->AddChainItem(Loc::getMessage("SPS_CHAIN_ORDERS"), $arResult['PATH_TO_ORDERS']);
$APPLICATION->AddChainItem(Loc::getMessage("SPS_CHAIN_ORDER_DETAIL", array("#ID#" => urldecode($arResult["VARIABLES"]["ID"]))));
$arDetParams = array(
		"PATH_TO_LIST" => $arResult["PATH_TO_ORDERS"],
		"PATH_TO_CANCEL" => $arResult["PATH_TO_ORDER_CANCEL"],
		"PATH_TO_COPY" => $arResult["PATH_TO_ORDER_COPY"],
		"PATH_TO_PAYMENT" => $arParams["PATH_TO_PAYMENT"],
		"SET_TITLE" =>$arParams["SET_TITLE"],
		"ID" => $arResult["VARIABLES"]["ID"],
		"ACTIVE_DATE_FORMAT" => $arParams["ACTIVE_DATE_FORMAT"],
		"ALLOW_INNER" => $arParams["ALLOW_INNER"],
		"ONLY_INNER_FULL" => $arParams["ONLY_INNER_FULL"],
		"CACHE_TYPE" => $arParams["CACHE_TYPE"],
		"CACHE_TIME" => $arParams["CACHE_TIME"],
		"CACHE_GROUPS" => $arParams["CACHE_GROUPS"],
		"RESTRICT_CHANGE_PAYSYSTEM" => $arParams["ORDER_RESTRICT_CHANGE_PAYSYSTEM"],
		"REFRESH_PRICES" => $arParams["ORDER_REFRESH_PRICES"],
		"DISALLOW_CANCEL" => $arParams["ORDER_DISALLOW_CANCEL"],
		"HIDE_USER_INFO" => $arParams["ORDER_HIDE_USER_INFO"],
		"AUTH_FORM_IN_TEMPLATE" => 'Y',
		"CONTEXT_SITE_ID" => $arParams["CONTEXT_SITE_ID"],
		"CUSTOM_SELECT_PROPS" => $arParams["CUSTOM_SELECT_PROPS"]
	);
foreach($arParams as $key => $val)
{
	if(mb_strpos($key, "PROP_") !== false)
		$arDetParams[$key] = $val;
}

$APPLICATION->IncludeComponent(
	"bitrix:sale.personal.order.detail",
	"bootstrap_v4",
	$arDetParams,
	$component
);
?>
