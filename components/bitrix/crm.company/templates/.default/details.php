<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
/** @global CMain $APPLICATION */
/** @var array $arParams */
/** @var array $arResult */

$APPLICATION->IncludeComponent(
	'bitrix:crm.entity.details.frame',
	'',
	array(
		'ENTITY_TYPE_ID' => CCrmOwnerType::Company,
		'ENTITY_ID' => $arResult['VARIABLES']['company_id'],
		'ENABLE_TITLE_EDIT' => true
	)
);
