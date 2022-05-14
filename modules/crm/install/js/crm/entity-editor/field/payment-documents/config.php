<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/payment-documents.bundle.css',
	'js' => 'dist/payment-documents.bundle.js',
	'rel' => [
		'main.popup',
		'ui.dialogs.messagebox',
		'main.core',
		'main.core.events',
		'ui.label',
		'currency.currency-core',
	],
	'skip_core' => false,
];