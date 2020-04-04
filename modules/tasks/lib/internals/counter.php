<?php

namespace Bitrix\Tasks\Internals;

use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Db\SqlQueryException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;

use Bitrix\Tasks\Internals\Counter\Agent;
use Bitrix\Tasks\Internals\Counter\EffectiveTable;
use Bitrix\Tasks\Item\Task;
use Bitrix\Tasks\Util\Collection;
use Bitrix\Tasks\Util\Type\DateTime;
use Bitrix\Tasks\Util\User;

use \Exception;
use \CTasks;

class Counter
{
	const DEFAULT_DEADLINE_LIMIT = 86400;
	private static $instance = null;
	private static $prefix = 'tasks_';
	private $userId;
	private $groupId;
	private $counters = array();

	private function __construct($userId, $groupId = 0)
	{
		$this->userId = (int)$userId;
		$this->groupId = (int)$groupId;

		$this->counters = $this->loadCounters();
		if (!$this->counters)
		{
			$this->recountAllCounters();
			$this->counters = $this->loadCounters();
		}
	}

	private function loadCounters()
	{
		$select = array();
		foreach ($this->getMap() as $key)
		{
			$select[] = "SUM({$key}) AS {$key}";
		}

		$sql = "
			SELECT 
				GROUP_ID, ".join(',', $select)."
			FROM 
				b_tasks_counters 
			WHERE
				USER_ID = {$this->userId}  
				".($this->groupId > 0 ? "AND GROUP_ID = {$this->groupId}" : "")." 
			GROUP BY 
				GROUP_ID";

		$res = Application::getConnection()->query($sql);

		$list = array();
		while ($item = $res->fetch())
		{
			$list[$item['GROUP_ID']] = $item;
		}

		return $list;
	}

	private function getMap()
	{
		return array(
			'OPENED',
			'CLOSED',
			'MY_EXPIRED',
			'MY_EXPIRED_SOON',
			'MY_NOT_VIEWED',
			'MY_WITHOUT_DEADLINE',
			'ORIGINATOR_WITHOUT_DEADLINE',
			'ORIGINATOR_EXPIRED',
			'ORIGINATOR_WAIT_CTRL',
			'AUDITOR_EXPIRED',
			'ACCOMPLICES_EXPIRED',
			'ACCOMPLICES_EXPIRED_SOON',
			'ACCOMPLICES_NOT_VIEWED'
		);
	}

	private function recountAllCounters()
	{
		if (!$this->userId)
		{
			return;
		}

		$reflect = new \ReflectionClass('\Bitrix\Tasks\Internals\Counter\Name');
		$collect = new Collection();
		foreach ($reflect->getConstants() as $counterName)
		{
			$collect->push($counterName);
		}

		$this->processRecalculate($collect);
		$this->saveCounters();
	}

	public function processRecalculate($plan)
	{
		/** @var Collection $plan */
		$plan = $plan->export();
		$plan = array_unique($plan);

		foreach ($plan as $counterName)
		{
			$method = 'calc'.implode('', array_map('ucfirst', explode('_', $counterName)));
			if (method_exists($this, $method))
			{
				$this->{$method}(true);
			}
		}

		$this->saveCounters();
	}

	private function saveCounters()
	{
		if (!$this->userId)
		{
			return;
		}


		foreach ($this->counters as $groupId => $counters)
		{
			$sql = Application::getConnection()->getSqlHelper()->prepareMerge(
				'b_tasks_counters',
				array('USER_ID', 'GROUP_ID'),
				array_merge(
					$counters,
					array(
						'USER_ID' => $this->userId,
						'GROUP_ID' => $groupId
					)
				),
				$counters
			);

			Application::getConnection()->queryExecute(current($sql));
		}

		\CUserCounter::Set(
			$this->userId,
			self::getPrefix().Counter\Name::MY,
			$this->get(Counter\Name::MY),
			'**',
			'',
			false
		);
		\CUserCounter::Set(
			$this->userId,
			self::getPrefix().Counter\Name::ACCOMPLICES,
			$this->get(Counter\Name::ACCOMPLICES),
			'**',
			'',
			false
		);
		\CUserCounter::Set(
			$this->userId,
			self::getPrefix().Counter\Name::AUDITOR,
			$this->get(Counter\Name::AUDITOR),
			'**',
			'',
			false
		);
		\CUserCounter::Set(
			$this->userId,
			self::getPrefix().Counter\Name::ORIGINATOR,
			$this->get(Counter\Name::ORIGINATOR),
			'**',
			'',
			false
		);

		\CUserCounter::Set(
			$this->userId,
			self::getPrefix().Counter\Name::TOTAL,
			$this->get(Counter\Name::TOTAL),
			'**',
			'',
			false
		);
	}

	public static function getPrefix()
	{
		return self::$prefix;
	}

	public function get($name)
	{
		switch ($name)
		{
			default:

				if($this->groupId > 0
				   && !in_array($name, [
				   	Counter\Name::MY_EXPIRED,
				   	Counter\Name::MY_WITHOUT_DEADLINE,
				   	Counter\Name::ORIGINATOR_WITHOUT_DEADLINE,
					Counter\Name::MY_EXPIRED_SOON,
					Counter\Name::ORIGINATOR_EXPIRED,
					Counter\Name::ACCOMPLICES_EXPIRED,
					Counter\Name::ACCOMPLICES_EXPIRED_SOON,
					Counter\Name::AUDITOR_EXPIRED,
					Counter\Name::ORIGINATOR_WAIT_CONTROL
					]))
				{
					return 0;
				}

				return $this->getInternal($name);
				break;
			case Counter\Name::MY:
				return $this->get(Counter\Name::MY_EXPIRED) +
					   $this->get(Counter\Name::MY_EXPIRED_SOON) +
					   $this->get(Counter\Name::MY_WITHOUT_DEADLINE) +
					   $this->get(Counter\Name::MY_NOT_VIEWED);
				break;
			case Counter\Name::ORIGINATOR:
				return $this->get(Counter\Name::ORIGINATOR_EXPIRED) +
					   $this->get(Counter\Name::ORIGINATOR_WITHOUT_DEADLINE) +
					   $this->get(Counter\Name::ORIGINATOR_WAIT_CONTROL);
				break;
			case Counter\Name::ACCOMPLICES:
				return $this->get(Counter\Name::ACCOMPLICES_EXPIRED) +
					   $this->get(Counter\Name::ACCOMPLICES_EXPIRED_SOON) +
					   $this->get(Counter\Name::ACCOMPLICES_NOT_VIEWED);
				break;
			case Counter\Name::AUDITOR:
				return $this->get(Counter\Name::AUDITOR_EXPIRED);
				break;
			case Counter\Name::EFFECTIVE:
				return $this->getKpi();
				break;
			case Counter\Name::TOTAL:
				return $this->get(Counter\Name::MY) +
					   $this->get(Counter\Name::ACCOMPLICES) +
					   $this->get(Counter\Name::AUDITOR) +
					   $this->get(Counter\Name::ORIGINATOR);
				break;
		}
	}

