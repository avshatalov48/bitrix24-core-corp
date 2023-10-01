<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

/** @var CMain $APPLICATION*/
/** @var array $arResult*/
/** @var array $arParams*/

$currentMenuItem = 'list';
include __DIR__ . '/common/menu.php';

global $APPLICATION;
$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	array(
		'POPUP_COMPONENT_NAME' => 'bitrix:crm.tracking.list',
		'POPUP_COMPONENT_TEMPLATE_NAME' => '',
		'POPUP_COMPONENT_PARAMS' => [
			'NAME_TEMPLATE' => $arResult['NAME_TEMPLATE'] ?? null,
			'PATH_TO_USER_PROFILE' => $arResult['PATH_TO_USER_PROFILE'] ?? null,
			'PATH_TO_LIST' => $arResult['PATH_TO_LIST'] ?? null,
			'PATH_TO_ADD' => $arResult['PATH_TO_ADD'] ?? null,
			'PATH_TO_EDIT' => $arResult['PATH_TO_EDIT'] ?? null,
			'PATH_TO_SITE' => $arResult['PATH_TO_SITE'] ?? null,
			'PATH_TO_PHONE' => $arResult['PATH_TO_PHONE'] ?? null,
			'PATH_TO_CHANNEL' => $arResult['PATH_TO_CHANNEL'] ?? null,
		],
		'USE_PADDING' => false,
	)
);