<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/edit-form.bundle.css',
	'js' => 'dist/edit-form.bundle.js',
	'rel' => [
		'main.popup',
		'ui.buttons',
		'tasks.wizard',
		'ui.sidepanel.layout',
		'tasks.interval-selector',
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
	],
	'skip_core' => false,
];