<?php
namespace Bitrix\Tasks\Rest\Controllers\Task;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Db\SqlQueryException;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Comments\Viewed\Group;
use Bitrix\Tasks\Comments\Viewed\Task;
use Bitrix\Tasks\Rest\Controllers\Base;

/**
 * Class Comment
 *
 * @package Bitrix\Tasks\Rest\Controllers\Task
 */
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

	/**
	 * @deprecated Use more perf service
	 * @see \Bitrix\Tasks\Rest\Controllers\ViewedGroup\User
	 *
	 * @param null $groupId
	 * @param null $userId
	 * @return bool
	 * @throws ArgumentException
	 * @throws ArgumentTypeException
	 * @throws LoaderException
	 * @throws ObjectPropertyException
	 * @throws SqlQueryException
	 * @throws SystemException
	 */
	public function readAllAction($groupId = null, $userId = null, string $role = null): bool
	{
		return (new Task())->readAll($groupId, $userId, $role);
	}

	/**
	 * @deprecated Use more perf service
	 * @see \Bitrix\Tasks\Rest\Controllers\ViewedGroup\Project
	 *
	 * @param null $groupId
	 * @return bool
	 * @throws ArgumentException
	 * @throws ArgumentTypeException
	 * @throws LoaderException
	 * @throws ObjectPropertyException
	 * @throws SqlQueryException
	 * @throws SystemException
	 */
	public function readProjectAction($groupId = null): bool
	{
		return (new Task())->readProject($groupId);
	}

	/**
	 * @deprecated Use more perf service
	 * @see \Bitrix\Tasks\Rest\Controllers\ViewedGroup\Scrum
	 *
	 * @param null $groupId
	 * @return bool
	 * @throws ArgumentTypeException
	 * @throws SqlQueryException
	 */
	public function readScrumAction($groupId = null): bool
	{
		return (new Task())->readScrum($groupId);
	}
}