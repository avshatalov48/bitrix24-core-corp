<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/collection-item-ai.bundle.css',
	'js' => 'dist/collection-item-ai.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'ui.design-tokens',
	],
	'skip_core' => true,
];