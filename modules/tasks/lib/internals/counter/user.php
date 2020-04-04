<?php

namespace Bitrix\Tasks\Internals\Counter;

use Bitrix\Main\Application;
use Bitrix\Tasks\Internals\Counter as BaseCounter;
use Bitrix\Tasks\Internals\CounterName;
use Bitrix\Tasks\Util\Collection;

class User extends BaseCounter
{
	private $needSaveCounters = false; // true if same counter was changed

	protected function __construct($userId, $groupId = 0)
	{
		$this->userId = $userId;
		$this->groupId = $groupId;

		$this->loadCounters();
	}

	protected function loadCounters()
	{
		$date = date('Y-m-d');

		if ($this->groupId > 0)
		{
			$sql = "SELECT * FROM b_tasks_counters WHERE DATE = '{$date}' AND USER_ID = {$this->userId} AND GROUP_ID = {$this->groupId}";
		}
		else
		{
			$sql = "
			SELECT 
				GROUP_ID,
				
				SUM(OPENED) AS OPENED,
				SUM(CLOSED) AS CLOSED,
				
				SUM(MY_EXPIRED) AS MY_EXPIRED,
				SUM(MY_EXPIRED_SOON) AS MY_EXPIRED_SOON,
				SUM(MY_NOT_VIEWED) AS MY_NOT_VIEWED,
				SUM(MY_WITHOUT_DEADLINE) AS MY_WITHOUT_DEADLINE,
				
				SUM(ORIGINATOR_EXPIRED) AS ORIGINATOR_EXPIRED,
				SUM(ORIGINATOR_WITHOUT_DEADLINE) AS ORIGINATOR_WITHOUT_DEADLINE,
				SUM(ORIGINATOR_WAIT_CTRL) AS ORIGINATOR_WAIT_CTRL,
				
				SUM(AUDITOR_EXPIRED) AS AUDITOR_EXPIRED,
				
				SUM(ACCOMPLICES_EXPIRED) AS ACCOMPLICES_EXPIRED,
				SUM(ACCOMPLICES_EXPIRED_SOON) AS ACCOMPLICES_EXPIRED_SOON,
				SUM(ACCOMPLICES_NOT_VIEWED) AS ACCOMPLICES_NOT_VIEWED

			FROM 
				b_tasks_counters 
			WHERE 
				DATE = '{$date}' AND 
				USER_ID = {$this->userId} 
			GROUP BY 
				GROUP_ID";
		}

		$res = Application::getConnection()->query($sql);
		$counters = $res->fetchAll();

		if (!$counters)
		{
			$this->recountAllCounters();
		}
		else
		{
			foreach ($counters as $row)
			{
				$groupId = $row['GROUP_ID'];
				unset($row['GROUP_ID']);

				foreach ($row as $key => $value)
				{
					$this->counters[strtolower($key)]['allCounters'] += $value;
					$this->counters[strtolower($key)][$groupId] = $value;
				}
			}
		}
	}

	private function recountAllCounters()
	{
		$reflect = new \ReflectionClass('\Bitrix\Tasks\Internals\CounterName');
		foreach ($reflect->getConstants() as $counterName)
		{
			$method = 'calc'.implode('', array_map('ucfirst', explode('_', $counterName)));
			if (method_exists($this, $method))
			{
				$this->{$method}(true);
			}
		}

		$this->saveCounters();
	}

	public static function onBeforeTaskAdd()
	{
	}

