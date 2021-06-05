<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => '/bitrix/js/crm/stage-model/dist/stage-model.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'crm.model',
	],
	'skip_core' => true,
];