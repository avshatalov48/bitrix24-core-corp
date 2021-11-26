<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/mixins.bundle.css',
	'js' => 'dist/mixins.bundle.js',
	'rel' => [
		'main.polyfill.core',
	],
	'skip_core' => true,
];