<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2015 Bitrix
 *
 * @access private
 *
 * Each method you put here you`ll be able to call as ENTITY_NAME.METHOD_NAME via AJAX and\or REST, so be careful.
 */

namespace Bitrix\Tasks\Dispatcher\PublicAction\Task;

use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Access\TaskAccessController;
use Bitrix\Tasks\Manager;
use Bitrix\Tasks\Util\User;

final class ElapsedTime extends \Bitrix\Tasks\Dispatcher\RestrictedAction
{
	/**
	 * Get all elapsed time items for a specified task
	 */
	public function getListByTask($taskId, array $order = array(), array $filter = array())
	{
		$result = [];

		if (!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_READ, (int)$taskId))
		{
			$this->addForbiddenError();
			return $result;
		}

		if($taskId = $this->checkTaskId($taskId))
		{
			$result = Manager\Task\ElapsedTime::getListByParentEntity($this->userId, $taskId);
		}

		return $result;
	}

	/**
	 * Add a new elapsed time record to a specified task
	 */
	public function add(array $data, array $parameters = array())
	{
		if (!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_ELAPSED_TIME, (int)$data['TASK_ID']))
		{
			$this->addForbiddenError();
			return [];
		}

		$mgrResult = Manager\Task\ElapsedTime::add($this->userId, $data, array(
			'PUBLIC_MODE' => true,
			'ERRORS' => $this->errors,
			'RETURN_ENTITY' => $parameters['RETURN_ENTITY'], // just an exception for this type of entity
		));

		return array(
			'DATA' => $mgrResult['DATA'],
			'CAN' => $mgrResult['CAN'],
		);
	}

	/**
	 * Update an elapsed time record
	 */
	public function update($id, array $data, array $parameters = array())
	{
		$taskId = $this->getOwnerTaskId($id);

		if (!$taskId || !TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_ELAPSED_TIME, $taskId))
		{
			$this->addForbiddenError();
			return [];
		}

		$mgrResult = Manager\Task\ElapsedTime::update($this->userId, $id, $data, array(
			'PUBLIC_MODE' => true,
			'ERRORS' => $this->errors,
			'RETURN_ENTITY' => $parameters['RETURN_ENTITY'],  // just an exception for this type of entity
		));

		return array(
			'DATA' => $mgrResult['DATA'],
			'CAN' => $mgrResult['CAN'],
		);
	}

	/**
	 * Delete an elapsed time record
	 */
	public function delete($id)
	{
		$result = [];

		$taskId = $this->getOwnerTaskId($id);

		if (!$taskId || !TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_ELAPSED_TIME, (int)$taskId))
		{
			$this->addForbiddenError();
			return [];
		}

		if($id = $this->checkId($id))
		{
			// get task id
			$taskId = $this->getOwnerTaskId($id);
			if($taskId)
			{
				$task = \CTaskItem::getInstanceFromPool($taskId, User::getId()); // or directly, new \CTaskItem($taskId, User::getId());
				$item = new \CTaskElapsedItem($task, $id);
				$item->delete();
			}
		}

		return $result;
	}

	private function getOwnerTaskId($itemId)
	{
		$item = \CTaskElapsedTime::getList(array(), array('ID' => $itemId), array('skipJoinUsers' => false))->fetch();
		if(is_array($item) && !empty($item))
		{
			return $item['TASK_ID'];
		}
		else
		{
			$this->errors->add('ITEM_NOT_FOUND', 'Item not found');
		}

		return false;
	}
}