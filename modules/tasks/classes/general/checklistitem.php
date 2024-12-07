<?php
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

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Db\SqlQueryException;
use Bitrix\Main\EventManager;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\ObjectException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\CheckList\CheckListFacade;
use Bitrix\Tasks\CheckList\Internals\CheckList;
use Bitrix\Tasks\CheckList\Task\TaskCheckListFacade;

/**
 * Class CTaskCheckListItem
 *
 * @deprecated
 *
 * @see TaskCheckListFacade
 * @see CheckList
 */
final class CTaskCheckListItem extends CTaskSubItemAbstract
{
	const /** @noinspection PhpUnused */ ACTION_ADD = 0x01;
	const ACTION_MODIFY = 0x02;
	const ACTION_REMOVE = 0x03;
	const ACTION_TOGGLE = 0x04;
	const /** @noinspection PhpUnused */ ACTION_REORDER = 0x05;

	/**
	 * @param array $parameters
	 * @return array
	 * @throws TasksException
	 *
	 * @deprecated
	 *
	 * @see TaskCheckListFacade::getList()
	 */
	public static function getList(array $parameters = [])
	{
		$select = (array_key_exists('select', $parameters)? $parameters['select'] : []);
		$filter = (array_key_exists('filter', $parameters)? $parameters['filter'] : []);
		$order = (array_key_exists('order', $parameters)? $parameters['order'] : []);

		try
		{
			return TaskCheckListFacade::getList($select, $filter, $order);
		}
		catch (Exception $e)
		{
			/** @noinspection PhpDeprecationInspection */
			throw new TasksException($e->getMessage(), TasksException::TE_ACTION_FAILED_TO_BE_PROCESSED);
		}
	}

	/**
	 * @param $itemId
	 * @return bool
	 * @throws SystemException
	 * @throws ArgumentException
	 * @throws NotImplementedException
	 *
	 * @deprecated
	 *
	 * @see TaskCheckListFacade::getList()
	 */
	public static function getTaskIdByItemId($itemId)
	{
		$itemId = (int)$itemId;

		if (!$itemId)
		{
			return false;
		}

		$item = TaskCheckListFacade::getList(['TASK_ID'], ['ID' => $itemId])[$itemId];

		return $item['TASK_ID'];
	}

	/**
	 * @param CTaskItemInterface $task (of class CTaskItem,checklist item will be added to this task)
	 * @param array $fields with mandatory element TITLE (string).
	 *
	 * @return CTaskCheckListItem
	 * @throws ArgumentException
	 * @throws NotImplementedException
	 * @throws ObjectException
	 * @throws SqlQueryException
	 * @throws SystemException
	 * @throws TasksException
	 *
	 * @deprecated
	 *
	 * @see TaskCheckListFacade::add()
	 */
	public static function add(CTaskItemInterface $task, $fields)
	{
		$taskId = $task->getId();
		$userId = $task->getExecutiveUserId();

		/** @noinspection PhpDeprecationInspection */
		$fields = static::fillFieldsForCompatibility($taskId, $userId, $fields);

		$addResult = TaskCheckListFacade::add($taskId, $userId, $fields);
		if (!$addResult->isSuccess() && $addResult->getErrors())
		{
			/** @noinspection PhpDeprecationInspection */
			throw new TasksException(
				$addResult->getErrors()->getMessages()[0],
				TasksException::TE_ACTION_FAILED_TO_BE_PROCESSED
			);
		}

		/** @var CheckList $newItem */
		$newItem = $addResult->getData()['ITEM'];
		$newItemId = $newItem->getFields()['ID'];

		/** @noinspection PhpDeprecationInspection */
		return new self($task, $newItemId);
	}

