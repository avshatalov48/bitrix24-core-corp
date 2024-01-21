<?php
namespace Bitrix\Tasks\CheckList\Internals;

use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\Connection;
use Bitrix\Main\Db\SqlQueryException;
use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\ObjectException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\CheckList\CheckListFacade;
use Bitrix\Tasks\UI;
use Bitrix\Tasks\Util\Result;
use Bitrix\Tasks\Util\User;
use Exception;

Loc::loadMessages(__FILE__);

/**
 * Class CheckList
 *
 * @package Bitrix\Tasks\CheckList\Internals
 */
class CheckList extends CompositeTreeItem
{
	private $fields;
	private $userId;
	private $facade;
	private $action = [];
	private bool $needSkipMembers = false;

	/** @var DataManager $checkListDataController */
	private $checkListDataController;
	/** @var CheckListTree $checkListTree */
	private $checkListTree;
	/** @var DataManager $checkListMemberDataController */
	private $checkListMemberDataController;
	/** @var string $userFieldsEntityIdName */
	private $userFieldsEntityIdName;

	/**
	 * CheckList constructor.
	 *
	 * @param mixed $nodeId
	 * @param int $userId
	 * @param CheckListFacade|string $facade
	 * @param array $fields
	 * @throws NotImplementedException
	 */
	public function __construct($nodeId, $userId, $facade, $fields = [])
	{
		parent::__construct($nodeId);

		$fields['USER_ID'] = $userId;

		$this->userId = $userId;
		$this->facade = $facade;
		$this->action = (isset($fields['ACTION']) && is_array($fields['ACTION']) ? $fields['ACTION'] : []);
		$this->fields = new CheckListFields($fields);

		$this->checkListDataController = $facade::getCheckListDataController();
		$this->checkListTree = $facade::getCheckListTree();
		$this->checkListMemberDataController = $facade::getCheckListMemberDataController();
		$this->userFieldsEntityIdName = $facade::$userFieldsEntityIdName;
	}

	public function setSkipMembers(): void
	{
		$this->needSkipMembers = true;
	}

	public function getSkipMembers(): bool
	{
		return $this->needSkipMembers;
	}

	/**
	 * Finds and returns checklist with given id if it is a part of current checklist subtree (or root itself),
	 * otherwise returns null.
	 *
	 * @param $id
	 * @return CheckList|null
	 */
	public function findById($id)
	{
		if ($this->fields->getId() == $id)
		{
			return $this;
		}

		foreach ($this->getDescendants() as $descendant)
		{
			/** @var CheckList $descendant */
			$found = $descendant->findById($id);
			if ($found !== null)
			{
				return $found;
			}
		}

		return null;
	}

	/**
	 * Checks checklist data fields.
	 *
	 * @return Result
	 */
	public function checkFields()
	{
		/** @noinspection PhpVariableNamingConventionInspection */ global $APPLICATION;
		/** @noinspection PhpVariableNamingConventionInspection */ global $USER_FIELD_MANAGER;

		$checkFieldsResult = $this->fields->checkFields();
		if (!$checkFieldsResult->isSuccess() || !ModuleManager::isModuleInstalled('disk'))
		{
			return $checkFieldsResult;
		}

		$id = $this->fields->getId();
		$attachments = $this->fields->getAttachments();

		$userFields = ['UF_CHECKLIST_FILES' => (isset($id)? array_keys($attachments) : array_values($attachments)),];

		if (!$USER_FIELD_MANAGER->CheckFields($this->userFieldsEntityIdName, $id, $userFields, $this->userId))
		{
			$exception = $APPLICATION->GetException();

			foreach ($exception->messages as $message)
			{
				$checkFieldsResult->addError('CHECK_UF_FAILED', $message);
			}
		}

		return $checkFieldsResult;
	}

	/**
	 * @return array
	 */
	public function getAction()
	{
		return $this->action;
	}

	/**
	 * Returns checklist fields.
	 *
	 * @return array
	 */
	public function getFields()
	{
		return $this->fields->getFields();
	}

	/**
	 * Sets new values to checklist fields.
	 *
	 * @param $data
	 */
	public function setFields($data)
	{
		$this->fields->setFields($data);
	}

	/**
	 * Adds members to checklist.
	 *
	 * @param $members
	 */
	public function addMembers($members)
	{
		$this->fields->setFields(['MEMBERS' => array_replace($this->fields->getMembers(), $members)]);
	}

	/**
	 * Removes members from checklist.
	 *
	 * @param $membersIds
	 */
	public function removeMembers($membersIds)
	{
		foreach ($membersIds as $id)
		{
			$this->fields->removeMember($id);
		}
	}

