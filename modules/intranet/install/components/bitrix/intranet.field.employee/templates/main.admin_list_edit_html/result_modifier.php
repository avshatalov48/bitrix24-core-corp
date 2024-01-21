<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Text\HtmlFilter;
use Bitrix\Main\UserTable;

/**
 * @var $arResult array
 */

$users = UserTable::getList([
	'select' => ['NAME', 'LAST_NAME', 'LOGIN'],
	'filter' => [
		'=ID' => $arResult['value'],
	],
]);

$results = [];
while($user = $users->fetch())
{
	$results[] = [
		'login' => HtmlFilter::encode($user['LOGIN']),
		'name' => HtmlFilter::encode($user['NAME']),
		'lastName' => HtmlFilter::encode($user['LAST_NAME']),
	];
}

$arResult['value'] = $results;