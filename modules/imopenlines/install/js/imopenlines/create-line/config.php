<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/create-line.bundle.css',
	'js' => 'dist/create-line.bundle.js',
	'rel' => [
		'main.core',
		'main.popup',
	],
	'skip_core' => false,
];