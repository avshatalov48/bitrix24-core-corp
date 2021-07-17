<?php

namespace Bitrix\Tasks\Internals;

use Bitrix\Main;
use Bitrix\Socialnetwork\UserToGroupTable;
use Bitrix\Tasks\Internals\Counter\CounterDictionary;
use Bitrix\Tasks\Internals\Counter\CounterController;
use Bitrix\Tasks\Internals\Counter\CounterService;
use Bitrix\Tasks\Internals\Counter\CounterState;
use Bitrix\Tasks\Internals\Registry\UserRegistry;
use Bitrix\Tasks\Util\User;
use CTasks;
use Bitrix\Tasks\Util\Collection;

/**
 * Class Counter
 *
 * @package Bitrix\Tasks\Internals
 */
class Counter
{
	private static $instance = [];

	private $userId;
	private $taskCounters;

	/**
	 * @param $userId
	 * @return bool
	 */
	public static function isReady($userId): bool
	{
		return array_key_exists($userId, self::$instance);
	}

	/**
	 * @return bool
	 */
	public static function isSonetEnable(): bool
	{
		return !\COption::GetOptionString("tasks", "tasksSonetCountersDisable", 0);
	}

	/**
	 * @return int
	 */
	public static function getGlobalLimit(): ?int
	{
		$limit = \COption::GetOptionString("tasks", "tasksCounterLimit", "");
		if ($limit === "")
		{
			return null;
		}
		return (int)$limit;
	}

	/**
	 * @param $userId
	 * @return static
	 * @throws Main\ArgumentException
	 * @throws Main\DB\SqlQueryException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function getInstance($userId): self
	{
		if (!array_key_exists($userId, self::$instance))
		{
			self::$instance[$userId] = new self($userId);
			(new CounterController($userId))->updateInOptionCounter();
		}

		return self::$instance[$userId];
	}

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
	private function __construct($userId)
	{
		$this->userId = (int)$userId;

		if ($this->userId && !$this->getState()->isCounted())
		{
			(new CounterController($this->userId))->recountAll();
		}

		CounterService::getInstance();
	}

	/**
	 * @return array
	 */
	public function getRawCounters(string $meta = CounterDictionary::META_PROP_ALL): array
	{
		return $this->getState()->getRawCounters($meta);
	}

	/**
	 * @deprecated since tasks 20.800.0
	 *
	 * @param Collection $counterNamesCollection
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 *
	 * Kept due to backward compatibility
	 */
	public function processRecalculate(Collection $counterNamesCollection): void
	{

	}

