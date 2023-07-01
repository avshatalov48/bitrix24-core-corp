<?php

namespace Bitrix\Tasks\Provider;

use Bitrix\Forum\MessageTable;
use Bitrix\Forum\TopicTable;
use Bitrix\Im\Model\LinkTaskTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Entity;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\SystemException;
use Bitrix\Main\UserTable;
use Bitrix\Tasks\Access\Model\UserModel;
use Bitrix\Tasks\Access\Permission\PermissionDictionary;
use Bitrix\Tasks\Access\Permission\TasksPermissionTable;
use Bitrix\Tasks\Access\Role\RoleDictionary;
use Bitrix\Tasks\Integration\Intranet\Department;
use Bitrix\Tasks\Integration\Intranet\Internals\Runtime\UtmUserTable;
use Bitrix\Tasks\Integration\SocialNetwork\Group;
use Bitrix\Tasks\Internals\Counter\CounterTable;
use Bitrix\Tasks\Internals\Counter\Deadline;
use Bitrix\Tasks\Internals\Task\ElapsedTimeTable;
use Bitrix\Tasks\Internals\Task\FavoriteTable;
use Bitrix\Tasks\Internals\Task\ScenarioTable;
use Bitrix\Tasks\Internals\Task\SortingTable;
use Bitrix\Tasks\Internals\Task\TaskTagTable;
use Bitrix\Tasks\Internals\Task\UserOptionTable;
use Bitrix\Tasks\Internals\Task\ViewedTable;
use Bitrix\Tasks\Internals\TaskTable;
use Bitrix\Tasks\Internals\UserOption\Option;
use Bitrix\Tasks\Kanban\TaskStageTable;
use Bitrix\Tasks\Provider\Exception\InvalidSelectException;
use Bitrix\Tasks\Provider\Exception\UnexpectedTableException;
use Bitrix\Tasks\Internals\Task\MemberTable;
use Bitrix\Tasks\Scrum\Form\EntityForm;
use Bitrix\Tasks\Scrum\Internal\EntityTable;
use Bitrix\Tasks\Scrum\Internal\ItemTable;
use CUserTypeEntity;

class TaskQueryBuilder
{
	public const ALIAS_TASK = 'T';
	public const ALIAS_TASK_VIEW = 'TV';
	public const ALIAS_FORUM_TOPIC = 'FT';
	public const ALIAS_TASK_FAVORITE = 'FVT';
	public const ALIAS_TASK_SORT = 'SRT';
	public const ALIAS_USER_CREATE = 'CU';
	public const ALIAS_USER_RESPONSIBLE = 'RU';
	public const ALIAS_TASK_MEMBER = 'TM';
	public const ALIAS_TASK_MEMBER_AUDITOR = 'TMU';
	public const ALIAS_TASK_MEMBER_ACCOMPLICE = 'TMA';
	public const ALIAS_TASK_ACCESS = 'TMACCESS';
	public const ALIAS_TASK_COUNTERS = 'TSC';
	public const ALIAS_SEARCH_FULL = 'TSIF';
	public const ALIAS_TASK_DEPENDS = 'TD';
	public const ALIAS_TASK_STAGES = 'STG';
	public const ALIAS_FORUM_MESSAGE = 'FM';
	public const ALIAS_TASK_TAG = 'TG';
	public const ALIAS_TASK_OPTION = 'TUO';
	public const ALIAS_TASK_ELAPSED_TIME = 'TE';
	public const ALIAS_CHAT_TASK = 'CTT';

	// scrum tables can be joined several times
	public const ALIAS_SCRUM_ITEM = 'TSI';
	public const ALIAS_SCRUM_ENTITY = 'TSE';
	public const ALIAS_SCRUM_ITEM_B = 'BTSI';
	public const ALIAS_SCRUM_ENTITY_B = 'BTSE';
	public const ALIAS_SCRUM_ITEM_C = 'TSIS';
	public const ALIAS_SCRUM_ENTITY_C = 'TSES';
	public const ALIAS_SCRUM_ITEM_D = 'TSIE';
	public const ALIAS_SCRUM_ENTITY_D = 'TSEE';
	public const ALIAS_TASK_SCENARIO = 'SCR';

	private const DEFAULT_LIMIT = 50;

	/**
	 * @var UserModel $user
	 */
	private $user;
	private $departmentMembers;

	/**
	 * @var Query $query
	 */
	private $query;

	/**
	 * @var TaskQuery $taskQuery
	 */
	private $taskQuery;

	/**
	 * @var TaskFilterBuilder $filterBuilder
	 */
	private $filterBuilder;

	/**
	 * @var array
	 */
	private $runtimeFields = [];

	private $roles;
	private $permissions;

	/**
	 * @var
	 */
	private static $lastBuildedSql;

	/**
	 * @param string $alias
	 * @param $entity
	 * @return Query
	 * @throws ArgumentException
	 * @throws SystemException
	 */
	public static function createQuery(string $alias, Entity $entity): Query
	{
		$aliasPostfix = mt_rand(10000, 99999);

		$query = (new Query($entity))
			->setCustomBaseTableAlias($alias.'_'.$aliasPostfix);

		return $query;
	}

	/**
	 * @return string[]
	 */
	public static function getTranslateMap(): array
	{
		return [
			"STATUS" => "COMPUTE_STATUS",
			"STATUS_CHANGED_DATE" => "COMPUTE_STATUS_CHANGED_DATE",
			"DURATION_PLAN" => "COMPUTE_DURATION_PLAN",
			"DURATION_TYPE" => "COMPUTE_DURATION_TYPE",
			"FAVORITE" => "COMPUTE_FAVORITE",
			"GROUP_ID" => "COMPUTE_GROUP_ID",
		];
	}

	/**
	 * @param TaskQuery $taskQuery
	 * @return Query
	 * @throws InvalidSelectException
	 * @throws UnexpectedTableException
	 * @throws ArgumentException
	 * @throws SystemException
	 */
	public static function build(TaskQuery $taskQuery): Query
	{
		$query = (new self($taskQuery))
			->buildOrder()
			->buildSelect()
			->buildWhere()
			->buildJoin()
			->buildGroupBy()
			->buildLimit()
			->addAccessCheck()
			->getQuery();

		self::$lastBuildedSql = $query->getQuery();

		return $query;
	}

