<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/company-editor.bundle.css',
	'js' => 'dist/company-editor.bundle.js',
	'rel' => [
		'main.core',
		'main.core.events',
		'main.loader',
		'ui.sidepanel.layout',
	],
	'skip_core' => false,
];