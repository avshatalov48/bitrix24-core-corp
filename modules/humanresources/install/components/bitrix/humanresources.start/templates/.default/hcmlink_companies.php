<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var \CMain $APPLICATION */
/** @var array $arParams */
$componentParams = $arParams['POPUP_COMPONENT_PARAMS'] ?? [];

$APPLICATION->SetPageProperty('BodyClass', 'ui-page-slider-wrapper-hcmlink');

$APPLICATION->SetTitle(
	\Bitrix\Main\Localization\Loc::getMessage('HUMAN_RESOURCES_START_HCMLINK_COMPANIES_PAGE_TITLE')
);
$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:humanresources.hcmlink.company.list',
		'POPUP_COMPONENT_TEMPLATE_NAME' => '',
		'POPUP_COMPONENT_PARAMS' => [],
		'USE_PADDING' => true,
		'PLAIN_VIEW' => false,
		'PAGE_MODE' => false,
		'PAGE_MODE_OFF_BACK_URL' => "/hr/structure"
	]
);