	/**
	 * @param $taskId
	 * @param $userId
	 * @param $fields
	 * @return array
	 * @throws ArgumentException
	 * @throws NotImplementedException
	 * @throws ObjectException
	 * @throws SqlQueryException
	 * @throws SystemException
	 */
	private static function fillFieldsForCompatibility($taskId, $userId, $fields)
	{
		$newFields = $fields;

		if (!isset($newFields['PARENT_ID'], $newFields['SORT_INDEX']))
		{
			$items = TaskCheckListFacade::getList(['ID', 'PARENT_ID', 'SORT_INDEX'], ['TASK_ID' => $taskId]);

			if (!isset($newFields['PARENT_ID']))
			{
				/** @noinspection PhpDeprecationInspection */
				$newFields['PARENT_ID'] = static::getFirstCheckListId($taskId, $userId, $items);
			}

			if (!isset($newFields['SORT_INDEX']))
			{
				/** @noinspection PhpDeprecationInspection */
				$newFields['SORT_INDEX'] = static::getNextSortIndex($items, $newFields['PARENT_ID']);
			}
		}

		return $newFields;
	}

	/**
	 * @param $taskId
	 * @param $userId
	 * @param array $items
	 * @return false|int|string
	 * @throws ArgumentException
	 * @throws NotImplementedException
	 * @throws ObjectException
	 * @throws ObjectPropertyException
	 * @throws SqlQueryException
	 * @throws SystemException
	 */
	private static function getFirstCheckListId($taskId, $userId, $items)
	{
		if (empty($items))
		{
			/** @noinspection PhpDeprecationInspection */
			$firstCheckListId = static::createFirstCheckList($taskId, $userId)->getFields()['ID'];
		}
		else
		{
			$arrayStructuredRoots = TaskCheckListFacade::getArrayStructuredRoots($items);
			$sortIndexes = array_column($arrayStructuredRoots, 'SORT_INDEX', 'ID');

			$firstCheckListId = array_search(min($sortIndexes), $sortIndexes, true);
		}

		return $firstCheckListId;
	}

	/**
	 * @param $taskId
	 * @param $userId
	 * @return CheckList
	 * @throws ArgumentException
	 * @throws NotImplementedException
	 * @throws ObjectException
	 * @throws SqlQueryException
	 * @throws SystemException
	 * @throws ObjectPropertyException
	 */
	private static function createFirstCheckList($taskId, $userId)
	{
		/** @var CheckListFacade $facade */
		$facade = TaskCheckListFacade::class;
		$checkList = new CheckList(0, $userId, $facade, [
			'ENTITY_ID' => $taskId,
			'CREATED_BY' => $userId,
			'TITLE' => 'BX_CHECKLIST_1',
			'PARENT_ID' => 0,
			'SORT_INDEX' => 0,
		]);
		$checkListSaveResult = $checkList->save();

		/** @var CheckList $checkList */
		$checkList = $checkListSaveResult->getData()['ITEM'];

		return $checkList;
	}

	/**
	 * @param array $items
	 * @param $parentId
	 * @return int
	 */
	private static function getNextSortIndex($items, $parentId)
	{
		$neighbours = array_filter(
			$items,
			static function ($item) use ($parentId)
			{
				return (int)$item['PARENT_ID'] === $parentId;
			}
		);
		$sortIndexes = array_column($neighbours, 'SORT_INDEX');

		return (empty($sortIndexes) ? 0 : (int)max($sortIndexes)) + 1;
	}

	/**
	 * @param $fields
	 * @throws ArgumentException
	 * @throws NotImplementedException
	 * @throws ObjectException
	 * @throws SystemException
	 * @throws TasksException
	 *
	 * @deprecated
	 *
	 * @see TaskCheckListFacade::update()
	 */
	public function update($fields)
	{
		$this->resetCache();

		$id = $this->getId();
		$userId = $this->getExecutiveUserId();
		$currentFields = TaskCheckListFacade::getList([], ['ID' => $id])[$id];

		/** @var TaskCheckListFacade $facade */
		$facade = TaskCheckListFacade::class;
		$checkList = new CheckList(0, $userId, $facade, $currentFields);
		$checkList->setFields($fields);

		$saveResult = $checkList->save();
		if (!$saveResult->isSuccess() && $saveResult->getErrors())
		{
			/** @noinspection PhpDeprecationInspection */
			throw new TasksException(
				$saveResult->getErrors()->getMessages()[0],
				TasksException::TE_ACTION_FAILED_TO_BE_PROCESSED
			);
		}
	}

