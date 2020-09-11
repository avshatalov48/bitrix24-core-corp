<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

/** @global CUser $USER */

use Bitrix\Main\Loader;
use Bitrix\Disk\Driver;

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