	public static function onAfterTaskAdd(array $fields)
	{
		$responsible = new Collection;
		$originator = new Collection;
		$auditor = new Collection;
		$accomplice = new Collection;

		if ($fields['RESPONSIBLE_ID'] != $fields['CREATED_BY'])
		{
			$responsible->push(CounterName::MY_NOT_VIEWED);
			$originator->push(CounterName::MY_NOT_VIEWED);

			if ($fields['DEADLINE'])
			{
				$originator->push(CounterName::ORIGINATOR_EXPIRED);
				$responsible->push(CounterName::MY_EXPIRED);
				$responsible->push(CounterName::MY_EXPIRED_SOON);
			}
			else
			{
				$originator->push(CounterName::ORIGINATOR_WITHOUT_DEADLINE);
				$responsible->push(CounterName::MY_WITHOUT_DEADLINE);
			}
		}

		if (!empty($fields['AUDITORS']))
		{
			if ($fields['DEADLINE'])
			{
				$auditor->push(CounterName::AUDITOR_EXPIRED);
			}
		}

		if (!empty($fields['ACCOMPLICES']))
		{
			$accomplice->push(CounterName::ACCOMPLICES_NOT_VIEWED);

			if ($fields['DEADLINE'])
			{
				$accomplice->push(CounterName::ACCOMPLICES_EXPIRED);
				$accomplice->push(CounterName::ACCOMPLICES_EXPIRED_SOON);
			}
		}

		// PROCESS RECALCULATE
		if ($responsible->count() > 0)
		{
			$responsible->push(CounterName::OPENED);
			$responsible->push(CounterName::CLOSED);

			$counter = self::getInstance($fields['RESPONSIBLE_ID']);
			$counter->processRecalculate($responsible);
		}

		if ($originator->count() > 0)
		{
			$originator->push(CounterName::OPENED);
			$originator->push(CounterName::CLOSED);

			$counter = self::getInstance($fields['CREATED_BY']);
			$counter->processRecalculate($originator);
		}

		if ($auditor->count() > 0)
		{
			foreach ($fields['AUDITORS'] as $userId)
			{
				$counter = self::getInstance($userId);
				$counter->processRecalculate($auditor);
			}
		}

		if ($accomplice->count() > 0)
		{
			foreach ($fields['ACCOMPLICES'] as $userId)
			{
				$accomplice->push(CounterName::OPENED);
				$accomplice->push(CounterName::CLOSED);

				$counter = static::getInstance($userId);
				$counter->processRecalculate($accomplice);
			}
		}
	}

	public static function getInstance($userId, $groupId = 0)
	{
		if (!self::$instance ||
			!array_key_exists($userId, self::$instance) ||
			!array_key_exists($groupId, self::$instance[$userId]))
		{
			self::$instance[$userId][$groupId] = new self($userId, $groupId);
		}

		return self::$instance[$userId][$groupId];
	}

	public static function onBeforeTaskUpdate()
	{
	}

	public static function onAfterTaskUpdate($fields, $newFields)
	{
		$responsible = new Collection;
		$originator = new Collection;
		$auditor = new Collection;
		$accomplice = new Collection;

		if ($fields['RESPONSIBLE_ID'] != $fields['CREATED_BY'])
		{
			$responsible->push(CounterName::MY_NOT_VIEWED);
			$originator->push(CounterName::MY_NOT_VIEWED);

			if ($fields['DEADLINE'])
			{
				$originator->push(CounterName::ORIGINATOR_EXPIRED);
				$responsible->push(CounterName::MY_EXPIRED);
				$responsible->push(CounterName::MY_EXPIRED_SOON);
			}
			else
			{
				$originator->push(CounterName::ORIGINATOR_WITHOUT_DEADLINE);
				$responsible->push(CounterName::MY_WITHOUT_DEADLINE);
			}
		}

		if (!empty($fields['AUDITORS']))
		{
			if (!$fields['DEADLINE'])
			{
				$auditor->push(CounterName::AUDITOR_EXPIRED);
			}
		}

		if (!empty($fields['ACCOMPLICES']))
		{
			$accomplice->push(CounterName::ACCOMPLICES_NOT_VIEWED);

			if ($fields['DEADLINE'])
			{
				$accomplice->push(CounterName::ACCOMPLICES_EXPIRED);
				$accomplice->push(CounterName::ACCOMPLICES_EXPIRED_SOON);
			}
		}

		// PROCESS RECALCULATE
		if ($responsible->count() > 0)
		{
			$responsible->push(CounterName::OPENED);
			$responsible->push(CounterName::CLOSED);


			$counter = self::getInstance($fields['RESPONSIBLE_ID']);
			$counter->processRecalculate($responsible);

			if(array_key_exists('RESPONSIBLE_ID', $newFields) &&
			   $newFields['RESPONSIBLE_ID'] != $fields['RESPONSIBLE_ID'])
			{
				$counter = self::getInstance($newFields['RESPONSIBLE_ID']);
				$counter->processRecalculate($responsible);
			}
		}

		if ($originator->count() > 0)
		{
			$originator->push(CounterName::OPENED);
			$originator->push(CounterName::CLOSED);

			$counter = self::getInstance($fields['CREATED_BY']);
			$counter->processRecalculate($originator);

			if(array_key_exists('CREATED_BY', $newFields) &&
			   $newFields['CREATED_BY'] != $fields['CREATED_BY'])
			{
				$counter = self::getInstance($newFields['CREATED_BY']);
				$counter->processRecalculate($originator);
			}
		}

		if ($auditor->count() > 0)
		{
			$auditors = array_unique(
				array_merge($fields['AUDITORS'], $newFields['AUDITORS'])
			);
			foreach ($auditors as $userId)
			{
				$auditor->push(CounterName::OPENED);
				$auditor->push(CounterName::CLOSED);

				$counter = self::getInstance($userId);
				$counter->processRecalculate($auditor);
			}
		}

		if ($accomplice->count() > 0)
		{
			$accomplices = array_unique(
				array_merge($fields['ACCOMPLICES'], $newFields['ACCOMPLICES'])
			);
			foreach ($accomplices as $userId)
			{
				$accomplice->push(CounterName::OPENED);
				$accomplice->push(CounterName::CLOSED);

				$counter = static::getInstance($userId);
				$counter->processRecalculate($accomplice);
			}
		}
	}

