<?php
namespace Bitrix\Tasks\Rest\Controllers\Task;

use Bitrix\Tasks\Rest\Controllers\Base;

use \Bitrix\Main\Error;

class Planner extends Base
{
	/**
	 * Return all DB and UF_ fields of file
	 *
	 * @return array
	 */
	public function fieldsAction()
	{
		return [];
	}

	/**
	 * Add task to plan of today
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
	 * Remove existing task from plan of today
	 *
	 * @param int $taskId
	 * @param int $fileId
	 *
	 * @param array $params
	 *
	 * @return bool
	 */
	public function deleteAction($taskId, $fileId, array $params = array())
	{
		return false;
	}

	/**
	 * Get list all task in plan at today
	 *
	 * @param array $params
	 *
	 *
	 * @return array
	 */
	public function listAction(array $params = array())
	{
		return [];
	}
}