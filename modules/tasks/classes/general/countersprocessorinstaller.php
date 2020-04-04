<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2013 Bitrix
 */


/**
 * This is not a part of public API.
 * For internal use only.
 * 
 * Installer = Recounter
 * 
 * @access private
 */
class CTaskCountersProcessorInstaller
{
	const STEP_BEGIN                           = 'remove CTaskCountersProcessor::agent()';
	const STEP_DROP_COUNTERS                   = 'drop counters';
	const STEP_COUNT_NEW_FOR_RESPONSIBLES      = 'count new tasks for responsibles';
	const STEP_COUNT_NEW_FOR_ACCOMPLICES       = 'count new tasks for accomplices';
	//const STEP_COUNT_NEW_FOR_AUDITORS          = 'count new tasks for auditors';
	const STEP_COUNT_WAIT_CTRL_FOR_ORIGINATORS = 'count new tasks for auditors';
	const STEP_COUNT_EXPIRED                   = 'count expired and expired soon tasks';
	const STEP_COUNT_WITHOUT_DEADLINES_MY      = 'count tasks without DEADLINE for responsibles';
	const STEP_COUNT_WITHOUT_DEADLINES_FOR_ORIGINATORS = 'count tasks without DEADLINE for originators';

	// stages of CTaskCountersProcessorInstaller life cycle
	const STAGE_INSTALL_IN_PROGRESS = 1;
	const STAGE_INSTALL_COMPLETE    = 2;


	public static function runSetup()
	{
		\CAgent::AddAgent(
			'CTaskCountersProcessorInstaller::setup();',
			'tasks',
			'N',
			15,
			'',
			'Y',
			GetTime(time() + 60, "FULL"),
			100,
			false,
			false
		);
	}


	/**
	 * This function resets all counters for all users and recounts them.
	 * 
	 * This function do work which IS NOT multi-thread safe.
	 */
	public static function setup($step = self::STEP_BEGIN,
		/** @noinspection PhpUnusedParameterInspection */ $extraParam = null)
	{
		$nextStep = $nextStepDelay = $nextStepExtraParam = null;

		$timeLimit = microtime(true) + 5;	// give at least 5 seconds for work
		while (microtime(true) <= $timeLimit)
		{
			/** @noinspection PhpUnusedLocalVariableInspection */
			$nextStep = $nextStepDelay = $nextStepExtraParam = null;

			if ($step === self::STEP_BEGIN)
			{
				self::setStage(self::STAGE_INSTALL_IN_PROGRESS);

				$nextStep = self::STEP_DROP_COUNTERS;
				$nextStepDelay = 130;
			}
			else switch($step)
			{
				case self::STEP_DROP_COUNTERS:
					// reset DEADLINE_COUNTED flags and all tasks counters for all users
					self::reset();
					$nextStep = self::STEP_COUNT_NEW_FOR_RESPONSIBLES;
				break;

				case self::STEP_COUNT_NEW_FOR_RESPONSIBLES:
					self::recountCounters_MY_NEW($userId = '*');	// recount for all users
					$nextStep = self::STEP_COUNT_NEW_FOR_ACCOMPLICES;
				break;

				case self::STEP_COUNT_NEW_FOR_ACCOMPLICES:
					self::recountCounters_ACCOMPLICE_NEW($userId = '*');	// recount for all users
					//$nextStep = self::STEP_COUNT_NEW_FOR_AUDITORS;
					$nextStep = self::STEP_COUNT_WAIT_CTRL_FOR_ORIGINATORS;
				break;

				case self::STEP_COUNT_WAIT_CTRL_FOR_ORIGINATORS:
					self::recountCounters_ORIGINATORS_WAIT_CTRL($userId = '*');	// recount for all users
					$nextStep = self::STEP_COUNT_WITHOUT_DEADLINES_MY;
				break;

				case self::STEP_COUNT_WITHOUT_DEADLINES_MY:
					self::recountCounters_MY_WITHOUT_DEADLINES($userId = '*');	// recount for all users
					$nextStep = self::STEP_COUNT_WITHOUT_DEADLINES_FOR_ORIGINATORS;
				break;

				case self::STEP_COUNT_WITHOUT_DEADLINES_FOR_ORIGINATORS:
					self::recountCounters_ORIGINATORS_WITHOUT_DEADLINES($userId = '*');	// recount for all users
					$nextStep = self::STEP_COUNT_EXPIRED;
				break;

				case self::STEP_COUNT_EXPIRED:
					$executionTimeLimit = mt_rand(1, 6);		// time limit in seconds
					$itemsProcessed = CTaskCountersProcessor::countExpiredAndExpiredSoonTasks($executionTimeLimit);

					// Some items processed?
					if ($itemsProcessed > 0)
					{
						// try again
						$nextStep      = self::STEP_COUNT_EXPIRED;
						$nextStepDelay = 5;
					}
					else
					{
						self::setStage(self::STAGE_INSTALL_COMPLETE);
//						CTaskCountersProcessorHomeostasis::onCalculationComplete();
						$nextStep = null;	// the end
					}
				break;

				default:
					CTaskAssert::logError('[0xd7b90d6d] ');
					$nextStep = null;	// the end
				break;
			}

			if ($nextStep === null)
				break;

			if ($nextStepDelay > 0)
				break;

			$step = $nextStep;
		}

		if ($nextStep !== null)
		{
			/** @noinspection PhpDynamicAsStaticMethodCallInspection */
			return 'CTaskCountersProcessorInstaller::setup("' . $nextStep . '");';
		}

		return "";
	}