	public static function onBeforeTaskDelete()
	{
	}

	public static function onAfterTaskDelete($fields)
	{
		$responsible = new Collection;
		$originator = new Collection;
		$auditor = new Collection;
		$accomplice = new Collection;

		if ($fields['RESPONSIBLE_ID'] != $fields['CREATED_BY'])
		{
			$responsible->push(CounterName::MY_NOT_VIEWED);
			$originator->push(CounterName::MY_NOT_VIEWED);

			if ($fields['DEADLINE'])
			{
				$originator->push(CounterName::ORIGINATOR_EXPIRED);
				$responsible->push(CounterName::MY_EXPIRED);
				$responsible->push(CounterName::MY_EXPIRED_SOON);
			}
			else
			{
				$originator->push(CounterName::ORIGINATOR_WITHOUT_DEADLINE);
				$responsible->push(CounterName::MY_WITHOUT_DEADLINE);
			}
		}

		if (!empty($fields['AUDITORS']))
		{
			if (!$fields['DEADLINE'])
			{
				$auditor->push(CounterName::AUDITOR_EXPIRED);
			}
		}

		if (!empty($fields['ACCOMPLICES']))
		{
			$accomplice->push(CounterName::ACCOMPLICES_NOT_VIEWED);

			if ($fields['DEADLINE'])
			{
				$accomplice->push(CounterName::ACCOMPLICES_EXPIRED);
				$accomplice->push(CounterName::ACCOMPLICES_EXPIRED_SOON);
			}
		}

		// PROCESS RECALCULATE
		if ($responsible->count() > 0)
		{
			$responsible->push(CounterName::OPENED);
			$responsible->push(CounterName::CLOSED);

			$counter = self::getInstance($fields['RESPONSIBLE_ID']);
			$counter->processRecalculate($responsible);
		}

		if ($originator->count() > 0)
		{
			$originator->push(CounterName::OPENED);
			$originator->push(CounterName::CLOSED);

			$counter = self::getInstance($fields['CREATED_BY']);
			$counter->processRecalculate($originator);
		}

		if ($auditor->count() > 0)
		{
			foreach ($fields['AUDITORS'] as $userId)
			{
				$auditor->push(CounterName::OPENED);
				$auditor->push(CounterName::CLOSED);

				$counter = self::getInstance($userId);
				$counter->processRecalculate($auditor);
			}
		}

		if ($accomplice->count() > 0)
		{
			foreach ($fields['ACCOMPLICES'] as $userId)
			{
				$accomplice->push(CounterName::OPENED);
				$accomplice->push(CounterName::CLOSED);

				$counter = static::getInstance($userId);
				$counter->processRecalculate($accomplice);
			}
		}
	}

	public static function onBeforeTaskViewedFirstTime()
	{
	}

