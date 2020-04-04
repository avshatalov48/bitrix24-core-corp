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

final class ElapsedTime extends \Bitrix\Tasks\Dispatcher\PublicCallable
{
	/**
	 * Get all elapsed time items for a specified task
	 */
	public function getListByTask($taskId, array $order = array(), array $filter = array())
	{
		global $USER;

		$result = array();

		if($taskId = $this->checkTaskId($taskId))
		{
			$task = \CTaskItem::getInstanceFromPool($taskId, $USER->GetId()); // or directly, new \CTaskItem($taskId, $USER->GetId());

			// todo: make this and TasksTaskComponent::getTaskDataElapsedTime() use common code
			$items = \CTaskElapsedItem::fetchList($task, $order, $filter);
			foreach($items as $item)
			{
				$data = $item->getData(false);
				$id = $data->getId();

				$result['DATA']['ELAPSEDTIME'][$id] = $data;
				$result['CAN']['ELAPSEDTIME'][$id]['ACTION'] = array(
					'MODIFY' => $item->isActionAllowed(\CTaskElapsedItem::ACTION_ELAPSED_TIME_MODIFY),
					'REMOVE' => $item->isActionAllowed(\CTaskElapsedItem::ACTION_ELAPSED_TIME_REMOVE),
				);
			}
		}

		return $result;
	}

	/**
	 * Add a new elapsed time record to a specified task
	 */
	public function add(array $data, array $parameters = array())
	{
		global $USER;

		$result = array();

		if($taskId = $this->checkTaskId($data['TASK_ID']))
		{
			$task = \CTaskItem::getInstanceFromPool($taskId, $USER->GetId()); // or directly, new \CTaskItem($taskId, $USER->GetId());

			list($task, $id) = \CTaskElapsedItem::add($task, $data);

			$result['DATA']['ELAPSEDTIME']['ID'] = $id;
		}

		return $result;
	}

	/**
	 * Update an elapsed time record
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
				$item = new \CTaskElapsedItem($task, $id);

				$item->update($data);
			}
		}

		return $result;
	}

	/**
	 * Delete an elapsed time record
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
				$item = new \CTaskElapsedItem($task, $id);

				$item->delete();
			}
		}

		return $result;
	}

	protected function getOwnerTaskId($itemId)
	{
		$item = \CTaskElapsedTime::getList(array(), array('ID' => $itemId), array('skipJoinUsers' => false))->fetch();
		if(is_array($item) && !empty($item))
		{
			return $item['TASK_ID'];
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