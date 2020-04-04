<?php

namespace Bitrix\Tasks\Rest\Controllers\Task;

use Bitrix\Main\Engine\AutoWire\ExactParameter;
use Bitrix\Main\Engine\AutoWire\Parameter;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Tasks\Exception;
use Bitrix\Tasks\Internals\Task\CheckListTable;
use Bitrix\Tasks\Rest\Controllers\Base;

class Checklist extends Base
{
	const ACCESS_CREATE = 100;
	const ACCESS_READ = 200;
	const ACCESS_UPDATE = 300;
	const ACCESS_DELETE = 400;

	const ACCESS_SORT = 500;

	/**
	 * @return array;
	 */
	public function getAutoWiredParameters()
	{
		return [
			new Parameter(
				\CTaskItem::class, function ($className, $id) {
				$userId = CurrentUser::get()->getId();

				/** @var \CTaskItem $className */
				return new $className($id, $userId);
			}
			),
			new ExactParameter(
				\CTaskCheckListItem::class, 'checkListItem', function ($className, $taskId, $checkListItemId) {
				$userId = CurrentUser::get()->getId();
				$task = new \CTaskItem($taskId, $userId);

				/** @var \CTaskChecklistItem $className */
				return new $className($task, $checkListItemId);
			}
			),
		];
	}

	/**
	 * Return all fields of checklist item
	 *
	 * @return array
	 */
	public function fieldsAction()
	{
		return [];
	}

	/**
	 *  Add checklist item to task
	 *
	 * @param \CTaskItem $task Number of task (id)
	 * @param array $fields
	 *
	 * @return array
	 * @throws \TasksException
	 */
	public function addAction(\CTaskItem $task, array $fields)
	{
		$result = \CTaskCheckListItem::add($task, $fields);

		return ['item' => $result->getData(false)];
	}

	/**
	 * Remove existing checklist item
	 *
	 * @param \CTaskCheckListItem $checkListItem
	 *
	 * @return array
	 * @throws \TasksException
	 */
	public function deleteAction(\CTaskCheckListItem $checkListItem)
	{
		$checkListItem->delete();

		return ['item' => true];
	}

	/**
	 * Get list all task checklist item
	 *
	 * @param \CTaskItem $task
	 * @param array $filter
	 *
	 * @param array $select
	 * @param array $order
	 *
	 * @return array
	 * @throws \TasksException
	 */
	public function listAction(\CTaskItem $task, array $filter = array(), array $select = array(), array $order = array())
	{
		$filter['=TASK_ID']=$task->getId();

		$list = \CTaskCheckListItem::getList([
			'filter'=>$filter,
			'select'=>$select,
			'order'=>$order
		]);

		return ['items'=>$list];
	}

	/**
	 * @param \CTaskCheckListItem $checkListItem
	 *
	 * @return array|null
	 * @throws \TasksException
	 */
	public function completeAction(\CTaskCheckListItem $checkListItem)
	{
		$checkListItem->complete();

		return ['item' => $checkListItem->getData(false)];
	}

	/**
	 * @param \CTaskCheckListItem $checkListItem
	 *
	 * @return array|null
	 * @throws \TasksException
	 */
	public function renewAction(\CTaskCheckListItem $checkListItem)
	{
		$checkListItem->renew();

		return ['item' => $checkListItem->getData(false)];
	}

	/***************************************** ACTIONS ****************************************************************/

	/**
	 * Update task checklist item
	 *
	 * @param \CTaskCheckListItem $checkListItem
	 * @param array $fields
	 *
	 * @return array|null
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \CTaskAssertException
	 * @throws \TasksException
	 */
	public function updateAction(\CTaskCheckListItem $checkListItem, array $fields)
	{
		$checkListItem->update($fields);

		return ['item' => $checkListItem->getData(false)];
	}

	/**
	 * @param \CTaskCheckListItem $checkListItem
	 * @param int $afterItemId
	 *
	 * @return bool
	 * @throws \TasksException
	 */
	public function moveAfterAction(\CTaskCheckListItem $checkListItem, $afterItemId)
	{
		$checkListItem->moveAfterItem($afterItemId);

		return true;
	}

	protected function buildErrorFromException(\Exception $exception)
	{
		if (!($exception instanceof Exception))
		{
			return parent::buildErrorFromException($exception);
		}

		if ($exception instanceof \TasksException)
		{
			/** @var \CAdminException $orig */
			$orig = $exception->getMessageOrigin();
			foreach ($orig->GetMessages() as $message)
			{
				return new Error($message['text'], $message['id']);
			}
		}

		return new Error($exception->getMessage(), $exception->getCode());
	}
}