	public static function onAfterTaskViewedFirstTime($taskId, $userId, $onTaskAdd)
	{
		if ($onTaskAdd)
		{
			return;
		}

		$responsible = new Collection;
		$responsible->push(CounterName::MY_NOT_VIEWED);
		$responsible->push(CounterName::ACCOMPLICES_NOT_VIEWED);
		$responsible->push(CounterName::OPENED);
		$responsible->push(CounterName::CLOSED);

		$counter = static::getInstance($userId);
		$counter->processRecalculate($responsible);
	}

	private function calcOpened($reCache = false)
	{
		static $count = null;

		if ($count == null || $reCache)
		{
			$statusSupposedlyCompleted = \CTasks::STATE_SUPPOSEDLY_COMPLETED;
			$statusCompleted = \CTasks::STATE_COMPLETED;
			$statusDeferred = \CTasks::STATE_DEFERRED;

			$sql = "
				SELECT 
					COUNT(t.ID) as COUNT,
					t.GROUP_ID
				FROM 
					b_tasks AS t
					JOIN b_tasks_member as tm ON 
						tm.TASK_ID = t.ID AND 
						tm.USER_ID = {$this->userId} AND
						tm.TYPE IN('A', 'R') 
				WHERE
					t.CREATED_BY != {$this->userId}
					AND t.ZOMBIE = 'N'
					".($this->groupId > 0 ? " AND t.GROUP_ID = {$this->groupId}" : "")."
					AND (
						t.STATUS != {$statusSupposedlyCompleted}
						AND t.STATUS != {$statusCompleted}
						AND	t.STATUS != {$statusDeferred}
					)
				GROUP BY 
					t.GROUP_ID
			";

			$this->changeCounter(
				CounterName::OPENED,
				Application::getConnection()->query($sql)->fetchAll()
			);
		}
	}

	private function calcClosed($reCache = false)
	{
		static $count = null;

		if ($count == null || $reCache)
		{
			$statusSupposedlyCompleted = \CTasks::STATE_SUPPOSEDLY_COMPLETED;
			$statusCompleted = \CTasks::STATE_COMPLETED;
			$statusDeferred = \CTasks::STATE_DEFERRED;

			$sql = "
				SELECT 
					COUNT(t.ID) as COUNT,
					t.GROUP_ID
				FROM 
					b_tasks AS t
					JOIN b_tasks_member as tm ON 
						tm.TASK_ID = t.ID AND 
						tm.USER_ID = {$this->userId} AND
						tm.TYPE IN('A', 'R') 
				WHERE
					t.CREATED_BY != {$this->userId}
					AND t.ZOMBIE = 'N'
					".($this->groupId > 0 ? " AND t.GROUP_ID = {$this->groupId}" : "")."
					AND (
						t.STATUS = {$statusSupposedlyCompleted}
						OR t.STATUS = {$statusCompleted}
					)
				GROUP BY 
					t.GROUP_ID
			";

			$this->changeCounter(
				CounterName::OPENED,
				Application::getConnection()->query($sql)->fetchAll()
			);
		}
	}

	private function calcMyNotViewed($reCache = false)
	{
		static $count = null;

		if ($count == null || $reCache)
		{
			$statusSupposedlyCompleted = \CTasks::STATE_SUPPOSEDLY_COMPLETED;
			$statusCompleted = \CTasks::STATE_COMPLETED;
			$statusDeferred = \CTasks::STATE_DEFERRED;

			$sql = "
				SELECT 
					COUNT(t.ID) as COUNT,
					t.GROUP_ID
				FROM 
					b_tasks as t
					LEFT JOIN b_tasks_viewed as tv
						ON tv.TASK_ID = t.ID AND tv.USER_ID = {$this->userId}
				WHERE
					(tv.TASK_ID IS NULL OR tv.TASK_ID = 0) AND
					t.CREATED_BY != {$this->userId} AND 
					t.ZOMBIE = 'N' AND
					".($this->groupId > 0 ? " t.GROUP_ID = {$this->groupId} AND" : "")."
					(
						t.STATUS != {$statusSupposedlyCompleted}
						AND t.STATUS != {$statusCompleted}
						AND	t.STATUS != {$statusDeferred}
					)
				GROUP BY
					t.GROUP_ID
			";

			$this->changeCounter(
				CounterName::MY_NOT_VIEWED,
				Application::getConnection()->query($sql)->fetchAll()
			);
		}
	}

