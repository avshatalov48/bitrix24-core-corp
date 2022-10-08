<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'style.css',
	'js' => 'dist/users.bundle.js',
	'rel' => [
		'ui.design-tokens',
		'main.core',
		'main.popup',
		'main.polyfill.intersectionobserver',
		'main.core.events',
		'main.loader',
	],
	'skip_core' => false,
];
