<?php

namespace Bitrix\Tasks\Manager\Task;

use Bitrix\Tasks\Manager;

class TaskWebdavFiles extends Manager
{
	public static function getCode($prefix = false): string
	{
		return 'UF_TASK_WEBDAV_FILES';
	}

	public static function mergeData($primary = [], $secondary = [], bool $withAddition = true): array
	{
		$primary = array_filter((array)$primary);
		$secondary = array_filter((array)$secondary);

		if (!$withAddition)
		{
			return $secondary;
		}

		return array_merge($secondary, $primary);
	}
}