	private function changeCounter($name, $counts)
	{
		if (!$counts || !is_array($counts))
		{
			return;
		}

		$counters = array();
		$allCount = 0;

		foreach ($counts as $row)
		{
			$this->counters[$name][$row['GROUP_ID']] = (int)$row['COUNT'];
			$counters[$row['GROUP_ID']] = (int)$row['COUNT'];
			$allCount += (int)$row['COUNT'];
		}

		if (!$this->needSaveCounters)
		{
			if (!($this->needSaveCounters = ($allCount != $this->counters[$name]['allCounters'])))
			{
				return;
			}
		}

		$this->counters[$name]['allCounters'] = $allCount;

		foreach ($counters as $groupId => $counter)
		{
			$this->counters[$name][$groupId] = $counter;
		}

		$this->counters[$name]['allCounters'] = $allCount;
	}

	private function calcMyWithoutDeadline($reCache = false)
	{
		static $count = null;

		if ($count == null || $reCache)
		{
			$statusSupposedlyCompleted = \CTasks::STATE_SUPPOSEDLY_COMPLETED;
			$statusCompleted = \CTasks::STATE_COMPLETED;
			$statusDeferred = \CTasks::STATE_DEFERRED;

			$sql = "
				SELECT 
					COUNT(ID) as COUNT,
					GROUP_ID
				FROM 
					b_tasks 
				WHERE 
					(DEADLINE = '' OR DEADLINE IS NULL)
					AND RESPONSIBLE_ID = {$this->userId}
					AND RESPONSIBLE_ID != CREATED_BY
					AND ZOMBIE = 'N'
					".($this->groupId > 0 ? " AND GROUP_ID = {$this->groupId}" : "")."
					AND (
						STATUS != {$statusSupposedlyCompleted}
						AND STATUS != {$statusCompleted}
						AND	STATUS != {$statusDeferred}
					)
				GROUP BY 
					GROUP_ID
			";

			$this->changeCounter(
				CounterName::MY_WITHOUT_DEADLINE,
				Application::getConnection()->query($sql)->fetchAll()
			);
		}
	}

	private function calcMyExpired($reCache = false)
	{
		static $count = null;

		if ($count == null || $reCache)
		{
			$expiredTime = $this->getExpiredTime();
			$statusSupposedlyCompleted = \CTasks::STATE_SUPPOSEDLY_COMPLETED;
			$statusCompleted = \CTasks::STATE_COMPLETED;
			$statusDeferred = \CTasks::STATE_DEFERRED;

			$sql = "
				SELECT 
					COUNT(ID) as COUNT,
					GROUP_ID
				FROM 
					b_tasks 
				WHERE 
					DEADLINE < '{$expiredTime}'
					AND RESPONSIBLE_ID = {$this->userId}
					AND RESPONSIBLE_ID != CREATED_BY
					AND ZOMBIE = 'N'
					".($this->groupId > 0 ? " AND GROUP_ID = {$this->groupId}" : "")."
					AND (
						STATUS != {$statusSupposedlyCompleted}
						AND STATUS != {$statusCompleted}
						AND	STATUS != {$statusDeferred}
					)
				GROUP BY 
					GROUP_ID
			";

			$this->changeCounter(
				CounterName::MY_EXPIRED,
				Application::getConnection()->query($sql)->fetchAll()
			);
		}
	}

