<?php

use Bitrix\Crm\Format\EntityAddressFormatter;
use Bitrix\Crm\Integration\location\Format;
use Bitrix\Location\Service\FormatService;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$settings = [
	'canUseAddressBlock' => \Bitrix\Main\ModuleManager::isModuleInstalled('location'),
];

if (\Bitrix\Main\Loader::includeModule('crm'))
{
	$settings['crmMode'] = \Bitrix\Crm\Settings\Mode::getCurrentName();
	$settings['locationFeatureEnabled'] = \Bitrix\Crm\Integration\Bitrix24Manager::isFeatureEnabled('calendar_location');
}

return [
	'css' => 'dist/todo-editor-v2.bundle.css',
	'js' => 'dist/todo-editor-v2.bundle.js',
	'rel' => [
		'ui.vue3',
		'crm.timeline.tools',
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
		'ui.text-editor',
	],
	'skip_core' => false,
	'settings' => $settings,
	'oninit' => static function() {
		$date = null;
		if (\Bitrix\Main\Loader::includeModule('crm'))
		{
			$date = (new \Bitrix\Crm\Settings\WorkTime())->detectNearestWorkDateTime(3, 1);
		}

		$defaultDescription = \Bitrix\Crm\Activity\Entity\ToDo::getDescriptionForEntityType(CCrmOwnerType::Undefined);
		$defaultDescriptionDeal = \Bitrix\Crm\Activity\Entity\ToDo::getDescriptionForEntityType(CCrmOwnerType::Deal);

		$format = null;
		if (\Bitrix\Main\Loader::includeModule('location'))
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