	/**
	 * Adds attachments to checklist.
	 *
	 * @param $filesIds
	 */
	public function addAttachments($filesIds)
	{
		foreach ($filesIds as $id)
		{
			$id = ($id[0] === 'n'? $id : 'n'.$id);
			$attachments = $this->fields->getAttachments();

			if (!in_array($id, $attachments, true))
			{
				$this->fields->addAttachment($id);
			}
		}
	}

	/**
	 * Removes attachments from checklist.
	 *
	 * @param $attachmentsIds
	 */
	public function removeAttachments($attachmentsIds)
	{
		foreach ($attachmentsIds as $id)
		{
			$this->fields->removeAttachment($id);
		}
	}

	/**
	 * Saves checklist and it's whole subtree.
	 * Updates existing item's fields or adds new item if ID is not set.
	 *
	 * @return Result
	 * @throws ArgumentException
	 * @throws NotImplementedException
	 * @throws ObjectException
	 * @throws ObjectPropertyException
	 * @throws SqlQueryException
	 * @throws SystemException
	 */
	public function save()
	{
		$saveResult = new Result();

		$this->cloneFileAttachments();
		$fieldsChecking = $this->checkFields();
		if (!$fieldsChecking->isSuccess())
		{
			$saveResult->loadErrors($fieldsChecking->getErrors());
			return $saveResult;
		}

		$id = $this->fields->getId();

		$actionProcessResult = (isset($id)? $this->processUpdate() : $this->processAdd());
		if (!$actionProcessResult->isSuccess())
		{
			$saveResult->loadErrors($actionProcessResult->getErrors());
			return $saveResult;
		}

		foreach ($this->getDescendants() as $descendant)
		{
			/** @var CheckList $descendant */
			$descendant->setFields([
				'ENTITY_ID' => $this->fields->getEntityId(),
				'USER_ID' => $this->userId,
				'PARENT_ID' => $this->fields->getId(),
			]);

			$descendantSaveResult = $descendant->save();
			if (!$descendantSaveResult->isSuccess())
			{
				$saveResult->loadErrors($descendantSaveResult->getErrors());
				return $saveResult;
			}
		}

		$saveResult->setData(['ITEM' => $this]);

		return $saveResult;
	}

	/**
	 * @return void
	 */
	private function cloneFileAttachments()
	{
		if (
			$this->fields->getId()
			|| !$this->fields->getCopiedId()
			|| empty($this->fields->getAttachments())
		)
		{
			return;
		}

		$attachments = $this->fields->getAttachments();
		$clone = \Bitrix\Tasks\Integration\Disk::cloneFileAttachment(array_keys($attachments), $this->userId);
		$this->fields->setAttachments($clone);
	}

	/**
	 * Transforms current item object tree structure to array tree structure.
	 *
	 * @return array
	 */
	public function toTreeArray()
	{
		$result = [
			'NODE_ID' => $this->getNodeId(),
			'FIELDS' => $this->getFields(),
			'ACTION' => $this->getAction(),
			'DESCENDANTS' => [],
		];

		foreach ($this->getDescendants() as $descendant)
		{
			/** @var CheckList $descendant */
			$result['DESCENDANTS'][] = $descendant->toTreeArray();
		}

		return $result;
	}

	/**
	 * Transforms current item object tree structure to array of items.
	 *
	 * @return array
	 */
	public function toItemsArray()
	{
		$result = [$this->getNodeId() => $this->getFields()];

		foreach ($this->getDescendants() as $descendant)
		{
			/** @var CheckList $descendant */
			$result = array_merge($result, $descendant->toItemsArray());
		}

		return $result;
	}

	/**
	 * Returns new item copied from current item.
	 *
	 * @return CheckList
	 * @throws NotImplementedException
	 */
	public function copy()
	{
		return new static($this->getNodeId(), $this->userId, $this->facade, $this->getFields());
	}

