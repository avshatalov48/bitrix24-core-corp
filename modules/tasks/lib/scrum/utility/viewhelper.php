<?php

namespace Bitrix\Tasks\Scrum\Utility;

use Bitrix\Main\Config\Option;

class ViewHelper
{
	public const ACTIVE_TAB = 'active_tab';
	public const VIEW_PLAN = 'plan';
	public const VIEW_ACTIVE_SPRINT = 'active_sprint';
	public const VIEW_COMPLETED_SPRINT = 'completed_sprint';

	private bool|string $siteId;

	public function __construct(bool|string $siteId = false)
	{
		$this->siteId = is_string($siteId) ? $siteId : false;
	}

	public function saveActiveView(?string $view, int $groupId): void
	{
		if ($view === self::VIEW_PLAN)
		{
			Option::set('tasks.scrum.' . $groupId, self::ACTIVE_TAB, self::VIEW_PLAN, $this->siteId);
		}

		if ($view === self::VIEW_ACTIVE_SPRINT)
		{
			Option::set('tasks.scrum.' . $groupId, self::ACTIVE_TAB, self::VIEW_ACTIVE_SPRINT, $this->siteId);
		}

		if ($view === self::VIEW_COMPLETED_SPRINT)
		{
			Option::set('tasks.scrum.' . $groupId, self::ACTIVE_TAB, self::VIEW_COMPLETED_SPRINT, $this->siteId);
		}
	}

	public function getActiveView($groupId): string
	{
		return Option::get('tasks.scrum.' . $groupId, self::ACTIVE_TAB, self::VIEW_PLAN, $this->siteId);
	}
}