	/**
	 * @return string|null
	 */
	public static function getLastQuery(): ?string
	{
		return self::$lastBuildedSql;
	}

	/**
	 * @param TaskQuery $taskQuery
	 * @throws ArgumentException
	 * @throws SystemException
	 */
	private function __construct(TaskQuery $taskQuery)
	{
		$this->taskQuery = $taskQuery;
		$this->user = null;
		$this->departmentMembers = null;

		$this->query = self::createQuery(self::ALIAS_TASK, TaskTable::getEntity());
		$this->filterBuilder = new TaskFilterBuilder($taskQuery);
	}

	/**
	 * @return Query
	 */
	private function getQuery(): Query
	{
		return $this->query;
	}

	/**
	 * @return $this
	 */
	private function buildWhere(): self
	{
		$filter = $this->filterBuilder->build();
		if ($filter)
		{
			$this->query->where($filter);
		}
		return $this;
	}

	/**
	 * @return $this
	 * @throws ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws SystemException
	 */
	private function addAccessCheck(): self
	{
		if (!$this->taskQuery->needAccessCheck())
		{
			return $this;
		}

		$permissions = $this->getPermissions();

		$accessFilter = Query::filter()
			->logic('or');

		// user in tasks
		$accessFilter->where(self::ALIAS_TASK_ACCESS.'.USER_ID', $this->taskQuery->getUserId());

		// user can view all non department tasks
		if (in_array(PermissionDictionary::TASK_NON_DEPARTMENT_VIEW, $permissions))
		{
			$departmentMembers = $this->getDepartmentMembers();
			if (empty($departmentMembers))
			{
				// view all tasks
				return $this;
			}

			$accessFilter->where(
				Query::filter()
					->whereIn(self::ALIAS_TASK_ACCESS.'.TYPE', [RoleDictionary::ROLE_RESPONSIBLE, RoleDictionary::ROLE_DIRECTOR, RoleDictionary::ROLE_ACCOMPLICE])
					->whereNotIn(self::ALIAS_TASK_ACCESS.'.USER_ID', $departmentMembers)
			);
		}

		// user can view all department tasks
		if (in_array(PermissionDictionary::TASK_DEPARTMENT_VIEW, $permissions))
		{
			$departmentMembers = $this->getDepartmentMembers();
			if (!empty($departmentMembers))
			{
				$accessFilter->where(
					Query::filter()
						->whereIn(self::ALIAS_TASK_ACCESS.'.TYPE', [RoleDictionary::ROLE_RESPONSIBLE, RoleDictionary::ROLE_DIRECTOR, RoleDictionary::ROLE_ACCOMPLICE])
						->whereIn(self::ALIAS_TASK_ACCESS.'.USER_ID', $departmentMembers)
				);
			}
		}

		$subordinate = $this->getUser()->getAllSubordinates();
		// user can view subordinate tasks
		if (!empty($subordinate))
		{
			$accessFilter->whereIn(self::ALIAS_TASK_ACCESS.'.USER_ID', $subordinate);
		}

		// user can view group tasks
		$userGroups = Group::getIdsByAllowedAction('view_all', true, $this->taskQuery->getUserId());
		if (!empty($userGroups))
		{
			$accessFilter->whereIn('GROUP_ID', $userGroups);
		}

		$this->query->registerRuntimeField(self::ALIAS_TASK_ACCESS,
			(new ReferenceField(
				self::ALIAS_TASK_ACCESS,
				MemberTable::getEntity(),
				Join::on('this.ID', 'ref.TASK_ID')
			))->configureJoinType('inner')
		);
		$this->query->where($accessFilter);

		return $this;
	}

	/**
	 * @return array
	 */
	private function getDepartmentMembers(): array
	{
		if ($this->departmentMembers === null)
		{
			$departments = $this->getUser()->getUserDepartments();
			$res = \Bitrix\Intranet\Util::getDepartmentEmployees([
				'DEPARTMENTS' 	=> $departments,
				'RECURSIVE' 	=> 'N',
				'ACTIVE' 		=> 'Y',
				'SKIP' 			=> [],
				'SELECT' 		=> null
			]);

			$this->departmentMembers = [];
			while ($row = $res->GetNext())
			{
				$this->departmentMembers[] = (int) $row['ID'];
			}
		}

		return $this->departmentMembers;
	}

	/**
	 * @return array
	 * @throws ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws SystemException
	 */
	private function getPermissions(): array
	{
		if (!isset($this->permissions))
		{
			$roles = $this->getUserRoles();
			if (empty($roles))
			{
				return [];
			}

			$res = TasksPermissionTable::getList([
				'select' => ['PERMISSION_ID'],
				'filter' => [
					'@ROLE_ID' => $roles,
					'=VALUE' => PermissionDictionary::VALUE_YES
				]
			]);

			$this->permissions = [];
			foreach ($res as $row)
			{
				$this->permissions[$row['PERMISSION_ID']] = $row['PERMISSION_ID'];
			}
		}

		return $this->permissions;
	}

	/**
	 * @return array
	 */
	private function getUserRoles(): array
	{
		if (!isset($this->roles))
		{
			$this->roles = $this->getUser()->getRoles();
		}

		return $this->roles;
	}

	/**
	 * @return UserModel
	 */
	private function getUser(): UserModel
	{
		if (!$this->user)
		{
			$this->user = UserModel::createFromId($this->taskQuery->getUserId());
		}
		return $this->user;
	}


	/**
	 * @return $this
	 */
	private function buildGroupBy(): self
	{
		$groupBy = $this->taskQuery->getGroupBy();
		if (!empty($groupBy))
		{
			$this->query->setGroup($groupBy);
		}
		return $this;
	}

	/**
	 * @return $this
	 */
	private function buildLimit(): self
	{
		$limit = $this->taskQuery->getLimit() ?? self::DEFAULT_LIMIT;
		$offset = $this->taskQuery->getOffset();

		$this->query
			->setLimit($limit)
			->setOffset($offset);

		return $this;
	}