	/**
	 * @param $role
	 * @param array $params
	 * @return array|array[]
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function getCounters($role, int $groupId = 0, $params = []): array
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
						'counter' => $this->get(CounterDictionary::COUNTER_MEMBER_TOTAL, $groupId),
						'code' => '',
					],
					'expired' => [
						'counter' => $this->get(CounterDictionary::COUNTER_EXPIRED, $groupId),
						'code' => Counter\Type::TYPE_EXPIRED,
					],
					'new_comments' => [
						'counter' => $this->get(CounterDictionary::COUNTER_NEW_COMMENTS, $groupId),
						'code' => Counter\Type::TYPE_NEW_COMMENTS,
					],
				];
				break;

			case Counter\Role::RESPONSIBLE:
				$counters = [
					'total' => [
						'counter' => $this->get(CounterDictionary::COUNTER_MY, $groupId),
						'code' => '',
					],
					'expired' => [
						'counter' => $this->get(CounterDictionary::COUNTER_MY_EXPIRED, $groupId),
						'code' => Counter\Type::TYPE_EXPIRED,
					],
					'new_comments' => [
						'counter' => $this->get(CounterDictionary::COUNTER_MY_NEW_COMMENTS, $groupId),
						'code' => Counter\Type::TYPE_NEW_COMMENTS,
					],
				];
				break;

			case Counter\Role::ORIGINATOR:
				$counters = [
					'total' => [
						'counter' => $this->get(CounterDictionary::COUNTER_ORIGINATOR, $groupId),
						'code' => '',
					],
					'expired' => [
						'counter' => $this->get(CounterDictionary::COUNTER_ORIGINATOR_EXPIRED, $groupId),
						'code' => Counter\Type::TYPE_EXPIRED,
					],
					'new_comments' => [
						'counter' => $this->get(CounterDictionary::COUNTER_ORIGINATOR_NEW_COMMENTS, $groupId),
						'code' => Counter\Type::TYPE_NEW_COMMENTS,
					],
				];
				break;

			case Counter\Role::ACCOMPLICE:
				$counters = [
					'total' => [
						'counter' => $this->get(CounterDictionary::COUNTER_ACCOMPLICES, $groupId),
						'code' => '',
					],
					'expired' => [
						'counter' => $this->get(CounterDictionary::COUNTER_ACCOMPLICES_EXPIRED, $groupId),
						'code' => Counter\Type::TYPE_EXPIRED,
					],
					'new_comments' => [
						'counter' => $this->get(CounterDictionary::COUNTER_ACCOMPLICES_NEW_COMMENTS, $groupId),
						'code' => Counter\Type::TYPE_NEW_COMMENTS,
					],
				];
				break;

			case Counter\Role::AUDITOR:
				$counters = [
					'total' => [
						'counter' => $this->get(CounterDictionary::COUNTER_AUDITOR, $groupId),
						'code' => '',
					],
					'expired' => [
						'counter' => $this->get(CounterDictionary::COUNTER_AUDITOR_EXPIRED, $groupId),
						'code' => Counter\Type::TYPE_EXPIRED,
					],
					'new_comments' => [
						'counter' => $this->get(CounterDictionary::COUNTER_AUDITOR_NEW_COMMENTS, $groupId),
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
	public function get($name, int $groupId = 0)
	{
		$value = 0;

		switch ($name)
		{
			case CounterDictionary::COUNTER_TOTAL:
				$value = $this->get(CounterDictionary::COUNTER_EXPIRED, $groupId)
					+ $this->get(CounterDictionary::COUNTER_NEW_COMMENTS, $groupId)
					+ $this->getMajorForeignExpired();
				break;

			case CounterDictionary::COUNTER_MEMBER_TOTAL:
				$value = $this->get(CounterDictionary::COUNTER_EXPIRED, $groupId)
					+ $this->get(CounterDictionary::COUNTER_NEW_COMMENTS, $groupId);
				break;

			case CounterDictionary::COUNTER_MY:
				$value = $this->get(CounterDictionary::COUNTER_MY_EXPIRED, $groupId)
					+ $this->get(CounterDictionary::COUNTER_MY_NEW_COMMENTS, $groupId);
				break;

			case CounterDictionary::COUNTER_ORIGINATOR:
				$value = $this->get(CounterDictionary::COUNTER_ORIGINATOR_EXPIRED, $groupId)
					+ $this->get(CounterDictionary::COUNTER_ORIGINATOR_NEW_COMMENTS, $groupId);
				break;

			case CounterDictionary::COUNTER_ACCOMPLICES:
				$value = $this->get(CounterDictionary::COUNTER_ACCOMPLICES_EXPIRED, $groupId)
					+ $this->get(CounterDictionary::COUNTER_ACCOMPLICES_NEW_COMMENTS, $groupId);
				break;

			case CounterDictionary::COUNTER_AUDITOR:
				$value = $this->get(CounterDictionary::COUNTER_AUDITOR_EXPIRED, $groupId)
					+ $this->get(CounterDictionary::COUNTER_AUDITOR_NEW_COMMENTS, $groupId);
				break;

			case CounterDictionary::COUNTER_GROUP_EXPIRED:
				if ($groupId && self::isSonetEnable())
				{
					$value = $this->getState()->getValue(CounterDictionary::COUNTER_GROUP_EXPIRED, $groupId)
						- $this->getState()->getValue(CounterDictionary::COUNTER_EXPIRED, $groupId);
					$value = ($value > 0) ? $value : 0;
				}
				break;

			case CounterDictionary::COUNTER_GROUP_COMMENTS:
				if ($groupId && self::isSonetEnable())
				{
					$value = $this->getState()->getValue(CounterDictionary::COUNTER_GROUP_COMMENTS, $groupId)
						- $this->getState()->getValue(CounterDictionary::COUNTER_NEW_COMMENTS, $groupId);
					$value = ($value > 0) ? $value : 0;
				}
				break;

			case CounterDictionary::COUNTER_EFFECTIVE:
				$value = $this->getKpi();
				break;

			case CounterDictionary::COUNTER_PROJECTS_TOTAL_EXPIRED:
			case CounterDictionary::COUNTER_PROJECTS_TOTAL_COMMENTS:
			case CounterDictionary::COUNTER_PROJECTS_FOREIGN_EXPIRED:
			case CounterDictionary::COUNTER_PROJECTS_FOREIGN_COMMENTS:
				if (self::isSonetEnable())
				{
					$counters = $this->getRawCounters(CounterDictionary::META_PROP_PROJECT);
					$type = CounterDictionary::MAP_SONET_TOTAL[$name];
					$value = (isset($counters[$type][0]) && $counters[$type][0]) ? $counters[$type][0] : 0;
				}
				break;

			case CounterDictionary::COUNTER_GROUPS_TOTAL_EXPIRED:
			case CounterDictionary::COUNTER_GROUPS_TOTAL_COMMENTS:
			case CounterDictionary::COUNTER_GROUPS_FOREIGN_EXPIRED:
			case CounterDictionary::COUNTER_GROUPS_FOREIGN_COMMENTS:
				if (self::isSonetEnable())
				{
					$counters = $this->getRawCounters(CounterDictionary::META_PROP_GROUP);
					$type = CounterDictionary::MAP_SONET_TOTAL[$name];
					$value = (isset($counters[$type][0]) && $counters[$type][0]) ? $counters[$type][0] : 0;
				}
				break;

			case CounterDictionary::COUNTER_SONET_TOTAL_EXPIRED:
			case CounterDictionary::COUNTER_SONET_TOTAL_COMMENTS:
			case CounterDictionary::COUNTER_SONET_FOREIGN_EXPIRED:
			case CounterDictionary::COUNTER_SONET_FOREIGN_COMMENTS:
				if (self::isSonetEnable())
				{
					$counters = $this->getRawCounters();
					$type = CounterDictionary::MAP_SONET_TOTAL[$name];
					$value = (isset($counters[$type][0]) && $counters[$type][0]) ? $counters[$type][0] : 0;
				}
				break;

			case CounterDictionary::COUNTER_PROJECTS_MAJOR:
				if (self::isSonetEnable())
				{
					$value = $this->get(CounterDictionary::COUNTER_SONET_TOTAL_EXPIRED)
						+ $this->get(CounterDictionary::COUNTER_SONET_TOTAL_COMMENTS)
						+ $this->getMajorForeignExpired();
				}
				break;

			default:
				$value = $this->getState()->getValue($name, $groupId);
				break;
		}

		return $value;
	}

	/**
	 * @param int $taskId
	 * @return array
	 */
	public function getTaskCounters(int $taskId): ?array
	{
		if (!is_null($this->taskCounters))
		{
			return array_key_exists($taskId, $this->taskCounters) ? $this->taskCounters[$taskId] : null;
		}

		$counters = [];

		foreach ($this->getState() as $row)
		{
			$id = $row['TASK_ID'];
			if (!$taskId)
			{
				continue;
			}

			$type = $row['TYPE'];
			$value = (int)$row['VALUE'];

			if (!array_key_exists($id, $counters))
			{
				$counters[$id] = [
					CounterDictionary::COUNTER_MY_NEW_COMMENTS => 0,
					CounterDictionary::COUNTER_MY_EXPIRED => 0,
					CounterDictionary::COUNTER_MY_MUTED_EXPIRED => 0,
					CounterDictionary::COUNTER_MY_MUTED_NEW_COMMENTS => 0,
					CounterDictionary::COUNTER_GROUP_EXPIRED => 0,
					CounterDictionary::COUNTER_GROUP_COMMENTS => 0,
				];
			}

			if (in_array($type, CounterDictionary::MAP_COMMENTS))
			{
				$counters[$id][CounterDictionary::COUNTER_MY_NEW_COMMENTS] = $value;
			}

			if (in_array($type, CounterDictionary::MAP_MUTED_COMMENTS))
			{
				$counters[$id][CounterDictionary::COUNTER_MY_MUTED_NEW_COMMENTS] = $value;
			}

			if (in_array($type, CounterDictionary::MAP_EXPIRED))
			{
				$counters[$id][CounterDictionary::COUNTER_MY_EXPIRED] = $value;
			}

			if (in_array($type, CounterDictionary::MAP_MUTED_EXPIRED))
			{
				$counters[$id][CounterDictionary::COUNTER_MY_MUTED_EXPIRED] = $value;
			}

			if (in_array($type, [
				CounterDictionary::COUNTER_GROUP_COMMENTS,
				CounterDictionary::COUNTER_GROUP_EXPIRED
			]))
			{
				$counters[$id][$type] = $value;
			}
		}

		foreach ($counters as $id => $values)
		{
			$projectExpired = $values[CounterDictionary::COUNTER_GROUP_EXPIRED] - $values[CounterDictionary::COUNTER_MY_EXPIRED];
			$counters[$id][CounterDictionary::COUNTER_GROUP_EXPIRED] = ($projectExpired > 0) ? $projectExpired : 0;

			$projectComments = $values[CounterDictionary::COUNTER_GROUP_COMMENTS] - $values[CounterDictionary::COUNTER_MY_NEW_COMMENTS];
			$counters[$id][CounterDictionary::COUNTER_GROUP_COMMENTS] = ($projectComments > 0) ? $projectComments : 0;
		}

		$this->taskCounters = $counters;

		return array_key_exists($taskId, $this->taskCounters) ? $this->taskCounters[$taskId] : null;
	}

