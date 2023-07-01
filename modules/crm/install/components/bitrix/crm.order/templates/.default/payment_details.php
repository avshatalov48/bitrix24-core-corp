<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @global CMain $APPLICATION */
/** @var array $arParams */
/** @var array $arResult */

$APPLICATION->IncludeComponent(
	'bitrix:crm.entity.details.frame',
	'',
	[
		'ENTITY_TYPE_ID' => CCrmOwnerType::OrderPayment,
		'ENTITY_ID' => $arResult['VARIABLES']['payment_id'] ?? null,
		'EXTRAS' => ["ORDER_ID" => $arResult['VARIABLES']['order_id'] ?? null],
		'ENABLE_TITLE_EDIT' => false,
		'DISABLE_TOP_MENU' => 'Y',
	]
);
