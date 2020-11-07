<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

/** @global CUser $USER */

$arResult['TOP_RATING_DATA'] = (
	\Bitrix\Main\ModuleManager::isModuleInstalled('intranet')
	&& !empty($arResult["arLogTmpID"])
		? \Bitrix\Socialnetwork\ComponentHelper::getLivefeedRatingData([
			'topCount' => 10,
			'logId' => array_unique($arResult["arLogTmpID"]),
		])
		: []
);

$arResult['TARGET'] = (isset($arParams['TARGET']) ? $arParams['TARGET'] : '');
/*
AddMessage2Log('reload: '.$arResult["RELOAD"]);
AddMessage2Log('ajax_call: '.$arResult["AJAX_CALL"]);
*/
$arResult['PAGE_MODE'] = 'first';
if ($arResult["RELOAD"])
{
	$arResult['PAGE_MODE'] = 'refresh';
}
elseif ($arResult["AJAX_CALL"])
{
	$arResult['PAGE_MODE'] = 'next';
}

//AddMessage2Log($arResult["PAGE_MODE"]);