	/**
	 * @return bool
	 * @throws ArgumentException
	 * @throws NotImplementedException
	 * @throws SystemException
	 * @throws TasksException
	 *
	 * @deprecated
	 *
	 * @see TaskCheckListFacade::delete()
	 */
	public function delete()
	{
		$this->resetCache();

		$taskId = $this->taskId;
		$userId = $this->getExecutiveUserId();

		/** @var TaskCheckListFacade $facade */
		$facade = TaskCheckListFacade::class;
		$checkList = new CheckList(0, $userId, $facade, ['ID' => $this->getId()]);

		$deleteResult = TaskCheckListFacade::delete($taskId, $userId, $checkList);
		if (!$deleteResult->isSuccess() && $deleteResult->getErrors())
		{
			/** @noinspection PhpDeprecationInspection */
			throw new TasksException(
				$deleteResult->getErrors()->getMessages()[0],
				TasksException::TE_ACTION_FAILED_TO_BE_PROCESSED
			);
		}

		return true;
	}

	/**
	 * Removes all checklist's items for given task.
	 * WARNING: This function doesn't check rights!
	 *
	 * @param integer $taskId
	 * @throws CTaskAssertException
	 * @throws TasksException
	 *
	 * @deprecated
	 *
	 * @see TaskCheckListFacade::deleteByEntityId()
	 * @see TaskCheckListFacade::deleteByEntityIdOnLowLevel()
	 */
	public static function deleteByTaskId($taskId)
	{
		CTaskAssert::assert(CTaskAssert::isLaxIntegers($taskId) && $taskId > 0);

		try
		{
			TaskCheckListFacade::deleteByEntityIdOnLowLevel($taskId);
		}
		catch (Exception $e)
		{
			/** @noinspection PhpDeprecationInspection */
			throw new TasksException($e->getMessage(), TasksException::TE_ACTION_FAILED_TO_BE_PROCESSED);
		}
	}

	/**
	 * @return bool
	 * @throws ArgumentException
	 * @throws NotImplementedException
	 * @throws ObjectException
	 * @throws SystemException
	 *
	 * @deprecated
	 *
	 * @see TaskCheckListFacade::complete()
	 */
	public function complete()
	{
		try
		{
			/** @noinspection PhpDeprecationInspection */
			$this->update(['IS_COMPLETE' => 'Y']);
		}
		/** @noinspection PhpDeprecationInspection */
		catch (TasksException $e)
		{
			return false;
		}

		return true;
	}

	/**
	 * @return bool
	 * @throws ArgumentException
	 * @throws NotImplementedException
	 * @throws ObjectException
	 * @throws SystemException
	 *
	 * @deprecated
	 *
	 * @see TaskCheckListFacade::renew()
	 */
	public function renew()
	{
		try
		{
			/** @noinspection PhpDeprecationInspection */
			$this->update(['IS_COMPLETE' => 'N']);
		}
		/** @noinspection PhpDeprecationInspection */
		catch (TasksException $e)
		{
			return false;
		}

		return true;
	}

	/**
	 * @param $sortIndex
	 * @throws ArgumentException
	 * @throws NotImplementedException
	 * @throws ObjectException
	 * @throws SystemException
	 * @throws TasksException
	 *
	 * @deprecated
	 *
	 * @see TaskCheckListFacade::update()
	 */
	public function setSortIndex($sortIndex)
	{
		/** @noinspection PhpDeprecationInspection */
		$this->update(['SORT_INDEX' => $sortIndex]);
	}

	/**
	 * Reorder item in checklist to position after some given item.
	 *
	 * @param $itemId
	 * @throws ArgumentException
	 * @throws NotImplementedException
	 * @throws SystemException
	 * @throws TasksException
	 *
	 * @deprecated
	 *
	 * @see TaskCheckListFacade::moveItem()
	 */
	public function moveAfterItem($itemId)
	{
		/** @noinspection PhpDeprecationInspection */
		$this->moveItem($this->getId(), $itemId);
	}

