<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/todo-editor.bundle.css',
	'js' => 'dist/todo-editor.bundle.js',
	'rel' => [
		'ui.vue3',
		'main.popup',
		'main.core',
		'main.core.events',
		'ui.entity-selector',
		'ui.notification',
		'main.date',
		'crm.timeline.tools',
		'crm.activity.file-uploader',
		'crm.activity.settings-popup',
	],
	'skip_core' => false,
	'oninit' => static function(){
		$date = null;
		if (\Bitrix\Main\Loader::includeModule('crm'))
		{
			$date = (new \Bitrix\Crm\Settings\WorkTime())->detectNearestWorkDateTime(3, 1);
		}

		$defaultDescription = \Bitrix\Crm\Activity\Entity\ToDo::getDescriptionForEntityType(CCrmOwnerType::Undefined);
		$defaultDescriptionDeal = \Bitrix\Crm\Activity\Entity\ToDo::getDescriptionForEntityType(CCrmOwnerType::Deal);

		return [
			'lang_additional' => [
				'CRM_TIMELINE_TODO_EDITOR_DEFAULT_DATETIME' => $date ? $date->toString() : '',
				'CRM_ACTIVITY_TODO_NOTIFICATION_DEFAULT_TEXT' => $defaultDescription,
				'CRM_ACTIVITY_TODO_NOTIFICATION_DEFAULT_TEXT_DEAL' => $defaultDescriptionDeal,
			],
		];
	}
];
