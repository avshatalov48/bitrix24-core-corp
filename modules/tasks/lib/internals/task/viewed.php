<?php
namespace Bitrix\Tasks\Internals\Task;

use Bitrix\Main;
use Bitrix\Main\Event;
use Bitrix\Main\UserTable;
use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Integration\Forum;
use Bitrix\Tasks\Internals\Counter;
use Bitrix\Tasks\Internals\TaskTable;
use Bitrix\Tasks\MemberTable;
use Exception;

/**
 * Class ViewedTable
 *
 * @package Bitrix\Tasks\Internals\Task
 */
class ViewedTable extends Main\Entity\DataManager
{
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
			Counter\CounterDictionary::EVENT_AFTER_TASK_VIEW,
			[
				'TASK_ID' => (int) $taskId,
				'USER_ID' => (int) $userId
			]
		);
	}

	/**
	 * @param int $userId
	 * @param int $taskId
	 * @throws Main\LoaderException
	 */
	public static function sendPushTaskView(int $userId, int $taskId): void
	{
		if (Main\Loader::includeModule('pull'))
		{
			\Bitrix\Pull\Event::add([$userId], [
				'module_id' => 'tasks',
				'command' => 'task_view',
				'params' => [
					'TASK_ID' => $taskId,
					'USER_ID' => $userId,
				],
			]);
		}
	}
}