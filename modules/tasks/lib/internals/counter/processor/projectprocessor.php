<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Internals\Counter\Processor;


use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Socialnetwork\Integration\UI\EntitySelector\ProjectProvider;
use Bitrix\Socialnetwork\UserToGroupTable;
use Bitrix\Socialnetwork\WorkgroupTable;
use Bitrix\Tasks\Internals\Counter\Collector\ProjectCollector;
use Bitrix\Tasks\Internals\Counter\CounterDictionary;
use Bitrix\Tasks\Internals\Counter\CounterState;
use Bitrix\Tasks\Internals\Counter\CounterTable;
use Bitrix\Tasks\Internals\Counter\Exception\UnknownCounterException;
use Bitrix\Tasks\Internals\Counter\Provider\GroupProvider;
use Bitrix\Tasks\Internals\Counter;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;

class ProjectProcessor
{
	use CommandTrait;

	private static $instance;

	private $userGroups = [];

	public static function getInstance()
	{
		if (!self::$instance)
		{
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 *
	 */
	private function __construct()
	{
	}

	/**
	 * @param int $userId
	 * @param array $groupIds
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 */
	public function readAll(int $userId = 0, array $groupIds = [])
	{
		$where = [];

		if ($userId)
		{
			$where[] = 'USER_ID = ' . $userId;
		}

		if (!empty($groupIds))
		{
			$where[] = "GROUP_ID IN (". implode(",", $groupIds) .")";
		}
		else
		{
			$where[] = "GROUP_ID > 0";
		}

		$types = array_merge(
			array_values(CounterDictionary::MAP_COMMENTS),
			array_values(CounterDictionary::MAP_MUTED_COMMENTS),
			[CounterDictionary::COUNTER_GROUP_COMMENTS]
		);
		$where[] = "TYPE IN ('". implode("','", $types) ."')";

		$where = (!empty($where)) ? ('WHERE ' . implode(' AND ', $where)) : '';

		$sql = "
			DELETE
			FROM ". CounterTable::getTableName(). "
			{$where}
		";

		Application::getConnection()->query($sql);
	}

	/**
	 * @param string $counter
	 * @param int $userId
	 * @param array $taskIds
	 * @param array $groupIds
	 */
	public function recount(string $counter, int $userId = 0, array $taskIds = [], array $groupIds = [])
	{
		$this->checkCounter($counter);

		if (!Loader::includeModule('socialnetwork'))
		{
			return;
		}

		if ($userId)
		{
			$counters = $this->recountForUser($counter, $userId, $taskIds, $groupIds);
		}
		elseif (!empty($taskIds))
		{
			$counters = $this->recountForTasks($counter, $taskIds);
		}
		elseif (!empty($groupIds))
		{
			$counters = $this->recountForProjects($counter, $groupIds);
		}
		else
		{
			return;
		}

		self::reset($userId, [$counter], $taskIds, $groupIds);
		$this->batchInsert($counters);

		Counter\State\Factory::getState($userId)->updateState($counters, [$counter], $taskIds);
	}

	/**
	 * @param string $counter
	 * @param int $userId
	 * @param array $taskIds
	 * @param array $groupIds
	 * @return array
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 */
	private function recountForUser(string $counter, int $userId, array $taskIds = [], array $groupIds = []): array
	{
		$allowedGroupIds = $this->getUserGroups($userId);

		if (!empty($taskIds))
		{
			$groupIds = $this->getTasksGroups($taskIds);
		}

		if (!empty($groupIds))
		{
			$groupIds = array_intersect($allowedGroupIds, $groupIds);
		}
		else
		{
			$groupIds = $allowedGroupIds;
		}

		return (new ProjectCollector())->recount($counter, [$userId], $taskIds, $groupIds);
	}

	/**
	 * @param string $counter
	 * @param array $taskIds
	 * @param array $groupIds
	 * @return array
	 */
	private function recountForTasks(string $counter, array $taskIds): array
	{
		$groupIds = $this->getTasksGroups($taskIds);
		if (empty($groupIds))
		{
			return [];
		}

		$counters = [];
		$collector = new ProjectCollector();

		$groupUsers = GroupProvider::getInstance()->getGroupUsers($groupIds);
		foreach ($groupUsers as $groupId => $userIds)
		{
			if (empty($userIds))
			{
				continue;
			}
			$counters = array_merge($counters, $collector->recount($counter, $userIds, $taskIds, [$groupId]));
		}

		return $counters;
	}

	/**
	 * @param string $counter
	 * @param array $groupIds
	 * @return array
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 * @throws \Bitrix\Main\LoaderException
	 */
	private function recountForProjects(string $counter, array $groupIds): array
	{
		$counters = [];
		$collector = new ProjectCollector();

		$groupUsers = GroupProvider::getInstance()->getGroupUsers($groupIds);
		foreach ($groupUsers as $groupId => $userIds)
		{
			if (empty($userIds))
			{
				continue;
			}
			$counters = array_merge($counters, $collector->recount($counter, $userIds, [], [$groupId]));
		}

		return $counters;
	}

	/**
	 * @param string $counter
	 * @throws UnknownCounterException
	 */
	private function checkCounter(string $counter)
	{
		if (!in_array($counter, [CounterDictionary::COUNTER_GROUP_EXPIRED, CounterDictionary::COUNTER_GROUP_COMMENTS]))
		{
			throw new UnknownCounterException();
		}
	}

	/**
	 * @param int $userId
	 * @return array
	 */
	private function getUserGroups(int $userId): array
	{
		if (array_key_exists($userId, $this->userGroups))
		{
			return $this->userGroups[$userId];
		}

		$query = WorkgroupTable::query();
		$query->setSelect(['ID']);
		$query->registerRuntimeField(
			new Reference(
				'MY_PROJECT',
				UserToGroupTable::class,
				Join::on('this.ID', 'ref.GROUP_ID')
					->where('ref.USER_ID', $userId)
					->where(
						'ref.ROLE',
						'<=',
						UserToGroupTable::ROLE_USER
					),
				["join_type" => "INNER"]
			)
		);
		$projects = $query->exec()->fetchCollection();
		$res = ProjectProvider::filterByFeatures($projects, ['tasks' => 'view_all'], $userId, $this->getSiteId());

		$groupIds = [];
		foreach ($res as $row)
		{
			$groupIds[] = (int)$row['ID'];
		}

		$this->userGroups[$userId] = $groupIds;

		return $this->userGroups[$userId];
	}

	/**
	 * @param array $taskIds
	 * @return array
	 */
	private function getTasksGroups(array $taskIds): array
	{
		$taskRegistry = TaskRegistry::getInstance()->load($taskIds);

		$groupIds = [];
		foreach ($taskIds as $taskId)
		{
			$task = $taskRegistry->get((int)$taskId);
			if (!$task)
			{
				continue;
			}
			if (
				isset($task['GROUP_ID'])
				&& $task['GROUP_ID'] > 0
			)
			{
				$groupIds[] = $task['GROUP_ID'];
			}
		}

		return array_unique($groupIds);
	}

	/**
	 * @return string
	 */
	private function getSiteId(): string
	{
		if (defined('SITE_ID'))
		{
			return SITE_ID;
		}
		$siteId = \CSite::GetDefSite();
		return $siteId ? $siteId : '';
	}
}