	private function getKpi()
	{
		$efficiency = Effective::getEfficiencyFromUserCounter($this->userId);

		if (!$efficiency)
		{
			$efficiency = Effective::getAverageEfficiency(null, null, $this->userId);

			if ($efficiency)
			{
				Effective::setEfficiencyToUserCounter($this->userId, $efficiency);
			}
		}

		return $efficiency;

		/*$filterOptions = new Filter\Options(
			Effective::getFilterId(),
			Effective::getPresetList()
		);

		$defId = $filterOptions->getDefaultFilterId();
		$settings = $filterOptions->getFilterSettings($defId);
		$filtersRaw = Filter\Options::fetchFieldValuesFromFilterSettings($settings);

		$dateFrom = DateTime::createFrom($filtersRaw['DATETIME_from']);
		$dateTo = DateTime::createFrom($filtersRaw['DATETIME_to']);

		$groupId = array_key_exists('GROUP_ID', $filtersRaw) && $filtersRaw['GROUP_ID']>0 ? $filtersRaw['GROUP_ID'] : 0;

		$counters = \Bitrix\Tasks\Internals\Effective::getCountersByRange($dateFrom, $dateTo, $this->userId, $groupId);
		if (($counters['CLOSED'] + $counters['OPENED']) == 0)
		{
			$kpi = 100;
		}
		else
		{
			$kpi = round(100 - ($counters['VIOLATIONS'] / ($counters['OPENED'] + $counters['CLOSED'])) * 100);
		}

		return $kpi < 0 ? 0 : $kpi;*/
	}

	private function getInternal($name)
	{
		$name = strtoupper($name);
		if ($this->groupId > 0)
		{
			if (!array_key_exists($this->groupId, $this->counters) ||
				!array_key_exists($name, $this->counters[$this->groupId]))
			{
				return 0;
			}

			return $this->counters[$this->groupId][$name];
		}
		else
		{
			$counter = 0;
			foreach ($this->counters as $groupId => $counters)
			{
				$counter += $counters[$name];
			}

			return $counter;
		}
	}

	public static function onBeforeTaskAdd()
	{
	}

	/**
	 * Recalculates counters and efficiency after task adding
	 *
	 * @param array $fields
	 */
	public static function onAfterTaskAdd(array $fields)
	{
		$responsible = new Collection;
		$originator = new Collection;
		$accomplice = new Collection;
		$auditor = new Collection;

		$responsible->push(Counter\Name::OPENED);
		$responsible->push(Counter\Name::MY_EXPIRED);
		$responsible->push(Counter\Name::MY_EXPIRED_SOON);
		$responsible->push(Counter\Name::MY_WITHOUT_DEADLINE);

		$originator->push(Counter\Name::OPENED);
		$originator->push(Counter\Name::ORIGINATOR_WAIT_CONTROL);
		$originator->push(Counter\Name::ORIGINATOR_EXPIRED);
		$originator->push(Counter\Name::ORIGINATOR_WITHOUT_DEADLINE);

		if ($fields['RESPONSIBLE_ID'] != $fields['CREATED_BY'])
		{
			$responsible->push(Counter\Name::MY_NOT_VIEWED);
			$originator->push(Counter\Name::MY_NOT_VIEWED);
		}

		$accomplice->push(Counter\Name::ACCOMPLICES_NOT_VIEWED);
		$accomplice->push(Counter\Name::ACCOMPLICES_EXPIRED);
		$accomplice->push(Counter\Name::ACCOMPLICES_EXPIRED_SOON);

		$auditor->push(Counter\Name::AUDITOR_EXPIRED);

		$countersMap = [
			'RESPONSIBLE_ID' => $responsible,
			'CREATED_BY' => $originator,
			'ACCOMPLICES' => $accomplice,
			'AUDITORS' => $auditor
		];
		$mode = 'ADD';

		$efficiencyMap = [];
		foreach ($fields['ACCOMPLICES'] as $userId)
		{
			$efficiencyMap[$userId] = 'A';
		}
		$efficiencyMap[$fields['RESPONSIBLE_ID']] = 'R';

		static::recalculateCounters($fields, $countersMap, $mode);
		static::recalculateEfficiency($fields, $efficiencyMap, Task::makeInstanceFromSource($fields, 1), $mode);

		if ($fields['DEADLINE'] && DateTime::createFrom($fields['DEADLINE']))
		{
			Agent::add($fields['ID'], DateTime::createFrom($fields['DEADLINE']));
		}
	}

	/**
	 * Recalculates counters by user roles
	 *
	 * @param $fields
	 * @param $map
	 * @param $mode
	 */
	private static function recalculateCounters($fields, $map, $mode)
	{
		/** @var  Collection $plan */
		foreach ($map as $key => $plan)
		{
			if (in_array($key, ['CREATED_BY', 'RESPONSIBLE_ID']))
			{
				$counter = static::getInstance($fields[$key]);
				if ($counter instanceof static)
				{
					$counter->processRecalculate($plan);
				}
			}
			else if (in_array($key, ['ACCOMPLICES', 'AUDITORS']))
			{
				if ($key == 'ACCOMPLICES')
				{
					$plan->push(Counter\Name::OPENED);

					if ($mode == 'DELETE')
					{
						$plan->push(Counter\Name::CLOSED);
					}
				}

				foreach ($fields[$key] as $userId)
				{
					$counter = static::getInstance($userId);
					if ($counter instanceof static)
					{
						$counter->processRecalculate($plan);
					}
				}
			}
		}
	}

	/**
	 * Recalculates efficiency for responsible user and accomplices
	 *
	 * @param $fields
	 * @param $map
	 * @param Task $taskInstance
	 * @param $mode
	 */
	private static function recalculateEfficiency($fields, $map, Task $taskInstance, $mode)
	{
		foreach ($map as $userId => $userType)
		{
			Effective::modify($userId, $userType, $taskInstance, $fields['GROUP_ID'], false);
		}

		if ($mode == 'DELETE')
		{
			Effective::repair($fields['ID']);
		}
	}

