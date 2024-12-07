<?php

namespace Bitrix\Tasks\Internals;

use Bitrix\Main;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Entity;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\UI\Filter;

use Bitrix\Tasks\Flow\Efficiency\Efficiency;
use Bitrix\Tasks\Flow\Efficiency\LastMonth;
use Bitrix\Tasks\Flow\Notification\NotificationService;
use Bitrix\Tasks\Integration\Pull\PushCommand;
use Bitrix\Tasks\Integration\Pull\PushService;
use Bitrix\Tasks\Internals\Counter\Deadline;
use Bitrix\Tasks\Internals\Counter\EffectiveTable;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;
use Bitrix\Tasks\Internals\Task\MemberTable;
use Bitrix\Tasks\Internals\Task\Status;
use Bitrix\Tasks\Update\EfficiencyRecount;
use Bitrix\Tasks\Util\Type\DateTime;

Loc::loadMessages(__FILE__);

/**
 * Class Effective
 * @package Bitrix\Tasks\Internals
 */
class Effective
{
	private const TASKS_EFFECTIVE_DISABLE_KEY = 'tasks_effective_disable';

	/**
	 * @return bool
	 */
	public static function isEnabled(): bool
	{
		return Option::get('tasks', self::TASKS_EFFECTIVE_DISABLE_KEY, 'null') === 'null';
	}

	/**
	 * Returns filter id on efficiency page
	 *
	 * @return string
	 */
	public static function getFilterId()
	{
		return 'TASKS_REPORT_EFFECTIVE_GRID';
	}

	/**
	 * Returns list of default presets
	 *
	 * @return array
	 */
	public static function getPresetList()
	{
		return array(
			'filter_tasks_range_day' => array(
				'name' => Loc::getMessage('TASKS_PRESET_CURRENT_DAY'),
				'default' => false,
				'fields' => array(
					"DATETIME_datesel" => Filter\DateType::CURRENT_DAY,
				),
			),
			'filter_tasks_range_month' => array(
				'name' => Loc::getMessage('TASKS_PRESET_CURRENT_MONTH'),
				'default' => true,
				'fields' => array(
					"DATETIME_datesel" => Filter\DateType::CURRENT_MONTH,
				),
			),
			'filter_tasks_range_quarter' => array(
				'name' => Loc::getMessage('TASKS_PRESET_CURRENT_QUARTER'),
				'default' => false,
				'fields' => array(
					"DATETIME_datesel" => Filter\DateType::CURRENT_QUARTER,
				),
			),
		);
	}

	/**
	 * @return array
	 */
	private static function getFilterList()
	{
		return [
			'GROUP_ID' => [
				'id' => 'GROUP_ID',
				'type' => 'custom_entity',
			],
			'DATETIME' => [
				'id' => 'DATETIME',
				'type' => 'date',
			],
		];
	}

	/**
	 * Returns value of efficiency user counter
	 *
	 * @param $userId
	 * @return bool|int
	 */
	public static function getEfficiencyFromUserCounter($userId)
	{
		$code = Counter\CounterDictionary::getCounterId(Counter\CounterDictionary::COUNTER_EFFECTIVE);

		return \CUserCounter::getValue($userId, $code, '**');
	}

	/**
	 * Sets efficiency for efficiency user counter
	 *
	 * @param $userId
	 * @param $efficiency
	 * @return bool
	 */
	public static function setEfficiencyToUserCounter($userId, $efficiency)
	{
		$code = Counter\CounterDictionary::getCounterId(Counter\CounterDictionary::COUNTER_EFFECTIVE);

		return \CUserCounter::Set($userId, $code, $efficiency, '**', '', false);
	}

