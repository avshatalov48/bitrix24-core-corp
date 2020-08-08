<?php

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Text\HtmlFilter;

if(isset($arResult['titleUserId']))
{
	print '[' . $arResult['titleUserId'] . ']';
	print '(' . HtmlFilter::encode($arResult['user']['LOGIN']) . ')';
	print implode(' ', [
		HtmlFilter::encode($arResult['user']['NAME']),
		HtmlFilter::encode($arResult['user']['LAST_NAME'])
	]);
} else {
	print '&nbsp;';
}