	/**
	 * @return $this
	 * @throws ArgumentException
	 * @throws SystemException
	 */
	private function buildOrder(): self
	{
		foreach ($this->taskQuery->getOrder() as $column => $order)
		{
			$this->addOrder($column, $order);
		}

		return $this;
	}

	/**
	 * @param string $column
	 * @param string $order
	 * @return void
	 * @throws ArgumentException
	 * @throws SystemException
	 */
	private function addOrder(string $column, string $order): void
	{
		$column = strtoupper($column);

		switch ($column)
		{
			case 'ID':
				$this->query->addOrder('ID', $order);
				break;

			case 'TITLE':
				$this->query->addOrder('TITLE', $order);
				$this->taskQuery->addSelect('TITLE');
				break;

			case 'DATE_START':
				$this->query->addOrder('DATE_START', $order);
				$this->taskQuery->addSelect('DATE_START');
				break;

			case 'CREATED_DATE':
				$this->query->addOrder('CREATED_DATE', $order);
				$this->taskQuery->addSelect('CREATED_DATE');
				break;

			case 'CHANGED_DATE':
				$this->query->addOrder('CHANGED_DATE', $order);
				$this->taskQuery->addSelect('CHANGED_DATE');
				break;

			case 'CLOSED_DATE':
				$this->query->addOrder('CLOSED_DATE', $order);
				$this->taskQuery->addSelect('CLOSED_DATE');
				break;

			case 'ACTIVITY_DATE':
				$this->query->addOrder('ACTIVITY_DATE', $order);
				$this->taskQuery->addSelect('ACTIVITY_DATE');
				break;

			case 'START_DATE_PLAN':
				$this->query->addOrder('START_DATE_PLAN', $order);
				$this->taskQuery->addSelect('START_DATE_PLAN');
				break;

			case 'END_DATE_PLAN':
				$this->query->addOrder('END_DATE_PLAN', $order);
				$this->taskQuery->addSelect('END_DATE_PLAN');
				break;

			case 'DEADLINE':
				if ($order === TaskQuery::SORT_ASC)
				{
					$this->query->addOrder('LENGTH_DEADLINE', TaskQuery::SORT_DESC);
					$this->query->addOrder('DEADLINE', TaskQuery::SORT_ASC);
					$this->taskQuery->addSelect('LENGTH_DEADLINE');
				}
				else
				{
					$this->query->addOrder('DEADLINE', TaskQuery::SORT_DESC);
				}

				$this->taskQuery->addSelect('DEADLINE');
				break;

			case 'STATUS':
				break;

			case 'REAL_STATUS':
				$this->query->addOrder('STATUS', $order);
				$this->taskQuery->addSelect('STATUS');
				break;

			case 'STATUS_COMPLETE':
				$this->query->addOrder('STATUS_COMPLETE', $order);
				$this->taskQuery->addSelect('STATUS_COMPLETE');
				break;

			case 'PRIORITY':
				$this->query->addOrder('PRIORITY', $order);
				$this->taskQuery->addSelect('PRIORITY');
				break;

			case 'MARK':
				$this->query->addOrder('MARK', $order);
				$this->taskQuery->addSelect('MARK');
				break;

			case 'ORIGINATOR_NAME':
			case 'CREATED_BY':
			case 'CREATED_BY_LAST_NAME':
				$this->query->addOrder(self::ALIAS_USER_CREATE.'.LAST_NAME', $order);
				$this->taskQuery->addSelect('CREATED_BY_LAST_NAME');
				break;

			case 'RESPONSIBLE_NAME':
			case 'RESPONSIBLE_ID':
			case 'RESPONSIBLE_LAST_NAME':
				$this->query->addOrder(self::ALIAS_USER_RESPONSIBLE.'.LAST_NAME', $order);
				$this->taskQuery->addSelect('RESPONSIBLE_LAST_NAME');
				break;

			case 'GROUP_ID':
			case 'COMPUTE_GROUP_ID':
				$this->query->addOrder('COMPUTE_GROUP_ID', $order);
				$this->taskQuery->addSelect('COMPUTE_GROUP_ID');
				break;

			case 'TIME_ESTIMATE':
				$this->query->addOrder('TIME_ESTIMATE', $order);
				$this->taskQuery->addSelect('TIME_ESTIMATE');
				break;

			case 'ALLOW_CHANGE_DEADLINE':
				$this->query->addOrder('ALLOW_CHANGE_DEADLINE', $order);
				$this->taskQuery->addSelect('ALLOW_CHANGE_DEADLINE');
				break;

			case 'ALLOW_TIME_TRACKING':
				$this->query->addOrder('ALLOW_TIME_TRACKING', $order);
				$this->taskQuery->addSelect('ALLOW_TIME_TRACKING');
				break;

			case 'MATCH_WORK_TIME':
				$this->query->addOrder('MATCH_WORK_TIME', $order);
				$this->taskQuery->addSelect('MATCH_WORK_TIME');
				break;

			case 'SORTING':
				$this->query->addOrder('NULL_SORTING', $order);
				$this->query->addOrder('SORTING', $order);
				$this->taskQuery->addSelect('SORTING');
				$this->taskQuery->addSelect('NULL_SORTING');
				break;

			case 'MESSAGE_ID':
				$this->query->addOrder('MESSAGE_ID', $order);
				$this->taskQuery->addSelect('MESSAGE_ID');
				break;

			case 'FAVORITE':
			case 'COMPUTE_FAVORITE':
				$this->taskQuery->addSelect('COMPUTE_FAVORITE');
				$this->query->addOrder('COMPUTE_FAVORITE', $order);
				break;

			case 'TIME_SPENT_IN_LOGS':
				$this->taskQuery->addSelect('TIME_SPENT_IN_LOGS');
				$this->query->addOrder('TIME_SPENT_IN_LOGS', $order);
				break;

			case 'IS_PINNED':
				$this->taskQuery->addSelect('IS_PINNED');
				$this->query->addOrder('IS_PINNED', $order);
				break;

			case 'IS_PINNED_IN_GROUP':
				$this->taskQuery->addSelect('IS_PINNED_IN_GROUP');
				$this->query->addOrder('IS_PINNED_IN_GROUP', $order);
				break;

			case 'SCRUM_ITEMS_SORT':
				$this->query->addOrder(self::ALIAS_SCRUM_ITEM_B.".SORT", $order);
				$this->registerRuntimeField(self::ALIAS_SCRUM_ITEM_B);
				break;

			case 'IM_CHAT_ID':
				if (!Loader::includeModule('im'))
				{
					break;
				}
				$this->query->addOrder(self::ALIAS_CHAT_TASK.".ID", $order);
				$this->registerRuntimeField(self::ALIAS_CHAT_TASK);
				break;

			default:
				if (preg_match('/^UF_/', $column))
				{
					$this->query->addOrder($column, $order);
					break;
				}

		}
	}

