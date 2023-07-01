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
		'ui.design-tokens',
		'ui.fonts.opensans',
		'kanban',
		'ajax',
		'color_picker',
		'date',
		'crm_activity_planner',
		'crm.activity.adding-popup',
		'ui.buttons',
		'main.popup',
		'ui.notification',
		'ls',
		'crm.kanban.sort',
		'crm.kanban.restriction',
		'crm.datetime',
		'crm.toolbar-component',
		'ui.tooltip',
		'ui.tour',
	],
];
