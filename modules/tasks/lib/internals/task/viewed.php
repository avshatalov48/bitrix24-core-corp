<?php
namespace Bitrix\Tasks\Internals\Task;

use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Entity\BooleanField;
use Bitrix\Main\Event;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\UserTable;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\ORM\Data\Internal\MergeTrait;
use Bitrix\Tasks\Integration\CRM\TimeLineManager;
use Bitrix\Tasks\Integration\Forum;
use Bitrix\Tasks\Integration\Pull\PushCommand;
use Bitrix\Tasks\Integration\Pull\PushService;
use Bitrix\Tasks\Internals\Counter\CounterService;
use Bitrix\Tasks\Internals\Counter\Event\EventDictionary;
use Bitrix\Tasks\Internals\Log\Logger;
use Bitrix\Tasks\Internals\TaskDataManager;
use Bitrix\Tasks\Internals\TaskTable;
use Bitrix\Tasks\MemberTable;
use Bitrix\Tasks\Internals\Counter\CounterDictionary;
use Throwable;

/**
 * Class ViewedTable
 *
 * @package Bitrix\Tasks\Internals\Task
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Viewed_Query query()
 * @method static EO_Viewed_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Viewed_Result getById($id)
 * @method static EO_Viewed_Result getList(array $parameters = [])
 * @method static EO_Viewed_Entity getEntity()
 * @method static \Bitrix\Tasks\Internals\Task\View createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Internals\Task\EO_Viewed_Collection createCollection()
 * @method static \Bitrix\Tasks\Internals\Task\View wakeUpObject($row)
 * @method static \Bitrix\Tasks\Internals\Task\EO_Viewed_Collection wakeUpCollection($rows)
 */
class ViewedTable extends TaskDataManager
{
	use MergeTrait;

	private const STEP_LIMIT = 5000;

	private static array $cache = [];

	public static function getObjectClass(): string
	{
		return View::class;
	}
	
	public static function getTableName(): string
	{
		return 'b_tasks_viewed';
	}
	
