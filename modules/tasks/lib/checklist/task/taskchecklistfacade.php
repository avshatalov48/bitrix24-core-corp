<?php
namespace Bitrix\Tasks\CheckList\Task;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Db\SqlQueryException;
use Bitrix\Main\LoaderException;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\CheckList\CheckListFacade;
use Bitrix\Tasks\CheckList\Internals\CheckList;
use Bitrix\Tasks\Internals\SearchIndex;
use Bitrix\Tasks\Internals\Task\CheckList\MemberTable;
use Bitrix\Tasks\Internals\Task\CheckListTable;
use Bitrix\Tasks\Util;
use CTaskAssert;
use CTaskItem;

/**
 * Class TaskCheckListFacade
 *
 * @package Bitrix\Tasks\CheckList\Task
 */
class TaskCheckListFacade extends CheckListFacade
{
	protected static $selectFields = [
		'ID',
		'TASK_ID',
		'CREATED_BY',
		'PARENT_ID',
		'TITLE',
		'SORT_INDEX',
		'IS_COMPLETE',
		'IS_IMPORTANT',
		'TOGGLED_BY',
		'TOGGLED_DATE',
		'MEMBERS',
		'ATTACHMENTS',
	];
	protected static $filterFields = [
		'ID',
		'TASK_ID',
		'CREATED_BY',
		'PARENT_ID',
		'TITLE',
		'SORT_INDEX',
		'IS_COMPLETE',
		'IS_IMPORTANT',
		'TOGGLED_BY',
		'TOGGLED_DATE',
	];
	protected static $orderFields = [
		'ID',
		'TASK_ID',
		'CREATED_BY',
		'PARENT_ID',
		'TITLE',
		'SORT_INDEX',
		'IS_COMPLETE',
		'IS_IMPORTANT',
		'TOGGLED_BY',
		'TOGGLED_DATE',
	];
	protected static $checkListPool;

	public static $entityIdName = 'TASK_ID';
	public static $userFieldsEntityIdName = 'TASKS_TASK_CHECKLIST';
	public static $commonAccessActions;
	public static $itemAccessActions;

	private static $collectedData = [];

	/**
	 * Returns class that extends abstract class CheckListTree
	 * @see CheckListTree
	 *
	 * @return string
	 */
	public static function getCheckListTree()
	{
		return TaskCheckListTree::class;
	}

	/**
	 * Returns table class for checklist table
	 *
	 * @return string
	 */
	public static function getCheckListDataController()
	{
		return CheckListTable::getClass();
	}

	/**
	 * Returns table class for checklist tree table
	 *
	 * @return string
	 */
	public static function getCheckListTreeDataController()
	{
		return TaskCheckListTree::getDataController();
	}

	/**
	 * Returns table class for checklist member table
	 *
	 * @return string
	 */
	public static function getCheckListMemberDataController()
	{
		return MemberTable::getClass();
	}

	/**
	 * Returns checklists with actions for entity if entity is accessible for reading.
	 *
	 * @param int $taskId
	 * @param int $userId
	 * @return array
	 * @throws ArgumentException
	 * @throws NotImplementedException
	 * @throws SystemException
	 */
	public static function getItemsForEntity($taskId, $userId)
	{
		$items = false;
		$task = CTaskItem::getInstanceFromPool($taskId, $userId);

		if ($task !== null && $task->checkCanRead())
		{
			$items = static::getByEntityId($taskId);
			$items = static::fillActionsForItems($taskId, $userId, $items);
		}

		return $items;
	}

	/**
	 * Does some actions after adding checklist.
	 *
	 * @param int $taskId
	 * @param int $userId
	 * @param CheckList $checkList
	 * @throws LoaderException
	 * @throws SqlQueryException
	 */
	public static function doAddPostActions($taskId, $userId, $checkList)
	{
		$checkListLog = new TaskCheckListLog($taskId, $userId, null, $checkList);

		if (static::getDeferredActionsMode())
		{
			static::$collectedData[] = $checkListLog->getActionFields(TaskCheckListLog::ACTION_ADD, [$checkList]);
		}
		else
		{
			$checkListLog->logAddingChanges();
			SearchIndex::setTaskSearchIndex($taskId);
		}
	}

	/**
	 * Does some actions after updating checklist.
	 *
	 * @param int $taskId
	 * @param int $userId
	 * @param CheckList $oldCheckList
	 * @param CheckList $newCheckList
	 * @throws LoaderException
	 * @throws SqlQueryException
	 */
	public static function doUpdatePostActions($taskId, $userId, $oldCheckList, $newCheckList)
	{
		$checkListLog = new TaskCheckListLog($taskId, $userId, $oldCheckList, $newCheckList);

		if (static::getDeferredActionsMode())
		{
			static::$collectedData[] = $checkListLog->getActionFields(
				TaskCheckListLog::ACTION_UPDATE,
				[['OLD' => $oldCheckList, 'NEW' => $newCheckList]]
			);
		}
		else
		{
			$checkListLog->logUpdatingChanges();
			SearchIndex::setTaskSearchIndex($taskId);
		}
	}

