<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/registry.bundle.css',
	'js' => 'dist/registry.bundle.js',
	'rel' => [
		'ui.sidepanel.layout',
		'seo.ads.login',
		'main.core',
		'ui.fonts.opensans',
	],
	'skip_core' => false,
];