	/**
	 * Returns time of first added efficiency record or false if it doesn't exist.
	 *
	 * @return bool|Main\Type\DateTime
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function getFirstRecordTime()
	{
		$result = EffectiveTable::getList([
			'select' => ['DATETIME'],
			'order' => ['ID' => 'ASC'],
			'limit' => 1,
		]);

		return (($date = $result->fetch()) ? $date['DATETIME'] : false);
	}

	/**
	 * Returns dates of current month borders (current month is used as default for now)
	 *
	 * @return array
	 * @throws Main\ObjectException
	 */
	public static function getDatesRange()
	{
		$currentDate = new DateTime();

		$dateFrom = DateTime::createFromTimestamp(strtotime($currentDate->format('01.m.Y 00:00:01')));
		$dateTo = DateTime::createFromTimestamp(strtotime($currentDate->format('t.m.Y 23:59:59')));

		return [
			'FROM' => $dateFrom,
			'TO' => $dateTo,
		];
	}

	/**
	 * @param $userId
	 * @return array
	 */
	private static function getDefaultFilterFieldsValues($userId)
	{
		$filterOptions = \CUserOptions::GetOption('main.ui.filter', static::getFilterId(), [], $userId);

		$filters = $filterOptions['filters'];
		$defaultFilterName = $filterOptions['default'];

		$fieldValues = Filter\Options::fetchFieldValuesFromFilterSettings($filters[$defaultFilterName], [], static::getFilterList());
		$fieldValues = static::processFieldValues($fieldValues);

		return $fieldValues;
	}

	/**
	 * @param $fieldValues
	 * @return array
	 */
	private static function processFieldValues($fieldValues)
	{
		$baseResult = [
			'FROM' => 0,
			'TO' => 0,
			'GROUP_ID' => 0,
		];
		$result = $baseResult;

		try
		{
			if (array_key_exists('DATETIME_from', $fieldValues) && $fieldValues['DATETIME_from'])
			{
				$result['FROM'] = new DateTime($fieldValues['DATETIME_from']);
			}
			if (array_key_exists('DATETIME_to', $fieldValues) && $fieldValues['DATETIME_to'])
			{
				$result['TO'] = new DateTime($fieldValues['DATETIME_to']);
			}
			if (array_key_exists('GROUP_ID', $fieldValues) && (int)$fieldValues['GROUP_ID'])
			{
				$result['GROUP_ID'] = $fieldValues['GROUP_ID'];
			}
		}
		catch (Main\ObjectException $e)
		{
			return $baseResult;
		}

		return $result;
	}

