<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/embed.bundle.css',
	'js' => 'dist/embed.bundle.js',
	'rel' => [
		'main.qrcode',
		'ui.stepbystep',
		'ui.notification',
		'landing.ui.field.color',
		'ui.switcher',
		'ui.feedback.form',
		'main.loader',
		'popup',
		'ui.sidepanel.layout',
		'main.core.events',
		'ui.alerts',
		'main.core',
		'ui.design-tokens',
		'ui.fonts.opensans',
	],
	'skip_core' => false,
];
