<?php

namespace Bitrix\Tasks\CheckList;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Db\SqlQueryException;
use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\Query\Join;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\ObjectException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\CheckList\Internals\CheckList;
use Bitrix\Tasks\CheckList\Internals\CheckListTree;
use Bitrix\Tasks\Integration\Disk\Rest\Attachment;
use Bitrix\Tasks\Ui\Avatar;
use Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit\TaskLimit;
use Bitrix\Tasks\Util\Result;
use Bitrix\Tasks\Util\User;
use Bitrix\Tasks\Util\UserField;
use Exception;

Loc::loadMessages(__FILE__);

/**
 * Class CheckListFacade
 *
 * @package Bitrix\Tasks\CheckList
 */
abstract class CheckListFacade
{
	public const TASK_ADD_CONTEXT = 'TASK_ADD';
	const ACTION_ADD = 0x01;
	const ACTION_MODIFY = 0x02;
	const ACTION_REMOVE = 0x03;
	const ACTION_TOGGLE = 0x04;
	const ACTION_REORDER = 0x05;

	const ACTIONS = [
		'COMMON' => [
			self::ACTION_ADD => 'ACTION_ADD',
			self::ACTION_REORDER => 'ACTION_REORDER',
		],
		'ITEM' => [
			self::ACTION_MODIFY => 'ACTION_MODIFY',
			self::ACTION_REMOVE => 'ACTION_REMOVE',
			self::ACTION_TOGGLE => 'ACTION_TOGGLE',
		],
	];

	const MOVING_POSITION_BEFORE = 'before';
	const MOVING_POSITION_AFTER = 'after';

	const MEMBER_ACCOMPLICE = 'A';
	const MEMBER_AUDITOR = 'U';

	public static $entityIdName = '';
	public static $userFieldsEntityIdName = '';
	public static $commonAccessActions = [];
	public static $itemAccessActions = [];

	protected static $nodeId = 0;
	protected static $deferredActionsMode = false;
	protected static $locPrefix = 'TASKS_CHECKLIST_FACADE_';

	protected static $selectFields = [];
	protected static $filterFields = [];
	protected static $orderFields = [];
	protected static $memberFields = [
		'USER_ID',
		'USER_TYPE',
		'USER_NAME',
		'USER_LAST_NAME',
		'USER_SECOND_NAME',
		'USER_TITLE',
		'USER_LOGIN',
		'USER_PERSONAL_PHOTO',
	];

	public static $oldItemsToMerge = [];

	/**
	 * Returns class that extends abstract class CheckListTree.
	 * @see CheckListTree
	 *
	 * @return string
	 * @throws NotImplementedException
	 */
	public static function getCheckListTree()
	{
		throw new NotImplementedException('Default checklist tree class doesnt exist');
	}

	/**
	 * Returns table class for checklist table.
	 *
	 * @return string
	 * @throws NotImplementedException
	 */
	public static function getCheckListDataController()
	{
		throw new NotImplementedException('Default checklist table class doesnt exist');
	}

	/**
	 * Returns table class for checklist tree table.
	 *
	 * @return string
	 * @throws NotImplementedException
	 */
	public static function getCheckListTreeDataController()
	{
		throw new NotImplementedException('Default checklist tree table class doesnt exist');
	}

	/**
	 * Returns table class for checklist member table.
	 *
	 * @return string
	 * @throws NotImplementedException
	 */
	public static function getCheckListMemberDataController()
	{
		throw new NotImplementedException('Default checklist member table class doesnt exist');
	}

	/**
	 * Returns current state of deferred actions mode.
	 *
	 * @return bool
	 */
	protected static function getDeferredActionsMode()
	{
		return static::$deferredActionsMode;
	}

	/**
	 * Sets deferred actions mode to true.
	 */
	protected static function enableDeferredActionsMode()
	{
		static::$deferredActionsMode = true;
	}

	/**
	 * Sets deferred actions mode to false.
	 */
	protected static function disableDeferredActionsMode()
	{
		static::$deferredActionsMode = false;
	}

	/**
	 * @param array $select
	 * @param array $filter
	 * @param array $order
	 * @return array
	 * @throws ArgumentException
	 * @throws NotImplementedException
	 * @throws SystemException
	 */
	public static function getList(array $select = [], array $filter = [], array $order = [])
	{
		[$filteredSelect, $filteredFilter, $filteredOrder] = static::getFilteredFields($select, $filter, $order);

		/** @var DataManager $checkListDataController */
		$checkListDataController = static::getCheckListDataController();

		$query = new Query($checkListDataController::getEntity());
		$query->setSelect($filteredSelect);
		$query->setFilter($filteredFilter);
		$query->setOrder($filteredOrder);
		$query->registerRuntimeField('', new ReferenceField(
			'IT',
			static::getCheckListTreeDataController(),
			Join::on('this.ID', 'ref.CHILD_ID')->where('ref.LEVEL', 1),
			['join_type' => 'LEFT']
		));

		$checkListMemberDataController = static::getCheckListMemberDataController();
		if ($checkListMemberDataController)
		{
			$query->registerRuntimeField('', new ReferenceField(
				'IM',
				$checkListMemberDataController,
				Join::on('this.ID', 'ref.ITEM_ID'),
				['join_type' => 'LEFT']
			));
		}

		$res = $query->exec();

		$items = [];
		while ($item = $res->fetch())
		{
			$itemId = $item['ID'];

			$item = static::processItemMembers($item, $items, $select);
			$item = static::processItemAttachments($item, $select);
			$item = static::processItemCommons($item, $select);

			$items[$itemId] = $item;
		}

		return $items;
	}

	/**
	 * @param int $entityId
	 * @return array
	 * @throws ArgumentException
	 * @throws NotImplementedException
	 * @throws SystemException
	 */
	public static function getByEntityId($entityId)
	{
		return static::getList([], [static::$entityIdName => $entityId]);
	}

