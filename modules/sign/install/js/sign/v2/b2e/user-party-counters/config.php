<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/user-party-counters.bundle.css',
	'js' => 'dist/user-party-counters.bundle.js',
	'rel' => [
		'main.core',
		'ui.icon-set.api.core',
		'main.popup',
		'sign.tour',
	],
	'skip_core' => false,
];
