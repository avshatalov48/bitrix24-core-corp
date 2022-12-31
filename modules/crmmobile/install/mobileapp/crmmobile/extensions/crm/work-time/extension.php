<?php

use Bitrix\Main\Loader;
use Bitrix\Crm\Settings\WorkTime;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

if (!Loader::includeModule('crm'))
{
	return [
		'WEEK_START' => 'MO',
		'TIME_FROM' => '9:0',
		'TIME_TO' => '18:0',
		'HOLIDAYS' => [],
		'DAY_OFF' => [],
	];
}

$workTime = new WorkTime();
$calendar = $workTime->getData();

return [
	'WEEK_START' => $calendar['WEEK_START'],
	'TIME_FROM' => $calendar['TIME_FROM']->toString(),
	'TIME_TO' => $calendar['TIME_TO']->toString(),
	'HOLIDAYS' => $calendar['HOLIDAYS'],
	'DAY_OFF' => $calendar['DAY_OFF'],
];