	/**
	 * @param $selectedItemId
	 * @param $insertAfterItemId
	 * @throws ArgumentException
	 * @throws NotImplementedException
	 * @throws SystemException
	 * @throws TasksException
	 *
	 * @deprecated
	 *
	 * @see TaskCheckListFacade::moveItem()
	 */
	private function moveItem($selectedItemId, $insertAfterItemId)
	{
		$taskId = $this->oTaskItem->getId();
		$userId = $this->getExecutiveUserId();
		$currentFields = TaskCheckListFacade::getList([], ['ID' => $selectedItemId])[$selectedItemId];

		/** @var TaskCheckListFacade $facade */
		$facade = TaskCheckListFacade::class;
		$checkList = new CheckList(0, $userId, $facade, $currentFields);

		$moveResult = TaskCheckListFacade::moveItem(
			$taskId,
			$userId,
			$checkList,
			$insertAfterItemId,
			TaskCheckListFacade::MOVING_POSITION_AFTER
		);
		if (!$moveResult->isSuccess() && $moveResult->getErrors())
		{
			/** @noinspection PhpDeprecationInspection */
			throw new TasksException(
				$moveResult->getErrors()->getMessages()[0],
				TasksException::TE_ACTION_FAILED_TO_BE_PROCESSED
			);
		}

		$eventManager = EventManager::getInstance();
		foreach ($eventManager->findEventHandlers("tasks", "OnCheckListItemMoveItem") as $event)
		{
			ExecuteModuleEventEx($event, [$selectedItemId, $insertAfterItemId]);
		}
	}

	/**
	 * @return mixed
	 * @throws TasksException
	 *
	 * @deprecated
	 *
	 * @see CheckList->fields->getTitle()
	 */
	public function getTitle()
	{
		$itemData = $this->getData();
		return ($itemData['TITLE']);
	}

	/**
	 * @return mixed
	 * @throws TasksException
	 *
	 * @deprecated
	 *
	 * @see CheckList->fields->getEntityId()
	 */
	public function getTaskId()
	{
		$itemData = $this->getData();
		return ($itemData['TASK_ID']);
	}

	/**
	 * @return bool true if complete, false otherwise
	 * @throws TasksException
	 *
	 * @deprecated
	 *
	 * @see CheckList->fields->getIsComplete()
	 */
	public function isComplete()
	{
		$itemsData = $this->getData();
		return $itemsData['IS_COMPLETE'] === 'Y';
	}

	/**
	 * @param $actionId
	 * @return bool
	 *
	 * @throws ArgumentException
	 * @throws NotImplementedException
	 * @throws SystemException
	 *
	 * @deprecated
	 *
	 * @see TaskCheckListFacade::isActionAllowed()
	 */
	public function isActionAllowed($actionId)
	{
		CTaskAssert::assertLaxIntegers($actionId);

		$id = $this->getId();
		$userId = $this->getExecutiveUserId();
		$currentFields = TaskCheckListFacade::getList([], ['ID' => $id])[$id];

		/** @var TaskCheckListFacade $facade */
		$facade = TaskCheckListFacade::class;
		$checkList = new CheckList(0, $userId, $facade, $currentFields);

		return TaskCheckListFacade::isActionAllowed($this->taskId, $checkList, $userId, $actionId);
	}

	/**
	 * @param array $fields
	 * @param bool $checkForAdd
	 * @return bool
	 *
	 * @deprecated
	 *
	 * @see TaskCheckListFacade::checkFields()
	 */
	private static function checkFields($fields, $checkForAdd)
	{
		/** @noinspection PhpVariableNamingConventionInspection */
		global $APPLICATION;

		$errors = [];

		if ($checkForAdd)
		{
			$checkResult = TaskCheckListFacade::checkFieldsForAdd($fields);
		}
		else
		{
			$checkResult = TaskCheckListFacade::checkFieldsForUpdate($fields);
		}

		if (!$checkResult->isSuccess())
		{
			/** @var \Bitrix\Main\Error $error */
			foreach ($checkResult->getErrors() as $error)
			{
				$errors[] = [
					'id' => $error->getCode(),
					'text' => $error->getMessage(),
				];
			}

			if (!empty($errors))
			{
				$e = new CAdminException($errors);
				$APPLICATION->ThrowException($e);
			}
		}

		return empty($errors);
	}

