<?php

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Text\HtmlFilter;

foreach($arResult['value'] as $value)
{
	$ar = CCrmStatus::GetStatusList($arResult['userField']['SETTINGS']['ENTITY_TYPE']);
	print (isset($ar[$value]) ? HtmlFilter::encode($ar[$value]) : '&nbsp;');
}
