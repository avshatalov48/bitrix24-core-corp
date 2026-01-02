<?php

namespace Bitrix\Mobile\Profile\Tab;

use Bitrix\Mobile\Profile\Enum\TabType;

class ProfileTabFactory
{
	/**
	 * @param int $viewerId
	 * @param int $ownerId
	 * @return BaseProfileTab[]
	 */
	public static function createTabs(int $viewerId, int $ownerId): array
	{
		$tabs = [];
		foreach (TabType::cases() as $tabType)
		{
			$tabs[$tabType->value] = self::createTab($tabType, $viewerId, $ownerId);
		}

		return $tabs;
	}

	public static function createTab(TabType $tabType, int $viewerId, int $ownerId): BaseProfileTab
	{
		return match ($tabType)
		{
			TabType::TASKS => new TasksTab($viewerId, $ownerId),
			TabType::FILES => new FilesTab($viewerId, $ownerId),
			TabType::CALENDAR => new CalendarTab($viewerId, $ownerId),
			TabType::COMMON => new CommonTab($viewerId, $ownerId),
			TabType::LIVE_FEED => new LiveFeedTab($viewerId, $ownerId),
			TabType::GROUPS => new GroupsTab($viewerId, $ownerId),
			TabType::DOCUMENTS => new DocumentsTab($viewerId, $ownerId),
		};
	}

}