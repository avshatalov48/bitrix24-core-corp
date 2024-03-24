<?php
namespace Bitrix\Tasks\Rest\Controllers\Task;

use Bitrix\Main\Error;
use Bitrix\Tasks\Rest\Controllers\Base;
use CTaskItem;
use Exception;

class Favorite extends Base
{
	/**
	 * Add task to favorite
	 *
	 * @restMethod tasks.task.favorite.add
	 */
	public function addAction(CTaskItem $task, array $params = array()): ?bool
	{
		try
		{
			$task->addToFavorite($params);
		}
		catch (Exception $exception)
		{
			$this->addError(Error::createFromThrowable($exception));
			return null;
		}

		return true;
	}

	/**
	 * Remove existing task
	 *
	 * @restMethod tasks.task.favorite.remove
	 */
	public function removeAction(CTaskItem $task, array $params = array()): ?bool
	{
		try
		{
			$task->deleteFromFavorite($params);
		}
		catch (Exception $exception)
		{
			$this->addError(Error::createFromThrowable($exception));
			return null;
		}

		return true;
	}
}
