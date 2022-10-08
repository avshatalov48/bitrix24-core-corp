<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => '../facebook.catalog.connector.css',
	'js' => '../facebook.catalog.connector.js',
	'rel' => [
		'main.core',
		'main.popup',
		'main.core.events',
		'ui.design-tokens',
	],
	'skip_core' => false,
];