	/**
	 * Does some actions after deleting checklists.
	 *
	 * @param int $taskId
	 * @param int $userId
	 * @param array $data
	 * @throws ArgumentTypeException
	 * @throws LoaderException
	 * @throws SqlQueryException
	 */
	public static function doDeletePostActions($taskId, $userId, $data = [])
	{
		if (static::getDeferredActionsMode())
		{
			$itemsToLog = $data['ITEMS'];

			if ($itemsToLog)
			{
				$checkListLog = new TaskCheckListLog($taskId, $userId);
				$collectedData = $checkListLog->getActionFields(TaskCheckListLog::ACTION_DELETE, $itemsToLog);
				$checkListLog->logItemsChanges($collectedData);
			}
		}
		else
		{
			$checkList = $data['CHECKLIST'];

			$checkListLog = new TaskCheckListLog($taskId, $userId, $checkList);
			$checkListLog->actionRemove($checkList->getFields()['TITLE']);
		}

		SearchIndex::setTaskSearchIndex($taskId);
	}

	/**
	 * Does some actions after merging checklists.
	 *
	 * @param int $taskId
	 * @param int $userId
	 * @param array $data
	 * @throws ArgumentTypeException
	 * @throws SqlQueryException
	 */
	public static function doMergePostActions($taskId, $userId, $data = [])
	{
		static::$collectedData = array_merge(...static::$collectedData);

		if (!empty(static::$collectedData) && static::getDeferredActionsMode())
		{
			$checkListLog = new TaskCheckListLog($taskId, $userId);
			$checkListLog->logItemsChanges(static::$collectedData);
		}
	}

	/**
	 * Returns array of fields suitable for table data adding or updating.
	 *
	 * @param array $fields
	 * @return array
	 */
	public static function getFieldsForTable($fields)
	{
		return [
			'TASK_ID' => $fields['ENTITY_ID'],
			'CREATED_BY' => $fields['CREATED_BY'],
			'TITLE' => $fields['TITLE'],
			'SORT_INDEX' => $fields['SORT_INDEX'],
			'IS_COMPLETE' => $fields['IS_COMPLETE'],
			'IS_IMPORTANT' => $fields['IS_IMPORTANT'],
		];
	}

	/**
	 * @param int $taskId
	 * @param int $userId
	 * @return void
	 */
	protected static function fillCommonAccessActions($taskId, $userId)
	{
		$actions = array_keys(self::ACTIONS['COMMON']);
		$task = \CTaskItem::getInstanceFromPool($taskId, $userId);

		if (!$task->checkCanRead())
		{
			static::$commonAccessActions[$taskId][$userId] = array_fill_keys($actions, false);
			return;
		}

		static::$commonAccessActions[$taskId][$userId] = [
			self::ACTION_ADD => $task->isActionAllowed(CTaskItem::ACTION_CHECKLIST_ADD_ITEMS),
			self::ACTION_REORDER => $task->isActionAllowed(CTaskItem::ACTION_CHECKLIST_REORDER_ITEMS),
		];
	}

	/**
	 * @param int $taskId
	 * @param CheckList $checkList
	 * @param int $userId
	 * @return void
	 */
	protected static function fillItemAccessActions($taskId, $checkList, $userId)
	{
		$actions = array_keys(self::ACTIONS['ITEM']);
		$task = \CTaskItem::getInstanceFromPool($taskId, $userId);
		$checkListId = $checkList->getFields()['ID'];

		if ($task->isActionAllowed(CTaskItem::ACTION_EDIT))
		{
			static::$itemAccessActions[$taskId][$userId][$checkListId] = array_fill_keys($actions, true);
			return;
		}

		$isCreator = ($userId === $checkList->getFields()['CREATED_BY']);
		$isExecutant = (bool)$task->isUserRole(CTaskItem::ROLE_RESPONSIBLE | CTaskItem::ROLE_ACCOMPLICE);

		if (!$task->checkCanRead())
		{
			static::$itemAccessActions[$taskId][$userId][$checkListId] = array_fill_keys($actions, false);
			return;
		}

		static::$itemAccessActions[$taskId][$userId][$checkListId] = [
			self::ACTION_MODIFY => ($isExecutant && $isCreator),
			self::ACTION_REMOVE => ($isExecutant && $isCreator),
			self::ACTION_TOGGLE => $isExecutant,
		];
	}

	/**
	 * Logs error message.
	 *
	 * @param string $message
	 */
	public static function logError($message)
	{
		CTaskAssert::log($message, CTaskAssert::ELL_ERROR);
		Util::log($message);
	}
}