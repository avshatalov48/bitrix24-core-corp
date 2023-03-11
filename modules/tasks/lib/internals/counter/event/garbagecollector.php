<?php

namespace Bitrix\Tasks\Internals\Counter\Event;

use Bitrix\Main\Application;
use Bitrix\Tasks\Integration\Forum\Task\Comment;
use Bitrix\Tasks\Internals\Counter\CounterController;
use Bitrix\Tasks\Internals\Counter\CounterDictionary;
use Bitrix\Tasks\Internals\Counter\CounterTable;
use Bitrix\Tasks\Internals\Counter\Push\PushSender;
use Bitrix\Tasks\Util\Type\DateTime;

class GarbageCollector
{
	private const STEP_LIMIT = 1000;
	private const TTL = 30;

	/** @var DateTime $viewedTime */
	private $viewedTime;


	/**
	 *
	 */
	public function __construct()
	{

	}

	/**
	 * @return void
	 * @throws \Bitrix\Main\ArgumentTypeException
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 */
	public function process()
	{
		$this->setViewedTime();

		$events = EventCollection::getInstance()->list();

		$processed = [];
		foreach ($events as $event)
		{
			$userId = $event->getUserId();

			if (
				$event->getType() !== EventDictionary::EVENT_GARBAGE_COLLECT
				|| !$userId
			)
			{
				continue;
			}

			if (array_key_exists($userId, $processed))
			{
				continue;
			}

			$processed[$userId] = $userId;

			$this->readProjectComments($userId);
			$this->setClearMarker($userId);
		}

		(new PushSender())->sendUserCounters($processed);
	}

	/**
	 * @param int $userId
	 * @return void
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 */
	private function setClearMarker(int $userId)
	{
		$sql = "
			DELETE FROM ". CounterTable::getTableName() ."
			WHERE USER_ID = {$userId}
				AND TYPE = '". CounterDictionary::COUNTER_FLAG_CLEARED ."'
		";
		Application::getConnection()->query($sql);

		$date = date('ymd');

		$sql = "
			INSERT INTO ". CounterTable::getTableName() ."
			(`USER_ID`, `TASK_ID`, `GROUP_ID`, `TYPE`, `VALUE`)
			VALUES ({$userId}, 0, 0, '". CounterDictionary::COUNTER_FLAG_CLEARED ."', {$date})
		";
		Application::getConnection()->query($sql);
	}

	/**
	 * @return void
	 */
	private function setViewedTime()
	{
		$this->viewedTime = (new DateTime())->add('-'.self::TTL.' day');
	}

	/**
	 * @param int $userId
	 * @return void
	 * @throws \Bitrix\Main\ArgumentTypeException
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 * @throws \Bitrix\Tasks\Internals\Counter\Exception\UnknownCounterException
	 */
	private function readProjectComments(int $userId)
	{
		$sql = "
			SELECT 
			    ts.TASK_ID,
			    t.FORUM_TOPIC_ID as TOPIC_ID
			FROM b_tasks_scorer ts
			LEFT JOIN b_tasks t 
				ON t.ID = ts.TASK_ID
			WHERE 
				ts.USER_ID = ".$userId."
				AND ts.TYPE = '".CounterDictionary::COUNTER_GROUP_COMMENTS."'
				AND ts.TASK_ID NOT IN (
					SELECT TASK_ID
					FROM b_tasks_scorer
					WHERE 
						USER_ID = ".$userId."
						AND TYPE IN (
							'".CounterDictionary::COUNTER_MY_NEW_COMMENTS."', 
							'".CounterDictionary::COUNTER_AUDITOR_NEW_COMMENTS."', 
							'".CounterDictionary::COUNTER_ORIGINATOR_NEW_COMMENTS."', 
							'".CounterDictionary::COUNTER_ORIGINATOR_MUTED_NEW_COMMENTS."', 
							'".CounterDictionary::COUNTER_AUDITOR_MUTED_NEW_COMMENTS."', 
							'".CounterDictionary::COUNTER_MY_MUTED_NEW_COMMENTS."', 
							'".CounterDictionary::COUNTER_ACCOMPLICES_NEW_COMMENTS."', 
							'".CounterDictionary::COUNTER_ACCOMPLICES_MUTED_NEW_COMMENTS."'
						)
						AND group_id > 0 AND TASK_ID = ts.TASK_ID
				)
			ORDER BY ts.TASK_ID
			LIMIT ". self::STEP_LIMIT ."
		";

		$res = Application::getConnection()->query($sql);
		$rows = $res->fetchAll();

		if (empty($rows))
		{
			return;
		}

		$taskIds = array_column($rows, 'TASK_ID');
		$topicIds = array_column($rows, 'TOPIC_ID');

		$this->readTasks($userId, $taskIds);
		$this->readTopics($userId, $topicIds);

		(new CounterController($userId))->recount(CounterDictionary::COUNTER_GROUP_COMMENTS, $taskIds);
	}

	/**
	 * @param int $userId
	 * @param array $taskIds
	 * @return void
	 * @throws \Bitrix\Main\ArgumentTypeException
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 */
	private function readTasks(int $userId, array $taskIds)
	{
		$sqlHelper = Application::getConnection()->getSqlHelper();
		$viewedTime = $this->viewedTime;
		$viewedTime = $sqlHelper->convertToDbDateTime($viewedTime);

		$inserts = [];
		foreach ($taskIds as $taskId)
		{
			$inserts[] = '(' . (int) $taskId . ', ' . $userId . ', ' . $viewedTime . ')';
		}

		$sql = "
			INSERT INTO b_tasks_viewed (TASK_ID, USER_ID, VIEWED_DATE)
			VALUES " . implode(',', $inserts) . "
			ON DUPLICATE KEY UPDATE VIEWED_DATE = {$viewedTime}
		";

		Application::getConnection()->query($sql);
	}

	/**
	 * @param int $userId
	 * @param array $topicIds
	 * @return void
	 * @throws \Bitrix\Main\ArgumentTypeException
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 */
	private function readTopics(int $userId, array $topicIds)
	{
		$forumId = Comment::getForumId();

		$sqlHelper = Application::getConnection()->getSqlHelper();
		$viewedTime = $this->viewedTime;
		$viewedTime = $sqlHelper->convertToDbDateTime($viewedTime);

		$inserts = [];
		foreach ($topicIds as $topicId)
		{
			$inserts[] = '(' . (int) $topicId . ', ' . $userId . ', ' . $forumId . ', ' . $viewedTime . ')';
		}

		$sql = "
			INSERT INTO b_forum_user_topic (TOPIC_ID, USER_ID, FORUM_ID, LAST_VISIT)
			VALUES " . implode(',', $inserts) . "
			ON DUPLICATE KEY UPDATE LAST_VISIT = {$viewedTime}
		";

		Application::getConnection()->query($sql);
	}
}