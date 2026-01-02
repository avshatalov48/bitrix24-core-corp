<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/dialog.bundle.css',
	'js' => 'dist/dialog.bundle.js',
	'rel' => [
		'ui.info-helper',
		'main.core',
		'main.popup',
		'main.loader',
		'color_picker',
		'ui.uploader.core',
	],
	'skip_core' => false,
];
