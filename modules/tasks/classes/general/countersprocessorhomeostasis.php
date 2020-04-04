<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2013 Bitrix
 */

use Bitrix\Tasks\Util\User;

/**
 * This is not a part of public API.
 * For internal use only.
 * 
 * @access private
 * 
 * This is helper class for automatically counters correction
 */
class CTaskCountersProcessorHomeostasis
{
	const MARKER_ID = 'MARKER_CTaskCountersProcessorHomeostasis';

	const OPTION_COUNTERS_BREAK_RECHECK_FOR_SUBTASKS = 'counters_break_recheck_for_subtasks';

	public static function injectMarker($arFilter, $curSection, $counterId, $userId)
	{
		static $arMonitoredCounters = array(
			CTaskCountersProcessor::COUNTER_TASKS_MY_NEW,
			CTaskCountersProcessor::COUNTER_TASKS_ACCOMPLICE_NEW,

			CTaskCountersProcessor::COUNTER_TASKS_MY_WO_DEADLINE,
			CTaskCountersProcessor::COUNTER_TASKS_ORIGINATOR_WO_DEADLINE,
			CTaskCountersProcessor::COUNTER_TASKS_ORIGINATOR_WAIT_CTRL,
			
			// monitor all expired
			CTaskCountersProcessor::COUNTER_TASKS_MY_EXPIRED,
			CTaskCountersProcessor::COUNTER_TASKS_ACCOMPLICE_EXPIRED,
			CTaskCountersProcessor::COUNTER_TASKS_AUDITOR_EXPIRED,
			CTaskCountersProcessor::COUNTER_TASKS_ORIGINATOR_EXPIRED,

			// monitor all almost expired
			CTaskCountersProcessor::COUNTER_TASKS_MY_EXPIRED_CANDIDATES,
			CTaskCountersProcessor::COUNTER_TASKS_ACCOMPLICE_EXPIRED_CANDIDATES
		);

		if (
			($curSection === CTaskListState::VIEW_SECTION_ROLES)
			&& ($counterId !== null)
			&& ($userId > 0)
			&& in_array($counterId, $arMonitoredCounters, true)
		)
		{
			// We monitor only some counters (except expiration counters)
			$filterCheksum = self::calcFilterChecksum($arFilter);

			if ( ! isset($arFilter['::MARKERS']) )
				$arFilter['::MARKERS'] = array();

			$arFilter['::MARKERS'][self::MARKER_ID] = array(
				'filterCheksum' => $filterCheksum,
				'counterId'     => $counterId,
				'userId'        => $userId
			);
		}

		return ($arFilter);
	}


	private static function calcFilterChecksum($arFilter)
	{
		if (isset($arFilter['::MARKERS']))
			unset($arFilter['::MARKERS']);

		$str = serialize($arFilter);
		return (strlen($str) . '-' . md5($str));
	}


	/**
	 * Checks for broken counters.
	 * Expirity counter is broken if it is < 0, or if it is more than tasks count in list of expired tasks
	 * Other counters is broken if it is < 0, or if it is != tasks count in list of respective tasks
	 *
	 * Method is called inside CTask::GetList() to perform recounting of broken counters.
	 * 
	 * @param mixed[] Filter was used in GetList() call
	 * @param $tasksCountInList Number of records returned by GetList() call
	 * @deprecated
	 */
	public static function onTaskGetList($arFilter, $tasksCountInList)
	{


		//_dump_r('getlist');

		if ( ! CTaskCountersProcessorInstaller::isInstallComplete() )
		{
			//_dump_r('Not complete');
			return;
		}

		// Is there our marker?
		if ( ! (
			array_key_exists('::MARKERS', $arFilter)
			&& array_key_exists(self::MARKER_ID, $arFilter['::MARKERS'])
			&& ($tasksCountInList !== null)
		))
		{
			return;
		}

		$tasksCountInList = (int) $tasksCountInList;
		$counterOwnerUserId = $arFilter['::MARKERS'][self::MARKER_ID]['userId'];
		$counterId = $arFilter['::MARKERS'][self::MARKER_ID]['counterId'];
		$counterValue = (int) CUserCounter::GetValue($counterOwnerUserId, $counterId, $site_id = '**');

		if (in_array(
			$counterId,
			array(
				CTaskCountersProcessor::COUNTER_TASKS_MY_EXPIRED,
				CTaskCountersProcessor::COUNTER_TASKS_ACCOMPLICE_EXPIRED,
				CTaskCountersProcessor::COUNTER_TASKS_AUDITOR_EXPIRED,
				CTaskCountersProcessor::COUNTER_TASKS_ORIGINATOR_EXPIRED,

				CTaskCountersProcessor::COUNTER_TASKS_MY_EXPIRED_CANDIDATES,
				CTaskCountersProcessor::COUNTER_TASKS_ACCOMPLICE_EXPIRED_CANDIDATES
			),
			true
		))
		{
			$isExpirityCounter = true;
		}
		else
			$isExpirityCounter = false;

		$needRecalc = false;
		$realTasksCount         = null;

		// Is checksum correct?
		$filterCheksum = $arFilter['::MARKERS'][self::MARKER_ID]['filterCheksum'];
		$realCheksum   = self::calcFilterChecksum($arFilter);

		// break detection part
		if ($filterCheksum === $realCheksum) // this is exactly the same filter for that counter
		{
			$realTasksCount = $tasksCountInList;

			// if counter is not equal to task count, we certainly must recalc now
			if(($counterValue < 0) || ($tasksCountInList != $counterValue))
			{
				$needRecalc = true;
			}
		}
		else if (
			// this can be the same filter, but with only parent (root) tasks shown
			isset($arFilter['SAME_GROUP_PARENT'], $arFilter['ONLY_ROOT_TASKS'])
			&& ($arFilter['SAME_GROUP_PARENT'] === 'Y')
			&& ($arFilter['ONLY_ROOT_TASKS'] === 'Y')
		)
		{
			// unset the corresponding fields and try to compare checksums again
			unset($arFilter['SAME_GROUP_PARENT']);
			unset($arFilter['ONLY_ROOT_TASKS']);

			$realCheksum = self::calcFilterChecksum($arFilter);

			if ($filterCheksum === $realCheksum) // okay, we were right about filter
			{
				// tasks count in list shouldn't be more than registered in counter
				// and counter shouldn't be less than zero
				if (($counterValue < 0) || ($tasksCountInList > $counterValue))
				{
					$needRecalc = true;
				}
				else // or else we dont exactly know if counter is broken, so have to recalculate, but not every time
				{
					$needRecalc = static::getCountersRecheckForSubTasksNeed();
				}
			}
		}

		if ($needRecalc)
		{
			if($realTasksCount === null)
			{
				$rsTasksCount = CTasks::getCount(
					$arFilter,
					array(
						'bIgnoreDbErrors'  => true,
						'bSkipUserFields'  => true,
						'bSkipExtraTables' => true,
					)
				);

				if (
					$rsTasksCount
					&& ($arTasksCount = $rsTasksCount->fetch())
				)
				{
					$realTasksCount = intval($arTasksCount['CNT']);
				}
			}

			if($realTasksCount !== null && $realTasksCount != $counterValue) // sure, we were right about filter mistiming
			{
				if ($isExpirityCounter) // a special way for correction of 'deadline expired' counters
				{
					// pend counters reinstalling (agent is used)
					self::pendCountersRecalculation();
				}
				else	// for all other counters we can fix them by counter correction JUST RIGHT NOW
				{
					$delta = $realTasksCount - $counterValue;

					CTaskCountersQueue::push(
						$counterId,
						CTaskCountersQueue::OP_INCREMENT,
						array($counterOwnerUserId),
						$delta
					);
					CTaskCountersQueue::execute();
				}
			}
			// else sadly skip it, what else to do?
		}
	}