	public static function deadlineIsExpired($deadline)
	{
		if (!$deadline)
		{
			return false;
		}

		$deadline = DateTime::createFromUserTime($deadline);
		if (!$deadline)
		{
			return false;
		}

		$expired = self::getExpiredTime();

		return $deadline->checkLT($expired);
	}

	/**
	 * @return DateTime
	 */
	public static function getExpiredTime()
	{
		$expired = new DateTime();

		return $expired;
	}

	public static function deadlineIsExpiredSoon($deadline)
	{
		if (!$deadline)
		{
			return false;
		}

		$deadline = DateTime::createFrom($deadline);

		$expired = self::getExpiredTime();
		$expiredSoon = self::getExpiredSoonTime();

		return $deadline->checkGT($expired) && $deadline->checkLT($expiredSoon);
	}

	/**
	 * @return DateTime
	 */
	public static function getExpiredSoonTime()
	{
		$expiredSoon = DateTime::createFromTimestamp(time() + Counter::getDeadlineTimeLimit());

		return $expiredSoon;
	}

	public static function getDeadlineTimeLimit($reCache = false)
	{
		static $time;

		if (!$time || $reCache)
		{
			$time = \CUserOptions::GetOption('tasks', 'deadlineTimeLimit', self::DEFAULT_DEADLINE_LIMIT);
		}

		return $time;
	}

