<?php

namespace Bitrix\Tasks\Slider\Path;

use Bitrix\Tasks\Internals\Routes\RouteDictionary;

class TaskPathMaker extends PathMaker
{
	public static function getPath(array $fields): string
	{
		$groupId = (int)($fields['group_id'] ?? null);
		$userId = (int)($fields['user_id'] ?? null);
		$entityId = (int)($fields['task_id'] ?? null);
		$action = in_array($fields['action'] ?? null, PathMaker::$allowedActions, true) ? $fields['action'] : PathMaker::DEFAULT_ACTION;
		$ownerId = $groupId > 0 ? $groupId : $userId;
		$context = $groupId > 0 ? PathMaker::GROUP_CONTEXT : PathMaker::PERSONAL_CONTEXT;

		return (new TaskPathMaker($entityId, $action, $ownerId, $context))->makeEntityPath();
	}

	public function makeEntityPath(): string
	{
		$replace = [$this->ownerId, $this->action, $this->entityId];

		switch ($this->context)
		{
			case PathMaker::GROUP_CONTEXT:
				$search = ['#group_id#', '#action#', '#task_id#'];
				$subject = RouteDictionary::PATH_TO_GROUP_TASK;
				break;

			case PathMaker::SPACE_CONTEXT:
				$search = ['#group_id#', '#action#', '#task_id#'];
				$subject = RouteDictionary::PATH_TO_SPACE_TASK;
				break;

			case PathMaker::PERSONAL_CONTEXT:
			default:
				$search = ['#user_id#', '#action#', '#task_id#'];
				$subject = RouteDictionary::PATH_TO_USER_TASK;
				break;
		}
		$path = str_replace($search, $replace, $subject);

		return $path . $this->queryParams;
	}

	public function makeEntitiesListPath(): string
	{
		$replace = [$this->ownerId];

		switch ($this->context)
		{
			case PathMaker::GROUP_CONTEXT:
				$search = ['#group_id#'];
				$subject = RouteDictionary::PATH_TO_GROUP_TASKS_LIST;
				break;

			case PathMaker::SPACE_CONTEXT:
				$search = ['#group_id#'];
				$subject = RouteDictionary::PATH_TO_SPACE_TASKS_LIST;
				break;

			case PathMaker::PERSONAL_CONTEXT:
			default:
				$search = ['#user_id#'];
				$subject = RouteDictionary::PATH_TO_USER_TASKS_LIST;
				break;
		}

		return str_replace($search, $replace, $subject);
	}
}