<?php
namespace Bitrix\Tasks\Rest\Controllers\Task;

use Bitrix\Tasks\Rest\Controllers\Base;

use \Bitrix\Main\Error;

class Stage extends Base
{
	/**
	 * Return all fields of stage
	 *
	 * @return array
	 */
	public function fieldsAction()
	{
		return  [];
	}

	/**
	 * Create new stage
	 *
	 * @param array $fields
	 * [
	 * 	TITLE = string required
	 *
	 * 	COLOR = #RGB default rand
	 * 	AFTER_ID = int default 0
	 * 	GROUP_ID = int default 0
	 * ]
	 *
	 * @param array $params
	 * [
	 * 	check_permissions = bool
	 * ]
	 *
	 * @return int
	 */
	public function addAction(array $fields, array $params = array())
	{
		return 1;
	}

	/**
	 * Update existing stage
	 *
	 * @param string $id
	 * @param array $fields
	 * [
	 * 	TITLE = string required
	 *
	 * 	COLOR = #RGB default rand
	 * 	AFTER_ID = int default 0
	 * 	GROUP_ID = int default 0
	 * ]
	 *
	 * @param array $params
	 * [
	 * 	check_permissions = bool
	 * ]
	 *
	 * @return bool
	 */
	public function updateAction($id, array $fields, array $params = array())
	{
		return false;
	}

	/**
	 * Remove existing stage
	 *
	 * @param int $id
	 *
	 * @param array $params
	 * [
	 * 	check_permissions = bool
	 * ]
	 *
	 * @return bool
	 */
	public function deleteAction($id, array $params = array())
	{
		return false;
	}

	/**
	 * Get list all task stages
	 *
	 * @param int|null $groupId if null - return stages from my plan, or return group stages
	 * @param array $params
	 *
	 *
	 * @return array
	 */
	public function listAction($groupId = null, array $params = array())
	{
		return  [];
	}
}