	/**
	 * @return Result
	 * @throws ObjectException
	 * @throws Exception
	 */
	private function processUpdate()
	{
		/** @noinspection PhpVariableNamingConventionInspection */
		global $USER_FIELD_MANAGER;

		$updateResult = new Result();

		$id = $this->fields->getId();
		$facade = $this->facade;

		/** @noinspection PhpUndefinedVariableInspection */
		$oldItemFields = (($facade::$oldItemsToMerge[$id] ?? null) ?: $facade::getList([], ['ID' => $id])[$id]);
		$oldItem = new CheckList(0, $this->userId, $facade, $oldItemFields);

		$changedFields = $this->getChangedFields($oldItem->getFields());
		$tableFields = $changedFields['TABLE'];
		$commonFields = $changedFields['COMMON'];

		if (array_key_exists('SORT_INDEX', $tableFields) || array_key_exists('PARENT_ID', $commonFields))
		{
			$this->recountSortIndexes(Application::getConnection());
		}

		if (!empty($tableFields))
		{
			$checkListDataController = $this->checkListDataController;
			$updateTableResult = $checkListDataController::update($id, $tableFields);
			if (!$updateTableResult->isSuccess())
			{
				$facade::logError($updateTableResult->getErrorMessages()[0]);
			}
		}

		if (array_key_exists('MEMBERS', $commonFields))
		{
			try
			{
				$this->updateMembers($oldItem->fields->getMembers(), $this->fields->getMembers());
			}
			catch (Exception $exception)
			{
				$facade::logError($exception->getMessage());
			}
		}

		if (array_key_exists('ATTACHMENTS', $commonFields) && ModuleManager::isModuleInstalled('disk'))
		{
			$userFields = ['UF_CHECKLIST_FILES' => $commonFields['ATTACHMENTS']];
			$USER_FIELD_MANAGER->Update($this->userFieldsEntityIdName, $id, $userFields, $this->userId);

			$this->setFields(['ATTACHMENTS' => $facade::getList(['ATTACHMENTS'], ['ID' => $id])[$id]['ATTACHMENTS']]);
		}

		$newParentId = ($commonFields['PARENT_ID'] ?? null);

		if (isset($newParentId))
		{
			$checkListTree = $this->checkListTree;

			if ($newParentId === 0)
			{
				$detachResult = $checkListTree::detachSubTree($id);
				if (!$detachResult->isSuccess())
				{
					$updateResult->loadErrors($detachResult->getErrors());
					return $updateResult;
				}
			}
			else
			{
				$attachResult = $checkListTree::attach($id, $newParentId);
				if (!$attachResult->isSuccess())
				{
					$updateResult->loadErrors($attachResult->getErrors());
					return $updateResult;
				}
			}
		}

		$facade::doUpdatePostActions($this->fields->getEntityId(), $this->userId, $oldItem, $this);

		return $updateResult;
	}

	/**
	 * @return Result
	 * @throws ArgumentException
	 * @throws NotImplementedException
	 * @throws ObjectPropertyException
	 * @throws SqlQueryException
	 * @throws SystemException
	 * @throws Exception
	 */
	private function processAdd()
	{
		/** @noinspection PhpVariableNamingConventionInspection */
		global $USER_FIELD_MANAGER;

		$addResult = new Result();

		$facade = $this->facade;
		$checkListTree = $this->checkListTree;
		$checkListDataController = $this->checkListDataController;
		$checkListMemberDataController = $this->checkListMemberDataController;

		$this->fillMandatoryFields();
		$this->recountSortIndexes(Application::getConnection());

		$fields = $this->getFields();
		$fieldsForTable = $facade::getFieldsForTable($fields);

		$tableAddResult = $checkListDataController::add($fieldsForTable);
		$id = ($tableAddResult->isSuccess()? $tableAddResult->getId() : false);

		if (!$id)
		{
			$facade::logError($tableAddResult->getErrorMessages()[0]);
			$addResult->addError('CHECKLIST_TABLE_ADD_FAILED', Loc::getMessage('TASKS_INTERNALS_CHECKLIST_CHECKLIST_TABLE_ADD_FAILED'));
			return $addResult;
		}

		if ($fields['PARENT_ID'] === 0)
		{
			$checkListTree::ensureNodeExists($id);
		}
		else
		{
			$attachResult = $checkListTree::attachNew($id, $fields['PARENT_ID']);
			if (!$attachResult->isSuccess())
			{
				$checkListDataController::delete($id);

				$addResult->loadErrors($attachResult->getErrors());
				return $addResult;
			}
		}

		foreach ($fields['MEMBERS'] as $userId => $data)
		{
			$checkListMemberDataController::add(['ITEM_ID' => $id, 'USER_ID' => $userId, 'TYPE' => $data['TYPE']]);
		}

		if (!empty($fields['ATTACHMENTS']) && ModuleManager::isModuleInstalled('disk'))
		{
			$userFields = ['UF_CHECKLIST_FILES' => $fields['ATTACHMENTS']];
			$USER_FIELD_MANAGER->Update($this->userFieldsEntityIdName, $id, $userFields, $this->userId);

			$this->setFields(['ATTACHMENTS' => $facade::getList(['ATTACHMENTS'], ['ID' => $id])[$id]['ATTACHMENTS']]);
		}

		$this->fields->setId($id);

		$facade::doAddPostActions($this->fields->getEntityId(), $this->userId, $this);

		return $addResult;
	}