	/**
	 * @param int $entityId
	 * @param int $userId
	 * @param array $fields
	 * @return Result
	 * @throws ArgumentException
	 * @throws NotImplementedException
	 * @throws ObjectException
	 * @throws ObjectPropertyException
	 * @throws SqlQueryException
	 * @throws SystemException
	 */
	public static function add($entityId, $userId, $fields)
	{
		$addResult = new Result();

		if (!static::checkAccess($entityId, $userId, ActionDictionary::ACTION_CHECKLIST_ADD, $fields))
		{
			$code = 'ACTION_NOT_ALLOWED';
			$addResult = static::addErrorToResult($addResult, $code, self::ACTION_ADD);
			return $addResult;
		}

		$fieldsChecking = static::checkFieldsForAdd($fields);
		if (!$fieldsChecking->isSuccess())
		{
			$addResult->loadErrors($fieldsChecking->getErrors());
			return $addResult;
		}

		/** @var static $facade */
		$facade = static::class;
		$fields['ENTITY_ID'] = $entityId;

		$newCheckList = new CheckList(0, $userId, $facade, $fields);
		$addResult = $newCheckList->save();

		return $addResult;
	}

	/**
	 * @param int $entityId
	 * @param int $userId
	 * @param CheckList $checkList
	 * @param $fields
	 * @return Result
	 * @throws ArgumentException
	 * @throws NotImplementedException
	 * @throws ObjectException
	 * @throws ObjectPropertyException
	 * @throws SqlQueryException
	 * @throws SystemException
	 */
	public static function update($entityId, $userId, CheckList $checkList, $fields)
	{
		$updateResult = new Result();

		$code = 'ACTION_NOT_ALLOWED';

		if (
			array_key_exists('IS_COMPLETE', $fields)
			&& count($fields) === 1
		)
		{
			if (!static::checkAccess($entityId, $userId, ActionDictionary::ACTION_CHECKLIST_TOGGLE, $checkList))
			{
				$updateResult = static::addErrorToResult($updateResult, $code, self::ACTION_TOGGLE);
				return $updateResult;
			}
			$checkList->setSkipMembers();
		}
		else if (!static::checkAccess($entityId, $userId, ActionDictionary::ACTION_CHECKLIST_EDIT, $checkList))
		{
			$updateResult = static::addErrorToResult($updateResult, $code, self::ACTION_MODIFY);
			return $updateResult;
		}

		$fieldsChecking = static::checkFieldsForUpdate($fields);
		if (!$fieldsChecking->isSuccess())
		{
			$updateResult->loadErrors($fieldsChecking->getErrors());
			return $updateResult;
		}

		$checkList->setFields($fields);
		$updateResult = $checkList->save();

		static::onAfterUpdate($entityId);
		return $updateResult;
	}

	/**
	 * @param int $entityId
	 * @param int $userId
	 * @param CheckList $checkList
	 * @param array $fields
	 * @return Result
	 * @throws ArgumentException
	 * @throws NotImplementedException
	 * @throws ObjectException
	 * @throws ObjectPropertyException
	 * @throws SqlQueryException
	 * @throws SystemException
	 */
	public static function updateComposite(int $entityId, int $userId, CheckList $checkList, array $fields): Result
	{
		$updateCompositeResult = new Result();

		foreach ($checkList->getDescendants() as $descendant)
		{
			$updateCompositeResult->setData(
				array_merge(
					($updateCompositeResult->getData() ?? []),
					static::updateComposite($entityId, $userId, $descendant, $fields)->getData()
				)
			);
		}

		if (
			array_key_exists('IS_COMPLETE', $fields)
			&& count($fields) === 1
			&& static::checkAccess($entityId, $userId, ActionDictionary::ACTION_CHECKLIST_TOGGLE, $checkList)
		)
		{
			$updateResult = static::update($entityId, $userId, $checkList, $fields);
			if ($updateResult->isSuccess())
			{
				$updateCompositeResult->setData(
					array_merge(
						($updateCompositeResult->getData() ?? []),
						[$updateResult]
					)
				);
			}
		}

		return $updateCompositeResult;
	}

	/**
	 * @param int $entityId
	 * @param int $userId
	 * @param CheckList $checkList
	 * @return Result|bool
	 * @throws ArgumentException
	 * @throws NotImplementedException
	 * @throws SystemException
	 */
	public static function delete($entityId, $userId, $checkList)
	{
		$deleteResult = new Result();

		$items = static::getByEntityId($entityId);
		$itemsTree = static::getObjectStructuredRoots($items, $entityId, $userId);

		$id = $checkList->getFields()['ID'];
		$checkListWithSubTree = null;

		/** @var CheckList $item */
		foreach ($itemsTree as $item)
		{
			if ($checkListWithSubTree = $item->findById($id))
			{
				break;
			}
		}

		if ($checkListWithSubTree !== null)
		{
			$deleteCompositeResult = static::deleteComposite($entityId, $userId, $checkListWithSubTree);
			if (!$deleteCompositeResult->isSuccess())
			{
				$deleteResult->loadErrors($deleteCompositeResult->getErrors());
				return $deleteResult;
			}
		}

		return $deleteResult;
	}