	/**
	 * Checks if not repaired violations exists for task/user/group separately or in some combination.
	 *
	 * @param null $taskId
	 * @param null $userId
	 * @param null $groupId
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function checkActiveViolations($taskId = null, $userId = null, $groupId = null)
	{
		if (!self::isEnabled())
		{
			return [];
		}

		if (!$taskId && !$userId && !$groupId)
		{
			return [];
		}

		$query = new Query(EffectiveTable::getEntity());

		$query->setSelect(['ID']);
		$query
			->where(($taskId? Query::filter()->where('TASK_ID', $taskId) : []))
			->where(($userId? Query::filter()->where('USER_ID', $userId) : []))
			->where(($groupId? Query::filter()->where('GROUP_ID', $groupId) : []))
			->where('IS_VIOLATION', 'Y')
			->where('DATETIME_REPAIR', null);

		return $query->exec()->fetchAll();
	}

	/**
	 * Modifies user efficiency. Also recounts efficiency user counter after modification if needed.
	 *
	 * @param int $userId
	 * @param string $userType
	 * @param array $taskData
	 * @param int $groupId
	 * @param bool|null $isViolation
	 * @param bool $recountEfficiency
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function modify(
		int $userId,
		string $userType,
		array $taskData,
		int $groupId = 0,
		bool $isViolation = null,
		bool $recountEfficiency = true
	): bool
	{
		if (!self::isEnabled())
		{
			return true;
		}

		$deadline = $taskData['DEADLINE'];
		$createdBy = $taskData['CREATED_BY'];

		if (!$userId || !$createdBy || $userId === $createdBy)
		{
			return false;
		}

		if ($isViolation === null)
		{
			$isViolation = static::isViolation($deadline);
		}

		EffectiveTable::add([
			'DATETIME' => new DateTime(),
			'USER_ID' => $userId,
			'USER_TYPE' => $userType,
			'GROUP_ID' => $groupId,
			'EFFECTIVE' => static::getEfficiencyForNow($userId, $groupId),
			'TASK_ID' => $taskData['ID'],
			'TASK_TITLE' => $taskData['TITLE'],
			'TASK_DEADLINE' => $deadline,
			'IS_VIOLATION'=> ($isViolation ? 'Y' : 'N'),
		]);

		if ($recountEfficiency)
		{
			static::recountEfficiencyUserCounter($userId);
		}

		static::recountFlowEfficiency((int)$taskData['ID'], $userId);

		return true;
	}

	/**
	 * Recounts efficiency user counter
	 *
	 * @param $userId
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function recountEfficiencyUserCounter($userId)
	{
		if (!self::isEnabled())
		{
			return;
		}

		$efficiency = static::getAverageEfficiency(null, null, $userId);
		static::setEfficiencyToUserCounter($userId, $efficiency);

		PushService::addEvent(
			$userId,
			[
				'module_id' => 'tasks',
				'command' => PushCommand::EFFICIENCY_RECOUNTED,
				'params' => [
					'value' => $efficiency,
				],
			]
		);
	}

	protected static function recountFlowEfficiency(int $taskId, int $userId): void
	{
		if ($taskId <= 0)
		{
			return;
		}

		$task = TaskRegistry::getInstance()->getObject($taskId);
		if ($task === null)
		{
			return;
		}

		if ($task->onFlow())
		{
			(new Efficiency(new LastMonth()))
				->invalidate($task->getFlowId());

			(new NotificationService())
				->onEfficiencyChanged($task->getFlowId());
		}
	}

	/**
	 * Creates agent for next efficiency recount
	 *
	 * @throws \Exception
	 */
	public static function createAgentForNextEfficiencyRecount()
	{
		if (!self::isEnabled())
		{
			return;
		}

		$date = new \DateTime();
		$date = $date->modify('first day of next month')->format('d.m.Y 00:00:01');

		\CAgent::AddAgent(
			'\Bitrix\Tasks\Internals\Effective::runEfficiencyRecount();',
			'tasks',
			'N',
			86400,
			"",
			"Y",
			$date
		);
	}

	/**
	 * Starts efficiency recount
	 *
	 * @throws Main\ArgumentOutOfRangeException
	 */
	public static function runEfficiencyRecount()
	{
		if (!self::isEnabled())
		{
			return;
		}

		Option::set("tasks", "needEfficiencyRecount", "Y");
		EfficiencyRecount::bind();
	}

	/**
	 * Repairs violations
	 *
	 * @param $taskId
	 * @param int $userId
	 * @param string $userType
	 * @return bool
	 * @throws Main\Db\SqlQueryException
	 */
	public static function repair($taskId, $userId = 0, $userType = 'R')
	{
		if (!self::isEnabled())
		{
			return true;
		}

		$taskId = (int)$taskId;

		$sql = "UPDATE b_tasks_effective 
				SET DATETIME_REPAIR = NOW() 
				WHERE TASK_ID = {$taskId} AND IS_VIOLATION = 'Y' AND DATETIME_REPAIR IS NULL";

		if ($userId)
		{
			$userType = ($userType == 'A'? 'A' : 'R');
			$sql .= ' AND USER_ID = ' . $userId . ' AND USER_TYPE = \'' . $userType . '\'';
		}

		Application::getConnection()->queryExecute($sql);

		return true;
	}

	/**
	 * @param $deadline
	 * @return bool
	 * @throws Main\ObjectException
	 */
	private static function isViolation($deadline)
	{
		if (!$deadline)
		{
			return false;
		}

		$deadline = DateTime::createFrom($deadline);
		$now = new DateTime();

		return $deadline->checkLT($now);
	}

