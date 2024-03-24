<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/index.bundle.css',
	'js' => 'dist/index.bundle.js',
	'rel' => [
		'ui.analytics',
		'ui.notification',
		'ui.dialogs.messagebox',
		'crm.activity.file-uploader-popup',
		'crm.activity.settings-popup',
		'crm.activity.todo-editor',
		'crm.timeline.tools',
		'main.date',
		'main.popup',
		'main.core.events',
		'ui.vue3',
		'main.core',
		'crm.router',
		'ui.cnt',
		'ui.label',
		'ui.buttons',
		'ui.vue3.components.audioplayer',
		'ui.vue3.components.hint',
		'ui.alerts',
		'ui.fonts.opensans',
		'loader',
		'crm.timeline.editors.comment-editor',
		'calendar.sharing.interface',
		'ui.icon-set.api.vue',
		'ui.icon-set.actions',
		'ui.icon-set.main',
		'crm.ai.call',
	],
	'skip_core' => false,
	'oninit' => static function()
	{
		return [
			'lang_additional' => [
				'AI_APP_COLLECTION_MARKET_LINK' => \Bitrix\Crm\Integration\AI\AIManager::getAiAppCollectionMarketLink(),
			]
		];
	}
];
