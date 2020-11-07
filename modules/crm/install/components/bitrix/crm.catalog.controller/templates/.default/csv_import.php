<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
	die();

/** @var \CBitrixComponentTemplate $this  */
/** @var \CrmCatalogControllerComponent $component */

$component->showCrmControlPanel();

$APPLICATION->IncludeComponent(
	'bitrix:crm.product.import',
	'',
	$arResult['PAGE_DESCRIPTION'],
	$component
);