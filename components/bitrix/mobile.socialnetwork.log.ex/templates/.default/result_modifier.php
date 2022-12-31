<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @global CUser $USER */
/** @var array $arParams */
/** @var array $arResult */

$arResult['TOP_RATING_DATA'] = (
	\Bitrix\Main\ModuleManager::isModuleInstalled('intranet')
	&& !empty($arResult["arLogTmpID"])
		? \Bitrix\Socialnetwork\ComponentHelper::getLivefeedRatingData([
			'topCount' => 10,
			'logId' => array_unique(array_merge($arResult["arLogTmpID"], $arResult["pinnedIdList"])),
		])
		: []
);

$arResult['TARGET'] = ($arParams['TARGET'] ?? '');

$arResult['PAGE_MODE'] = 'first';
if ($arResult['RELOAD'])
{
	$arResult['PAGE_MODE'] = 'refresh';
}
elseif ($arResult['AJAX_CALL'])
{
	$arResult['PAGE_MODE'] = 'next';
}
elseif ($arParams['EMPTY_PAGE'] === 'Y')
{
	$arResult['PAGE_MODE'] = 'detail_empty';
}
elseif ($_REQUEST['empty_get_comments'] === 'Y')
{
	$arResult['PAGE_MODE'] = 'detail_comments';
}
elseif ((int)$arParams['LOG_ID'] > 0)
{
	$arResult['PAGE_MODE'] = 'detail';
}