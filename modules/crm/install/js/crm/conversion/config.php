<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => '/bitrix/js/crm/conversion/dist/conversion.bundle.js',
	'skip_core' => false,
	'rel' => [
		'crm.category-list',
		'crm.category-model',
		'main.core.events',
		'main.popup',
		'ui.buttons',
		'ui.dialogs.messagebox',
		'ui.forms',
		'main.core',
	],
];
