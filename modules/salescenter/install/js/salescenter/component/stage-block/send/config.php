<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/send.bundle.css',
	'js' => 'dist/send.bundle.js',
	'rel' => [
		'main.core',
	],
	'skip_core' => false,
];