<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

if (!\Bitrix\Main\Loader::includeModule('tasks'))
{
	return [];
}

return [
	'js' => 'dist/calendar.bundle.js',
	'rel' => [
		'main.core',
		'main.date',
		'tasks.v2.lib.timezone',
	],
	'skip_core' => false,
	'settings' => [
		'calendarSettings' => \Bitrix\Tasks\Util\Calendar::getSettings(),
	],
];