	/**
	 * @return $this
	 * @throws ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws SystemException
	 */
	private function buildJoin(): self
	{
		$fromFilter = $this->filterBuilder->getRuntimeFields();

		foreach ($fromFilter as $alias => $field)
		{
			$this->registerRuntimeField($alias, $field);
		}

		foreach ($this->runtimeFields as $alias => $field)
		{
			if (!empty($field))
			{
				$this->query->registerRuntimeField($alias, $field);
			}
			else
			{
				$this->joinByAlias($alias);
			}
		}

		if (!empty($this->runtimeFields))
		{
			$this->query->setDistinct();
		}

		return $this;
	}

	/**
	 * @param string $alias
	 * @return void
	 * @throws ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws SystemException
	 */
	private function joinByAlias(string $alias): void
	{
		if ($alias === self::ALIAS_TASK)
		{
			return;
		}

		if ($this->query->getEntity()->hasField($alias))
		{
			return;
		}

		switch ($alias)
		{
			case self::ALIAS_TASK_VIEW:
				$this->query->registerRuntimeField(
					$alias,
					(new ReferenceField(
						$alias,
						ViewedTable::getEntity(),
						Join::on('this.ID', 'ref.TASK_ID')
							->where('ref.USER_ID', $this->taskQuery->getBehalfUser())
					))->configureJoinType('left')
				);
				break;

			case self::ALIAS_FORUM_TOPIC:
				if (!\Bitrix\Main\Loader::includeModule('forum'))
				{
					break;
				}
				$this->query->registerRuntimeField(
					$alias,
					(new ReferenceField(
						$alias,
						TopicTable::getEntity(),
						Join::on('this.FORUM_TOPIC_ID', 'ref.ID')
					))->configureJoinType('left')
				);
				break;

			case self::ALIAS_FORUM_MESSAGE:
				if (!\Bitrix\Main\Loader::includeModule('forum'))
				{
					break;
				}
				$this->query->registerRuntimeField(
					$alias,
					(new ReferenceField(
						$alias,
						MessageTable::getEntity(),
						Join::on('this.FORUM_TOPIC_ID', 'ref.TOPIC_ID')
					))->configureJoinType('left')
				);
				break;

			case self::ALIAS_TASK_FAVORITE:
				$this->query->registerRuntimeField(
					$alias,
					(new ReferenceField(
						$alias,
						FavoriteTable::getEntity(),
						Join::on('this.ID', 'ref.TASK_ID')
							->where('ref.USER_ID', $this->taskQuery->getBehalfUser())
					))->configureJoinType('left')
				);
				break;

			case self::ALIAS_TASK_SORT:
				$this->query->registerRuntimeField(
					$alias,
					(new ReferenceField(
						$alias,
						SortingTable::getEntity(),
						Join::on('this.ID', 'ref.TASK_ID')
							->where('ref.USER_ID', $this->taskQuery->getBehalfUser())
					))->configureJoinType('left')
				);
				break;

			case self::ALIAS_USER_CREATE:
				$this->query->registerRuntimeField(
					$alias,
					(new ReferenceField(
						$alias,
						UserTable::getEntity(),
						Join::on('this.CREATED_BY', 'ref.ID')
					))->configureJoinType('inner')
				);
				break;

			case self::ALIAS_USER_RESPONSIBLE:
				$this->query->registerRuntimeField(
					$alias,
					(new ReferenceField(
						$alias,
						UserTable::getEntity(),
						Join::on('this.RESPONSIBLE_ID', 'ref.ID')
					))->configureJoinType('inner')
				);
				break;

			case self::ALIAS_TASK_MEMBER_AUDITOR:
			case self::ALIAS_TASK_MEMBER_ACCOMPLICE:
				$memberType = ($alias === self::ALIAS_TASK_MEMBER_ACCOMPLICE)
					? MemberTable::MEMBER_TYPE_ACCOMPLICE
					: MemberTable::MEMBER_TYPE_AUDITOR;

				$this->query->registerRuntimeField(
					$alias,
					(new ReferenceField(
						$alias,
						MemberTable::getEntity(),
						Join::on('this.ID', 'ref.TASK_ID')
							->where('ref.TYPE', $memberType)
					))->configureJoinType('inner')
				);
				break;

			case self::ALIAS_TASK_MEMBER:
				$this->query->registerRuntimeField(
					$alias,
					(new ReferenceField(
						$alias,
						MemberTable::getEntity(),
						Join::on('this.ID', 'ref.TASK_ID')
							->where('ref.USER_ID', $this->taskQuery->getBehalfUser())
					))->configureJoinType('inner')
				);
				break;

			case self::ALIAS_TASK_COUNTERS:
				$this->query->registerRuntimeField(
					$alias,
					(new ReferenceField(
						$alias,
						CounterTable::getEntity(),
						Join::on('this.ID', 'ref.TASK_ID')
							->where('ref.USER_ID', $this->taskQuery->getBehalfUser())
					))->configureJoinType('left')
				);
				break;

			case self::ALIAS_SCRUM_ITEM:
				$this->joinByAlias(TaskQueryBuilder::ALIAS_SCRUM_ENTITY);

				$this->query->registerRuntimeField(
					$alias,
					(new ReferenceField(
						$alias,
						ItemTable::getEntity(),
						Join::on('this.ID', 'ref.SOURCE_ID')
							->whereColumn('ref.ENTITY_ID', 'this.' . self::ALIAS_SCRUM_ENTITY . '.ID')
					))->configureJoinType('left')
				);
				break;

			case self::ALIAS_SCRUM_ITEM_B:
				$this->joinByAlias(TaskQueryBuilder::ALIAS_SCRUM_ENTITY_B);
				$this->query->registerRuntimeField(
					$alias,
					(new ReferenceField(
						$alias,
						ItemTable::getEntity(),
						Join::on('this.ID', 'ref.SOURCE_ID')
							->where('ref.ACTIVE', 'Y')
					))->configureJoinType('inner')
				);
				$this->query->whereExpr('%s = %s', [$alias.'.ENTITY_ID', self::ALIAS_SCRUM_ENTITY_B.'.ID']);
				break;

			case self::ALIAS_SCRUM_ENTITY:
				$this->query->registerRuntimeField(
					$alias,
					(new ReferenceField(
						$alias,
						EntityTable::getEntity(),
						Join::on('this.GROUP_ID', 'ref.GROUP_ID')
							->where('ref.STATUS', EntityForm::SPRINT_ACTIVE)
					))->configureJoinType('left')
				);
				break;

			case self::ALIAS_SCRUM_ENTITY_B:
				$join = Join::on('this.GROUP_ID', 'ref.GROUP_ID');

				$filter = $this->taskQuery->getWhere();
				if (array_key_exists('SCRUM_ENTITY_IDS', $filter))
				{
					$join->whereIn('ref.ID', $filter['SCRUM_ENTITY_IDS']);
				}

				$this->query->registerRuntimeField(
					$alias,
					(new ReferenceField(
						$alias,
						EntityTable::getEntity(),
						$join
					))->configureJoinType('inner')
				);
				break;

			case self::ALIAS_SCRUM_ENTITY_C:
			case self::ALIAS_SCRUM_ENTITY_D:
				$this->query->registerRuntimeField(
					$alias,
					(new ReferenceField(
						$alias,
						EntityTable::getEntity(),
						Join::on('this.GROUP_ID', 'ref.GROUP_ID')
					))->configureJoinType('inner')
				);
				break;

			case self::ALIAS_SCRUM_ITEM_C:
				$this->joinByAlias(TaskQueryBuilder::ALIAS_SCRUM_ENTITY_C);
				$this->query->registerRuntimeField(
					$alias,
					(new ReferenceField(
						$alias,
						ItemTable::getEntity(),
						Join::on('this.ID', 'ref.SOURCE_ID')
					))->configureJoinType('inner')
				);
				$this->query->whereExpr('%s = %s', [$alias.'.ENTITY_ID', self::ALIAS_SCRUM_ENTITY_C.'.ID']);
				break;

			case self::ALIAS_SCRUM_ITEM_D:
				$this->joinByAlias(TaskQueryBuilder::ALIAS_SCRUM_ENTITY_D);

				$epicId = null;
				$filter = $this->taskQuery->getWhere();
				if (array_key_exists('EPIC', $filter))
				{
					$epicId = (int) $filter['EPIC'];
				}
				elseif (isset($filter['::SUBFILTER-EPIC']['EPIC']))
				{
					$epicId = (int) $filter['::SUBFILTER-EPIC']['EPIC'];
				}

				if (!$epicId)
				{
					break;
				}

				$this->query->registerRuntimeField(
					$alias,
					(new ReferenceField(
						$alias,
						ItemTable::getEntity(),
						Join::on('this.ID', 'ref.SOURCE_ID')
							->where('ref.EPIC_ID', $epicId)
					))->configureJoinType('inner')
				);
				$this->query->whereExpr('%s = %s', [$alias.'.ENTITY_ID', self::ALIAS_SCRUM_ENTITY_D.'.ID']);
				break;

			case self::ALIAS_TASK_STAGES:
				$this->query->registerRuntimeField(
					$alias,
					(new ReferenceField(
						$alias,
						TaskStageTable::getEntity(),
						Join::on('this.ID', 'ref.TASK_ID')
					))->configureJoinType('inner')
				);
				break;

			case self::ALIAS_TASK_TAG:
				$this->query->registerRuntimeField(
					$alias,
					(new ReferenceField(
						$alias,
						TaskTagTable::getEntity(),
						Join::on('this.ID', 'ref.TASK_ID')
					))->configureJoinType('inner')
				);
				break;

			case self::ALIAS_TASK_SCENARIO:
				$this->query->registerRuntimeField(
					$alias,
					(new ReferenceField(
						$alias,
						ScenarioTable::getEntity(),
						Join::on('this.ID', 'ref.TASK_ID')
					))->configureJoinType('left')
				);
				break;

			case self::ALIAS_CHAT_TASK:
				if (!Loader::includeModule('im'))
				{
					break;
				}
				$this->query->registerRuntimeField(
					$alias,
					(new ReferenceField(
						$alias,
						LinkTaskTable::getEntity(),
						Join::on('this.ID', 'ref.TASK_ID')
					))->configureJoinType('inner')
				);
				break;

			case self::ALIAS_SEARCH_FULL:
				break;
		}
	}

