<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/text.bundle.css',
	'js' => 'dist/text.bundle.js',
	'rel' => [
		'main.core',
		'landing.node',
	],
	'skip_core' => false,
];