	/**
	 * @param int $entityId
	 * @param int $userId
	 * @param CheckList $checkList
	 * @return Result
	 * @throws ArgumentException
	 * @throws NotImplementedException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public static function deleteComposite($entityId, $userId, $checkList)
	{
		$deleteCompositeResult = new Result();

		foreach ($checkList->getDescendants() as $descendant)
		{
			$deleteCompositeResult->loadErrors(static::deleteComposite($entityId, $userId, $descendant)->getErrors());
			if (!$deleteCompositeResult->isSuccess())
			{
				return $deleteCompositeResult;
			}
		}

		$deleteLeafResult = static::deleteLeaf($entityId, $userId, $checkList);
		if (!$deleteLeafResult->isSuccess())
		{
			$deleteCompositeResult->loadErrors($deleteLeafResult->getErrors());
		}

		return $deleteCompositeResult;
	}

	/**
	 * @param int $entityId
	 * @param int $userId
	 * @param CheckList $checkList
	 * @return Result
	 * @throws ArgumentException
	 * @throws NotImplementedException
	 * @throws SystemException
	 * @throws ObjectPropertyException
	 * @throws Exception
	 */
	public static function deleteLeaf($entityId, $userId, $checkList)
	{
		/** @noinspection PhpVariableNamingConventionInspection */
		global $USER_FIELD_MANAGER;

		$deleteLeafResult = new Result();

		if (!static::checkAccess($entityId, $userId, ActionDictionary::ACTION_CHECKLIST_EDIT, $checkList))
		{
			$code = 'ACTION_NOT_ALLOWED';
			$deleteLeafResult = static::addErrorToResult($deleteLeafResult, $code, self::ACTION_REMOVE);
			return $deleteLeafResult;
		}

		$id = $checkList->getFields()['ID'];

		try
		{
			if (static::$userFieldsEntityIdName)
			{
				$USER_FIELD_MANAGER->Delete(static::$userFieldsEntityIdName, $id);
			}
		}
		catch (Exception $exception)
		{
			$deleteLeafResult = static::addErrorToResult($deleteLeafResult, 'USER_FIELD_DELETE_FAILED');
			static::logError($exception->getMessage());
		}

		/** @var DataManager $memberDataController */
		$memberDataController = static::getCheckListMemberDataController();
		if ($memberDataController)
		{
			$members = $memberDataController::getList([
				'select' => ['ID'],
				'filter' => ['ITEM_ID' => $id]
			])->fetchAll();
			foreach ($members as $member)
			{
				$memberDeleteResult = $memberDataController::delete($member['ID']);
				if (!$memberDeleteResult->isSuccess())
				{
					$deleteLeafResult = static::addErrorToResult($deleteLeafResult, 'MEMBER_DELETE_FAILED');
					static::logError($memberDeleteResult->getErrorMessages()[0]);
				}
			}
		}

		/** @var CheckListTree $checkListTree */
		$checkListTree = static::getCheckListTree();
		$treeDeleteResult = $checkListTree::delete($id, ['DELETE_SUBTREE' => true]);
		if (!$treeDeleteResult->isSuccess())
		{
			$deleteLeafResult->loadErrors($treeDeleteResult->getErrors());
		}

		/** @var DataManager $checkListDataController */
		$checkListDataController = static::getCheckListDataController();
		$tableDeleteResult = $checkListDataController::delete($id);
		if (!$tableDeleteResult->isSuccess())
		{
			$deleteLeafResult = static::addErrorToResult($deleteLeafResult, 'CHECKLIST_DELETE_FAILED');
			static::logError($tableDeleteResult->getErrorMessages()[0]);
		}

		static::doDeletePostActions($entityId, $userId, ['CHECKLIST' => $checkList]);

		return $deleteLeafResult;
	}

	/**
	 * Deletes all checklists of entity.
	 * Works much slower than deleteByEntityIdOnLowLevel because of recursion,
	 * but checks rights and does post actions.
	 *
	 * @param int $entityId
	 * @param int $userId
	 * @throws ArgumentException
	 * @throws NotImplementedException
	 * @throws SystemException
	 */
	public static function deleteByEntityId($entityId, $userId)
	{
		$checkListItems = static::getList(['ID', 'PARENT_ID'], [static::$entityIdName => $entityId]);
		$checkListItemsTree = static::getObjectStructuredRoots($checkListItems, $entityId, $userId);

		foreach ($checkListItemsTree as $item)
		{
			/** @var CheckList $item */
			static::deleteComposite($entityId, $userId, $item);
		}
	}

	/**
	 * Deletes all checklists of entity.
	 * Works much faster than deleteByEntityId.
	 * This function doesn't check rights and doesn't do post actions.
	 *
	 * @param int $entityId
	 * @throws ArgumentException
	 * @throws NotImplementedException
	 * @throws SystemException
	 */
	public static function deleteByEntityIdOnLowLevel($entityId)
	{
		$checkLists = array_keys(static::getList(['ID', 'TITLE'], [static::$entityIdName => $entityId]));
		static::deleteByCheckListsIds($checkLists);
	}

	/**
	 * @param array $checkLists
	 * @throws NotImplementedException
	 */
	private static function deleteByCheckListsIds($checkLists)
	{
		/** @noinspection PhpVariableNamingConventionInspection */
		global $USER_FIELD_MANAGER;

		if (!$checkLists)
		{
			return;
		}

		$checkListsIds = '('.implode(',', $checkLists).')';

		$dataControllers = [
			static::getCheckListDataController(),
			static::getCheckListTreeDataController(),
		];

		$checkListMemberDataController = static::getCheckListMemberDataController();
		if ($checkListMemberDataController)
		{
			$dataControllers[] = $checkListMemberDataController;
		}

		foreach ($dataControllers as $controller)
		{
			$controller::deleteByCheckListsIds($checkListsIds);
		}

		if (static::$userFieldsEntityIdName)
		{
			foreach ($checkLists as $id)
			{
				$USER_FIELD_MANAGER->Delete(static::$userFieldsEntityIdName, $id);
			}
		}
	}

