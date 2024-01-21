<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/todo-editor.bundle.css',
	'js' => 'dist/todo-editor.bundle.js',
	'rel' => [
		'crm.activity.file-uploader',
		'crm.activity.settings-popup',
		'ui.notification',
		'ui.vue3',
		'crm.timeline.tools',
		'main.date',
		'main.popup',
		'crm.field.item-selector',
		'main.core',
		'main.core.events',
		'ui.entity-selector',
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
