<?php
namespace Bitrix\Tasks\CheckList\Task;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Db\SqlQueryException;
use Bitrix\Main\LoaderException;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Access\TaskAccessController;
use Bitrix\Tasks\CheckList\CheckListFacade;
use Bitrix\Tasks\CheckList\Internals\CheckList;
use Bitrix\Tasks\Comments\Task\CommentPoster;
use Bitrix\Tasks\Helper\Analytics;
use Bitrix\Tasks\Integration\CRM\TimeLineManager;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;
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
	public static function getCheckListTree(): string
	{
		return TaskCheckListTree::class;
	}

	/**
	 * Returns table class for checklist table
	 *
	 * @return string
	 */
	public static function getCheckListDataController(): string
	{
		return CheckListTable::getClass();
	}

	/**
	 * Returns table class for checklist tree table
	 *
	 * @return string
	 */
	public static function getCheckListTreeDataController(): string
	{
		return TaskCheckListTree::getDataController();
	}

	/**
	 * Returns table class for checklist member table
	 *
	 * @return string
	 */
	public static function getCheckListMemberDataController(): string
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
	public static function doAddPostActions($taskId, $userId, $checkList): void
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

			$members = $checkList->getFields()['MEMBERS'];
			if (is_array($members) && !empty($members))
			{
				$task = new CTaskItem($taskId, $userId);
				static::addMembersToTask($task, $members);
			}
		}
	}

	private static function addMembersToTask(CTaskItem $task, array $members): void
	{
		static::addAccomplicesToTask($task, $members);
		static::addAuditorsToTask($task, $members);
	}

	private static function addAccomplicesToTask(CTaskItem $task, array $members): void
	{
		try
		{
			$taskData = $task->getData(false);
		}
		catch (\TasksException $e)
		{
			return;
		}

		$accomplicesIds = [];
		foreach ($members as $id => $member)
		{
			$type = (is_array($member) ? $member['TYPE'] : $member);
			if ($type === \Bitrix\Tasks\Internals\Task\MemberTable::MEMBER_TYPE_ACCOMPLICE)
			{
				$accomplicesIds[] = $id;
			}
		}

		if (empty($accomplicesIds))
		{
			return;
		}

		$accomplices = array_unique(array_merge($taskData['ACCOMPLICES'], $accomplicesIds));
		$task->update(['ACCOMPLICES' => $accomplices]);
	}

	private static function addAuditorsToTask(CTaskItem $task, array $members): void
	{
		//checklist auditors
		try
		{
			$taskData = $task->getData(false);
		}
		catch (\TasksException $e)
		{
			return;
		}

		$auditorsIds = [];
		foreach ($members as $id => $member)
		{
			$type = (is_array($member) ? $member['TYPE'] : $member);
			if ($type === \Bitrix\Tasks\Internals\Task\MemberTable::MEMBER_TYPE_AUDITOR)
			{
				$auditorsIds[] = $id;
			}
		}

		if (empty($auditorsIds))
		{
			return;
		}

		$auditors = array_unique(array_merge($taskData['AUDITORS'], $auditorsIds));
		$task->update(['AUDITORS' => $auditors]);
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
	public static function doUpdatePostActions($taskId, $userId, $oldCheckList, $newCheckList): void
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

			if ($newCheckList->getSkipMembers())
			{
				return;
			}

			$members = $newCheckList->getFields()['MEMBERS'];
			if (is_array($members) && !empty($members))
			{
				$task = new CTaskItem($taskId, $userId);
				static::addMembersToTask($task, $members);
			}
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
	public static function doDeletePostActions($taskId, $userId, $data = []): void
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

			SearchIndex::setTaskSearchIndex($taskId);
		}
	}

	/**
	 * Does some actions before merging checklists.
	 *
	 * @param int $taskId
	 * @param int $userId
	 * @param array $data
	 */
	public static function doMergePreActions($taskId, $userId, $data = []): void
	{
		static::$collectedData = [];
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
	public static function doMergePostActions($taskId, $userId, $data = []): void
	{
		if (static::$collectedData)
		{
			static::$collectedData = array_merge(...static::$collectedData);
		}

		if (!empty(static::$collectedData) && static::getDeferredActionsMode())
		{
			$checkListLog = new TaskCheckListLog($taskId, $userId);
			$checkListLog->logItemsChanges(static::$collectedData);
		}

		SearchIndex::setTaskSearchIndex($taskId);

		static::logToAnalyticsFile($data['PARAMETERS']);
	}

	/**
	 * @param $taskId
	 * @param $userId
	 */
	private static function postChangesComment($taskId, $userId): void
	{
		$commentPoster = CommentPoster::getInstance($taskId, $userId);
		$commentPoster->appendChecklistChangesMessage();

		if (!$commentPoster->getDeferredPostMode())
		{
			$commentPoster->postComments();
			$commentPoster->clearComments();
		}
	}

	/**
	 * Returns array of fields suitable for table data adding or updating.
	 *
	 * @param array $fields
	 * @return array
	 */
	public static function getFieldsForTable($fields): array
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
	protected static function fillCommonAccessActions($taskId, $userId): void
	{
		$actions = array_keys(self::ACTIONS['COMMON']);
		$task = CTaskItem::getInstanceFromPool($taskId, $userId);

		if (!$task->checkCanRead())
		{
			static::$commonAccessActions[$taskId][$userId] = array_fill_keys($actions, false);
			return;
		}

		static::$commonAccessActions[$taskId][$userId] = [
			self::ACTION_ADD => $task->checkAccess(ActionDictionary::ACTION_CHECKLIST_ADD),
			self::ACTION_REORDER => $task->checkAccess(ActionDictionary::ACTION_CHECKLIST_EDIT),
		];
	}

	/**
	 * @param int $taskId
	 * @param CheckList $checkList
	 * @param int $userId
	 * @return void
	 */
	protected static function fillItemAccessActions($taskId, $checkList, $userId): void
	{
		$actions = array_keys(self::ACTIONS['ITEM']);
		$task = CTaskItem::getInstanceFromPool($taskId, $userId);
		$checkListId = $checkList->getFields()['ID'];

		if ($task->checkAccess(ActionDictionary::ACTION_TASK_EDIT))
		{
			static::$itemAccessActions[$taskId][$userId][$checkListId] = array_fill_keys($actions, true);
			return;
		}

		$isCreator = ($userId === $checkList->getFields()['CREATED_BY']);
		$isExecutant = $task->checkAccess(ActionDictionary::ACTION_TASK_EDIT);

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
	public static function logError($message): void
	{
		CTaskAssert::log($message, CTaskAssert::ELL_ERROR);
		Util::log($message);
	}

	/**
	 * Logs checklist changes to analytics file.
	 *
	 * @param array $parameters
	 */
	private static function logToAnalyticsFile($parameters): void
	{
		$analyticsData = ($parameters['analyticsData'] ?? null);

		if (is_array($analyticsData) && !empty($analyticsData))
		{
			foreach ($analyticsData as $action => $value)
			{
				$tag = '';
				$label = '';

				if ($action === 'checklistCount')
				{
					$action = 'saveChecklist';
					$label = $value;
				}

				Analytics::getInstance()->logToFile($action, $tag, $label);
			}
		}
	}

	protected static function getAccessControllerClass(): string
	{
		return TaskAccessController::class;
	}

	protected static function onAfterMerge(array $traversedItems, int $userId, int $taskId, array $parameters): void
	{
		if (
			isset($parameters['context'])
			&& $parameters['context'] === self::TASK_ADD_CONTEXT
		)
		{
			return;
		}
		$task = TaskRegistry::getInstance()->getObject($taskId);
		if (is_null($task))
		{
			return;
		}

		$newRootItems = array_filter($traversedItems,
			static fn (array $item): bool => (int)$item['PARENT_ID'] === 0
		);
		$newRootItemKeys = array_values(array_map(
			static fn (array $item): int => (int)$item['ID'],
			$newRootItems
		));

		$oldRootItems = array_filter(static::$oldItemsToMerge,
			static fn (array $item): bool => (int)$item['PARENT_ID'] === 0
		);
		$oldRootItemKeys = array_keys($oldRootItems);

		$timeline = new TimeLineManager($taskId, $userId);
		if (!empty(array_diff($newRootItemKeys, $oldRootItemKeys)))
		{
			$timeline->onTaskChecklistAdded();
		}
		else
		{
			$timeline->onTaskChecklistChanged();
		}
		$timeline->save();
	}

	protected static function onAfterUpdate(int $taskId): void
	{
		(new TimeLineManager($taskId))->onTaskChecklistChanged()->save();
	}
}