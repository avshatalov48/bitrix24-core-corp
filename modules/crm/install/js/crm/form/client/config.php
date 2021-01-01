<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/client.bundle.css',
	'js' => 'dist/client.bundle.js',
	'rel' => [
		'main.core',
	],
	'skip_core' => false,
];