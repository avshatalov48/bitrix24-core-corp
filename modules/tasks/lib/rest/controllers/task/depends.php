<?php
namespace Bitrix\Tasks\Rest\Controllers\Task;

use Bitrix\Tasks\Rest\Controllers\Base;

use \Bitrix\Main\Error;

class Depends extends Base
{

	/**
	 * @param int $taskId
	 *
	 * @param array $params
	 *
	 * @return array
	 */
	public function getAction($taskId, array $params = array())
	{
		return [];
	}
	/**
	 * @param int $taskIdFrom
	 * @param int $TaskIdTo
	 * @param int $linkType
	 *
	 * @see \Bitrix\Tasks\Internals\Task\ProjectDependenceTable::LINK_TYPE_START_START
	 *
	 * @param array $params
	 *
	 * @return bool
	 */
	public function addAction($taskIdFrom, $TaskIdTo, $linkType, array $params = array())
	{
		return false;
	}
	/**
	 * @param int $taskIdFrom
	 * @param int $TaskIdTo
	 * @param int $linkType
	 *
	 * @see \Bitrix\Tasks\Internals\Task\ProjectDependenceTable::LINK_TYPE_START_START
	 *
	 * @param array $params
	 *
	 * @return bool
	 */
	public function deleteAction($taskIdFrom, $TaskIdTo, $linkType, array $params = array())
	{
		return false;
	}
}