<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

/** @var CMain $APPLICATION*/
/** @var CBitrixComponentTemplate $this*/
/** @var array $arResult*/
/** @var array $arParams*/

$currentMenuItem = 'settings';
include __DIR__ . '/common/menu.php';

global $APPLICATION;
$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	array(
		'POPUP_COMPONENT_NAME' => 'bitrix:crm.tracking.settings',
		'POPUP_COMPONENT_TEMPLATE_NAME' => '',
		'POPUP_COMPONENT_PARAMS' => [

			'NAME_TEMPLATE' => $arResult['NAME_TEMPLATE'],
			'PATH_TO_USER_PROFILE' => $arResult['PATH_TO_USER_PROFILE'],
			'PATH_TO_LIST' => $arResult['PATH_TO_LIST'],
			'PATH_TO_ADD' => $arResult['PATH_TO_ADD'],
			'PATH_TO_EDIT' => $arResult['PATH_TO_EDIT'],
		],
		'USE_PADDING' => false,
		'CLOSE_AFTER_SAVE' => true,
	)
);