	/**
	 * @param string $date
	 * @return string
	 * @throws \Exception
	 * @throws Main\Db\SqlQueryException
	 * @throws Main\ObjectException
	 */
	public static function agent($date = '')
	{
		if (!self::isEnabled())
		{
			return '';
		}

		$date = ($date? new DateTime($date, 'Y-m-d') : new DateTime());

		$sql = "
			SELECT DISTINCT 
			   ef1.USER_ID 
			FROM 
			   b_tasks_effective ef1
			WHERE 
			   NOT EXISTS (
			    SELECT ef2.ID FROM b_tasks_effective ef2 
			    WHERE 
			      ef2.DATETIME > '".$date->format('Y-m-d')." 00:00:00' AND 
			      ef2.DATETIME <= '".$date->format('Y-m-d')." 23:59:59' AND 
			      ef2.USER_ID = ef1.USER_ID 
				)
		";
		$users = Application::getConnection()->query($sql)->fetchAll();

		if (!empty($users))
		{
			foreach ($users as $user)
			{
				$userId = $user['USER_ID'];

				EffectiveTable::add([
					'DATETIME' => $date,
					'USER_ID' => $userId,
					'USER_TYPE' => '',
					'GROUP_ID' => 0,
					'EFFECTIVE' => static::getEfficiencyForNow($userId),
					'TASK_ID' => '',
					'IS_VIOLATION' => 'N',
				]);
			}
		}

		$date->addDay(1);

		return '\Bitrix\Tasks\Internals\Effective::agent("'.$date->format('Y-m-d').'");';
	}

