<?php

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Text\HtmlFilter;

$result = [];
foreach($arResult['value'] as $value)
{
	$ar = CCrmStatus::GetStatusList($arResult['userField']['SETTINGS']['ENTITY_TYPE']);
	$result[] = (isset($ar[$value]) ? HtmlFilter::encode($ar[$value]) : '&nbsp;');
}

print implode('<br>', $result);