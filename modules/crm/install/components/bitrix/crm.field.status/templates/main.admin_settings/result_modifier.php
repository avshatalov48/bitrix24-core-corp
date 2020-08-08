<?php

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Text\HtmlFilter;

if($arResult['additionalParameters']['bVarsFromForm'])
{
	$arResult['value'] =
		HtmlFilter::encode($GLOBALS[$arResult['additionalParameters']['NAME']]['ENTITY_TYPE']);
}
elseif(is_array($arResult))
{
	$arResult['value'] =
		HtmlFilter::encode($arResult['userField']['SETTINGS']['ENTITY_TYPE']);
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

