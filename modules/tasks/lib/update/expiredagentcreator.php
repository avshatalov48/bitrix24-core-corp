<?php
namespace Bitrix\Tasks\Update;

use Bitrix\Main;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Update\Stepper;
use Bitrix\Tasks\Internals\Counter\Agent;
use Bitrix\Tasks\Util\Type\DateTime;

/**
 * Class ExpiredAgentCreator
 *
 * @package Bitrix\Tasks\Update
 */
class ExpiredAgentCreator extends Stepper
{
	protected static $moduleId = 'tasks';

	/**
	 * @param array $result
	 * @return bool
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\Db\SqlQueryException
	 * @throws Main\LoaderException
	 */
	public function execute(array &$result): bool
	{
		if (!Loader::includeModule("tasks"))
		{
			return false;
		}

		$found = false;
		$parameters = static::getParameters();

		static::clearOldAgents($parameters);

		if ($parameters["count"] > 0)
		{
			$result["count"] = $parameters["count"];

			$res = Application::getConnection()->query("
				SELECT ID, DEADLINE
				FROM b_tasks 
				WHERE 
					STATUS < 4
					AND DEADLINE IS NOT NULL
					AND DEADLINE > NOW()
					AND ID > {$parameters['last_id']}
				ORDER BY ID
				LIMIT 100
			");
			while ($task = $res->fetch())
			{
				$taskId = $task['ID'];

				if ($deadline = DateTime::createFromInstance($task['DEADLINE']))
				{
					Agent::add($taskId, $deadline);
				}

				$parameters["last_id"] = $taskId;
				$found = true;
			}

			if ($found)
			{
				Option::set("tasks", "expiredAgentCreator", serialize($parameters));
			}
		}

		if ($found === false)
		{
			Option::delete("tasks", ["name" => "expiredAgentCreator"]);
		}

		return $found;
	}

	/**
	 * @return array|int[]
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\Db\SqlQueryException
	 */
	private static function getParameters(): array
	{
		$parameters = Option::get("tasks", "expiredAgentCreator", "");
		$parameters = ($parameters !== "" ? @unserialize($parameters, ['allowed_classes' => false]) : []);
		$parameters = (is_array($parameters) ? $parameters : []);

		if (empty($parameters))
		{
			$res = Application::getConnection()->query("
				SELECT COUNT(ID) AS CNT
				FROM b_tasks
				WHERE
					STATUS < 4
				  	AND DEADLINE IS NOT NULL
					AND DEADLINE > NOW()
			")->fetch();

			$parameters = [
				"last_id" => 0,
				"count" => (int)$res['CNT'],
			];
		}

		return $parameters;
	}

	/**
	 * @param array $parameters
	 * @throws Main\Db\SqlQueryException
	 */
	private static function clearOldAgents(array $parameters): void
	{
		if ((int)$parameters["last_id"] === 0)
		{
			Application::getConnection()->query("
				DELETE FROM b_agent
				WHERE MODULE_ID = 'tasks' AND NAME LIKE '%Agent::expired%'
			");
		}
	}
}