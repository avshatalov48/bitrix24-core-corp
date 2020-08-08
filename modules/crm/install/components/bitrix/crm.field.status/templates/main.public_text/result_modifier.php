<?php

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$settings = (
isset($arResult['userField']['SETTINGS']) && is_array($arResult['userField']['SETTINGS'])
	?
	$arResult['userField']['SETTINGS']
	:
	[]
);

$entityType = ($settings['ENTITY_TYPE'] ?? '');

$statusList = ($entityType !== '' ? CCrmStatus::GetStatus($entityType) : []);
if(empty($statusList))
{
	$value = '';
}
else
{
	$results = [];
	$value = $arResult['value'];
	if(count($value) && $value[0] !== null)
	{
		foreach($value as $statusId)
		{
			if(isset($statusList[$statusId]))
			{
				$results[] = ($statusList[$statusId]['NAME'] ?? "[{$statusId}]");
			}
		}
	}
	$value = implode(', ', $results);
}

$arResult['value'] = $value;