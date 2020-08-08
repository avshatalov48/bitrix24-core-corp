<?php

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Text\HtmlFilter;

$userField = $arResult['userField'];

$entityTypeMap = [];
$settings = (
isset($userField['SETTINGS']) && is_array($userField['SETTINGS'])
	? $userField['SETTINGS'] : []
);

foreach($settings as $entityTypeName => $flag)
{
	if(mb_strtoupper($flag) === 'Y')
	{
		$entityTypeMap[CCrmOwnerType::ResolveID($entityTypeName)] = true;
	}
}

$primaryEntityTypeId = CCrmOwnerType::Undefined;
if(count($entityTypeMap))
{
	reset($entityTypeMap);
	$primaryEntityTypeId = key($entityTypeMap);
}

$results = [];
$value = $arResult['value'];

foreach($value as $slug)
{
	if($slug !== null)
	{
		if(is_numeric($slug))
		{
			$results[] = CCrmOwnerType::GetCaption($primaryEntityTypeId, $slug, false);
		}
		else
		{
			$parts = explode('_', $slug);
			if(count($parts) <= 1)
			{
				continue;
			}

			$entityTypeID = \CCrmOwnerTypeAbbr::ResolveTypeID($parts[0]);
			if(isset($entityTypeMap[$entityTypeID]))
			{
				$results[] = CCrmOwnerType::GetCaption($entityTypeID, $parts[1], false);
			}
		}
	}
}

$arResult['value'] = HtmlFilter::encode(implode(', ', $results));