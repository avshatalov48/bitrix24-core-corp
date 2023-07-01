<?php

namespace Bitrix\Tasks\Rest\Controllers\Task;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Db\SqlQueryException;
use Bitrix\Main\Engine\AutoWire\ExactParameter;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\ObjectException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Access\TaskAccessController;
use Bitrix\Tasks\Exception;
use Bitrix\Tasks\CheckList\Internals\CheckList as CheckListItem;
use Bitrix\Tasks\CheckList\Task\TaskCheckListFacade;
use Bitrix\Tasks\Rest\Controllers\Base;
use Bitrix\Tasks\Util\Result;

use CAdminException;
use CTaskItem;
use TasksException;

Loc::loadMessages(__FILE__);

/**
 * Class Checklist
 *
 * @package Bitrix\Tasks\Rest\Controllers\Task
 */
class Checklist extends Base
{
	/**
	 * @return array;
	 */
	public function getAutoWiredParameters()
	{
		return [
			new ExactParameter(
				CheckListItem::class,
				'checkListItem',
				static function ($className, $checkListItemId)
				{
					$userId = CurrentUser::get()->getId();
					$fields = TaskCheckListFacade::getList([], ['ID' => $checkListItemId])[$checkListItemId];

					/** @var ChecklistItem $className */
					return new $className(0, $userId, TaskCheckListFacade::class, $fields);
				}
			),
		];
	}

	/**
	 * Saves CheckList tree.
	 *
	 * @param int $taskId Task id.
	 * @param array $items List fields.
	 * @return array|null
	 * @throws ArgumentException
	 * @throws NotImplementedException
	 * @throws ObjectException
	 * @throws SystemException
	 */
	public function saveAction(int $taskId, array $items = [], array $parameters = [])
	{
		$userId = CurrentUser::get()->getId();

		if (!TaskAccessController::can($userId, ActionDictionary::ACTION_CHECKLIST_SAVE, $taskId, $items))
		{
			$this->errorCollection->add(
				[new Error(Loc::getMessage('TASKS_REST_TASK_CHECKLIST_ACCESS_DENIED'))]
			);

			return null;
		}

		foreach ($items as $id => $item)
		{
			$item['ID'] = ((int)($item['ID'] ?? null) === 0 ? null : (int)$item['ID']);

			$item['IS_COMPLETE'] = (
				($item['IS_COMPLETE'] === true)
				|| ((int) $item['IS_COMPLETE'] > 0)
			);
			$item['IS_IMPORTANT'] = (
				($item['IS_IMPORTANT'] === true)
				|| ((int) $item['IS_IMPORTANT'] > 0)
			);

			$items[$item['NODE_ID']] = $item;

			unset($items[$id]);
		}

		$result = TaskCheckListFacade::merge($taskId, $userId, $items, $parameters);

		return $this->getReturn($result);
	}

	/**
	 * @param $taskId
	 * @param CheckListItem $checkListItem
	 * @return array|null
	 */
	public function getAction($taskId, CheckListItem $checkListItem)
	{
		try
		{
			$task = new CTaskItem($taskId, CurrentUser::get()->getId());
		}
		catch (\CTaskAssertException $e)
		{
			$this->errorCollection->add([new Error(Loc::getMessage('TASKS_REST_TASK_CHECKLIST_ACCESS_DENIED'))]);
			return null;
		}

		if (!$task->checkCanRead())
		{
			$this->errorCollection->add([new Error(Loc::getMessage('TASKS_REST_TASK_CHECKLIST_ACCESS_DENIED'))]);
			return null;
		}

		return $this->getReturnValue($checkListItem);
	}

	/**
	 * @param $taskId
	 * @param array $filter
	 * @param array $select
	 * @param array $order
	 * @return array|null
	 * @throws ArgumentException
	 * @throws SystemException
	 */
	public function listAction($taskId, array $filter = [], array $select = [], array $order = [])
	{
		try
		{
			$task = new CTaskItem($taskId, CurrentUser::get()->getId());
		}
		catch (\CTaskAssertException $e)
		{
			$this->errorCollection->add([new Error(Loc::getMessage('TASKS_REST_TASK_CHECKLIST_ACCESS_DENIED'))]);
			return null;
		}

		if (!$task->checkCanRead())
		{
			$this->errorCollection->add([new Error(Loc::getMessage('TASKS_REST_TASK_CHECKLIST_ACCESS_DENIED'))]);
			return null;
		}

		$filter['TASK_ID'] = $taskId;
		$items = TaskCheckListFacade::getList($select, $filter, $order);

		foreach (array_keys($items) as $id)
		{
			unset($items[$id]['ENTITY_ID'], $items[$id]['UF_CHECKLIST_FILES']);
		}

		return ['checkListItems' => $this->convertKeysToCamelCase($items)];
	}

