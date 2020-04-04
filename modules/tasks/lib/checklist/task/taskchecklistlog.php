<?php
namespace Bitrix\Tasks\CheckList\Task;

use Bitrix\Main\Application;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Db\SqlQueryException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\CheckList\Internals\CheckList;
use Bitrix\Tasks\UI;
use Bitrix\Tasks\Util\User;

use CTaskLog;

/**
 * Class TaskCheckListLog
 *
 * @package Bitrix\Tasks\CheckList
 */
class TaskCheckListLog
{
	const FIELD_CREATE = 'CHECKLIST_ITEM_CREATE';
	const FIELD_REMOVE = 'CHECKLIST_ITEM_REMOVE';
	const FIELD_RENAME = 'CHECKLIST_ITEM_RENAME';
	const FIELD_CHECK = 'CHECKLIST_ITEM_CHECK';
	const FIELD_UNCHECK = 'CHECKLIST_ITEM_UNCHECK';
	const FIELD_MAKE_IMPORTANT = 'CHECKLIST_ITEM_MAKE_IMPORTANT';
	const FIELD_MAKE_UNIMPORTANT = 'CHECKLIST_ITEM_MAKE_UNIMPORTANT';

	const ACTION_ADD = 'ADD';
	const ACTION_UPDATE = 'UPDATE';
	const ACTION_DELETE = 'DELETE';

	private $taskId;
	private $userId;
	private $oldCheckList;
	private $newCheckList;
	private $log;

	/**
	 * TaskCheckListLog constructor.
	 *
	 * @param $taskId
	 * @param $userId
	 * @param CheckList $oldCheckList
	 * @param CheckList $newCheckList
	 */
	public function __construct($taskId, $userId, $oldCheckList = null, $newCheckList = null)
	{
		$this->taskId = (int)$taskId;
		$this->userId = (int)$userId;

		$this->oldCheckList = $oldCheckList;
		$this->newCheckList = $newCheckList;

		$this->log = new CTaskLog();
	}

	/**
	 * @param string $action
	 * @param array $checkLists
	 * @return array
	 */
	public function getActionFields($action, $checkLists)
	{
		$fields = [];

		foreach ($checkLists as $item)
		{
			switch ($action)
			{
				case self::ACTION_ADD:
					$fields[] = $this->getAddActionFields($item);
					break;

				case self::ACTION_UPDATE:
					$fields[] = $this->getUpdateActionFields($item);
					break;

				case self::ACTION_DELETE:
					$fields[] = $this->getDeleteActionFields($item);
					break;

				default:
					break;
			}
		}

		return array_merge(...$fields);
	}

	/**
	 * @param CheckList $item
	 * @return array
	 */
	private function getAddActionFields($item)
	{
		$actionFields = [];

		$fields = $item->getFields();

		$title = $fields['TITLE'];
		$isComplete = $fields['IS_COMPLETE'];
		$isImportant = $fields['IS_IMPORTANT'];

		$actionFields[] = $this->getDynamicLogFields(self::FIELD_CREATE, '', $title);

		if ($isComplete)
		{
			$actionFields[] = $this->getDynamicLogFields(self::FIELD_CHECK, $title, $title);
		}

		if ($isImportant)
		{
			$actionFields[] = $this->getDynamicLogFields(self::FIELD_MAKE_IMPORTANT, $title, $title);
		}

		return $actionFields;
	}

	/**
	 * @param array $items
	 * @return array
	 */
	private function getUpdateActionFields($items)
	{
		$actionFields = [];

		$oldFields = $items['OLD']->getFields();
		$newFields = $items['NEW']->getFields();

		$oldTitle = $oldFields['TITLE'];
		$newTitle = $newFields['TITLE'];

		$oldIsComplete = $oldFields['IS_COMPLETE'];
		$newIsComplete = $newFields['IS_COMPLETE'];

		$oldIsImportant = $oldFields['IS_IMPORTANT'];
		$newIsImportant = $newFields['IS_IMPORTANT'];

		if ($newTitle !== $oldTitle)
		{
			$actionFields[] = $this->getDynamicLogFields(self::FIELD_RENAME, $oldTitle, $newTitle);
		}

		if ($newIsComplete !== $oldIsComplete)
		{
			$field = ($newIsComplete? self::FIELD_CHECK : self::FIELD_UNCHECK);
			$actionFields[] = $this->getDynamicLogFields($field, $oldTitle, $newTitle);
		}

		if ($newIsImportant !== $oldIsImportant)
		{
			$field = ($newIsImportant? self::FIELD_MAKE_IMPORTANT : self::FIELD_MAKE_UNIMPORTANT);
			$actionFields[] = $this->getDynamicLogFields($field, $oldTitle, $newTitle);
		}

		return $actionFields;
	}

	/**
	 * @param array $item
	 * @return array
	 */
	private function getDeleteActionFields($item)
	{
		return [$this->getDynamicLogFields(self::FIELD_REMOVE, $item['TITLE'], '')];
	}

