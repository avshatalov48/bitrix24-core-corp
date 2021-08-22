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
use Bitrix\Tasks\Internals\DataBase\Tree\Exception;

final class Dependence extends \Bitrix\Tasks\Dispatcher\RestrictedAction
{
	/**
	 * Add a new dependence between two tasks
	 */
	public function add($taskIdFrom, $taskIdTo, $linkType)
	{
		if (
			!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_READ, (int)$taskIdFrom)
			|| !TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_READ, (int)$taskIdTo)
		)
		{
			$this->addForbiddenError();
			return [];
		}

		try
		{
			$task = new \CTaskItem($taskIdTo, $this->userId);
			$task->addDependOn($taskIdFrom, $linkType);
		}
		catch(Exception | \CTaskAssertException $e)
		{
			$this->errors->add('ILLEGAL_NEW_LINK', \Bitrix\Tasks\Dispatcher::proxyExceptionMessage($e));
		}

		return [];
	}

	/**
	 * Delete an existing dependence between two tasks
	 */
	public function delete($taskIdFrom, $taskIdTo)
	{
		if (
			!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_READ, (int)$taskIdFrom)
			|| !TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_READ, (int)$taskIdTo)
		)
		{
			$this->addForbiddenError();
			return [];
		}

		try
		{
			$task = new \CTaskItem($taskIdTo, $this->userId);
			$task->deleteDependOn($taskIdFrom);
		}
		catch(Exception | \CTaskAssertException $e)
		{
			$this->errors->add('ILLEGAL_LINK', \Bitrix\Tasks\Dispatcher::proxyExceptionMessage($e));
		}

		return [];
	}
}