	public static function isInstallComplete()
	{
		return (self::getStage() == self::STAGE_INSTALL_COMPLETE);
	}

	public static function dropStageToCompleted()
	{
		// drop stage option
		static::setStage(self::STAGE_INSTALL_COMPLETE);

		// remove all transitional agents
		$res = CAgent::GetList(array(), array(
			'NAME' => 'CTaskCountersProcessorInstaller%',
			'MODULE_ID' => 'tasks',
		));
		while($item = $res->fetch())
		{
			CAgent::RemoveAgent($item['NAME'], 'tasks');
		}
	}

	public static function getStage()
	{
		static $arKnownStages = array(
			self::STAGE_INSTALL_IN_PROGRESS,
			self::STAGE_INSTALL_COMPLETE
		);

		$stageId = (int) COption::GetOptionString('tasks', '~counters_installer_stage', -1, $siteId = '');

		// determine stage
		if ( ! in_array($stageId, $arKnownStages, true))
		{
			self::runSetup(); // why initialize runSetup ?
			$stageId = self::STAGE_INSTALL_IN_PROGRESS;
		}

		return ($stageId);
	}

	public static function checkProcessIsNotActive()
	{
		$stageId = (int) COption::GetOptionString('tasks', '~counters_installer_stage', -1, $siteId = '');

		return $stageId == 0 || $stageId == self::STAGE_INSTALL_COMPLETE;
	}

	public static function setStage($stageId)
	{
		if($stageId != self::STAGE_INSTALL_IN_PROGRESS && $stageId != self::STAGE_INSTALL_COMPLETE)
		{
			return;
		}

		COption::SetOptionString('tasks', '~counters_installer_stage', $stageId, $description = '', $siteId = '');
	}


	private static function reset()
	{
		global $DB, $CACHE_MANAGER;

		// Reset tasks marked as processed by CTaskCountersProcessor::agent()
		$DB->Query("UPDATE b_tasks SET DEADLINE_COUNTED = 0 WHERE 1=1");

		$arCountersIds = CTaskCountersProcessor::enumCountersIds();

		if (count($arCountersIds))
		{
			// Reset all tasks' counters of users
			$DB->Query(
				"UPDATE b_user_counter 
				SET CNT = 0
				WHERE SITE_ID = '**' 
					AND CODE IN ('" . implode("', '", $arCountersIds) . "')",
				$bIgnoreErrors = true
			);

			/** @var $CACHE_MANAGER CCacheManager */
			$CACHE_MANAGER->CleanDir('user_counter');
		}
	}


