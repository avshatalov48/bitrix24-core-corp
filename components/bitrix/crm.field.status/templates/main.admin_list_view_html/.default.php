<?php

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Text\HtmlFilter;

$ar = CCrmStatus::GetStatusList($arResult['userField']['SETTINGS']['ENTITY_TYPE']);
print (
isset($ar[$arResult['additionalParameters']['VALUE']])
	? HtmlFilter::encode($ar[$arResult['additionalParameters']['VALUE']])
	: '&nbsp;'
);