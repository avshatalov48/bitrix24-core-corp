<?php
namespace Bitrix\Tasks\Copy\Integration;

use Bitrix\Tasks\Internals\Log\Log;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Update\Stepper;
use Bitrix\Tasks\Copy\Exception\GroupCopyException;
use Bitrix\Tasks\Copy\TaskManager;
use Bitrix\Tasks\Internals\Task\ProjectDependenceTable;

class GroupStepper extends Stepper
{
	protected static $moduleId = "tasks";

	protected $queueName = "TasksGroupQueue";
	protected $checkerName = "TasksGroupChecker_";
	protected $baseName = "TasksGroupStepper_";
	protected $errorName = "TasksGroupError_";
	private ?Log $logger;

	private const MAX_VALUE_LENGTH = 2**24 - 1;

	public function __construct()
	{
		$this->logger = new Log();
	}
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

			$executiveUserId = ($queueOption["executiveUserId"] ?? 0);
			$groupId = ($queueOption["groupId"] ?? 0);
			$copiedGroupId = ($queueOption["copiedGroupId"] ?? 0);
			$queueOption["errorOffset"] = ($queueOption["errorOffset"] ?? 0);

			$limit = 3;
			$offset = $this->getOffset($executiveUserId, $copiedGroupId) + $queueOption["errorOffset"];

			$tasksIds = $this->getTasksIdsByGroupId($executiveUserId, $groupId);
			$count = count($tasksIds);
			$tasksIds = array_slice($tasksIds, $offset, $limit);

			$mapIdsCopiedStages = ($queueOption["mapIdsCopiedStages"] ?? []);
			$mapIdsCopiedTasks = ($queueOption["mapIdsCopiedTasks"] ?? []);
			$features = ($queueOption["features"] ?? []);
			$projectTerm = $queueOption["projectTerm"] ?? [];

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

				$result = $taskCopyManager->startCopy();
				if (!$result->isSuccess())
				{
					$queueOption["errorOffset"] += $this->getErrorOffset($taskCopyManager);
					$queueOption["errorOffset"] += $this->getErrorOffset($taskCopyManager);
				}

				$mapIdsCopiedTasks = $taskCopyManager->getMapIdsCopiedTasks() + $mapIdsCopiedTasks;
				$queueOption["mapIdsCopiedTasks"] = $mapIdsCopiedTasks;
				$this->saveQueueOption($queueOption);
				$option["steps"] = $offset;

				return true;
			}
			else
			{
				$this->onAfterCopy($queueOption);
				$this->deleteQueueOption();
				return !$this->isQueueEmpty();
			}
		}
		catch (\Exception $exception)
		{
			$this->logger->collect("Error while copying a group. Reason: {$exception->getMessage()}");
			$this->deleteQueueOption();
			return false;
		}
	}

	private function onAfterCopy(array $queueOption)
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
				["GROUP_ID" => $groupId, "CHECK_PERMISSIONS" => "N"]);
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
					catch (\Throwable $exception) {}
				}
			}
		}
	}

	private function getTasksIdsByGroupId($userId, $groupId)
	{
		try
		{
			$tasksIds = [];
			[$tasks, $res] = \CTaskItem::fetchList($userId, [], ["GROUP_ID" => $groupId], [], ["ID", "PARENT_ID"]);
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

	private function getErrorOffset(TaskManager $taskCopyManager): int
	{
		$numberIds = count($taskCopyManager->getMapIdsCopiedTasks());
		$numberSuccessIds = count(array_filter($taskCopyManager->getMapIdsCopiedTasks()));
		return $numberIds - $numberSuccessIds;
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

	protected function getQueue(): array
	{
		return $this->getOptionData($this->queueName);
	}

	protected function setQueue(array $queue): void
	{
		$queueId = (string) current($queue);
		$this->checkerName = (mb_strpos($this->checkerName, $queueId) === false ?
			$this->checkerName.$queueId : $this->checkerName);
		$this->baseName = (mb_strpos($this->baseName, $queueId) === false ?
			$this->baseName.$queueId : $this->baseName);
		$this->errorName = (mb_strpos($this->errorName, $queueId) === false ?
			$this->errorName.$queueId : $this->errorName);
	}

	protected function getQueueOption()
	{
		return $this->getOptionData($this->baseName);
	}

	/**
	 * @throws ArgumentOutOfRangeException
	 * @throws GroupCopyException
	 */
	protected function saveQueueOption(array $data)
	{
		$data = serialize($data);
		if (strlen($data) > self::MAX_VALUE_LENGTH)
		{
			throw new GroupCopyException('Copied data is too large');
		}
		Option::set(static::$moduleId, $this->baseName, $data);
	}

	protected function deleteQueueOption()
	{
		$queue = $this->getQueue();
		$this->setQueue($queue);
		$this->deleteCurrentQueue($queue);
		$this->deleteStepperOptions();
	}

	protected function deleteCurrentQueue(array $queue): void
	{
		$queueId = current($queue);
		$currentPos = array_search($queueId, $queue);
		if ($currentPos !== false)
		{
			unset($queue[$currentPos]);
			Option::set(static::$moduleId, $this->queueName, serialize($queue));
		}
	}

	protected function isQueueEmpty()
	{
		$queue = $this->getOptionData($this->queueName);
		return empty($queue);
	}

	/**
	 * @throws GroupCopyException
	 */
	protected function getOptionData($optionName): array
	{
		$option = Option::get(static::$moduleId, $optionName);
		$result = [];
		if ($option !== '')
		{
			$result = unserialize($option, ['allowed_classes' => false]);
			if ($result === false)
			{
				throw new GroupCopyException('Can not unserialize group tasks data');
			}
			$result = is_array($result) ? $result: [];
		}

		return $result;
	}

	protected function deleteOption($optionName)
	{
		Option::delete(static::$moduleId, ["name" => $optionName]);
	}

	private function deleteStepperOptions(): void
	{
		Option::delete(static::$moduleId, ['name' => $this->checkerName]);
		Option::delete(static::$moduleId, ['name' => $this->baseName]);
	}
}