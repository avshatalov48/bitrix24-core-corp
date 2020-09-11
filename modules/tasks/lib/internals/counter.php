<?php

namespace Bitrix\Tasks\Internals;

use Bitrix\Main;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Pull\Event;
use Bitrix\Tasks\Comments;
use Bitrix\Tasks\Internals\Counter\Agent;
use Bitrix\Tasks\Internals\Counter\CounterTable;
use Bitrix\Tasks\Internals\Counter\EffectiveTable;
use Bitrix\Tasks\Internals\Counter\Name;
use Bitrix\Tasks\Item\Task;
use Bitrix\Tasks\Update\CounterRecount;
use Bitrix\Tasks\Util\Collection;
use Bitrix\Tasks\Util\Type\DateTime;
use Bitrix\Tasks\Util\User;
use CTasks;
use CUserCounter;
use CUserOptions;
use CUserTypeSQL;
use Exception;
use ReflectionClass;

/**
 * Class Counter
 *
 * @package Bitrix\Tasks\Internals
 */
class Counter
{
	public const DEFAULT_DEADLINE_LIMIT = 86400;

	private static $instance;
	private static $prefix = 'tasks_';

	private $userId;
	private $groupId;
	private $counters;
	private $incrementsToSkip = [];

	/**
	 * Counter constructor.
	 *
	 * @param $userId
	 * @param int $groupId
	 * @throws Main\ArgumentException
	 * @throws Main\DB\SqlQueryException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private function __construct($userId, $groupId = 0)
	{
		$this->userId = (int)$userId;
		$this->groupId = (int)$groupId;

		if (!($this->counters = $this->loadCounters()))
		{
			$this->recountAllCounters();
			$this->counters = $this->loadCounters();
		}
	}

	/**
	 * @param string $increment
	 */
	public function addIncrementToSkip(string $increment): void
	{
		$this->setIncrementsToSkip(
			array_unique(
				array_merge(
					$this->getIncrementsToSkip(),
					[$increment]
				)
			)
		);
	}

	/**
	 * @param string $increment
	 */
	public function deleteIncrementToSkip(string $increment): void
	{
		$currentIncrements = $this->getIncrementsToSkip();

		if (in_array($increment, $currentIncrements, true))
		{
			unset($currentIncrements[array_search($increment, $currentIncrements, true)]);
		}

		$this->setIncrementsToSkip($currentIncrements);
	}

	/**
	 * @return array
	 */
	public function getIncrementsToSkip(): array
	{
		return $this->incrementsToSkip;
	}

	/**
	 * @param array $countersIncrementsToSkip
	 */
	public function setIncrementsToSkip(array $countersIncrementsToSkip): void
	{
		$this->incrementsToSkip = $countersIncrementsToSkip;
	}

