<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Internals\Counter\Collector;


use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Tasks\Comments\Internals\Comment;
use Bitrix\Tasks\Comments\Viewed\Enum;
use Bitrix\Tasks\Comments\Viewed\Group;
use Bitrix\Tasks\Internals\Counter\CounterDictionary;
use Bitrix\Tasks\Internals\Counter\Deadline;
use Bitrix\Tasks\Internals\Task\Status;

class ProjectCollector
{
	public function __construct()
	{

	}

	/**
	 * @param string $counter
	 * @param array $userIds
	 * @param array $taskIds
	 * @param array $groupIds
	 * @return array
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function recount(string $counter, array $userIds = [], array $taskIds = [], array $groupIds = []): array
	{
		$counters = [];

		if (!Loader::includeModule('socialnetwork'))
		{
			return [];
		}

		switch ($counter)
		{
			case CounterDictionary::COUNTER_GROUP_EXPIRED:
				$counters = $this->recountExpired($groupIds, $taskIds, $userIds);
				break;
			case CounterDictionary::COUNTER_GROUP_COMMENTS:
				$counters = $this->recountComments($groupIds, $taskIds, $userIds);
				break;
			default:
				break;
		}

		return $counters;
	}

	/**
	 * @param array $groupIds
	 * @param array $taskIds
	 * @param array $userIds
	 * @return array
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 */
	private function recountExpired(array $groupIds = [], array $taskIds = [], array $userIds = []): array
	{
		$expiredTime = Deadline::getExpiredTime()->format('Y-m-d H:i:s');

		$joinFilter[] = "SU.GROUP_ID = T.GROUP_ID";

		if (count($userIds) === 1)
		{
			$joinFilter[] = 'SU.USER_ID = '. (int) array_shift($userIds);
		}
		elseif (count($userIds) > 1)
		{
			$joinFilter[] = 'SU.USER_ID IN ('. implode(',', $userIds) .')';
		}

		$filter = [];
		if (!empty($taskIds))
		{
			$filter[] = 'T.ID IN ('. implode(',', $taskIds) .')';
		}

		if (count($groupIds) === 1)
		{
			$groupId = (int) array_shift($groupIds);
			$filter[] = 'T.GROUP_ID = '. $groupId;
			$joinFilter[] = 'SU.GROUP_ID = '. $groupId;
		}
		elseif (count($groupIds) > 1)
		{
			$filter[] = 'T.GROUP_ID IN ('. implode(',', $groupIds) .')';
			$joinFilter[] = 'SU.GROUP_ID IN ('. implode(',', $groupIds) .')';
		}

		$filter[] = "T.DEADLINE < '". $expiredTime ."'";
		$filter[] = 'T.STATUS IN ('. implode(',', [Status::PENDING, Status::IN_PROGRESS]) .')';

		$filter = implode(' AND ', $filter);
		$joinFilter = implode(' AND ', $joinFilter);

		$sql = "
			SELECT
				T.ID,
			    T.GROUP_ID,
			    SU.USER_ID
			FROM b_tasks T
			INNER JOIN b_sonet_user2group SU
				ON {$joinFilter}
			WHERE
				{$filter}
		";

		$res = Application::getConnection()->query($sql);
		$rows = $res->fetchAll();

		$counters = [];
		foreach ($rows as $row)
		{
			$counters[] = [
				'USER_ID'	=> (int) $row['USER_ID'],
				'TASK_ID' 	=> (int) $row['ID'],
				'GROUP_ID' 	=> (int) $row['GROUP_ID'],
				'TYPE' 		=> CounterDictionary::COUNTER_GROUP_EXPIRED,
				'VALUE' 	=> 1
			];
		}

		return $counters;
	}

