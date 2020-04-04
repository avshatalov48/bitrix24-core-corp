<?php
namespace Bitrix\Tasks\Rest\Controllers\Task;

use Bitrix\Tasks\Rest\Controllers\Base;

class Favorite extends Base
{
	/**
	 * Add task to favorite
	 *
	 * @param \CTaskItem $task
	 * @param array $params
	 *
	 * @return bool
	 */
	public function addAction(\CTaskItem $task, array $params = array())
	{
		$task->addToFavorite($params);

		return true;
	}

	/**
	 * Remove existing task
	 *
	 * @param \CTaskItem $task
	 * @param array $params
	 *
	 * @return bool
	 */
	public function removeAction(\CTaskItem $task, array $params = array())
	{
		$task->deleteFromFavorite($params);

		return true;
	}
}