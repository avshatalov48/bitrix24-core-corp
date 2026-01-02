<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
	return;

$entityName = \CCrmOwnerType::ResolveName($arParams['ENTITY_TYPE']);
if (empty($entityName))
{
	return;
}

if (!\Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->entityType()->canReadItems((int)$arParams['ENTITY_TYPE']))
{
	return;
}

$arResult['NAVIGATION_ITEMS'] = is_array($arParams['NAVIGATION_ITEMS']) ? $arParams['NAVIGATION_ITEMS'] : array();
$arResult['GRID_ID'] = $arParams['GRID_ID'] <> '' ? $arParams['GRID_ID'] : $entityName;

$this->IncludeComponentTemplate();