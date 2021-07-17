<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/property.bundle.css',
	'js' => 'dist/property.bundle.js',
	'rel' => [
		'ui.type',
		'main.core',
		'ui.vue',
		'sale.checkout.const',
		'sale.checkout.view.property',
	],
	'skip_core' => false,
];