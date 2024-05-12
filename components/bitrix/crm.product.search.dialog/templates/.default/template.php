<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

/** @var array $arResult */
/** @var \CCrmProductSearchDialogComponent $component */

global $APPLICATION;

$APPLICATION->IncludeComponent(
	'bitrix:catalog.product.search',
	'',
	array(
		'IBLOCK_ID' => $arResult['CATALOG_ID'],
	),
	$component,
	array('HIDE_ICONS'=>true)
);
