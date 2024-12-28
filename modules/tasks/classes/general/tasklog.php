<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2013 Bitrix
 */

use Bitrix\Main\ArgumentException;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Socialnetwork\Item\Workgroup;
use Bitrix\Tasks\Control\Log\Change;
use Bitrix\Tasks\Control\Log\Command\AddCommand;
use Bitrix\Tasks\Control\Log\Command\DeleteByTaskIdCommand;
use Bitrix\Tasks\Control\Log\TaskLogService;
use Bitrix\Tasks\InvalidCommandException;
use Bitrix\Tasks\Kanban\StagesTable;
use Bitrix\Tasks\Provider\Exception\Log\TaskLogProviderException;
use Bitrix\Tasks\Provider\Log\TaskLogProvider;
use Bitrix\Tasks\Provider\Log\TaskLogQuery;
use Bitrix\Tasks\Scrum\Service\KanbanService;
use Bitrix\Tasks\Scrum\Service\SprintService;
use Bitrix\Tasks\Util\Db;
use Psr\Container\NotFoundExceptionInterface;

/**
 * @deprecated
 *
 * @use TaskLogService for C*UD operations
 * @use TaskLogProvider to get task history
 */
class CTaskLog
{
	// left for compatibility
	static array $arComparedFields = array(
		'TITLE' => 'string',
		'DESCRIPTION' => 'text',
		'STATUS' => 'integer',
		'PRIORITY' => 'integer',
		'MARK' => 'string',
		'PARENT_ID' => 'integer',
		'GROUP_ID' => 'integer',
		'STAGE_ID' => 'integer',
		'CREATED_BY' => 'integer',
		'RESPONSIBLE_ID' => 'integer',
		'ACCOMPLICES' => 'array',
		'AUDITORS' => 'array',
		'DEADLINE' => 'date',
		'START_DATE_PLAN' => 'date',
		'END_DATE_PLAN' => 'date',
		'DURATION_PLAN' => 'integer',
		'DURATION_PLAN_SECONDS' => 'integer',
		'DURATION_FACT' => 'integer',
		'TIME_ESTIMATE' => 'integer',
		'TIME_SPENT_IN_LOGS' => 'integer',
		'TAGS' => 'array',
		'DEPENDS_ON' => 'array',
		'FILES' => 'array',
		'UF_TASK_WEBDAV_FILES' => 'array',
		'CHECKLIST_ITEM_CREATE' => 'string',
		'CHECKLIST_ITEM_RENAME' => 'string',
		'CHECKLIST_ITEM_REMOVE' => 'string',
		'CHECKLIST_ITEM_CHECK' => 'string',
		'CHECKLIST_ITEM_UNCHECK' => 'string',
		'ADD_IN_REPORT' => 'bool',
		'TASK_CONTROL' => 'bool',
		'ALLOW_TIME_TRACKING' => 'bool',
		'ALLOW_CHANGE_DEADLINE' => 'bool',
		'FLOW_ID' => 'integer',
	);

	/**
	 * @use TaskLogService::getTrackedFields
	 *
	 * @throws ObjectNotFoundException
	 * @throws NotFoundExceptionInterface
	 */
	public static function getTrackedFields(): array
	{
		$service = ServiceLocator::getInstance()->get('tasks.control.log.task.service');

		return $service->getTrackedFields();
	}

	public static function CheckFields(
		/** @noinspection PhpUnusedParameterInspection */
		&$arFields, $ID = false
	): bool
	{
		if ((string)($arFields['CREATED_DATE'] ?? null) == '')
		{
			$arFields['CREATED_DATE'] = \Bitrix\Tasks\Util\Type\DateTime::getCurrentTimeString();
		}

		return true;
	}

