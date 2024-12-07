<?php

use Bitrix\Main;
use Bitrix\Timeman\Integration\Stafftrack\CheckIn;
use Bitrix\Timeman\Integration\Stafftrack\Counter;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$counter = null;
$isCheckinEnabled = false;
if (Main\Loader::includeModule('timeman'))
{
	$counter = Counter::get();
	$isCheckinEnabled = CheckIn::isEnabled();
}

return [
	'css' => 'dist/stafftrack-check-in.bundle.css',
	'js' => 'dist/stafftrack-check-in.bundle.js',
	'rel' => [
		'main.core',
		'stafftrack.user-statistics-link',
		'ui.analytics',
		'ui.counter',
	],
	'settings' => [
		'counter' => $counter,
		'isCheckinEnabled' => $isCheckinEnabled,
	],
	'skip_core' => false,
];
