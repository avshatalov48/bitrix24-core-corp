<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
	die();

/** @var \CBitrixComponentTemplate $this  */
/** @var \CrmCatalogControllerComponent $component */

$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:catalog.productcard.controller',
		'POPUP_COMPONENT_TEMPLATE_NAME' => '.default',
		'POPUP_COMPONENT_PARAMS' => [
			'SEF_MODE' => 'Y',
			'SEF_FOLDER' => '/crm/catalog/',
			'BUILDER_CONTEXT' => \Bitrix\Crm\Product\Url\ProductBuilder::TYPE_ID,
		],
		'USE_PADDING' => false,
		'USE_UI_TOOLBAR' => 'Y',
	],
	$component
);
?>
