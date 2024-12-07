<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => [
		'css/kanban.css',
	],
	'js' => [
		'js/actions.js',
		'js/grid.js',
		'js/item.js',
		'js/column.js',
		'js/dropzone.js',
		'dist/kanban.js',
	],
	'lang' => '/bitrix/modules/crm/kanban.php',
	'rel' => [
		'ajax',
		'color_picker',
		'date',
		'kanban',
		'ls',
		'crm_activity_planner',
		'crm.activity.adding-popup',
		'crm.integration.analytics',
		'crm.kanban.restriction',
		'crm.kanban.sort',
		'crm.toolbar-component',
		'main.date',
		'main.popup',
		'pull.queuemanager',
		'ui.buttons',
		'ui.design-tokens',
		'ui.fonts.opensans',
		'ui.notification',
		'ui.tooltip',
		'ui.tour',
	],
];
