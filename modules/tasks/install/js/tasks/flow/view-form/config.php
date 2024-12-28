<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/view-form.bundle.css',
	'js' => 'dist/view-form.bundle.js',
	'rel' => [
		'main.core.events',
		'main.popup',
		'tasks.flow.team-popup',
		'tasks.side-panel-integration',
		'ui.label',
		'main.core',
		'main.loader',
		'ui.buttons',
		'ui.info-helper',
		'ui.notification',
	],
	'skip_core' => false,
];
