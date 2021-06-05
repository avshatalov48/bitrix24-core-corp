<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => '/bitrix/js/crm/category-model/dist/category-model.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'crm.model',
	],
	'skip_core' => true,
];