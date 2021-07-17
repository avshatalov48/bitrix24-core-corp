<?php
namespace Bitrix\Tasks\Internals\Task;

use Bitrix\Main;
use Bitrix\Main\Application;
use Bitrix\Main\Event;
use Bitrix\Main\UserTable;
use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Integration\Forum;
use Bitrix\Tasks\Integration\Pull\PushService;
use Bitrix\Tasks\Internals\Counter;
use Bitrix\Tasks\Internals\TaskTable;
use Bitrix\Tasks\MemberTable;
use Exception;
use Bitrix\Tasks\Internals\Counter\CounterDictionary;

/**
 * Class ViewedTable
 *
 * @package Bitrix\Tasks\Internals\Task
 */
class ViewedTable extends Main\Entity\DataManager
{
	private const STEP_LIMIT = 5000;

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName(): string
	{
		return 'b_tasks_viewed';
	}

	/**
	 * @return false|string
	 */
	public static function getClass()
	{
		return static::class;
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap(): array
	{
		return [
			'TASK_ID' => [
				'data_type' => 'integer',
				'primary' => true,
			],
			'USER_ID' => [
				'data_type' => 'integer',
				'primary' => true,
			],
			'VIEWED_DATE' => [
				'data_type' => 'datetime',
				'required' => true,
			],
			// references
			'USER' => [
				'data_type' => UserTable::class,
				'reference' => ['=this.USER_ID' => 'ref.ID'],
			],
			'TASK' => [
				'data_type' => TaskTable::class,
				'reference' => ['=this.TASK_ID' => 'ref.ID'],
			],
			'MEMBERS' => [
				'data_type' => MemberTable::class,
				'reference' => [
					'=this.TASK_ID' => 'ref.TASK_ID',
					'=this.USER_ID' => 'ref.USER_ID',
				],
			],
		];
	}

	/**
	 * @param int $userId
	 * @param int $taskId
	 * @throws Main\LoaderException
	 */
	public static function sendPushTaskView(int $userId, int $taskId): void
	{
		PushService::addEvent([$userId], [
			'module_id' => 'tasks',
			'command' => 'task_view',
			'params' => [
				'TASK_ID' => $taskId,
				'USER_ID' => $userId,
			],
		]);
	}

	/**
	 * @param int $currentUserId
	 * @param string $userJoin
	 * @param string $groupCondition
	 * @throws Main\ArgumentTypeException
	 * @throws Main\DB\SqlQueryException
	 */
	public static function readAll(int $currentUserId, string $userJoin, string $groupCondition = ''): void
	{
		$connection = Main\Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$viewedDate = $sqlHelper->convertToDbDateTime(new DateTime());

		$sql = "
			SELECT
					T.ID as ID
				FROM b_tasks T
				INNER JOIN b_tasks_scorer TS
					ON TS.TASK_ID = T.ID
					AND TS.USER_ID = {$currentUserId}
				{$userJoin}
				WHERE 
					TS.USER_ID = {$currentUserId}      
					{$groupCondition}
					AND TS.TYPE IN (
						'".CounterDictionary::COUNTER_MY_NEW_COMMENTS."',
						'".CounterDictionary::COUNTER_MY_MUTED_NEW_COMMENTS."',
						'".CounterDictionary::COUNTER_ACCOMPLICES_NEW_COMMENTS."',
						'".CounterDictionary::COUNTER_ACCOMPLICES_MUTED_NEW_COMMENTS."',
						'".CounterDictionary::COUNTER_AUDITOR_NEW_COMMENTS."',
						'".CounterDictionary::COUNTER_AUDITOR_MUTED_NEW_COMMENTS."',
						'".CounterDictionary::COUNTER_ORIGINATOR_NEW_COMMENTS."',
						'".CounterDictionary::COUNTER_ORIGINATOR_MUTED_NEW_COMMENTS."',
						'".CounterDictionary::COUNTER_GROUP_COMMENTS."'
					)
		";
		$res = $connection->query($sql);

		$inserts = [];
		while ($row = $res->fetch())
		{
			$inserts[] = '(' . (int)$row['ID'] . ', ' . $currentUserId . ', ' . $viewedDate . ')';
		}

		$chunks = array_chunk($inserts, self::STEP_LIMIT);
		unset($inserts);

		foreach ($chunks as $chunk)
		{
			$sql = "
				INSERT INTO b_tasks_viewed (TASK_ID, USER_ID, VIEWED_DATE)
				VALUES " . implode(',', $chunk) . "
				ON DUPLICATE KEY UPDATE VIEWED_DATE = {$viewedDate}
			";
			$connection->query($sql);
		}
	}

	/**
	 * @param int $userId
	 * @param array $groupIds
	 * @param bool $closedOnly
	 * @throws Main\ArgumentTypeException
	 * @throws Main\DB\SqlQueryException
	 */
	public static function readGroups(int $userId, array $groupIds, bool $closedOnly = false): void
	{
		$connection = Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$viewedDate = $sqlHelper->convertToDbDateTime(new DateTime());

		$intGroupIds = array_map(function($el) {
			return (int) $el;
		}, $groupIds);

		$condition = [];
		if (count($groupIds) === 1)
		{
			$condition[] = 'T.GROUP_ID = '. array_shift($groupIds);
		}
		else
		{
			$condition[] = 'T.GROUP_ID IN ('. implode(",", $intGroupIds) .')';
		}

		if ($closedOnly)
		{
			$condition[] = 'T.STATUS = '. \CTasks::STATE_COMPLETED;
		}

		$condition[] = 'TV.VIEWED_DATE IS NULL';
		$condition[] = 'FM.POST_DATE >= T.CREATED_DATE';
		$condition[] = 'FM.NEW_TOPIC = "N"';

		$condition = '(' . implode(') AND (', $condition) . ')';

		$sql = "
			SELECT DISTINCT T.ID as ID
			FROM b_tasks T
				LEFT JOIN b_tasks_viewed TV ON TV.TASK_ID = T.ID AND TV.USER_ID = {$userId}
				LEFT JOIN b_forum_message FM ON FM.TOPIC_ID = T.FORUM_TOPIC_ID
			WHERE
				{$condition}
		";
		$res = $connection->query($sql);

		$inserts = [];
		while ($row = $res->fetch())
		{
			$inserts[] = '(' . (int)$row['ID'] . ', ' . $userId . ', ' . $viewedDate . ')';
		}

		$chunks = array_chunk($inserts, self::STEP_LIMIT);
		unset($inserts);

		foreach ($chunks as $chunk)
		{
			$sql = "
				INSERT INTO b_tasks_viewed (TASK_ID, USER_ID, VIEWED_DATE)
				VALUES " . implode(',', $chunk) . "
				ON DUPLICATE KEY UPDATE VIEWED_DATE = {$viewedDate}
			";
			$connection->query($sql);
		}
	}

	/**
	 * @param int $taskId
	 * @param int $userId
	 * @param DateTime|null $viewedDate
	 * @param array $parameters
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 * @throws Main\LoaderException
	 */
	public static function set(int $taskId, int $userId, ?DateTime $viewedDate = null, array $parameters = []): void
	{
		$parameters['SEND_PUSH'] = ($parameters['SEND_PUSH'] ?? !isset($viewedDate));
		$parameters['UPDATE_TOPIC_LAST_VISIT'] = ($parameters['UPDATE_TOPIC_LAST_VISIT'] ?? true);
		$parameters['SOURCE_VIEWED_DATE'] = $viewedDate;

		$viewedDate = ($viewedDate ?? new DateTime());

		static::onBeforeView($taskId, $userId, $viewedDate, $parameters);
		static::viewTask($taskId, $userId, $viewedDate);
		static::onAfterView($taskId, $userId, $viewedDate, $parameters);
	}

	/**
	 * @param int $taskId
	 * @param int $userId
	 * @param DateTime $viewedDate
	 * @param array $parameters
	 * @throws Main\ArgumentException
	 * @throws Main\DB\SqlQueryException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private static function onBeforeView(int $taskId, int $userId, DateTime $viewedDate, array $parameters): void
	{
		Counter\CounterService::getInstance()->collectData($taskId);
	}

	/**
	 * @param int $taskId
	 * @param int $userId
	 * @param DateTime $viewedDate
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 * @throws Exception
	 */
	private static function viewTask(int $taskId, int $userId, DateTime $viewedDate): void
	{
		$list = static::getList([
			'select' => ['TASK_ID', 'USER_ID'],
			'filter' => [
				'=TASK_ID' => $taskId,
				'=USER_ID' => $userId,
			],
		]);

		if ($item = $list->fetch())
		{
			static::update($item, ['VIEWED_DATE' => $viewedDate]);
		}
		else
		{
			static::add([
				'TASK_ID' => $taskId,
				'USER_ID' => $userId,
				'VIEWED_DATE' => $viewedDate,
			]);
		}
	}

	/**
	 * @param int $taskId
	 * @param int $userId
	 * @param DateTime $viewedDate
	 * @param array $parameters
	 * @throws Main\ArgumentException
	 * @throws Main\Db\SqlQueryException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private static function onAfterView(int $taskId, int $userId, DateTime $viewedDate, array $parameters): void
	{
		if ($parameters['SEND_PUSH'])
		{
			static::sendPushTaskView($userId, $taskId);
		}
		if ($parameters['UPDATE_TOPIC_LAST_VISIT'])
		{
			Forum\Task\UserTopic::updateLastVisit($taskId, $userId, $viewedDate);
		}

		$event = new Event('tasks', 'onTaskUpdateViewed', ['taskId' => $taskId, 'userId' => $userId]);
		$event->send();

		Counter\CounterService::addEvent(
			Counter\Event\EventDictionary::EVENT_AFTER_TASK_VIEW,
			[
				'TASK_ID' => (int) $taskId,
				'USER_ID' => (int) $userId
			]
		);
	}
}