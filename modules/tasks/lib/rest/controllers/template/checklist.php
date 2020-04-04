<?php
namespace Bitrix\Tasks\Rest\Controllers\Template;

use Bitrix\Tasks\Rest\Controllers\Base;

use \Bitrix\Main\Error;

class Checklist extends Base
{
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
	 * Add checklist item to task
	 *
	 * @param int $taskId
	 * @param array $fields
	 *
	 * @param array $params
	 *
	 * @return int
	 */
	public function addAction($taskId, array $fields, array $params = array())
	{
		return 1;
	}

	/**
	 * Update task checklist item
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
	 * Remove existing checklist item
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
	 * Get list all task checklist item
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

	/***************************************** ACTIONS ****************************************************************/

	/**
	 * @param int $taskId
	 * @param int $itemId
	 *
	 * @param array $params
	 *
	 * @return bool
	 */
	public function completeAction($taskId, $itemId, array $params = array())
	{
		return false;
	}

	/**
	 * @param int $taskId
	 * @param int $itemId
	 *
	 * @param array $params
	 *
	 * @return bool
	 */
	public function renewAction($taskId, $itemId, array $params = array())
	{
		return false;
	}

	/**
	 * @param int $taskId
	 * @param int $itemId
	 * @param int $afterItemId
	 *
	 * @param array $params
	 *
	 * @return bool
	 */
	public function moveAfterAction($taskId, $itemId, $afterItemId, array $params = array())
	{
		return false;
	}

}