<?php
namespace Bitrix\Tasks\Rest\Controllers\Task;

use Bitrix\Tasks\Rest\Controllers\Base;

use Bitrix\Tasks\Manager\Task;

class Elapsedtime extends Base
{
	/**
	 * Return all fields of elapsed time
	 *
	 * @return array
	 */
	public function fieldsAction()
	{
		return  [];
	}

	/**
	 * Add elapsed time to task
	 *
	 * @param array $fields
	 *
	 * @param array $params
	 *
	 * @return array
	 */
	public function addAction(array $fields, array $params = [])
	{
		$errors = [];

		$mgrResult = Task\ElapsedTime::add(
			$this->getCurrentUser()->getId(),
			$fields,
			[
				'PUBLIC_MODE' => true,
				'ERRORS' => $errors,
				'RETURN_ENTITY' => $params['RETURN_ENTITY'], // just an exception for this type of entity
			]
		);

		return [__CLASS__ => $mgrResult['DATA']];
	}

	/**
	 * Update task elapsed time
	 *
	 * @param int $taskId
	 * @param int $itemId
	 * @param array $fields
	 *
	 * @param array $params
	 *
	 * @return bool
	 */
	public function updateAction($taskId, $itemId, array $fields, array $params = array())
	{
		return false;
	}

	/**
	 * Remove existing elapsed time
	 *
	 * @param int $taskId
	 * @param int $itemId
	 *
	 * @param array $params
	 *
	 * @return bool
	 */
	public function deleteAction($taskId, $itemId, array $params = array())
	{
		return false;
	}

	/**
	 * Get list all task elapsed time
	 *
	 * @param int $taskId
	 * @param array $params ORM get list params
	 *
	 *
	 * @return array
	 */
	public function listAction($taskId, array $params = array())
	{
		return [];
	}









	/**
	 * Get all elapsed time items for a specified task
	 */
	public function getListByTask($taskId, array $order = array(), array $filter = array())
	{
		$result = array();

		if($taskId = $this->checkTaskId($taskId))
		{
			$result = Manager\Task\ElapsedTime::getListByParentEntity(User::getId(), $taskId);
		}

		return $result;
	}


	/**
	 * Update an elapsed time record
	 */
	public function update($id, array $data, array $parameters = array())
	{
		$mgrResult = Manager\Task\ElapsedTime::update(User::getId(), $id, $data, array(
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
		$result = array();

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