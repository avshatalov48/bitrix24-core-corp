<?php

use Bitrix\Main\Localization\Loc;

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var \CBitrixComponentTemplate $this  */
/** @var \CrmCatalogControllerComponent $component */

Loc::loadMessages(__FILE__);

$isIframeMode = $_REQUEST['IFRAME'] === 'Y' && $_REQUEST['IFRAME_TYPE'] === 'SIDE_SLIDER';

$componentParams = [
	'TITLE' => Loc::getMessage('CRM_ORDER_MATCHER_ACCESS_DENIED_TITLE'),
];

if ($isIframeMode)
{
	$APPLICATION->IncludeComponent(
		'bitrix:ui.sidepanel.wrapper',
		'',
		[
			'POPUP_COMPONENT_NAME' => 'bitrix:ui.info.error',
			'POPUP_COMPONENT_TEMPLATE_NAME' => '',
			'POPUP_COMPONENT_USE_BITRIX24_THEME' => 'Y',
			'POPUP_COMPONENT_PARAMS' => $componentParams,
			'USE_PADDING' => true,
			'USE_UI_TOOLBAR' => 'Y',
		]
	);
}
else
{
	$APPLICATION->IncludeComponent(
		"bitrix:ui.info.error",
		"",
		$componentParams
	);
}

