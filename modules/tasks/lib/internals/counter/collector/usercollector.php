<?php
namespace Bitrix\Tasks\Internals\Counter\Collector;

use Bitrix\Main\Application;
use Bitrix\Tasks\Comments\Internals\Comment;
use Bitrix\Tasks\Internals\Task\UserOptionTable;
use Bitrix\Tasks\Internals\UserOption;
use CTasks;
use Bitrix\Tasks\Internals\Task\MemberTable;
use Bitrix\Tasks\Internals\Counter\CounterDictionary;
use Bitrix\Tasks\Internals\Counter\Deadline;

class UserCollector
{
	private $userId;

	private $mutedTasks = [];

	public function __construct(int $userId)
	{
		$this->userId = $userId;
	}

	/**
	 * @param string $counter
	 * @param array $taskIds
	 * @return array
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public function recount(string $counter, array $taskIds = []): array
	{
		if (!$this->userId)
		{
			return [];
		}

		if (empty($taskIds))
		{
			return [];
		}

		$taskFilter = $this->getTasksFilter($taskIds);
		if (!$taskFilter)
		{
			return [];
		}

		$mutedTasks = $this->getMutedTasks($taskIds);

		$counters = [];
		switch ($counter)
		{
			case CounterDictionary::COUNTER_EXPIRED:
				$counters = $this->recountExpired($taskFilter, $mutedTasks);
				break;
			case CounterDictionary::COUNTER_NEW_COMMENTS:
				$counters = $this->recountComments($taskFilter, $mutedTasks);
				break;
			default:
				break;
		}

		return $counters;
	}

	/**
	 * @param string $taskFilter
	 * @param array $mutedTasks
	 * @return array
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	private function recountExpired(string $taskFilter, array $mutedTasks): array
	{
		$expiredTime = Deadline::getExpiredTime()->format('Y-m-d H:i:s');
		$statuses = implode(',', [CTasks::STATE_PENDING, CTasks::STATE_IN_PROGRESS]);

		$sql = "
			SELECT
				T.ID,
				T.GROUP_ID,
				TM.TYPE,
				'1' as `COUNT`
			FROM b_tasks T
			INNER JOIN ". MemberTable::getTableName() ." TM 
				ON TM.TASK_ID = T.ID 
				AND TM.USER_ID = {$this->userId}
			WHERE
				{$taskFilter}
				AND T.DEADLINE < '{$expiredTime}'
				AND T.STATUS IN ({$statuses})
		";

		$res = Application::getConnection()->query($sql);
		$rows = $res->fetchAll();

		$responsibleTasks = [];
		foreach ($rows as $row)
		{
			if ($row['TYPE'] === MemberTable::MEMBER_TYPE_RESPONSIBLE)
			{
				$responsibleTasks[] = $row['ID'];
			}
		}

		$counters = [];
		foreach ($rows as $row)
		{
			$type = $row['TYPE'];
			if (!array_key_exists($type, CounterDictionary::MAP_EXPIRED))
			{
				continue;
			}
			if (
				$type === MemberTable::MEMBER_TYPE_ORIGINATOR
				&& in_array($row['ID'], $responsibleTasks)
			)
			{
				continue;
			}

			$counters[] = [
				'USER_ID'	=> $this->userId,
				'TASK_ID' 	=> (int) $row['ID'],
				'GROUP_ID' 	=> (int) $row['GROUP_ID'],
				'TYPE' 		=> in_array($row['ID'], $mutedTasks)
					? CounterDictionary::MAP_MUTED_EXPIRED[$type]
					: CounterDictionary::MAP_EXPIRED[$type],
				'VALUE' 	=> (int) $row['COUNT']
			];
		}

		return $counters;
	}

	/**
	 * @param string $taskFilter
	 * @param array $mutedTasks
	 * @return array
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	private function recountComments(string $taskFilter, array $mutedTasks): array
	{
		$filter = [];

		$filter[] = $taskFilter;
		$filter[] = "(
			(TV.VIEWED_DATE IS NOT NULL AND FM.POST_DATE > TV.VIEWED_DATE)
			OR (TV.VIEWED_DATE IS NULL AND FM.POST_DATE >= T.CREATED_DATE)
		)";
		$filter[] = "FM.NEW_TOPIC = 'N'";
		$filter[] = "(
			(
				FM.AUTHOR_ID <> {$this->userId}
				AND (BUF.UF_TASK_COMMENT_TYPE IS NULL OR BUF.UF_TASK_COMMENT_TYPE <> " . Comment::TYPE_EXPIRED . ")
			)
			OR
			(
				BUF.UF_TASK_COMMENT_TYPE = " . Comment::TYPE_EXPIRED_SOON . "
			)
		)";

		$counterFilter = $this->getCounterFilter();
		if (!empty($counterFilter))
		{
			$filter[] = $counterFilter;
		}

		$filter = implode(' AND ', $filter);

		$sql = "
			SELECT
			    T.ID,
				COUNT(DISTINCT FM.ID) as COUNT,
				T.GROUP_ID,
			   	TM.TYPE
			FROM b_tasks T
				LEFT JOIN b_tasks_viewed TV ON TV.TASK_ID = T.ID AND TV.USER_ID = {$this->userId}
				INNER JOIN b_tasks_member TM ON TM.TASK_ID = T.ID AND TM.USER_ID = {$this->userId}
				INNER JOIN b_forum_message FM ON FM.TOPIC_ID = T.FORUM_TOPIC_ID
				LEFT JOIN b_uts_forum_message BUF ON BUF.VALUE_ID = FM.ID
			WHERE
				{$filter}
			GROUP BY T.ID, TM.TYPE
		";

		$res = Application::getConnection()->query($sql);
		$rows = $res->fetchAll();

		$responsibleTasks = [];
		foreach ($rows as $row)
		{
			if ($row['TYPE'] === MemberTable::MEMBER_TYPE_RESPONSIBLE)
			{
				$responsibleTasks[] = $row['ID'];
			}
		}

		$counters = [];

		foreach ($rows as $row)
		{
			$type = $row['TYPE'];
			if (!array_key_exists($type, CounterDictionary::MAP_COMMENTS))
			{
				continue;
			}
			if (
				$type === MemberTable::MEMBER_TYPE_ORIGINATOR
				&& in_array($row['ID'], $responsibleTasks)
			)
			{
				continue;
			}

			$counters[] = [
				'USER_ID'	=> $this->userId,
				'TASK_ID' 	=> (int) $row['ID'],
				'GROUP_ID' 	=> (int) $row['GROUP_ID'],
				'TYPE' 		=> in_array($row['ID'], $mutedTasks)
					? CounterDictionary::MAP_MUTED_COMMENTS[$type]
					: CounterDictionary::MAP_COMMENTS[$type],
				'VALUE' 	=> (int) $row['COUNT']
			];
		}

		return $counters;
	}

	/**
	 * @param array $tasksIds
	 * @return string
	 */
	private function getTasksFilter(array $tasksIds): ?string
	{
		$ids = array_map(function($item) {
			return (int) $item;
		}, $tasksIds);

		return 'T.ID IN ('.implode(',', $ids).')';
	}

	/**
	 * @return string
	 */
	private function getMutedTasks(array $taskIds = []): array
	{
		$key = md5(json_encode($taskIds));

		if (array_key_exists($key, $this->mutedTasks))
		{
			return $this->mutedTasks[$key];
		}

		$query = UserOptionTable::query()
			->addSelect('TASK_ID')
			->where('USER_ID', $this->userId)
			->where('OPTION_CODE', UserOption\Option::MUTED);

		if (!empty($taskIds))
		{
			$query->whereIn('TASK_ID', $taskIds);
		}

		$res = $query->exec();

		$mutedTasks = [];
		while ($row = $res->fetch())
		{
			$mutedTasks[] = $row['TASK_ID'];
		}

		$this->mutedTasks[$key] = $mutedTasks;

		return $this->mutedTasks[$key];
	}

	/**
	 * @return string
	 */
	private function getCounterFilter(): string
	{
		$counterFilter = '';

		$startCounterDate = \COption::GetOptionString("tasks", "tasksDropCommentCounters", null);
		if ($startCounterDate)
		{
			$counterFilter = "FM.POST_DATE > '{$startCounterDate}'";
		}

		return $counterFilter;
	}
}