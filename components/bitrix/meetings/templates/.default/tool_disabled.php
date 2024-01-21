<?php

use Bitrix\Meeting\Integration\Intranet\Settings;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}

/** @var array $arResult*/
global $APPLICATION;

$componentParameters = [
	'LIMIT_CODE' => Settings::LIMIT_CODE,
	'MODULE' => 'meeting',
	'SOURCE' => 'meeting',
];

$APPLICATION->IncludeComponent(
	"bitrix:ui.sidepanel.wrapper",
	"",
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:intranet.settings.tool.stub',
		'POPUP_COMPONENT_TEMPLATE_NAME' => '',
		'POPUP_COMPONENT_PARAMS' => $componentParameters,
	],
);