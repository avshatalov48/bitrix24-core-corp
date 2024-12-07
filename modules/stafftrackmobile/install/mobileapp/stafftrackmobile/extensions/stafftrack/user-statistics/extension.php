<?php

use Bitrix\Main\Loader;
use Bitrix\Stafftrack\Integration\Calendar\SettingsProvider;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$calendarSettings = [];
if (Loader::includeModule('stafftrack'))
{
	$calendarSettings = SettingsProvider::getInstance()->getSettings();
}

return [
	'calendarSettings' => $calendarSettings,
];
