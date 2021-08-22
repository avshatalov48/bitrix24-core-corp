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
use \Bitrix\Tasks\Manager;
use \Bitrix\Tasks\Util;

final class CheckList extends \Bitrix\Tasks\Dispatcher\RestrictedAction
{
	/**
	 * @deprecated since tasks 21.200.0
	 *
	 * Get all check list items for a specified task
	 */
	public function getListByTask($taskId, array $order = array())
	{
		$this->addForbiddenError();
		return [];

		$result = [];

		if (!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_READ, (int)$taskId))
		{
			$this->addForbiddenError();
			return $result;
		}

		if($taskId = $this->checkTaskId($taskId))
		{
			list($data, $can) = Manager\Task\Checklist::getListByParentEntity($this->userId, $taskId);

			$result['DATA'] = $data;
			$result['CAN'] = $can;
		}

		return $result;
	}

	/**
	 * @deprecated since tasks 21.200.0
	 *
	 * Add a new check list item to a specified task
	 */
	public function add(array $data, array $parameters = array())
	{
		$this->addForbiddenError();
		return [];

		$result = [];

		if (!TaskAccessController::can($this->userId, ActionDictionary::ACTION_CHECKLIST_ADD, (int)$data['TASK_ID']))
		{
			$this->addForbiddenError();
			return $result;
		}

		$mgrResult = Manager\Task\Checklist::add($this->userId, $data, array(
			'ERRORS' => $this->errors,
			'PUBLIC_MODE' => true,
		));

		if($this->errors->checkNoFatals())
		{
			$result['DATA'] = $mgrResult['DATA'];
		}

		return $result;
	}

	/**
	 * @deprecated since tasks 21.200.0
	 *
	 * Update a check list item
	 */
	public function update($id, array $data, array $parameters = array())
	{
		$this->addForbiddenError();
		return [];

		$result = $mgrResult = array();

		if (!TaskAccessController::can($this->userId, ActionDictionary::ACTION_CHECKLIST_SAVE, $this->getOwnerTaskId($id), $data))
		{
			$this->addForbiddenError();
			return $result;
		}

		if($id = $this->checkId($id))
		{
			if(array_key_exists('TITLE', $data))
			{
				$data[ 'TITLE' ] = htmlspecialcharsback($data[ 'TITLE' ]);
			}

			$mgrResult = Manager\Task\Checklist::update($this->userId, $id, $data, array(
				'ERRORS' => $this->errors,
				'PUBLIC_MODE' => true,
			));
		}

		if($this->errors->checkNoFatals())
		{
			$result['DATA'] = $mgrResult['DATA'];
		}

		return $result;
	}

	/**
	 * @deprecated since tasks 21.200.0
	 *
	 * Delete a check list item
	 */
	public function delete($id)
	{
		$this->addForbiddenError();
		return [];

		$result = [];

		if (!TaskAccessController::can($this->userId, ActionDictionary::ACTION_CHECKLIST_EDIT, $this->getOwnerTaskId($id)))
		{
			$this->addForbiddenError();
			return $result;
		}

		if($id = $this->checkId($id))
		{
			// get task id
			$taskId = $this->getOwnerTaskId($id);
			if($taskId)
			{
				$task = \CTaskItem::getInstance($taskId, Util\User::getId());
				$item = new \CTaskCheckListItem($task, $id);

				$item->delete();
			}
		}

		return $result;
	}

	/**
	 * @deprecated since tasks 21.200.0
	 *
	 * Set a specified check list item complete
	 */
	public function complete($id)
	{
		return $this->update($id, array('IS_COMPLETE' => 'Y'));
	}

	/**
	 * @deprecated since tasks 21.200.0
	 *
	 * Set a specified check list item uncomplete
	 */
	public function renew($id)
	{
		return $this->update($id, array('IS_COMPLETE' => 'N'));
	}

	/**
	 * @deprecated since tasks 21.200.0
	 *
	 * Move a specified check list item after another check list item
	 */
	public function moveAfter($id, $afterId)
	{
		$this->addForbiddenError();
		return [];

		// you can move check list items ONLY when you have write access to the task
		$result = array();

		if($id = $this->checkId($id))
		{
			$afterId = intval($afterId);

			if($id != $afterId)
			{
				// get task id
				$taskId = $this->getOwnerTaskId($id);
				if($taskId)
				{
					$task = \CTaskItem::getInstance($taskId, Util\User::getId());

					$item = new \CTaskCheckListItem($task, $id);
					$item->moveAfterItem($afterId);
				}
			}
		}

		return $result;
	}

	protected function getOwnerTaskId($itemId)
	{
		$taskId = \CTaskCheckListItem::getTaskIdByItemId($itemId);
		if(intval($taskId))
		{
			return $taskId;
		}
		else
		{
			$this->errors->add('ACCESS_DENIED.NO_TASK', 'Task not found');
		}

		return false;
	}
}