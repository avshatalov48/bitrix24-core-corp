<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2015 Bitrix
 * 
 * @access private
 * 
 * Each method you put here you`ll be able to call as ENTITY_NAME.METHOD_NAME, so be careful.
 */

namespace Bitrix\Tasks\Dispatcher\PublicCallable;

final class Task extends \Bitrix\Tasks\Dispatcher\PublicCallable
{
	/**
	 * Get a task
	 */
	public function get($id)
	{
		global $USER;

		$result = array();

		if($id = $this->checkTaskId($id))
		{
			$task = new \CTaskItem($id, $USER->GetId());

			$id = $task->getId();
			$data = $task->getData(false);

			$result['DATA']['TASK'][$id] = $data;
			$result['CAN']['TASK'][$id]['ACTION'] = static::translateAllowedActionNames($task->getAllowedActions());
		}

		return $result;
	}

	/**
	 * Get a list of tasks
	 */
	public function getList(array $order = array(), array $filter = array(), array $select = array(), array $parameters = array())
	{
		global $USER;

		$result = array();

		// ID is required
		$select[] = 'ID';

		// restrict navigation
		if(is_numeric($parameters['NAV_PARAMS']['nPageTop']))
		{
			$parameters['NAV_PARAMS']['nPageTop'] = min($parameters['NAV_PARAMS']['nPageTop'], \CTaskRestService::TASKS_LIMIT_TOP_COUNT);
		}
		else
		{
			if(is_numeric($parameters['NAV_PARAMS']['nPageSize']))
			{
				$parameters['NAV_PARAMS']['nPageSize'] = min($parameters['NAV_PARAMS']['nPageSize'], \CTaskRestService::TASKS_LIMIT_PAGE_SIZE);
			}
			else
			{
				$parameters['NAV_PARAMS']['nPageSize'] = \CTaskRestService::TASKS_LIMIT_PAGE_SIZE;
			}
		}

		$items = \CTaskItem::fetchList($USER->GetId(), $order, $filter, $parameters, $select);
		if(is_array($items))
		{
			foreach($items as $item)
			{
				$data = $item->getData(false);
				$id = $item->getId();

				$result['DATA']['TASK'][$id] = $data;
				$result['CAN']['TASK'][$id]['ACTION'] = static::translateAllowedActionNames($item->getAllowedActions());
			}
		}

		return $result;
	}

	/**
	 * Add a new task
	 */
	public function add(array $data, array $parameters = array())
	{
		global $USER;

		$result = array();

		// todo: teach CTaskItem::add() accept CHECLIST, REMINDER, ...
		$task = \CTaskItem::add($data, $USER->GetId());

		if($parameters['RETURN_OPERATION_RESULT_DATA'])
		{
			$result['DATA']['TASK'] = $task->getData(false); // todo: some additional info here, like CHECKLIST, REMINDER, ...?
			$result['CAN']['TASK']['ACTION'] = static::translateAllowedActionNames($task->getAllowedActions());
		}
		else
		{
			$result['DATA']['TASK']['ID'] = $task->getId();
		}

		return $result;
	}

	/**
	 * Update a task with some new data
	 */
	public function update($id, array $data, array $parameters = array())
	{
		global $USER;

		$result = array();

		if($id = $this->checkTaskId($id))
		{
			if(!empty($data)) // simply nothing to do, not a error
			{
				$cacheAFWasDisabled = \CTasks::disableCacheAutoClear();
				$notifADWasDisabled = \CTaskNotifications::disableAutoDeliver();

				$task = new \CTaskItem($id, $USER->GetId());
				$task->update($data);

				if($notifADWasDisabled)
				{
					\CTaskNotifications::enableAutoDeliver();
				}
				if($cacheAFWasDisabled)
				{
					\CTasks::enableCacheAutoClear();
				}

				if($parameters['RETURN_OPERATION_RESULT_DATA'])
				{
					$result['DATA']['OPERATION_RESULT'] = $task->getLastOperationResultData('UPDATE');
				}
			}
		}

		return $result;
	}

	/**
	 * Delete a task
	 */
	public function delete($id, array $parameters = array())
	{
		global $USER;

		$result = array();

		if($id = $this->checkTaskId($id))
		{
			$task = new \CTaskItem($id, $USER->GetId());
			$task->delete();
		}

		return $result;
	}

	/**
	 * Get a list of actions that you can do with a specified task
	 */
	public function getAllowedActions($id)
	{
		global $USER;

		$result = array();

		if($id = $this->checkTaskId($id))
		{
			$task = new \CTaskItem($id, $USER->GetId());
			$result = static::translateAllowedActionNames($task->getAllowedActions());
		}

		return $result;
	}

	/**
	 * Start execution of a specified task
	 */
	public function start($id)
	{
		global $USER;

		$result = array();

		if($id = $this->checkTaskId($id))
		{
			$task = new \CTaskItem($id, $USER->GetId());
			$result = $task->getAllowedActions();
		}

		return $result;
	}

	/**
	 * Pause execution of a specified task
	 */
	public function pause($id)
	{
		global $USER;

		$result = array();

		if($id = $this->checkTaskId($id))
		{
			$task = new \CTaskItem($id, $USER->GetId());
			$task->pause();
		}

		return $result;
	}

	/**
	 * Complete a specified task
	 */
	public function complete($id)
	{
		global $USER;

		$result = array();

		if($id = $this->checkTaskId($id))
		{
			$task = new \CTaskItem($id, $USER->GetId());
			$task->complete();
		}

		return $result;
	}

	/**
	 * Accept a specified task
	 */
	public function accept($id)
	{
		global $USER;

		$result = array();

		if($id = $this->checkTaskId($id))
		{
			$task = new \CTaskItem($id, $USER->GetId());
			$task->accept();
		}

		return $result;
	}

	/**
	 * Decline a specified task
	 */
	public function decline($id)
	{
		global $USER;

		$result = array();

		if($id = $this->checkTaskId($id))
		{
			$task = new \CTaskItem($id, $USER->GetId());
			$task->decline();
		}

		return $result;
	}

	/**
	 * Renew (switch to status "pending, await execution") a specified task
	 */
	public function renew($id)
	{
		global $USER;

		$result = array();

		if($id = $this->checkTaskId($id))
		{
			$task = new \CTaskItem($id, $USER->GetId());
			$task->renew();
		}

		return $result;
	}

	/**
	 * Defer (put aside) a specified task
	 */
	public function defer($id)
	{
		global $USER;

		$result = array();

		if($id = $this->checkTaskId($id))
		{
			$task = new \CTaskItem($id, $USER->GetId());
			$task->defer();
		}

		return $result;
	}

	/**
	 * Approve (confirm the result of) a specified task
	 */
	public function approve($id)
	{
		global $USER;

		$result = array();

		if($id = $this->checkTaskId($id))
		{
			$task = new \CTaskItem($id, $USER->GetId());
			$task->approve();
		}

		return $result;
	}

	/**
	 * Disapprove (reject the result of) a specified task
	 */
	public function disapprove($id)
	{
		global $USER;

		$result = array();

		if($id = $this->checkTaskId($id))
		{
			$task = new \CTaskItem($id, $USER->GetId());
			$task->disapprove();
		}

		return $result;
	}

	/**
	 * Start an execution timer for a specified task
	 */
	public function startWatch($id)
	{
		global $USER;

		$result = array();

		if($id = $this->checkTaskId($id))
		{
			$task = new \CTaskItem($id, $USER->GetId());
			$task->startWatch();
		}

		return $result;
	}

	/**
	 * Stop an execution timer for a specified task
	 */
	public function stopWatch($id)
	{
		global $USER;

		$result = array();

		if($id = $this->checkTaskId($id))
		{
			$task = new \CTaskItem($id, $USER->GetId());
			$task->stopWatch();
		}

		return $result;
	}

	// todo: make this and TasksBaseComponent::translateAllowedActionNames() the same
	protected static function translateAllowedActionNames($can)
	{
		$newCan = array();
		if(is_array($can))
		{
			foreach($can as $act => $flag)
			{
				$newCan[str_replace('ACTION_', '', $act)] = $flag;
			}
		}

		return $newCan;
	}

}