	/**
	 * @param array $taskIds
	 * @return array
	 */
	public function getCommentsCount(array $taskIds): array
	{
		$res = array_fill_keys($taskIds, 0);

		foreach ($this->getState() as $row)
		{
			if (!in_array($row['TASK_ID'], $taskIds))
			{
				continue;
			}
			if (
				in_array($row['TYPE'], CounterDictionary::MAP_COMMENTS)
				|| in_array($row['TYPE'], CounterDictionary::MAP_MUTED_COMMENTS)
			)
			{
				$res[$row['TASK_ID']] = (int)$row['VALUE'];
			}
		}

		return $res;
	}

	/**
	 * @return bool
	 */
	public function hasMajorForeignExpired(string $mode = CounterDictionary::META_PROP_SONET): bool
	{
		if (
			!self::isSonetEnable()
			|| !Main\Loader::includeModule('socialnetwork')
		)
		{
			return false;
		}

		$registryMode = $this->getGroupMode($mode);

		$userGroups = UserRegistry::getInstance($this->userId)->getUserGroups($registryMode);
		foreach ($userGroups as $groupId => $role)
		{
			if (!in_array($role, [UserToGroupTable::ROLE_OWNER, UserToGroupTable::ROLE_MODERATOR]))
			{
				continue;
			}

			if ($this->get(CounterDictionary::COUNTER_GROUP_EXPIRED, $groupId))
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * @return int
	 */
	private function getMajorForeignExpired(string $mode = CounterDictionary::META_PROP_SONET): int
	{
		$value = 0;

		if (
			!self::isSonetEnable()
			|| !Main\Loader::includeModule('socialnetwork')
		)
		{
			return $value;
		}

		$registryMode = $this->getGroupMode($mode);

		$userGroups = UserRegistry::getInstance($this->userId)->getUserGroups($registryMode);
		foreach ($userGroups as $groupId => $role)
		{
			if (!in_array($role, [UserToGroupTable::ROLE_OWNER, UserToGroupTable::ROLE_MODERATOR]))
			{
				continue;
			}

			$value += $this->get(CounterDictionary::COUNTER_GROUP_EXPIRED, $groupId);
		}

		return $value;
	}

	/**
	 * @param string $mode
	 * @return string
	 */
	private function getGroupMode(string $mode): string
	{
		$registryMode = UserRegistry::MODE_GROUP_ALL;
		if ($mode === CounterDictionary::META_PROP_PROJECT)
		{
			$registryMode = UserRegistry::MODE_PROJECT;
		}
		elseif ($mode === CounterDictionary::META_PROP_GROUP)
		{
			$registryMode = UserRegistry::MODE_GROUP;
		}

		return $registryMode;
	}

	/**
	 * @param array $counters
	 * @param string $type
	 * @param array $groupIds
	 * @return int
	 */
	private function partialSum(array $counters, string $type, array $groupIds): int
	{
		$sum = 0;

		if (!array_key_exists($type, $counters))
		{
			return $sum;
		}

		foreach ($groupIds as $groupId)
		{
			if (isset($counters[$type][$groupId]))
			{
				$sum += $counters[$type][$groupId];
			}
		}

		return $sum;
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
	 * @return CounterState
	 */
	private function getState(): CounterState
	{
		return CounterState::getInstance($this->userId);
	}
}