	/**
	 * @return $this
	 * @throws InvalidSelectException
	 */
	private function buildSelect(): self
	{
		$select = $this->taskQuery->getSelect();
		if (empty($select))
		{
			$select = $this->getDefaultSelect();
		}

		$entityFields = (TaskTable::getEntity())->getFields();

		foreach ($select as $key)
		{
			if (
				preg_match('/^UF_/', $key)
				&& array_key_exists($key, $entityFields)
			)
			{
				$this->query->addSelect($key, $key);
				continue;
			}

			$this->addSelect($key);
		}

		$this->query->setDistinct($this->taskQuery->getDistinct());

		return $this;
	}

	/**
	 * @param string $key
	 * @return void
	 */
	private function addSelect(string $key)
	{
		$translateMap = self::getTranslateMap();
		if (isset($translateMap[$key]))
		{
			$this->addSelect($translateMap[$key]);
			return;
		}

		$modules = $this->getModuleMap();
		$fields = $this->getFieldMap();
		if (
			!array_key_exists($key, $fields)
			|| empty($fields[$key])
		)
		{
			return;
		}

		if (
			array_key_exists($key, $modules)
			&& !Loader::includeModule($modules[$key])
		)
		{
			return;
		}

		$field = $fields[$key];

		if (is_callable($field))
		{
			$field = $field();
		}

		if (is_a($field, ExpressionField::class))
		{
			$this->query->addSelect($field, $key);
			return;
		}

		$this->addJoinByField($field);
		$this->query->addSelect($field, $key);
	}