	/**
	 * Sets new checklists for entity by merging with old ones
	 * (runs through $newItems, adds completely new to entity, detects differences and changes inner data of
	 * changed checklists, deletes all old checklists it did not traverse through).
	 *
	 * @param int $entityId
	 * @param int $userId
	 * @param array $newItems
	 * @param array $parameters
	 * @return Result
	 * @throws ArgumentException
	 * @throws NotImplementedException
	 * @throws ObjectException
	 * @throws SystemException
	 */
	public static function merge($entityId, $userId, $newItems, $parameters = [])
	{
		$mergeResult = new Result();
		$isLimitExceeded = TaskLimit::isLimitExceeded();
		if ($isLimitExceeded)
		{
			foreach ($newItems as &$newItem)
			{
				unset($newItem['MEMBERS']);
			}
		}

		static::doMergePreActions($entityId, $userId);

		static::enableDeferredActionsMode();

		static::$oldItemsToMerge = static::getList([], [static::$entityIdName => $entityId]);
		$oldItemsKeys = array_keys(static::$oldItemsToMerge);

		if (empty($newItems))
		{
			static::deleteByCheckListsIds($oldItemsKeys);
			static::doDeletePostActions($entityId, $userId, ['ITEMS' => static::$oldItemsToMerge]);

			static::doMergePostActions($entityId, $userId, ['PARAMETERS' => $parameters]);

			return $mergeResult;
		}

		$traversedItems = [];
		$newItemsTree = static::getObjectStructuredRoots($newItems, $entityId, $userId, 'PARENT_NODE_ID');

		foreach ($newItemsTree as $item)
		{
			/** @var CheckList $item */
			$saveResult = $item->save();
			if (!$saveResult->isSuccess())
			{
				$mergeResult->loadErrors($saveResult->getErrors());
				return $mergeResult;
			}

			/** @var CheckList $savedItem */
			$savedItem = $saveResult->getData()['ITEM'];
			$traversedItems[] = $savedItem->toItemsArray();
		}

		$traversedItems = array_merge(...$traversedItems);
		$mergeResult->setData(['TRAVERSED_ITEMS' => $traversedItems]);

		$itemsToRemoveKeys = array_diff($oldItemsKeys, array_column($traversedItems, 'ID'));
		$itemsToRemove = array_filter(
			static::$oldItemsToMerge,
			static function ($item) use ($itemsToRemoveKeys)
			{
				return in_array((int)$item['ID'], $itemsToRemoveKeys, true);
			}
		);

		static::deleteByCheckListsIds($itemsToRemoveKeys);
		static::doDeletePostActions($entityId, $userId, ['ITEMS' => $itemsToRemove]);

		static::doMergePostActions($entityId, $userId, ['ITEMS' => $traversedItems, 'PARAMETERS' => $parameters]);

		static::disableDeferredActionsMode();
		static::onAfterMerge($traversedItems, $userId, $entityId, $parameters);

		return $mergeResult;
	}

	/**
	 * Moves item before or after another item depending on position.
	 *
	 * @param int $entityId
	 * @param int $userId
	 * @param CheckList $itemToMove
	 * @param int $relatedItemId
	 * @param string $position
	 * @return Result
	 * @throws ArgumentException
	 * @throws NotImplementedException
	 * @throws SystemException
	 */
	public static function moveItem(
		$entityId,
		$userId,
		$itemToMove,
		$relatedItemId,
		$position = self::MOVING_POSITION_AFTER
	)
	{
		$moveResult = new Result();

		if (!static::checkAccess($entityId, $userId, ActionDictionary::ACTION_CHECKLIST_EDIT, $itemToMove))
		{
			$code = 'ACTION_NOT_ALLOWED';
			$moveResult = static::addErrorToResult($moveResult, $code, self::ACTION_REORDER);
			return $moveResult;
		}

		/** @var CheckListFacade $facade */
		$items = static::getByEntityId($entityId);
		$facade = static::class;
		$relatedItemFields = static::getList([], ['ID' => $relatedItemId])[$relatedItemId];
		$relatedItem = new CheckList(0, $userId, $facade, $relatedItemFields);

		$itemToMoveFields = $itemToMove->getFields();
		$relatedItemFields = $relatedItem->getFields();

		$sortIndex = $relatedItemFields['SORT_INDEX'];
		$newParentId = $relatedItemFields['PARENT_ID'];

		$previousParentId = $newParentId;

		while ($previousParentId !== 0)
		{
			if ($previousParentId === $itemToMoveFields['ID'])
			{
				$moveResult = static::addErrorToResult($moveResult, 'NO_LOOPS_AVAILABLE');
				return $moveResult;
			}

			$previousParentId = $items[$previousParentId]['PARENT_ID'];
		}

		$newFields = [
			'SORT_INDEX' => ($position === static::MOVING_POSITION_BEFORE? $sortIndex : $sortIndex + 1)
		];

		if ($itemToMoveFields['PARENT_ID'] !== $newParentId)
		{
			$newFields['PARENT_ID'] = $newParentId;
		}

		$itemToMove->setFields($newFields);
		$moveResult = $itemToMove->save();

		return $moveResult;
	}

	/**
	 * Completes checklist.
	 *
	 * @param int $entityId
	 * @param int $userId
	 * @param CheckList $checkList
	 * @return Result
	 * @throws ArgumentException
	 * @throws NotImplementedException
	 * @throws ObjectException
	 * @throws ObjectPropertyException
	 * @throws SqlQueryException
	 * @throws SystemException
	 */
	public static function complete($entityId, $userId, $checkList)
	{
		$completeResult = static::update($entityId, $userId, $checkList, ['IS_COMPLETE' => true]);
		return $completeResult;
	}

