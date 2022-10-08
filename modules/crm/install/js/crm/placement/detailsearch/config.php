<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => '/bitrix/js/crm/placement/detailsearch/dist/placement.bundle.js',
	'css' => '/bitrix/js/crm/placement/detailsearch/dist/placement.bundle.css',
	'rel' => [
		'main.core',
	],
	'skip_core' => false,
];