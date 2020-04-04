<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2013 Bitrix
 *
 * @global $USER_FIELD_MANAGER CUserTypeManager
 * @global $APPLICATION CMain
 */

/*

Usage example:

CModule::IncludeModule('tasks');

try
{
	$oTaskItem = new CTaskItem(75, 1);
	
	$oListItem = CTaskCheckListItem::add($oTaskItem, array('TITLE' => 'test'));
	//var _dump($oListItem->getId());
	
	//var _dump($oListItem->isComplete());
	$oListItem->complete();
	//var _dump($oListItem->isComplete());
	$oListItem->renew();
	//var _dump($oListItem->isComplete());
	$oListItem->delete();
	//var _dump($oListItem->isComplete());
}
catch (Exception $e)
{
	echo 'Got exception: ' . $e->getCode() . '; ' . $e->getFile() . ':' . $e->getLine();
}

Expected output:
int(15)
string(4) "test"
string(2) "75"
bool(false)
bool(true)
bool(false) 
Got exception: 8; /var/www/sites/RAM/cpb24.bxram.bsr/html/bitrix/modules/tasks/classes/general/checklistitem.php:282
*/

use Bitrix\Tasks\Internals\Task\CheckListTable;

final class CTaskCheckListItem extends CTaskSubItemAbstract
{
	const ACTION_ADD = 0x01;
	const ACTION_MODIFY = 0x02;
	const ACTION_REMOVE = 0x03;
	const ACTION_TOGGLE = 0x04;
	const ACTION_REORDER = 0x05;

	/**
	 * @access private
	 *
	 * @deprecated
	 */
	public static function getTaskIdByItemId($itemId)
	{
		global $DB;

		$itemId = intval($itemId);

		if (!$itemId)
		{
			return false;
		}

		$item = $DB->Query("select TASK_ID from b_tasks_checklist_items where ID = '".$itemId."'")->fetch();

		return $item['TASK_ID'];
	}

	/**
	 * Removes all checklist's items for given task.
	 * WARNING: This function doesn't check rights!
	 *
	 * @param integer $taskId
	 *
	 * @throws TasksException
	 * @throws CTaskAssertException
	 */
	public static function deleteByTaskId($taskId)
	{
		CTaskAssert::assert(
			CTaskAssert::isLaxIntegers($taskId) && ($taskId > 0)
		);

		$list = CheckListTable::getList(
			array(
				"select" => array("ID"),
				"filter" => array(
					"=TASK_ID" => $taskId,
				),
			)
		);
		while ($item = $list->fetch())
		{
			$result = CheckListTable::delete($item["ID"]);
			if (!$result->isSuccess())
			{
				throw new TasksException(
					'', TasksException::TE_ACTION_FAILED_TO_BE_PROCESSED
				);
			}
		}
	}

	/**
	 * @param array $parameters
	 *
	 * @return array |null
	 * @throws TasksException
	 */
	public static function getList(array $parameters = array())
	{
		if(!array_key_exists('select', $parameters) || empty($parameters['select']))
		{
			$parameters['select'] = ['ID', 'CREATED_BY', 'TITLE', 'IS_COMPLETE', 'SORT_INDEX'];
		}

		if(!array_key_exists('order', $parameters) || empty($parameters['order']))
		{
			$parameters['order'] = ['SORT_INDEX'=>'asc', 'ID'=>'DESC'];
		}

		$result = CheckListTable::getList($parameters);

		/** @var $result \Bitrix\Main\ORM\Query\Result */
		if (!$result->isSuccess())
		{
			throw new TasksException(
				'', TasksException::TE_ACTION_FAILED_TO_BE_PROCESSED
			);
		}
		else
		{
			return $result->fetchAll();
		}
	}

	/* @access private */
	/**
	 * @param $taskId
	 *
	 * @return bool|CDBResult
	 * @throws \Bitrix\Main\SystemException
	 *
	 * @deprecated
	 * 
	 */
	final public static function getByTaskId($taskId)
	{
		global $DB;

		$rc = $DB->Query(
			"SELECT ID, CREATED_BY, TITLE, IS_COMPLETE
				FROM b_tasks_checklist_items 
				WHERE TASK_ID = ".(int)$taskId." ORDER BY SORT_INDEX",
			$bIgnoreErrors = true
		);

		if ($rc)
			return ($rc);
		else
			throw new \Bitrix\Main\SystemException();
	}

