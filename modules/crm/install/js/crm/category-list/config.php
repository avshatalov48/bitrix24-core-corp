<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => '/bitrix/js/crm/category-list/dist/category-list.bundle.js',
	'skip_core' => false,
	'rel' => [
		'main.core',
		'crm.category-model',
	],
];
