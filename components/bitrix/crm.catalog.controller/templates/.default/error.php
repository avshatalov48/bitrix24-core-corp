<?php

use Bitrix\Main\Localization\Loc;

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var \CBitrixComponentTemplate $this  */
/** @var \CrmCatalogControllerComponent $component */

if (!$arResult['IS_SIDE_PANEL'])
{
	$component->showCrmControlPanel();
	$APPLICATION->SetTitle(\Bitrix\Main\Localization\Loc::getMessage('CRM_CATALOG_TITLE'));
}

Loc::loadMessages(__FILE__);

$componentParams = [
	'TITLE' => Loc::getMessage('CRM_CATALOG_CONTROLLER_ACCESS_DENIED_ERROR_TITLE'),
];

Bitrix\UI\Toolbar\Facade\Toolbar::deleteFavoriteStar();
$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:ui.info.error',
		'POPUP_COMPONENT_TEMPLATE_NAME' => '.default',
		'POPUP_COMPONENT_PARAMS' => $componentParams,
		'USE_PADDING' => false,
		'USE_UI_TOOLBAR' => 'N',
	],
	$component
);