	public static function runRestMethod($executiveUserId, $methodName, $args,
		/** @noinspection PhpUnusedParameterInspection */
										 $navigation)
	{
		static $arManifest = null;
		static $arMethodsMetaInfo = null;

		if ($arManifest === null)
		{
			$arManifest = self::getManifest();
			$arMethodsMetaInfo = $arManifest['REST: available methods'];
		}

		// Check and parse params
		CTaskAssert::assert(isset($arMethodsMetaInfo[$methodName]));
		$arMethodMetaInfo = $arMethodsMetaInfo[$methodName];
		$argsParsed = CTaskRestService::_parseRestParams('ctaskchecklistitem', $methodName, $args);

		$returnValue = null;
		if (isset($arMethodMetaInfo['staticMethod']) && $arMethodMetaInfo['staticMethod'])
		{
			if ($methodName === 'add')
			{
				$taskId = $argsParsed[0];
				$arFields = $argsParsed[1];
				$oTaskItem = CTaskItem::getInstance($taskId, $executiveUserId);
				$oItem = self::add($oTaskItem, $arFields);

				$returnValue = $oItem->getId();
			}
			elseif ($methodName === 'getlist')
			{
				$taskId = $argsParsed[0];
				$order = $argsParsed[1];
				$oTaskItem = CTaskItem::getInstance($taskId, $executiveUserId);
				list($oCheckListItems, $rsData) = self::fetchList($oTaskItem, $order);

				$returnValue = array();

				foreach ($oCheckListItems as $oCheckListItem)
				{
					$returnValue[] = $oCheckListItem->getData(false);
				}
			}
			else
				$returnValue = call_user_func_array(array('self', $methodName), $argsParsed);
		}
		else
		{
			$taskId = array_shift($argsParsed);
			$itemId = array_shift($argsParsed);
			$oTaskItem = CTaskItem::getInstance($taskId, $executiveUserId);
			$obElapsed = new self($oTaskItem, $itemId);

			if ($methodName === 'get')
			{
				$returnValue = $obElapsed->getData();
				$returnValue['TITLE'] = htmlspecialcharsback($returnValue['TITLE']);
			}
			else
				$returnValue = call_user_func_array(array($obElapsed, $methodName), $argsParsed);
		}

		return (array($returnValue, null));
	}