	/**
	 * @param $taskId
	 * @param array $fields
	 * @return array|null
	 * @throws ObjectException
	 * @throws SystemException
	 */
	public function addAction($taskId, array $fields)
	{
		$addResult = TaskCheckListFacade::add($taskId, CurrentUser::get()->getId(), $fields);
		return $this->getReturn($addResult);
	}

	/**
	 * @param $taskId
	 * @param CheckListItem $checkListItem
	 * @param array $fields
	 * @return array|null
	 * @throws ArgumentException
	 * @throws NotImplementedException
	 * @throws ObjectException
	 * @throws SqlQueryException
	 * @throws SystemException
	 * @throws ObjectPropertyException
	 */
	public function updateAction($taskId, CheckListItem $checkListItem, array $fields)
	{
		$updateResult = TaskCheckListFacade::update($taskId, CurrentUser::get()->getId(), $checkListItem, $fields);
		return $this->getReturn($updateResult);
	}

	/**
	 * @param $taskId
	 * @param CheckListItem $checkListItem
	 * @return bool|null
	 * @throws ArgumentException
	 * @throws NotImplementedException
	 * @throws SystemException
	 */
	public function deleteAction($taskId, CheckListItem $checkListItem)
	{
		$deleteResult = TaskCheckListFacade::delete($taskId, CurrentUser::get()->getId(), $checkListItem);

		if ($deleteResult->isSuccess())
		{
			return true;
		}

		if ($deleteResult->getErrors())
		{
			$this->errorCollection[] = new Error($deleteResult->getErrors()->getMessages()[0]);
		}

		return null;
	}

	/**
	 * @param $taskId
	 * @param CheckListItem $checkListItem
	 * @return array|null
	 * @throws ArgumentException
	 * @throws NotImplementedException
	 * @throws ObjectException
	 * @throws ObjectPropertyException
	 * @throws SqlQueryException
	 * @throws SystemException
	 */
	public function completeAction($taskId, CheckListItem $checkListItem)
	{
		$completeResult = TaskCheckListFacade::complete($taskId, CurrentUser::get()->getId(), $checkListItem);
		return $this->getReturn($completeResult);
	}

	public function completeAllAction($taskId, CheckListItem $checkListItem)
	{
		$result = [];

		$completeAllResult = TaskCheckListFacade::completeAll($taskId, CurrentUser::get()->getId(), $checkListItem);
		foreach ($completeAllResult->getData() as $itemCompleteResult)
		{
			$result[] = $this->getReturnValue($itemCompleteResult)['checkListItem'];
		}

		return $result;
	}

	/**
	 * @param $taskId
	 * @param CheckListItem $checkListItem
	 * @return array|null
	 * @throws ArgumentException
	 * @throws NotImplementedException
	 * @throws ObjectException
	 * @throws ObjectPropertyException
	 * @throws SqlQueryException
	 * @throws SystemException
	 */
	public function renewAction($taskId, CheckListItem $checkListItem)
	{
		$renewResult = TaskCheckListFacade::renew($taskId, CurrentUser::get()->getId(), $checkListItem);
		return $this->getReturn($renewResult);
	}

	/**
	 * @param $taskId
	 * @param CheckListItem $checkListItem
	 * @param $beforeItemId
	 * @return array|null
	 * @throws ArgumentException
	 * @throws SystemException
	 */
	public function moveBeforeAction($taskId, CheckListItem $checkListItem, $beforeItemId)
	{
		$userId = CurrentUser::get()->getId();
		$position = TaskCheckListFacade::MOVING_POSITION_BEFORE;
		$moveResult = TaskCheckListFacade::moveItem($taskId, $userId, $checkListItem, $beforeItemId, $position);

		return $this->getReturn($moveResult);
	}

	/**
	 * @param $taskId
	 * @param CheckListItem $checkListItem
	 * @param $afterItemId
	 * @return array|null
	 * @throws ArgumentException
	 * @throws SystemException
	 */
	public function moveAfterAction($taskId, CheckListItem $checkListItem, $afterItemId)
	{
		$userId = CurrentUser::get()->getId();
		$position = TaskCheckListFacade::MOVING_POSITION_AFTER;
		$moveResult = TaskCheckListFacade::moveItem($taskId, $userId, $checkListItem, $afterItemId, $position);

		return $this->getReturn($moveResult);
	}

