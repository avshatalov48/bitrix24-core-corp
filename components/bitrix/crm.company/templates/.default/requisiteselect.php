<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

/**
 * Bitrix vars
 * @global CUser $USER
 * @global CMain $APPLICATION
 * @global CDatabase $DB
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponent $component
 */

$APPLICATION->IncludeComponent(
	'bitrix:crm.entity.requisite.select',
	'',
	array(
		'GUID' => 'company_requisite_selector',
		'ENTITY_TYPE_ID' => CCrmOwnerType::Company,
		'ENTITY_ID' => $arResult['VARIABLES']['company_id']
	)
);