	/**
	 * Returns grouped data of average efficiency for graph building
	 *
	 * @param DateTime|null $timeFrom
	 * @param DateTime|null $timeTo
	 * @param int $userId
	 * @param int $groupId
	 * @param string $groupBy
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function getEfficiencyForGraph(
		DateTime $timeFrom = null,
		Datetime $timeTo = null,
		int $userId = 0,
		int $groupId = 0,
		string $groupBy = 'DATE'
	)
	{
		if (!self::isEnabled())
		{
			return [];
		}

		if ($groupBy !== 'HOUR')
		{
			$groupBy = 'DATE';
		}
		$helper = static::getHelper();
		$expressions = [
			'EFFECTIVE' => new Entity\ExpressionField('EFFECTIVE', 'AVG(EFFECTIVE)'),
			'DATE' => new Entity\ExpressionField('DATE', $helper->getDatetimeToDateFunction('DATETIME')),
			'HOUR' => new Entity\ExpressionField('HOUR', $helper->formatDate('%%Y-%%m-%%d %%H:00:01', 'DATETIME')),
		];
		$select = [$expressions['EFFECTIVE'], $expressions[$groupBy]];
		$group = [$groupBy];

		$query = EffectiveTable::query();
		$query->setSelect($select);
		$query
			->where('DATETIME', '>=', $timeFrom)
			->where('DATETIME', '<=', $timeTo)
		;
		if ($userId)
		{
			$query->where('USER_ID', $userId);
			$group[] = 'USER_ID';
		}
		if ($groupId)
		{
			$query->where('GROUP_ID', $groupId);
			$group[] = 'GROUP_ID';
		}
		$query->setGroup($group);

		return $query->exec()->fetchAll();
	}

	/**
	 * @param $userId
	 * @param int $groupId
	 * @return float|int
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function getEfficiencyForNow($userId, $groupId = 0)
	{
		if (!self::isEnabled())
		{
			return 100;
		}

		static $cache = [];

		if (array_key_exists($userId, $cache) && array_key_exists($groupId, $cache[$userId]))
		{
			return $cache[$userId][$groupId];
		}

		$efficiency = 100;
		$expiredTasksCount = static::getExpiredTasksCountForNow($userId, $groupId);
		$inProgressTasksCount = static::getInProgressTasksCountForNow($userId, $groupId);

		if ($inProgressTasksCount > 0)
		{
			$efficiency = round(100 - ($expiredTasksCount / $inProgressTasksCount) * 100);
		}

		$cache[$userId][$groupId] = $efficiency;

		return $efficiency;
	}

	/**
	 * @param $userId
	 * @param int $groupId
	 * @return mixed
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private static function getExpiredTasksCountForNow($userId, $groupId = 0)
	{
		$query = new Query(TaskTable::getEntity());

		$query->setSelect([new Entity\ExpressionField('COUNT', 'COUNT(%s)', 'ID')]);
		$query->registerRuntimeField('TM', new Entity\ReferenceField(
			'TM',
			MemberTable::getEntity(),
			Join::on('this.ID', 'ref.TASK_ID')
				->where('ref.USER_ID', $userId)
				->whereIn('ref.TYPE', ['R', 'A']),
			['join_type' => 'inner']
		));
		$query
			->where(
				Query::filter()
					->logic('or')
					->where(
						Query::filter()
							->where('TM.TYPE', 'R')
							->whereColumn('CREATED_BY', '<>', 'RESPONSIBLE_ID')
					)
					->where(
						Query::filter()
							->where('TM.TYPE', 'A')
							->where('CREATED_BY', '<>', $userId)
							->where('RESPONSIBLE_ID', '<>', $userId)
					)
			)
			->where('CLOSED_DATE', NULL)
			->where('DEADLINE', '<', Deadline::getExpiredTime())
			->where('STATUS', '<', Status::SUPPOSEDLY_COMPLETED)
			->where(($groupId? Query::filter()->where('GROUP_ID', $groupId) : []));

		$count = $query->exec()->fetch();

		return $count['COUNT'];
	}

	/**
	 * @param $userId
	 * @param int $groupId
	 * @return mixed
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private static function getInProgressTasksCountForNow($userId, $groupId = 0)
	{
		$helper = static::getHelper();
		$expressions = [
			'COUNT' => new Entity\ExpressionField('COUNT', 'COUNT(%s)', 'ID'),
			'DATE' => new Entity\ExpressionField('DATE', $helper->getDatetimeToDateFunction('%s'), 'CLOSED_DATE'),
			'NOW' => new Main\DB\SqlExpression($helper->getCurrentDateFunction()),
		];

		$query = new Query(TaskTable::getEntity());

		$query->setSelect([$expressions['COUNT']]);
		$query->registerRuntimeField('TM', new Entity\ReferenceField(
			'TM',
			MemberTable::getEntity(),
			Join::on('this.ID', 'ref.TASK_ID')
				->where('ref.USER_ID', $userId)
				->whereIn('ref.TYPE', ['R', 'A']),
			['join_type' => 'inner']
		));
		$query
			->where(
				Query::filter()
					->logic('or')
					->where(
						Query::filter()
							->where('TM.TYPE', 'R')
							->whereColumn('CREATED_BY', '<>', 'RESPONSIBLE_ID')
					)
					->where(
						Query::filter()
							->where('TM.TYPE', 'A')
							->where('CREATED_BY', '<>', $userId)
							->where('RESPONSIBLE_ID', '<>', $userId)
					)
			)
			->where(
				Query::filter()
					->logic('or')
					->where(
						Query::filter()
							->where('CLOSED_DATE', NULL)
							->where('STATUS', '<>', Status::DEFERRED)
					)
					->where($expressions['DATE'], $expressions['NOW'])
			)
			->where(($groupId? Query::filter()->where('GROUP_ID', $groupId) : []));

		$count = $query->exec()->fetch();

		return $count['COUNT'];
	}

	/**
	 * Returns average efficiency for given dates.
	 * If at least one of dates is not set, it will try to take the dates from the filter preset user pinned as default.
	 * If filter options were not set in b_user_options for some reason and we still haven't dates,
	 * it will take the default dates from static::getDatesRange().
	 * For now they are borders of current month.
	 *
	 * @param DateTime|null $dateFrom
	 * @param DateTime|null $dateTo
	 * @param int $userId
	 * @param int $groupId
	 * @return int
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function getAverageEfficiency(DateTime $dateFrom = null, DateTime $dateTo = null, $userId = 0, $groupId = 0)
	{
		if (!self::isEnabled())
		{
			return 100;
		}

		if (!$dateFrom || !$dateTo)
		{
			// DO NOT USE DEFAULT FILTER FOR NOW
//			$defaultFilterFieldsValues = static::getDefaultFilterFieldsValues($userId);
//
//			$dateFrom = $defaultFilterFieldsValues['FROM'];
//			$dateTo = $defaultFilterFieldsValues['TO'];
//			$groupId = $defaultFilterFieldsValues['GROUP_ID'];
//
//			// filter options probably were not set in b_user_options, anyway still haven't dates
//			if (!$dateFrom || !$dateTo)
//			{
//				$datesRange = static::getDatesRange();
//
//				$dateFrom = $datesRange['FROM'];
//				$dateTo = $datesRange['TO'];
//			}

			$datesRange = static::getDatesRange();

			$dateFrom = $datesRange['FROM'];
			$dateTo = $datesRange['TO'];
		}

		$efficiency = 100;
		$violations = static::getViolationsCount($dateFrom, $dateTo, $userId, $groupId);
		$inProgress = static::getInProgressCount($dateFrom, $dateTo, $userId, $groupId);

		if ($inProgress > 0)
		{
			$efficiency = (int)round(100 - ($violations / $inProgress) * 100);
		}
		else if ($violations > 0)
		{
			$efficiency = 0;
		}

		if ($efficiency < 0)
		{
			$efficiency = 0;
		}

		return $efficiency;
	}

	/**
	 * Returns count of violations, completed tasks and tasks in progress for given dates range.
	 *
	 * @param DateTime $dateFrom
	 * @param DateTime $dateTo
	 * @param int $userId
	 * @param int $groupId
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function getCountersByRange(DateTime $dateFrom, DateTime $dateTo, $userId = 0, $groupId = 0)
	{
		if (!self::isEnabled())
		{
			return [
				'VIOLATIONS' => 0,
				'IN_PROGRESS' => 0,
				'COMPLETED' => 0,
			];
		}

		$userId = intval($userId);
		$groupId = intval($groupId);

		return [
			'VIOLATIONS' => static::getViolationsCount($dateFrom, $dateTo, $userId, $groupId),
			'IN_PROGRESS' => static::getInProgressCount($dateFrom, $dateTo, $userId, $groupId),
			'COMPLETED' => static::getCompletedCount($dateFrom, $dateTo, $userId, $groupId),
		] ;
	}

	/**
	 * @param DateTime $dateFrom
	 * @param DateTime $dateTo
	 * @param $userId
	 * @param $groupId
	 * @return mixed
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function getViolationsCount(DateTime $dateFrom, DateTime $dateTo, $userId, $groupId)
	{
		$count = static::getViolationsQuery($dateFrom, $dateTo, $userId, $groupId)
			->where('T.RESPONSIBLE_ID', '>', 0)
			->exec()
			->fetch();

		return $count['COUNT'];
	}

	protected static function getViolationsQuery(DateTime $dateFrom, DateTime $dateTo, $userId = null, $groupId = null): Query
	{
		$query = EffectiveTable::query();

		$query->setSelect([new Entity\ExpressionField('COUNT', 'COUNT(%s)', 'TASK_ID')]);
		$query->registerRuntimeField('T', new Entity\ReferenceField(
			'T',
			TaskTable::getEntity(),
			Join::on('this.TASK_ID', 'ref.ID'),
			['join_type' => 'inner']
		));
		$query
			->where(($userId? Query::filter()->where('USER_ID', $userId) : []))
			->where(($groupId? Query::filter()->where('GROUP_ID', $groupId) : []))
			->where('IS_VIOLATION', 'Y')
			->where(
				Query::filter()
					->where('DATETIME', '<=', $dateTo)
					->where(
						Query::filter()
							->logic('or')
							->where('DATETIME', '>=', $dateFrom)
							->where('DATETIME_REPAIR', NULL)
							->where('DATETIME_REPAIR', '>=', $dateFrom)
					)
			);

		return $query;
	}

	/**
	 * @param DateTime $dateFrom
	 * @param DateTime $dateTo
	 * @param $userId
	 * @param $groupId
	 * @return mixed
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private static function getInProgressCount(DateTime $dateFrom, DateTime $dateTo, $userId, $groupId)
	{
		$count = static::getInProgressQuery($dateFrom, $dateTo, $userId, $groupId)->exec()->fetch();

		return $count['COUNT'];
	}

	protected static function getInProgressQuery(DateTime $dateFrom, DateTime $dateTo, $userId = null, $groupId = null): Query
	{
		$query = TaskTable::query();

		$query->setSelect([new Entity\ExpressionField('COUNT', 'COUNT(%s)', 'ID')]);

		if ($userId)
		{
			$query->registerRuntimeField('TM', new Entity\ReferenceField(
				'TM',
				MemberTable::getEntity(),
				Join::on('this.ID', 'ref.TASK_ID')
					->where('ref.USER_ID', $userId)
				    ->whereIn('ref.TYPE', ['R', 'A']),
				['join_type' => 'inner']
			));

			$query
				->where(
					Query::filter()
						->logic('or')
						->where(
							Query::filter()
								->where('TM.TYPE', 'R')
								->whereColumn('CREATED_BY', '<>', 'RESPONSIBLE_ID')
						)
						->where(
							Query::filter()
								->where('TM.TYPE', 'A')
								->where('CREATED_BY', '<>', $userId)
								->where('RESPONSIBLE_ID', '<>', $userId)
						)
				);
		}
		else
		{
			$query->whereColumn('CREATED_BY', '<>', 'RESPONSIBLE_ID');
		}

		$query
			->where('CREATED_DATE', '<=', $dateTo)
			->where(
				Query::filter()
					->logic('or')
					->where('CLOSED_DATE', '>=', $dateFrom)
					->where('CLOSED_DATE', NULL)
			)
			->where('STATUS', '<>', Status::DEFERRED)
			->where(($groupId? Query::filter()->where('GROUP_ID', $groupId) : []));

		return $query;
	}

	/**
	 * @param DateTime $dateFrom
	 * @param DateTime $dateTo
	 * @param $userId
	 * @param $groupId
	 * @return mixed
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private static function getCompletedCount(DateTime $dateFrom, DateTime $dateTo, $userId, $groupId)
	{
		$query = new Query(TaskTable::getEntity());

		$query->setSelect([new Entity\ExpressionField('COUNT', 'COUNT(%s)', 'ID')]);

		if ($userId)
		{
			$query->registerRuntimeField('TM', new Entity\ReferenceField(
				'TM',
				MemberTable::getEntity(),
				Join::on('this.ID', 'ref.TASK_ID')
					->where('ref.USER_ID', $userId)
					->whereIn('ref.TYPE', ['R', 'A']),
				['join_type' => 'inner']
			));

			$query
				->where(
					Query::filter()
						->logic('or')
						->where(
							Query::filter()
								->where('TM.TYPE', 'R')
								->whereColumn('CREATED_BY', '<>', 'RESPONSIBLE_ID')
						)
						->where(
							Query::filter()
								->where('TM.TYPE', 'A')
								->where('CREATED_BY', '<>', $userId)
								->where('RESPONSIBLE_ID', '<>', $userId)
						)
				);
		}
		else
		{
			$query->whereColumn('CREATED_BY', '<>', 'RESPONSIBLE_ID');
		}

		$query
			->where('CLOSED_DATE', '>=', $dateFrom)
			->where('CLOSED_DATE', '<=', $dateTo)
			->where(($groupId? Query::filter()->where('GROUP_ID', $groupId) : []));

		$count = $query->exec()->fetch();

		return $count['COUNT'];
	}

	public static function getAverageEfficiencyForGroups(
		DateTime $dateFrom = null,
		DateTime $dateTo = null,
		int $userId = 0,
		array $groupIds = []
	): array
	{

		$efficiencies = array_fill_keys($groupIds, 100);

		if (!self::isEnabled())
		{
			return $efficiencies;
		}

		if (!$dateFrom || !$dateTo)
		{
			$datesRange = static::getDatesRange();

			$dateFrom = $datesRange['FROM'];
			$dateTo = $datesRange['TO'];
		}

		$violations = static::getViolationsCountForGroups($dateFrom, $dateTo, $userId, $groupIds);
		$inProgress = static::getInProgressCountForGroups($dateFrom, $dateTo, $userId, $groupIds);

		foreach ($efficiencies as $groupId => $efficiency)
		{
			if ($inProgress[$groupId] > 0)
			{
				$efficiencies[$groupId] = (int)round(100 - ($violations[$groupId] / $inProgress[$groupId]) * 100);
			}
			elseif ($violations[$groupId] > 0)
			{
				$efficiencies[$groupId] = 0;
			}

			if ($efficiencies[$groupId] < 0)
			{
				$efficiencies[$groupId] = 0;
			}
		}

		return $efficiencies;
	}

	private static function getViolationsCountForGroups(
		DateTime $dateFrom,
		DateTime $dateTo,
		int $userId,
		array $groupIds
	): array
	{
		$query = new Query(EffectiveTable::getEntity());
		$query->setSelect([
			'GROUP_ID',
			new Entity\ExpressionField('COUNT', 'COUNT(%s)', 'TASK_ID'),
		]);
		$query->registerRuntimeField('T', new Entity\ReferenceField(
			'T',
			TaskTable::getEntity(),
			Join::on('this.TASK_ID', 'ref.ID'),
			['join_type' => 'inner']
		));
		$query
			->where(($userId? Query::filter()->where('USER_ID', $userId) : []))
			->where((!empty($groupIds) ? Query::filter()->whereIn('GROUP_ID', $groupIds) : []))
			->where('IS_VIOLATION', 'Y')
			->where(
				Query::filter()
					->where('DATETIME', '<=', $dateTo)
					->where(
						Query::filter()
							->logic('or')
							->where('DATETIME', '>=', $dateFrom)
							->where('DATETIME_REPAIR', NULL)
							->where('DATETIME_REPAIR', '>=', $dateFrom)
					)
			);

		$count = array_fill_keys($groupIds, 0);
		$res = $query->exec();
		while ($item = $res->fetch())
		{
			$count[$item['GROUP_ID']] = (int)$item['COUNT'];
		}

		return $count;
	}

	private static function getInProgressCountForGroups(
		DateTime $dateFrom,
		DateTime $dateTo,
		int $userId,
		array $groupIds
	): array
	{
		$query = new Query(TaskTable::getEntity());
		$query->setSelect([
			'GROUP_ID',
			new Entity\ExpressionField('COUNT', 'COUNT(%s)', 'ID'),
		]);

		if ($userId > 0)
		{
			$query
				->registerRuntimeField('TM', new Entity\ReferenceField(
					'TM',
					MemberTable::getEntity(),
					Join::on('this.ID', 'ref.TASK_ID')
						->where('ref.USER_ID', $userId)
						->whereIn('ref.TYPE', ['R', 'A']),
					['join_type' => 'inner']
				))
				->where(
					Query::filter()
						->logic('or')
						->where(
							Query::filter()
								->where('TM.TYPE', 'R')
								->whereColumn('CREATED_BY', '<>', 'RESPONSIBLE_ID')
						)
						->where(
							Query::filter()
								->where('TM.TYPE', 'A')
								->where('CREATED_BY', '<>', $userId)
								->where('RESPONSIBLE_ID', '<>', $userId)
						)
				);
		}
		else
		{
			$query->whereColumn('CREATED_BY', '<>', 'RESPONSIBLE_ID');
		}

		$query
			->where('CREATED_DATE', '<=', $dateTo)
			->where(
				Query::filter()
					->logic('or')
					->where('CLOSED_DATE', '>=', $dateFrom)
					->where('CLOSED_DATE', NULL)
			)
			->where('STATUS', '<>', Status::DEFERRED)
			->where((!empty($groupIds) ? Query::filter()->whereIn('GROUP_ID', $groupIds) : []));

		$count = array_fill_keys($groupIds, 0);
		$res = $query->exec();
		while ($item = $res->fetch())
		{
			$count[$item['GROUP_ID']] = (int)$item['COUNT'];
		}

		return $count;
	}

	private static function getHelper(): Main\DB\SqlHelper
	{
		return Application::getConnection()->getSqlHelper();
	}
}