	/**
	 * @param $taskId
	 * @param CheckListItem $checkListItem
	 * @param array $members
	 * @return array|null
	 * @throws ObjectException
	 * @throws SystemException
	 */
	public function addMembersAction($taskId, CheckListItem $checkListItem, array $members)
	{
		$userId = CurrentUser::get()->getId();
		$addMembersResult = TaskCheckListFacade::addMembers($taskId, $userId, $checkListItem, $members);

		return $this->getReturn($addMembersResult);
	}

	/**
	 * @param $taskId
	 * @param CheckListItem $checkListItem
	 * @param array $membersIds
	 * @return array|null
	 * @throws ObjectException
	 * @throws SystemException
	 */
	public function removeMembersAction($taskId, CheckListItem $checkListItem, array $membersIds)
	{
		$userId = CurrentUser::get()->getId();
		$removeMembersResult = TaskCheckListFacade::removeMembers($taskId, $userId, $checkListItem, $membersIds);

		return $this->getReturn($removeMembersResult);
	}

	/**
	 * @param $taskId
	 * @param CheckListItem $checkListItem
	 * @param array $attachmentParameters
	 * @return array|null
	 */
	public function addAttachmentByContentAction($taskId, CheckListItem $checkListItem, array $attachmentParameters)
	{
		$addAttachmentResult = TaskCheckListFacade::addAttachmentByContent(
			$taskId,
			CurrentUser::get()->getId(),
			$checkListItem,
			$attachmentParameters
		);

		return $this->getReturn($addAttachmentResult);
	}

	/**
	 * @param $taskId
	 * @param CheckListItem $checkListItem
	 * @param array $filesIds
	 * @return array|null
	 * @throws ArgumentException
	 * @throws NotImplementedException
	 * @throws ObjectException
	 * @throws ObjectPropertyException
	 * @throws SqlQueryException
	 * @throws SystemException
	 */
	public function addAttachmentsFromDiskAction($taskId, CheckListItem $checkListItem, array $filesIds)
	{
		$addAttachmentsResult = TaskCheckListFacade::addAttachmentsFromDisk(
			$taskId,
			CurrentUser::get()->getId(),
			$checkListItem,
			$filesIds
		);

		return $this->getReturn($addAttachmentsResult);
	}

	/**
	 * @param $taskId
	 * @param CheckListItem $checkListItem
	 * @param array $attachmentsIds
	 * @return array|null
	 * @throws ArgumentException
	 * @throws NotImplementedException
	 * @throws ObjectException
	 * @throws ObjectPropertyException
	 * @throws SqlQueryException
	 * @throws SystemException
	 */
	public function removeAttachmentsAction($taskId, CheckListItem $checkListItem, array $attachmentsIds)
	{
		$removeAttachmentsResult = TaskCheckListFacade::removeAttachments(
			$taskId,
			CurrentUser::get()->getId(),
			$checkListItem,
			$attachmentsIds
		);

		return $this->getReturn($removeAttachmentsResult);
	}

	/**
	 * @param Result $result
	 * @return array|null
	 */
	private function getReturn(Result $result)
	{
		if ($result->isSuccess())
		{
			return $this->getReturnValue($result);
		}

		if ($errors = $result->getErrors())
		{
			$this->errorCollection[] = new Error($errors->getMessages()[0]);
		}

		return null;
	}

	/**
	 * @param $value
	 * @return array
	 */
	private function getReturnValue($value)
	{
		$checkListItemData = [];

		if ($value instanceof Result)
		{
			/** @var CheckListItem $checkListItem */
			$checkListItem = ($value->getData()['ITEM'] ?? null);
			if ($checkListItem)
			{
				$checkListItemData = $checkListItem->getFields();
			}
			else
			{
				$checkListItemData = $value->getData();
			}
		}
		else if ($value instanceof CheckListItem)
		{
			$checkListItemData = $value->getFields();
		}

		if ($checkListItemData)
		{
			$checkListItemData['TASK_ID'] = ($checkListItemData['ENTITY_ID'] ?? null);
			unset($checkListItemData['ENTITY_ID']);
		}

		return ['checkListItem' => $this->convertKeysToCamelCase($checkListItemData)];
	}

	/**
	 * @param \Exception $exception
	 * @return Error
	 */
	protected function buildErrorFromException(\Exception $exception)
	{
		if (!($exception instanceof Exception))
		{
			return parent::buildErrorFromException($exception);
		}

		return new Error($exception->getMessageOrigin(), $exception->getCode());
	}
}