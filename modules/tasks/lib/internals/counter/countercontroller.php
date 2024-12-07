<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Internals\Counter;

use Bitrix\Main;
use Bitrix\Tasks\Integration\Socialnetwork\SpaceService;
use Bitrix\Tasks\Internals\Counter;
use Bitrix\Tasks\Internals\Counter\Processor\CommandTrait;
use Bitrix\Tasks\Internals\Counter\Processor\ProjectProcessor;
use Bitrix\Tasks\Internals\Counter\Processor\UserProcessor;
use Bitrix\Tasks\Internals\Counter\Push\PushSender;
use Bitrix\Tasks\Internals\Registry\UserRegistry;

class CounterController
{
	use CommandTrait;

	private const STEP_LIMIT = 2000;

	private $userId;

	/**
	 * @param int $userId
	 * @throws Main\Db\SqlQueryException
	 * @throws \Bitrix\Tasks\Internals\Counter\Exception\UnknownCounterException
	 */
	public static function recountForUser(int $userId): void
	{
		(new self($userId))->recount(CounterDictionary::COUNTER_EXPIRED);
		(new PushSender())->sendUserCounters([$userId]);
	}

	public static function getStepLimit(): int
	{
		return (int)Main\Config\Option::get('tasks', 'tasks_counter_step_limit', self::STEP_LIMIT);
	}

	/**
	 * CounterBroker constructor.
	 * @param int $userId
	 */
	public function __construct(int $userId = 0)
	{
		$this->userId = $userId;
	}

	/**
	 * @param string $counter
	 * @param array $taskIds
	 * @param array $groupIds
	 * @throws Exception\UnknownCounterException
	 * @throws Main\Db\SqlQueryException
	 */
	public function recount(string $counter, array $taskIds = [], array $groupIds = []): void
	{
		$projectCounters = [
			CounterDictionary::COUNTER_GROUP_COMMENTS,
			CounterDictionary::COUNTER_GROUP_EXPIRED,
		];

		if (in_array($counter, $projectCounters))
		{
			if (Counter::isSonetEnable())
			{
				ProjectProcessor::getInstance()->recount($counter, $this->userId, $taskIds, $groupIds);
			}
		}
		elseif($this->userId)
		{
			UserProcessor::getInstance($this->userId)->recount($counter, $taskIds);
		}
	}

	/**
	 *
	 */
	public function recountAll(): void
	{
		if (!$this->userId)
		{
			return;
		}

		self::reset($this->userId);

		$userProcessor = UserProcessor::getInstance($this->userId);
		$userProcessor->recount(CounterDictionary::COUNTER_EXPIRED);
		$userProcessor->recount(CounterDictionary::COUNTER_NEW_COMMENTS);

		if (Counter::isSonetEnable())
		{
			$projectProcessor = ProjectProcessor::getInstance();
			$projectProcessor->recount(CounterDictionary::COUNTER_GROUP_EXPIRED, $this->userId);
			$projectProcessor->recount(CounterDictionary::COUNTER_GROUP_COMMENTS, $this->userId);
		}

		$this->saveFlag($this->userId);
	}

	/**
	 * @param string|null $role
	 */
	public function readAll(int $groupId = 0, string $role = null): void
	{
		if (!$this->userId)
		{
			return;
		}
		UserProcessor::getInstance($this->userId)->readAll($groupId, $role);
	}

	/**
	 * @param int $groupId
	 * @throws Main\DB\SqlQueryException
	 */
	public function readProject(int $groupId = 0): void
	{
		if (!$this->userId)
		{
			return;
		}

		$groupIds = array_keys(UserRegistry::getInstance($this->userId)->getUserGroups(UserRegistry::MODE_EXCLUDE_SCRAM));
		if ($groupId > 0)
		{
			$groupIds = [$groupId];
		}

		ProjectProcessor::getInstance()->readAll($this->userId, $groupIds);
	}

	/**
	 * @param int $groupId
	 * @throws Main\DB\SqlQueryException
	 */
	public function readScrum(int $groupId = 0): void
	{
		if (!$this->userId)
		{
			return;
		}

		$groupIds = array_keys(UserRegistry::getInstance($this->userId)->getUserGroups(UserRegistry::MODE_SCRUM));
		if ($groupId > 0)
		{
			$groupIds = [$groupId];
		}

		ProjectProcessor::getInstance()->readAll($this->userId, $groupIds);
	}

	/**
	 * @param array $tasks
	 */
	public function deleteTasks(array $taskIds, array $memberIds): void
	{
		self::reset(0, [], $taskIds);

		foreach ($memberIds as $memberId)
		{
			Counter\State\Factory::reloadState($memberId);
		}
	}

	/**
	 * @throws Main\ArgumentException
	 * @throws Main\DB\SqlQueryException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function updateInOptionCounter()
	{
		$value = Counter::getInstance($this->userId)->get(CounterDictionary::COUNTER_MEMBER_TOTAL);
		if (!$this->isSameValueCached($value))
		{
			(new SpaceService())->addEvent(
				Counter\Event\EventDictionary::EVENT_TOTAL_COUNTER_UPDATED,
				[
					'USER_ID' => $this->userId,
				]
			);

			\CUserCounter::Set(
				$this->userId,
				CounterDictionary::LEFT_MENU_TASKS,
				$value,
				'**',
				'',
				false
			);
		}
	}

	private function isSameValueCached(int $value): bool
	{
		global $CACHE_MANAGER;

		$cache = $CACHE_MANAGER->Get('user_counter' . $this->userId);
		if (!$cache)
		{
			return false;
		}

		foreach ($cache as $item)
		{
			if (
				$item['CODE'] === CounterDictionary::LEFT_MENU_TASKS
				&& $item['SITE_ID'] === '**'
				&& (int)$item['CNT'] === $value
			)
			{
				return true;
			}
		}

		return false;
	}
}