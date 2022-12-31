<?php

use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

Loc::loadMessages(__FILE__);

$APPLICATION->IncludeComponent(
	'bitrix:ui.info.error',
	'',
	[
		'TITLE' => Loc::getMessage('CRM_CONFIG_CATALOG_SETTINGS_ACCESS_DENIED_TITLE'),
		'DESCRIPTION' => Loc::getMessage('CRM_CONFIG_CATALOG_SETTINGS_ACCESS_DENIED_DESCRIPTION'),
	]
);