	/**
	 * Completes all checklists recursively starting from $checkList.
	 *
	 * @param int $entityId
	 * @param int $userId
	 * @param CheckList $checkList
	 * @return Result
	 * @throws ArgumentException
	 * @throws NotImplementedException
	 * @throws SystemException
	 */
	public static function completeAll(int $entityId, int $userId, CheckList $checkList): Result
	{
		$completeAllResult = new Result();

		$items = static::getByEntityId($entityId);
		$itemsTree = static::getObjectStructuredRoots($items, $entityId, $userId);

		/** @var CheckList $item */
		foreach ($itemsTree as $item)
		{
			if ($checkListToComplete = $item->findById($checkList->getFields()['ID']))
			{
				return static::updateComposite($entityId, $userId, $checkListToComplete, ['IS_COMPLETE' => 'Y']);
			}
		}

		return $completeAllResult;
	}


	/**
	 * Renews checklist.
	 *
	 * @param int $entityId
	 * @param int $userId
	 * @param CheckList $checkList
	 * @return Result
	 * @throws ArgumentException
	 * @throws NotImplementedException
	 * @throws ObjectException
	 * @throws ObjectPropertyException
	 * @throws SqlQueryException
	 * @throws SystemException
	 */
	public static function renew($entityId, $userId, $checkList)
	{
		$renewResult = static::update($entityId, $userId, $checkList, ['IS_COMPLETE' => false]);
		return $renewResult;
	}

	/**
	 * Adds members to checklist.
	 *
	 * @param int $entityId
	 * @param int $userId
	 * @param CheckList $checkList
	 * @param $members
	 * @return Result
	 * @throws ArgumentException
	 * @throws NotImplementedException
	 * @throws ObjectException
	 * @throws ObjectPropertyException
	 * @throws SqlQueryException
	 * @throws SystemException
	 */
	public static function addMembers($entityId, $userId, $checkList, $members)
	{
		$addMembersResult = new Result();

		if (!static::checkAccess($entityId, $userId, ActionDictionary::ACTION_CHECKLIST_EDIT, $checkList))
		{
			$code = 'ACTION_NOT_ALLOWED';
			$addMembersResult = static::addErrorToResult($addMembersResult, $code, self::ACTION_MODIFY);
			return $addMembersResult;
		}

		$fieldsChecking = static::checkFields(['MEMBERS' => $members]);
		if (!$fieldsChecking->isSuccess())
		{
			$addMembersResult->loadErrors($fieldsChecking->getErrors());
			return $addMembersResult;
		}

		$members = array_map(
			static function($data)
			{
				return (!is_array($data)? ['TYPE' => $data] : $data);
			},
			$members
		);

		$checkList->addMembers($members);
		$addMembersResult = $checkList->save();

		return $addMembersResult;
	}

	/**
	 * Removes members from checklist.
	 *
	 * @param int $entityId
	 * @param int $userId
	 * @param CheckList $checkList
	 * @param $membersIds
	 * @return Result
	 * @throws ArgumentException
	 * @throws NotImplementedException
	 * @throws ObjectException
	 * @throws ObjectPropertyException
	 * @throws SqlQueryException
	 * @throws SystemException
	 */
	public static function removeMembers($entityId, $userId, $checkList, $membersIds)
	{
		$removeMembersResult = new Result();

		if (!static::checkAccess($entityId, $userId, ActionDictionary::ACTION_CHECKLIST_EDIT, $checkList))
		{
			$code = 'ACTION_NOT_ALLOWED';
			$removeMembersResult = static::addErrorToResult($removeMembersResult, $code, self::ACTION_MODIFY);
			return $removeMembersResult;
		}

		$checkList->removeMembers($membersIds);
		$removeMembersResult = $checkList->save();

		return $removeMembersResult;
	}

	/**
	 * Attaches file represented by base64 content to checklist.
	 * This function uploads file on bitrix24 disk before attaching.
	 *
	 * @param int $entityId
	 * @param int $userId
	 * @param CheckList $checkList
	 * @param array $attachmentParameters - consists of NAME => (string)$name and CONTENT => (base64)$content
	 * @return Result
	 */
	public static function addAttachmentByContent($entityId, $userId, $checkList, $attachmentParameters)
	{
		$addAttachmentResult = new Result();

		if (!static::checkAccess($entityId, $userId, ActionDictionary::ACTION_CHECKLIST_EDIT, $checkList))
		{
			$code = 'ACTION_NOT_ALLOWED';
			$addAttachmentResult = static::addErrorToResult($addAttachmentResult, $code, self::ACTION_MODIFY);
			return $addAttachmentResult;
		}

		try
		{
			Attachment::add($checkList->getFields()['ID'], $attachmentParameters, [
				'USER_ID' => $userId,
				'ENTITY_ID' => static::$userFieldsEntityIdName,
				'FIELD_NAME' => 'UF_CHECKLIST_FILES',
			]);
		}
		catch (Exception $exception)
		{
			$addAttachmentResult = static::addErrorToResult($addAttachmentResult, 'ATTACHMENT_ADDING_FAILED');
			return $addAttachmentResult;
		}

		$addAttachmentResult->setData(['ITEM' => $checkList]);

		return $addAttachmentResult;
	}

	/**
	 * Attaches file from bitrix24 disk to checklist.
	 *
	 * @param int $entityId
	 * @param int $userId
	 * @param CheckList $checkList
	 * @param array $filesIds
	 * @return Result
	 * @throws ArgumentException
	 * @throws NotImplementedException
	 * @throws ObjectException
	 * @throws ObjectPropertyException
	 * @throws SqlQueryException
	 * @throws SystemException
	 */
	public static function addAttachmentsFromDisk($entityId, $userId, $checkList, $filesIds)
	{
		$addAttachmentsResult = new Result();

		if (!static::checkAccess($entityId, $userId, ActionDictionary::ACTION_CHECKLIST_EDIT, $checkList))
		{
			$code = 'ACTION_NOT_ALLOWED';
			$addAttachmentsResult = static::addErrorToResult($addAttachmentsResult, $code, self::ACTION_MODIFY);
			return $addAttachmentsResult;
		}

		$checkList->addAttachments($filesIds);
		$addAttachmentsResult = $checkList->save();

		return $addAttachmentsResult;
	}

