<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @var CBitrixComponentTemplate $this */
/** @var CCrmEntityDetailsFrameComponent $component */

\Bitrix\Main\UI\Extension::load([
	'ui.hint',
]);

$component->addToolbar($this);

$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => $arResult['COMPONENT_NAME'],
		'POPUP_COMPONENT_TEMPLATE_NAME' => '',
		'POPUP_COMPONENT_PARAMS' => $arResult['COMPONENT_PARAMS'],
		'USE_UI_TOOLBAR' => 'Y',
		'USE_FAST_WAY_CLOSE_LOADER' => true,
		'USE_PADDING' => false,
	],
);