	/**
	 * @param array $groupIds
	 * @param array $taskIds
	 * @param array $userIds
	 * @return array
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 */
	private function recountComments(array $groupIds = [], array $taskIds = [], array $userIds = []): array
	{
		$statement = [
			'join' => [
				Counter::getJoinForRecountCommentsByType(Enum::PROJECT_NAME, [])
			],
			'filter' => [
				Counter::getConditionForRecountComments()
			],
		];

		$filter = [];
		$joinFilter = [];

		$joinFilter[] = "SU.GROUP_ID = T.GROUP_ID";

		if (count($userIds) === 1)
		{
			$joinFilter[] = 'SU.USER_ID = '. (int) array_shift($userIds);
		}
		elseif (count($userIds) > 1)
		{
			$joinFilter[] = 'SU.USER_ID IN ('. implode(',', $userIds) .')';
		}

		if (count($groupIds) === 1)
		{
			$groupId = (int) array_shift($groupIds);
			$filter[] = 'T.GROUP_ID = '. $groupId;
			$joinFilter[] = 'SU.GROUP_ID = '. $groupId;
		}
		elseif (count($groupIds) > 1)
		{
			$filter[] = 'T.GROUP_ID IN ('. implode(',', $groupIds) .')';
			$joinFilter[] = 'SU.GROUP_ID IN ('. implode(',', $groupIds) .')';
		}

		if (!empty($taskIds))
		{
			$filter[] = 'T.ID IN ('. implode(',', $taskIds) .')';
		}

		$filter[] = "(
			FM.POST_DATE >= SU.DATE_CREATE
			OR TM.USER_ID IS NOT NULL
		)";
		$filter[] = "(
			(
				FM.AUTHOR_ID <> SU.USER_ID
				AND (
				   BUF.UF_TASK_COMMENT_TYPE IS NULL OR BUF.UF_TASK_COMMENT_TYPE <> ". Comment::TYPE_EXPIRED ."
				)
			)
			OR
			(
				BUF.UF_TASK_COMMENT_TYPE = ". Comment::TYPE_EXPIRED_SOON ."
			)
		)";
		$filter[] = "FM.NEW_TOPIC = 'N'";

		$startCounterDate = \COption::GetOptionString("tasks", "tasksDropCommentCounters", null);
		if ($startCounterDate)
		{
			$filter[] = "FM.POST_DATE > '". $startCounterDate ."'";
		}

		$join = implode('\n\r', $statement['join']);
		$filter = array_merge($filter, $statement['filter']);

		$filter = implode(' AND ', $filter);
		$joinFilter = implode(' AND ', $joinFilter);

		$sql = "
			SELECT 
				T.ID as ID,
			   	T.GROUP_ID as GROUP_ID,
				SU.USER_ID as USER_ID,
				COUNT(DISTINCT FM.ID) AS COUNT
			FROM b_tasks T				
				INNER JOIN b_sonet_user2group SU
					ON {$joinFilter}
				LEFT JOIN b_tasks_viewed TV
					ON TV.TASK_ID = T.ID AND TV.USER_ID = SU.USER_ID
				INNER JOIN b_forum_message FM
					ON FM.TOPIC_ID = T.FORUM_TOPIC_ID
				LEFT JOIN b_uts_forum_message BUF
					ON BUF.VALUE_ID = FM.ID
				LEFT JOIN b_tasks_member TM
					ON TM.TASK_ID = T.ID AND TM.USER_ID = SU.USER_ID
				{$join}
			WHERE
				{$filter}
			GROUP BY T.ID, SU.USER_ID
		";

		$res = Application::getConnection()->query($sql);
		$rows = $res->fetchAll();

		$counters = [];

		foreach ($rows as $row)
		{
			$counters[] = [
				'USER_ID'	=> (int) $row['USER_ID'],
				'TASK_ID' 	=> (int) $row['ID'],
				'GROUP_ID' 	=> (int) $row['GROUP_ID'],
				'TYPE' 		=> CounterDictionary::COUNTER_GROUP_COMMENTS,
				'VALUE' 	=> (int) $row['COUNT']
			];
		}

		return $counters;
	}
}