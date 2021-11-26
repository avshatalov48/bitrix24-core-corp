<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/user-consent.bundle.css',
	'js' => 'dist/user-consent.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'main.core.events',
		'sale.payment-pay.const',
	],
	'skip_core' => true,
];