	public static function getClass(): string
	{
		return static::class;
	}
	
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
			(new BooleanField('IS_REAL_VIEW'))
				->configureValues('N', 'Y')
				->configureDefaultValue('Y'),
		];
	}
	
	public static function sendPushTaskView(int $userId, int $taskId): void
	{
		PushService::addEvent([$userId], [
			'module_id' => 'tasks',
			'command' => PushCommand::TASK_VIEWED,
			'params' => [
				'TASK_ID' => $taskId,
				'USER_ID' => $userId,
			],
		]);
	}

	/**
	 * @throws SqlQueryException
	 */
	public static function getListForReadAll(
		int $currentUserId,
		string $userJoin,
		string $groupCondition = '',
		array $select = [],
		bool $distinct = false
	): ?array
	{
		$result = [];
		$connection = Application::getConnection();

		$strSelect = "T.ID as ID\n";
		foreach ($select as $key => $field)
		{
			$strSelect .= ",". $field["FIELD_NAME"]." AS ".$key."\n";
		}

		$distinct = $distinct ? 'DISTINCT' : '';
		$sql = "
			SELECT
				{$distinct} {$strSelect}
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

		while ($row = $res->fetch())
		{
			$result[] = $row;
		}

		return $result;
	}


	/**
	 * @param int $currentUserId
	 * @param string $userJoin
	 * @param string $groupCondition
	 * @throws ArgumentTypeException
	 * @throws SqlQueryException
	 */
	public static function readAll(int $currentUserId, string $userJoin, string $groupCondition = ''): void
	{
		$connection = Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$currentDateTime = new DateTime();
		$viewedDate = $sqlHelper->convertToDbDateTime($currentDateTime);

		$list = static::getListForReadAll($currentUserId, $userJoin, $groupCondition, [], true);

		$inserts = [];
		foreach ($list as $row)
		{
			$inserts[] = '(' . (int)$row['ID'] . ', ' . $currentUserId . ', ' . $viewedDate . ')';
		}

		static::read($inserts);
	}

	/**
	 * @param int $userId
	 * @param array $groupIds
	 * @param bool $closedOnly
	 * @throws ArgumentTypeException
	 * @throws SqlQueryException
	 */
	public static function readGroups(int $userId, array $groupIds, bool $closedOnly = false): void
	{
		$connection = Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$currentDateTime = new DateTime();
		$viewedDate = $sqlHelper->convertToDbDateTime($currentDateTime);

		$intGroupIds = array_map(static function($el) {
			return (int)$el;
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
			$condition[] = 'T.STATUS = '. Status::COMPLETED;
		}

		$condition[] = 'TV.VIEWED_DATE IS NULL';
		$condition[] = 'FM.POST_DATE >= T.CREATED_DATE';
		$condition[] = 'FM.NEW_TOPIC = \'N\'';

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

		static::read($inserts);
	}

	/**
	 * @param int $taskId
	 * @param int $userId
	 * @param DateTime|null $viewedDate
	 * @param array $parameters
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public static function set(int $taskId, int $userId, ?DateTime $viewedDate = null, array $parameters = []): void
	{
		$parameters['SEND_PUSH'] = ($parameters['SEND_PUSH'] ?? !isset($viewedDate));
		$parameters['IS_REAL_VIEW'] = ($parameters['IS_REAL_VIEW'] ?? false);
		$parameters['UPDATE_TOPIC_LAST_VISIT'] = ($parameters['UPDATE_TOPIC_LAST_VISIT'] ?? true);
		$parameters['SOURCE_VIEWED_DATE'] = $viewedDate;

		$viewedDate = ($viewedDate ?? new DateTime());

		static::onBeforeView($taskId);
		static::viewTask($taskId, $userId, $viewedDate, $parameters);
		static::onAfterView($taskId, $userId, $viewedDate, $parameters);
	}
	
	private static function onBeforeView(int $taskId): void
	{
		CounterService::getInstance()->collectData($taskId);
	}
	
	private static function viewTask(int $taskId, int $userId, DateTime $viewedDate, array $parameters = []): void
	{
		$cacheKey = $taskId . '.' . $userId . '.' . $viewedDate->getTimestamp();
		if (isset(static::$cache[$cacheKey]))
		{
			return;
		}

		$view = static::getView($taskId, $userId);

		if (null !== $view)
		{
			static::updateView($view, $viewedDate, $parameters);
		}
		else
		{
			static::addView($taskId, $userId, $viewedDate, $parameters);
		}

		self::$cache[$cacheKey] = true;
	}

	private static function getView(int $taskId, int $userId): ?View
	{
		try
		{
			return static::query()
				->setSelect(['TASK_ID', 'USER_ID', 'IS_REAL_VIEW'])
				->where('TASK_ID', $taskId)
				->where('USER_ID', $userId)
				->fetchObject();
		}
		catch (Throwable $t)
		{
			Logger::log($t, 'TASKS_DEBUG_VIEW_GET');
			return  null;
		}
	}

	private static function updateView(View $item, DateTime $viewedDate, array $parameters = []): void
	{
		$primary = ['TASK_ID' => $item->getTaskId(), 'USER_ID' => $item->getUserId()];
		$params = ['VIEWED_DATE' => $viewedDate];
		if ($parameters['IS_REAL_VIEW'] && !$item->getIsRealView())
		{
			$params['IS_REAL_VIEW'] = 'Y';
			static::onFirstRealView($item->getTaskId(), $item->getUserId());
		}
		try
		{
			static::update($primary, $params);
		}
		catch (Throwable $t)
		{
			Logger::log($t, 'TASKS_DEBUG_VIEW_UPDATE');
		}
	}

	private static function addView(int $taskId, int $userId, DateTime $viewedDate, array $parameters = []): void
	{
		$connection = Application::getConnection();
		$helper = $connection->getSqlHelper();

		$isRealView = $parameters['IS_REAL_VIEW'] === 'N' || $parameters['IS_REAL_VIEW'] === false ? 'N' : 'Y';

		$fields = "(TASK_ID, USER_ID, VIEWED_DATE, IS_REAL_VIEW)";
		try
		{
			$values = "({$taskId}, {$userId}, {$helper->convertToDbDateTime($viewedDate)}, '{$isRealView}')";

			$sql = $helper->getInsertIgnore(static::getTableName(), " {$fields}", " VALUES {$values}");

			$connection->query($sql);
		}
		catch (Throwable $t)
		{
			Logger::log($t, 'TASKS_DEBUG_VIEW_ADD');
		}

		if ($parameters['IS_REAL_VIEW'])
		{
			static::onFirstRealView($taskId, $userId);
		}
	}

	/**
	 * @throws SqlQueryException
	 */
	private static function read(array $inserts): void
	{
		$connection =  Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$currentDateTime = new DateTime();

		$chunks = array_chunk($inserts, self::STEP_LIMIT);

		foreach ($chunks as $chunk)
		{
			$values = implode(',', $chunk);
			$values = "VALUES {$values}";
			$sql = $sqlHelper->prepareMergeSelect(
				static::getTableName(),
				['TASK_ID', 'USER_ID'],
				['TASK_ID', 'USER_ID', 'VIEWED_DATE'],
				$values,
				['VIEWED_DATE' => $currentDateTime]
			);
			$connection->query($sql);
		}
	}

	/**
	 * @param int $taskId
	 * @param int $userId
	 * @param DateTime $viewedDate
	 * @param array $parameters
	 * @throws ArgumentException
	 * @throws SqlQueryException
	 * @throws ObjectPropertyException
	 * @throws SystemException
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

		$eventParameters = [
			'taskId' => $taskId,
			'userId' => $userId,
			'isRealView' => $parameters['IS_REAL_VIEW'],
		];
		$event = new Event('tasks', 'onTaskUpdateViewed', $eventParameters);
		$event->send();
		CounterService::addEvent(
			EventDictionary::EVENT_AFTER_TASK_VIEW,
			[
				'TASK_ID' => $taskId,
				'USER_ID' => $userId,
			]
		);

		TimeLineManager::get($taskId)
			->setUserId($userId)
			->onTaskAllCommentViewed()
			->save();
	}

	private static function onFirstRealView(int $taskId, int $userId): void
	{
		TimeLineManager::get($taskId)
			->setUserId($userId)
			->onTaskViewed()
			->save();
	}
}
