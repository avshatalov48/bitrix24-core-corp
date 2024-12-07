<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => 'dist/invitation-counter.bundle.js',
	'rel' => [
		'ui.counterpanel',
		'main.core',
		'main.core.events',
	],
	'skip_core' => false,
];