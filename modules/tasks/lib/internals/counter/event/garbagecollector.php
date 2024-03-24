<?php

namespace Bitrix\Tasks\Internals\Counter\Event;

use Bitrix\Main\Application;
use Bitrix\Tasks\Integration\Forum\Task\Comment;
use Bitrix\Tasks\Internals\Counter\CounterController;
use Bitrix\Tasks\Internals\Counter\CounterDictionary;
use Bitrix\Tasks\Internals\Counter\CounterTable;
use Bitrix\Tasks\Internals\Counter\Push\PushSender;
use Bitrix\Tasks\Internals\Counter;
use Bitrix\Tasks\Internals\Task\ViewedTable;
use Bitrix\Tasks\Util\Type\DateTime;

class GarbageCollector
{
	private const STEP_LIMIT = 1000;
	private const TTL = 45;

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

			$this->setClearMarker($userId);
			$this->readProjectComments($userId);
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
			(USER_ID, TASK_ID, GROUP_ID, TYPE, VALUE)
			VALUES ({$userId}, 0, 0, '". CounterDictionary::COUNTER_FLAG_CLEARED ."', {$date})
		";
		Application::getConnection()->query($sql);

		Counter\State\Factory::getState($userId)->resetCache();
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
		$sqlHelper = Application::getConnection()->getSqlHelper();
		$viewedTime = $this->viewedTime;
		$viewedTime = $sqlHelper->convertToDbDateTime($viewedTime);

		$sql = "
			SELECT 
			    ts.TASK_ID,
			    t.FORUM_TOPIC_ID
			FROM b_tasks_scorer ts
			LEFT JOIN b_tasks_viewed tv
				ON tv.TASK_ID = ts.TASK_ID
				AND tv.USER_ID = ts.USER_ID
			LEFT JOIN b_tasks t
				ON t.ID = ts.TASK_ID
			WHERE
				ts.USER_ID = ".$userId."
				AND ts.TYPE = '".CounterDictionary::COUNTER_GROUP_COMMENTS."'
				AND
				(
					tv.VIEWED_DATE < ".$viewedTime."
					or tv.VIEWED_DATE is null
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
		$topicIds = array_column($rows, 'FORUM_TOPIC_ID');

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
		$viewedTime = $sqlHelper->convertToDbDateTime($this->viewedTime);

		$inserts = [];
		foreach ($taskIds as $taskId)
		{
			$inserts[] = '(' . (int) $taskId . ', ' . $userId . ', ' . $viewedTime . ')';
		}
		$values = implode(',', $inserts);
		$values = " VALUES {$values}";
		$sql = $sqlHelper->prepareMergeSelect(
			ViewedTable::getTableName(),
			['TASK_ID', 'USER_ID'],
			['TASK_ID', 'USER_ID', 'VIEWED_DATE'],
			$values,
			['VIEWED_DATE' => $this->viewedTime]
		);

		Application::getConnection()->query($sql);
	}

	/**
	 * @param int $userId
	 * @param array $topicIds
	 * @return void
	 * @throws \Bitrix\Main\ArgumentTypeException
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 */
	public function readTopics(int $userId, array $topicIds)
	{
		$forumId = Comment::getForumId();

		$sqlHelper = Application::getConnection()->getSqlHelper();
		$viewedTime = $sqlHelper->convertToDbDateTime($this->viewedTime);

		$inserts = [];
		foreach ($topicIds as $topicId)
		{
			$inserts[] = '(' . (int) $topicId . ', ' . $userId . ', ' . $forumId . ', ' . $viewedTime . ')';
		}
		$inserts = array_unique($inserts);
		$values = implode(',', $inserts);
		$values = " VALUES {$values}";
		$sql = $sqlHelper->prepareMergeSelect(
			'b_forum_user_topic',
			['TOPIC_ID', 'USER_ID'],
			['TOPIC_ID', 'USER_ID', 'FORUM_ID', 'LAST_VISIT',],
			$values,
			['LAST_VISIT' => $this->viewedTime,]
		);

		Application::getConnection()->query($sql);
	}
}
