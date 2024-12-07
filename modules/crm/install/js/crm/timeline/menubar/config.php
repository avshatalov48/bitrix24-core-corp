<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/toolbar.bundle.css',
	'js' => 'dist/toolbar.bundle.js',
	'rel' => [
		'main.core.events',
		'calendar.sharing.interface',
		'crm_common',
		'crm.activity.todo-editor-v2',
		'crm.client-selector',
		'crm.entity-selector',
		'crm.messagesender',
		'crm.template.editor',
		'crm.tour-manager',
		'ui.entity-selector',
		'main.loader',
		'main.popup',
		'ui.icon-set.api.core',
		'ui.icon-set.actions',
		'ui.icon-set.main',
		'ui.icon-set.social',
		'ui.tour',
		'ui.forms',
	],
	'oninit' => function() {
		return [
			'lang_additional' => [
				'MARKET_BASE_PATH' => \Bitrix\Crm\Integration\Market\Router::getBasePath(),
			],
		];
	},
];
