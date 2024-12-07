<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/user-party.bundle.css',
	'js' => 'dist/user-party.bundle.js',
	'rel' => [
		'main.core',
		'sign.v2.helper',
		'ui.entity-selector',
		'sign.v2.b2e.user-party-counters',
		'ui.icon-set.main',
	],
	'skip_core' => false,
];