	/**
	 * Removes attachments from checklist.
	 *
	 * @param int $entityId
	 * @param int $userId
	 * @param CheckList $checkList
	 * @param array $attachmentsIds
	 * @return Result
	 * @throws ArgumentException
	 * @throws NotImplementedException
	 * @throws ObjectException
	 * @throws ObjectPropertyException
	 * @throws SqlQueryException
	 * @throws SystemException
	 */
	public static function removeAttachments($entityId, $userId, $checkList, $attachmentsIds)
	{
		$removeAttachmentsResult = new Result();

		if (!static::checkAccess($entityId, $userId, ActionDictionary::ACTION_CHECKLIST_EDIT, $checkList))
		{
			$code = 'ACTION_NOT_ALLOWED';
			$removeAttachmentsResult = static::addErrorToResult($removeAttachmentsResult, $code, self::ACTION_MODIFY);
			return $removeAttachmentsResult;
		}

		$checkList->removeAttachments($attachmentsIds);
		$removeAttachmentsResult = $checkList->save();

		return $removeAttachmentsResult;
	}

	public static function checkAccess($entityId, $userId, $action, $params = null)
	{
		$accessController = static::getAccessControllerClass();
		return $accessController::can($userId, $action, $entityId, $params);
	}

	abstract protected static function getAccessControllerClass(): string;

	/**
	 * Checks if action is allowed for user and entity.
	 *
	 * @param int $entityId
	 * @param ?CheckList $checkList
	 * @param int $userId
	 * @param $actionId
	 * @return bool
	 */
	public static function isActionAllowed($entityId, $checkList, $userId, $actionId)
	{
		$action = ActionDictionary::ACTION_CHECKLIST_EDIT;

		if ($actionId == self::ACTION_ADD)
		{
			$action = ActionDictionary::ACTION_CHECKLIST_ADD;
		}
		elseif ($actionId == self::ACTION_TOGGLE)
		{
			$action = ActionDictionary::ACTION_CHECKLIST_TOGGLE;
		}

		return static::checkAccess($entityId, $userId, $action, $checkList);
	}

	/**
	 * Returns array of roots with subtrees as array structures.
	 *
	 * @param array $sourceArray
	 * @param string $keyToParent
	 * @return array
	 */
	public static function getArrayStructuredRoots(array $sourceArray, $keyToParent = 'PARENT_ID')
	{
		$roots = [];
		$result = [];

		foreach ($sourceArray as $id => $item)
		{
			if (!isset($sourceArray[$id]['SUB_TREE']))
			{
				$sourceArray[$id]['SUB_TREE'] = [];
			}

			if ($item[$keyToParent] && isset($sourceArray[$item[$keyToParent]]))
			{
				$sourceArray[$item[$keyToParent]]['SUB_TREE'][$id] =& $sourceArray[$id];
			}
			else
			{
				$roots[] = $id;
			}
		}

		foreach ($roots as $root)
		{
			$result[$root] = $sourceArray[$root];
		}

		return $result;
	}

	/**
	 * Returns array of roots with subtrees as object structures.
	 *
	 * @param array $items
	 * @param int $entityId
	 * @param int $userId
	 * @param string $keyToParent
	 * @return array
	 * @throws NotImplementedException
	 */
	public static function getObjectStructuredRoots($items, $entityId, $userId, $keyToParent = 'PARENT_ID')
	{
		$result = [];

		$arrayStructuredRoots = static::getArrayStructuredRoots($items, $keyToParent);
		foreach ($arrayStructuredRoots as $root)
		{
			$checkList = static::makeCheckListItem($root, $entityId, $userId);
			$checkList->setFields(['PARENT_ID' => 0]);

			$result[] = $checkList;
		}

		return $result;
	}

	/**
	 * @param array $root
	 * @param int $entityId
	 * @param int $userId
	 * @return CheckList
	 * @throws NotImplementedException
	 */
	private static function makeCheckListItem($root, $entityId, $userId)
	{
		static::$nodeId++;

		$nodeId = (isset($root['NODE_ID'])? $root['NODE_ID'] : static::$nodeId);
		$fields = $root;
		$fields['ENTITY_ID'] = $entityId;
		/** @var CheckListFacade $facade */
		$facade = static::class;

		$tree = new CheckList($nodeId, $userId, $facade, $fields);

		foreach ($root['SUB_TREE'] as $item)
		{
			$tree->add(static::makeCheckListItem($item, $entityId, $userId));
		}

		return $tree;
	}

	/**
	 * Checks fields before adding checklist.
	 *
	 * @param array $fields
	 * @return Result
	 */
	public static function checkFieldsForAdd(array $fields)
	{
		return static::checkFields($fields, ['MODE' => 'add']);
	}

	/**
	 * Checks fields before updating checklist.
	 *
	 * @param array $fields
	 * @return Result
	 */
	public static function checkFieldsForUpdate(array $fields)
	{
		return static::checkFields($fields, ['MODE' => 'update']);
	}

