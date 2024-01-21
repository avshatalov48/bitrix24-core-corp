<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => '/bitrix/js/crm/entity-editor/duplicate/summary-list/dist/summary-list.bundle.js',
	'css' => '/bitrix/js/crm/entity-editor/duplicate/summary-list/dist/summary-list.bundle.css',
	'rel' => [
		'main.core',
		'main.core.events',
		'main.popup',
		'ui.buttons',
		'ui.design-tokens',
		'ui.tooltip',
		'ui.hint',
	],
	'skip_core' => false,
];