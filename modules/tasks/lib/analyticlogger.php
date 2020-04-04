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
	 */
	public static function logToFile($action, $tag = '', $label = '')
	{
		if (function_exists('AddEventToStatFile'))
		{
			AddEventToStatFile('tasks', $action, $tag, $label);
		}
	}
}