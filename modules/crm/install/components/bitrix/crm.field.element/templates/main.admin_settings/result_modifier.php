<?php

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$possibleEntityTypeIds = \Bitrix\Crm\UserField\Types\ElementType::getPossibleEntityTypes();

$arResult['titles'] = $possibleEntityTypeIds;
$arResult['entities'] = [];

foreach ($possibleEntityTypeIds as $entityType => $title)
{
	if($arResult['additionalParameters']['bVarsFromForm'])
	{
		$arResult['entities'][$entityType] = ($GLOBALS[$arResult['additionalParameters']['NAME']][$entityType] === 'Y' ? 'Y' : 'N');
	}
	elseif(is_array($arResult['userField']))
	{
		$arResult['entities'][$entityType] = ($arResult['userField']['SETTINGS'][$entityType] === 'Y' ? 'Y' : 'N');
	}
	else
	{
		$arResult['entities'][$entityType] = 'Y';
	}
}