	/**
	 * @param array $oldFields
	 * @return array
	 * @throws ObjectException
	 */
	private function getChangedFields($oldFields)
	{
		$changedTableFields = [];
		$changedCommonFields = [];

		$newFields = $this->getFields();
		$possibleChanges = ['TITLE', 'PARENT_ID', 'SORT_INDEX', 'IS_COMPLETE', 'IS_IMPORTANT', 'MEMBERS', 'ATTACHMENTS'];

		foreach ($possibleChanges as $field)
		{
			switch ($field)
			{
				case 'PARENT_ID':
					if ($oldFields[$field] !== $newFields[$field])
					{
						$changedCommonFields[$field] = $newFields[$field];
					}
					break;

				case 'MEMBERS':
					if ($changedMembers = $this->getChangedMembers($oldFields[$field], $newFields[$field]))
					{
						$changedCommonFields[$field] = $changedMembers;
					}
					break;

				case 'ATTACHMENTS':
					if (($changedAttachments = $this->getChangedAttachments($oldFields[$field], $newFields[$field])) !== null)
					{
						$changedCommonFields[$field] = $changedAttachments;
					}
					break;

				default:
					if ($oldFields[$field] !== $newFields[$field])
					{
						$changedTableFields[$field] = $newFields[$field];

						if ($field === 'IS_COMPLETE')
						{
							$userTime = User::getTime($this->userId);

							$changedTableFields['TOGGLED_BY'] = $this->userId;
							$changedTableFields['TOGGLED_DATE'] = new DateTime(UI::formatDateTime($userTime));
						}
					}
					break;
			}
		}

		return [
			'TABLE' => $changedTableFields,
			'COMMON' => $changedCommonFields,
		];
	}

	/**
	 * @param $oldMembers
	 * @param $newMembers
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws Exception
	 */
	private function updateMembers($oldMembers, $newMembers)
	{
		$id = $this->fields->getId();
		$oldTableMembers = [];
		/** @var DataManager $memberDataController */
		$memberDataController = $this->checkListMemberDataController;

		foreach ($oldMembers as $userId => $data)
		{
			$oldMembers[$userId] = $data['TYPE'];
		}

		foreach ($newMembers as $userId => $data)
		{
			$newMembers[$userId] = $data['TYPE'];
		}

		$toDelete = array_diff_assoc($oldMembers, $newMembers);
		$toCreate = array_diff_assoc($newMembers, $oldMembers);
		$toChange = array_intersect_key($toCreate, $toDelete);

		foreach (array_keys($toChange) as $userId)
		{
			unset($toDelete[$userId], $toCreate[$userId]);
		}

		if (!empty($toDelete) || !empty($toChange))
		{
			$oldTableMembers = $memberDataController::getList([
				'select' => ['ID', 'USER_ID', 'TYPE'],
				'filter' => ['ITEM_ID' => $id],
			])->fetchAll();
		}

		foreach ($toDelete as $userId => $type)
		{
			foreach ($oldTableMembers as $key => $member)
			{
				if ($member['USER_ID'] == $userId && $member['TYPE'] === $type)
				{
					$memberDataController::delete($member['ID']);
					unset($oldTableMembers[$key]);
					break;
				}
			}
		}

		foreach ($toCreate as $userId => $type)
		{
			$memberDataController::add(['ITEM_ID' => $id, 'USER_ID' => $userId, 'TYPE' => $type]);
		}

		foreach ($toChange as $userId => $type)
		{
			foreach ($oldTableMembers as $key => $member)
			{
				if ($member['USER_ID'] == $userId && $member['TYPE'] === $oldMembers[$userId])
				{
					$memberDataController::update($member['ID'], ['TYPE' => $type]);
					unset($oldTableMembers[$key]);
					break;
				}
			}
		}
	}

	/**
	 * @param $oldField
	 * @param $newField
	 * @return bool
	 */
	private function getChangedMembers($oldField, $newField)
	{
		$oldMembers = [];
		$newMembers = [];

		foreach ($newField as $userId => $data)
		{
			$newMembers[$userId] = $data['TYPE'];
		}

		foreach ($oldField as $userId => $data)
		{
			$oldMembers[$userId] = $data['TYPE'];
		}

		return array_diff_assoc($newMembers, $oldMembers) || array_diff_assoc($oldMembers, $newMembers);
	}

