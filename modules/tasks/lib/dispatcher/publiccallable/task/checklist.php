<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2015 Bitrix
 * 
 * @access private
 * 
 * Each method you put here you`ll be able to call as ENTITY_NAME.METHOD_NAME, so be careful.
 */

namespace Bitrix\Tasks\Dispatcher\PublicCallable\Task;

use \Bitrix\Tasks\Util\Error\Collection;
use \Bitrix\Tasks;

final class CheckList extends \Bitrix\Tasks\Dispatcher\PublicCallable
{
	/**
	 * Get all check list items for a specified task
	 */
	public function getListByTask($taskId, array $order = array())
	{
		global $USER;

		$result = array();

		if($taskId = $this->checkTaskId($taskId))
		{
			$task = \CTaskItem::getInstanceFromPool($taskId, $USER->GetId()); // or directly, new \CTaskItem($taskId, $USER->GetId());

			// todo: make this and TasksTaskComponent::getTaskDataChecklist() use common code
			$items = \CTaskCheckListItem::fetchList($task, $order);
			foreach($items as $item)
			{
				$data = $item->getData(false);
				$id = $data->getId();

				$result['DATA']['CHECKLIST'][$id] = $data;
				$result['CAN']['CHECKLIST'][$id]['ACTION'] = array(
					'MODIFY' => $item->isActionAllowed(\CTaskCheckListItem::ACTION_MODIFY),
					'REMOVE' => $item->isActionAllowed(\CTaskCheckListItem::ACTION_REMOVE),
					'TOGGLE' => $item->isActionAllowed(\CTaskCheckListItem::ACTION_TOGGLE)
				);
			}
		}

		return $result;
	}

	/**
	 * Add a new check list item to a specified task
	 */
	public function add(array $data, array $parameters = array())
	{
		global $USER;

		$result = array();

		if($taskId = $this->checkTaskId($data['TASK_ID']))
		{
			$task = \CTaskItem::getInstanceFromPool($taskId, $USER->GetId()); // or directly, new \CTaskItem($taskId, $USER->GetId());

			list($task, $id) = \CTaskCheckListItem::add($task, $data);

			$result['DATA']['CHECKLIST']['ID'] = $id;
		}

		return $result;
	}

	/**
	 * Update a check list item
	 */
	public function update($id, array $data, array $parameters = array())
	{
		global $USER;

		$result = array();

		if($id = $this->checkId($id))
		{
			if(isset($data['TASK_ID']))
			{
				$this->add('FIELD_NOT_ALLOWED', 'You are not allowed to pass TASK_ID field', Collection::TYPE_WARNING, array('TASK_ID'));
				unset($data['TASK_ID']); // not allowed to change TASK_ID of an existing item
			}

			// get task id
			$taskId = $this->getOwnerTaskId($id);
			if($taskId)
			{
				$task = \CTaskItem::getInstanceFromPool($taskId, $USER->GetId()); // or directly, new \CTaskItem($taskId, $USER->GetId());
				$item = new \CTaskCheckListItem($task, $id);

				$item->update($data);
			}
		}

		return $result;
	}

	/**
	 * Delete a check list item
	 */
	public function delete($id)
	{
		global $USER;

		$result = array();

		if($id = $this->checkId($id))
		{
			// get task id
			$taskId = $this->getOwnerTaskId($id);
			if($taskId)
			{
				$task = \CTaskItem::getInstanceFromPool($taskId, $USER->GetId()); // or directly, new \CTaskItem($taskId, $USER->GetId());
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
		global $USER;

		$result = array();

		if(($id = $this->checkId($id)) && ($afterId = $this->checkId($afterId)))
		{
			if($id != $afterId)
			{
				// get task id
				$taskId = $this->getOwnerTaskId($id);
				if($taskId)
				{
					$task = \CTaskItem::getInstanceFromPool($taskId, $USER->GetId()); // or directly, new \CTaskItem($taskId, $USER->GetId());
					if(!$task->isActionAllowed(\CTaskItem::ACTION_EDIT))
					{
						throw new Tasks\ActionNotAllowedException('Checklist move after', array(
							'AUX' => array(
								'ERROR' => array(
									'TASK_ID' => $taskId,
									'ITEM' => $id, 
									'AFTER_ITEM' => $afterId
								),
							)
						));
					}

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
			$this->add('ITEM_NOT_FOUND', 'Item not found');
		}

		return false;
	}

	protected function checkId($id)
	{
		return parent::checkId($id, 'Elapsed item');
	}
}