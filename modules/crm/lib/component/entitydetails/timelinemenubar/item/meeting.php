<?php

namespace Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item;

use Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item;

class Meeting extends Item
{
	public function getId(): string
	{
		return 'meeting';
	}

	public function getName(): string
	{
		return \Bitrix\Main\Localization\Loc::getMessage('CRM_TIMELINE_MEETING');
	}

	public function isAvailable(): bool
	{
		if (!\Bitrix\Main\ModuleManager::isModuleInstalled('calendar'))
		{
			return false;
		}
		if (!\Bitrix\Crm\Settings\ActivitySettings::areOutdatedCalendarActivitiesEnabled())
		{
			return false;
		}

		return !$this->isCatalogEntityType() && \Bitrix\Crm\Settings\Crm::isUniversalActivityScenarioEnabled();
	}
}