	/**
	 * This method is not part of public API.
	 * Its purpose is for internal use only.
	 * It can be changed without any notifications
	 *
	 * @access private
	 */
	public static function getManifest()
	{
		// todo: plug getPublicFieldMap() here

		$arWritableKeys = array('TITLE', 'SORT_INDEX', 'IS_COMPLETE');
		$arSortableKeys = array_merge(array('ID', 'CREATED_BY', 'TOGGLED_BY', 'TOGGLED_DATE'), $arWritableKeys);
		$arDateKeys = array('TOGGLED_DATE');
		$arReadableKeys = array_merge(
			array('TASK_ID'),
			$arDateKeys,
			$arSortableKeys,
			$arWritableKeys
		);

		return (array(
			'Manifest version'                         => '1.0',
			'Warning'                                  => 'don\'t rely on format of this manifest, it can be changed without any notification',
			'REST: shortname alias to class'           => 'checklistitem',
			'REST: writable checklistitem data fields' => $arWritableKeys,
			'REST: readable checklistitem data fields' => $arReadableKeys,
			'REST: sortable checklistitem data fields' => $arSortableKeys,
			'REST: date fields'                        => $arDateKeys,
			'REST: available methods'                  => array(
				'getmanifest'     => array(
					'staticMethod' => true,
					'params'       => array()
				),
				'getlist'         => array(
					'staticMethod'             => true,
					'mandatoryParamsCount'     => 1,
					'params'                   => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						),
						array(
							'description' => 'arOrder',
							'type'        => 'array',
							'allowedKeys' => $arSortableKeys
						),
					),
					'allowedKeysInReturnValue' => $arReadableKeys,
					'collectionInReturnValue'  => true
				),
				'get'             => array(
					'mandatoryParamsCount'     => 2,
					'params'                   => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						),
						array(
							'description' => 'itemId',
							'type'        => 'integer'
						)
					),
					'allowedKeysInReturnValue' => $arReadableKeys
				),
				'add'             => array(
					'staticMethod'         => true,
					'mandatoryParamsCount' => 2,
					'params'               => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						),
						array(
							'description' => 'arFields',
							'type'        => 'array',
							'allowedKeys' => $arWritableKeys
						)
					)
				),
				'update'          => array(
					'staticMethod'         => false,
					'mandatoryParamsCount' => 3,
					'params'               => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						),
						array(
							'description' => 'itemId',
							'type'        => 'integer'
						),
						array(
							'description' => 'arFields',
							'type'        => 'array',
							'allowedKeys' => $arWritableKeys
						)
					)
				),
				'delete'          => array(
					'staticMethod'         => false,
					'mandatoryParamsCount' => 2,
					'params'               => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						),
						array(
							'description' => 'itemId',
							'type'        => 'integer'
						)
					)
				),
				'complete'        => array(
					'staticMethod'         => false,
					'mandatoryParamsCount' => 2,
					'params'               => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						),
						array(
							'description' => 'itemId',
							'type'        => 'integer'
						)
					)
				),
				'renew'           => array(
					'staticMethod'         => false,
					'mandatoryParamsCount' => 2,
					'params'               => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						),
						array(
							'description' => 'itemId',
							'type'        => 'integer'
						)
					)
				),
				'moveafteritem'   => array(
					'staticMethod'         => false,
					'mandatoryParamsCount' => 3,
					'params'               => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						),
						array(
							'description' => 'itemId',
							'type'        => 'integer'
						),
						array(
							'description' => 'afterItemId',
							'type'        => 'integer'
						)
					)
				),
				'isactionallowed' => array(
					'staticMethod'         => false,
					'mandatoryParamsCount' => 3,
					'params'               => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						),
						array(
							'description' => 'itemId',
							'type'        => 'integer'
						),
						array(
							'description' => 'actionId',
							'type'        => 'integer'
						)
					)
				)
			)
		));
	}

	/**
	 * @param CTaskItemInterface $task (of class CTaskItem,checklist item will be added to this task)
	 * @param array $arFields with mandatory element TITLE (string).
	 *
	 * @return CTaskCheckListItem
	 * @throws TasksException with code TasksException::TE_WRONG_ARGUMENTS
	 */
	public static function add(CTaskItemInterface $task, $arFields)
	{
		global $DB;

		if (!self::checkFieldsForAdd($arFields))
		{
			throw new TasksException(false, TasksException::TE_WRONG_ARGUMENTS);
		}

		$arFields['SORT_INDEX'] = (int)$arFields['SORT_INDEX'];
		$arFields['IS_COMPLETE'] = in_array($arFields['IS_COMPLETE'], ['Y', true, 1], true) ? 'Y' : 'N';

		if (!$task->isActionAllowed(CTaskItem::ACTION_CHECKLIST_ADD_ITEMS))
		{
			throw new TasksException(false, TasksException::TE_ACTION_NOT_ALLOWED);
		}

		$taskId = (int)$task->getId();
		$executiveUserId = (int)$task->getExecutiveUserId();

		/** @noinspection PhpDynamicAsStaticMethodCallInspection */
		$curDatetime = \Bitrix\Tasks\UI::formatDateTime(\Bitrix\Tasks\Util\User::getTime());

		$arFieldsToDb = array(
			'TITLE'       => $arFields['TITLE'],
			'TASK_ID'     => $taskId,
			'CREATED_BY'  => $executiveUserId,
			'IS_COMPLETE' => $arFields['IS_COMPLETE'],
			'SORT_INDEX'  => 0
		);

		if ($arFields['SORT_INDEX'])
		{
			$arFieldsToDb['SORT_INDEX'] = $arFields['SORT_INDEX'];
		}
		else
		{
			$rc = $DB->Query(
				"SELECT MAX(SORT_INDEX) AS MAX_SORT_INDEX
				FROM b_tasks_checklist_items 
				WHERE TASK_ID = ".(int)$taskId
			);
			if (($maxSortIndex = $rc->fetch()) && isset($maxSortIndex['MAX_SORT_INDEX']))
				$arFieldsToDb['SORT_INDEX'] = (int)$maxSortIndex['MAX_SORT_INDEX'] + 1;
		}

		$addResult = CheckListTable::add($arFieldsToDb);
		$id = $addResult->isSuccess() ? $addResult->getId() : false;

		if (!$id)
		{
			throw new TasksException('Action failed', TasksException::TE_ACTION_FAILED_TO_BE_PROCESSED);
		}

		$occurAsUserId = CTasksTools::getOccurAsUserId();
		if (!$occurAsUserId)
		{
			$occurAsUserId = $executiveUserId;
		}

		// changes log
		$arLogFields = [
			'TASK_ID'      => $taskId,
			'USER_ID'      => $occurAsUserId,
			'CREATED_DATE' => $curDatetime,
			'FIELD'        => 'CHECKLIST_ITEM_CREATE',
			'FROM_VALUE'   => '',
			'TO_VALUE'     => $arFields['TITLE']
		];

		// TODO: move search index update in afterTaskCheckListItemAdd event when it comes to life
		\Bitrix\Tasks\Internals\SearchIndex::setTaskSearchIndex($taskId);

		$log = new CTaskLog();
		$log->Add($arLogFields);

		if ($arFieldsToDb['IS_COMPLETE'] === 'Y')
		{
			// changes log
			$arLogFields = array(
				'TASK_ID'      => $taskId,
				'USER_ID'      => $occurAsUserId,
				'CREATED_DATE' => $curDatetime,
				'FIELD'        => 'CHECKLIST_ITEM_CHECK',
				'FROM_VALUE'   => 0,
				'TO_VALUE'     => 1
			);

			$log->Add($arLogFields);
		}

		return new self($task, $id);
	}

	public static function checkFieldsForAdd($arFields)
	{
		return (self::checkFields($arFields, $checkForAdd = true));
	}

	private static function checkFields($fields, $checkForAdd)
	{
		global $APPLICATION;

		$errors = [];

		if ($checkForAdd)
		{
			// TITLE must be set during add
			if (!array_key_exists('TITLE', $fields))
			{
				$errors[] = [
					'text' => GetMessage('TASKS_CHECKLISTITEM_BAD_TITLE'),
					'id'   => 'ERROR_TASKS_CHECKLISTITEM_BAD_TITLE'
				];
			}
		}

		$allowedFields = ['SORT_INDEX', 'TITLE', 'IS_COMPLETE'];
		foreach (array_keys($fields) as $fieldName)
		{
			if (!in_array($fieldName, $allowedFields))
			{
				$errors[] = [
					'text' => GetMessage('TASKS_CHECKLISTITEM_UNKNOWN_FIELD'),
					'id'   => 'ERROR_TASKS_CHECKLISTITEM_UNKNOWN_FIELD'
				];
			}
		}

		// TITLE must be an non-empty string
		if (array_key_exists('TITLE', $fields))
		{
			if (!trim($fields['TITLE']))
			{
				$errors[] = array(
					'text' => GetMessage('TASKS_CHECKLISTITEM_BAD_TITLE'),
					'id'   => 'ERROR_TASKS_CHECKLISTITEM_BAD_TITLE'
				);
			}
		}

		// IS_COMPLETE can be 'Y' / 'N' / true / false
		if (array_key_exists('IS_COMPLETE', $fields))
		{
			$availableValues = ['Y', 'N', true, false, 0, 1];
			if (!in_array($fields['IS_COMPLETE'], $availableValues))
			{
				$errors[] = array(
					'text' => GetMessage('TASKS_CHECKLISTITEM_BAD_COMPLETE_FLAG'),
					'id'   => 'ERROR_TASKS_CHECKLISTITEM_BAD_COMPLETE_FLAG'
				);
			}
		}

		if (!empty($errors))
		{
			$e = new CAdminException($errors);
			$APPLICATION->ThrowException($e);
		}

		return empty($errors);
	}

	public static function getPublicFieldMap()
	{
		// READ, WRITE, SORT, FILTER, DATE
		return [
			'TITLE'        => [1, 1, 1, 0, 0],
			'SORT_INDEX'   => [1, 1, 1, 0, 0],
			'IS_COMPLETE'  => [1, 1, 1, 0, 0],
			'ID'           => [1, 0, 1, 0, 0],
			'CREATED_BY'   => [1, 0, 1, 0, 0],
			'TOGGLED_BY'   => [1, 0, 1, 0, 0],
			'TOGGLED_DATE' => [1, 0, 1, 1, 1],
			'TASK_ID'      => [1, 0, 0, 0, 0],
		];
	}

	public function getTitle()
	{
		$arItemData = $this->getData();

		return ($arItemData['TITLE']);
	}

	// this function does not check rights on EDIT action, because item reordering is not an actual EDIT

	public function getTaskId()
	{
		$arItemData = $this->getData();

		return ($arItemData['TASK_ID']);
	}

	/**
	 * @return bool true if complete, false otherwise
	 * @throws TasksException
	 */
	public function isComplete()
	{
		$arItemData = $this->getData();
		$isComplete = ($arItemData['IS_COMPLETE'] === 'Y');

		return ($isComplete);
	}

	public function complete()
	{
		try
		{
			$this->update(array('IS_COMPLETE' => 'Y'));
		}
		catch (\TasksException $e)
		{
			return false;
		}
	}

	/**
	 * @param $arFields
	 *
	 * @throws CTaskAssertException
	 * @throws TasksException
	 * @throws \Bitrix\Main\ObjectException
	 */
	public function update($arFields)
	{
		global $DB;

		if (!self::checkFieldsForUpdate($arFields))
		{
			throw new TasksException('', TasksException::TE_WRONG_ARGUMENTS);
		}

		$arFields = self::normalizeFieldsDataForUpdate($arFields);

		CTaskAssert::assert(is_array($arFields));

		// Nothing to do?
		if (empty($arFields))
			return;

		if (!$this->isActionAllowed(self::ACTION_MODIFY))
		{
			if ((count($arFields) == 1) && array_key_exists('IS_COMPLETE', $arFields))
			{
				// this field can be edited only in case of ACTION_TOGGLE is allowed
				if (!$this->isActionAllowed(self::ACTION_TOGGLE))
				{
					throw new TasksException('Item toggle permission denied', TasksException::TE_ACTION_NOT_ALLOWED);
				}
			}
			else
			{
				throw new TasksException('Item edit permission denied', TasksException::TE_ACTION_NOT_ALLOWED);
			}
		}

		/** @noinspection PhpDynamicAsStaticMethodCallInspection */
		$curDatetime = \Bitrix\Tasks\UI::formatDateTime(\Bitrix\Tasks\Util\User::getTime());

		$arCurrentData = $this->getData();
		$curTitle = $arCurrentData['~TITLE'];
		$curIsComplete = $arCurrentData['IS_COMPLETE'];

		if (isset($arFields['IS_COMPLETE']))
			$newIsComplete = $arFields['IS_COMPLETE'];
		else
			$newIsComplete = $curIsComplete;

		if (isset($arFields['TITLE']))
			$newTitle = $arFields['TITLE'];
		else
			$newTitle = $curTitle;

		if (isset($arFields['IS_COMPLETE']))
		{
			$arFields['TOGGLED_BY'] = $this->executiveUserId;
			$arFields['TOGGLED_DATE'] = new \Bitrix\Main\Type\DateTime($curDatetime);
		}

		$result = CheckListTable::update($this->itemId, $arFields);

		// Reset cache
		$this->resetCache();

		if (!$result->isSuccess())
		{
			throw new TasksException(
				'', TasksException::TE_ACTION_FAILED_TO_BE_PROCESSED
			);
		}

		// TODO: move search index update in afterTaskCheckListItemUpdate event when it comes to life
		\Bitrix\Tasks\Internals\SearchIndex::setTaskSearchIndex($this->taskId);

		if ($curTitle !== $newTitle)
		{
			$occurAsUserId = CTasksTools::getOccurAsUserId();
			if (!$occurAsUserId)
				$occurAsUserId = (int)$this->executiveUserId;

			// changes log
			$arLogFields = array(
				'TASK_ID'      => (int)$this->taskId,
				'USER_ID'      => $occurAsUserId,
				'CREATED_DATE' => $curDatetime,
				'FIELD'        => 'CHECKLIST_ITEM_RENAME',
				'FROM_VALUE'   => $curTitle,
				'TO_VALUE'     => $newTitle
			);

			$log = new CTaskLog();
			$log->Add($arLogFields);
		}

		if ($curIsComplete !== $newIsComplete)
		{
			$occurAsUserId = CTasksTools::getOccurAsUserId();
			if (!$occurAsUserId)
				$occurAsUserId = (int)$this->executiveUserId;

			// changes log
			$arLogFields = array(
				'TASK_ID'      => (int)$this->taskId,
				'USER_ID'      => $occurAsUserId,
				'CREATED_DATE' => $curDatetime,
				'FIELD'        => (($newIsComplete === 'Y') ? 'CHECKLIST_ITEM_CHECK' : 'CHECKLIST_ITEM_UNCHECK'),
				'FROM_VALUE'   => $curTitle,
				'TO_VALUE'     => $newTitle
			);

			$log = new CTaskLog();
			$log->Add($arLogFields);
		}
	}

	public static function checkFieldsForUpdate($arFields)
	{
		return (self::checkFields($arFields, $checkForAdd = false));
	}

	private static function normalizeFieldsDataForUpdate($arFields)
	{
		if (isset($arFields['IS_COMPLETE']))
		{
			if ($arFields['IS_COMPLETE'] === true ||
				$arFields['IS_COMPLETE'] === 'Y' ||
				intval($arFields['IS_COMPLETE']) > 0)
				$arFields['IS_COMPLETE'] = 'Y';
			else
				$arFields['IS_COMPLETE'] = 'N';
		}

		if (isset($arFields['SORT_INDEX']))
			$arFields['SORT_INDEX'] = (int)$arFields['SORT_INDEX'];

		return ($arFields);
	}

	public function isActionAllowed($actionId)
	{
		$isActionAllowed = false;
		CTaskAssert::assertLaxIntegers($actionId);
		$actionId = (int)$actionId;

		$isAdmin = CTasksTools::IsAdmin($this->executiveUserId) ||
				   CTasksTools::IsPortalB24Admin($this->executiveUserId);

		if ($actionId === self::ACTION_ADD || $actionId === self::ACTION_REORDER) // ask taskitem for add() permission
		{
			$isActionAllowed = $this->oTaskItem->isActionAllowed(
				self::ACTION_ADD ? CTaskItem::ACTION_CHECKLIST_ADD_ITEMS : CTaskItem::ACTION_CHECKLIST_REORDER_ITEMS
			);
		}
		elseif (in_array(
			(int)$actionId,
			array(self::ACTION_MODIFY, self::ACTION_REMOVE, self::ACTION_TOGGLE),
			true
		)) // for other actions - below
		{
			$arItemData = $this->getData($bEscape = false);

			if ($isAdmin ||
				($arItemData['CREATED_BY'] == $this->executiveUserId)) // admin and creator may do what they want
			{
				$isActionAllowed = true;
			}
			elseif ($actionId == self::ACTION_TOGGLE)
			{
				// toggle() can do director, responsible and accomplices
				if ($this->oTaskItem->isUserRole(
					CTaskItem::ROLE_DIRECTOR | CTaskItem::ROLE_RESPONSIBLE | CTaskItem::ROLE_ACCOMPLICE
				))
				{
					$isActionAllowed = true;
				}
			}
			elseif (($actionId == self::ACTION_MODIFY) || ($actionId == self::ACTION_REMOVE))
			{
				// edit() and remove() can do director or user who can edit task
				if ($this->oTaskItem->isUserRole(CTaskItem::ROLE_DIRECTOR) ||
					$this->oTaskItem->isActionAllowed(CTaskItem::ACTION_EDIT))
				{
					$isActionAllowed = true;
				}
			}
		}

		return ($isActionAllowed);
	}

	public function renew()
	{
		try
		{
			$this->update(array('IS_COMPLETE' => 'N'));
		}
		catch (\TasksException $e)
		{
			return false;
		}
	}

	/**
	 * @return bool
	 * @throws TasksException
	 */
	public function delete()
	{
		$taskId = (int)$this->oTaskItem->getId();
		$executiveUserId = (int)$this->oTaskItem->getExecutiveUserId();

		/** @noinspection PhpDynamicAsStaticMethodCallInspection */
		$curDatetime = \Bitrix\Tasks\UI::formatDateTime(\Bitrix\Tasks\Util\User::getTime());

		if (!$this->isActionAllowed(self::ACTION_REMOVE))
		{
			throw new TasksException('Access denied or checklist item not found', TasksException::TE_ACTION_NOT_ALLOWED);
		}

		$arCurrentData = $this->getData();

		$rc = CheckListTable::delete($this->itemId);

		// Reset cache
		$this->resetCache();

		if (!$rc->isSuccess())
		{
			throw new TasksException('', TasksException::TE_ACTION_FAILED_TO_BE_PROCESSED);
		}

		// TODO: move search index update in afterTaskCheckListItemDelete event when it comes to life
		\Bitrix\Tasks\Internals\SearchIndex::setTaskSearchIndex($taskId);

		$occurAsUserId = CTasksTools::getOccurAsUserId();
		if (!$occurAsUserId)
		{
			$occurAsUserId = $executiveUserId;
		}

		// changes log
		$arLogFields = array(
			'TASK_ID'      => $taskId,
			'USER_ID'      => $occurAsUserId,
			'CREATED_DATE' => $curDatetime,
			'FIELD'        => 'CHECKLIST_ITEM_REMOVE',
			'FROM_VALUE'   => $arCurrentData['~TITLE'],
			'TO_VALUE'     => ''
		);

		$log = new CTaskLog();
		$log->Add($arLogFields);

		return true;
	}

	public function setSortIndex($sortIndex)
	{
		if (!$this->oTaskItem->isActionAllowed(CTaskItem::ACTION_EDIT))
		{
			throw new TasksException('', TasksException::TE_ACTION_NOT_ALLOWED);
		}

		$rc = CheckListTable::update(
			$this->itemId,
			array(
				"SORT_INDEX" => $sortIndex,
			)
		);

		if (!$rc->isSuccess())
			throw new TasksException('', TasksException::TE_SQL_ERROR);

		$this->resetCache();
	}

	/**
	 * Reorder item in checklist to position after some given item.
	 */
	public function moveAfterItem($itemId)
	{
		if (!$this->isActionAllowed(self::ACTION_REORDER))
		{
			throw new TasksException('', TasksException::TE_ACTION_NOT_ALLOWED);
		}

		$this->moveItem($this->getId(), $itemId);
	}

	private function moveItem($selectedItemId, $insertAfterItemId)
	{
		global $DB;

		$rc = $DB->Query(
			"SELECT ID, SORT_INDEX
			FROM b_tasks_checklist_items 
			WHERE TASK_ID = ".(int)$this->taskId."
			ORDER BY SORT_INDEX ASC, ID ASC
			",
			$bIgnoreErrors = true
		);

		if (!$rc)
			throw new TasksException('', TasksException::TE_SQL_ERROR);

		$arItems = array($selectedItemId => 0);    // by default to first position
		$prevItemId = 0;
		$sortIndex = 1;
		while ($arItem = $rc->fetch())
		{
			if ($insertAfterItemId == $prevItemId)
				$arItems[$selectedItemId] = $sortIndex++;

			if ($arItem['ID'] != $selectedItemId)
				$arItems[$arItem['ID']] = $sortIndex++;

			$prevItemId = $arItem['ID'];
		}

		if ($insertAfterItemId == $prevItemId)
			$arItems[$selectedItemId] = $sortIndex;

		if (!empty($arItems))
		{
			$sqlUpdate = "UPDATE b_tasks_checklist_items
				SET SORT_INDEX = CASE ID\n";

			foreach ($arItems as $id => $sortIndex)
			{
				$sqlUpdate .= "WHEN $id THEN $sortIndex\n";
			}

			$sqlUpdate .= "END\n"."WHERE ID IN (".implode(', ', array_keys($arItems)).")";

			$DB->Query($sqlUpdate);
		}

		foreach (\Bitrix\Main\EventManager::getInstance()->findEventHandlers(
			"tasks",
			"OnCheckListItemMoveItem"
		) as $event)
		{
			\ExecuteModuleEventEx($event, array($selectedItemId, $insertAfterItemId));
		}
	}

	final protected function fetchListFromDb($taskData, $arOrder = array('SORT_INDEX' => 'asc', 'ID' => 'asc'))
	{
		CTaskAssert::assertLaxIntegers($taskData['ID']);

		if (!isset($arOrder))
			$arOrder = array('SORT_INDEX' => 'asc', 'ID' => 'asc');

		global $DB;

		if (is_array($arOrder) && !empty($arOrder))
		{
			if (!self::checkFieldsForSort($arOrder))
				throw new TasksException('', TasksException::TE_WRONG_ARGUMENTS);

			$sqlOrder = array();
			foreach ($arOrder as $fld => $way)
			{
				$sqlOrder[] = $fld.' '.$way;
			}
			$sqlOrder = 'ORDER BY '.implode(', ', $sqlOrder);
		}
		else
			$sqlOrder = '';

		$rc = $DB->Query(
			"SELECT ID, CREATED_BY, TASK_ID, TITLE, IS_COMPLETE, SORT_INDEX, ".
			$DB->DateToCharFunction("TOGGLED_DATE", "FULL").
			" AS TOGGLED_DATE , TOGGLED_BY
				FROM b_tasks_checklist_items 
				WHERE TASK_ID = ".
			(int)$taskData['ID'].
			' '.
			$sqlOrder,
			$bIgnoreErrors = true
		);

		if (!$rc)
			throw new \Bitrix\Main\SystemException();

		$arItemsData = array();
		while ($arItemData = $rc->fetch())
		{
			$arItemsData[] = $arItemData;
		}

		return (array($arItemsData, $rc));
	}

	public static function checkFieldsForSort($arOrder)
	{
		global $APPLICATION;

		$bErrorsFound = false;
		$arErrorsMsgs = array();

		$allowedSortFields = array(
			'SORT_INDEX',
			'ID',
			'TITLE',
			'IS_COMPLETE',
			'CREATED_BY',
			'TASK_ID',
			'TOGGLED_BY',
			'TOGGLED_DATE'
		);
		foreach ($arOrder as $fld => $way)
		{
			if (!in_array($fld, $allowedSortFields))
			{
				$bErrorsFound = true;
				$arErrorsMsgs[] = array(
					'text' => GetMessage('TASKS_CHECKLISTITEM_UNKNOWN_FIELD'),
					'id'   => 'ERROR_TASKS_CHECKLISTITEM_UNKNOWN_FIELD'
				);
			}

			$way = ToLower($way);
			if ($way != 'desc' && $way != 'asc')
			{
				$bErrorsFound = true;
				$arErrorsMsgs[] = array(
					'text' => GetMessage('TASKS_CHECKLISTITEM_BAD_SORT_DIRECTION'),
					'id'   => 'ERROR_TASKS_CHECKLISTITEM_BAD_SORT_DIRECTION'
				);
			}
		}

		if ($bErrorsFound)
		{
			$e = new CAdminException($arErrorsMsgs);
			$APPLICATION->ThrowException($e);
		}

		$isAllRight = !$bErrorsFound;

		return ($isAllRight);
	}

	final protected function fetchDataFromDb($taskId, $itemId)
	{
		global $DB;

		$rc = $DB->Query(
			"SELECT ID, CREATED_BY, TASK_ID, TITLE, IS_COMPLETE, SORT_INDEX
				FROM b_tasks_checklist_items 
				WHERE ID = ".(int)$itemId." AND TASK_ID = ".(int)$taskId,
			$bIgnoreErrors = true
		);

		if ($rc && ($arItemData = $rc->fetch()))
			return ($arItemData);
		else
			throw new \Bitrix\Main\SystemException();
	}
}
