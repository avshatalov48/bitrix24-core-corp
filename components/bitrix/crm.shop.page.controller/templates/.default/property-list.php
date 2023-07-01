<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @var array $arParams
 * @var array $arResult
 * @var CMain $APPLICATION
 * @var CBitrixComponent $component
 * @var CBitrixComponentTemplate $this
 */

$APPLICATION->IncludeComponent(
	"bitrix:crm.admin.page.controller",
	"",
	$arResult['CRM_ADMIN_PAGE_CONTROLLER_PARAMS'] + [
		'IS_ONLY_MENU' => true,
	]
);

$APPLICATION->IncludeComponent(
	"bitrix:iblock.property.grid",
	"",
	[
		'IBLOCK_ID' => $arResult['IBLOCK_ID'],
	]
);
