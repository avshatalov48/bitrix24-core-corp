<?php
namespace Bitrix\Tasks\Update;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Update\Stepper;

use Bitrix\Tasks\Internals\Counter\EffectiveTable;
use Bitrix\Tasks\Internals\Effective;

Loc::loadMessages(__FILE__);

/**
 * Class EfficiencyRecount
 *
 * @package Bitrix\Tasks\Update
 */
final class EfficiencyRecount extends Stepper
{
	protected static $moduleId = 'tasks';

	public function execute(array &$result)
	{
		if (!(
			Loader::includeModule("tasks") &&
			Option::get("tasks", "needEfficiencyRecount", "Y") == 'Y'
		))
		{
			return false;
		}

		global $DB;

		$return = false;

		$params = Option::get("tasks", "efficiencyrecount", "");
		$params = ($params !== ""? @unserialize($params, ['allowed_classes' => false]) : []);
		$params = (is_array($params)? $params : []);

		if (empty($params))
		{
			$res = $DB->Query("
				SELECT COUNT(U.USER_ID) AS CNT
				FROM (SELECT USER_ID FROM b_tasks_effective GROUP BY USER_ID) U
			")->Fetch();

			$params = [
				"number" => 0,
				"last_id" => 0,
				"count" => (int)$res['CNT'],
			];
		}

		$found = false;

		if ($params["count"] > 0)
		{
			$result["progress"] = 1;
			$result["steps"] = "";
			$result["count"] = $params["count"];

			$time = time();

			$res = EffectiveTable::getList([
				'select' => ['USER_ID'],
				'filter' => ['>USER_ID' => $params["last_id"]],
				'group' => ['USER_ID'],
				'order' => ['USER_ID'],
				'offset' => 0,
				'limit' => 100
			]);

			while ($user = $res->fetch())
			{
				$userId = $user['USER_ID'];

				Effective::recountEfficiencyUserCounter($userId);

				$params["number"]++;
				$params["last_id"] = $userId;

				$found = true;

				if (time() - $time > 3)
				{
					break;
				}
			}

			if ($found)
			{
				Option::set("tasks", "efficiencyrecount", serialize($params));
				$return = true;
			}

			$result["progress"] = intval($params["number"] * 100 / $params["count"]);
			$result["steps"] = $params["number"];
		}

		if ($found === false)
		{
			Option::delete("tasks", ["name" => "efficiencyrecount"]);
			Option::set("tasks", "needEfficiencyRecount", "N");

			Effective::createAgentForNextEfficiencyRecount();
		}

		return $return;
	}
}