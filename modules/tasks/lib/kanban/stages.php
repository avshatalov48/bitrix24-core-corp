<?php

namespace Bitrix\Tasks\Kanban;

use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Data\DeleteResult;
use Bitrix\Main\ORM\Data\Result;
use Bitrix\Main\ORM\Event;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Helper\Sort;
use Bitrix\Tasks\Internals\Log\LogFacade;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;
use Bitrix\Tasks\Internals\Task\SortingTable;
use Bitrix\Tasks\Internals\TaskTable as Task;
use Bitrix\Main\ORM\Fields\ArrayField;
use Bitrix\Tasks\Provider\Exception\InvalidGroupByException;
use Bitrix\Tasks\Provider\TaskList;
use Bitrix\Tasks\Provider\TaskQuery;
use Bitrix\Tasks\Provider\TaskQueryBuilder;
use Bitrix\Tasks\Util\User;
use CTaskMembers;
use CTasks;
use CUserOptions;
use Exception;
use TasksException;

Loc::loadMessages(__FILE__);

/**
 * Class StagesTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Stages_Query query()
 * @method static EO_Stages_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Stages_Result getById($id)
 * @method static EO_Stages_Result getList(array $parameters = [])
 * @method static EO_Stages_Entity getEntity()
 * @method static \Bitrix\Tasks\Kanban\Stage createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Kanban\StagesCollection createCollection()
 * @method static \Bitrix\Tasks\Kanban\Stage wakeUpObject($row)
 * @method static \Bitrix\Tasks\Kanban\StagesCollection wakeUpCollection($rows)
 */
class StagesTable extends DataManager
{
	public const MY_PLAN_VERSION = '6';

	/**
	 * System type of stages (new, in progress, etc.).
	 * Separated from other stages - timeline's stages, sprint's stages.
	 *
	 * @see TimeLineTable::getStages()
	 */
	public const SYS_TYPE_NEW = 'NEW';
	public const SYS_TYPE_PROGRESS = 'WORK';
	public const SYS_TYPE_REVIEW = 'REVIEW';
	public const SYS_TYPE_FINISH = 'FINISH';
	public const SYS_TYPE_DEFAULT = 'NEW';

	public const SYS_TYPE_NEW_COLOR = '00C4FB';
	public const SYS_TYPE_PROGRESS_COLOR = self::DEF_COLOR_STAGE;
	public const SYS_TYPE_FINISH_COLOR = '75D900';

	/**
	 * Default colors.
	 */
	public const DEF_COLOR_STAGE = '47D1E2';

	/**
	 * Allowed work modes.
	 */
	public const WORK_MODE_GROUP = 'G';
	public const WORK_MODE_USER = 'U';
	public const WORK_MODE_TIMELINE = 'P';
	public const WORK_MODE_ACTIVE_SPRINT = 'A';

	private static array $systemStages = [];

	/**
	 * Disable linked for these users.
	 */
	private static array $disableLink = [];

	/**
	 * Disable pin for these users.
	 */
	private static array $disablePin = [];

	/**
	 * Disable pin for these users.
	 */
	private static string $mode = 'G';

	/**
	 * @var array
	 */
	private static array $cache = [];

	public static function getTableName(): string
	{
		return 'b_tasks_stages';
	}

