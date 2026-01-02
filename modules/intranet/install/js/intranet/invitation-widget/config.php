<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/invitation-widget.bundle.css',
	'js' => 'dist/invitation-widget.bundle.js',
	'rel' => [
		'ui.analytics',
		'ui.popupcomponentsmaker',
		'ui.cnt',
		'main.popup',
		'main.core.events',
		'main.core',
		'main.loader',
	],
	'skip_core' => false,
];
