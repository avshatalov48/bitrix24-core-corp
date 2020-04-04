<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 *
 * @access private
 *
 * Each method you put here you`ll be able to call as ENTITY_NAME.METHOD_NAME via AJAX and\or REST, so be careful.
 */

namespace Bitrix\Tasks\Dispatcher\PublicAction\Integration;

final class TimeManager extends \Bitrix\Tasks\Dispatcher\PublicAction
{
	/**
	 * Start an execution timer for a specified task
	 *
	 * @param $taskId
	 * @param bool $stopPrevious
	 * @return array
	 */
	public function start($taskId, $stopPrevious = false)
	{
		$result = array();

		if($taskId = $this->checkTaskId($taskId))
		{
			global $USER;

			$timer = \CTaskTimerManager::getInstance($USER->getId());
			$lastTimer = $timer->getLastTimer();
			if(!$stopPrevious && $lastTimer['TASK_ID'] && $lastTimer['TIMER_STARTED_AT'] > 0 && intval($lastTimer['TASK_ID']) && $lastTimer['TASK_ID'] != $taskId)
			{
				$additional = array();

				// use direct query here, avoiding cached CTaskItem::getData(), because $lastTimer['TASK_ID'] unlikely will be in cache
				list($tasks, $res) = \CTaskItem::fetchList($USER->getId(), array(), array('ID' => intval($lastTimer['TASK_ID'])), array(), array('ID', 'TITLE'));
				if(is_array($tasks))
				{
					$task = array_shift($tasks);
					if($task)
					{
						$data = $task->getData(false);
						if(intval($data['ID']))
						{
							$additional['TASK'] = array(
								'ID' => $data['ID'],
								'TITLE' => $data['TITLE']
							);
						}
					}
				}

				$this->errors->add('OTHER_TASK_ON_TIMER', 'Some other task is on timer', false, $additional);
			}
			else
			{
				if($timer->start($taskId) === false)
				{
					$this->errors->add('TIMER_ACTION_FAILED.START', 'Timer action failed');
				}
			}
		}

		return $result;
	}

	/**
	 * Stop an execution timer for a specified task
	 *
	 * @param $taskId
	 * @return array
	 */
	public function stop($taskId)
	{
		$result = array();

		if($taskId = $this->checkTaskId($taskId))
		{
			global $USER;

			$timer = \CTaskTimerManager::getInstance($USER->GetId());
			if($timer->stop($taskId) === false)
			{
				$this->errors->add('TIMER_ACTION_FAILED.STOP', 'Timer action failed');
			}
		}

		return $result;
	}
}