	private function calcMyExpiredSoon($reCache = false)
	{
		static $count = null;

		if ($count == null || $reCache)
		{
			$expiredSoonTime = $this->getExpiredSoonTime();
			$expiredTime = $this->getExpiredTime();
			$statusSupposedlyCompleted = \CTasks::STATE_SUPPOSEDLY_COMPLETED;
			$statusCompleted = \CTasks::STATE_COMPLETED;
			$statusDeferred = \CTasks::STATE_DEFERRED;

			$sql = "
				SELECT 
					COUNT(ID) as COUNT,
					GROUP_ID
				FROM 
					b_tasks 
				WHERE 
					DEADLINE < '{$expiredSoonTime}'
					AND DEADLINE >= '{$expiredTime}'
					AND RESPONSIBLE_ID = {$this->userId}
					AND RESPONSIBLE_ID != CREATED_BY
					AND ZOMBIE = 'N'
					".($this->groupId > 0 ? " AND GROUP_ID = {$this->groupId}" : "")."
					AND (
						STATUS != {$statusSupposedlyCompleted}
						AND STATUS != {$statusCompleted}
						AND	STATUS != {$statusDeferred}
					)
				GROUP BY 
					GROUP_ID
			";

			$this->changeCounter(
				CounterName::MY_EXPIRED_SOON,
				Application::getConnection()->query($sql)->fetchAll()
			);
		}
	}

	private function calcAuditorExpired($reCache = false)
	{
		static $count = null;

		if ($count == null || $reCache)
		{
			$expiredTime = $this->getExpiredTime();

			$statusSupposedlyCompleted = \CTasks::STATE_SUPPOSEDLY_COMPLETED;
			$statusCompleted = \CTasks::STATE_COMPLETED;
			$statusDeferred = \CTasks::STATE_DEFERRED;

			$sql = "
				SELECT 
					COUNT(t.ID) as COUNT,
					t.GROUP_ID
				FROM 
					b_tasks as t
					INNER JOIN b_tasks_member as tm 
						ON tm.TASK_ID = t.ID AND tm.TYPE = 'U'
				WHERE 
					t.DEADLINE < '{$expiredTime}'
					AND tm.USER_ID = {$this->userId}
					AND t.ZOMBIE = 'N'
					".($this->groupId > 0 ? " AND t.GROUP_ID = {$this->groupId}" : "")."
					AND (
						t.STATUS != {$statusSupposedlyCompleted}
						AND t.STATUS != {$statusCompleted}
						AND	t.STATUS != {$statusDeferred}
					)
				GROUP BY 
					t.GROUP_ID
			";

			$this->changeCounter(
				CounterName::AUDITOR_EXPIRED,
				Application::getConnection()->query($sql)->fetchAll()
			);
		}
	}

	private function calcAccomplicesExpiredSoon($reCache = false)
	{
		static $count = null;

		if ($count == null || $reCache)
		{
			$expiredTime = $this->getExpiredTime();
			$expiredSoonTime = $this->getExpiredSoonTime();

			$statusSupposedlyCompleted = \CTasks::STATE_SUPPOSEDLY_COMPLETED;
			$statusCompleted = \CTasks::STATE_COMPLETED;
			$statusDeferred = \CTasks::STATE_DEFERRED;

			$sql = "
				SELECT 
					COUNT(t.ID) as COUNT,
					t.GROUP_ID
				FROM 
					b_tasks as t
					INNER JOIN b_tasks_member as tm
						ON tm.TASK_ID = t.ID AND tm.TYPE = 'A'
				WHERE
					DEADLINE < '{$expiredSoonTime}'
					AND DEADLINE >= '{$expiredTime}'
					
					AND tm.USER_ID = {$this->userId}
					AND t.ZOMBIE = 'N'
					".($this->groupId > 0 ? " AND t.GROUP_ID = {$this->groupId}" : "")."
					AND (
						t.STATUS != {$statusSupposedlyCompleted}
						AND t.STATUS != {$statusCompleted}
						AND	t.STATUS != {$statusDeferred}
					)
				GROUP BY 
					t.GROUP_ID
			";

			$this->changeCounter(
				CounterName::ACCOMPLICES_EXPIRED_SOON,
				Application::getConnection()->query($sql)->fetchAll()
			);
		}
	}

