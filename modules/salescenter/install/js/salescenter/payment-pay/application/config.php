<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/application.bundle.css',
	'js' => 'dist/application.bundle.js',
	'rel' => [
		'sale.payment-pay.lib',
		'salescenter.payment-pay.user-consent',
		'salescenter.payment-pay.mixins',
		'salescenter.payment-pay.backend-provider',
		'ui.vue',
		'main.core',
		'main.core.events',
		'sale.payment-pay.const',
	],
	'skip_core' => false,
];