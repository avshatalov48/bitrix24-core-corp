<?php

namespace Bitrix\Intranet\Site\Sections;

use Bitrix\Calendar\Internals\Counter\CounterDictionary;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

class CalendarSection
{
	public static function getItems(): array
	{
		return [
			static::getMyCalendar(),
			static::getCompanyCalendar(),
			static::getRooms(),
		];
	}

	public static function isBitrix24Cloud(): bool
	{
		return ModuleManager::isModuleInstalled('bitrix24');
	}

	public static function getMyCalendar(): array
	{
		$available = ModuleManager::isModuleInstalled('calendar') && \CBXFeatures::isFeatureEnabled('Calendar');
		if (!static::isBitrix24Cloud())
		{
			$available = $available && CollaborationSection::isFeatureEnabled('calendar');
		}

		return [
			'id' => 'my_calendar',
			'title' => Loc::getMessage('INTRANET_CALENDAR_SECTION_MY_CALENDAR'),
			'available' => $available,
			'url' => SITE_DIR . 'company/personal/user/' . CurrentUser::get()->getId() . '/calendar/',
			'menuData' => [
				'menu_item_id' => 'menu_my_calendar',
				'counter_id' => 'calendar_my',
			],
		];
	}

	public static function getCompanyCalendar(): array
	{
		$available = ModuleManager::isModuleInstalled('calendar') && \CBXFeatures::isFeatureEnabled('CompanyCalendar');

		return [
			'id' => 'company_calendar',
			'title' => Loc::getMessage('INTRANET_CALENDAR_SECTION_COMPANY_CALENDAR'),
			'available' => $available,
			'url' => SITE_DIR . 'calendar/',
			'menuData' => [
				'menu_item_id' => 'menu_company_calendar',
			],
		];
	}

	public static function getRooms(): array
	{
		return [
			'id' => 'rooms',
			'title' => Loc::getMessage('INTRANET_CALENDAR_SECTION_ROOMS'),
			'available' => ModuleManager::isModuleInstalled('calendar'),
			'url' => SITE_DIR . 'calendar/rooms/',
			'menuData' => [
				'menu_item_id' => 'menu_rooms',
			],
		];
	}
}
