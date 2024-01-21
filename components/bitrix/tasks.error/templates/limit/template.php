<?php

use Bitrix\Main\UI\Extension;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

/** @var array $arResult*/
global $APPLICATION;


$componentParameters = [
	'LIMIT_CODE' => $arResult['LIMIT_CODE'],
	'MODULE' => 'tasks',
	'SOURCE' => $arResult['SOURCE'],
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