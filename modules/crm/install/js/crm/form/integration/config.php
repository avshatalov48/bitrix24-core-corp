<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/integration.bundle.css',
	'js' => 'dist/integration.bundle.js',
	'rel' => [
		'ui.sidepanel-content',
		'main.core',
		'main.core.events',
		'crm.form.type',
		'crm.form.fields.mapper',
		'ui.alerts',
		'ui.buttons',
		'ui.dropdown',
		'main.core.ajax',
		'main.loader',
		'seo.ads.login',
		'ui.dialogs.messagebox',
	],
	'skip_core' => false,
];