<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @var $arResult array
 */

$items = [];
foreach($arResult['value'] as $value)
{
	$items[] = implode(' ', [
		'(' . $value['login'] . ')',
		$value['name'],
		$value['lastName'],
	]);
}

print implode('<br>', $items);