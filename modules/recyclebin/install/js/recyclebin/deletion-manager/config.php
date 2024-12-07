<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/deletion-manager.bundle.css',
	'js' => 'dist/deletion-manager.bundle.js',
	'rel' => [
		'main.core.cache',
		'ui.progressbar',
		'ui.design-tokens',
		'main.core',
		'main.core.events',
	],
	'skip_core' => false,
];
