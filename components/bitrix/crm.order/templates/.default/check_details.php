<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
/** @global CMain $APPLICATION */
/** @var array $arParams */
/** @var array $arResult */

$APPLICATION->IncludeComponent(
	'bitrix:crm.entity.details.frame',
	'',
	array(
		'ENTITY_TYPE_ID' => CCrmOwnerType::OrderCheck,
		'ENTITY_ID' => $arResult['VARIABLES']['check_id'],
		'EXTRAS' => array("ORDER_ID" => $arResult['VARIABLES']['order_id']),
		'ENABLE_TITLE_EDIT' => false,
		'DISABLE_TOP_MENU' => 'Y'
	)
);