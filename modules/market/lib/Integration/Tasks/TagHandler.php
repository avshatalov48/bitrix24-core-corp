<?php

namespace Bitrix\Market\Integration\Tasks;

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Market\Integration\TagHandlerInterface;

/**
 * class TagHandler
 *
 * @package Bitrix\Market\Integration\Tasks
 */
class TagHandler implements TagHandlerInterface
{
	private const MODULE_ID = 'tasks';
	private const TAG_TASKS = 'tasks_count';

	private static function getCountTasksTag(): array
	{
		$result = [];
		if (Loader::includeModule(static::MODULE_ID))
		{
			$sql = "select COUNT(ID) as TASKS_COUNT from b_tasks";
			$connection = Application::getConnection();
			$query = $connection->query($sql);
			if ($item = $query->fetch())
			{
				$result = [
					'MODULE_ID' => static::MODULE_ID,
					'CODE' => static::TAG_TASKS,
					'VALUE' => $item['TASKS_COUNT'],
				];
			}
		}

		return $result;
	}

	/**
	 * Return all tasks tags for.
	 *
	 * @return array
	 */
	public static function list(): array
	{
		return [
			static::getCountTasksTag(),
		];
	}
}