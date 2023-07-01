<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2013 Bitrix
 */

use Bitrix\Main\Type\Date;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Integration\Pull\PushService;
use Bitrix\Tasks\Util\User;

/**
 * @access public
 */
final class CTaskTimerManager
{
	private static $instances = array();
	private $userId = null;
	private $cachedLastTimer = null;


	public static function onAfterTMEntryUpdate(/** @noinspection PhpUnusedParameterInspection */ $id, $arFields)
	{
		$needStopTimer = false;

		if(!array_key_exists('USER_ID', $arFields))
		{
			return;
		}

		$arFields['USER_ID'] = intval($arFields['USER_ID']);
		if($arFields['USER_ID'] <= 0)
		{
			return;
		}

		if (($arFields['PAUSED'] ?? null) === 'Y')
		{
			$needStopTimer = true;
		}
		elseif (isset($arFields['DATE_FINISH'], $arFields['TIME_FINISH']))
		{
			if (($arFields['DATE_FINISH'] !== false) && ($arFields['TIME_FINISH'] !== false))
				$needStopTimer = true;
		}

		if ($needStopTimer)
		{
			$oTimer = CTaskTimerManager::getInstance($arFields['USER_ID']);
			$oTimer->stop();
		}
	}


	public static function onBeforeTaskUpdate(/** @noinspection PhpUnusedParameterInspection */$id, $arFields, $arTask)
	{
		$userId = User::getId();

		if ($userId)
		{
			$oTimer  = self::getInstance($userId);
			$arTimer = $oTimer->getLastTimer();

			// If task on timer & completed by logged in user, stop timer
			if (
				$arTimer
				&& ($arTimer['TASK_ID'] == $arTask['ID'])
				&& ($arTask['REAL_STATUS'] != CTasks::STATE_SUPPOSEDLY_COMPLETED)
				&& ($arTask['REAL_STATUS'] != CTasks::STATE_COMPLETED)
				&& ($arTask['REAL_STATUS'] != CTasks::STATE_DEFERRED)
				&& (
					($arFields['STATUS'] == CTasks::STATE_SUPPOSEDLY_COMPLETED)
					|| ($arFields['STATUS'] == CTasks::STATE_COMPLETED)
					|| ($arFields['STATUS'] == CTasks::STATE_DEFERRED)
				)
				&& (
					($arTask['RESPONSIBLE_ID'] == $userId)
					|| in_array($userId, (array) $arTask['ACCOMPLICES'])
				)
			)
			{
				$oTimer->stop();
			}
		}

		// If users are not responsible or accomplices in task elsemore,
		// stop they timers
		$arPrevParticipants = array_unique(array_merge(
			array($arTask['RESPONSIBLE_ID']),
			$arTask['ACCOMPLICES']
		));

		$arNewParticipants = array();

		if (isset($arFields['RESPONSIBLE_ID']))
			$arNewParticipants[] = $arFields['RESPONSIBLE_ID'];
		else
			$arNewParticipants[] = $arTask['RESPONSIBLE_ID'];

		if (isset($arFields['ACCOMPLICES']))
			$arNewParticipants = array_merge($arNewParticipants, $arFields['ACCOMPLICES']);
		else
			$arNewParticipants = array_merge($arNewParticipants, $arTask['ACCOMPLICES']);

		$arNewParticipants = array_unique($arNewParticipants);

		$arEliminatedUsers = array_diff($arPrevParticipants, $arNewParticipants);

		static::stopTimerForUsers($arTask['ID'], $arEliminatedUsers);
	}


	public static function onBeforeTaskDelete($id, $arTask)
	{
		$arParticipants = array_unique(array_merge(
			array($arTask['CREATED_BY'], $arTask['RESPONSIBLE_ID']),
			$arTask['ACCOMPLICES'],
			$arTask['AUDITORS']
		));

		self::stopTimerForUsers($id, $arParticipants);
	}

	private static function stopTimerForUsers($id, $users)
	{
		if(is_array($users) && !empty($users))
		{
			foreach ($users as $userId)
			{
				$userId = intval($userId);
				if($userId > 0)
				{
					$oTimer  = self::getInstance($userId);
					$arTimer = $oTimer->getLastTimer();
					if ($arTimer && ($arTimer['TASK_ID'] == $id))
					{
						$oTimer->stop();
					}
				}
			}
		}
	}

	public function getLastTimer($bResetStaticCache = true)
	{
		if (($bResetStaticCache) || ($this->cachedLastTimer === null))
		{
			$arTimer = CTaskTimerCore::get($this->userId);
			if (($arTimer !== false) && ($arTimer['TIMER_STARTED_AT'] > 0))
				$arTimer['RUN_TIME'] = time() - $arTimer['TIMER_STARTED_AT'];

			$this->cachedLastTimer = $arTimer;
		}

		return ($this->cachedLastTimer);
	}


	/**
	 * Get ID of currently task in timer.
	 *
	 * @param $bResetStaticCache
	 * @return bool|int	false if there is no running task, elsewhere taskid
	 */
	public function getRunningTask($bResetStaticCache = true)
	{
		$arTimer = $this->getLastTimer($bResetStaticCache);

		if (
			($arTimer !== false)
			&& ($arTimer['TIMER_STARTED_AT'] > 0)
		)
		{
			// refresh run-time
			$arTimer['RUN_TIME'] = time() - $arTimer['TIMER_STARTED_AT'];
			return ($arTimer);
		}
		else
			return (false);
	}

