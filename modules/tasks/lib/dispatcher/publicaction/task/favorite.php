<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2015 Bitrix
 *
 * @access private
 *
 * Each method you put here you`ll be able to call as ENTITY_NAME.METHOD_NAME via AJAX and\or REST, so be careful.
 */

namespace Bitrix\Tasks\Dispatcher\PublicAction\Task;

use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Access\TaskAccessController;
use Bitrix\Tasks\Util\User;

final class Favorite extends \Bitrix\Tasks\Dispatcher\RestrictedAction
{
	/**
	 * Add a task to users own favorite list
	 */
	public function add($taskId)
	{
		$result = [];

		if (!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_READ, (int)$taskId))
		{
			$this->addForbiddenError();
			return $result;
		}

		try
		{
			if($taskId = $this->checkTaskId($taskId))
			{
				// user can add a task ONLY to his OWN favorite-list
				$task = new \CTaskItem($taskId, User::getId());
				$task->addToFavorite();
			}
		}
		catch (\CTaskAssertException $e)
		{
			return $result;
		}

		return $result;
	}

	/**
	 * Remove a task from users own favorite list
	 */
	public function delete($taskId)
	{
		$result = [];

		if (!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_READ, (int)$taskId))
		{
			$this->addForbiddenError();
			return $result;
		}

		try
		{
			if($taskId = $this->checkTaskId($taskId))
			{
				// user can add a task ONLY to his OWN favorite-list
				$task = new \CTaskItem($taskId, User::getId());
				$task->deleteFromFavorite();
			}
		}
		catch (\CTaskAssertException $e)
		{
			return $result;
		}

		return $result;
	}
}