<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @global \CMain $APPLICATION */
/** @var array $arResult */
/** @var \CatalogStoreDocumentControllerComponent $component */
/** @var \CBitrixComponentTemplate $this */

global $APPLICATION;

$APPLICATION->IncludeComponent(
	'bitrix:crm.sign.counterparty_contact.list',
	'',
	[
		'MENU_ITEMS' => $arResult['MENU_ITEMS'],
	]
);