	private function calcAccomplicesExpired($reCache = false)
	{
		static $count = null;

		if ($count == null || $reCache)
		{
			$expiredTime = $this->getExpiredTime();

			$statusSupposedlyCompleted = \CTasks::STATE_SUPPOSEDLY_COMPLETED;
			$statusCompleted = \CTasks::STATE_COMPLETED;
			$statusDeferred = \CTasks::STATE_DEFERRED;

			$sql = "
				SELECT 
					COUNT(t.ID) as COUNT,
					t.GROUP_ID
				FROM 
					b_tasks as t
					INNER JOIN b_tasks_member as tm
						ON tm.TASK_ID = t.ID AND tm.TYPE = 'A'
				WHERE
					t.DEADLINE < '{$expiredTime}'
					AND tm.USER_ID = {$this->userId}
					AND t.ZOMBIE = 'N'
					".($this->groupId > 0 ? " AND t.GROUP_ID = {$this->groupId}" : "")."
					AND (
						t.STATUS != {$statusSupposedlyCompleted}
						AND t.STATUS != {$statusCompleted}
						AND	t.STATUS != {$statusDeferred}
					)
				GROUP BY 
					t.GROUP_ID
			";

			$this->changeCounter(
				CounterName::ACCOMPLICES_EXPIRED,
				Application::getConnection()->query($sql)->fetchAll()
			);
		}
	}

	private function calcAccomplicesNotViewed($reCache = false)
	{
		static $count = null;

		if ($count == null || $reCache)
		{
			$statusSupposedlyCompleted = \CTasks::STATE_SUPPOSEDLY_COMPLETED;
			$statusCompleted = \CTasks::STATE_COMPLETED;
			$statusDeferred = \CTasks::STATE_DEFERRED;

			$sql = "
				SELECT 
					COUNT(t.ID) as COUNT,
					t.GROUP_ID
				FROM 
					b_tasks as t
					INNER JOIN b_tasks_member as tm
						ON tm.TASK_ID = t.ID AND tm.TYPE = 'A'
					LEFT JOIN b_tasks_viewed as tv
						ON tv.TASK_ID = t.ID AND tv.USER_ID = {$this->userId}
				WHERE
					(tv.TASK_ID IS NULL OR tv.TASK_ID = 0)
					AND tm.USER_ID = {$this->userId}
					AND t.ZOMBIE = 'N'
					".($this->groupId > 0 ? " AND t.GROUP_ID = {$this->groupId}" : "")."
					AND (
						t.STATUS != {$statusSupposedlyCompleted}
						AND t.STATUS != {$statusCompleted}
						AND	t.STATUS != {$statusDeferred}
					)
				GROUP BY 
					t.GROUP_ID
			";

			$this->changeCounter(
				CounterName::ACCOMPLICES_NOT_VIEWED,
				Application::getConnection()->query($sql)->fetchAll()
			);
		}
	}

	private function calcOriginatorExpired($reCache = false)
	{
		static $count = null;

		if ($count == null || $reCache)
		{
			$expiredTime = $this->getExpiredTime();

			$statusSupposedlyCompleted = \CTasks::STATE_SUPPOSEDLY_COMPLETED;
			$statusCompleted = \CTasks::STATE_COMPLETED;
			$statusDeferred = \CTasks::STATE_DEFERRED;

			$sql = "
				SELECT 
					COUNT(ID) as COUNT,
					GROUP_ID
				FROM 
					b_tasks 
				WHERE 
					DEADLINE < '{$expiredTime}'
					AND CREATED_BY = {$this->userId}
					AND RESPONSIBLE_ID != CREATED_BY
					AND ZOMBIE = 'N'
					".($this->groupId > 0 ? " AND GROUP_ID = {$this->groupId}" : "")."
					AND (
						STATUS != {$statusSupposedlyCompleted}
						AND STATUS != {$statusCompleted}
						AND	STATUS != {$statusDeferred}
					)
				GROUP BY 
					GROUP_ID
			";

			$this->changeCounter(
				CounterName::ORIGINATOR_EXPIRED,
				Application::getConnection()->query($sql)->fetchAll()
			);
		}
	}

	private function calcOriginatorWaitControl($reCache = false)
	{
		static $count = null;

		if ($count == null || $reCache)
		{
			$statusSupposedlyCompleted = \CTasks::STATE_SUPPOSEDLY_COMPLETED;

			$sql = "
				SELECT 
					COUNT(ID) as COUNT,
					GROUP_ID
				FROM 
					b_tasks 
				WHERE 
					CREATED_BY = {$this->userId}
					AND RESPONSIBLE_ID != CREATED_BY
					AND ZOMBIE = 'N'
					".($this->groupId > 0 ? " AND GROUP_ID = {$this->groupId}" : "")."
					AND STATUS = {$statusSupposedlyCompleted}
				GROUP BY 
					GROUP_ID
			";

			$this->changeCounter(
				CounterName::ORIGINATOR_WAIT_CONTROL,
				Application::getConnection()->query($sql)->fetchAll()
			);
		}
	}

