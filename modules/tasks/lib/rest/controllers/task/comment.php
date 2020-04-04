<?php
namespace Bitrix\Tasks\Rest\Controllers\Task;

use Bitrix\Tasks\Rest\Controllers\Base;

use \Bitrix\Main\Error;

class Comment extends Base
{
	/**
	 * Return all DB and UF_ fields of comment
	 *
	 * @return array
	 */
	public function fieldsAction()
	{
		return  [];
	}


	/**
	 * Add comment to task
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
	 * Update task comment
	 *
	 * @param int $taskId
	 * @param int $commentId
	 * @param array $fields
	 *
	 * @param array $params
	 *
	 * @return bool
	 */
	public function updateAction($taskId, $commentId, array $fields, array $params = array())
	{
		return false;
	}

	/**
	 * Remove existing comment
	 *
	 * @param int $taskId
	 * @param int $commentId
	 *
	 * @param array $params
	 *
	 * @return bool
	 */
	public function deleteAction($taskId, $commentId, array $params = array())
	{
		return false;
	}

	/**
	 * Get list all task
	 *
	 * @param array $params ORM get list params
	 *
	 * @return array
	 */
	public function listAction(array $params = array())
	{
		return [];
	}
}