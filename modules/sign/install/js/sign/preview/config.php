<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/preview.bundle.css',
	'js' => 'dist/preview.bundle.js',
	'rel' => [
		'main/loader',
		'main.loader',
		'main.core.events',
		'main.core',
		'ui.design-tokens',
		'ui.font.opensans',
	],
	'skip_core' => false,
];