	private static function recountCounters_MY_NEW($userId)
	{
		global $DB;

		$DB->startTransaction();

		// Count not viewed tasks by responsible
		$strSql = "SELECT COUNT(T.ID) AS MY_NEW_CNT, T.RESPONSIBLE_ID AS RESPONSIBLE_ID
			FROM b_tasks T
			LEFT JOIN b_tasks_viewed TV ON TV.TASK_ID = T.ID AND TV.USER_ID = T.RESPONSIBLE_ID
			WHERE 
				T.ID IS NOT NULL 
				AND T.ZOMBIE = 'N'
				AND TV.USER_ID IS NULL 
				AND (T.STATUS = " . CTasks::STATE_NEW . " OR T.STATUS = " . CTasks::STATE_PENDING . ")
			";

		if ($userId !== '*')	// All users or not?
			$strSql .= " AND T.RESPONSIBLE_ID = " . (int) $userId;
				
		$strSql .= " GROUP BY T.RESPONSIBLE_ID";

		$rc = $DB->query($strSql);

		$i = 0;
		while ($ar = $rc->fetch())
		{
			$arValues = CUserCounter::GetValues($ar['RESPONSIBLE_ID'], $site_id = '**');

			$total = 0;
			if (isset($arValues[CTaskCountersProcessor::COUNTER_TASKS_TOTAL]))
				$total = (int) $arValues[CTaskCountersProcessor::COUNTER_TASKS_TOTAL];

			$subtotal = 0;
			if (isset($arValues[CTaskCountersProcessor::COUNTER_TASKS_MY]))
				$subtotal = (int) $arValues[CTaskCountersProcessor::COUNTER_TASKS_MY];

			CUserCounter::Set(
				(int) $ar['RESPONSIBLE_ID'],
				CTaskCountersProcessor::COUNTER_TASKS_MY_NEW,
				(int) $ar['MY_NEW_CNT'],
				'**',		// $site_id
				$tag = '',
				false		// $sendPull
			);

			CUserCounter::Set(
				(int) $ar['RESPONSIBLE_ID'],
				CTaskCountersProcessor::COUNTER_TASKS_MY,
				$subtotal + (int) $ar['MY_NEW_CNT'],
				'**',		// $site_id
				$tag = '',
				false		// $sendPull
			);

			CUserCounter::Set(
				(int) $ar['RESPONSIBLE_ID'],
				CTaskCountersProcessor::COUNTER_TASKS_TOTAL,
				$total + (int) $ar['MY_NEW_CNT'],
				'**',		// $site_id
				$tag = '',
				false		// $sendPull
			);

			// commit on after every 100 users have been processed
			if ( ! (++$i % 100) )
			{
				soundex('commit every 100 users');
				$DB->commit();
				$DB->startTransaction();
			}
		}

		$DB->commit();
	}


	private static function recountCounters_ACCOMPLICE_NEW($userId)
	{
		global $DB;

		$DB->startTransaction();

		// Count not viewed tasks by accomplices
		$strSql = "SELECT COUNT(TM.TASK_ID) AS ACC_NEW_CNT, TM.USER_ID AS USER_ID
			FROM b_tasks_member TM
			LEFT JOIN b_tasks T ON T.ID = TM.TASK_ID
			LEFT JOIN b_tasks_viewed TV ON TV.TASK_ID = T.ID AND TV.USER_ID = TM.USER_ID
			WHERE
				T.ID IS NOT NULL 
				AND T.ZOMBIE = 'N' 
				AND TM.TYPE = 'A' 
				AND TV.USER_ID IS NULL 
				AND (T.STATUS = " . CTasks::STATE_NEW . " OR T.STATUS = " . CTasks::STATE_PENDING . ")
		";

		if ($userId !== '*')	// All users or not?
			$strSql .= " AND TM.USER_ID = " . (int) $userId;
				
		$strSql .= "GROUP BY TM.USER_ID";

		$rc = $DB->query($strSql);

		$i = 0;
		while ($ar = $rc->fetch())
		{
			$arValues = CUserCounter::GetValues($ar['USER_ID'], $site_id = '**');

			$total = 0;
			if (isset($arValues[CTaskCountersProcessor::COUNTER_TASKS_TOTAL]))
				$total = (int) $arValues[CTaskCountersProcessor::COUNTER_TASKS_TOTAL];

			$subtotal = 0;
			if (isset($arValues[CTaskCountersProcessor::COUNTER_TASKS_ACCOMPLICE]))
				$subtotal = (int) $arValues[CTaskCountersProcessor::COUNTER_TASKS_ACCOMPLICE];

			CUserCounter::Set(
				(int) $ar['USER_ID'],
				CTaskCountersProcessor::COUNTER_TASKS_ACCOMPLICE_NEW,
				(int) $ar['ACC_NEW_CNT'],
				'**',		// $site_id
				$tag = '',
				false		// $sendPull
			);

			CUserCounter::Set(
				(int) $ar['USER_ID'],
				CTaskCountersProcessor::COUNTER_TASKS_ACCOMPLICE,
				$subtotal + (int) $ar['ACC_NEW_CNT'],
				'**',		// $site_id
				$tag = '',
				false		// $sendPull
			);

			CUserCounter::Set(
				(int) $ar['USER_ID'],
				CTaskCountersProcessor::COUNTER_TASKS_TOTAL,
				$total + (int) $ar['ACC_NEW_CNT'],
				'**',		// $site_id
				$tag = '',
				false		// $sendPull
			);

			// commit on after every 100 users have been processed
			if ( ! (++$i % 100) )
			{
				soundex('commit every 100 users');
				$DB->commit();
				$DB->startTransaction();
			}
		}

		$DB->commit();
	}


