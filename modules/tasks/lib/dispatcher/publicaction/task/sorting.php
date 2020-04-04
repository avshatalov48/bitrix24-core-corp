<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 *
 * @access private
 *
 * Each method you put here you`ll be able to call as ENTITY_NAME.METHOD_NAME via AJAX and\or REST, so be careful.
 */

namespace Bitrix\Tasks\Dispatcher\PublicAction\Task;

use Bitrix\Main\Loader;
use Bitrix\Tasks;
use Bitrix\Tasks\Dispatcher\RestrictedAction;
use Bitrix\Tasks\Internals\Task\SortingTable;
use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Integration\SocialNetwork;

Loc::loadMessages(__FILE__);

final class Sorting extends RestrictedAction
{
	/**
	 *
	 * Moves the source task before/after the target task;
	 * @param array $data
	 * @return false|array
	 */
	public function move($data)
	{
		if (!Tasks\Util\User::isAuthorized())
		{
			$this->errors->add("AUTH_REQUIRED", Loc::getMessage("TASKS_SORTING_AUTH_REQUIRED"));
			return false;
		}

		$sourceId = isset($data["sourceId"]) ? intval($data["sourceId"]) : 0;
		$targetId = isset($data["targetId"]) ? intval($data["targetId"]) : 0;
		$before = isset($data["before"]) && ($data["before"] === true || $data["before"] === "1");
		$newGroupId = isset($data["newGroupId"]) ? intval($data["newGroupId"]) : null;
		$newParentId = isset($data["newParentId"]) ? intval($data["newParentId"]) : null;
		$currentGroupId = isset($data["currentGroupId"]) ? intval($data["currentGroupId"]) : 0;
		$userId = Tasks\Util\User::getId();

		if ($sourceId === $targetId || $sourceId < 1)
		{
			return array();
		}

		$sourceTask = new \CTaskItem($sourceId, $userId);
		if (!$sourceTask->checkCanRead())
		{
			$this->errors->add("SOURCE_TASK_NOT_FOUND", Loc::getMessage("TASKS_SORTING_WRONG_SOURCE_TASK"));
			return false;
		}

		if ($currentGroupId && Loader::includeModule("socialnetwork"))
		{
			$group = \CSocNetGroup::getByID($currentGroupId);
			$canSort = SocialNetwork\Group::can($currentGroupId, SocialNetwork\Group::ACTION_SORT_TASKS);
			if (!$group || !$canSort)
			{
				$this->errors->add("GROUP_PERMS_NOT_FOUND", Loc::getMessage("TASKS_SORTING_WRONG_GROUP_PERMISSIONS"));
				return false;
			}
		}

		/*
		GROUP_ID and PARENT_ID could be changed after drag&drop manipulations.
		Target task is not required. Example: We want to move Task 1 after Project. In this case a target task is undefined.
			Task 1
			Project (without tasks)
		*/
		$newTaskData = array();
		if ($newGroupId !== null)
		{
			$newTaskData["GROUP_ID"] = $newGroupId;
		}

		if ($newParentId !== null)
		{
			$newTaskData["PARENT_ID"] = $newParentId;
		}

		if (count($newTaskData))
		{
			$sourceTask->update($newTaskData);
		}

		//But it's required for sorting
		if ($targetId < 1)
		{
			return array();
		}

		$targetTask = new \CTaskItem($targetId, $userId);
		if (!$targetTask->checkCanRead())
		{
			$this->errors->add("TARGET_TASK_NOT_FOUND", Loc::getMessage("TASKS_SORTING_WRONG_TARGET_TASK"));
			return false;
		}

		SortingTable::setSorting($userId, $currentGroupId, $sourceId, $targetId, $before);
		return array();
	}
}

