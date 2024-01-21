<?php

use Bitrix\Main\UI\Extension;
use Bitrix\Timeman\Util\LimitDictionary;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}

global $APPLICATION;
$componentParameters = [
	'LIMIT_CODE' => LimitDictionary::LIMIT_OFFICE_WORKTIME_OFF,
	'MODULE' => 'timeman',
	'SOURCE' => 'schedules',
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