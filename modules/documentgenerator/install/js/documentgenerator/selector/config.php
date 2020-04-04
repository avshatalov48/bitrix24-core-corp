<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => [
		'/bitrix/js/documentgenerator/selector/dist/selector.bundle.css'
	],
	'js' => '/bitrix/js/documentgenerator/selector/dist/selector.bundle.js',
	'rel' => [
		'main.loader',
		'main.popup',
		'main.core',
		'documentpreview',
	],
	'skip_core' => false,
];