	/** @noinspection PhpUnused */
	/**
	 * @param array $fields
	 * @return bool
	 *
	 * @deprecated
	 *
	 * @see TaskCheckListFacade::checkFieldsForAdd()
	 */
	public static function checkFieldsForAdd($fields)
	{
		/** @noinspection PhpDeprecationInspection */
		return self::checkFields($fields, true);
	}

	/** @noinspection PhpUnused */
	/**
	 * @param array $fields
	 * @return bool
	 *
	 * @deprecated
	 *
	 * @see TaskCheckListFacade::checkFieldsForUpdate()
	 */
	public static function checkFieldsForUpdate($fields)
	{
		/** @noinspection PhpDeprecationInspection */
		return (self::checkFields($fields, false));
	}

	/**
	 * @param array $order
	 * @return bool
	 *
	 * @deprecated
	 */
	public static function checkFieldsForSort($order)
	{
		/** @noinspection PhpVariableNamingConventionInspection */
		global $APPLICATION;

		$errors = [];
		$errorsFound = false;

		$allowedSortFields = [
			'ID',
			'TASK_ID',
			'CREATED_BY',
			'PARENT_ID',
			'TITLE',
			'SORT_INDEX',
			'IS_COMPLETE',
			'IS_IMPORTANT',
			'TOGGLED_BY',
			'TOGGLED_DATE'
		];

		foreach ($order as $field => $sort)
		{
			if (!in_array($field, $allowedSortFields, true))
			{
				$errorsFound = true;
				$errors[] = [
					'id' => 'ERROR_TASKS_CHECKLISTITEM_UNKNOWN_FIELD',
					'text' => GetMessage('TASKS_CHECKLISTITEM_UNKNOWN_FIELD'),
				];
			}

			$sort = mb_strtolower($sort);
			if ($sort !== 'desc' && $sort !== 'asc')
			{
				$errorsFound = true;
				$errors[] = [
					'id' => 'ERROR_TASKS_CHECKLISTITEM_BAD_SORT_DIRECTION',
					'text' => GetMessage('TASKS_CHECKLISTITEM_BAD_SORT_DIRECTION'),
				];
			}
		}

		if ($errorsFound)
		{
			$e = new CAdminException($errors);
			$APPLICATION->ThrowException($e);
		}

		return !$errorsFound;
	}

	/**
	 * @param $executiveUserId
	 * @param $methodName
	 * @param $args
	 * @param $navigation
	 * @return array
	 * @throws ArgumentException
	 * @throws CTaskAssertException
	 * @throws NotImplementedException
	 * @throws ObjectException
	 * @throws SqlQueryException
	 * @throws SystemException
	 * @throws TasksException
	 *
	 * @deprecated
	 */
	public static function runRestMethod($executiveUserId, $methodName, $args,
		/** @noinspection PhpUnusedParameterInspection */ $navigation)
	{
		static $manifest = null;
		static $methodsMetaInfo = null;

		if ($manifest === null)
		{
			/** @noinspection PhpDeprecationInspection */
			$manifest = self::getManifest();
			$methodsMetaInfo = $manifest['REST: available methods'];
		}

		// Check and parse params
		CTaskAssert::assert(isset($methodsMetaInfo[$methodName]));
		$currentMethodMetaInfo = $methodsMetaInfo[$methodName];
		$parsedArguments = CTaskRestService::_parseRestParams('ctaskchecklistitem', $methodName, $args);

		$returnValue = null;

		if (isset($currentMethodMetaInfo['staticMethod']) && $currentMethodMetaInfo['staticMethod'])
		{
			if ($methodName === 'add')
			{
				list($taskId, $fields) = $parsedArguments;
				$task = CTaskItem::getInstance($taskId, $executiveUserId);
				/** @noinspection PhpDeprecationInspection */
				$checkListItem = self::add($task, $fields);

				$returnValue = $checkListItem->getId();
			}
			elseif ($methodName === 'getlist')
			{
				list($taskId, $order) = $parsedArguments;
				$task = CTaskItem::getInstance($taskId, $executiveUserId);
				/** @noinspection PhpDeprecationInspection */
				/** @noinspection PhpUnusedLocalVariableInspection */
				list($checkListItems, $checkListItemsResult) = self::fetchList($task, $order);

				$returnValue = [];
				foreach ($checkListItems as $checkListItem)
				{
					/** @var self $checkListItem */
					$returnValue[] = $checkListItem->getData(false);
				}
			}
			else
			{
				$returnValue = call_user_func_array(['self', $methodName], $parsedArguments);
			}
		}
		else
		{
			$taskId = array_shift($parsedArguments);
			$checkListId = array_shift($parsedArguments);
			$task = CTaskItem::getInstance($taskId, $executiveUserId);
			/** @noinspection PhpDeprecationInspection */
			$checkListItem = new self($task, $checkListId);

			if ($methodName === 'get')
			{
				$returnValue = $checkListItem->getData();
				$returnValue['TITLE'] = htmlspecialcharsback($returnValue['TITLE']);
			}
			else
			{
				$returnValue = call_user_func_array([$checkListItem, $methodName], $parsedArguments);
			}
		}

		return ([$returnValue, null]);
	}

