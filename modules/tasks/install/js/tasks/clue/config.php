<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/clue.bundle.css',
	'js' => 'dist/clue.bundle.js',
	'rel' => [
		'ui.tour',
		'ui.banner-dispatcher',
		'spotlight',
		'main.core',
	],
	'skip_core' => false,
];