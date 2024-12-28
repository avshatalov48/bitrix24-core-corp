<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}


return [
	'css' => 'dist/recognition-promo.bundle.css',
	'js' => 'dist/recognition-promo.bundle.js',
	'rel' => [
		'main.core',
		'main.popup',
		'ui.buttons',
		'ui.icon-set.api.core',
		'ui.icon-set.main',
		'ui.icon-set.actions',
		'ui.lottie',
	],
	'skip_core' => false,
];
