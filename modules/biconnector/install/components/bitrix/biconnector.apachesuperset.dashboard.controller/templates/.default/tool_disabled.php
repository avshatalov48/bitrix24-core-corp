<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

global $APPLICATION;

/**
 * @var array $arResult
 * @var \CMain $APPLICATION
 */

$APPLICATION->IncludeComponent(
	'bitrix:intranet.tool.inaccessibility',
	'',
	[
		'SLIDER_CODE' => 'limit_BI_off'
	],
	null,
	['HIDE_ICONS' => 'Y'],
);
