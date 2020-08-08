<?php

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

foreach($arResult['value'] as $key => $value)
{
	$attrList = [];
	$attrList['value'] = $value;
	$attrList['fieldName'] = str_replace(
		'[]',
		'[' . $key . ']',
		$arResult['fieldName']
	);

	$arResult['value'][$key] = $attrList;
}