	/**
	 * @param string $field
	 * @return void
	 */
	private function addJoinByField(string $field): void
	{
		$tableAlias = explode('.', $field);
		if (count($tableAlias) < 2)
		{
			return;
		}

		$tableAlias = array_shift($tableAlias);
		$this->registerRuntimeField($tableAlias);
	}

	/**
	 * @return string[]
	 */
	private function getDefaultSelect(): array
	{
		return [
			"ID",
		];
	}

	/**
	 * @return $this
	 */
	private function getFieldMap(): array
	{
		return [
			"ID" => "ID",
			"TITLE" => "TITLE",
			"DESCRIPTION" => "DESCRIPTION",
			"DESCRIPTION_IN_BBCODE" => "DESCRIPTION_IN_BBCODE",
			"DECLINE_REASON" => "DECLINE_REASON",
			"PRIORITY" => "PRIORITY",
			"STATUS_COMPLETE" => new ExpressionField(
				"STATUS_COMPLETE",
				"CASE
					WHEN
						%s = '".\CTasks::STATE_COMPLETED."'
					THEN
						'".\CTasks::STATE_PENDING."'
					ELSE
						'".\CTasks::STATE_NEW."'
					END",
				["STATUS"]
			),
			"COMPUTE_STATUS" => function() {
				return $this->getStatusField();
			},
			"REAL_STATUS" => "STATUS",
			"MULTITASK" => "MULTITASK",
			"STAGE_ID" => "STAGE_ID",
			"RESPONSIBLE_ID" => "RESPONSIBLE_ID",
			"RESPONSIBLE_NAME" => self::ALIAS_USER_RESPONSIBLE.".NAME",
			"RESPONSIBLE_LAST_NAME" => self::ALIAS_USER_RESPONSIBLE.".LAST_NAME",
			"RESPONSIBLE_SECOND_NAME" => self::ALIAS_USER_RESPONSIBLE.".SECOND_NAME",
			"RESPONSIBLE_LOGIN" => self::ALIAS_USER_RESPONSIBLE.".LOGIN",
			"RESPONSIBLE_WORK_POSITION" => self::ALIAS_USER_RESPONSIBLE.".WORK_POSITION",
			"RESPONSIBLE_PHOTO" => self::ALIAS_USER_RESPONSIBLE.".PERSONAL_PHOTO",
			"DATE_START" => "DATE_START",
			"TIME_ESTIMATE" => "TIME_ESTIMATE",
			"REPLICATE" => "REPLICATE",
			"DEADLINE" => "DEADLINE",
			"DEADLINE_ORIG" => "DEADLINE",
			"START_DATE_PLAN" => "START_DATE_PLAN",
			"END_DATE_PLAN" => "END_DATE_PLAN",
			"CREATED_BY" => "CREATED_BY",
			"CREATED_BY_NAME" => self::ALIAS_USER_CREATE.".NAME",
			"CREATED_BY_LAST_NAME" => self::ALIAS_USER_CREATE.".LAST_NAME",
			"CREATED_BY_SECOND_NAME" => self::ALIAS_USER_CREATE.".SECOND_NAME",
			"CREATED_BY_LOGIN" => self::ALIAS_USER_CREATE.".LOGIN",
			"CREATED_BY_WORK_POSITION" => self::ALIAS_USER_CREATE.".WORK_POSITION",
			"CREATED_BY_PHOTO" => self::ALIAS_USER_CREATE.".PERSONAL_PHOTO",
			"CREATED_DATE" => "CREATED_DATE",
			"CHANGED_BY" => "CHANGED_BY",
			"CHANGED_DATE" => "CHANGED_DATE",
			"STATUS_CHANGED_BY" => "STATUS_CHANGED_BY",
			"CLOSED_BY" => "CLOSED_BY",
			"CLOSED_DATE" => "CLOSED_DATE",
			"ACTIVITY_DATE" => "ACTIVITY_DATE",
			"GUID" => "GUID",
			"XML_ID" => "XML_ID",
			"MARK" => "MARK",
			"ALLOW_CHANGE_DEADLINE" => "ALLOW_CHANGE_DEADLINE",
			"ALLOW_TIME_TRACKING" => "ALLOW_TIME_TRACKING",
			"MATCH_WORK_TIME" => "MATCH_WORK_TIME",
			"TASK_CONTROL" => "TASK_CONTROL",
			"ADD_IN_REPORT" => "ADD_IN_REPORT",
			"COMPUTE_GROUP_ID" => new ExpressionField(
				'COMPUTE_GROUP_ID',
				'CASE WHEN %1$s IS NULL THEN 0 ELSE %1$s END',
				['GROUP_ID']
			),
			"FORUM_TOPIC_ID" => "FORUM_TOPIC_ID",
			"PARENT_ID" => "PARENT_ID",
			"COMMENTS_COUNT" => self::ALIAS_FORUM_TOPIC.".POSTS",
			"SERVICE_COMMENTS_COUNT" => self::ALIAS_FORUM_TOPIC.".POSTS_SERVICE",
			"FORUM_ID" => self::ALIAS_FORUM_TOPIC.".FORUM_ID",
			"MESSAGE_ID" => function () {
				return $this->getMessageIdField();
			},
			"SITE_ID" => "SITE_ID",
			"EXCHANGE_MODIFIED" => "EXCHANGE_MODIFIED",
			"EXCHANGE_ID" => "EXCHANGE_ID",
			"OUTLOOK_VERSION" => "OUTLOOK_VERSION",
			"VIEWED_DATE" => self::ALIAS_TASK_VIEW.".VIEWED_DATE",
			"DEADLINE_COUNTED" => "DEADLINE_COUNTED",
			"FORKED_BY_TEMPLATE_ID" => "FORKED_BY_TEMPLATE_ID",
			"NOT_VIEWED" => function () {
				return $this->getNotViewedField();
			},
			"COMPUTE_FAVORITE" => function () {
				return $this->getFavoriteField();
			},
			"SORTING" => self::ALIAS_TASK_SORT.".SORT",
			"IM_CHAT_ID" => self::ALIAS_CHAT_TASK.".ID",
			"IM_CHAT_MESSAGE_ID" => self::ALIAS_CHAT_TASK.".MESSAGE_ID",
			"IM_CHAT_CHAT_ID" => self::ALIAS_CHAT_TASK.".CHAT_ID",
			"IM_CHAT_AUTHOR_ID" => self::ALIAS_CHAT_TASK.".AUTHOR_ID",

			"DURATION_PLAN_SECONDS" => "DURATION_PLAN",
			"DURATION_TYPE_ALL" => "DURATION_TYPE",

			"COMPUTE_DURATION_PLAN" => new ExpressionField(
				'COMPUTE_DURATION_PLAN',
				'case
					when
						%1$s = \''. \CTasks::TIME_UNIT_TYPE_MINUTE .'\' or %1$s = \''. \CTasks::TIME_UNIT_TYPE_HOUR .'\'
					then
						ROUND(%2$s / 3600, 0)
					when
						%1$s = \''. \CTasks::TIME_UNIT_TYPE_DAY .'\' or %1$s = "" or %1$s is null
					then
						ROUND(%2$s / 86400, 0)
					else
						%2$s
				end',
				["DURATION_TYPE", "DURATION_PLAN"]
			),
			"COMPUTE_DURATION_TYPE" => new ExpressionField(
				"COMPUTE_DURATION_TYPE",
				'case
					when
						%1$s = \''. \CTasks::TIME_UNIT_TYPE_MINUTE .'\'
					then
						\''. \CTasks::TIME_UNIT_TYPE_HOUR .'\'
					else
						%1$s
				end',
				["DURATION_TYPE"]
			),

			"COMPUTE_STATUS_CHANGED_DATE" => new ExpressionField(
				"COMPUTE_STATUS_CHANGED_DATE",
				'CASE
					WHEN
						%1$s IS NULL
					THEN
						%2$s
					ELSE
						%1$s
				END',
				["STATUS_CHANGED_DATE", "CHANGED_DATE"]
			),

			"TIME_SPENT_IN_LOGS" => function () {
				return $this->getTimeSpentField();
			},
			"DURATION_FACT" => function () {
				return $this->getDurationFactField();
			},
			"IS_MUTED" => function () {
				return $this->getUserOptionField("IS_MUTED", Option::MUTED);
			},
			"IS_PINNED" => function () {
				return $this->getUserOptionField("IS_PINNED", Option::PINNED);
			},
			"IS_PINNED_IN_GROUP" => function () {
				return $this->getUserOptionField("IS_PINNED_IN_GROUP", Option::PINNED_IN_GROUP);
			},
			"SUBORDINATE" => function () {
				return $this->getSubordinateField();
			},

			"COUNT" => new ExpressionField("CNT", "COUNT(DISTINCT %s)", ['ID']),
			"NULL_SORTING" => new ExpressionField(
				"NULL_SORTING",
				'CASE
					WHEN
						%s IS NULL
					THEN
						1
					ELSE
						0
				END',
				[self::ALIAS_TASK_SORT.".SORT"]
			),
			"LENGTH_DEADLINE" => new ExpressionField(
				"LENGTH_DEADLINE",
				'CASE
					WHEN
						length(%s) > 0
					THEN
						1
					ELSE
						0
				END',
				["DEADLINE"]
			),
			"SCENARIO_NAME" => self::ALIAS_TASK_SCENARIO.".SCENARIO",
		];
	}

