<?php

namespace Bitrix\Tasks\Slider\Path;

use Bitrix\Tasks\Internals\Routes\RouteDictionary;

class TaskTagsPathMaker extends PathMaker
{
	public function makeEntityPath(): string
	{
		$path = str_replace(
			['#user_id#'],
			[$this->ownerId],
			RouteDictionary::PATH_TO_USER_TAGS
		);

		return $path . $this->queryParams;
	}

	public function makeEntitiesListPath(): string
	{
		return str_replace(
			['#user_id#'],
			[$this->ownerId],
			RouteDictionary::PATH_TO_USER_TASKS_LIST
		);
	}
}