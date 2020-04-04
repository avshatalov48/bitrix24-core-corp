<?php
namespace Bitrix\Tasks\Copy\Integration;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Socialnetwork\Copy\Integration\Feature;
use Bitrix\Socialnetwork\Copy\Integration\Helper;
use Bitrix\Tasks\Copy\TaskManager;

class Group implements Feature, Helper
{
	private $stepper;

	private $executiveUserId;
	private $features = [];

	private $moduleId = "tasks";
	private $queueOption = "TasksGroupQueue";
	private $checkerOption = "TasksGroupChecker_";
	private $stepperOption = "TasksGroupStepper_";
	private $errorOption = "TasksGroupError_";

	private $projectTerm = [];

	public function __construct($executiveUserId = 0, array $features = [])
	{
		$this->executiveUserId = $executiveUserId;
		$this->features = $features;

		$this->stepper = GroupStepper::class;
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
		$tasksIds = $this->getTasksIdsByGroupId($this->executiveUserId, $groupId);
		if (!$tasksIds)
		{
			return;
		}

		$this->addToQueue($copiedGroupId);

		Option::set($this->moduleId, $this->checkerOption.$copiedGroupId, "Y");

		$taskCopyManager = new TaskManager($this->executiveUserId, []);
		$mapIdsCopiedStages = $taskCopyManager->copyKanbanStages($groupId, $copiedGroupId);
		if (in_array("robots", $this->features))
		{
			$taskCopyManager->copyGroupRobots($groupId, $copiedGroupId);
		}

		$dataToCopy = [
			"executiveUserId" => $this->executiveUserId,
			"groupId" => $groupId,
			"copiedGroupId" => $copiedGroupId,
			"features" => $this->features,
			"mapIdsCopiedStages" => $mapIdsCopiedStages,
			"projectTerm" => $this->projectTerm
		];
		Option::set($this->moduleId, $this->stepperOption.$copiedGroupId, serialize($dataToCopy));

		$agent = \CAgent::getList([], [
			"MODULE_ID" => $this->moduleId,
			"NAME" => $this->stepper."::execAgent();"
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
		$option = Option::get($this->moduleId, $this->queueOption, "");
		$option = ($option !== "" ? unserialize($option) : []);
		$option = (is_array($option) ? $option : []);

		$option[] = $copiedGroupId;
		Option::set($this->moduleId, $this->queueOption, serialize($option));
	}

	/**
	 * Returns a module id for work with options.
	 * @return string
	 */
	public function getModuleId()
	{
		return $this->moduleId;
	}

	/**
	 * Returns a map of option names.
	 *
	 * @return array
	 */
	public function getOptionNames()
	{
		return [
			"queue" => $this->queueOption,
			"checker" => $this->checkerOption,
			"stepper" => $this->stepperOption,
			"error" => $this->errorOption
		];
	}

	/**
	 * Returns a link to stepper class.
	 * @return string
	 */
	public function getLinkToStepperClass()
	{
		return $this->stepper;
	}

	/**
	 * Returns a text map.
	 * @return array
	 */
	public function getTextMap()
	{
		return [
			"title" => Loc::getMessage("GROUP_STEPPER_PROGRESS_TITLE"),
			"error" => Loc::getMessage("GROUP_STEPPER_PROGRESS_ERROR")
		];
	}
}