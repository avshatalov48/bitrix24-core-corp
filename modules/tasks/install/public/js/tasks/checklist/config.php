<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => '/bitrix/js/tasks/checklist/dist/check-list-item.bundle.css',
	'js' => '/bitrix/js/tasks/checklist/dist/check-list-item.bundle.js',
	'rel' => [
		'main.core',
	],
	'skip_core' => false,
];