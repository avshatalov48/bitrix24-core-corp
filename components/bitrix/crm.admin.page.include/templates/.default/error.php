<?php

use Bitrix\Main\Localization\Loc;

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var \CBitrixComponentTemplate $this  */
/** @var \CrmCatalogControllerComponent $component */

Loc::loadMessages(__FILE__);

$APPLICATION->IncludeComponent(
	"bitrix:ui.info.error",
	"",
	[
		'TITLE' => Loc::getMessage('CRM_ADMIN_PAGE_INCLUDE_ERROR_TITLE'),
	]
);
