<?php

namespace Bitrix\Crm\Integration\Tasks;

use Bitrix\Main\Loader;
use Bitrix\Tasks\Internals\SearchIndex;

class TaskSearchIndex
{
	public static function getTaskSearchIndex(int $taskId): string
	{
		if (!Loader::includeModule('tasks'))
		{
			return '';
		}

		return SearchIndex::getTaskSearchIndex($taskId);
	}
}