	/**
	 * @param $userId
	 * @param int $groupId
	 * @param bool $recache
	 *
	 * @return self
	 */
	public static function getInstance($userId, $groupId = 0, $recache = false)
	{
		if ($recache || !self::$instance ||
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

	/**
	 * @param $fields
	 * @param $newFields
	 * @param array $params
	 * [
	 * FORCE_RECOUNT_COUNTER = Y|N
	 * ]
	 */
	public static function onAfterTaskUpdate($fields, $newFields, array $params = array())
	{
		if(self::fieldChanged('DEADLINE', $fields, $newFields))
		{
			if (!$newFields['DEADLINE'] || !DateTime::createFrom($newFields['DEADLINE']))
			{
				Agent::remove($fields['ID']);
			}
			else
			{
				Agent::add($fields['ID'], DateTime::createFrom($newFields['DEADLINE']));
			}
		}

		if(
			self::fieldChanged('STATUS', $fields, $newFields) ||
			self::fieldChanged('DEADLINE', $fields, $newFields) ||
			self::fieldChanged('GROUP_ID', $fields, $newFields) ||
			self::fieldChanged('RESPONSIBLE_ID', $fields, $newFields) ||
			self::fieldChanged('CREATED_BY', $fields, $newFields) ||
			self::fieldChanged('AUDITORS', $fields, $newFields) ||
			self::fieldChanged('ACCOMPLICES', $fields, $newFields) ||
			(array_key_exists('FORCE_RECOUNT_COUNTER', $params) && $params['FORCE_RECOUNT_COUNTER'] == 'Y')
		)
		{
			$task = Task::getInstance($fields['ID'], 1);

			$controller = $task->getAccessController()->spawn();
			$controller->disable();
			$task->setAccessController($controller);

			self::onAfterUpdateTaskInternal($fields, $task);
			self::onAfterUpdateTaskInternal($newFields, $task);

			self::updateEffective($fields, $newFields, $task);
		}

	}

	/**
	 * @param $fields
	 * @param $newFields
	 * @param Task $task
	 * @throws ArgumentException
	 * @throws SqlQueryException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws Exception
	 */
	private static function updateEffective($fields, $newFields, Task $task)
	{
		$responsibleChanged = self::fieldChanged('RESPONSIBLE_ID', $fields, $newFields);
		$accomplicesChanged = self::fieldChanged('ACCOMPLICES', $fields, $newFields);
		$deadlineChanged = self::fieldChanged('DEADLINE', $fields, $newFields);
		$groupChanged = self::fieldChanged('GROUP_ID', $fields, $newFields);
		$statusChanged = self::fieldChanged('STATUS', $fields, $newFields);

		$taskId = $fields['ID'];

		$oldStatus = (int)($fields['STATUS'] > 0? $fields['STATUS'] : $fields['REAL_STATUS']);
		$newStatus = (int)($newFields['STATUS'] > 0? $newFields['STATUS'] : $newFields['REAL_STATUS']);

		$oldGroupId = $fields['GROUP_ID'];
		$newGroupId = $newFields['GROUP_ID'];
		$groupId = ($groupChanged? $newGroupId : $oldGroupId);

		$oldResponsibleId = $fields['RESPONSIBLE_ID'];
		$newResponsibleId = $newFields['RESPONSIBLE_ID'];

		$oldAccomplices = (array)$fields['ACCOMPLICES'];
		$newAccomplices = (array)$newFields['ACCOMPLICES'];
		$accomplicesIn = array_diff($newAccomplices, $oldAccomplices);
		$accomplicesOut = array_diff($oldAccomplices, $newAccomplices);
		$allAccomplices = array_unique(array_merge($oldAccomplices, $newAccomplices));

		$deadline = ($newFields['DEADLINE']? $newFields['DEADLINE'] : $fields['DEADLINE']);
		$isViolation = self::deadlineIsExpired($deadline);

		$responsibleModified = false;
		$accomplicesModified = false;

		$canProceed = false;
		$statesCompleted = [CTasks::STATE_DEFERRED, CTasks::STATE_SUPPOSEDLY_COMPLETED, CTasks::STATE_COMPLETED];
		$statesInProgress = [CTasks::STATE_NEW, Ctasks::STATE_PENDING, CTasks::STATE_IN_PROGRESS];

		// TASK DEFERRED OR COMPLETED
		if ($statusChanged && in_array($newStatus, $statesCompleted))
		{
			Effective::repair($taskId);
			Effective::modify($oldResponsibleId, 'R', $task, $oldGroupId, false);

			foreach ($oldAccomplices as $userId)
			{
				if ($userId != $oldResponsibleId)
				{
					Effective::modify($userId, 'A', $task, $oldGroupId, false);
				}
			}

			if ($responsibleChanged)
			{
				Effective::modify($newResponsibleId, 'R', $task, $groupId, false);
			}

			if ($accomplicesChanged)
			{
				foreach ($accomplicesIn as $userId)
				{
					if ($userId != $oldResponsibleId && $userId != $newResponsibleId)
					{
						Effective::modify($userId, 'A', $task, $groupId, false);
					}
				}
			}

			return;
		}

		// TASK RESTARTED
		if ($statusChanged && in_array($oldStatus, $statesCompleted) && in_array($newStatus, $statesInProgress))
		{
			if (!$responsibleChanged)
			{
				Effective::modify($oldResponsibleId, 'R', $task, $groupId, $isViolation);

				$responsibleModified = true;
			}

			if (!$accomplicesChanged)
			{
				foreach ($oldAccomplices as $userId)
				{
					if ($userId != $oldResponsibleId && $userId != $newResponsibleId)
					{
						Effective::modify($userId, 'A', $task, $groupId, $isViolation);
					}
				}

				$accomplicesModified = true;
			}

			$canProceed = true;
		}

		if (!$canProceed && in_array($oldStatus, $statesCompleted))
		{
			return;
		}

		// RESPONSIBLE CHANGED
		if ($responsibleChanged)
		{
			if (($activeViolations = Effective::checkActiveViolations($taskId, $oldResponsibleId)) &&
				in_array($oldResponsibleId, $newAccomplices))
			{
				EffectiveTable::update($activeViolations[0]['ID'], ['USER_TYPE' => 'A', 'GROUP_ID' => $groupId]);
			}
			else
			{
				Effective::repair($taskId, $oldResponsibleId, 'R');
			}

			Effective::modify($oldResponsibleId, 'R', $task, $oldGroupId, false);

			if ($activeViolations = Effective::checkActiveViolations($taskId, $newResponsibleId))
			{
				EffectiveTable::update($activeViolations[0]['ID'], ['USER_TYPE' => 'R', 'GROUP_ID' => $groupId]);
				Effective::modify($newResponsibleId, 'R', $task, $groupId, false);
			}
			else
			{
				Effective::modify($newResponsibleId, 'R', $task, $groupId, $isViolation);
			}

			$responsibleModified = true;
		}

		// ACCOMPLICES CHANGED
		if ($accomplicesChanged)
		{
			foreach ($accomplicesOut as $userId)
			{
				if ($userId != $oldResponsibleId && $userId != $newResponsibleId)
				{
					Effective::repair($taskId, $userId, 'A');
					Effective::modify($userId, 'A', $task, $oldGroupId, false);
				}
			}

			foreach ($accomplicesIn as $userId)
			{
				if ($userId != $oldResponsibleId && $userId != $newResponsibleId)
				{
					Effective::modify($userId, 'A', $task, $groupId, $isViolation);
				}
			}
		}

		// DEADLINE CHANGED
		if ($deadlineChanged && !self::deadlineIsExpired($deadline))
		{
			Effective::repair($taskId);

			if (!$responsibleModified)
			{
				Effective::modify($oldResponsibleId, 'R', $task, $groupId, false);

				$responsibleModified = true;
			}

			if (!$accomplicesModified)
			{
				$accomplices = ($accomplicesChanged? array_diff($newAccomplices, $accomplicesIn) : $allAccomplices);

				foreach ($accomplices as $userId)
				{
					if ($userId != $oldResponsibleId && $userId != $newResponsibleId)
					{
						Effective::modify($userId, 'A', $task, $groupId, false);
					}
				}

				$accomplicesModified = true;
			}
		}

		// GROUP CHANGED
		if ($groupChanged)
		{
			if ($activeViolations = Effective::checkActiveViolations($taskId))
			{
				foreach ($activeViolations as $violation)
				{
					EffectiveTable::update($violation['ID'], ['GROUP_ID' => $newGroupId]);
				}

				if (!$responsibleModified)
				{
					Effective::modify($oldResponsibleId, 'R', $task, $newGroupId, false);
				}

				if (!$accomplicesModified)
				{
					$accomplices = ($accomplicesChanged? array_diff($newAccomplices, $accomplicesIn) : $allAccomplices);

					foreach ($accomplices as $userId)
					{
						if ($userId != $oldResponsibleId && $userId != $newResponsibleId)
						{
							Effective::modify($userId, 'A', $task, $newGroupId, false);
						}
					}
				}
			}
			else
			{
				if (!$responsibleModified)
				{
					Effective::modify($oldResponsibleId, 'R', $task, $newGroupId, $isViolation);
				}

				if (!$accomplicesModified)
				{
					$accomplices = ($accomplicesChanged? array_diff($newAccomplices, $accomplicesIn) : $allAccomplices);

					foreach ($accomplices as $userId)
					{
						if ($userId != $oldResponsibleId && $userId != $newResponsibleId)
						{
							Effective::modify($userId, 'A', $task, $newGroupId, $isViolation);
						}
					}
				}
			}
		}
	}

	private static function fieldChanged($key, $fields, $newFields)
	{
		return array_key_exists($key, $newFields) && $newFields[$key] != $fields[$key];
	}

	private static function onAfterUpdateTaskInternal($fields, Task $task)
	{
		$responsible = new Collection;
		$originator = new Collection;
		$auditor = new Collection;
		$accomplice = new Collection;

		if (!array_key_exists('CREATED_BY', $fields) ||
			!array_key_exists('RESPONSIBLE_ID', $fields) ||
			!array_key_exists('GROUP_ID', $fields))
		{
			if (!array_key_exists('RESPONSIBLE_ID', $fields))
			{
				$fields['RESPONSIBLE_ID'] = $task['RESPONSIBLE_ID'];
			}

			if (!array_key_exists('CREATED_BY', $fields))
			{
				$fields['CREATED_BY'] = $task['CREATED_BY'];
			}

			if (!array_key_exists('GROUP_ID', $fields))
			{
				$fields['GROUP_ID'] = $task['GROUP_ID'];
			}
		}

		$originator->push(Counter\Name::OPENED);
		$originator->push(Counter\Name::CLOSED);

		$originator->push(Counter\Name::ORIGINATOR_WAIT_CONTROL);

		$responsible->push(Counter\Name::MY_NOT_VIEWED);
		$originator->push(Counter\Name::MY_NOT_VIEWED);

		$responsible->push(Counter\Name::OPENED);
		$responsible->push(Counter\Name::CLOSED);

		//		if ($fields['RESPONSIBLE_ID'] != $fields['CREATED_BY'])
		//		{
			$responsible->push(Counter\Name::MY_NOT_VIEWED);
			$originator->push(Counter\Name::MY_NOT_VIEWED);

		//			if ($fields['DEADLINE'])
		//			{
				$originator->push(Counter\Name::ORIGINATOR_EXPIRED);
				$responsible->push(Counter\Name::MY_EXPIRED_SOON);
				$responsible->push(Counter\Name::MY_EXPIRED);
		//			}
		//			else
		//			{
				$originator->push(Counter\Name::ORIGINATOR_WITHOUT_DEADLINE);
				$responsible->push(Counter\Name::MY_WITHOUT_DEADLINE);

				$originator->push(Counter\Name::ORIGINATOR_EXPIRED);
				$responsible->push(Counter\Name::MY_EXPIRED_SOON);
				$responsible->push(Counter\Name::MY_EXPIRED);
		//			}
		//		}
		//		else
		//		{
		//			if ($fields['DEADLINE'])
		//			{
		//				$responsible->push(Counter\Name::MY_EXPIRED_SOON);
		//				$responsible->push(Counter\Name::MY_EXPIRED);
		//			}
		//		}

		if (!empty($fields['AUDITORS']))
		{
			if ($fields['DEADLINE'])
			{
				$auditor->push(Counter\Name::AUDITOR_EXPIRED);
			}
		}

		if (!empty($fields['ACCOMPLICES']))
		{
			$accomplice->push(Counter\Name::ACCOMPLICES_NOT_VIEWED);

			if ($fields['DEADLINE'])
			{
				$accomplice->push(Counter\Name::ACCOMPLICES_EXPIRED);
				$accomplice->push(Counter\Name::ACCOMPLICES_EXPIRED_SOON);
			}
		}

		if(array_key_exists('CREATED_BY', $fields))
		{
			$originator->push(Counter\Name::MY_EXPIRED);
			$originator->push(Counter\Name::MY_EXPIRED_SOON);
			$originator->push(Counter\Name::MY_NOT_VIEWED);
			$originator->push(Counter\Name::MY_WITHOUT_DEADLINE);
		}

		// PROCESS RECALCULATE
		if ($responsible->count() > 0)
		{
			if ($fields['RESPONSIBLE_ID'])
			{
				$counter = self::getInstance($fields['RESPONSIBLE_ID']);
				$counter->processRecalculate($responsible);
			}
		}

		if ($originator->count() > 0)
		{
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
				$accomplice->push(Counter\Name::OPENED);
				$accomplice->push(Counter\Name::CLOSED);

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
		$accomplice = new Collection;
		$auditor = new Collection;

		$responsible->push(Counter\Name::OPENED);
		$responsible->push(Counter\Name::CLOSED);
		$responsible->push(Counter\Name::MY_EXPIRED);
		$responsible->push(Counter\Name::MY_EXPIRED_SOON);
		$responsible->push(Counter\Name::MY_WITHOUT_DEADLINE);

		$originator->push(Counter\Name::OPENED);
		$originator->push(Counter\Name::CLOSED);
		$originator->push(Counter\Name::ORIGINATOR_WAIT_CONTROL);
		$originator->push(Counter\Name::ORIGINATOR_EXPIRED);
		$originator->push(Counter\Name::ORIGINATOR_WITHOUT_DEADLINE);

		if ($fields['RESPONSIBLE_ID'] != $fields['CREATED_BY'])
		{
			$responsible->push(Counter\Name::MY_NOT_VIEWED);
			$originator->push(Counter\Name::MY_NOT_VIEWED);
		}

		$accomplice->push(Counter\Name::ACCOMPLICES_NOT_VIEWED);
		$accomplice->push(Counter\Name::ACCOMPLICES_EXPIRED);
		$accomplice->push(Counter\Name::ACCOMPLICES_EXPIRED_SOON);

		$auditor->push(Counter\Name::AUDITOR_EXPIRED);

		$countersMap = [
			'RESPONSIBLE_ID' => $responsible,
			'CREATED_BY' => $originator,
			'ACCOMPLICES' => $accomplice,
			'AUDITORS' => $auditor
		];
		$mode = 'DELETE';

		$efficiencyMap = [];
		foreach ($fields['ACCOMPLICES'] as $userId)
		{
			$efficiencyMap[$userId] = 'A';
		}
		$efficiencyMap[$fields['RESPONSIBLE_ID']] = 'R';

		static::recalculateCounters($fields, $countersMap, $mode);
		static::recalculateEfficiency($fields, $efficiencyMap, Task::makeInstanceFromSource($fields, 1), $mode);

		Agent::remove($fields['ID']);
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

		$responsible = new Collection();
		$responsible->push(Counter\Name::MY_NOT_VIEWED);
		$responsible->push(Counter\Name::ACCOMPLICES_NOT_VIEWED);
		$responsible->push(Counter\Name::OPENED);
		$responsible->push(Counter\Name::CLOSED);

		$counter = static::getInstance($userId);
		$counter->processRecalculate($responsible);
	}

	public static function setDeadlineTimeLimit($timeLimit)
	{
		\CUserOptions::SetOption('tasks', 'deadlineTimeLimit', $timeLimit);

		return Counter::getDeadlineTimeLimit(true);
	}

	public function recount($counter)
	{
		if (!$this->userId)
		{
			return;
		}

		$plan = new Collection();
		$plan->push($counter);

		$this->processRecalculate($plan);
	}

	/**
	 * @param string $type
	 *
	 * @return array
	 */
	public function getCounters($type)
	{
		if (!self::isAccessToCounters())
		{
			return array();
		}

		$type = strtolower($type);
		switch ($type)
		{
			case Counter\Role::RESPONSIBLE:
				$data = array(
					'total' => array(
						'counter' => $this->get(Counter\Name::MY),
						'code' => ''
					),

					'wo_deadline' => array(
						'counter' => $this->get(Counter\Name::MY_WITHOUT_DEADLINE),
						'code' => Counter\Type::TYPE_WO_DEADLINE
					),

					'expired' => array(
						'counter' => $this->get(Counter\Name::MY_EXPIRED),
						'code' => Counter\Type::TYPE_EXPIRED
					),

					'expired_soon' => array(
						'counter' => $this->get(Counter\Name::MY_EXPIRED_SOON),
						'code' => Counter\Type::TYPE_EXPIRED_CANDIDATES
					),

					'not_viewed' => array(
						'counter' => $this->get(Counter\Name::MY_NOT_VIEWED),
						'code' => Counter\Type::TYPE_NEW
					),
				);
				break;
			case Counter\Role::ACCOMPLICE:
				$data = array(
					'total' => array(
						'counter' => $this->get(Counter\Name::ACCOMPLICES),
						'code' => ''
					),

					'expired' => array(
						'counter' => $this->get(Counter\Name::ACCOMPLICES_EXPIRED),
						'code' => Counter\Type::TYPE_EXPIRED
					),

					'expired_soon' => array(
						'counter' => $this->get(Counter\Name::ACCOMPLICES_EXPIRED_SOON),
						'code' => Counter\Type::TYPE_EXPIRED_CANDIDATES
					),

					'not_viewed' => array(
						'counter' => $this->get(Counter\Name::ACCOMPLICES_NOT_VIEWED),
						'code' => Counter\Type::TYPE_NEW
					),
				);
				break;
			case Counter\Role::ORIGINATOR:
				$data = array(
					'total' => array(
						'counter' => $this->get(Counter\Name::ORIGINATOR),
						'code' => ''
					),
					'wo_deadline' => array(
						'counter' => $this->get(Counter\Name::ORIGINATOR_WITHOUT_DEADLINE),
						'code' => Counter\Type::TYPE_WO_DEADLINE
					),
					'expired' => array(
						'counter' => $this->get(Counter\Name::ORIGINATOR_EXPIRED),
						'code' => Counter\Type::TYPE_EXPIRED
					),
					'wait_ctrl' => array(
						'counter' => $this->get(Counter\Name::ORIGINATOR_WAIT_CONTROL),
						'code' => Counter\Type::TYPE_WAIT_CTRL
					),
				);
				break;
			case Counter\Role::AUDITOR:
				$data = array(
					'total' => array(
						'counter' => $this->get(Counter\Name::AUDITOR),
						'code' => ''
					),
					'expired' => array(
						'counter' => $this->get(Counter\Name::AUDITOR_EXPIRED),
						'code' => Counter\Type::TYPE_EXPIRED
					),
				);
				break;
			default:
			case Counter\Role::ALL:
				$data = array(
					'total' => array(
						'counter' => $this->get(Counter\Name::MY) +
									 $this->get(Counter\Name::ACCOMPLICES) +
									 $this->get(Counter\Name::AUDITOR) +
									 $this->get(Counter\Name::ORIGINATOR),
						'code' => ''
					),

					'wo_deadline' => array(
						'counter' => $this->get(Counter\Name::MY_WITHOUT_DEADLINE) +
									 $this->get(Counter\Name::ORIGINATOR_WITHOUT_DEADLINE),
						'code' => Counter\Type::TYPE_WO_DEADLINE
					),

					'expired' => array(
						'counter' => $this->get(Counter\Name::MY_EXPIRED) +
									 $this->get(Counter\Name::ACCOMPLICES_EXPIRED) +
									 $this->get(Counter\Name::AUDITOR_EXPIRED) +
									 $this->get(Counter\Name::ORIGINATOR_EXPIRED),
						'code' => Counter\Type::TYPE_EXPIRED
					),

					'expired_soon' => array(
						'counter' => $this->get(Counter\Name::MY_EXPIRED_SOON) +
									 $this->get(Counter\Name::ACCOMPLICES_EXPIRED_SOON),
						'code' => Counter\Type::TYPE_EXPIRED_CANDIDATES
					),

					'not_viewed' => array(
						'counter' => $this->get(Counter\Name::MY_NOT_VIEWED) +
									 $this->get(Counter\Name::ACCOMPLICES_NOT_VIEWED),
						'code' => Counter\Type::TYPE_NEW
					),

					'wait_ctrl' => array(
						'counter' => $this->get(Counter\Name::ORIGINATOR_WAIT_CONTROL),
						'code' => Counter\Type::TYPE_WAIT_CTRL
					),
				);
				break;
		}

		return $data;
	}

	private function isAccessToCounters()
	{
		return ($this->userId == User::getId()) ||
			   User::isAdmin() ||
			   \Bitrix\Tasks\Integration\Bitrix24\User::isAdmin() ||
			   \CTasks::IsSubordinate($this->userId, User::getId());
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
					".($this->groupId > 0 ? 'JOIN b_sonet_group as sg on sg.ID = t.GROUP_ID' : '')."
				WHERE
					/*t.CREATED_BY != {$this->userId} AND*/

					t.ZOMBIE = 'N'
					".($this->groupId > 0 ? " AND t.GROUP_ID = {$this->groupId} AND sg.CLOSED != 'Y'" : "")."
					AND (
						t.STATUS != {$statusSupposedlyCompleted}
						AND t.STATUS != {$statusCompleted}
						AND	t.STATUS != {$statusDeferred}
					)
				GROUP BY 
					t.GROUP_ID
			";

			$this->changeCounter(
				Counter\Name::OPENED,
				Application::getConnection()->query($sql)->fetchAll()
			);
		}
	}

	private function changeCounter($name, $counters)
	{
		$name = strtoupper($name);
		$counts = array();

		foreach ($counters as $data)
		{
			$counts[$data['GROUP_ID']] = $data['COUNT'];
		}

		foreach (array_keys($this->counters) as $groupId)
		{
			if (array_key_exists($groupId, $counts))
			{
				$this->counters[$groupId][$name] = $counts[$groupId];
			}
			else
			{
				$this->counters[$groupId][$name] = 0;
			}
		}

		foreach ($counts as $groupId => $value)
		{
			$this->counters[$groupId][$name] = $value;
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
					".($this->groupId > 0 ? 'JOIN b_sonet_group as sg on sg.ID = t.GROUP_ID' : '')."
				WHERE
					t.CREATED_BY != {$this->userId} 
					AND DATE(t.CLOSED_DATE) = DATE(NOW())
					AND t.ZOMBIE = 'N'
					".($this->groupId > 0 ? " AND t.GROUP_ID = {$this->groupId} AND sg.CLOSED != 'Y'" : "")."
					AND (
						t.STATUS = {$statusSupposedlyCompleted}
						OR t.STATUS = {$statusCompleted}
					)
				GROUP BY 
					t.GROUP_ID
			";

			$this->changeCounter(
				Counter\Name::CLOSED,
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
					/*JOIN b_tasks_member as tm ON tm.TASK_ID = t.ID AND tm.TYPE = 'R'*/
					LEFT JOIN b_tasks_viewed as tv
						ON tv.TASK_ID = t.ID AND tv.USER_ID = {$this->userId}/*tm.USER_ID*/
					".($this->groupId > 0 ? 'JOIN b_sonet_group as sg on sg.ID = t.GROUP_ID' : '')."
				WHERE
					(tv.TASK_ID IS NULL OR tv.TASK_ID = 0) AND
					t.CREATED_BY != t.RESPONSIBLE_ID AND
					t.RESPONSIBLE_ID /*tm.USER_ID*/ = {$this->userId} AND
					t.ZOMBIE = 'N' AND
					
					".($this->groupId > 0 ? " t.GROUP_ID = {$this->groupId} AND sg.CLOSED != 'Y' AND" : "")."
					(
						t.STATUS <3
					)
				GROUP BY
					t.GROUP_ID
					
					
				
			";

			$this->changeCounter(
				Counter\Name::MY_NOT_VIEWED,
				Application::getConnection()->query($sql)->fetchAll()
			);
		}
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
					COUNT(t.ID) as COUNT,
					GROUP_ID
				FROM 
					b_tasks as t
					".($this->groupId > 0 ? 'JOIN b_sonet_group as sg on sg.ID = t.GROUP_ID' : '')."
				WHERE 
					(DEADLINE = '' OR DEADLINE IS NULL)
					AND RESPONSIBLE_ID = {$this->userId}
					AND RESPONSIBLE_ID != CREATED_BY
					AND ZOMBIE = 'N'
					".($this->groupId > 0 ? " AND GROUP_ID = {$this->groupId} AND sg.CLOSED != 'Y'" : "")."
					AND (
						STATUS != {$statusSupposedlyCompleted}
						AND STATUS != {$statusCompleted}
						AND	STATUS != {$statusDeferred}
					)
				GROUP BY 
					GROUP_ID
			";

			$this->changeCounter(
				Counter\Name::MY_WITHOUT_DEADLINE,
				Application::getConnection()->query($sql)->fetchAll()
			);
		}
	}

	private function calcMyExpired($reCache = false)
	{
		static $count = null;

		if ($count == null || $reCache)
		{
			$expiredTime = Counter::getExpiredTime()->format('Y-m-d H:i:s');
			$statusSupposedlyCompleted = \CTasks::STATE_SUPPOSEDLY_COMPLETED;
			$statusCompleted = \CTasks::STATE_COMPLETED;
			$statusDeferred = \CTasks::STATE_DEFERRED;

			$sql = "
				SELECT 
					COUNT(t.ID) as COUNT,
					t.GROUP_ID
				FROM 
					b_tasks as t
					/*INNER JOIN b_tasks_member as tm 
						ON tm.TASK_ID = t.ID AND tm.TYPE = 'R'*/
					".($this->groupId > 0 ? 'JOIN b_sonet_group as sg on sg.ID = t.GROUP_ID' : '')."
				WHERE 
					t.DEADLINE < '{$expiredTime}'
					/*AND tm.USER_ID = {$this->userId}*/
	   				AND RESPONSIBLE_ID =  {$this->userId}
					AND t.ZOMBIE = 'N'
					".($this->groupId > 0 ? " AND t.GROUP_ID = {$this->groupId} AND sg.CLOSED != 'Y'" : "")."
					AND (
						t.STATUS != {$statusSupposedlyCompleted}
						AND t.STATUS != {$statusCompleted}
						AND	t.STATUS != {$statusDeferred}
					)
				GROUP BY 
					t.GROUP_ID
			";

			$this->changeCounter(
				Counter\Name::MY_EXPIRED,
				Application::getConnection()->query($sql)->fetchAll()
			);
		}
	}

	private function calcMyExpiredSoon($reCache = false)
	{
		static $count = null;

		if ($count == null || $reCache)
		{
			$expiredSoonTime = Counter::getExpiredSoonTime()->format('Y-m-d H:i:s');;
			$expiredTime = Counter::getExpiredTime()->format('Y-m-d H:i:s');
			$statusSupposedlyCompleted = \CTasks::STATE_SUPPOSEDLY_COMPLETED;
			$statusCompleted = \CTasks::STATE_COMPLETED;
			$statusDeferred = \CTasks::STATE_DEFERRED;

			$sql = "
				SELECT 
					COUNT(t.ID) as COUNT,
					GROUP_ID
				FROM 
					b_tasks t
					".($this->groupId > 0 ? 'JOIN b_sonet_group as sg on sg.ID = t.GROUP_ID' : '')."
				WHERE 
					DEADLINE < '{$expiredSoonTime}'
					AND DEADLINE >= '{$expiredTime}'
					AND RESPONSIBLE_ID = {$this->userId}
					/*AND RESPONSIBLE_ID != CREATED_BY*/
					AND ZOMBIE = 'N'
					".($this->groupId > 0 ? " AND GROUP_ID = {$this->groupId} AND sg.CLOSED != 'Y'" : "")."
					AND (
						STATUS != {$statusSupposedlyCompleted}
						AND STATUS != {$statusCompleted}
						AND	STATUS != {$statusDeferred}
					)
				GROUP BY 
					GROUP_ID
			";

			$this->changeCounter(
				Counter\Name::MY_EXPIRED_SOON,
				Application::getConnection()->query($sql)->fetchAll()
			);
		}
	}

	private function calcAuditorExpired($reCache = false)
	{
		static $count = null;

		if ($count == null || $reCache)
		{
			$expiredTime = Counter::getExpiredTime()->format('Y-m-d H:i:s');

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
					".($this->groupId > 0 ? 'JOIN b_sonet_group as sg on sg.ID = t.GROUP_ID' : '')."
				WHERE 
					t.DEADLINE < '{$expiredTime}'
					AND tm.USER_ID = {$this->userId}
					
					
					AND t.ZOMBIE = 'N'
					".($this->groupId > 0 ? " AND t.GROUP_ID = {$this->groupId} AND sg.CLOSED != 'Y'" : "")."
					AND (
						t.STATUS != {$statusSupposedlyCompleted}
						AND t.STATUS != {$statusCompleted}
						AND	t.STATUS != {$statusDeferred}
					)
				GROUP BY 
					t.GROUP_ID
			";

			$this->changeCounter(
				Counter\Name::AUDITOR_EXPIRED,
				Application::getConnection()->query($sql)->fetchAll()
			);
		}
	}

	private function calcAccomplicesExpiredSoon($reCache = false)
	{
		static $count = null;

		if ($count == null || $reCache)
		{
			$expiredTime = Counter::getExpiredTime()->format('Y-m-d H:i:s');
			$expiredSoonTime = Counter::getExpiredSoonTime()->format('Y-m-d H:i:s');;

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
					".($this->groupId > 0 ? 'JOIN b_sonet_group as sg on sg.ID = t.GROUP_ID' : '')."
				WHERE
					DEADLINE < '{$expiredSoonTime}'
					AND DEADLINE >= '{$expiredTime}'
					
					AND tm.USER_ID = {$this->userId}
					AND t.ZOMBIE = 'N'
					".($this->groupId > 0 ? " AND t.GROUP_ID = {$this->groupId} AND sg.CLOSED != 'Y'" : "")."
					AND (
						t.STATUS != {$statusSupposedlyCompleted}
						AND t.STATUS != {$statusCompleted}
						AND	t.STATUS != {$statusDeferred}
					)
				GROUP BY 
					t.GROUP_ID
			";

			$this->changeCounter(
				Counter\Name::ACCOMPLICES_EXPIRED_SOON,
				Application::getConnection()->query($sql)->fetchAll()
			);
		}
	}

	private function calcAccomplicesExpired($reCache = false)
	{
		static $count = null;

		if ($count == null || $reCache)
		{
			$expiredTime = Counter::getExpiredTime()->format('Y-m-d H:i:s');

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
					".($this->groupId > 0 ? 'JOIN b_sonet_group as sg on sg.ID = t.GROUP_ID' : '')."
				WHERE
					t.DEADLINE < '{$expiredTime}'
					AND tm.USER_ID = {$this->userId}
					AND t.ZOMBIE = 'N'
					".($this->groupId > 0 ? " AND t.GROUP_ID = {$this->groupId} AND sg.CLOSED != 'Y'" : "")."
					AND (
						t.STATUS != {$statusSupposedlyCompleted}
						AND t.STATUS != {$statusCompleted}
						AND	t.STATUS != {$statusDeferred}
					)
				GROUP BY 
					t.GROUP_ID
			";

			$this->changeCounter(
				Counter\Name::ACCOMPLICES_EXPIRED,
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
						
					".($this->groupId > 0 ? 'JOIN b_sonet_group as sg on sg.ID = t.GROUP_ID' : '')."
				WHERE
					(tv.TASK_ID IS NULL OR tv.TASK_ID = 0)
					AND tm.USER_ID = {$this->userId}
					AND t.ZOMBIE = 'N'
					".($this->groupId > 0 ? " AND t.GROUP_ID = {$this->groupId} AND sg.CLOSED != 'Y'" : "")."
					AND (
						t.STATUS < 3
					)
				GROUP BY 
					t.GROUP_ID
			";

			$this->changeCounter(
				Counter\Name::ACCOMPLICES_NOT_VIEWED,
				Application::getConnection()->query($sql)->fetchAll()
			);
		}
	}

	private function calcOriginatorExpired($reCache = false)
	{
		static $count = null;

		if ($count == null || $reCache)
		{
			$expiredTime = Counter::getExpiredTime()->format('Y-m-d H:i:s');

			$statusSupposedlyCompleted = \CTasks::STATE_SUPPOSEDLY_COMPLETED;
			$statusCompleted = \CTasks::STATE_COMPLETED;
			$statusDeferred = \CTasks::STATE_DEFERRED;

			$sql = "
				SELECT 
					COUNT(t.ID) as COUNT,
					GROUP_ID
				FROM 
					b_tasks t
					
					".($this->groupId > 0 ? 'JOIN b_sonet_group as sg on sg.ID = t.GROUP_ID' : '')."
				WHERE 
					DEADLINE < '{$expiredTime}'
					AND CREATED_BY = {$this->userId}
					AND RESPONSIBLE_ID != CREATED_BY
					AND ZOMBIE = 'N'
					".($this->groupId > 0 ? " AND GROUP_ID = {$this->groupId} AND sg.CLOSED != 'Y'" : "")."
					AND (
						STATUS != {$statusSupposedlyCompleted}
						AND STATUS != {$statusCompleted}
						AND	STATUS != {$statusDeferred}
					)
				GROUP BY 
					GROUP_ID
			";

			$this->changeCounter(
				Counter\Name::ORIGINATOR_EXPIRED,
				Application::getConnection()->query($sql)->fetchAll()
			);
		}
	}

	private function calcOriginatorWaitCtrl($reCache = false)
	{
		static $count = null;

		if ($count == null || $reCache)
		{
			$statusSupposedlyCompleted = \CTasks::STATE_SUPPOSEDLY_COMPLETED;

			$sql = "
				SELECT 
					COUNT(t.ID) as COUNT,
					GROUP_ID
				FROM 
					b_tasks t
					
					".($this->groupId > 0 ? 'JOIN b_sonet_group as sg on sg.ID = t.GROUP_ID' : '')."
				WHERE 
					CREATED_BY = {$this->userId}
					AND RESPONSIBLE_ID != CREATED_BY
					AND ZOMBIE = 'N'
					".($this->groupId > 0 ? " AND GROUP_ID = {$this->groupId} AND sg.CLOSED != 'Y'" : "")."
					AND STATUS = {$statusSupposedlyCompleted}
				GROUP BY 
					GROUP_ID
			";

			$this->changeCounter(
				Counter\Name::ORIGINATOR_WAIT_CONTROL,
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
					COUNT(t.ID) as COUNT,
					GROUP_ID
				FROM 
					b_tasks t
					
					".($this->groupId > 0 ? 'JOIN b_sonet_group as sg on sg.ID = t.GROUP_ID' : '')."
				WHERE 
					(DEADLINE IS NULL OR DEADLINE = '')
					AND CREATED_BY = {$this->userId}
					AND RESPONSIBLE_ID != CREATED_BY
					AND ZOMBIE = 'N'
					".($this->groupId > 0 ? " AND GROUP_ID = {$this->groupId} AND sg.CLOSED != 'Y'" : "")."
					AND (
						STATUS != {$statusSupposedlyCompleted}
						AND STATUS != {$statusCompleted}
						AND	STATUS != {$statusDeferred}
					)
				GROUP BY 
					GROUP_ID
			";

			$this->changeCounter(
				Counter\Name::ORIGINATOR_WITHOUT_DEADLINE,
				Application::getConnection()->query($sql)->fetchAll()
			);
		}
	}

	/**
	 * @param array $users
	 */
	public static function sendPushCounters(array $users)
	{
		$tag = 'task_counters';
		$types = [
			Counter\Role::ALL,
			Counter\Role::RESPONSIBLE,
			Counter\Role::ACCOMPLICE,
			Counter\Role::ORIGINATOR,
			Counter\Role::AUDITOR,
		];

		foreach ($users as $userId)
		{
			$pushData = ['userId' => $userId];
			$counter = self::getInstance($userId);

			foreach ($types as $type)
			{
				$data = $counter->getCounters($type);

				foreach ($data as $key => $value)
				{
					$pushData[$type][$key] = $value['counter'];
				}
			}

			\CPullWatch::Add($userId, $tag);
			\CPullWatch::AddToStack($tag, [
				'module_id' => 'tasks',
				'command' => 'user_counter',
				'params' => $pushData,
			]);
		}
	}
}