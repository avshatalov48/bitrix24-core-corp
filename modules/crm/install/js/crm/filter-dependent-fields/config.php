<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => 'dist/filter-dependent-fields.bundle.js',
	'rel' => [
		'main.core',
		'ui.notification',
	],
	'skip_core' => false,
];