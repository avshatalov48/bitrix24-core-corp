<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
};

use Bitrix\Socialnetwork\ComponentHelper;

/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */
/** @var array $arResult */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */

$pageId = '';
include('util_group_menu.php');

$componentParameters = [
	"PATH_TO_USER" => $arParams["PATH_TO_USER"],
	"PATH_TO_GROUP" => $arResult["PATH_TO_GROUP"],
	"PAGE_VAR" => $arResult["ALIASES"]["page"],
	"USER_VAR" => $arResult["ALIASES"]["user_id"],
	"GROUP_VAR" => $arResult["ALIASES"]["group_id"],
	"SET_NAV_CHAIN" => $arResult["SET_NAV_CHAIN"],
	"SET_TITLE" => $arResult["SET_TITLE"],
	"USER_ID" => $arResult["VARIABLES"]["user_id"],
	"GROUP_ID" => $arResult["VARIABLES"]["group_id"],
	"PAGE_ID" => "group_features",
];
/*
$APPLICATION->IncludeComponent(
	'bitrix:socialnetwork.group.card.menu',
	'',
	[
		'GROUP_ID' => $arResult['VARIABLES']['group_id'],
		'TAB' => 'features',
		'URLS' => ComponentHelper::getWorkgroupSliderMenuUrlList($arResult),
		'SIGNED_PARAMETERS' => ComponentHelper::listWorkgroupSliderMenuSignedParameters($componentParameters),
	]
);
*/
$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:socialnetwork.features',
		'POPUP_COMPONENT_TEMPLATE_NAME' => '',
		'POPUP_COMPONENT_PARAMS' => $componentParameters,
	]
);
