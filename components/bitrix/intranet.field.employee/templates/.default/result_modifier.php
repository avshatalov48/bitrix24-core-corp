<?php

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Text\HtmlFilter;
use Bitrix\Main\UserTable;

$results = [];

$users = UserTable::getList([
	'select' => ['NAME', 'WORK_POSITION'],
	'filter' => [
		'=ID' => $arResult['value']
	],
]);

while($user = $users->fetch())
{
	$results[] = [
		'name' => HtmlFilter::encode($user['NAME']),
		'workPosition' => HtmlFilter::encode($user['WORK_POSITION'])
	];
}

$arResult['value'] = $results;