	/**
	 * @param $oldAttachments
	 * @param $newAttachments
	 * @return array|null
	 */
	private function getChangedAttachments($oldAttachments, $newAttachments)
	{
		$attachmentsChanged = false;
		$changedAttachments = [];

		foreach ($newAttachments as $attachmentId => $fileId)
		{
			if (array_key_exists($attachmentId, $oldAttachments))
			{
				$changedAttachments[] = $attachmentId;
			}
			else if (in_array($attachmentId, $oldAttachments, true))
			{
				$changedAttachments[] = array_search($attachmentId, $oldAttachments, true);
			}
			else
			{
				$changedAttachments[] = $fileId;
				$attachmentsChanged = true;
			}
		}

		if (!$attachmentsChanged)
		{
			foreach (array_keys($oldAttachments) as $id)
			{
				if (!in_array($id, $changedAttachments))
				{
					$attachmentsChanged = true;
					break;
				}
			}
		}

		if (!$attachmentsChanged)
		{
			$changedAttachments = null;
		}

		return $changedAttachments;
	}

	/**
	 * Fills mandatory fields createdBy, sortIndex and (attachments for root items).
	 *
	 * @throws ArgumentException
	 * @throws NotImplementedException
	 * @throws SystemException
	 */
	private function fillMandatoryFields()
	{
		$fields = $this->getFields();

		if (!isset($fields['CREATED_BY']))
		{
			$this->fields->setCreatedBy($fields['USER_ID']);
		}

		if (!isset($fields['SORT_INDEX']))
		{
			$facade = $this->facade;
			$items = $facade::getList(['ID', 'PARENT_ID', 'SORT_INDEX'], [$facade::$entityIdName => $fields['ENTITY_ID']]);
			$sortIndex = $this->getNextSortIndex($items);

			$this->fields->setSortIndex($sortIndex);
		}

		if ($fields['PARENT_ID'] === 0)
		{
			$this->fields->setAttachments([]);
		}
	}

	/**
	 * @param array $items
	 * @return int
	 */
	private function getNextSortIndex($items)
	{
		$neighbours = array_filter(
			$items,
			function ($item)
			{
				return (int)$item['PARENT_ID'] === $this->fields->getParentId();
			}
		);
		$sortIndexes = array_column($neighbours, 'SORT_INDEX');

		return (empty($sortIndexes) ? 0 : (int)max($sortIndexes) + 1);
	}

	/**
	 * @param Connection $connection
	 * @throws ArgumentException
	 * @throws NotImplementedException
	 * @throws SystemException
	 */
	private function recountSortIndexes($connection)
	{
		$facade = $this->facade;
		$entityId = $this->fields->getEntityId();
		$parentId = $this->fields->getParentId();
		$sortIndex = $this->fields->getSortIndex();

		$items = $facade::getList(['ID', 'SORT_INDEX'], [$facade::$entityIdName => $entityId, 'PARENT_ID' => $parentId]);
		$sortIndexes = array_column($items, 'SORT_INDEX', 'ID');

		if (in_array($sortIndex, $sortIndexes))
		{
			$recountedSortIndexes = $this->getRecountedSortIndexes($sortIndexes, $sortIndex);

			try
			{
				$this->updateItemsWithRecountedSortIndexes($recountedSortIndexes, $connection);
			}
			catch (SqlQueryException $exception)
			{
				$facade::logError($exception->getMessage());
			}
		}
	}

	/**
	 * @param $sortIndexes
	 * @param $newSortIndex
	 * @return mixed
	 */
	private function getRecountedSortIndexes($sortIndexes, $newSortIndex)
	{
		$recountedSortIndexes = $sortIndexes;

		$nextIndex = $newSortIndex + 1;

		foreach ($recountedSortIndexes as $id => $index)
		{
			if ($index >= $newSortIndex)
			{
				$recountedSortIndexes[$id] = $nextIndex;
				$nextIndex++;
			}
		}

		return $recountedSortIndexes;
	}

	/**
	 * @param $recountedSortIndexes
	 * @param Connection $connection
	 * @throws SqlQueryException
	 */
	private function updateItemsWithRecountedSortIndexes($recountedSortIndexes, $connection)
	{
		$checkListDataController = $this->checkListDataController;

		$tableName = $checkListDataController::getTableName();
		$sortColumn = $checkListDataController::getSortColumnName();

		$updateSql = "
			UPDATE {$tableName}
			SET {$sortColumn} = CASE ID\n
		";

		foreach ($recountedSortIndexes as $itemId => $sortIndex)
		{
			$updateSql .= "WHEN $itemId THEN $sortIndex\n";
		}

		$updateSql .= "END\nWHERE ID IN (" . implode(', ', array_keys($recountedSortIndexes)) . ")";

		$connection->query($updateSql);
	}
}