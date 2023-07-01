<?php

namespace Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item;

use Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item;

class Call extends Item
{
	public function getId(): string
	{
		return 'call';
	}

	public function getName(): string
	{
		return \Bitrix\Main\Localization\Loc::getMessage('CRM_TIMELINE_CALL');
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

		return !$this->isCatalogEntityType();
	}
}
