<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/grid-activities-manager.bundle.css',
	'js' => 'dist/grid-activities-manager.bundle.js',
	'rel' => [
		'main.core',
	],
	'skip_core' => false,
];