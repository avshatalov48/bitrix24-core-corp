<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$needUseSchedule = true;
if (\Bitrix\Main\Loader::includeModule('tasks'))
{
	$needUseSchedule =
		\Bitrix\Tasks\Integration\Calendar\Calendar::needUseCalendar('flow')
		&& \Bitrix\Tasks\Integration\Calendar\Calendar::needUseSchedule()
	;
}

return [
	'css' => 'dist/edit-form.bundle.css',
	'js' => 'dist/edit-form.bundle.js',
	'rel' => [
		'main.popup',
		'ui.buttons',
		'tasks.wizard',
		'tasks.interval-selector',
		'main.polyfill.intersectionobserver',
		'pull.client',
		'ui.entity-selector',
		'main.core.events',
		'main.core',
		'ui.form-elements.view',
		'ui.lottie',
		'ui.sidepanel-content',
		'ui.forms',
		'ui.hint',
	],
	'settings' => [
		'currentUser' => \Bitrix\Main\Engine\CurrentUser::get()->getId(),
		'needUseSchedule' => $needUseSchedule,
	],
	'skip_core' => false,
];