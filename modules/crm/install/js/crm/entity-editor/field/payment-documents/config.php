<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/payment-documents.bundle.css',
	'js' => 'dist/payment-documents.bundle.js',
	'rel' => [
		'main.core.events',
		'main.popup',
		'currency.currency-core',
		'ui.dialogs.messagebox',
		'main.core',
		'ui.label',
	],
	'skip_core' => false,
];