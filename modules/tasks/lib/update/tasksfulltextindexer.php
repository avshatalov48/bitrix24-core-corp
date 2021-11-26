<?php
namespace Bitrix\Tasks\Update;

use Bitrix\Main\Entity\Query;
use Bitrix\Main\Update\Stepper;
use Bitrix\Main\Localization\Loc;
use Bitrix\Blog\Integration;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Tasks\Internals\TaskTable;

Loc::loadMessages(__FILE__);

final class TasksFulltextIndexer extends Stepper
{
	protected static $moduleId = "tasks";
	private $countAtHit = 100;

	public function execute(array &$result)
	{
		global $DB;

		if (!Loader::includeModule("tasks")
			|| Option::get('tasks', 'tasksNeedIndex', 'Y') == 'N'
		)
		{
			return false;
		}
		$return = false;

		$params = Option::get("tasks", "tasksindextask", "");
		$params = ($params !== "" ? @unserialize($params, ['allowed_classes' => false]) : array());
		$params = (is_array($params) ? $params : array());
		if (empty($params))
		{
			$filter = Query::filter();
			$filter->whereNull('SEARCH_INDEX');

			$params = array(
				"lastId" => 0,
				"number" => 0,
				"count" => TaskTable::getCount($filter)
			);
		}

		$found = false;

		if ($params["count"] > 0)
		{
			$res = $DB->Query("SELECT ID FROM b_tasks WHERE SEARCH_INDEX IS NULL AND ID > ".(int)$params["lastId"]." LIMIT {$this->countAtHit}");
			while($t = $res->Fetch())
			{
				$taskId = (int)$t['ID'];
				$task = new \Bitrix\Tasks\Item\Task($taskId);

				$controllerDefault = $task->getAccessController();
				$controller = $controllerDefault->spawn();
				$controller->disable();
				$task->setAccessController($controller);

				$index = $DB->ForSql(\Bitrix\Tasks\Manager\Task::prepareSearchIndex($task->getData()));
				$sql = "UPDATE b_tasks SET SEARCH_INDEX = '{$index}' WHERE ID = {$taskId}";
				$DB->Query($sql);

				$params["lastId"] = $t['ID'];
				$params["number"]++;
				$found = true;
			}

			$result["steps"] = $params["number"];
			$result["count"] = $params["count"];

			if ($found)
			{
				Option::set("tasks", "tasksindextask", serialize($params));
				$return = true;
			}
		}

		if (!$found)
		{
			Option::delete("tasks", array("name" => "tasksindextask"));
			Option::set('tasks', 'tasksNeedIndex', 'N');
		}

		return $return;
	}
}
?>