	/**
	 * @return array
	 * @throws Main\DB\SqlQueryException
	 */
	private function loadCounters(): array
	{
		$counters = [];
		$select = implode(',', $this->getMap());
		$res = Application::getConnection()->query("
			SELECT GROUP_ID, {$select}
			FROM b_tasks_counters 
			WHERE
				USER_ID = {$this->userId}
				".($this->groupId > 0 ? "AND GROUP_ID = {$this->groupId}" : "")." 
			GROUP BY GROUP_ID
		");
		while ($item = $res->fetch())
		{
			$counters[$item['GROUP_ID']] = $item;
		}

		return $counters;
	}

	/**
	 * @return array|string[]
	 */
	private function getMap(): array
	{
		return [
			'EXPIRED',
			'MY_EXPIRED',
			'ORIGINATOR_EXPIRED',
			'ACCOMPLICES_EXPIRED',
			'AUDITOR_EXPIRED',
			'NEW_COMMENTS',
			'MY_NEW_COMMENTS',
			'ORIGINATOR_NEW_COMMENTS',
			'ACCOMPLICES_NEW_COMMENTS',
			'AUDITOR_NEW_COMMENTS',
		];
	}

	/**
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private function recountAllCounters(): void
	{
		if (!$this->userId)
		{
			return;
		}

		$counterNamesCollection = new Collection();
		$reflect = new ReflectionClass(Name::class);
		foreach ($reflect->getConstants() as $counterName)
		{
			$counterNamesCollection->push($counterName);
		}

		$this->processRecalculate($counterNamesCollection);
	}

	/**
	 * @param string $counterName
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function recount(string $counterName): void
	{
		if (!$this->userId)
		{
			return;
		}

		$counterNamesCollection = new Collection();
		$counterNamesCollection->push($counterName);

		$this->processRecalculate($counterNamesCollection);
	}

	/**
	 * @param Collection $counterNamesCollection
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function processRecalculate(Collection $counterNamesCollection): void
	{
		$counterNames = array_unique($counterNamesCollection->export());
		foreach ($counterNames as $name)
		{
			$method = 'calc'.implode('', array_map('ucfirst', explode('_', $name)));
			if (method_exists($this, $method))
			{
				$this->{$method}(true);
			}
		}

		$this->saveCounters();
	}

	/**
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 * @throws Exception
	 */
	private function saveCounters(): void
	{
		if (!$this->userId)
		{
			return;
		}

		foreach ($this->counters as $groupId => $counters)
		{
			$list = CounterTable::getList([
				'select' => ['ID'],
				'filter' => [
					'=USER_ID' => $this->userId,
					'=GROUP_ID' => $groupId,
				],
			]);

			if ($item = $list->fetch())
			{
				CounterTable::update($item, $counters);
			}
			else
			{
				CounterTable::add(array_merge(['USER_ID' => $this->userId, 'GROUP_ID' => $groupId], $counters));
			}
		}

		$this->saveCountersByNames([Name::TOTAL]);
	}

	/**
	 * @param array $names
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private function saveCountersByNames(array $names): void
	{
		foreach ($names as $name)
		{
			CUserCounter::Set($this->userId, self::getPrefix().$name, $this->get($name), '**', '', false);
		}
	}

	/**
	 * @return string
	 */
	public static function getPrefix(): string
	{
		return self::$prefix;
	}

	/**
	 * @param $role
	 * @param array $params
	 * @return array|array[]
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function getCounters($role, $params = []): array
	{
		$skipAccessCheck = (isset($params['SKIP_ACCESS_CHECK']) && $params['SKIP_ACCESS_CHECK']);

		if (!$skipAccessCheck && !$this->isAccessToCounters())
		{
			return [];
		}

		switch (strtolower($role))
		{
			case Counter\Role::ALL:
				$counters = [
					'total' => [
						'counter' => $this->get(Name::TOTAL),
						'code' => '',
					],
					'expired' => [
						'counter' => $this->get(Name::EXPIRED),
						'code' => Counter\Type::TYPE_EXPIRED,
					],
					'new_comments' => [
						'counter' => $this->get(Name::NEW_COMMENTS),
						'code' => Counter\Type::TYPE_NEW_COMMENTS,
					],
				];
				break;

			case Counter\Role::RESPONSIBLE:
				$counters = [
					'total' => [
						'counter' => $this->get(Name::MY),
						'code' => '',
					],
					'expired' => [
						'counter' => $this->get(Name::MY_EXPIRED),
						'code' => Counter\Type::TYPE_EXPIRED,
					],
					'new_comments' => [
						'counter' => $this->get(Name::MY_NEW_COMMENTS),
						'code' => Counter\Type::TYPE_NEW_COMMENTS,
					],
				];
				break;

			case Counter\Role::ORIGINATOR:
				$counters = [
					'total' => [
						'counter' => $this->get(Name::ORIGINATOR),
						'code' => '',
					],
					'expired' => [
						'counter' => $this->get(Name::ORIGINATOR_EXPIRED),
						'code' => Counter\Type::TYPE_EXPIRED,
					],
					'new_comments' => [
						'counter' => $this->get(Name::ORIGINATOR_NEW_COMMENTS),
						'code' => Counter\Type::TYPE_NEW_COMMENTS,
					],
				];
				break;

			case Counter\Role::ACCOMPLICE:
				$counters = [
					'total' => [
						'counter' => $this->get(Name::ACCOMPLICES),
						'code' => '',
					],
					'expired' => [
						'counter' => $this->get(Name::ACCOMPLICES_EXPIRED),
						'code' => Counter\Type::TYPE_EXPIRED,
					],
					'new_comments' => [
						'counter' => $this->get(Name::ACCOMPLICES_NEW_COMMENTS),
						'code' => Counter\Type::TYPE_NEW_COMMENTS,
					],
				];
				break;

			case Counter\Role::AUDITOR:
				$counters = [
					'total' => [
						'counter' => $this->get(Name::AUDITOR),
						'code' => '',
					],
					'expired' => [
						'counter' => $this->get(Name::AUDITOR_EXPIRED),
						'code' => Counter\Type::TYPE_EXPIRED,
					],
					'new_comments' => [
						'counter' => $this->get(Name::AUDITOR_NEW_COMMENTS),
						'code' => Counter\Type::TYPE_NEW_COMMENTS,
					],
				];
				break;

			default:
				$counters = [];
				break;
		}

		return $counters;
	}

	/**
	 * @param $name
	 * @return bool|int|mixed
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function get($name)
	{
		switch ($name)
		{
			case Name::TOTAL:
				$value = $this->get(Name::EXPIRED) + $this->get(Name::NEW_COMMENTS);
				break;

			case Name::MY:
				$value = $this->get(Name::MY_EXPIRED) + $this->get(Name::MY_NEW_COMMENTS);
				break;

			case Name::ORIGINATOR:
				$value = $this->get(Name::ORIGINATOR_EXPIRED) + $this->get(Name::ORIGINATOR_NEW_COMMENTS);
				break;

			case Name::ACCOMPLICES:
				$value = $this->get(Name::ACCOMPLICES_EXPIRED) + $this->get(Name::ACCOMPLICES_NEW_COMMENTS);
				break;

			case Name::AUDITOR:
				$value = $this->get(Name::AUDITOR_EXPIRED) + $this->get(Name::AUDITOR_NEW_COMMENTS);
				break;

			case Name::EFFECTIVE:
				$value = $this->getKpi();
				break;

			default:
				$value = $this->getInternal($name);
				break;
		}

		return $value;
	}

	/**
	 * @return bool|int
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private function getKpi()
	{
		$efficiency = Effective::getEfficiencyFromUserCounter($this->userId);
		if (!$efficiency && ($efficiency = Effective::getAverageEfficiency(null, null, $this->userId)))
		{
			Effective::setEfficiencyToUserCounter($this->userId, $efficiency);
		}

		return $efficiency;
	}

	/**
	 * @param string $name
	 * @param int|null $groupId
	 * @return int
	 */
	private function getInternal(string $name, ?int $groupId = null): int
	{
		$name = mb_strtoupper($name);

		if ($this->groupId > 0 || isset($groupId))
		{
			$groupId = ($this->groupId > 0 ? $this->groupId : $groupId);

			if (
				!array_key_exists($groupId, $this->counters)
				|| !array_key_exists($name, $this->counters[$groupId])
			)
			{
				return 0;
			}

			return $this->counters[$groupId][$name];
		}

		$counter = 0;
		foreach ($this->counters as $counters)
		{
			$counter += $counters[$name];
		}

		return $counter;
	}

	/**
	 * @param $deadline
	 * @return bool
	 */
	public static function isDeadlineExpired($deadline): bool
	{
		if (!$deadline || !($deadline = DateTime::createFrom($deadline)))
		{
			return false;
		}

		return $deadline->checkLT(self::getExpiredTime());
	}

	/**
	 * @param $deadline
	 * @return bool
	 */
	public static function isDeadlineExpiredSoon($deadline): bool
	{
		if (!$deadline || !($deadline = DateTime::createFrom($deadline)))
		{
			return false;
		}

		return $deadline->checkGT(self::getExpiredTime()) && $deadline->checkLT(self::getExpiredSoonTime());
	}

	/**
	 * @return DateTime
	 */
	public static function getExpiredTime(): DateTime
	{
		return new DateTime();
	}

	/**
	 * @return DateTime
	 */
	public static function getExpiredSoonTime(): DateTime
	{
		return DateTime::createFromTimestamp(time() + self::getDeadlineTimeLimit());
	}

	/**
	 * @param bool $reCache
	 * @return int
	 */
	public static function getDeadlineTimeLimit($reCache = false): int
	{
		static $time;

		if (!$time || $reCache)
		{
			$time = CUserOptions::GetOption(
				'tasks',
				'deadlineTimeLimit',
				self::DEFAULT_DEADLINE_LIMIT
			);
		}

		return $time;
	}

	/**
	 * @param $timeLimit
	 * @return int
	 */
	public static function setDeadlineTimeLimit($timeLimit): int
	{
		CUserOptions::SetOption('tasks', 'deadlineTimeLimit', $timeLimit);
		return self::getDeadlineTimeLimit(true);
	}

	/**
	 * @param $userId
	 * @param int $groupId
	 * @param bool $reCache
	 * @return static
	 * @throws Main\ArgumentException
	 * @throws Main\DB\SqlQueryException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function getInstance($userId, $groupId = 0, $reCache = false): self
	{
		if (
			$reCache
			|| !self::$instance
			|| !array_key_exists($userId, self::$instance)
			|| !array_key_exists($groupId, self::$instance[$userId])
		)
		{
			self::$instance[$userId][$groupId] = new self($userId, $groupId);
		}

		return self::$instance[$userId][$groupId];
	}

	public static function onBeforeTaskAdd(): void
	{

	}

	/**
	 * @param array $fields
	 * @throws Exception
	 */
	public static function onAfterTaskAdd(array $fields): void
	{
		$efficiencyMap = array_fill_keys($fields['ACCOMPLICES'], 'A');
		$efficiencyMap[$fields['RESPONSIBLE_ID']] = 'R';

		$task = Task::makeInstanceFromSource($fields, User::getAdminId());
		$taskId = $task->getId();

		self::recalculateEfficiency($fields, $efficiencyMap, $task, 'ADD');

		if ($fields['DEADLINE'] && ($deadline = DateTime::createFrom($fields['DEADLINE'])))
		{
			Agent::add($taskId, $deadline);
		}
	}

	public static function onBeforeTaskUpdate(): void
	{

	}

	/**
	 * @param array $fields
	 * @param array $newFields
	 * @param array $params
	 * @throws Exception
	 */
	public static function onAfterTaskUpdate(array $fields, array $newFields, array $params = []): void
	{
		self::updateAgents($fields, $newFields);

		if (
			(isset($params['FORCE_RECOUNT_COUNTER']) && $params['FORCE_RECOUNT_COUNTER'] === 'Y')
			|| self::needUpdateRecounts($fields, $newFields)
		)
		{
			if ($task = Task::getInstance($fields['ID'], User::getAdminId()))
			{
				self::updateEfficiency($fields, $newFields, $task);
				self::updateCounters($fields, $newFields);
			}
		}

	}

	/**
	 * @param array $fields
	 * @param array $newFields
	 */
	private static function updateAgents(array $fields, array $newFields): void
	{
		$taskId = (int)$fields['ID'];

		if (self::fieldChanged('DEADLINE', $fields, $newFields))
		{
			if ($newFields['DEADLINE'] && ($deadline = DateTime::createFrom($newFields['DEADLINE'])))
			{
				Agent::add($taskId, $deadline);
			}
			else
			{
				Agent::remove($taskId);
			}
		}
	}

	/**
	 * @param array $fields
	 * @param array $newFields
	 * @return bool
	 */
	private static function needUpdateRecounts(array $fields, array $newFields): bool
	{
		return in_array(true, self::getChangeFact($fields, $newFields), true);
	}

	/**
	 * @param array $fields
	 * @param array $newFields
	 * @param Task $task
	 * @throws Main\ArgumentException
	 * @throws Main\Db\SqlQueryException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 * @throws Exception
	 */
	private static function updateEfficiency(array $fields, array $newFields, Task $task): void
	{
		[
			$statusChanged,
			$deadlineChanged,
			$groupChanged,
			$responsibleChanged,
			$accomplicesChanged,
		] = self::getChangeFact($fields, $newFields);

		$taskId = $fields['ID'];

		$oldStatus = (int)($fields['STATUS'] > 0 ? $fields['STATUS'] : $fields['REAL_STATUS']);
		$newStatus = (int)($newFields['STATUS'] > 0 ? $newFields['STATUS'] : $newFields['REAL_STATUS']);

		$deadline = ($deadlineChanged ? $newFields['DEADLINE'] : $fields['DEADLINE']);
		$isViolation = self::isDeadlineExpired($deadline);

		$oldGroupId = (int)$fields['GROUP_ID'];
		$newGroupId = (int)$newFields['GROUP_ID'];
		$groupId = ($groupChanged ? $newGroupId : $oldGroupId);

		$oldResponsibleId = (int)$fields['RESPONSIBLE_ID'];
		$newResponsibleId = (int)$newFields['RESPONSIBLE_ID'];

		$oldAccomplices = array_map('intval', (array)$fields['ACCOMPLICES']);
		$newAccomplices = array_map('intval', (array)$newFields['ACCOMPLICES']);
		$accomplicesIn = array_diff($newAccomplices, $oldAccomplices);
		$accomplicesOut = array_diff($oldAccomplices, $newAccomplices);
		$allAccomplices = array_unique(array_merge($oldAccomplices, $newAccomplices));

		$responsibleModified = false;
		$accomplicesModified = false;

		$canProceed = false;
		$statesCompleted = [CTasks::STATE_DEFERRED, CTasks::STATE_SUPPOSEDLY_COMPLETED, CTasks::STATE_COMPLETED];
		$statesInProgress = [CTasks::STATE_NEW, Ctasks::STATE_PENDING, CTasks::STATE_IN_PROGRESS];

		// TASK DEFERRED OR COMPLETED
		if ($statusChanged && in_array($newStatus, $statesCompleted, true))
		{
			Effective::repair($taskId);
			Effective::modify($oldResponsibleId, 'R', $task, $oldGroupId, false);

			foreach ($oldAccomplices as $userId)
			{
				if ($userId !== $oldResponsibleId)
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
					if ($userId !== $oldResponsibleId && $userId !== $newResponsibleId)
					{
						Effective::modify($userId, 'A', $task, $groupId, false);
					}
				}
			}

			return;
		}

		// TASK RESTARTED
		if (
			$statusChanged
			&& in_array($oldStatus, $statesCompleted, true)
			&& in_array($newStatus, $statesInProgress, true)
		)
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
					if ($userId !== $oldResponsibleId && $userId !== $newResponsibleId)
					{
						Effective::modify($userId, 'A', $task, $groupId, $isViolation);
					}
				}
				$accomplicesModified = true;
			}

			$canProceed = true;
		}

		if (!$canProceed && in_array($oldStatus, $statesCompleted, true))
		{
			return;
		}

		// RESPONSIBLE CHANGED
		if ($responsibleChanged)
		{
			if (
				($activeViolations = Effective::checkActiveViolations($taskId, $oldResponsibleId))
				&& in_array($oldResponsibleId, $newAccomplices, true))
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
				if ($userId !== $oldResponsibleId && $userId !== $newResponsibleId)
				{
					Effective::repair($taskId, $userId, 'A');
					Effective::modify($userId, 'A', $task, $oldGroupId, false);
				}
			}

			foreach ($accomplicesIn as $userId)
			{
				if ($userId !== $oldResponsibleId && $userId !== $newResponsibleId)
				{
					Effective::modify($userId, 'A', $task, $groupId, $isViolation);
				}
			}
		}

		// DEADLINE CHANGED
		if ($deadlineChanged && !$isViolation)
		{
			Effective::repair($taskId);

			if (!$responsibleModified)
			{
				Effective::modify($oldResponsibleId, 'R', $task, $groupId, false);

				$responsibleModified = true;
			}

			if (!$accomplicesModified)
			{
				$accomplices = ($accomplicesChanged ? array_diff($newAccomplices, $accomplicesIn) : $allAccomplices);

				foreach ($accomplices as $userId)
				{
					if ($userId !== $oldResponsibleId && $userId !== $newResponsibleId)
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
					$accomplices = ($accomplicesChanged ? array_diff($newAccomplices, $accomplicesIn) : $allAccomplices);

					foreach ($accomplices as $userId)
					{
						if ($userId !== $oldResponsibleId && $userId !== $newResponsibleId)
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
					$accomplices = ($accomplicesChanged ? array_diff($newAccomplices, $accomplicesIn) : $allAccomplices);

					foreach ($accomplices as $userId)
					{
						if ($userId !== $oldResponsibleId && $userId !== $newResponsibleId)
						{
							Effective::modify($userId, 'A', $task, $newGroupId, $isViolation);
						}
					}
				}
			}
		}
	}

	/**
	 * @param array $fields
	 * @param array $newFields
	 * @throws Main\ArgumentException
	 * @throws Main\DB\SqlQueryException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private static function updateCounters(array $fields, array $newFields): void
	{
		[
			,
			$deadlineChanged,
			$groupChanged,
			$responsibleChanged,
			$accomplicesChanged,
			$creatorChanged,
			$auditorsChanged,
		] = self::getChangeFact($fields, $newFields);

		$taskId = $fields['ID'];

		$wasExpired = self::isDeadlineExpired($fields['DEADLINE']);

		$oldGroupId = (int)$fields['GROUP_ID'];
		$newGroupId = (int)$newFields['GROUP_ID'];

		$oldResponsibleId = (int)$fields['RESPONSIBLE_ID'];
		$newResponsibleId = (int)$newFields['RESPONSIBLE_ID'];

		$oldCreator = (int)$fields['CREATED_BY'];
		$newCreator = (int)$newFields['CREATED_BY'];

		$oldAccomplices = array_map('intval', (array)$fields['ACCOMPLICES']);
		$newAccomplices = array_map('intval', (array)$newFields['ACCOMPLICES']);

		$oldAuditors = array_map('intval', (array)$fields['AUDITORS']);
		$newAuditors = array_map('intval', (array)$newFields['AUDITORS']);

		$oldMembers = array_unique(
			array_merge(
				[$oldCreator, $oldResponsibleId],
				$oldAccomplices,
				$oldAuditors
			)
		);
		$newMembers = array_unique(
			array_merge(
				[
					($creatorChanged ? $newCreator : $oldCreator),
					($responsibleChanged ? $newResponsibleId : $oldResponsibleId),
				],
				($accomplicesChanged ? $newAccomplices : $oldAccomplices),
				($auditorsChanged ? $newAuditors : $oldAuditors)
			)
		);
		$allMembers = array_unique(array_merge($oldMembers, $newMembers));
		$membersIn = array_diff($newMembers, $oldMembers);
		$membersOut = array_diff($oldMembers, $newMembers);
		$staticMembers = array_diff($allMembers, array_merge($membersIn, $membersOut));

		foreach ($membersOut as $userId)
		{
			if (self::isMuted($taskId, $userId))
			{
				continue;
			}

			$newCommentsCount = Comments\Task::getNewCommentsCountForTasks([$taskId], $userId)[$taskId];

			$instance = self::getInstance($userId);
			$instance->changeCounter($oldGroupId, Name::NEW_COMMENTS, -$newCommentsCount);
			$instance->changeCounter($oldGroupId, Name::EXPIRED, -$wasExpired);

			if ($userId === $oldResponsibleId)
			{
				$instance->changeCounter($oldGroupId, Name::MY_NEW_COMMENTS, -$newCommentsCount);
				$instance->changeCounter($oldGroupId, Name::MY_EXPIRED, -$wasExpired);
			}
			if ($userId === $oldCreator && $oldCreator !== $oldResponsibleId)
			{
				$instance->changeCounter($oldGroupId, Name::ORIGINATOR_NEW_COMMENTS, -$newCommentsCount);
				$instance->changeCounter($oldGroupId, Name::ORIGINATOR_EXPIRED, -$wasExpired);
			}
			if (in_array($userId, $oldAccomplices, true))
			{
				$instance->changeCounter($oldGroupId, Name::ACCOMPLICES_NEW_COMMENTS, -$newCommentsCount);
				$instance->changeCounter($oldGroupId, Name::ACCOMPLICES_EXPIRED, -$wasExpired);
			}
			if (in_array($userId, $oldAuditors, true))
			{
				$instance->changeCounter($oldGroupId, Name::AUDITOR_NEW_COMMENTS, -$newCommentsCount);
				$instance->changeCounter($oldGroupId, Name::AUDITOR_EXPIRED, -$wasExpired);
			}
			$instance->saveCounters();
		}

		if (!$deadlineChanged && $wasExpired)
		{
			$groupId = ($groupChanged ? $newGroupId : $oldGroupId);

			foreach ($membersIn as $userId)
			{
				if (self::isMuted($taskId, $userId))
				{
					continue;
				}

				$instance = self::getInstance($userId);
				$instance->changeCounter($groupId, Name::EXPIRED, $wasExpired);

				if ($userId === $newResponsibleId)
				{
					$instance->changeCounter($groupId, Name::MY_EXPIRED, $wasExpired);
				}
				if ($userId === $newCreator && $newCreator !== $newResponsibleId)
				{
					$instance->changeCounter($groupId, Name::ORIGINATOR_EXPIRED, $wasExpired);
				}
				if (in_array($userId, $newAccomplices, true))
				{
					$instance->changeCounter($groupId, Name::ACCOMPLICES_EXPIRED, $wasExpired);
				}
				if (in_array($userId, $newAuditors, true))
				{
					$instance->changeCounter($groupId, Name::AUDITOR_EXPIRED, $wasExpired);
				}
				$instance->saveCounters();
			}
		}

		foreach ($staticMembers as $userId)
		{
			if (self::isMuted($taskId, $userId))
			{
				continue;
			}

			$instance = self::getInstance($userId);
			$instance->processRecalculate(
				new Collection([
					Name::EXPIRED,
					Name::MY_EXPIRED,
					Name::ORIGINATOR_EXPIRED,
					Name::ACCOMPLICES_EXPIRED,
					Name::AUDITOR_EXPIRED,
				])
			);

			if ($groupChanged)
			{
				$newCommentsCount = Comments\Task::getNewCommentsCountForTasks([$taskId], $userId)[$taskId];

				$instance->changeCounter($oldGroupId, Name::NEW_COMMENTS, -$newCommentsCount);
				$instance->changeCounter($newGroupId, Name::NEW_COMMENTS, +$newCommentsCount);

				if ($userId === $oldResponsibleId)
				{
					$instance->changeCounter($oldGroupId, Name::MY_NEW_COMMENTS, -$newCommentsCount);
					$instance->changeCounter($newGroupId, Name::MY_NEW_COMMENTS, +$newCommentsCount);
				}
				if ($userId === $oldCreator && $oldCreator !== $oldResponsibleId)
				{
					$instance->changeCounter($oldGroupId, Name::ORIGINATOR_NEW_COMMENTS, -$newCommentsCount);
					$instance->changeCounter($newGroupId, Name::ORIGINATOR_NEW_COMMENTS, +$newCommentsCount);
				}
				if (in_array($userId, $oldAccomplices, true))
				{
					$instance->changeCounter($oldGroupId, Name::ACCOMPLICES_NEW_COMMENTS, -$newCommentsCount);
					$instance->changeCounter($newGroupId, Name::ACCOMPLICES_NEW_COMMENTS, +$newCommentsCount);
				}
				if (in_array($userId, $oldAuditors, true))
				{
					$instance->changeCounter($oldGroupId, Name::AUDITOR_NEW_COMMENTS, -$newCommentsCount);
					$instance->changeCounter($newGroupId, Name::AUDITOR_NEW_COMMENTS, +$newCommentsCount);
				}
				$instance->saveCounters();
			}
		}
	}

	/**
	 * @param array $fields
	 * @param array $newFields
	 * @return array
	 */
	private static function getChangeFact(array $fields, array $newFields): array
	{
		return [
			self::fieldChanged('STATUS', $fields, $newFields),
			self::fieldChanged('DEADLINE', $fields, $newFields),
			self::fieldChanged('GROUP_ID', $fields, $newFields),
			self::fieldChanged('RESPONSIBLE_ID', $fields, $newFields),
			self::fieldChanged('ACCOMPLICES', $fields, $newFields),
			self::fieldChanged('CREATED_BY', $fields, $newFields),
			self::fieldChanged('AUDITORS', $fields, $newFields),
		];
	}

	/**
	 * @param $key
	 * @param $fields
	 * @param $newFields
	 * @return bool
	 */
	private static function fieldChanged($key, $fields, $newFields): bool
	{
		return (array_key_exists($key, $newFields) && $newFields[$key] != $fields[$key]);
	}

	public static function onBeforeTaskDelete(): void
	{

	}

	/**
	 * @param $fields
	 * @throws Main\ArgumentException
	 * @throws Main\Db\SqlQueryException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function onAfterTaskDelete($fields): void
	{
		$efficiencyMap = array_fill_keys($fields['ACCOMPLICES'], 'A');
		$efficiencyMap[$fields['RESPONSIBLE_ID']] = 'R';

		$task = Task::makeInstanceFromSource($fields, User::getAdminId());
		$taskId = $task->getId();

		self::recalculateEfficiency($fields, $efficiencyMap, $task, 'DELETE');
		Agent::remove($taskId);

		$groupId = (int)$fields['GROUP_ID'];
		$createdBy = (int)$fields['CREATED_BY'];
		$responsibleId = (int)$fields['RESPONSIBLE_ID'];
		$accomplices = $fields['ACCOMPLICES'];
		$auditors = $fields['AUDITORS'];
		$accomplices = array_map('intval', (is_array($accomplices) ? $accomplices : $accomplices->toArray()));
		$auditors = array_map('intval', (is_array($auditors) ? $auditors : $auditors->toArray()));

		$isExpired = self::isDeadlineExpired($fields['DEADLINE']);

		$usersToRecountCounters = array_unique(array_merge([$createdBy, $responsibleId], $accomplices, $auditors));
		foreach ($usersToRecountCounters as $userId)
		{
			if (self::isMuted($taskId, $userId))
			{
				return;
			}

			$newCommentsCount = Comments\Task::getNewCommentsCountForTasks([$taskId], $userId)[$taskId];

			$instance = self::getInstance($userId);
			$instance->changeCounter($groupId, Name::NEW_COMMENTS, -$newCommentsCount);
			$instance->changeCounter($groupId, Name::EXPIRED, -$isExpired);

			if ($userId === $responsibleId)
			{
				$instance->changeCounter($groupId, Name::MY_NEW_COMMENTS, -$newCommentsCount);
				$instance->changeCounter($groupId, Name::MY_EXPIRED, -$isExpired);
			}
			if ($userId === $createdBy && $createdBy !== $responsibleId)
			{
				$instance->changeCounter($groupId, Name::ORIGINATOR_NEW_COMMENTS, -$newCommentsCount);
				$instance->changeCounter($groupId, Name::ORIGINATOR_EXPIRED, -$isExpired);
			}
			if (in_array($userId, $accomplices, true))
			{
				$instance->changeCounter($groupId, Name::ACCOMPLICES_NEW_COMMENTS, -$newCommentsCount);
				$instance->changeCounter($groupId, Name::ACCOMPLICES_EXPIRED, -$isExpired);
			}
			if (in_array($userId, $auditors, true))
			{
				$instance->changeCounter($groupId, Name::AUDITOR_NEW_COMMENTS, -$newCommentsCount);
				$instance->changeCounter($groupId, Name::AUDITOR_EXPIRED, -$isExpired);
			}
			$instance->saveCounters();
		}
	}

	/**
	 * @param $fields
	 * @param $map
	 * @param Task $task
	 * @param $mode
	 * @throws Main\Db\SqlQueryException
	 */
	private static function recalculateEfficiency($fields, $map, Task $task, $mode): void
	{
		foreach ($map as $userId => $userType)
		{
			Effective::modify($userId, $userType, $task, $fields['GROUP_ID'], false);
		}

		if ($mode === 'DELETE')
		{
			Effective::repair($fields['ID']);
		}
	}

	/**
	 * @param int $taskId
	 * @param int $userId
	 * @throws Main\ArgumentException
	 * @throws Main\DB\SqlQueryException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function onBeforeTaskViewed(int $taskId, int $userId): void
	{
		if (self::isMuted($taskId, $userId))
		{
			return;
		}

		$task = new Task($taskId, User::getAdminId());
		$taskData = $task->getData(['GROUP_ID', 'CREATED_BY', 'RESPONSIBLE_ID', 'ACCOMPLICES', 'AUDITORS']);

		[$isCreator, $isResponsible, $isAccomplice, $isAuditor] = self::getUserRoles($userId, $taskData);
		if (!$isCreator && !$isResponsible && !$isAccomplice && !$isAuditor)
		{
			return;
		}

		$newCommentsCount = Comments\Task::getNewCommentsCountForTasks([$taskId], $userId)[$taskId];
		$newCommentsCount = -$newCommentsCount;

		if ($newCommentsCount === 0)
		{
			return;
		}

		$changes = [
			Name::NEW_COMMENTS => $newCommentsCount,
		];
		if ($isCreator && !$isResponsible)
		{
			$changes[Name::ORIGINATOR_NEW_COMMENTS] = $newCommentsCount;
		}
		if ($isResponsible)
		{
			$changes[Name::MY_NEW_COMMENTS] = $newCommentsCount;
		}
		if ($isAccomplice)
		{
			$changes[Name::ACCOMPLICES_NEW_COMMENTS] = $newCommentsCount;
		}
		if ($isAuditor)
		{
			$changes[Name::AUDITOR_NEW_COMMENTS] = $newCommentsCount;
		}

		$instance = self::getInstance($userId);
		foreach ($changes as $name => $value)
		{
			$instance->changeCounter((int)$taskData['GROUP_ID'], $name, $value);
		}
		$instance->saveCounters();
	}

	/**
	 * @param int $taskId
	 * @param int $userId
	 */
	public static function onAfterTaskViewed(int $taskId, int $userId): void
	{

	}

	/**
	 * @param Task $task
	 */
	public static function onTaskExpiredSoon(Task $task): void
	{

	}

	/**
	 * @param Task $task
	 * @throws Main\ArgumentException
	 * @throws Main\DB\SqlQueryException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function onTaskExpired(Task $task): void
	{
		$taskId = $task->getId();
		$groupId = (int)$task['GROUP_ID'];
		$createdBy = (int)$task['CREATED_BY'];
		$responsibleId = (int)$task['RESPONSIBLE_ID'];
		$accomplices = $task['ACCOMPLICES'];
		$auditors = $task['AUDITORS'];
		$accomplices = array_map('intval', (is_array($accomplices) ? $accomplices : $accomplices->toArray()));
		$auditors = array_map('intval', (is_array($auditors) ? $auditors : $auditors->toArray()));

		$usersToRecountEfficiency = array_unique(array_merge([$responsibleId], $accomplices));
		foreach ($usersToRecountEfficiency as $userId)
		{
			if (!Effective::checkActiveViolations($taskId, $userId, $groupId))
			{
				$userType = ((int)$userId === $responsibleId ? 'R' : 'A');
				Effective::modify($userId, $userType, $task, $groupId, true);
			}
		}

		$usersToRecountCounters = array_unique(array_merge($usersToRecountEfficiency, [$createdBy], $auditors));
		foreach ($usersToRecountCounters as $userId)
		{
			$instance = self::getInstance($userId);
			$instance->processRecalculate(
				new Collection([
					Name::EXPIRED,
					Name::MY_EXPIRED,
					Name::ORIGINATOR_EXPIRED,
					Name::ACCOMPLICES_EXPIRED,
					Name::AUDITOR_EXPIRED,
				])
			);
		}
		// foreach ($usersToRecountCounters as $userId)
		// {
		// 	if (self::isMuted($taskId, $userId))
		// 	{
		// 		continue;
		// 	}
		//
		// 	$instance = self::getInstance($userId);
		// 	$instance->changeCounter($groupId, Name::EXPIRED, 1);
		//
		// 	if ($userId === $responsibleId)
		// 	{
		// 		$instance->changeCounter($groupId, Name::MY_EXPIRED, 1);
		// 	}
		// 	if ($userId === $createdBy && $createdBy !== $responsibleId)
		// 	{
		// 		$instance->changeCounter($groupId, Name::ORIGINATOR_EXPIRED, 1);
		// 	}
		// 	if (in_array($userId, $accomplices, true))
		// 	{
		// 		$instance->changeCounter($groupId, Name::ACCOMPLICES_EXPIRED, 1);
		// 	}
		// 	if (in_array($userId, $auditors, true))
		// 	{
		// 		$instance->changeCounter($groupId, Name::AUDITOR_EXPIRED, 1);
		// 	}
		// 	$instance->saveCounters();
		// }

		self::sendPushCounters([$userId]);
	}

	/**
	 * @param int $taskId
	 * @param int $userId
	 * @param array $parameters
	 * @throws Main\ArgumentException
	 * @throws Main\DB\SqlQueryException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function onAfterCommentAdd(int $taskId, int $userId, array $parameters): void
	{
		$task = new Task($taskId, User::getAdminId());
		$taskData = $task->getData(['GROUP_ID', 'CREATED_BY', 'RESPONSIBLE_ID', 'ACCOMPLICES', 'AUDITORS']);

		$groupId = (int)$taskData['GROUP_ID'];
		$createdBy = (int)$taskData['CREATED_BY'];
		$responsibleId = (int)$taskData['RESPONSIBLE_ID'];
		$accomplices = $taskData['ACCOMPLICES'];
		$auditors = $taskData['AUDITORS'];
		$accomplices = array_map('intval', (is_array($accomplices) ? $accomplices : $accomplices->toArray()));
		$auditors = array_map('intval', (is_array($auditors) ? $auditors : $auditors->toArray()));

		$usersToRecountCounters = array_unique(array_merge([$createdBy, $responsibleId], $accomplices, $auditors));
		foreach ($usersToRecountCounters as $id)
		{
			if (
				$id === $userId
				|| $parameters['COMMENT_TYPE'] === Comments\Internals\Comment::TYPE_EXPIRED
				|| self::isMuted($taskId, $id)
			)
			{
				continue;
			}

			$instance = self::getInstance($id);
			if (in_array('onAfterCommentAdd', $instance->getIncrementsToSkip(), true))
			{
				$instance->deleteIncrementToSkip('onAfterCommentAdd');
				continue;
			}

			$instance->changeCounter($groupId, Name::NEW_COMMENTS, 1);

			if ($id === $responsibleId)
			{
				$instance->changeCounter($groupId, Name::MY_NEW_COMMENTS, 1);
			}
			if ($id === $createdBy && $createdBy !== $responsibleId)
			{
				$instance->changeCounter($groupId, Name::ORIGINATOR_NEW_COMMENTS, 1);
			}
			if (in_array($id, $accomplices, true))
			{
				$instance->changeCounter($groupId, Name::ACCOMPLICES_NEW_COMMENTS, 1);
			}
			if (in_array($id, $auditors, true))
			{
				$instance->changeCounter($groupId, Name::AUDITOR_NEW_COMMENTS, 1);
			}
			$instance->saveCounters();
		}
	}

	/**
	 * @param int $taskId
	 * @param int $userId
	 * @param int $commentId
	 * @throws Main\ArgumentException
	 * @throws Main\DB\SqlQueryException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function onBeforeCommentDelete(int $taskId, int $userId, int $commentId): void
	{
		$task = new Task($taskId, User::getAdminId());
		$taskData = $task->getData(['GROUP_ID', 'CREATED_BY', 'RESPONSIBLE_ID', 'ACCOMPLICES', 'AUDITORS']);

		$groupId = (int)$taskData['GROUP_ID'];
		$createdBy = (int)$taskData['CREATED_BY'];
		$responsibleId = (int)$taskData['RESPONSIBLE_ID'];
		$accomplices = $taskData['ACCOMPLICES'];
		$auditors = $taskData['AUDITORS'];
		$accomplices = array_map('intval', (is_array($accomplices) ? $accomplices : $accomplices->toArray()));
		$auditors = array_map('intval', (is_array($auditors) ? $auditors : $auditors->toArray()));

		$usersToRecountCounters = array_unique(array_merge([$createdBy, $responsibleId], $accomplices, $auditors));
		foreach ($usersToRecountCounters as $id)
		{
			if (
				$id === $userId
				|| self::isMuted($taskId, $id)
				|| !Comments\Task::isCommentNew($taskId, $id, $commentId)
			)
			{
				continue;
			}

			$instance = self::getInstance($id);
			$instance->changeCounter($groupId, Name::NEW_COMMENTS, -1);

			if ($id === $responsibleId)
			{
				$instance->changeCounter($groupId, Name::MY_NEW_COMMENTS, -1);
			}
			if ($id === $createdBy && $createdBy !== $responsibleId)
			{
				$instance->changeCounter($groupId, Name::ORIGINATOR_NEW_COMMENTS, -1);
			}
			if (in_array($id, $accomplices, true))
			{
				$instance->changeCounter($groupId, Name::ACCOMPLICES_NEW_COMMENTS, -1);
			}
			if (in_array($id, $auditors, true))
			{
				$instance->changeCounter($groupId, Name::AUDITOR_NEW_COMMENTS, -1);
			}
			$instance->saveCounters();
		}
	}

	/**
	 * @param int $taskId
	 * @param int $userId
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private static function isMuted(int $taskId, int $userId): bool
	{
		return UserOption::isOptionSet($taskId, $userId, UserOption\Option::MUTED);
	}

	/**
	 * @param int $taskId
	 * @param int $userId
	 * @param int $commentId
	 */
	public static function onAfterCommentDelete(int $taskId, int $userId, int $commentId): void
	{

	}

	/**
	 * @param int $userId
	 * @param int $groupId
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function onBeforeCommentsReadAll(int $userId, int $groupId = 0): void
	{
		$counters = [
			Name::NEW_COMMENTS,
			Name::MY_NEW_COMMENTS,
			Name::ORIGINATOR_NEW_COMMENTS,
			Name::ACCOMPLICES_NEW_COMMENTS,
			Name::AUDITOR_NEW_COMMENTS,
		];
		$instance = self::getInstance($userId);
		foreach ($counters as $name)
		{
			if ($groupId)
			{
				$instance->changeCounter($groupId, $name, -$instance->getInternal($name, $groupId));
			}
			else
			{
				$instance->setCounter($name, []);
			}
		}
		$instance->saveCounters();
	}

	/**
	 * @param int $userId
	 */
	public static function onAfterCommentsReadAll(int $userId): void
	{

	}

	/**
	 * @param int $taskId
	 * @param int $userId
	 * @param bool $added
	 * @throws Main\ArgumentException
	 * @throws Main\DB\SqlQueryException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function onAfterTaskMuteChange(int $taskId, int $userId, bool $added): void
	{
		$task = new Task($taskId, User::getAdminId());
		$taskData = $task->getData(['DEADLINE', 'GROUP_ID', 'CREATED_BY', 'RESPONSIBLE_ID', 'ACCOMPLICES', 'AUDITORS']);

		[$isCreator, $isResponsible, $isAccomplice, $isAuditor] = self::getUserRoles($userId, $taskData);
		if (!$isCreator && !$isResponsible && !$isAccomplice && !$isAuditor)
		{
			return;
		}

		$newCommentsCount = Comments\Task::getNewCommentsCountForTasks([$taskId], $userId)[$taskId];
		$newCommentsCount = ($added ? -$newCommentsCount : $newCommentsCount);

		$isExpired = (int)self::isDeadlineExpired($taskData['DEADLINE']);
		$isExpired = ($added ? -$isExpired : $isExpired);

		if ($newCommentsCount === 0 && $isExpired === 0)
		{
			return;
		}

		$changes = [
			Name::NEW_COMMENTS => $newCommentsCount,
			Name::EXPIRED => $isExpired,
		];
		if ($isCreator && !$isResponsible)
		{
			$changes[Name::ORIGINATOR_NEW_COMMENTS] = $newCommentsCount;
			$changes[Name::ORIGINATOR_EXPIRED] = $isExpired;
		}
		if ($isResponsible)
		{
			$changes[Name::MY_NEW_COMMENTS] = $newCommentsCount;
			$changes[Name::MY_EXPIRED] = $isExpired;
		}
		if ($isAccomplice)
		{
			$changes[Name::ACCOMPLICES_NEW_COMMENTS] = $newCommentsCount;
			$changes[Name::ACCOMPLICES_EXPIRED] = $isExpired;
		}
		if ($isAuditor)
		{
			$changes[Name::AUDITOR_NEW_COMMENTS] = $newCommentsCount;
			$changes[Name::AUDITOR_EXPIRED] = $isExpired;
		}

		$instance = self::getInstance($userId);
		foreach ($changes as $name => $value)
		{
			$instance->changeCounter((int)$taskData['GROUP_ID'], $name, $value);
		}
		$instance->saveCounters();
	}

	/**
	 * @param int $userId
	 * @param array $taskData
	 * @return array
	 */
	private static function getUserRoles(int $userId, array $taskData): array
	{
		$createdBy = [(int)$taskData['CREATED_BY']];
		$responsibleId = [(int)$taskData['RESPONSIBLE_ID']];
		$accomplices = $taskData['ACCOMPLICES'];
		$auditors = $taskData['AUDITORS'];

		$accomplices = array_map('intval', (is_array($accomplices) ? $accomplices : $accomplices->toArray()));
		$auditors = array_map('intval', (is_array($auditors) ? $auditors : $auditors->toArray()));

		return [
			in_array($userId, $createdBy, true),
			in_array($userId, $responsibleId, true),
			in_array($userId, $accomplices, true),
			in_array($userId, $auditors, true),
		];
	}

	/**
	 * @param int $groupId
	 * @param string $name
	 * @param int $value
	 */
	public function changeCounter(int $groupId, string $name, int $value): void
	{
		$name = strtoupper($name);

		$newValue = $this->counters[$groupId][$name] + $value;
		$newValue = ($newValue < 0 ? 0 : $newValue);

		$this->counters[$groupId][$name] = $newValue;
	}

	/**
	 * @return array
	 */
	public static function getDefaultCountersToRecount(): array
	{
		return [
			Name::EXPIRED,
			Name::NEW_COMMENTS,
		];
	}

	/**
	 * @param array $countersToRecount
	 * @throws Main\ArgumentOutOfRangeException
	 */
	public static function runCounterRecount(array $countersToRecount = []): void
	{
		$countersToRecount = array_intersect($countersToRecount, self::getDefaultCountersToRecount());

		if (empty($countersToRecount))
		{
			return;
		}

		Option::set("tasks", "countersToRecount", serialize($countersToRecount));
		Option::set("tasks", "needCounterRecount", "Y");

		CounterRecount::bind();
	}

	/**
	 * @return string
	 */
	private function getMuteFilter(): string
	{
		$muteCondition = UserOption::getFilterSql($this->userId, UserOption\Option::MUTED);
		return ($muteCondition === "" ? $muteCondition : "AND NOT {$muteCondition}");
	}

	/**
	 * @return array
	 */
	private static function getUserTypeSqlParts(): array
	{
		$userType = new CUserTypeSQL();
		$userType->SetEntity('FORUM_MESSAGE', 'FM.ID');
		$userType->SetFilter([
			'LOGIC' => 'OR',
			'UF_TASK_COMMENT_TYPE' => null,
			'!UF_TASK_COMMENT_TYPE' => Comments\Internals\Comment::TYPE_EXPIRED,
		]);
		$userTypeFilter = $userType->GetFilter();
		$userTypeJoin = $userType->GetJoin('FM.ID');

		return [
			$userTypeJoin,
			(!$userTypeFilter ? "" : "AND ({$userTypeFilter})"),
		];
	}

	/**
	 * @return array|string[]
	 */
	private function getGroupSqlParts(): array
	{
		return [
			($this->groupId > 0 ? "INNER JOIN b_sonet_group SG ON SG.ID = T.GROUP_ID" : ""),
			($this->groupId > 0 ? "AND T.GROUP_ID = {$this->groupId} AND SG.CLOSED != 'Y'" : ""),
		];
	}

	private function getCounterFilter(): string
	{
		$counterFilter = '';

		$startCounterDate = \COption::GetOptionString("tasks", "tasksDropCommentCounters", null);
		if ($startCounterDate)
		{
			$counterFilter = " AND FM.POST_DATE > '{$startCounterDate}'";
		}

		return $counterFilter;
	}

	/**
	 * @throws Main\ArgumentException
	 * @throws Main\Db\SqlQueryException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function recountNewCommentsCounters(): void
	{
		$muteCondition = $this->getMuteFilter();
		[$userTypeJoin, $userTypeFilter] = self::getUserTypeSqlParts();
		[$groupJoin, $groupFilter] = $this->getGroupSqlParts();

		$sql = "
			SELECT
				COUNT(DISTINCT FM.ID) AS COUNT,
				T.GROUP_ID,
				TM.TYPE
			FROM b_tasks T
				LEFT JOIN b_tasks_viewed TV ON TV.TASK_ID = T.ID AND TV.USER_ID = {$this->userId}
				INNER JOIN b_tasks_member TM ON TM.TASK_ID = T.ID AND TM.USER_ID = {$this->userId}
				LEFT JOIN b_tasks_member TM2 ON TM2.TASK_ID = T.ID AND TM2.USER_ID = {$this->userId} AND TM2.TYPE = 'R'
				INNER JOIN b_forum_message FM ON FM.TOPIC_ID = T.FORUM_TOPIC_ID
				{$userTypeJoin}
				{$groupJoin}
			WHERE
				NOT (TM.TYPE = 'O' AND TM2.TYPE IS NOT NULL AND TM2.TYPE = 'R')
				AND T.ZOMBIE = 'N'
				AND (
					(TV.VIEWED_DATE IS NOT NULL AND FM.POST_DATE > TV.VIEWED_DATE)
					OR (TV.VIEWED_DATE IS NULL AND FM.POST_DATE >= T.CREATED_DATE)
				)
				AND FM.NEW_TOPIC = 'N'
				AND FM.AUTHOR_ID <> {$this->userId}
				{$muteCondition}
				{$userTypeFilter}
				{$groupFilter}
				{$this->getCounterFilter()}
			GROUP BY T.GROUP_ID, TM.TYPE
		";

		$roles = [
			'R' => Name::MY_NEW_COMMENTS,
			'O' => Name::ORIGINATOR_NEW_COMMENTS,
			'A' => Name::ACCOMPLICES_NEW_COMMENTS,
			'U' => Name::AUDITOR_NEW_COMMENTS,
		];
		$counters = Application::getConnection()->query($sql)->fetchAll();

		$this->changeCountersByRoles($roles, $counters);
		$this->recount(Name::NEW_COMMENTS);
	}

	/**
	 * @throws Main\ArgumentException
	 * @throws Main\Db\SqlQueryException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function recountExpiredCounters(): void
	{
		$expiredTime = self::getExpiredTime()->format('Y-m-d H:i:s');
		$statuses = [CTasks::STATE_PENDING, CTasks::STATE_IN_PROGRESS];
		$statuses = implode(',', $statuses);
		$muteCondition = $this->getMuteFilter();
		[$groupJoin, $groupFilter] = $this->getGroupSqlParts();

		$sql = "
			SELECT
				COUNT(DISTINCT T.ID) AS COUNT,
				T.GROUP_ID,
				TM.TYPE
			FROM b_tasks T
				INNER JOIN b_tasks_member TM ON TM.TASK_ID = T.ID AND TM.USER_ID = {$this->userId}
				LEFT JOIN b_tasks_member TM2 ON TM2.TASK_ID = T.ID AND TM2.USER_ID = {$this->userId} AND TM2.TYPE = 'R'
				{$groupJoin}
			WHERE
				NOT (TM.TYPE = 'O' AND TM2.TYPE IS NOT NULL AND TM2.TYPE = 'R')
				AND T.DEADLINE < '{$expiredTime}'
				AND T.ZOMBIE = 'N'
				AND T.STATUS IN ({$statuses})
				{$muteCondition}
				{$groupFilter}
			GROUP BY T.GROUP_ID, TM.TYPE
		";

		$roles = [
			'R' => Name::MY_EXPIRED,
			'O' => Name::ORIGINATOR_EXPIRED,
			'A' => Name::ACCOMPLICES_EXPIRED,
			'U' => Name::AUDITOR_EXPIRED,
		];
		$counters = Application::getConnection()->query($sql)->fetchAll();

		$this->changeCountersByRoles($roles, $counters);
		$this->recount(Name::EXPIRED);
	}

	/**
	 * @param array $roles
	 * @param array $counters
	 */
	private function changeCountersByRoles(array $roles, array $counters): void
	{
		foreach ($roles as $role => $counterName)
		{
			$counterData = [];
			foreach ($counters as $roleCounter)
			{
				if ($roleCounter['TYPE'] === $role)
				{
					$counterData[] = [
						'GROUP_ID' => (int)$roleCounter['GROUP_ID'],
						'COUNT' => (int)$roleCounter['COUNT'],
					];
				}
			}
			$this->setCounter($counterName, $counterData);
		}
	}

	/**
	 * @param array $users
	 * @throws Main\ArgumentException
	 * @throws Main\DB\SqlQueryException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function sendPushCounters(array $users): void
	{
		if (!Loader::includeModule('pull'))
		{
			return;
		}

		$types = [
			Counter\Role::ALL,
			Counter\Role::RESPONSIBLE,
			Counter\Role::ORIGINATOR,
			Counter\Role::ACCOMPLICE,
			Counter\Role::AUDITOR,
		];

		foreach ($users as $userId)
		{
			$pushData = ['userId' => $userId];
			$counter = self::getInstance($userId);

			$groupIds = array_keys($counter->counters);
			foreach ($groupIds as $groupId)
			{
				$groupCounter = self::getInstance($userId, $groupId);

				foreach ($types as $type)
				{
					$data = $groupCounter->getCounters($type, ['SKIP_ACCESS_CHECK' => true]);
					foreach ($data as $key => $value)
					{
						$pushData[$groupId][$type][$key] = $value['counter'];
					}
				}
			}

			Event::add([$userId], [
				'module_id' => 'tasks',
				'command' => 'user_counter',
				'params' => $pushData,
			]);
		}
	}

	/**
	 * @return bool
	 */
	private function isAccessToCounters(): bool
	{
		return $this->userId === User::getId()
			|| User::isSuper()
			|| CTasks::IsSubordinate($this->userId, User::getId());
	}

	#region calculations

	/**
	 * @param $name
	 * @param $counters
	 */
	private function setCounter($name, $counters): void
	{
		$name = mb_strtoupper($name);
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

	/**
	 * @param bool $reCache
	 * @throws Main\Db\SqlQueryException
	 */
	private function calcNewComments($reCache = false): void
	{
		static $count = null;

		if ($count === null || $reCache)
		{
			$muteCondition = $this->getMuteFilter();
			[$userTypeJoin, $userTypeFilter] = self::getUserTypeSqlParts();
			[$groupJoin, $groupFilter] = $this->getGroupSqlParts();

			$sql = "
				SELECT
					COUNT(DISTINCT FM.ID) AS COUNT,
					T.GROUP_ID
				FROM b_tasks T
					LEFT JOIN b_tasks_viewed TV ON TV.TASK_ID = T.ID AND TV.USER_ID = {$this->userId}
					INNER JOIN b_tasks_member TM ON TM.TASK_ID = T.ID AND TM.USER_ID = {$this->userId}
					INNER JOIN b_forum_message FM ON FM.TOPIC_ID = T.FORUM_TOPIC_ID
					{$userTypeJoin}
					{$groupJoin}
				WHERE
					T.ZOMBIE = 'N'
					AND (
						(TV.VIEWED_DATE IS NOT NULL AND FM.POST_DATE > TV.VIEWED_DATE)
						OR (TV.VIEWED_DATE IS NULL AND FM.POST_DATE >= T.CREATED_DATE)
					)
					AND FM.NEW_TOPIC = 'N'
					AND FM.AUTHOR_ID <> {$this->userId}
					{$muteCondition}
					{$userTypeFilter}
					{$groupFilter}
					{$this->getCounterFilter()}
				GROUP BY T.GROUP_ID
			";

			$this->setCounter(
				Name::NEW_COMMENTS,
				Application::getConnection()->query($sql)->fetchAll()
			);
		}
	}

	/**
	 * @param bool $reCache
	 * @throws Main\Db\SqlQueryException
	 */
	private function calcMyNewComments($reCache = false): void
	{
		static $count = null;

		if ($count === null || $reCache)
		{
			$muteCondition = $this->getMuteFilter();
			[$userTypeJoin, $userTypeFilter] = self::getUserTypeSqlParts();
			[$groupJoin, $groupFilter] = $this->getGroupSqlParts();

			$sql = "
				SELECT
					COUNT(DISTINCT FM.ID) AS COUNT,
					T.GROUP_ID
				FROM b_tasks T
					LEFT JOIN b_tasks_viewed TV ON TV.TASK_ID = T.ID AND TV.USER_ID = {$this->userId}
					INNER JOIN b_tasks_member TM ON TM.TASK_ID = T.ID AND TM.USER_ID = {$this->userId} AND TM.TYPE = 'R'
					INNER JOIN b_forum_message FM ON FM.TOPIC_ID = T.FORUM_TOPIC_ID
					{$userTypeJoin}
					{$groupJoin}
				WHERE
					T.ZOMBIE = 'N'
					AND (
						(TV.VIEWED_DATE IS NOT NULL AND FM.POST_DATE > TV.VIEWED_DATE)
						OR (TV.VIEWED_DATE IS NULL AND FM.POST_DATE >= T.CREATED_DATE)
					)
					AND FM.NEW_TOPIC = 'N'
					AND FM.AUTHOR_ID <> {$this->userId}
					{$muteCondition}
					{$userTypeFilter}
					{$groupFilter}
					{$this->getCounterFilter()}
				GROUP BY T.GROUP_ID
			";

			$this->setCounter(
				Name::MY_NEW_COMMENTS,
				Application::getConnection()->query($sql)->fetchAll()
			);
		}
	}

	/**
	 * @param bool $reCache
	 * @throws Main\Db\SqlQueryException
	 */
	private function calcOriginatorNewComments($reCache = false): void
	{
		static $count = null;

		if ($count === null || $reCache)
		{
			$muteCondition = $this->getMuteFilter();
			[$userTypeJoin, $userTypeFilter] = self::getUserTypeSqlParts();
			[$groupJoin, $groupFilter] = $this->getGroupSqlParts();

			$sql = "
				SELECT
					COUNT(DISTINCT FM.ID) AS COUNT,
					T.GROUP_ID
				FROM b_tasks T
					LEFT JOIN b_tasks_viewed TV ON TV.TASK_ID = T.ID AND TV.USER_ID = {$this->userId}
					INNER JOIN b_tasks_member TM ON TM.TASK_ID = T.ID AND TM.USER_ID = {$this->userId} AND TM.TYPE = 'O'
					INNER JOIN b_forum_message FM ON FM.TOPIC_ID = T.FORUM_TOPIC_ID
					{$userTypeJoin}
					{$groupJoin}
				WHERE
					T.ZOMBIE = 'N'
					AND NOT EXISTS (
						SELECT 'x'
						FROM b_tasks_member
						WHERE TASK_ID = T.ID AND USER_ID = {$this->userId} AND TYPE = 'R'
					)
					AND (
						(TV.VIEWED_DATE IS NOT NULL AND FM.POST_DATE > TV.VIEWED_DATE)
						OR (TV.VIEWED_DATE IS NULL AND FM.POST_DATE >= T.CREATED_DATE)
					)
					AND FM.NEW_TOPIC = 'N'
					AND FM.AUTHOR_ID <> {$this->userId}
					{$muteCondition}
					{$userTypeFilter}
					{$groupFilter}
					{$this->getCounterFilter()}
				GROUP BY T.GROUP_ID
			";

			$this->setCounter(
				Name::ORIGINATOR_NEW_COMMENTS,
				Application::getConnection()->query($sql)->fetchAll()
			);
		}
	}

	/**
	 * @param bool $reCache
	 * @throws Main\Db\SqlQueryException
	 */
	private function calcAccomplicesNewComments($reCache = false): void
	{
		static $count = null;

		if ($count === null || $reCache)
		{
			$muteCondition = $this->getMuteFilter();
			[$userTypeJoin, $userTypeFilter] = self::getUserTypeSqlParts();
			[$groupJoin, $groupFilter] = $this->getGroupSqlParts();

			$sql = "
				SELECT
					COUNT(DISTINCT FM.ID) AS COUNT,
					T.GROUP_ID
				FROM b_tasks T
					LEFT JOIN b_tasks_viewed TV ON TV.TASK_ID = T.ID AND TV.USER_ID = {$this->userId}
					INNER JOIN b_tasks_member TM ON TM.TASK_ID = T.ID AND TM.USER_ID = {$this->userId} AND TM.TYPE = 'A'
					INNER JOIN b_forum_message FM ON FM.TOPIC_ID = T.FORUM_TOPIC_ID
					{$userTypeJoin}
					{$groupJoin}
				WHERE
					T.ZOMBIE = 'N'
					AND (
						(TV.VIEWED_DATE IS NOT NULL AND FM.POST_DATE > TV.VIEWED_DATE)
						OR (TV.VIEWED_DATE IS NULL AND FM.POST_DATE >= T.CREATED_DATE)
					)
					AND FM.NEW_TOPIC = 'N'
					AND FM.AUTHOR_ID <> {$this->userId}
					{$muteCondition}
					{$userTypeFilter}
					{$groupFilter}
					{$this->getCounterFilter()}
				GROUP BY T.GROUP_ID
			";

			$this->setCounter(
				Name::ACCOMPLICES_NEW_COMMENTS,
				Application::getConnection()->query($sql)->fetchAll()
			);
		}
	}

	/**
	 * @param bool $reCache
	 * @throws Main\Db\SqlQueryException
	 */
	private function calcAuditorNewComments($reCache = false): void
	{
		static $count = null;

		if ($count === null || $reCache)
		{
			$muteCondition = $this->getMuteFilter();
			[$userTypeJoin, $userTypeFilter] = self::getUserTypeSqlParts();
			[$groupJoin, $groupFilter] = $this->getGroupSqlParts();

			$sql = "
				SELECT
					COUNT(DISTINCT FM.ID) AS COUNT,
					T.GROUP_ID
				FROM b_tasks T
					LEFT JOIN b_tasks_viewed TV ON TV.TASK_ID = T.ID AND TV.USER_ID = {$this->userId}
					INNER JOIN b_tasks_member TM ON TM.TASK_ID = T.ID AND TM.USER_ID = {$this->userId} AND TM.TYPE = 'U'
					INNER JOIN b_forum_message FM ON FM.TOPIC_ID = T.FORUM_TOPIC_ID
					{$userTypeJoin}
					{$groupJoin}
				WHERE
					T.ZOMBIE = 'N'
					AND (
						(TV.VIEWED_DATE IS NOT NULL AND FM.POST_DATE > TV.VIEWED_DATE)
						OR (TV.VIEWED_DATE IS NULL AND FM.POST_DATE >= T.CREATED_DATE)
					)
					AND FM.NEW_TOPIC = 'N'
					AND FM.AUTHOR_ID <> {$this->userId}
					{$muteCondition}
					{$userTypeFilter}
					{$groupFilter}
					{$this->getCounterFilter()}
				GROUP BY T.GROUP_ID
			";

			$this->setCounter(
				Name::AUDITOR_NEW_COMMENTS,
				Application::getConnection()->query($sql)->fetchAll()
			);
		}
	}

	/**
	 * @param bool $reCache
	 * @throws Main\Db\SqlQueryException
	 */
	private function calcExpired($reCache = false): void
	{
		static $count = null;

		if ($count === null || $reCache)
		{
			$expiredTime = self::getExpiredTime()->format('Y-m-d H:i:s');
			$statuses = [CTasks::STATE_PENDING, CTasks::STATE_IN_PROGRESS];
			$statuses = implode(',', $statuses);
			$muteCondition = $this->getMuteFilter();
			[$groupJoin, $groupFilter] = $this->getGroupSqlParts();

			$sql = "
				SELECT
					COUNT(DISTINCT T.ID) AS COUNT,
					T.GROUP_ID
				FROM b_tasks T
					INNER JOIN b_tasks_member TM ON TM.TASK_ID = T.ID AND TM.USER_ID = {$this->userId}
					{$groupJoin}
				WHERE
					T.DEADLINE < '{$expiredTime}'
					AND T.ZOMBIE = 'N'
					AND T.STATUS IN ({$statuses})
					{$muteCondition}
					{$groupFilter}
				GROUP BY T.GROUP_ID
			";

			$this->setCounter(
				Name::EXPIRED,
				Application::getConnection()->query($sql)->fetchAll()
			);
		}
	}

	/**
	 * @param bool $reCache
	 * @throws Main\Db\SqlQueryException
	 */
	private function calcMyExpired($reCache = false): void
	{
		static $count = null;

		if ($count === null || $reCache)
		{
			$expiredTime = self::getExpiredTime()->format('Y-m-d H:i:s');
			$statuses = [CTasks::STATE_PENDING, CTasks::STATE_IN_PROGRESS];
			$statuses = implode(',', $statuses);
			$muteCondition = $this->getMuteFilter();
			[$groupJoin, $groupFilter] = $this->getGroupSqlParts();

			$sql = "
				SELECT
					COUNT(DISTINCT T.ID) AS COUNT,
					T.GROUP_ID
				FROM b_tasks T
					INNER JOIN b_tasks_member TM ON TM.TASK_ID = T.ID AND TM.USER_ID = {$this->userId} AND TM.TYPE = 'R'
					{$groupJoin}
				WHERE
					T.DEADLINE < '{$expiredTime}'
					AND T.ZOMBIE = 'N'
					AND T.STATUS IN ({$statuses})
					{$muteCondition}
					{$groupFilter}
				GROUP BY T.GROUP_ID
			";

			$this->setCounter(
				Name::MY_EXPIRED,
				Application::getConnection()->query($sql)->fetchAll()
			);
		}
	}

	/**
	 * @param bool $reCache
	 * @throws Main\Db\SqlQueryException
	 */
	private function calcOriginatorExpired($reCache = false): void
	{
		static $count = null;

		if ($count === null || $reCache)
		{
			$expiredTime = self::getExpiredTime()->format('Y-m-d H:i:s');
			$statuses = [CTasks::STATE_PENDING, CTasks::STATE_IN_PROGRESS];
			$statuses = implode(',', $statuses);
			$muteCondition = $this->getMuteFilter();
			[$groupJoin, $groupFilter] = $this->getGroupSqlParts();

			$sql = "
				SELECT
					COUNT(DISTINCT T.ID) AS COUNT,
					T.GROUP_ID
				FROM b_tasks T
					INNER JOIN b_tasks_member TM ON TM.TASK_ID = T.ID AND TM.USER_ID = {$this->userId} AND TM.TYPE = 'O'
					{$groupJoin}
				WHERE
					T.DEADLINE < '{$expiredTime}'
					AND T.ZOMBIE = 'N'
					AND T.STATUS IN ({$statuses})
					AND NOT EXISTS (
						SELECT 'x'
						FROM b_tasks_member
						WHERE TASK_ID = T.ID AND USER_ID = {$this->userId} AND TYPE = 'R'
					)
					{$muteCondition}
					{$groupFilter}
				GROUP BY T.GROUP_ID
			";

			$this->setCounter(
				Name::ORIGINATOR_EXPIRED,
				Application::getConnection()->query($sql)->fetchAll()
			);
		}
	}

	/**
	 * @param bool $reCache
	 * @throws Main\Db\SqlQueryException
	 */
	private function calcAccomplicesExpired($reCache = false): void
	{
		static $count = null;

		if ($count === null || $reCache)
		{
			$expiredTime = self::getExpiredTime()->format('Y-m-d H:i:s');
			$statuses = [CTasks::STATE_PENDING, CTasks::STATE_IN_PROGRESS];
			$statuses = implode(',', $statuses);
			$muteCondition = $this->getMuteFilter();
			[$groupJoin, $groupFilter] = $this->getGroupSqlParts();

			$sql = "
				SELECT
					COUNT(DISTINCT T.ID) AS COUNT,
					T.GROUP_ID
				FROM b_tasks T
					INNER JOIN b_tasks_member TM ON TM.TASK_ID = T.ID AND TM.USER_ID = {$this->userId} AND TM.TYPE = 'A'
					{$groupJoin}
				WHERE
					T.DEADLINE < '{$expiredTime}'
					AND T.ZOMBIE = 'N'
					AND T.STATUS IN ({$statuses})
					{$muteCondition}
					{$groupFilter}
				GROUP BY T.GROUP_ID
			";

			$this->setCounter(
				Name::ACCOMPLICES_EXPIRED,
				Application::getConnection()->query($sql)->fetchAll()
			);
		}
	}

	/**
	 * @param bool $reCache
	 * @throws Main\Db\SqlQueryException
	 */
	private function calcAuditorExpired($reCache = false): void
	{
		static $count = null;

		if ($count === null || $reCache)
		{
			$expiredTime = self::getExpiredTime()->format('Y-m-d H:i:s');
			$statuses = [CTasks::STATE_PENDING, CTasks::STATE_IN_PROGRESS];
			$statuses = implode(',', $statuses);
			$muteCondition = $this->getMuteFilter();
			[$groupJoin, $groupFilter] = $this->getGroupSqlParts();

			$sql = "
				SELECT
					COUNT(DISTINCT T.ID) AS COUNT,
					T.GROUP_ID
				FROM b_tasks T
					INNER JOIN b_tasks_member TM ON TM.TASK_ID = T.ID AND TM.USER_ID = {$this->userId} AND TM.TYPE = 'U'
					{$groupJoin}
				WHERE
					T.DEADLINE < '{$expiredTime}'
					AND T.ZOMBIE = 'N'
					AND T.STATUS IN ({$statuses})
					{$muteCondition}
					{$groupFilter}
				GROUP BY T.GROUP_ID
			";

			$this->setCounter(
				Name::AUDITOR_EXPIRED,
				Application::getConnection()->query($sql)->fetchAll()
			);
		}
	}

	#endregion
}