<?php

namespace Bitrix\Tasks\Scrum\Utility;

use Bitrix\Main\Config\Option;

class ViewHelper
{
	public function saveActiveView(?string $view, int $groupId): void
	{
		if ($view == 'plan')
		{
			Option::set('tasks.scrum.' . $groupId, 'active_tab', 'plan');
		}

		if ($view == 'active_sprint')
		{
			Option::set('tasks.scrum.' . $groupId, 'active_tab', 'active_sprint');
		}

		if ($view == 'completed_sprint')
		{
			Option::set('tasks.scrum.' . $groupId, 'active_tab', 'completed_sprint');
		}
	}

	public function getActiveView($groupId): string
	{
		return Option::get('tasks.scrum.' . $groupId, 'active_tab', 'plan');
	}
}