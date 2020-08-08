<?php

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\UserField\Types\ElementType;
use Bitrix\Main\Text\HtmlFilter;

print (
!empty($arResult['additionalParameters']['VALUE']) ?
	$arResult['additionalParameters']['VALUE'] : '&nbsp;'
);