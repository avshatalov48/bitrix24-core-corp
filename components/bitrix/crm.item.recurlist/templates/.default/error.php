<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;


$APPLICATION->IncludeComponent(
	'bitrix:ui.info.error',
	'',
	[
		'TITLE' => Loc::getMessage('CRM_ITEM_RECURLIST_ERROR_TITLE'),
	],
);