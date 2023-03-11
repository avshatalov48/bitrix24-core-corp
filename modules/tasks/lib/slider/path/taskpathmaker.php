<?php

namespace Bitrix\Tasks\Slider\Path;

use Bitrix\Tasks\Internals\Routes\RouteDictionary;

class TaskPathMaker extends PathMaker
{
	public function makeEntityPath(): string
	{
		$replace = [$this->ownerId, $this->action, $this->entityId];

		switch ($this->context)
		{
			case PathMaker::GROUP_CONTEXT:
				$search = ['#group_id#', '#action#', '#task_id#'];
				$subject = RouteDictionary::PATH_TO_GROUP_TASK;
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

			case PathMaker::PERSONAL_CONTEXT:
			default:
				$search = ['#user_id#'];
				$subject = RouteDictionary::PATH_TO_USER_TASKS_LIST;
				break;
		}

		return str_replace($search, $replace, $subject);
	}
}