	/**
	 * @use TaskLogService::add
	 * @throws InvalidCommandException
	 * @throws ObjectNotFoundException
	 * @throws NotFoundExceptionInterface
	 */
	public function Add(array $arFields): bool|int
	{
		/** @noinspection PhpDeprecationInspection */
		static::CheckFields($arFields);
		$createdDate = DateTime::createFromUserTime($arFields['CREATED_DATE']);

		if ($arFields['USER_ID'] === null
			|| $arFields['TASK_ID'] === null
			|| $arFields['FIELD'] === null
		)
		{
			// For compatibility
			return false;
		}

		$command = (new AddCommand())
			->setUserId($arFields['USER_ID'])
			->setTaskId($arFields['TASK_ID'])
			->setField($arFields['FIELD'])
			->setChange(new Change($arFields['FROM_VALUE'] ?? null, $arFields['TO_VALUE'] ?? null))
			->setCreatedDate($createdDate);

		$service = ServiceLocator::getInstance()->get('tasks.control.log.task.service');

		try
		{
			$log = $service->add($command);
		} catch (Exception)
		{
			// For compatibility
			return false;
		}

		return $log->getId();
	}

	public static function GetFilter(array $filter): array
	{
		$arSqlSearch = [];

		foreach ($filter as $column => $value)
		{
			$createdFilter = CTasks::MkOperationFilter($column);
			$column = $createdFilter["FIELD"];
			$cOperationType = $createdFilter["OPERATION"];

			$column = strtoupper($column);

			switch ($column)
			{
				case "CREATED_DATE":
					$arSqlSearch[] = CTasks::FilterCreate(
						"TL." . $column,
						Db::charToDateFunction($value),
						"date",
						$bFullJoin,
						$cOperationType
					);

					break;
				case "USER_ID":
				case "TASK_ID":
					$arSqlSearch[] = CTasks::FilterCreate(
						"TL." . $column,
						$value,
						"number",
						$bFullJoin,
						$cOperationType
					);

					break;
				case "FIELD":
					$arSqlSearch[] = CTasks::FilterCreate(
						"TL." . $column,
						$value,
						"string",
						$bFullJoin,
						$cOperationType
					);

					break;
			}
		}

		return $arSqlSearch;
	}

	/**
	 * @use TaskLogProvider::getList
	 * @throws TaskLogProviderException
	 * @throws ArgumentException
	 */
	public static function GetList(array $orders, array $filters): CDBResult
	{
		$query = new TaskLogQuery();
		$query
			->setDistinct(false)
			->setSelect([
				'ID',
				'CREATED_DATE',
				'USER_ID',
				'TASK_ID',
				'FIELD',
				'FROM_VALUE',
				'TO_VALUE',
				'USER_NAME' => 'USER.NAME',
				'USER_LAST_NAME' => 'USER.LAST_NAME',
				'USER_SECOND_NAME' => 'USER.SECOND_NAME',
				'USER_LOGIN' => 'USER.LOGIN',
			]);

		$possibleColumns = ['CREATED_DATE', 'USER_ID', 'TASK_ID', 'FIELD'];
		$possibleOperations = ['!=', '=', '>', '>=', '<', '<=', 'like', 'in'];

		$preparedFilters = [];
		foreach ($filters as $column => $value)
		{
			$createdFilter = CTasks::MkOperationFilter($column);

			$column = $createdFilter['FIELD'];
			$column = mb_strtoupper($column);

			if (!in_array($column, $possibleColumns, true))
			{
				continue;
			}

			if ($column === 'CREATED_DATE')
			{
				if (!is_string($value))
				{
					continue;
				}

				try
				{
					$value = new DateTime($value);
				}
				catch (\Bitrix\Main\ObjectException)
				{
					return (new CDBResult());
				}
			}

			$operator = array_search($createdFilter['OPERATION'], CSQLWhere::$operations, true);
			if ($column === 'FIELD')
			{
				if ($operator === '%')
				{
					$value = "%{$value}%";
				}

				$operator = 'like';
			}

			if (is_array($value))
			{
				$operator = 'in';
			}

			if (!in_array($operator, $possibleOperations, true))
			{
				$operator = '=';
			}

			$preparedFilters[] = [
				'COLUMN' => $column,
				'OPERATOR' => $operator,
				'VALUE' => $value,
			];
		}

		$whereCondition = new ConditionTree();
		foreach ($preparedFilters as $filter)
		{
			$whereCondition->where($filter['COLUMN'], $filter['OPERATOR'], $filter['VALUE']);
		}
		$query->setWhere($whereCondition);

		$possibleOrders = ['USER', 'USER_ID', 'FIELD', 'TASK_ID', 'CREATED_DATE'];

		$preparedOrders = [];
		foreach ($orders as $by => $order)
		{
			$by = mb_strtoupper($by);
			if (!in_array($by, $possibleOrders, true))
			{
				continue;
			}

			if ($by === 'USER')
			{
				$by = 'USER_ID';
			}

			$order = mb_strtoupper($order);
			if ($order !== 'ASC')
			{
				$order = 'DESC';
			}

			$preparedOrders[$by] = $order;
		}

		if (empty($preparedOrders))
		{
			$preparedOrders['CREATED_DATE'] = 'ASC';
		}
		$query->setOrderBy($preparedOrders);

		$collection = (new TaskLogProvider)->getList($query);

		$res = (new CDBResult());
		$res->InitFromArray($collection->toArray());

		return $res;
	}

