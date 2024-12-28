<?php

use Bitrix\Main\Application;
use Bitrix\Main\ModuleManager;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$settings = [
	'hasLocationModule' => ModuleManager::isModuleInstalled('location'),
];

return [
	'css' => 'dist/index.bundle.css',
	'js' => 'dist/index.bundle.js',
	'rel' => [
		'ui.analytics',
		'crm.integration.analytics',
		'ui.notification',
		'ui.dialogs.messagebox',
		'crm.activity.file-uploader-popup',
		'crm.activity.settings-popup',
		'crm.activity.todo-editor-v2',
		'crm.field.item-selector',
		'crm.field.ping-selector',
		'crm.field.color-selector',
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
		'ui.sidepanel',
		'loader',
		'crm.timeline.editors.comment-editor',
		'calendar.sharing.interface',
		'ui.icon-set.api.vue',
		'ui.icon-set.actions',
		'ui.icon-set.main',
		'crm.ai.call',
		'location.core',
		'sign.v2.api',
		'sign.feature-resolver',
		'im.public',
		'ui.text-editor',
		'ui.bbcode.formatter.html-formatter',
		'bizproc.workflow.timeline',
		'ui.image-stack-steps',
		'ui.design-tokens',
		'ui.avatar',
	],
	'settings' => $settings,
	'skip_core' => false,
	'oninit' => static function() {
		return [
			'lang_additional' => [
				'AI_APP_COLLECTION_MARKET_LINK' => \Bitrix\Crm\Integration\AI\AIManager::getAiAppCollectionMarketLink(),
				'PORTAL_ZONE' => mb_strtolower(Application::getInstance()->getLicense()->getRegion() ?? ''),
			],
		];
	}
];
