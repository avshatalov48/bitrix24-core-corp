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
		'main.loader',
		'main.core.events',
		'ui.sidepanel.layout',
	],
	'skip_core' => false,
];