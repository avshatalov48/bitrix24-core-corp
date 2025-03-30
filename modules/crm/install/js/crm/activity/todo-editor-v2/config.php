<?php

use Bitrix\Crm\Activity\Entity\ToDo;
use Bitrix\Crm\Format\EntityAddressFormatter;
use Bitrix\Crm\Integration\Bitrix24Manager;
use Bitrix\Crm\Integration\location\Format;
use Bitrix\Crm\Settings\Mode;
use Bitrix\Crm\Settings\WorkTime;
use Bitrix\Location\Service\FormatService;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$settings = [
	'canUseAddressBlock' => ModuleManager::isModuleInstalled('location'),
	'canUseCalendarBlock' => ModuleManager::isModuleInstalled('calendar'),
];

if (Loader::includeModule('crm'))
{
	$settings['crmMode'] = Mode::getCurrentName();
	$settings['locationFeatureEnabled'] = Bitrix24Manager::isFeatureEnabled('calendar_location');
}

return [
	'css' => 'dist/todo-editor-v2.bundle.css',
	'js' => 'dist/todo-editor-v2.bundle.js',
	'rel' => [
		'ui.vue3',
		'crm.timeline.tools',
		'ui.text-editor',
		'ui.analytics',
		'location.core',
		'location.widget',
		'ui.design-tokens',
		'calendar.planner',
		'main.date',
		'ui.info-helper',
		'calendar.controls',
		'calendar.sectionmanager',
		'ui.sidepanel',
		'crm.client-selector',
		'ui.notification',
		'ui.uploader.tile-widget',
		'main.popup',
		'crm.field.color-selector',
		'crm.field.ping-selector',
		'main.core',
		'main.core.events',
		'ui.entity-selector',
		'ui.vue3.directives.hint',
	],
	'skip_core' => false,
	'settings' => $settings,
	'oninit' => static function() {
		$date = null;
		if (Loader::includeModule('crm'))
		{
			$date = (new WorkTime())->detectNearestWorkDateTime(3, 1);
		}

		$defaultDescription = ToDo::getDescriptionForEntityType(CCrmOwnerType::Undefined);
		$defaultDescriptionDeal = ToDo::getDescriptionForEntityType(CCrmOwnerType::Deal);

		$format = null;
		if (Loader::includeModule('location'))
		{
			$format = FormatService::getInstance()->findByCode(
				Format::getLocationFormatCode(EntityAddressFormatter::getFormatID()),
				LANGUAGE_ID
			);
		}

		return [
			'lang_additional' => [
				'CRM_TIMELINE_TODO_EDITOR_DEFAULT_DATETIME' => $date ? $date->toString() : '',
				'CRM_ACTIVITY_TODO_NOTIFICATION_DEFAULT_TEXT' => $defaultDescription,
				'CRM_ACTIVITY_TODO_NOTIFICATION_DEFAULT_TEXT_DEAL' => $defaultDescriptionDeal,
				'CRM_ACTIVITY_TODO_ADDRESS_FORMAT' => $format ? $format->toJson() : null,
			],
		];
	}
];
