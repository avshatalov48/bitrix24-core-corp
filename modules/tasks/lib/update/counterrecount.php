<?php
namespace Bitrix\Tasks\Update;

use Bitrix\Main;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Update\Stepper;
use Bitrix\Tasks\Internals\Counter;
use Bitrix\Tasks\Util\Collection;

/**
 * Class CounterRecount
 *
 * @package Bitrix\Tasks\Update
 */
class CounterRecount extends Stepper
{
	protected static $moduleId = "tasks";

	/**
	 * @param array $result
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\Db\SqlQueryException
	 * @throws Main\LoaderException
	 * @throws Main\SystemException
	 */
	public function execute(array &$result): bool
	{
		if (
			!Loader::includeModule("tasks")
			|| Option::get("tasks", "needCounterRecount", "Y") !== "Y"
		)
		{
			return false;
		}

		$found = false;
		$parameters = static::getParameters();
		$countersToRecount = static::getCountersToRecount();

		if ($parameters["count"] > 0)
		{
			$result["count"] = $parameters["count"];

			$res = Application::getConnection()->query("
				SELECT DISTINCT USER_ID
				FROM b_tasks_member
				WHERE USER_ID > {$parameters['last_id']}
				ORDER BY USER_ID
				LIMIT 25
			");
			while ($user = $res->fetch())
			{
				$userId = $user["USER_ID"];

				$counter = Counter::getInstance($userId);
				static::recount($counter, $countersToRecount);

				$parameters["last_id"] = $userId;
				$found = true;
			}

			if ($found)
			{
				Option::set("tasks", "counterRecount", serialize($parameters));
			}
		}

		if ($found === false)
		{
			Option::delete("tasks", ["name" => "counterRecount"]);
			Option::delete("tasks", ["name" => "countersToRecount"]);
			Option::set("tasks", "needCounterRecount", "N");
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
		$parameters = Option::get("tasks", "counterRecount", "");
		$parameters = ($parameters !== "" ? unserialize($parameters, ["allowed_classes" => false]) : []);
		$parameters = (is_array($parameters) ? $parameters : []);

		if (empty($parameters))
		{
			$res = Application::getConnection()->query("
				SELECT COUNT(DISTINCT USER_ID) AS CNT
				FROM b_tasks_member
			")->fetch();

			$parameters = [
				"last_id" => 0,
				"count" => (int)$res["CNT"],
			];
		}

		return $parameters;
	}

	/**
	 * @return array
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 */
	private static function getCountersToRecount(): array
	{
		$counters = Option::get("tasks", "countersToRecount", "");
		$counters = ($counters !== "" ? unserialize($counters, ["allowed_classes" => false]) : []);
		$counters = (is_array($counters) ? $counters : []);

		if (empty($counters))
		{
			$counters = Counter::getDefaultCountersToRecount();
		}

		return $counters;
	}

	/**
	 * @param Counter $counter
	 * @param array $countersToRecount
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private static function recount(Counter $counter, array $countersToRecount): void
	{
		if (in_array(Counter\Name::EXPIRED, $countersToRecount, true))
		{
			$counter->processRecalculate(
				new Collection([
					Counter\Name::EXPIRED,
					Counter\Name::MY_EXPIRED,
					Counter\Name::ORIGINATOR_EXPIRED,
					Counter\Name::ACCOMPLICES_EXPIRED,
					Counter\Name::AUDITOR_EXPIRED,
				])
			);
		}
		if (in_array(Counter\Name::NEW_COMMENTS, $countersToRecount, true))
		{
			$counter->processRecalculate(
				new Collection([
					Counter\Name::NEW_COMMENTS,
					Counter\Name::MY_NEW_COMMENTS,
					Counter\Name::ORIGINATOR_NEW_COMMENTS,
					Counter\Name::ACCOMPLICES_NEW_COMMENTS,
					Counter\Name::AUDITOR_NEW_COMMENTS,
				])
			);
		}
	}
}