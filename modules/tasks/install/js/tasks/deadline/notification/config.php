<?php

use Bitrix\Tasks\Integration\Calendar\Schedule\PortalSchedule;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/notification.bundle.css',
	'js' => 'dist/notification.bundle.js',
	'rel' => [
		'ui.form-elements.view',
		'tasks.date-rounder',
		'main.date',
		'ui.date-picker',
		'main.core.events',
		'main.popup',
		'ui.buttons',
		'main.core',
		'tasks.interval-selector',
		'ui.analytics',
	],
	'settings' => [
		'portalSettings' => (new PortalSchedule())->getSettings(),
	],
	'skip_core' => false,
];
