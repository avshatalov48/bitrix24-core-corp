<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/registry.bundle.css',
	'js' => 'dist/registry.bundle.js',
	'rel' => [
		'sale.payment-pay.lib',
		'sale.payment-pay.mixins.application',
		'salescenter.payment-pay.user-consent',
		'salescenter.payment-pay.backend-provider',
		'sale.payment-pay.mixins.payment-system',
		'main.core',
		'main.core.events',
		'sale.payment-pay.const',
		'ui.vue',
	],
	'skip_core' => false,
];