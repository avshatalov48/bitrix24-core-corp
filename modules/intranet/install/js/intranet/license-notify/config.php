<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/license-notify.bundle.css',
	'js' => 'dist/license-notify.bundle.js',
	'rel' => [
		'main.popup',
		'ui.buttons',
		'ui.banner-dispatcher',
		'ui.design-tokens',
		'main.core',
		'main.date',
	],
	'skip_core' => false,
];