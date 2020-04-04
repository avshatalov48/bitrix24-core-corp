<?
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

use \Bitrix\Tasks\Util\Error\Collection;
use \Bitrix\Tasks;
use \Bitrix\Tasks\Manager;
use \Bitrix\Tasks\Util;

final class CheckList extends \Bitrix\Tasks\Dispatcher\RestrictedAction
{
	/**
	 * Get all check list items for a specified task
	 */
	public function getListByTask($taskId, array $order = array())
	{
		$result = array();

		if($taskId = $this->checkTaskId($taskId))
		{
			list($data, $can) = Manager\Task\Checklist::getListByParentEntity(Util\User::getId(), $taskId);

			$result['DATA'] = $data;
			$result['CAN'] = $can;
		}

		return $result;
	}

	/**
	 * Add a new check list item to a specified task
	 */
	public function add(array $data, array $parameters = array())
	{
		$result = array();

		$mgrResult = Manager\Task\Checklist::add(Util\User::getId(), $data, array(
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
	 * Update a check list item
	 */
	public function update($id, array $data, array $parameters = array())
	{
		$result = $mgrResult = array();

		if($id = $this->checkId($id))
		{
			if(array_key_exists('TITLE', $data))
			{
				$data[ 'TITLE' ] = htmlspecialcharsback($data[ 'TITLE' ]);
			}

			$mgrResult = Manager\Task\Checklist::update(Util\User::getId(), $id, $data, array(
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
	 * Delete a check list item
	 */
	public function delete($id)
	{
		$result = array();

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
	 * Set a specified check list item complete
	 */
	public function complete($id)
	{
		return $this->update($id, array('IS_COMPLETE' => 'Y'));
	}

	/**
	 * Set a specified check list item uncomplete
	 */
	public function renew($id)
	{
		return $this->update($id, array('IS_COMPLETE' => 'N'));
	}

	/**
	 * Move a specified check list item after another check list item
	 */
	public function moveAfter($id, $afterId)
	{
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