	/**
	 * @param $items
	 * @throws ArgumentTypeException
	 * @throws SqlQueryException
	 */
	public function logItemsChanges($items)
	{
		$connection = Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$createdDate = DateTime::createFromUserTime(UI::formatDateTime(User::getTime($this->userId)));
		$createdDate = $sqlHelper->convertToDbDateTime($createdDate);

		$sql = "
			INSERT INTO b_tasks_log (TASK_ID, USER_ID, CREATED_DATE, FIELD, FROM_VALUE, TO_VALUE)
			VALUES
		";

		foreach ($items as $item)
		{
			$logFields = [
				'TASK_ID' => $this->taskId,
				'USER_ID' => $this->userId,
				'CREATED_DATE' => $createdDate,
				'FIELD' => $sqlHelper->convertToDb($item['FIELD']),
				'FROM_VALUE' => $sqlHelper->convertToDb($item['FROM_VALUE']),
				'TO_VALUE' => $sqlHelper->convertToDb($item['TO_VALUE']),
			];
			$values = implode(',', $logFields);

			$sql .= "({$values}),";
		}

		$sql = rtrim($sql, ',');

		$connection->query($sql);
	}

	public function logAddingChanges()
	{
		$fields = $this->newCheckList->getFields();

		$title = $fields['TITLE'];
		$isComplete = $fields['IS_COMPLETE'];
		$isImportant = $fields['IS_IMPORTANT'];

		$this->actionCreate($title);

		if ($isComplete)
		{
			$this->actionCheck($title, $title);
		}

		if ($isImportant)
		{
			$this->actionMakeImportant($title, $title);
		}
	}

	public function logUpdatingChanges()
	{
		$oldFields = $this->oldCheckList->getFields();
		$newFields = $this->newCheckList->getFields();

		$oldTitle = $oldFields['TITLE'];
		$newTitle = $newFields['TITLE'];

		$oldIsComplete = $oldFields['IS_COMPLETE'];
		$newIsComplete = $newFields['IS_COMPLETE'];

		$oldIsImportant = $oldFields['IS_IMPORTANT'];
		$newIsImportant = $newFields['IS_IMPORTANT'];

		if ($newTitle !== $oldTitle)
		{
			$this->actionRename($oldTitle, $newTitle);
		}

		if ($newIsComplete !== $oldIsComplete)
		{
			if ($newIsComplete)
			{
				$this->actionCheck($oldTitle, $newTitle);
			}
			else
			{
				$this->actionUncheck($oldTitle, $newTitle);
			}
		}

		if ($newIsImportant !== $oldIsImportant)
		{
			if ($newIsImportant)
			{
				$this->actionMakeImportant($oldTitle, $newTitle);
			}
			else
			{
				$this->actionMakeUnimportant($oldTitle, $newTitle);
			}
		}
	}

	/**
	 * @param $title
	 */
	public function actionCreate($title)
	{
		$logFields = $this->getFullLogFields(self::FIELD_CREATE, '', $title);
		$this->log->Add($logFields);
	}

	/**
	 * @param $title
	 */
	public function actionRemove($title)
	{
		$logFields = $this->getFullLogFields(self::FIELD_REMOVE, $title, '');
		$this->log->Add($logFields);
	}

	/**
	 * @param $oldTitle
	 * @param $newTitle
	 */
	public function actionRename($oldTitle, $newTitle)
	{
		$logFields = $this->getFullLogFields(self::FIELD_RENAME, $oldTitle, $newTitle);
		$this->log->Add($logFields);
	}

	/**
	 * @param $oldTitle
	 * @param $newTitle
	 */
	public function actionCheck($oldTitle, $newTitle)
	{
		$logFields = $this->getFullLogFields(self::FIELD_CHECK, $oldTitle, $newTitle);
		$this->log->Add($logFields);
	}

	/**
	 * @param $oldTitle
	 * @param $newTitle
	 */
	public function actionUncheck($oldTitle, $newTitle)
	{
		$logFields = $this->getFullLogFields(self::FIELD_UNCHECK, $oldTitle, $newTitle);
		$this->log->Add($logFields);
	}

	/**
	 * @param $oldTitle
	 * @param $newTitle
	 */
	public function actionMakeImportant($oldTitle, $newTitle)
	{
		$logFields = $this->getFullLogFields(self::FIELD_MAKE_IMPORTANT, $oldTitle, $newTitle);
		$this->log->Add($logFields);
	}

	/**
	 * @param $oldTitle
	 * @param $newTitle
	 */
	public function actionMakeUnimportant($oldTitle, $newTitle)
	{
		$logFields = $this->getFullLogFields(self::FIELD_MAKE_UNIMPORTANT, $oldTitle, $newTitle);
		$this->log->Add($logFields);
	}

	/**
	 * @param $field
	 * @param $fromValue
	 * @param $toValue
	 * @return array
	 */
	private function getFullLogFields($field, $fromValue, $toValue)
	{
		return array_merge($this->getStaticLogFields(), $this->getDynamicLogFields($field, $fromValue, $toValue));
	}

	/**
	 * @return array
	 */
	private function getStaticLogFields()
	{
		return [
			'TASK_ID' => $this->taskId,
			'USER_ID' => $this->userId,
			'CREATED_DATE' => UI::formatDateTime(User::getTime($this->userId)),
		];
	}

	/**
	 * @param $field
	 * @param $fromValue
	 * @param $toValue
	 * @return array
	 */
	private function getDynamicLogFields($field, $fromValue, $toValue)
	{
		return [
			'FIELD' => $field,
			'FROM_VALUE' => $fromValue,
			'TO_VALUE' => $toValue,
		];
	}
}