	private function calcOriginatorWithoutDeadline($reCache = false)
	{
		static $count = null;

		if ($count == null || $reCache)
		{
			$statusSupposedlyCompleted = \CTasks::STATE_SUPPOSEDLY_COMPLETED;
			$statusCompleted = \CTasks::STATE_COMPLETED;
			$statusDeferred = \CTasks::STATE_DEFERRED;

			$sql = "
				SELECT 
					COUNT(ID) as COUNT,
					GROUP_ID
				FROM 
					b_tasks 
				WHERE 
					(DEADLINE IS NULL OR DEADLINE = '')
					AND CREATED_BY = {$this->userId}
					AND RESPONSIBLE_ID != CREATED_BY
					AND ZOMBIE = 'N'
					".($this->groupId > 0 ? " AND GROUP_ID = {$this->groupId}" : "")."
					AND (
						STATUS != {$statusSupposedlyCompleted}
						AND STATUS != {$statusCompleted}
						AND	STATUS != {$statusDeferred}
					)
				GROUP BY 
					GROUP_ID
			";

			$this->changeCounter(
				CounterName::ORIGINATOR_WITHOUT_DEADLINE,
				Application::getConnection()->query($sql)->fetchAll()
			);
		}
	}

	protected function processRecalculate($plan)
	{
		foreach ($plan->export() as $counterName)
		{
			$method = 'calc'.implode('', array_map('ucfirst', explode('_', $counterName)));
			if (method_exists($this, $method))
			{
				$this->{$method}(true);
			}
		}

		$this->saveCounters();
	}

	public function saveCounters()
	{
		if (!$this->needSaveCounters)
		{
			return;
		}

		$groupFields = array(); //accumulate counters by groups
		foreach ($this->counters as $counterName => $groupsValue)
		{
			foreach ($groupsValue as $groupId => $value)
			{
				$groupFields[$groupId][$counterName] = $value;
			}
		}

		unset($groupFields['allCounters']);
		foreach ($groupFields as $groupId => $counters)
		{
			$fields = array_map(
				function($counterName) use ($counters) {
					return strtoupper($counterName).' = '.(int)$counters[$counterName];
				},
				array_keys($this->counters)
			);

			$currentDate = "'".date('Y-m-d')."'";

			$sql = "
				SELECT 
					ID 
				FROM 
					b_tasks_counters 
				WHERE
					DATE = {$currentDate} AND
					USER_ID = {$this->userId} AND
					GROUP_ID = {$groupId}
			";

			$res = Application::getConnection()->query($sql)->fetch();
			$counterId = $res['ID'];

			if (!$counterId)
			{
				$fields[] = "DATE = ".$currentDate;
				$fields[] = "USER_ID = ".$this->userId;
				$fields[] = "GROUP_ID = ".$groupId;

				$sql = 'INSERT INTO b_tasks_counters SET '.join(',', $fields);
			}
			else
			{
				$sql = 'UPDATE b_tasks_counters SET '.join(',', $fields)." WHERE ID = {$counterId}";
			}

			Application::getConnection()->query($sql);

			\CUserCounter::Set($this->userId, $this->getPrefix().CounterName::MY, $this->getCounter(CounterName::MY), '**', '', false);
			\CUserCounter::Set($this->userId, $this->getPrefix().CounterName::ACCOMPLICES, $this->getCounter(CounterName::ACCOMPLICES), '**', '', false);
			\CUserCounter::Set($this->userId, $this->getPrefix().CounterName::AUDITOR, $this->getCounter(CounterName::AUDITOR), '**', '', false);
			\CUserCounter::Set($this->userId, $this->getPrefix().CounterName::ORIGINATOR, $this->getCounter(CounterName::ORIGINATOR), '**', '', false);
			\CUserCounter::Set($this->userId, $this->getPrefix().CounterName::TOTAL, $this->getCounter(CounterName::TOTAL), '**', '', false);
		}
	}
}