<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/done.bundle.css',
	'js' => 'dist/done.bundle.js',
	'rel' => [
		'main.core',
	],
	'skip_core' => false,
];