	public static function pendCountersRecalculation()
	{
		$optionCountersBrokeDetected = (int) COption::GetOptionString('tasks', '~counters_broke_detected', -1, $siteId = '');

		// check if the agent event exists
		CTaskCountersProcessor::ensureAgentExists();

		if ($optionCountersBrokeDetected !== 1)
			COption::SetOptionString('tasks', '~counters_broke_detected', 1, $description = '', $siteId = '');
	}


	public static function onExpirityRecountAgent()
	{
		$optionCountersBrokeDetected = (int) COption::GetOptionString('tasks', '~counters_broke_detected', -1, $siteId = '');
		$lastRecalculationTimestamp  = (int) COption::GetOptionString('tasks', '~counters_last_recalculation', -1, $siteId = '');

		if ($optionCountersBrokeDetected === 1)
			$isCountersBrokeDetected = true;
		else
			$isCountersBrokeDetected = false;

		$secondsSinceLastRecalculation = time() - $lastRecalculationTimestamp;

		//_dump_r('===============');
		//_dump_r('BD: '.$isCountersBrokeDetected);
		//_dump_r($secondsSinceLastRecalculation);

		// if broke was detected and recalculator is not working now, and also last time was more than 15 minutes ago...
		if ($isCountersBrokeDetected && ($secondsSinceLastRecalculation > 900) && \CTaskCountersProcessorInstaller::checkProcessIsNotActive())
		{
			CTaskCountersProcessorInstaller::runSetup();
		}
	}


	public static function onCalculationComplete()
	{
		$strTimestamp = (string) time();

		COption::SetOptionString('tasks', '~counters_broke_detected', '0', $description = '', $siteId = '');
		COption::SetOptionString('tasks', '~counters_last_recalculation', $strTimestamp, $description = '', $siteId = '');
	}

	private static function getCountersRecheckForSubTasksNeed()
	{
		$userId = User::getId();
		$optName = '~'.static::OPTION_COUNTERS_BREAK_RECHECK_FOR_SUBTASKS;

		if($userId)
		{
			$recheckTime = User::getOption($optName, $userId, 0);
		}
		else
		{
			$recheckTime = (int) COption::GetOptionString('tasks', $optName, -1, $siteId = '');
		}

		$needReCheck = false;

		if(!$recheckTime)
		{
			$recheckTime = 1;
		}

		if($recheckTime >= 5) // re-check each 5 hits
		{
			$needReCheck = true;
			$recheckTime = 1;

			//_dump_r('Go recount!');
		}
		else
		{
			$recheckTime++;

			//_dump_r('Skip: '.$recheckTime);
		}

		if($userId)
		{
			User::setOption($optName, $recheckTime, $userId);
		}
		else
		{
			COption::SetOptionString('tasks', $optName, $recheckTime, -1, $siteId = '');
		}

		return $needReCheck;
	}
}
