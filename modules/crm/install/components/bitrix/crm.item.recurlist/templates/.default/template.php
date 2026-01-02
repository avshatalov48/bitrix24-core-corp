<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$APPLICATION->IncludeComponent(
	'bitrix:crm.item.list',
	'',
	$arResult,
	$component,
	['HIDE_ICONS' => 'Y'],
);