	/**
	 * This method is not part of public API.
	 * Its purpose is for internal use only.
	 * It can be changed without any notifications
	 *
	 * @access private
	 *
	 * @deprecated
	 */
	public static function getManifest()
	{
		$allKeys = [
			0 => [], // readableKeys
			1 => [], // writableKeys
			2 => [], // sortableKeys
			3 => [], // filterableKeys
			4 => [], // dateKeys
		];

		foreach ($allKeys as $index => $keys)
		{
			/** @noinspection PhpDeprecationInspection */
			foreach ($fieldMap = static::getPublicFieldMap() as $fieldName => $field)
			{
				if ($field[$index] === 1)
				{
					$allKeys[$index][] = $fieldName;
				}
			}
		}

		list($readableKeys, $writableKeys, $sortableKeys, $dateKeys) = $allKeys;

		return [
			'Manifest version'                         => '2.0',
			'Warning'                                  => 'don\'t rely on format of this manifest, it can be changed without any notification',
			'REST: shortname alias to class'           => 'checklistitem',
			'REST: writable checklistitem data fields' => $writableKeys,
			'REST: readable checklistitem data fields' => $readableKeys,
			'REST: sortable checklistitem data fields' => $sortableKeys,
			'REST: date fields'                        => $dateKeys,
			'REST: available methods' => [
				'getmanifest'     => [
					'staticMethod' => true,
					'params'       => [],
				],
				'get'             => [
					'mandatoryParamsCount'     => 2,
					'params'                   => [
						[
							'description' => 'taskId',
							'type'        => 'integer',
						],
						[
							'description' => 'itemId',
							'type'        => 'integer',
						],
					],
					'allowedKeysInReturnValue' => $readableKeys,
				],
				'getlist'         => [
					'staticMethod'             => true,
					'mandatoryParamsCount'     => 1,
					'params'                   => [
						[
							'description' => 'taskId',
							'type'        => 'integer',
						],
						[
							'description' => 'arOrder',
							'type'        => 'array',
							'allowedKeys' => $sortableKeys,
						],
					],
					'allowedKeysInReturnValue' => $readableKeys,
					'collectionInReturnValue'  => true,
				],
				'add'             => [
					'staticMethod'         => true,
					'mandatoryParamsCount' => 2,
					'params'               => [
						[
							'description' => 'taskId',
							'type'        => 'integer',
						],
						[
							'description' => 'arFields',
							'type'        => 'array',
							'allowedKeys' => $writableKeys,
						],
					],
				],
				'update'          => [
					'staticMethod'         => false,
					'mandatoryParamsCount' => 3,
					'params'               => [
						[
							'description' => 'taskId',
							'type'        => 'integer',
						],
						[
							'description' => 'itemId',
							'type'        => 'integer',
						],
						[
							'description' => 'arFields',
							'type'        => 'array',
							'allowedKeys' => $writableKeys,
						],
					],
				],
				'delete'          => [
					'staticMethod'         => false,
					'mandatoryParamsCount' => 2,
					'params'               => [
						[
							'description' => 'taskId',
							'type'        => 'integer',
						],
						[
							'description' => 'itemId',
							'type'        => 'integer',
						],
					],
				],
				'complete'        => [
					'staticMethod'         => false,
					'mandatoryParamsCount' => 2,
					'params'               => [
						[
							'description' => 'taskId',
							'type'        => 'integer',
						],
						[
							'description' => 'itemId',
							'type'        => 'integer',
						],
					],
				],
				'renew'           => [
					'staticMethod'         => false,
					'mandatoryParamsCount' => 2,
					'params'               => [
						[
							'description' => 'taskId',
							'type'        => 'integer',
						],
						[
							'description' => 'itemId',
							'type'        => 'integer',
						],
					],
				],
				'moveafteritem'   => [
					'staticMethod'         => false,
					'mandatoryParamsCount' => 3,
					'params'               => [
						[
							'description' => 'taskId',
							'type'        => 'integer',
						],
						[
							'description' => 'itemId',
							'type'        => 'integer',
						],
						[
							'description' => 'afterItemId',
							'type'        => 'integer',
						],
					],
				],
				'isactionallowed' => [
					'staticMethod'         => false,
					'mandatoryParamsCount' => 3,
					'params'               => [
						[
							'description' => 'taskId',
							'type'        => 'integer',
						],
						[
							'description' => 'itemId',
							'type'        => 'integer',
						],
						[
							'description' => 'actionId',
							'type'        => 'integer',
						],
					],
				],
			],
		];
	}