	/**
	 * @return ExpressionField
	 * @throws ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws SystemException
	 */
	private function getFavoriteField(): ExpressionField
	{
		$this->joinByAlias(self::ALIAS_TASK_FAVORITE);

		return new ExpressionField(
			"COMPUTE_FAVORITE",
			"CASE WHEN %s IS NULL THEN 'N' ELSE 'Y' END",
			[self::ALIAS_TASK_FAVORITE.".TASK_ID"]
		);
	}

	/**
	 * @return ExpressionField
	 * @throws ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws SystemException
	 */
	private function getNotViewedField(): ExpressionField
	{
		$this->joinByAlias(self::ALIAS_TASK_VIEW);

		return new ExpressionField(
			"NOT_VIEWED",
			'CASE
				WHEN
					%3$s IS NULL
					AND
					%2$s != '. $this->taskQuery->getBehalfUser() .'
					AND
					(%1$s = '. \CTasks::STATE_NEW .' OR %1$s = '. \CTasks::STATE_PENDING .')
				THEN
					"Y"
				ELSE
					"N"
			END',
			["STATUS", "CREATED_BY", self::ALIAS_TASK_VIEW.".USER_ID"]
		);
	}

	/**
	 * @return ExpressionField
	 * @throws ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws SystemException
	 */
	private function getMessageIdField(): ExpressionField
	{
		$this->joinByAlias(self::ALIAS_SEARCH_FULL);

		return new ExpressionField(
			"MESSAGE_ID",
			"MIN(%s)",
			[self::ALIAS_SEARCH_FULL.".MESSAGE_ID"]
		);
	}

	/**
	 * @return ExpressionField
	 * @throws ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws SystemException
	 */
	private function getStatusField(): ExpressionField
	{
		$this->joinByAlias(self::ALIAS_TASK_VIEW);

		return new ExpressionField(
			"COMPUTE_STATUS",
			'CASE
					WHEN
						%1$s < DATE_ADD(NOW(), INTERVAL '. Deadline::getDeadlineTimeLimit() .' SECOND)
						AND %1$s >= NOW()
						AND %2$s != '. \CTasks::STATE_SUPPOSEDLY_COMPLETED .'
						AND %2$s != '. \CTasks::STATE_COMPLETED .'
						AND (
							%2$s != '. \CTasks::STATE_DECLINED .'
							OR %3$s != '. $this->taskQuery->getBehalfUser() .'
						)
					THEN
						"'. \CTasks::METASTATE_EXPIRED_SOON .'"
					WHEN
						%1$s < NOW() 
						AND %2$s != '. \CTasks::STATE_SUPPOSEDLY_COMPLETED .'
						AND %2$s != '. \CTasks::STATE_COMPLETED .'
						AND (%2$s != '. \CTasks::STATE_DECLINED .' OR %3$s != '. $this->taskQuery->getBehalfUser() .')
					THEN
						'. \CTasks::METASTATE_EXPIRED .'
					WHEN
						%5$s IS NULL
						AND %4$s != '. $this->taskQuery->getBehalfUser() .'
						AND (%2$s = '. \CTasks::STATE_NEW .' OR %2$s = '. \CTasks::STATE_PENDING .')
					THEN
						'. \CTasks::METASTATE_VIRGIN_NEW .'
					ELSE
						%2$s
				END',
			["DEADLINE", "STATUS", "RESPONSIBLE_ID", "CREATED_BY", self::ALIAS_TASK_VIEW.".USER_ID"]
		);
	}