	private static function recountCounters_ORIGINATORS_WAIT_CTRL($userId)
	{
		global $DB;

		$DB->startTransaction();

		// Count tasks in state CTasks::STATE_SUPPOSEDLY_COMPLETED for originators
		$strSql = "SELECT COUNT(T.ID) AS ORIG_WAIT_CTRL_CNT, T.CREATED_BY AS CREATED_BY
			FROM b_tasks T
			WHERE 
				T.ZOMBIE = 'N'
				AND T.CREATED_BY != T.RESPONSIBLE_ID
				AND T.CREATED_BY != 0
				AND T.RESPONSIBLE_ID != 0
				AND T.STATUS = " . CTasks::STATE_SUPPOSEDLY_COMPLETED . "
			";

		if ($userId !== '*')	// All users or not?
			$strSql .= " AND T.CREATED_BY = " . (int) $userId;
				
		$strSql .= " GROUP BY T.CREATED_BY";

		$rc = $DB->query($strSql);

		$i = 0;
		while ($ar = $rc->fetch())
		{
			$arValues = CUserCounter::GetValues($ar['CREATED_BY'], $site_id = '**');

			$total = 0;
			if (isset($arValues[CTaskCountersProcessor::COUNTER_TASKS_TOTAL]))
				$total = (int) $arValues[CTaskCountersProcessor::COUNTER_TASKS_TOTAL];

			$subtotal = 0;
			if (isset($arValues[CTaskCountersProcessor::COUNTER_TASKS_ORIGINATOR]))
				$subtotal = (int) $arValues[CTaskCountersProcessor::COUNTER_TASKS_ORIGINATOR];

			CUserCounter::Set(
				(int) $ar['CREATED_BY'],
				CTaskCountersProcessor::COUNTER_TASKS_ORIGINATOR_WAIT_CTRL,
				(int) $ar['ORIG_WAIT_CTRL_CNT'],
				'**',		// $site_id
				$tag = '',
				false		// $sendPull
			);

			CUserCounter::Set(
				(int) $ar['CREATED_BY'],
				CTaskCountersProcessor::COUNTER_TASKS_ORIGINATOR,
				$subtotal + (int) $ar['ORIG_WAIT_CTRL_CNT'],
				'**',		// $site_id
				$tag = '',
				false		// $sendPull
			);

			CUserCounter::Set(
				(int) $ar['CREATED_BY'],
				CTaskCountersProcessor::COUNTER_TASKS_TOTAL,
				$total + (int) $ar['ORIG_WAIT_CTRL_CNT'],
				'**',		// $site_id
				$tag = '',
				false		// $sendPull
			);

			// commit on after every 100 users have been processed
			if ( ! (++$i % 100) )
			{
				soundex('commit every 100 users');
				$DB->commit();
				$DB->startTransaction();
			}
		}

		$DB->commit();
	}


	private static function recountCounters_MY_WITHOUT_DEADLINES($userId)
	{
		global $DB;

		$DB->startTransaction();

		// Count tasks without DEADLINES
		// not in states: CTasks::STATE_SUPPOSEDLY_COMPLETED, CTasks::STATE_COMPLETED, CTasks::STATE_DECLINED
		// and where CREATED_BY != RESPONSIBLE_ID.
		// Count tasks for resposibles.

		$strSql = 
			"SELECT COUNT(T.ID) AS T_WO_DEADLINES, 
				T.RESPONSIBLE_ID AS RESPONSIBLE_ID
			FROM b_tasks T
			WHERE 
				T.ZOMBIE = 'N'
				AND T.CREATED_BY != T.RESPONSIBLE_ID
				AND T.CREATED_BY != 0
				AND T.RESPONSIBLE_ID != 0
				AND T.DEADLINE IS NULL
				AND T.STATUS != " . CTasks::STATE_DECLINED . "
				AND T.STATUS != " . CTasks::STATE_SUPPOSEDLY_COMPLETED . "
				AND T.STATUS != " . CTasks::STATE_COMPLETED . "
			";

		if ($userId !== '*')	// All users or not?
			$strSql .= " AND T.RESPONSIBLE_ID = " . (int) $userId;
				
		$strSql .= " GROUP BY T.RESPONSIBLE_ID";

		$rc = $DB->query($strSql);

		$i = 0;
		while ($ar = $rc->fetch())
		{
			$arValues = CUserCounter::GetValues($ar['RESPONSIBLE_ID'], $site_id = '**');

			$total = 0;
			if (isset($arValues[CTaskCountersProcessor::COUNTER_TASKS_TOTAL]))
				$total = (int) $arValues[CTaskCountersProcessor::COUNTER_TASKS_TOTAL];

			$subtotal = 0;
			if (isset($arValues[CTaskCountersProcessor::COUNTER_TASKS_MY]))
				$subtotal = (int) $arValues[CTaskCountersProcessor::COUNTER_TASKS_MY];

			CUserCounter::Set(
				(int) $ar['RESPONSIBLE_ID'],
				CTaskCountersProcessor::COUNTER_TASKS_MY_WO_DEADLINE,
				(int) $ar['T_WO_DEADLINES'],
				'**',		// $site_id
				$tag = '',
				false		// $sendPull
			);

			CUserCounter::Set(
				(int) $ar['RESPONSIBLE_ID'],
				CTaskCountersProcessor::COUNTER_TASKS_MY,
				$subtotal + (int) $ar['T_WO_DEADLINES'],
				'**',		// $site_id
				$tag = '',
				false		// $sendPull
			);

			CUserCounter::Set(
				(int) $ar['RESPONSIBLE_ID'],
				CTaskCountersProcessor::COUNTER_TASKS_TOTAL,
				$total + (int) $ar['T_WO_DEADLINES'],
				'**',		// $site_id
				$tag = '',
				false		// $sendPull
			);

			// commit on after every 100 users have been processed
			if ( ! (++$i % 100) )
			{
				soundex('commit every 100 users');
				$DB->commit();
				$DB->startTransaction();
			}
		}

		$DB->commit();
	}


