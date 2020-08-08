<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => '/bitrix/js/rpa/component/dist/component.bundle.js',
	'rel' => [
		'main.core',
		'main.loader',
	],
	'skip_core' => false,
];