<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
	die();

/** @var \CBitrixComponentTemplate $this  */
/** @var \CrmCatalogControllerComponent $component */

$arResult['PAGE_DESCRIPTION']['SEF_FOLDER'] = $this->GetFolder().'/';
$arResult['PAGE_DESCRIPTION']['PAGE_PATH'] = 'include/list_slider.php';

$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:crm.admin.page.include',
		'POPUP_COMPONENT_TEMPLATE_NAME' => '.default',
		'POPUP_COMPONENT_PARAMS' => $arResult['PAGE_DESCRIPTION'],
		'USE_PADDING' => false,
		'USE_UI_TOOLBAR' => 'N',
	],
	$component,
	['HIDE_ICONS' => 'Y']
);