	/**
	 * @use TaskLogService::getChanges
	 *
	 * @param $currentFields
	 * @param $newFields
	 * @return array
	 * @throws NotFoundExceptionInterface
	 * @throws ObjectNotFoundException
	 * @throws ArgumentException
	 * @throws LoaderException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public static function GetChanges($currentFields, $newFields): array
	{
		$service = ServiceLocator::getInstance()->get('tasks.control.log.task.service');

		$changes = $service->getChanges($currentFields, $newFields);

		// For compatibility
		foreach ($changes as $key => $change)
		{
			$changes[$key] = $change->toArray();

			foreach ($changes[$key] as $field => $value)
			{
				if ($value === null)
				{
					$changes[$key][$field] = false;
				}
			}
		}

		return $changes;
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws LoaderException
	 * @throws ArgumentException
	 * @throws SystemException
	 */
	public static function getStageChanges(int $oldStageId, int $newStageId, int $oldGroupId, int $newGroupId): array
	{
		if ($newGroupId !== $oldGroupId)
		{
			return [];
		}

		$isScrum = false;
		if (Loader::includeModule('socialnetwork'))
		{
			$group = Workgroup::getById($newGroupId);
			$isScrum = ($group && $group->isScrumProject());
		}

		if ($isScrum)
		{
			$kanbanService = new KanbanService();

			if (!$oldStageId && $oldGroupId)
			{
				$sprintService = new SprintService();

				$sprint = $sprintService->getActiveSprintByGroupId($newGroupId);

				$oldStageId = $kanbanService->getDefaultStageId($sprint->getId());
			}

			$stageTitles = $kanbanService->getStageTitles([$newStageId, $oldStageId]);

			return [
				'FROM_VALUE' => $stageTitles[$oldStageId],
				'TO_VALUE' => $stageTitles[$newStageId],
			];
		}

		if (!$oldStageId && $oldGroupId)
		{
			$oldStageId = StagesTable::getDefaultStageId($oldGroupId);
		}

		$stageFrom = false;
		$stageTo = false;

		$res = StagesTable::getList([
			'select' => ['ID', 'TITLE'],
			'filter' => ['@ID' => [$oldStageId, $newStageId]],
		]);
		while ($stage = $res->fetch())
		{
			if ((int)$stage['ID'] === $oldStageId)
			{
				$stageFrom = $stage['TITLE'];
			}
			elseif ((int)$stage['ID'] === $newStageId)
			{
				$stageTo = $stage['TITLE'];
			}
		}

		return [
			'FROM_VALUE' => $stageFrom,
			'TO_VALUE' => $stageTo,
		];
	}

	/**
	 * @use TaskLogService::castValueTypeByKeyInTrackedFields
	 *
	 * @throws ObjectNotFoundException
	 * @throws NotFoundExceptionInterface
	 */
	public static function UnifyFields(&$value, $key): void
	{
		$service = ServiceLocator::getInstance()->get('tasks.control.log.task.service');

		$service->castValueTypeByKeyInTrackedFields($value, $key);
	}


	/**
	 * @use TaskLogService::deleteByTaskId
	 *
	 * @param int $in_taskId
	 *
	 * @throws Exception on any error
	 * @throws NotFoundExceptionInterface
	 *
	 */
	public static function DeleteByTaskId(int $in_taskId): void
	{
		$command = (new DeleteByTaskIdCommand())->setTaskId($in_taskId);

		$service = ServiceLocator::getInstance()->get('tasks.control.log.task.service');

		$service->deleteByTaskId($command);
	}
}
