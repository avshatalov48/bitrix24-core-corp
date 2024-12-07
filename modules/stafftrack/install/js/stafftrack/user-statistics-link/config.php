<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/user-statistics-link.bundle.css',
	'js' => 'dist/user-statistics-link.bundle.js',
	'rel' => [
		'main.core',
		'ui.qrauthorization',
	],
	'skip_core' => false,
];