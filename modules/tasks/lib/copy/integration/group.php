<?php
namespace Bitrix\Tasks\Copy\Integration;

use Bitrix\Main\Config\Option;
use Bitrix\Socialnetwork\Copy\Integration\Feature;
use Bitrix\Tasks\Copy\TaskManager;
use Bitrix\Tasks\Util;

class Group implements Feature
{
	private $executiveUserId;
	private $features = [];

	const MODULE_ID = "tasks";
	const QUEUE_OPTION = "TasksGroupQueue";
	const CHECKER_OPTION = "TasksGroupChecker_";
	const STEPPER_OPTION = "TasksGroupStepper_";
	const STEPPER_CLASS = GroupStepper::class;
	const ERROR_OPTION = "TasksGroupError_";

	private $projectTerm = [];

	public function __construct($executiveUserId = 0, array $features = [])
	{
		$this->executiveUserId = Util\User::getAdminId();
		$this->features = $features;
	}

	/**
	 * Setting the start date of a project to update dates in tasks.
	 *
	 * @param array $projectTerm ["project" => true, "start_point" => "", "end_point" => ""].
	 */
	public function setProjectTerm($projectTerm)
	{
		$this->projectTerm = $projectTerm;
	}

	public function copy($groupId, $copiedGroupId)
	{
		$taskCopyManager = new TaskManager($this->executiveUserId, []);
		$mapIdsCopiedStages = $taskCopyManager->copyKanbanStages($groupId, $copiedGroupId);
		if (in_array("robots", $this->features))
		{
			$taskCopyManager->copyGroupRobots($groupId, $copiedGroupId);
		}

		$tasksIds = $this->getTasksIdsByGroupId($this->executiveUserId, $groupId);
		if (!$tasksIds)
		{
			return;
		}

		$this->addToQueue($copiedGroupId);

		Option::set(self::MODULE_ID, self::CHECKER_OPTION.$copiedGroupId, "Y");

		$dataToCopy = [
			"executiveUserId" => $this->executiveUserId,
			"groupId" => $groupId,
			"copiedGroupId" => $copiedGroupId,
			"features" => $this->features,
			"mapIdsCopiedStages" => $mapIdsCopiedStages,
			"projectTerm" => $this->projectTerm
		];
		Option::set(self::MODULE_ID, self::STEPPER_OPTION.$copiedGroupId, serialize($dataToCopy));

		$agent = \CAgent::getList([], [
			"MODULE_ID" => self::MODULE_ID,
			"NAME" => GroupStepper::class."::execAgent();"
		])->fetch();
		if (!$agent)
		{
			GroupStepper::bind(1);
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

	private function addToQueue(int $copiedGroupId)
	{
		$option = Option::get(self::MODULE_ID, self::QUEUE_OPTION, "");
		$option = ($option !== "" ? unserialize($option, ['allowed_classes' => false]) : []);
		$option = (is_array($option) ? $option : []);

		$option[] = $copiedGroupId;
		Option::set(self::MODULE_ID, self::QUEUE_OPTION, serialize($option));
	}
}