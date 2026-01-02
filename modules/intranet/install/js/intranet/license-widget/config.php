<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/license-widget.bundle.css',
	'js' => 'dist/license-widget.bundle.js',
	'rel' => [
		'main.core.events',
		'ui.info-helper',
		'main.core',
		'ui.popupcomponentsmaker',
	],
	'skip_core' => false,
];