	/**
	 * @param array $fields
	 * @param array $parameters
	 * @return Result
	 */
	private static function checkFields(array $fields, array $parameters = [])
	{
		$checkResult = new Result();

		if (!array_key_exists('TITLE', $fields) && $parameters['MODE'] === 'add')
		{
			$checkResult = static::addErrorToResult($checkResult, 'EMPTY_TITLE');
		}
		else if (empty($fields) && $parameters['MODE'] === 'update')
		{
			$checkResult = static::addErrorToResult($checkResult, 'EMPTY_FIELDS');
		}

		$allowedFields = ['TITLE', 'PARENT_ID', 'SORT_INDEX', 'IS_COMPLETE', 'IS_IMPORTANT', 'MEMBERS', 'ATTACHMENTS'];
		foreach (array_keys($fields) as $fieldName)
		{
			if (!in_array($fieldName, $allowedFields, true))
			{
				$checkResult = static::addErrorToResult($checkResult, 'NOT_ALLOWED_FIELD', $fieldName);
			}
		}

		if (array_key_exists('MEMBERS', $fields))
		{
			if (!$fields['MEMBERS'])
			{
				$fields['MEMBERS'] = [];
			}

			foreach ($fields['MEMBERS'] as $id => $data)
			{
				$type = $data;

				if (is_array($data))
				{
					$type = $data['TYPE'];
				}

				if (!in_array($type, [self::MEMBER_ACCOMPLICE, self::MEMBER_AUDITOR], true))
				{
					$checkResult = static::addErrorToResult($checkResult, 'WRONG_MEMBER_TYPE', $type);
				}
			}
		}

		return $checkResult;
	}

	/**
	 * @param array $select
	 * @param array $filter
	 * @param array $order
	 * @return array
	 */
	private static function getFilteredFields($select, $filter, $order)
	{
		return [
			static::getFilteredSelect($select),
			static::getFilteredFilter($filter),
			static::getFilteredOrder($order),
		];
	}

	/**
	 * @param array $select
	 * @return array
	 */
	private static function getFilteredSelect($select)
	{
		$filteredSelect = [];

		if (empty($select))
		{
			$select = static::$selectFields;
		}

		foreach (array_values($select) as $field)
		{
			if (in_array($field, static::$selectFields, true))
			{
				if ($field === 'MEMBERS')
				{
					foreach (static::$memberFields as $userField)
					{
						if ($userField === 'USER_ID')
						{
							$value = 'IM.USER_ID';
						}
						else if ($userField === 'USER_TYPE')
						{
							$value = 'IM.TYPE';
						}
						else
						{
							$value = 'IM.USER.'.str_replace('USER_', '', $userField);
						}

						$filteredSelect[$userField] = $value;
					}
					continue;
				}

				if ($field === 'PARENT_ID')
				{
					$filteredSelect[$field] = 'IT.PARENT_ID';
					continue;
				}

				if ($field === 'ATTACHMENTS')
				{
					if (ModuleManager::isModuleInstalled('disk'))
					{
						$filteredSelect[] = 'UF_CHECKLIST_FILES';
					}
					continue;
				}

				$filteredSelect[] = $field;
			}
		}

		if (!in_array('ID', $filteredSelect, true))
		{
			$filteredSelect[] = 'ID';
		}

		return $filteredSelect;
	}

	/**
	 * @param array $filter
	 * @return array
	 */
	private static function getFilteredFilter($filter)
	{
		$filteredFilter = [];

		foreach ($filter as $field => $value)
		{
			if (in_array($field, static::$filterFields, true))
			{
				if ($field === 'PARENT_ID')
				{
					$filteredFilter['IT.PARENT_ID'] = $value;
					continue;
				}

				$filteredFilter[$field] = $value;
			}
		}

		return $filteredFilter;
	}

	/**
	 * @param array $order
	 * @return array
	 */
	private static function getFilteredOrder($order)
	{
		$filteredOrder = [];
		$availableSorts = ['asc', 'desc', 'ASC', 'DESC'];

		if (empty($order))
		{
			$order = [static::$entityIdName => 'DESC', 'SORT_INDEX' => 'ASC', 'ID' => 'DESC'];
		}

		foreach ($order as $field => $sort)
		{
			if (in_array($field, static::$orderFields, true) && in_array($sort, $availableSorts, true))
			{
				if ($field === 'PARENT_ID')
				{
					$filteredOrder['IT.PARENT_ID'] = $sort;
					continue;
				}

				$filteredOrder[$field] = $sort;
			}
		}

		return $filteredOrder;
	}

	/**
	 * @param array $item
	 * @param array $items
	 * @param array $select
	 * @return array
	 */
	private static function processItemMembers($item, $items, $select)
	{
		$processedItem = $item;

		if (empty($select) || in_array('MEMBERS', $select, true))
		{
			$processedItem['MEMBERS'] = [];

			$id = $processedItem['ID'];
			$userId = $processedItem['USER_ID'] ?? null;

			if (
				isset($userId)
				|| (
					array_key_exists($id, $items)
					&& $items[$id]
				)
			)
			{
				$userFields = [];

				foreach (static::$memberFields as $field)
				{
					$userFields[str_replace('USER_', '', $field)] = $processedItem[$field];
				}

				$member = [
					'ID' => $userId,
					'TYPE' => $processedItem['USER_TYPE'],
					'NAME' => User::formatName($userFields),
					'IMAGE' => Avatar::getSrc($processedItem['USER_PERSONAL_PHOTO']),
					'IS_COLLABER' => $userId && \Bitrix\Tasks\Integration\Extranet\User::isCollaber($userId),
				];

				if (isset($items[$id]))
				{
					$items[$id]['MEMBERS'][$userId] = $member;
					return $items[$id];
				}

				$processedItem['MEMBERS'][$userId] = $member;
			}

			foreach (static::$memberFields as $field)
			{
				unset($processedItem[$field]);
			}
		}

		return $processedItem;
	}

