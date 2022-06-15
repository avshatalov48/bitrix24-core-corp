<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => [
		'dist/catalog.bundle.css',
		'/bitrix/components/bitrix/ui.button.panel/templates/.default/style.css',
		'/bitrix/js/catalog/product-form/src/component.css',
	],
	'js' => 'dist/catalog.bundle.js',
	'rel' => [
		'main.core',
		'main.popup',
		'ui.buttons',
		'catalog.store-use',
		'ui.vue',
		'ui.notification',
	],
	'skip_core' => false,
];