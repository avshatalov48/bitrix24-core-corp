<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/restriction.bundle.css',
	'js' => 'dist/restriction.bundle.js',
	'rel' => [
		'main.core',
	],
	'skip_core' => false,
];