	/**
	 * @param array $item
	 * @param array $select
	 * @return array
	 */
	private static function processItemAttachments($item, $select)
	{
		$processedItem = $item;

		if (
			(empty($select) || in_array('ATTACHMENTS', $select, true))
			&& ModuleManager::isModuleInstalled('disk')
		)
		{
			/** @noinspection PhpVariableNamingConventionInspection */
			global $USER_FIELD_MANAGER;

			$processedItem['ATTACHMENTS'] = [];

			if ($processedItem['UF_CHECKLIST_FILES'] ?? null)
			{
				$userFields = $USER_FIELD_MANAGER->GetUserFields(static::$userFieldsEntityIdName, $item['ID'], LANGUAGE_ID);
				$value = $userFields['UF_CHECKLIST_FILES']['VALUE'];

				if (!UserField::isValueEmpty($value))
				{
					foreach ($value as $attachmentId)
					{
						$processedItem['ATTACHMENTS'][$attachmentId] = Attachment::getById($attachmentId);
					}
					$processedItem['UF_CHECKLIST_FILES'] = $userFields['UF_CHECKLIST_FILES'];
				}
			}
		}

		return $processedItem;
	}

	/**
	 * @param array $item
	 * @param array $select
	 * @return array
	 */
	private static function processItemCommons($item, $select)
	{
		$processedItem = $item;

		if (array_key_exists('PARENT_ID', $processedItem) && $processedItem['PARENT_ID'] === null)
		{
			$processedItem['PARENT_ID'] = 0;
		}

		if (array_key_exists(static::$entityIdName, $processedItem))
		{
			$processedItem['ENTITY_ID'] = $processedItem[static::$entityIdName];
		}

		if (!empty($select) && !in_array('ID', $select, true))
		{
			unset($processedItem['ID']);
		}

		return $processedItem;
	}

	/**
	 * @param int $entityId
	 * @param int $userId
	 * @param array $items
	 * @return mixed
	 */
	public static function fillActionsForItems($entityId, $userId, $items)
	{
		if (empty($items))
		{
			return $items;
		}

		$items = array_map(
			static function($item) use ($entityId, $userId)
			{
				$item['ACTION'] = [
					'MODIFY' => static::checkAccess($entityId, $userId, ActionDictionary::ACTION_CHECKLIST_EDIT, $item),
					'REMOVE' => static::checkAccess($entityId, $userId, ActionDictionary::ACTION_CHECKLIST_EDIT, $item),
					'TOGGLE' => static::checkAccess($entityId, $userId, ActionDictionary::ACTION_CHECKLIST_TOGGLE, $item),
				];

				return $item;
			},
			$items
		);

		return $items;
	}

	/**
	 * @param Result $result
	 * @param string $code
	 * @param mixed $argument
	 * @return Result
	 */
	private static function addErrorToResult($result, $code, $argument = null)
	{
		$actions = static::ACTIONS['COMMON'] + static::ACTIONS['ITEM'];
		$replaces = [
			'NOT_ALLOWED_FIELD' => '#FIELD_NAME#',
			'WRONG_MEMBER_TYPE' => '#TYPE#',
		];

		if ($code === 'ACTION_NOT_ALLOWED' && isset($actions[$argument]))
		{
			$actionName = Loc::getMessage(static::$locPrefix.$actions[$argument]);
			$message = str_replace('#ACTION_NAME#', $actionName, Loc::getMessage(static::$locPrefix.$code));
		}
		else if (array_key_exists($code, $replaces))
		{
			$message = str_replace($replaces[$code], $argument, Loc::getMessage(static::$locPrefix.$code));
		}
		else
		{
			$message = Loc::getMessage(static::$locPrefix.$code);
		}

		$result->addError($code, $message);

		return $result;
	}

	/**
	 * Returns checklists with actions for entity if entity is accessible for reading.
	 *
	 * @param int $entityId
	 * @param int $userId
	 * @return array
	 */
	public static function getItemsForEntity($entityId, $userId)
	{
		return [];
	}

	/**
	 * Does some actions after adding checklist.
	 *
	 * @param int $entityId
	 * @param int $userId
	 * @param CheckList $checkList
	 */
	public static function doAddPostActions($entityId, $userId, $checkList)
	{

	}

	/**
	 * Does some actions after updating checklist.
	 *
	 * @param int $entityId
	 * @param int $userId
	 * @param CheckList $oldCheckList
	 * @param CheckList $newCheckList
	 */
	public static function doUpdatePostActions($entityId, $userId, $oldCheckList, $newCheckList)
	{

	}

	/**
	 * Does some actions after deleting checklists.
	 *
	 * @param int $entityId
	 * @param int $userId
	 * @param array $data
	 */
	public static function doDeletePostActions($entityId, $userId, $data = [])
	{

	}

	/**
	 * Does some actions before merging checklists.
	 *
	 * @param int $entityId
	 * @param int $userId
	 * @param array $data
	 */
	public static function doMergePreActions($entityId, $userId, $data = [])
	{

	}

	/**
	 * Does some actions after merging checklists.
	 *
	 * @param int $entityId
	 * @param int $userId
	 * @param array $data
	 */
	public static function doMergePostActions($entityId, $userId, $data = [])
	{

	}

	/**
	 * Returns array of fields suitable for table data adding or updating.
	 *
	 * @param array $fields
	 * @return array
	 */
	public static function getFieldsForTable($fields)
	{
		return [];
	}

	/**
	 * Logs error message.
	 *
	 * @param string $message
	 */
	public static function logError($message)
	{

	}

	/**
	 * @param int $entityId
	 * @param int $userId
	 * @return array
	 */
	protected static function fillCommonAccessActions($entityId, $userId)
	{
		return [];
	}

	/**
	 * @param int $entityId
	 * @param CheckList $checkList
	 * @param int $userId
	 * @return array
	 */
	protected static function fillItemAccessActions($entityId, $checkList, $userId)
	{
		return [];
	}

	protected static function onAfterMerge(array $traversedItems, int $userId, int $taskId, array $parameters): void
	{
	}

	protected static function onAfterUpdate(int $taskId): void
	{
	}
}