	public static function getMap(): array
	{
		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),
			(new StringField('TITLE')),
			(new IntegerField('SORT')),
			(new StringField('COLOR')),
			(new StringField('SYSTEM_TYPE')),
			(new IntegerField('ENTITY_ID')),
			(new StringField('ENTITY_TYPE')),
			(new ArrayField('ADDITIONAL_FILTER'))
				->configureSerializationPhp(),
			(new ArrayField('TO_UPDATE'))
				->configureSerializationPhp(),
			(new StringField('TO_UPDATE_ACCESS')),
		];
	}

	public static function checkWorkMode(string $mode): bool
	{
		return $mode === self::WORK_MODE_GROUP
			|| $mode === self::WORK_MODE_USER
			|| $mode === self::WORK_MODE_TIMELINE
			|| $mode === self::WORK_MODE_ACTIVE_SPRINT
		;
	}

	public static function setWorkMode(string $mode): void
	{
		if (self::checkWorkMode($mode))
		{
			self::$mode = $mode;
		}
	}

	public static function getWorkMode(): string
	{
		return self::$mode;
	}

	/**
	 * @throws Exception
	 */
	public static function systemDelete(int $id): DeleteResult
	{
		return parent::delete($id);
	}

	/**
	 * Base delete-method, first check that column is not system.
	 *
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws Exception
	 */
	public static function delete($primary, int $entityId = 0): DeleteResult|bool|null
	{
		$entityType = self::getWorkMode();

		$res = self::getList([
			'filter' => [
				'ID' => $primary,
				'ENTITY_ID' => $entityId,
				'=ENTITY_TYPE' => $entityType,
			],
		]);
		if ($stage = $res->fetch())
		{
			// user can't delete first stage
			if (
				$stage['SYSTEM_TYPE'] == self::SYS_TYPE_NEW
				&& $entityType !== self::WORK_MODE_ACTIVE_SPRINT
			)
			{
				$result = new DeleteResult();
				$result->addError(new Error(
					Loc::getMessage('TASKS_STAGE_ERROR_CANT_DELETE_FIRST'),
					'CANT_DELETE_FIRST'
				));
				return $result;
			}
			$res = parent::delete($stage['ID']);
			// remove tasks from this stage
			if ($res->isSuccess())
			{
				if ($entityType == self::WORK_MODE_GROUP)
				{
					$resT = Task::getList([
						'select' => ['ID'],
						'filter' => [
							'STAGE_ID' => $stage['ID'],
						],
					]);
					while ($row = $resT->fetch())
					{
						Task::update($row['ID'], [
							'STAGE_ID' => 0,
						]);
					}
				}
				elseif (
					$entityType === self::WORK_MODE_USER
					|| $entityType === self::WORK_MODE_ACTIVE_SPRINT
				)
				{
					$resT = TaskStageTable::getList([
						'filter' => [
							'STAGE_ID' => $stage['ID'],
						],
					]);
					while ($row = $resT->fetch())
					{
						TaskStageTable::delete($row['ID']);
					}
				}
			}
			return $res;
		}

		return false;
	}

	/**
	 * Get stages and create default.
	 *
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws Exception
	 */
	public static function getStages(int|string $entityId = 0, bool $disableCreate = false): array
	{
		static $stages = [];

		$entityType = self::getWorkMode();

		if (
			isset($stages[$entityType . $entityId])
			&& !empty($stages[$entityType . $entityId])
		)
		{
			return $stages[$entityType . $entityId];
		}

		$stages[$entityType . $entityId] = [];
		$predefinedStages = ($entityType == self::WORK_MODE_TIMELINE)
			? TimeLineTable::getStages()
			: [];

		$res = self::getList([
			'filter' => [
				'ENTITY_ID' => $entityId,
				'=ENTITY_TYPE' => $entityType,
			],
			'order' => [
				'SORT' => 'ASC',
			],
		]);
		while ($row = $res->fetch())
		{
			// set default color
			if ($row['COLOR'] == '')
			{
				$row['COLOR'] = self::DEF_COLOR_STAGE;
			}
			// set default title
			if ($row['TITLE'] == '')
			{
				if ($row['SYSTEM_TYPE'] != '')
				{
					$row['TITLE'] = Loc::getMessage('TASKS_STAGE_' . $row['SYSTEM_TYPE']);
				}
				else
				{
					$row['TITLE'] = Loc::getMessage('TASKS_STAGE_' . self::SYS_TYPE_DEFAULT);
				}
			}
			if ($row['SYSTEM_TYPE'])
			{
				if (
					!$row['ADDITIONAL_FILTER']
					&& isset($predefinedStages[$row['SYSTEM_TYPE']]['FILTER'])
				)
				{
					$row['ADDITIONAL_FILTER'] = $predefinedStages[$row['SYSTEM_TYPE']]['FILTER'];
					$row['ADDITIONAL_FILTER_TEST'] = $row['ADDITIONAL_FILTER'];
				}
				if (isset($row['ADDITIONAL_FILTER_TEST']))
				{
					foreach ($row['ADDITIONAL_FILTER_TEST'] as &$date)
					{
						if ($date instanceof DateTime)
						{
							$date = clone $date;
							$date = (string)$date;
						}
					}
					unset($date);
				}
				if (
					!$row['TO_UPDATE']
					&& isset($predefinedStages[$row['SYSTEM_TYPE']]['UPDATE'])
				)
				{
					$row['TO_UPDATE'] = $predefinedStages[$row['SYSTEM_TYPE']]['UPDATE'];
				}
				if (
					!$row['TO_UPDATE_ACCESS']
					&& isset($predefinedStages[$row['SYSTEM_TYPE']]['UPDATE_ACCESS'])
				)
				{
					$row['TO_UPDATE_ACCESS'] = $predefinedStages[$row['SYSTEM_TYPE']]['UPDATE_ACCESS'];
				}
			}
			$row['TO_UPDATE'] = (array)$row['TO_UPDATE'];
			$row['ADDITIONAL_FILTER'] = (array)$row['ADDITIONAL_FILTER'];
			$stages[$entityType . $entityId][$row['ID']] = $row;
		}
		if ($disableCreate)
		{
			return $stages[$entityType . $entityId];
		}
		// if empty, create default stages
		if (empty($stages[$entityType . $entityId]))
		{
			if ($entityType == self::WORK_MODE_USER)
			{
				self::add([
					'SYSTEM_TYPE' => self::SYS_TYPE_NEW,
					'TITLE' => Loc::getMessage('TASKS_STAGE_MP_1'),
					'SORT' => 100,
					'ENTITY_ID' => $entityId,
					'ENTITY_TYPE' => $entityType,
					'COLOR' => static::SYS_TYPE_NEW_COLOR,
				]);
				self::add([
					'TITLE' => Loc::getMessage('TASKS_STAGE_MP_2'),
					'SORT' => 200,
					'ENTITY_ID' => $entityId,
					'ENTITY_TYPE' => $entityType,
					'COLOR' => static::DEF_COLOR_STAGE,
				]);
			}
			elseif ($entityType == self::WORK_MODE_GROUP)
			{
				if ($entityId > 0)
				{
					self::getStages();
					self::copyView(0, $entityId);
				}
				else
				{
					self::add([
						'SYSTEM_TYPE' => self::SYS_TYPE_NEW,
						'SORT' => 100,
						'ENTITY_ID' => $entityId,
						'ENTITY_TYPE' => $entityType,
						'COLOR' => static::SYS_TYPE_NEW_COLOR,
					]);
					self::add([
						'SYSTEM_TYPE' => self::SYS_TYPE_PROGRESS,
						'SORT' => 200,
						'ENTITY_ID' => $entityId,
						'ENTITY_TYPE' => $entityType,
						'COLOR' => static::SYS_TYPE_PROGRESS_COLOR,
					]);
					self::add([
						'SYSTEM_TYPE' => self::SYS_TYPE_FINISH,
						'SORT' => 300,
						'ENTITY_ID' => $entityId,
						'ENTITY_TYPE' => $entityType,
						'COLOR' => static::SYS_TYPE_FINISH_COLOR,
					]);
				}
			}
			else
			{
				$i = 0;

				if ($entityType == self::WORK_MODE_TIMELINE)
				{
					$source = TimeLineTable::getStages();
				}
				else
				{
					return [];
				}

				foreach ($source as $stageCode => $stageItem)
				{
					self::add([
						'SYSTEM_TYPE' => array_key_exists('SYSTEM_TYPE', $stageItem)
							? $stageItem['SYSTEM_TYPE']
							: $stageCode,
						'TITLE' => array_key_exists('TITLE', $stageItem)
							? $stageItem['TITLE']
							: '',
						'SORT' => ++$i * 100,
						'ENTITY_ID' => $entityId,
						'ENTITY_TYPE' => $entityType,
						'COLOR' => $stageItem['COLOR'],
					]);
				}
			}

			return self::getStages($entityId);
		}

		return $stages[$entityType . $entityId];
	}

	/**
	 * @param int $groupId
	 * @param bool $disableCreate
	 * @return array
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public static function getGroupStages(int $groupId, bool $disableCreate = false): array
	{
		$previousMode = self::getWorkMode();
		self::setWorkMode(self::WORK_MODE_GROUP);
		$stages = self::getStages($groupId, $disableCreate);
		self::setWorkMode($previousMode);

		return $stages;
	}

	/**
	 * @param int $sprintId
	 * @param bool $disableCreate
	 * @return array
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public static function getActiveSprintStages(int $sprintId, bool $disableCreate = false): array
	{
		$previousMode = self::getWorkMode();
		self::setWorkMode(self::WORK_MODE_ACTIVE_SPRINT);
		$stages = self::getStages($sprintId, $disableCreate);
		self::setWorkMode($previousMode);

		return $stages;
	}

	/**
	 * @param int $stageId
	 * @param int $groupId
	 * @return array|null
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public static function getGroupStageById(int $stageId, int $groupId): ?array
	{
		$stages = self::getGroupStages($groupId, true);

		foreach ($stages as $stage)
		{
			if ($stageId === 0 && $stage['SYSTEM_TYPE'] === self::SYS_TYPE_NEW)
			{
				return $stage;
			}

			if ((int)$stage['ID'] === $stageId)
			{
				return $stage;
			}
		}

		return null;
	}

	/**
	 * Add or update stages by code/id.
	 *
	 * @throws ArgumentException
	 * @throws SqlQueryException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws Exception
	 */
	public static function updateByCode($id, $fields): ?Result
	{
		$id = intval($id);
		$afterId = isset($fields['AFTER_ID']) ? intval($fields['AFTER_ID']) : 0;
		$entityId = isset($fields['ENTITY_ID']) ? intval($fields['ENTITY_ID']) : 0;
		$entityType = self::getWorkMode();
		if ($entityType == self::WORK_MODE_TIMELINE)
		{
			return null;
		}
		// get stages
		$newStageId = 0;
		$stages = [];
		$res = self::getList([
			'filter' => [
				'ENTITY_ID' => $entityId,
				'=ENTITY_TYPE' => $entityType,
			],
			'order' => [
				'SORT' => 'ASC',
			],
		]);
		while ($row = $res->fetch())
		{
			if ($row['SYSTEM_TYPE'] == self::SYS_TYPE_NEW)
			{
				$newStageId = $row['ID'];
			}
			$stages[$row['ID']] = $row;
		}
		// if move first - update tasks for fix in this stage
		if (
			isset($fields['AFTER_ID'])
			&& isset($stages[$id])
			&& $entityType == self::WORK_MODE_GROUP
		)
		{
			if (
				$fields['AFTER_ID'] == 0
				|| $stages[$id]['SYSTEM_TYPE'] == self::SYS_TYPE_NEW
			)
			{
				$connection = Application::getConnection();
				$sql = 'UPDATE '
					. '`' . Task::getTableName() . '` '
					. 'SET STAGE_ID=' . ($newStageId) . ' '
					. 'WHERE STAGE_ID=0 AND GROUP_ID=' . $entityId . ';';
				$connection->query($sql);
			}
		}
		// set new
		if (!isset($stages[$id]))
		{
			$id = 0;
		}
		$stages[$id] = array_merge(
			$stages[$id] ?? [],
			$fields
		);
		// set sort
		if (array_key_exists('AFTER_ID', $fields))
		{
			if ($afterId == 0)
			{
				$stages[$id]['SORT'] = 10;
			}
			elseif (isset($stages[$afterId]))
			{
				$stages[$id]['SORT'] = $stages[$afterId]['SORT'] + 10;
			}
			else
			{
				$stages[$id]['SORT'] = count($stages) * 100 + 10;
			}
		}
		uasort($stages, function ($a, $b) {
			if ($a['SORT'] == $b['SORT'])
			{
				return 0;
			}
			return ($a['SORT'] < $b['SORT']) ? -1 : 1;
		});
		// renew
		$return = null;
		$sort = 100;
		foreach ($stages as $i => $stage)
		{
			if ($entityType == self::WORK_MODE_ACTIVE_SPRINT)
			{
				$systemType = $stage['SYSTEM_TYPE'] ?? '';
			}
			else
			{
				if (
					$stage['TITLE']
					|| $stage['SYSTEM_TYPE'] == self::SYS_TYPE_NEW
				)
				{
					$stage['SYSTEM_TYPE'] = '';
				}
				$systemType = ($sort == 100 ? self::SYS_TYPE_NEW : $stage['SYSTEM_TYPE']);
			}

			$fields = [
				'TITLE' => $stage['TITLE'],
				'COLOR' => $stage['COLOR'],
				'ENTITY_ID' => $stage['ENTITY_ID'],
				'ENTITY_TYPE' => $entityType,
				'SORT' => $sort,
				'SYSTEM_TYPE' => $systemType,
			];

			$sort += 100;
			if ($i > 0)
			{
				$res = self::update($i, $fields);
			}
			else
			{
				$res = self::add($fields);
			}
			if ($i == $id)
			{
				$return = $res;
			}
		}

		return $return;
	}

	/**
	 * Get stages id by stage code.
	 *
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws Exception
	 */
	public static function getStageIdByCode(int $id, int $entityId = 0): int|array
	{
		if (self::getWorkMode() == self::WORK_MODE_USER)
		{
			return $id;
		}

		$stages = self::getStages($entityId);

		if (isset($stages[$id]))
		{
			$stage = $stages[$id];
			return match ($stage['SYSTEM_TYPE'])
			{
				self::SYS_TYPE_NEW => [$stage['ID'], 0],
				default => $stage['ID'],
			};
		}

		return -1;
	}

	/**
	 * Get default stage id.
	 *
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws Exception
	 */
	public static function getDefaultStageId($id = 0): int
	{
		foreach (self::getStages($id) as $stage)
		{
			if ($stage['SYSTEM_TYPE'] == self::SYS_TYPE_NEW)
			{
				return $stage['ID'];
			}
		}

		return -1;
	}

	/**
	 * @throws InvalidGroupByException
	 */
	public static function getStagesCount(array $stageList, array $filter = [], mixed $userId = false): array
	{
		if ($userId === false)
		{
			$userId = User::getId();
		}
		$userId = intval($userId);

		$stageList = array_map('intval', array_keys($stageList));

		if (
			self::getWorkMode() == self::WORK_MODE_USER
			|| self::getWorkMode() == self::WORK_MODE_ACTIVE_SPRINT
		)
		{
			$filter = [
				'STAGES_ID' => $stageList,
				'::SUBFILTER-1' => $filter,
			];
			$select = [
				'STAGES_ID',
				'COUNT',
			];
			$groupBy = [
				'STAGES_ID',
			];
		}
		else
		{
			$filter = [
				'STAGE_ID' => $stageList,
				'::SUBFILTER-1' => $filter,
			];
			$select = [
				'STAGE_ID',
				'COUNT',
			];
			$groupBy = [
				'STAGE_ID',
			];
		}

		$query = new TaskQuery($userId);
		$query
			->setSelect($select)
			->setGroupBy($groupBy)
			->setWhere($filter);

		$counts = [];
		try
		{
			$list = new TaskList();
			$counts = $list->getList($query);

			$sql = TaskQueryBuilder::getLastQuery();
		}
		catch (Exception $e)
		{
			LogFacade::logThrowable($e);
		}

		$res = [];
		foreach ($counts as $row)
		{
			$stageId = $row['STAGES_ID'] ?? $row['STAGE_ID'];
			$res[$stageId] = $row['COUNT'];
		}

		return $res;
	}

	/**
	 * Copy view from one entity to another.
	 *
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws Exception
	 */
	public static function copyView(int $fromEntityId, int $toEntityId, string $entityType = self::WORK_MODE_GROUP): array|bool
	{
		if (
			$fromEntityId != $toEntityId
			&& (
				$entityType == self::WORK_MODE_USER
				|| $entityType == self::WORK_MODE_GROUP
				|| $entityType == self::WORK_MODE_ACTIVE_SPRINT
			)
		)
		{
			$result = [];
			$res = self::getList([
				'filter' => [
					'ENTITY_ID' => $fromEntityId,
					'=ENTITY_TYPE' => $entityType,
				],
				'order' => [
					'ID' => 'ASC',
				],
			]);
			while ($row = $res->fetch())
			{
				$oldStageId = $row['ID'];
				if (!$row['TITLE'])
				{
					$row['TITLE'] = Loc::getMessage('TASKS_STAGE_' . $row['SYSTEM_TYPE']);
				}
				if (
					$row['SYSTEM_TYPE']
					&& ($row['SYSTEM_TYPE'] != self::SYS_TYPE_NEW)
				)
				{
					unset($row['SYSTEM_TYPE']);
				}
				unset($row['ID']);
				$row['ENTITY_ID'] = $toEntityId;
				$newStageId = (($addResult = self::add($row)) ? $addResult->getId() : false);
				$result[$oldStageId] = $newStageId;
			}
			return $result;
		}

		return false;
	}

	/**
	 * Disable pin in stage for user.
	 */
	public static function disablePinForUser(array|int $userIds): void
	{
		if (!is_array($userIds))
		{
			$userIds = [$userIds];
		}
		self::$disablePin = array_merge(self::$disablePin, $userIds);
	}

	/**
	 * Disable link in stage for user.
	 */
	public static function disableLinkForUser(array|int $userIds): void
	{
		if (!is_array($userIds))
		{
			$userIds = [$userIds];
		}
		self::$disableLink = array_merge(self::$disableLink, $userIds);
	}

	/**
	 * Pin the task in the DEFAULT stage for users/group.
	 *
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws Exception
	 */
	public static function pinInStage(int $taskId, int|array $users = [], bool $refreshGroup = false): void
	{
		if (!is_array($users))
		{
			$users = [$users];
		}
		$newTask = empty($users);

		// get additional data
		$currentUsers = [];
		$task = TaskRegistry::getInstance()->get($taskId);
		if (!$task)
		{
			return;
		}

		$currentUsers[] = $task['RESPONSIBLE_ID'];
		$currentUsers[] = $task['CREATED_BY'];

		// get current other members
		$res = CTaskMembers::GetList(
			[],
			['TASK_ID' => $taskId]
		);
		while ($row = $res->fetch())
		{
			$currentUsers[] = $row['USER_ID'];
		}

		if ($newTask)
		{
			$users = $currentUsers;
			$currentUsers = [];
		}

		$users = array_unique($users);

		// pin in personal default stage (if already Kanban exist)
		$personalDefStages = [];
		self::setWorkMode(self::WORK_MODE_USER);
		foreach ($users as $userId)
		{
			$checkStages = self::getStages($userId, true);
			if (!empty($checkStages))
			{
				$personalDefStages[$userId] = self::getDefaultStageId($userId);
				if (!in_array($userId, self::$disableLink))
				{
					$resStg = TaskStageTable::getList([
						'filter' => [
							'TASK_ID' => $taskId,
							'STAGE_ID' => array_keys($checkStages),
						],
					]);
					if (!$resStg->fetch())
					{
						$fields = [
							'TASK_ID' => $taskId,
							'STAGE_ID' => self::getDefaultStageId($userId),
						];
						if (
							!TaskStageTable::getList([
									'filter' => $fields,
								]
							)->fetch()
						)
						{
							try
							{
								TaskStageTable::add($fields);
							}
							catch (Exception $exception)
							{
								LogFacade::logThrowable($exception);
							}
						}
					}
				}
			}
		}

		// work mode
		self::setWorkMode(
			$task['GROUP_ID'] > 0
				? self::WORK_MODE_GROUP
				: self::WORK_MODE_USER
		);

		if ($task['GROUP_ID'] > 0 && ($newTask || $refreshGroup))
		{
			$checkStages = self::getStages($task['GROUP_ID'], true);
		}
		else
		{
			$checkStages = [];
		}

		// one sort for project
		if ($task['GROUP_ID'] > 0 && !empty($checkStages) && ($newTask || $refreshGroup))
		{
			// get order
			$project = self::getProject((int) $task['GROUP_ID']);
			if ($project)
			{
				$order = $project['ORDER_NEW_TASK'] ?: 'desc';
			}
			else
			{
				$order = 'desc';
			}

			// set sorting
			$targetId = (new Sort())->getPositionForGroup($taskId, $order, (int)$task['GROUP_ID']);
			if ($targetId)
			{
				SortingTable::setSorting(
					User::getId() > 0 ? User::getId() : $task['CREATED_BY'],
					$task['GROUP_ID'],
					$taskId,
					$targetId,
					!($order == 'asc')
				);
			}
		}

		// and for each user
		foreach ($users as $userId)
		{
			if (
				$userId
				&& !in_array($userId, self::$disablePin)
				&& !in_array($userId, $currentUsers)
				&& isset($personalDefStages[$userId])
			)
			{
				// get order
				$order = CUserOptions::getOption(
					'tasks',
					'order_new_task',
					'desc',
					$userId
				);

				$targetId = (new Sort())->getPositionForUser($taskId, $order, (int)$userId);
				if ($targetId)
				{
					SortingTable::setSorting(
						$userId,
						0,
						$taskId,
						$targetId,
						!($order == 'asc')
					);
				}
			}
		}
	}

	/**
	 * Pin the task in the stage for user/group.
	 *
	 * @throws SqlQueryException
	 * @throws ObjectPropertyException
	 * @throws TasksException
	 * @throws ArgumentException
	 * @throws SystemException
	 */
	public static function pinInTheStage(int $taskId, int $stageId): void
	{
		if (($stage = StagesTable::getById($stageId)->fetch()))
		{
			$order = 'desc';
			// get order
			if ($stage['ENTITY_TYPE'] == self::WORK_MODE_GROUP)
			{
				if (($project = ProjectsTable::getById($stage['ENTITY_ID'])->fetch()))
				{
					$order = $project['ORDER_NEW_TASK'] ?: 'desc';
				}
			}
			else
			{
				$order = CUserOptions::getOption(
					'tasks',
					'order_new_task',
					'desc',
					$stage['ENTITY_ID']
				);
			}
			// set order
			if ($order == 'desc')
			{
				$sort = [
					'SORTING' => 'ASC',
					'STATUS_COMPLETE' => 'ASC',
					'DEADLINE' => 'ASC,NULLS',
					'ID' => 'ASC',
				];
			}
			else
			{
				$sort = [
					'SORTING' => 'DESC',
					'STATUS_COMPLETE' => 'DESC',
					'DEADLINE' => 'DESC',
					'ID' => 'DESC',
				];
			}
			// set filter
			$filter = [
				'CHECK_PERMISSIONS' => 'N',
				'ONLY_ROOT_TASKS' => 'N',
				'!ID' => $taskId,
			];
			if ($stage['ENTITY_TYPE'] == self::WORK_MODE_GROUP)
			{
				$filter['GROUP_ID'] = $stage['ENTITY_ID'];
			}
			else
			{
				$filter['MEMBER'] = $stage['ENTITY_ID'];
			}
			// set params
			$params = [
				'NAV_PARAMS' => [
					'nTopCount' => 1,
				],
			];
			if ($stage['ENTITY_TYPE'] == self::WORK_MODE_GROUP)
			{
				$params['SORTING_GROUP_ID'] = $stage['ENTITY_ID'];
			}
			else
			{
				$params['USER_ID'] = $stage['ENTITY_ID'];
			}
			// set sorting
			$res = CTasks::getList(
				$sort,
				$filter,
				['ID'],
				$params
			);
			if ($row = $res->fetch())
			{
				if ($stage['ENTITY_TYPE'] == self::WORK_MODE_GROUP)
				{
					$userId = User::getId();
					$groupId = $stage['ENTITY_ID'];
				}
				else
				{
					$userId = $stage['ENTITY_ID'];
					$groupId = 0;
				}
				SortingTable::setSorting(
					$userId,
					$groupId,
					$taskId,
					$row['ID'],
					!($order == 'asc')
				);
			}
		}
	}

	/**
	 * Delete all stages and sprints of group after group delete.
	 *
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 * @throws Exception
	 */
	public static function onSocNetGroupDelete(int $groupId): void
	{
		$res = self::getList([
			'filter' => [
				'ENTITY_ID' => $groupId,
				'=ENTITY_TYPE' => self::WORK_MODE_GROUP,
			],
		]);
		while ($row = $res->fetch())
		{
			parent::delete($row['ID']);
		}
	}

	/**
	 * Delete all stages of user after user delete.
	 *
	 * @throws Exception
	 */
	public static function onUserDelete(int $userId): void
	{
		$res = self::getList([
			'filter' => [
				'ENTITY_ID' => $userId,
				'=ENTITY_TYPE' => [
					self::WORK_MODE_USER,
					self::WORK_MODE_TIMELINE,
				],
			],
		]);
		while ($row = $res->fetch())
		{
			parent::delete($row['ID']);
		}
	}

	public static function onDelete(Event $event): void
	{
		$primary = $event->getParameter('id');
		TaskStageTable::clearStage($primary['ID']);
	}

	/**
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public static function getSystemStage(int $groupId, bool $force = false): ?Stage
	{
		if (isset(static::$systemStages[$groupId]) && !$force)
		{
			return static::$systemStages[$groupId];
		}

		$query = static::query()
			->where('ENTITY_ID', $groupId)
			->where('ENTITY_TYPE', static::WORK_MODE_GROUP)
			->where('SYSTEM_TYPE', static::SYS_TYPE_NEW);

		static::$systemStages[$groupId] = $query->exec()->fetchObject();

		return static::$systemStages[$groupId];
	}

	public static function getObjectClass(): string
	{
		return Stage::class;
	}

	public static function getCollectionClass(): string
	{
		return StagesCollection::class;
	}

	/**
	 * @param int $groupId
	 * @return mixed|null
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	private static function getProject(int $groupId)
	{
		if (
			isset(self::$cache['projects'])
			&& array_key_exists($groupId, self::$cache['projects'])
		)
		{
			return self::$cache['projects'][$groupId];
		}

		$project = ProjectsTable::getById($groupId)->fetch();
		if ($project)
		{
			self::$cache['projects'][$groupId] = $project;
			return self::$cache['projects'][$groupId];
		}

		return null;
	}
}