	/**
	 * @return ExpressionField
	 * @throws ArgumentException
	 * @throws SystemException
	 */
	private function getTimeSpentField(): ExpressionField
	{
		$query = self::createQuery(self::ALIAS_TASK_ELAPSED_TIME, ElapsedTimeTable::getEntity());
		$query->addSelect(
			new ExpressionField(
				"SUM",
				"sum(%s)",
				["SECONDS"]
			)
		);
		$query->where('TASK_ID', new SqlExpression('%s'));
		$query->addGroup('TASK_ID');

		return new ExpressionField(
			"TIME_SPENT_IN_LOGS1",
			"(".$query->getQuery().")",
			["ID"]
		);
	}

	/**
	 * @return ExpressionField
	 * @throws ArgumentException
	 * @throws SystemException
	 */
	private function getDurationFactField(): ExpressionField
	{
		$query = self::createQuery(self::ALIAS_TASK_ELAPSED_TIME, ElapsedTimeTable::getEntity());
		$query->addSelect(
			new ExpressionField(
				"SUM",
				"sum(%s)",
				["MINUTES"]
			)
		);
		$query->where('TASK_ID', new SqlExpression('%s'));
		$query->addGroup('TASK_ID');

		return new ExpressionField(
			"DURATION_FACT",
			"(".$query->getQuery().")",
			["ID"]
		);
	}

	/**
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	private function getSubordinateSql(): string
	{
		$departmentIds = Department::getSubordinateIds($this->taskQuery->getBehalfUser(), true);
		$departmentIds = array_map('intval', $departmentIds);
		if (count($departmentIds) <= 0)
		{
			return '';
		}
		$departmentOption = CUserTypeEntity::GetList([], [
			'ENTITY_ID' => 'USER',
			'FIELD_NAME' => 'UF_DEPARTMENT',
		])->Fetch();

		if (!$departmentOption)
		{
			return '';
		}

		$fieldId = (int)$departmentOption['ID'];

		$responsibleIdQuery = self::createQuery('BUF', UtmUserTable::getEntity());
		$responsibleIdQuery
			->setSelect(['FIELD_ID'])
			->where('FIELD_ID', $fieldId)
			->whereIn('VALUE_INT', $departmentIds)
			->where('VALUE_ID', new SqlExpression('%s'))
		;

		$createdByQuery = clone $responsibleIdQuery;

		//todo.
		$existsQuery = self::createQuery('BUF', UtmUserTable::getEntity());
		$existsQuery
			->setSelect(['FIELD_ID'])
			->where('FIELD_ID', $fieldId)
			->where('DSTM.TASK_ID', new SqlExpression('%s'))
			->whereIn('VALUE_INT', $departmentIds)
			->registerRuntimeField('DSTM',
				(new ReferenceField(
					'rel',
					MemberTable::getEntity(),
					Join::on('this.VALUE_ID', 'ref.USER_ID')
				))->configureJoinType(Join::TYPE_INNER)
			)
		;

		$sql = "
			CASE
				WHEN EXISTS({$responsibleIdQuery->getQuery()}) THEN 'Y'
				WHEN EXISTS({$createdByQuery->getQuery()}) THEN 'Y'
				WHEN EXISTS({$existsQuery->getQuery()}) THEN 'Y'
				ELSE 'N'
			END
		";

		return $sql;
	}
	/**
	 * @return ExpressionField
	 * @throws SystemException
	 */
	private function getSubordinateField(): ExpressionField
	{
		$subordinateQuery = $this->getSubordinateSql();
		if (!empty($subordinateQuery))
		{
			$field = (new ExpressionField(
				'SUBORDINATE',
				$subordinateQuery,
				['RESPONSIBLE_ID', 'CREATED_BY', 'ID']
			));
		}
		else
		{
			$field = new ExpressionField('SUBORDINATE', "'N'");
		}

		return $field;
	}

	/**
	 * @param string $field
	 * @param int $option
	 * @return ExpressionField
	 * @throws SystemException
	 */
	private function getUserOptionField(string $field, int $option): ExpressionField
	{
		$sql = "
			SELECT 'x' 
			FROM ". UserOptionTable::getTableName() ."
			WHERE TASK_ID = %s
			  AND USER_ID = {$this->taskQuery->getBehalfUser()} 
			  AND OPTION_CODE = {$option}
		";

		$sql = "IF(EXISTS({$sql}), 'Y', 'N')";

		return new ExpressionField(
			$field,
			$sql,
			['ID']
		);
	}

	/**
	 * @return string[]
	 */
	private function getModuleMap(): array
	{
		return [
			'COMMENTS_COUNT' => 'forum',
			'SERVICE_COMMENTS_COUNT' => 'forum',
			'FORUM_ID' => 'forum',
			'IM_CHAT_ID' => 'im',
			'IM_CHAT_MESSAGE_ID' => 'im',
			'IM_CHAT_CHAT_ID' => 'im',
			'IM_CHAT_AUTHOR_ID' => 'im',
		];
	}

	/**
	 * @param string $alias
	 * @param ReferenceField|null $field
	 * @return void
	 */
	private function registerRuntimeField(string $alias, $field = null): void
	{
		if (
			array_key_exists($alias, $this->runtimeFields)
			&& empty($field)
		)
		{
			return;
		}
		$this->runtimeFields[$alias] = $field;
	}
}