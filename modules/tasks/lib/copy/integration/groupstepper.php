<?php
namespace Bitrix\Tasks\Copy\Integration;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Update\Stepper;
use Bitrix\Tasks\Copy\TaskManager;
use Bitrix\Tasks\Internals\Task\ProjectDependenceTable;

class GroupStepper extends Stepper
{
	protected static $moduleId = "tasks";

	protected $queueName = "TasksGroupQueue";
	protected $checkerName = "TasksGroupChecker_";
	protected $baseName = "TasksGroupStepper_";
	protected $errorName = "TasksGroupError_";

	/**
	 * Executes some action, and if return value is false, agent will be deleted.
	 * @param array $option Array with main data to show if it is necessary
	 * like {steps : 35, count : 7}, where steps is an amount of iterations, count - current position.
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function execute(array &$option)
	{
		if (!Loader::includeModule(self::$moduleId))
		{
			return false;
		}

		try
		{
			$queue = $this->getQueue();
			$this->setQueue($queue);

			$queueOption = $this->getOptionData($this->baseName);
			if (empty($queueOption))
			{
				$this->deleteQueueOption();
				return !$this->isQueueEmpty();
			}

			$executiveUserId = ($queueOption["executiveUserId"] ?: 0);
			$groupId = ($queueOption["groupId"] ?: 0);
			$copiedGroupId = ($queueOption["copiedGroupId"] ?: 0);

			$limit = 3;
			$offset = $this->getOffset($executiveUserId, $copiedGroupId);

			$tasksIds = $this->getTasksIdsByGroupId($executiveUserId, $groupId);
			$count = count($tasksIds);
			$tasksIds = array_slice($tasksIds, $offset, $limit);

			$mapIdsCopiedStages = ($queueOption["mapIdsCopiedStages"] ?: []);
			$mapIdsCopiedTasks = ($queueOption["mapIdsCopiedTasks"] ?: []);
			$features = ($queueOption["features"] ?: []);
			$projectTerm = $queueOption["projectTerm"] ?: [];

			if ($tasksIds)
			{
				$option["count"] = $count;

				$taskCopyManager = new TaskManager($executiveUserId, $tasksIds);
				$taskCopyManager->setTargetGroup($copiedGroupId);
				$taskCopyManager->setMapIdsCopiedStages($mapIdsCopiedStages);
				$taskCopyManager->setProjectTerm($projectTerm);

				if (!in_array("checklists", $features))
				{
					$taskCopyManager->markChecklist(false);
				}
				if (!in_array("comments", $features))
				{
					$taskCopyManager->markComment(false);
				}

				$taskCopyManager->startCopy();

				$mapIdsCopiedTasks = $mapIdsCopiedTasks + $taskCopyManager->getMapIdsCopiedTasks();
				$queueOption["mapIdsCopiedTasks"] = $mapIdsCopiedTasks;
				$this->saveQueueOption($queueOption);

				$option["steps"] = $offset;

				return true;
			}
			else
			{
				$this->afterCopy($queueOption);
				$this->deleteCurrentQueue($queue);
				$this->deleteQueueOption();
				return !$this->isQueueEmpty();
			}
		}
		catch (\Exception $exception)
		{
			$this->writeToLog($exception);
			$this->deleteQueueOption();
			return false;
		}
	}

	private function afterCopy(array $queueOption)
	{
		$this->saveErrorOption($queueOption);
		$this->addGanttDependencies($queueOption);
	}

	private function addGanttDependencies(array $queueOption)
	{
		$groupId = ($queueOption["groupId"] ?: 0);
		$mapIdsCopiedTasks = $queueOption["mapIdsCopiedTasks"] ?: [];
		$executiveUserId = ($queueOption["executiveUserId"] ?: 0);
		if ($groupId && $mapIdsCopiedTasks)
		{
			$queryObject = ProjectDependenceTable::getListByLegacyTaskFilter(
				["GROUP_ID" => $groupId, "CHECK_PERMISSIONS" => "N", "ZOMBIE" => "N"]);
			while ($dependence = $queryObject->fetch())
			{
				if (array_key_exists($dependence["TASK_ID"], $mapIdsCopiedTasks)
					&& array_key_exists($dependence["DEPENDS_ON_ID"], $mapIdsCopiedTasks))
				{
					$taskIdTo = $mapIdsCopiedTasks[$dependence["TASK_ID"]];
					$taskIdFrom = $mapIdsCopiedTasks[$dependence["DEPENDS_ON_ID"]];
					try
					{
						$task = new \CTaskItem($taskIdTo, $executiveUserId);
						$task->addProjectDependence($taskIdFrom, $dependence["TYPE"]);
					}
					catch (\Exception $exception) {}
				}
			}
		}
	}

	private function getTasksIdsByGroupId($userId, $groupId)
	{
		try
		{
			$tasksIds = [];
			list($tasks, $res) = \CTaskItem::fetchList($userId, [], ["GROUP_ID" => $groupId], [], ["ID", "PARENT_ID"]);
			foreach ($tasks as $task)
			{
				/** @var \CTaskItem $task */
				$taskData = $task->getData(false);
				$tasksIds[$taskData["ID"]] = ($taskData["PARENT_ID"] ? $taskData["PARENT_ID"] : "");
			}

			$keyIds = [];
			foreach ($tasksIds as $key => $val)
			{
				if (array_key_exists($val, $tasksIds))
				{
					$keyIds[$key] = $key;
				}
			}

			$tasksIds = array_keys(array_diff_key($tasksIds, $keyIds));

			return $tasksIds;
		}
		catch (\Exception $exception)
		{
			return [];
		}
	}

	private function getOffset(int $executiveUserId, int $copiedGroupId): int
	{
		$tasksIds = $this->getTasksIdsByGroupId($executiveUserId, $copiedGroupId);
		return count($tasksIds);
	}

	private function saveErrorOption(array $queueOption)
	{
		$mapIdsCopiedTasks = $queueOption["mapIdsCopiedTasks"] ?: [];

		$mapIdsWithErrors = [];
		foreach ($mapIdsCopiedTasks as $taskId => $copiedTaskId)
		{
			if (!$copiedTaskId)
			{
				$mapIdsWithErrors[] = $taskId;
			}
		}

		if ($mapIdsWithErrors)
		{
			Option::set(self::$moduleId, $this->errorName, serialize($mapIdsWithErrors));
		}
	}
}