	/**
	 * @return array
	 *
	 * @deprecated
	 */
	public static function getPublicFieldMap()
	{
		// READ, WRITE, SORT, FILTER, DATE
		return [
			'ID' => [1, 0, 1, 0, 0],
			'TASK_ID' => [1, 0, 0, 0, 0],
			'PARENT_ID' => [1, 1, 1, 0, 0],
			'CREATED_BY' => [1, 0, 1, 0, 0],
			'TITLE' => [1, 1, 1, 0, 0],
			'SORT_INDEX' => [1, 1, 1, 0, 0],
			'IS_COMPLETE' => [1, 1, 1, 0, 0],
			'IS_IMPORTANT' => [1, 1, 1, 0, 0],
			'TOGGLED_BY' => [1, 0, 1, 0, 0],
			'TOGGLED_DATE' => [1, 0, 1, 1, 1],
			'MEMBERS' => [1, 1, 0, 0, 0],
			'ATTACHMENTS' => [1, 0, 0, 0, 0],
		];
	}

	/**
	 * @param $taskData
	 * @param array $order
	 * @return array
	 * @throws ArgumentException
	 * @throws NotImplementedException
	 * @throws SystemException
	 * @throws TasksException
	 *
	 * @deprecated
	 */
	protected static function fetchListFromDb($taskData, $order = ['SORT_INDEX' => 'asc', 'ID' => 'asc'])
	{
		$taskId = $taskData['ID'];

		CTaskAssert::assertLaxIntegers($taskId);

		if (!isset($order) || !is_array($order))
		{
			$order = ['SORT_INDEX' => 'asc', 'ID' => 'asc'];
		}

		/** @noinspection PhpDeprecationInspection */
		if (is_array($order) && !empty($order) && !self::checkFieldsForSort($order))
		{
			/** @noinspection PhpDeprecationInspection */
			throw new TasksException('', TasksException::TE_WRONG_ARGUMENTS);
		}

		$items = TaskCheckListFacade::getList([], ['TASK_ID' => $taskId], $order);

		/** @noinspection PhpUndefinedClassInspection */
		$dbResult = new CDBResult();
		$dbResult->InitFromArray($items);

		return [$items, $dbResult];
	}

	/**
	 * @param $taskId
	 * @param $itemId
	 * @return mixed
	 * @throws ArgumentException
	 * @throws NotImplementedException
	 * @throws SystemException
	 *
	 * @deprecated
	 */
	protected static function fetchDataFromDb($taskId, $itemId)
	{
		$itemData = TaskCheckListFacade::getList([], ['ID' => $itemId, 'TASK_ID' => $taskId])[$itemId];

		if (!$itemData)
		{
			throw new SystemException();
		}

		return $itemData;
	}
}