	private static function recountCounters_ORIGINATORS_WITHOUT_DEADLINES($userId)
	{
		global $DB;

		$DB->startTransaction();

		// Count tasks without DEADLINES
		// not in states: CTasks::STATE_SUPPOSEDLY_COMPLETED, CTasks::STATE_COMPLETED, CTasks::STATE_DECLINED
		// and where CREATED_BY != RESPONSIBLE_ID.
		// Count tasks for originators.

		$strSql = 
			"SELECT COUNT(T.ID) AS T_WO_DEADLINES, 
				T.CREATED_BY AS CREATED_BY
			FROM b_tasks T
			WHERE 
				T.ZOMBIE = 'N'
				AND T.CREATED_BY != T.RESPONSIBLE_ID
				AND T.CREATED_BY != 0
				AND T.RESPONSIBLE_ID != 0
				AND T.DEADLINE IS NULL
				AND T.STATUS != " . CTasks::STATE_DECLINED . "
				AND T.STATUS != " . CTasks::STATE_SUPPOSEDLY_COMPLETED . "
				AND T.STATUS != " . CTasks::STATE_COMPLETED . "
			";

		if ($userId !== '*')	// All users or not?
			$strSql .= " AND T.CREATED_BY = " . (int) $userId;
				
		$strSql .= " GROUP BY T.CREATED_BY";

		$rc = $DB->query($strSql);

		$i = 0;
		while ($ar = $rc->fetch())
		{
			$arValues = CUserCounter::GetValues($ar['CREATED_BY'], $site_id = '**');

			$total = 0;
			if (isset($arValues[CTaskCountersProcessor::COUNTER_TASKS_TOTAL]))
				$total = (int) $arValues[CTaskCountersProcessor::COUNTER_TASKS_TOTAL];

			$subtotal = 0;
			if (isset($arValues[CTaskCountersProcessor::COUNTER_TASKS_ORIGINATOR]))
				$subtotal = (int) $arValues[CTaskCountersProcessor::COUNTER_TASKS_ORIGINATOR];

			CUserCounter::Set(
				(int) $ar['CREATED_BY'],
				CTaskCountersProcessor::COUNTER_TASKS_ORIGINATOR_WO_DEADLINE,
				(int) $ar['T_WO_DEADLINES'],
				'**',		// $site_id
				$tag = '',
				false		// $sendPull
			);

			CUserCounter::Set(
				(int) $ar['CREATED_BY'],
				CTaskCountersProcessor::COUNTER_TASKS_ORIGINATOR,
				$subtotal + (int) $ar['T_WO_DEADLINES'],
				'**',		// $site_id
				$tag = '',
				false		// $sendPull
			);

			CUserCounter::Set(
				(int) $ar['CREATED_BY'],
				CTaskCountersProcessor::COUNTER_TASKS_TOTAL,
				$total + (int) $ar['T_WO_DEADLINES'],
				'**',		// $site_id
				$tag = '',
				false		// $sendPull
			);

			// commit on after every 100 users have been processed
			if ( ! (++$i % 100) )
			{
				soundex('commit every 100 users');
				$DB->commit();
				$DB->startTransaction();
			}
		}

		$DB->commit();
	}
}
