<?php

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Text\HtmlFilter;

if($arResult['additionalParameters']['bVarsFromForm'])
{
	$entityType = $GLOBALS[$arResult['additionalParameters']['NAME']]['ENTITY_TYPE'];
	$entityTypeId = (is_array($entityType) ? $entityType['ID'] : $entityType);
	$arResult['value'] = HtmlFilter::encode($entityTypeId);
}
elseif(is_array($arResult))
{
	$entityType = $arResult['userField']['SETTINGS']['ENTITY_TYPE'];
	$entityTypeId = (is_array($entityType) ? $entityType['ID'] : $entityType);
	$arResult['value'] = HtmlFilter::encode($entityTypeId);
}
else
{
	$arResult['value'] = '';
}

$entityTypes = CCrmStatus::GetEntityTypes();
foreach ($entityTypes as $entityType)
{
	$arr['reference'][] = $entityType['NAME'];
	$arr['reference_id'][] = $entityType['ID'];
}

$arResult['arr'] = $arr;

