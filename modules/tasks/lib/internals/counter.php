<?php

namespace Bitrix\Tasks\Internals;

use Bitrix\Main;
use Bitrix\Tasks\Internals\Counter\CounterDictionary;
use Bitrix\Tasks\Internals\Counter\CounterProcessor;
use Bitrix\Tasks\Internals\Counter\CounterService;
use Bitrix\Tasks\Internals\Counter\CounterState;
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
	private static $instance;

	private $userId;
	private $processor;

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
		if (
			!self::$instance
			|| !array_key_exists($userId, self::$instance)
		)
		{
			self::$instance[$userId] = new self($userId);
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
			$this->getProcessor()->recountAll();
		}

		CounterService::getInstance();
	}

	/**
	 * @return array
	 */
	public function getRawCounters(): array
	{
		return $this->getState()->getRawCounters();
	}

	/**
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
						'counter' => $this->get(CounterDictionary::COUNTER_TOTAL, $groupId),
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
		switch ($name)
		{
			case CounterDictionary::COUNTER_TOTAL:
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

			case CounterDictionary::COUNTER_EFFECTIVE:
				$value = $this->getKpi();
				break;

			default:
				$value = $this->getState()->getValue($name, $groupId);
				break;
		}

		return $value;
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
				$res[$row['TASK_ID']] = $row['VALUE'];
			}
		}

		return $res;
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
	 * @return CounterProcessor
	 */
	private function getProcessor(): CounterProcessor
	{
		if (!$this->processor)
		{
			$this->processor = new CounterProcessor($this->userId);
		}
		return $this->processor;
	}

	/**
	 * @return CounterState
	 */
	private function getState(): CounterState
	{
		return CounterState::getInstance($this->userId);
	}
}