	public function start($taskId)
	{
		global $CACHE_MANAGER;

		// Stop timer of user (if it is run)
		$this->stop();

		$task = CTaskItem::getInstance($taskId, $this->userId);

		try
		{
			$taskData = $task->getData(false);
		}
		catch (TasksException $e)
		{
			return false;
		}

		if (!$task->checkAccess(ActionDictionary::ACTION_TASK_TIME_TRACKING))
		{
			return false;
		}

		// Run timer for given task
		$timer = CTaskTimerCore::start($this->userId, $taskId);

		$this->cachedLastTimer = null;

		$affectedUsers = array_unique(
			array_merge(
				[$this->userId, $taskData['RESPONSIBLE_ID']],
				(array)$taskData['ACCOMPLICES']
			)
		);
		foreach ($affectedUsers as $userId)
		{
			$CACHE_MANAGER->ClearByTag("tasks_user_{$userId}");
		}

		if ($timer === false)
		{
			return false;
		}

		// Add task to day plan
		$currentUserId = User::getId();
		if ($currentUserId && ($currentUserId === $this->userId))
		{
			$dayPlanTasks = CTaskPlannerMaintance::getCurrentTasksList();
			if (!in_array($taskId, $dayPlanTasks))
			{
				CTaskPlannerMaintance::plannerActions(['add' => [$taskId]]);
			}
		}

		if ((int)$taskData['REAL_STATUS'] !== CTasks::STATE_IN_PROGRESS)
		{
			if ($task->checkAccess(ActionDictionary::ACTION_TASK_START))
			{
				$task->startExecution();
			}
			elseif ($task->checkAccess(ActionDictionary::ACTION_TASK_RENEW))
			{
				$task->renew();
			}
		}

		PushService::addEvent(
			$this->userId,
			[
				'module_id' => 'tasks',
				'command' => 'task_timer_start',
				'params' => [
					'taskId' => $taskId,
					'timeElapsed' => (int)$taskData['TIME_SPENT_IN_LOGS'] + (time() - $timer['TIMER_STARTED_AT']),
				],
			]
		);

		return $timer;
	}


	public function stop($taskId = 0)
	{
		global $CACHE_MANAGER;

		if ($taskId)
		{
			$task = CTaskItem::getInstance($taskId, $this->userId);
			if (!$task->checkAccess(ActionDictionary::ACTION_TASK_TIME_TRACKING))
			{
				return false;
			}
		}

		$timer = CTaskTimerCore::stop($this->userId, $taskId);

		$dateFormat = Date::convertFormatToPhp(\CSite::GetDateFormat());
		$userOffset = User::getTimeZoneOffset($this->userId);

		$dateStart = date($dateFormat, ($timer['TIMER_STARTED_AT'] ?? null) + $userOffset);
		$dateStop = date($dateFormat, ($timer['TIMER_STARTED_AT'] ?? null) + ($timer['TIMER_ACCUMULATOR'] ?? null) + $userOffset);

		$this->cachedLastTimer = null;

		if ($timer !== false && $timer['TIMER_ACCUMULATOR'] > 0)
		{
			/** @noinspection PhpDeprecationInspection */
			$elapsedTime = new CTaskElapsedTime();
			$elapsedTime->add(
				[
					'USER_ID' => $this->userId,
					'TASK_ID' => $timer['TASK_ID'],
					'SECONDS' => $timer['TIMER_ACCUMULATOR'],
					'COMMENT_TEXT' => '',
					'CREATED_DATE' => $dateStart,
					'DATE_START' => $dateStart,
					'DATE_STOP' => $dateStop,
				],
				[
					'SOURCE_SYSTEM' => 'Y',
				]
			);

			$task = CTaskItem::getInstance($timer['TASK_ID'], $this->userId);

			try
			{
				$taskData = $task->getData(false);
			}
			catch (TasksException $e)
			{
				return false;
			}

			$timeElapsed = [
				$this->userId => $taskData['TIME_SPENT_IN_LOGS'],
			];
			$affectedUsers = array_unique(
				array_merge(
					[$this->userId, $taskData['RESPONSIBLE_ID']],
					(array)$taskData['ACCOMPLICES']
				)
			);
			foreach ($affectedUsers as $userId)
			{
				$CACHE_MANAGER->ClearByTag("tasks_user_{$userId}");

				if ((int)$userId !== $this->userId)
				{
					$timeElapsed[$userId] = $taskData['TIME_SPENT_IN_LOGS'];

					$affectedUserTimer = CTaskTimerCore::get($userId, $timer['TASK_ID']);
					if ($affectedUserTimer !== false && $affectedUserTimer['TIMER_STARTED_AT'] > 0)
					{
						$timeElapsed[$userId] += time() - $affectedUserTimer['TIMER_STARTED_AT'];
					}
				}
			}

			PushService::addEvent(
				$affectedUsers,
				[
					'module_id' => 'tasks',
					'command' => 'task_timer_stop',
					'params' => [
						'taskId' => (int)$timer['TASK_ID'],
						'userId' => $this->userId,
						'timeElapsed' => $timeElapsed,
					],
				]
			);
		}

		return $timer;
	}


	/**
	 * @param $userId
	 * @return CTaskTimerManager
	 */
	public static function getInstance($userId)
	{
		// Cache instance in pool
		if ( ! isset(self::$instances[$userId]) )
			self::$instances[$userId] = new self($userId);

		return (self::$instances[$userId]);
	}


	private function __construct($userId)
	{
		CTaskAssert::assertLaxIntegers($userId);
		CTaskAssert::assert($userId > 0);

		$this->userId = (int) $userId;
	}


	// prevent __wakeup of object
	public function __wakeup()
	{
	}

	// prevent __sleep of object
	public function __sleep()
	{
	}

	// prevent clone of object
	private function __clone()
	{
	}
}
