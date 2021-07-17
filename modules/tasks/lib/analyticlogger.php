<?php
namespace Bitrix\Tasks;

/**
 * Class AnalyticLogger
 *
 * @package Bitrix\Tasks
 */
class AnalyticLogger
{
	/**
	 * Logs data for analytic to file.
	 *
	 * @param string $action
	 * @param string $tag
	 * @param string $label
	 * @param string $actionType
	 * @param int|null $userId
	 */
	public static function logToFile(
		string $action,
		string $tag = '',
		string $label = '',
		string $actionType = '',
		?int $userId = null
	): void
	{
		if (function_exists('AddEventToStatFile'))
		{
			AddEventToStatFile('tasks', $action, $tag, $label, $actionType, $userId);
		}
	}
}