<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/messages.bundle.css',
	'js' => 'dist/messages.bundle.js',
	'rel' => [
		'crm.autorun',
		'ui.notification',
		'main.popup',
		'ui.entity-catalog',
		'main.core',
		'main.core.events',
		'ui.design-tokens',
	],
	'skip_core' => false,
];