<?php

/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */
namespace Bitrix\Tasks\Control;

use Bitrix\Tasks\Internals\Task\SortingTable;

class Grid
{
	public function __construct(private int $userId)
	{
	}

	public function sortTask($data)
	{
		$sourceId = isset($data["sourceId"]) ? intval($data["sourceId"]) : 0;
		$targetId = isset($data["targetId"]) ? intval($data["targetId"]) : 0;
		$before = isset($data["before"]) && (int) $data['before'];
		$newGroupId = isset($data["newGroupId"]) ? intval($data["newGroupId"]) : null;
		$newParentId = isset($data["newParentId"]) ? intval($data["newParentId"]) : null;
		$currentGroupId = isset($data["currentGroupId"]) ? intval($data["currentGroupId"]) : 0;

		if ($sourceId === $targetId || $sourceId < 1)
		{
			return [];
		}

		try
		{
			$sourceTask = new \CTaskItem($sourceId, $this->userId);
		}
		catch (\CTaskAssertException $e)
		{
			return false;
		}

		/*
		GROUP_ID and PARENT_ID could be changed after drag&drop manipulations.
		Target task is not required. Example: We want to move Task 1 after Project. In this case a target task is undefined.
			Task 1
			Project (without tasks)
		*/
		$newTaskData = [];
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
			return true;
		}

		SortingTable::setSorting($this->userId, $currentGroupId, $sourceId, $targetId, $before);
		return true;
	}
}