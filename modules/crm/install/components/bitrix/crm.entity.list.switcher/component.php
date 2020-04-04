<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
	return;

$entityName = \CCrmOwnerType::ResolveName($arParams['ENTITY_TYPE']);
if (empty($entityName))
	return;

$userPermissions = CCrmPerms::GetCurrentUserPermissions();
if ($userPermissions->HavePerm($entityName, BX_CRM_PERM_NONE))
	return;

$arResult['NAVIGATION_ITEMS'] = is_array($arParams['NAVIGATION_ITEMS']) ? $arParams['NAVIGATION_ITEMS'] : array();
$arResult['GRID_ID'] = strlen($arParams['GRID_ID']) > 0 ? $arParams['GRID_ID'] : $entityName;

$this->IncludeComponentTemplate();