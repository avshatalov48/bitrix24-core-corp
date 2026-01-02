<?php

namespace Bitrix\Mobile\Profile\Tab;

use Bitrix\Intranet\Settings\Tools\ToolsManager;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Mobile\Profile\Enum\TabContextType;
use Bitrix\Mobile\Profile\Enum\TabType;

class CalendarTab extends BaseProfileTab
{
	/**
	 * @return TabType
	 */
	public function getType(): TabType
	{
		return TabType::CALENDAR;
	}

	/**
	 * @return TabContextType
	 */
	public function getContextType(): TabContextType
	{
		return TabContextType::COMPONENT;
	}

	/**
	 * @return bool
	 * @throws LoaderException
	 */
	public function isAvailable(): bool
	{
		$isToolAvailable = (
			!Loader::includeModule('intranet')
			|| ToolsManager::getInstance()->checkAvailabilityByToolId('calendar')
		);

		return (
			$isToolAvailable
			&& Loader::includeModule('calendar')
			&& Loader::includeModule('calendarmobile')
		);
	}

	/**
	 * @return string
	 */
	public function getTitle(): string
	{
		return Loc::getMessage('PROFILE_TAB_CALENDAR_TITLE');
	}

	/**
	 * @return array
	 */
	public function getParams(): array
	{
		return [
			'CAL_TYPE' => 'user',
			'OWNER_ID' => $this->ownerId,
			'VIEW_MODE' => 'tabs',
		];
	}

	/**
	 * @return string|null
	 */
	public function getComponentName(): ?string
	{
		return 'calendar:calendar.event.list';
	}
}
