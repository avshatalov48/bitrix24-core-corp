<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2023 Bitrix
 */

namespace Bitrix\Tasks\Provider;

use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\DateField;
use Bitrix\Main\ObjectException;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\Entity\Query\Filter\ConditionTree;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Query\Filter\Condition;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Comments\Internals\Comment;
use Bitrix\Tasks\Internals\Counter\CounterDictionary;
use Bitrix\Tasks\Internals\Counter\CounterTable;
use Bitrix\Tasks\Internals\Counter\Deadline;
use Bitrix\Tasks\Internals\Task\LabelTable;
use Bitrix\Tasks\Internals\Task\MemberTable;
use Bitrix\Tasks\Internals\Task\RelatedTable;
use Bitrix\Tasks\Internals\Task\ScenarioTable;
use Bitrix\Tasks\Internals\Task\SearchIndexTable;
use Bitrix\Tasks\Internals\Task\TaskTagTable;
use Bitrix\Tasks\Internals\Task\UserOptionTable;
use Bitrix\Tasks\Internals\TaskTable;
use Bitrix\Tasks\Internals\UserOption\Option;
use Bitrix\Tasks\Provider\Exception\InvalidFilterException;
use Bitrix\Tasks\Provider\Exception\LoadModuleException;
use Bitrix\Tasks\Scrum\Form\EntityForm;
use Bitrix\Tasks\Util\Entity\DateTimeField;

class TaskFilterBuilder
{
	private const FILTER_ROLE_KEY = '::SUBFILTER-ROLEID';

	private const OPERATION_EQUAL = '=';
	private const OPERATION_NOT_EQUAL = '!=';
	private const OPERATION_LIKE = '%';
	private const OPERATION_NOT_LIKE = '!%';
	private const OPERATION_ASK = '?';
	private const OPERATION_GREAT_LESS = '><';
	private const OPERATION_MATCH_EQ = '*=';
	private const OPERATION_MATCH_LIKE = '*%';
	private const OPERATION_MATCH = '*';
	private const OPERATION_NOT_NULL = '!><';
	private const OPERATION_GREAT_EQ = '>=';
	private const OPERATION_GREAT = '>';
	private const OPERATION_LESS_EQ = '<=';
	private const OPERATION_LESS = '<';
	private const OPERATION_NOT = '!';
	private const OPERATION_LEGACY = '#';
	private const OPERATION_DEFAULT = 'DEF';

	private const CAST_NUMBER = 'number';
	private const CAST_NUMBER_WO_NULLS = 'number_wo_nulls';
	private const CAST_REFERENCE = 'reference';
	private const CAST_NULL_OR_ZERO = 'null_or_zero';
	private const CAST_STRING = 'string';
	private const CAST_FULLTEXT = 'fulltext';
	private const CAST_STRING_EQ = 'string_equal';
	private const CAST_DATE = 'date';
	private const CAST_LEFT_EXIST = 'left_existence';

	/**
	 * @var TaskQuery $query
	 */
	private $query;

	/**
	 * @var int $userId
	 */
	private $userId;

	/**
	 * @var int $behalfUserId
	 */
	private $behalfUserId;

	/**
	 * @var array
	 */
	private $runtimeFields = [];

	private TasksUFManager $ufManager;

	public function __construct(TaskQuery $query)
	{
		$this->query = $query;
		$this->userId = $query->getUserId();
		$this->behalfUserId = $query->getBehalfUser();
		$this->ufManager = TasksUFManager::getInstance();
	}

	/**
	 * @return ConditionTree|null
	 * @throws InvalidFilterException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function build(): ?ConditionTree
	{
		$where = $this->query->getWhere();
		$filter = $this->prepareFilter($where);
		$conditionTree = $this
			->optimizeFilter($filter)
			->translateFilter($filter);

		return $conditionTree;
	}

	/**
	 * @return array
	 */
	public function getRuntimeFields(): array
	{
		return $this->runtimeFields;
	}

	/**
	 * @param array $filter
	 * @return ConditionTree|null
	 * @throws InvalidFilterException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function translateFilter(array $filter): ?ConditionTree
	{
		if (
			array_key_exists('ONLY_ROOT_TASKS', $filter)
			&& $filter['ONLY_ROOT_TASKS'] === 'Y'
			&&
			(
				array_key_exists('FULL_SEARCH_INDEX', $filter)
				|| array_key_exists('COMMENT_SEARCH_INDEX', $filter)
			)
		)
		{
			unset($filter['ONLY_ROOT_TASKS']);
		}

		if (
			array_key_exists('SAME_GROUP_PARENT_EX', $filter)
			&& $filter['SAME_GROUP_PARENT_EX'] === 'Y'
		)
		{
			unset($filter['SAME_GROUP_PARENT']);
		}

		$conditionTree = Query::filter();

		$filterCount = count($filter);
		$logicOr = false;

		if (isset($filter['::LOGIC']))
		{
			$filterCount = $filterCount - 1;

			switch ($filter['::LOGIC'])
			{
				case 'AND':
					$conditionTree->logic('and');
					break;
				case 'OR':
					$conditionTree->logic('or');
					$logicOr = true;
					break;
				default:
					throw new InvalidFilterException('Unknown logic in filter');
			}
		}

		foreach ($filter as $key => $val)
		{
			// Skip meta-key
			if ($key === '::LOGIC')
			{
				continue;
			}

			// Skip markers
			if ($key === '::MARKERS')
			{
				continue;
			}

			if ($this->isSubFilter($key))
			{
				$subFilter = $this->translateFilter($val);
				$subFilter && $conditionTree->where($subFilter);
				continue;
			}

			$key = ltrim($key);

			// This type of operations should be processed in special way
			// Fields like "META:DEADLINE_TS" will be replaced to "DEADLINE"
			if (mb_substr($key, -3) === '_TS')
			{
				$subFilter = $this->translateFilterTs($key, $val);
				if ($subFilter)
				{
					$conditionTree->where($subFilter);
				}
				continue;
			}

			$operation = $this->parseOperation($key);
			$field = $key;
			if ($operation !== self::OPERATION_DEFAULT)
			{
				$field = mb_substr($key, mb_strlen($operation));
			}

			switch ($field)
			{
				case "META::ID_OR_NAME":
					if (empty($val))
					{
						break;
					}

					$subFilter = Query::filter();
					$subFilter
						->logic('or')
						->where('ID', (int) $val);

					$val = strtoupper((string)$val);
					if ($this->query->needTitleEscape())
					{
						$val = $this->escapeStencilCharacters($val);
					}
					$subFilter->whereLike('TITLE', '%'. $val .'%');

					if ($subFilter)
					{
						$conditionTree->where($subFilter);
					}
					break;

				case "PARENT_ID":
				case "GROUP_ID":
				case "STATUS_CHANGED_BY":
				case "FORUM_TOPIC_ID":
					$subFilter = $this->createSubfilter($field, $val, $operation, self::CAST_NUMBER);
					if ($subFilter)
					{
						$conditionTree->where($subFilter);
					}
					break;

				case "ID":
				case "PRIORITY":
				case "CREATED_BY":
				case "RESPONSIBLE_ID":
				case "STAGE_ID":
				case "TIME_ESTIMATE":
				case "FORKED_BY_TEMPLATE_ID":
				case "DEADLINE_COUNTED":
					$subFilter = $this->createSubfilter($field, $val, $operation, self::CAST_NUMBER_WO_NULLS);
					if ($subFilter)
					{
						$conditionTree->where($subFilter);
					}
					break;

				case "REFERENCE:RESPONSIBLE_ID":
					$subFilter = $this->createSubfilter('RESPONSIBLE_ID', $val, $operation, self::CAST_REFERENCE);
					if ($subFilter)
					{
						$conditionTree->where($subFilter);
					}
					break;

				case "REFERENCE:START_DATE_PLAN":
					$subFilter = $this->createSubfilter('START_DATE_PLAN', $val, $operation, self::CAST_REFERENCE);
					if ($subFilter)
					{
						$conditionTree->where($subFilter);
					}
					break;

				case 'META:GROUP_ID_IS_NULL_OR_ZERO':
					$subFilter = $this->createSubfilter('GROUP_ID', $val, $operation, self::CAST_NULL_OR_ZERO);
					if ($subFilter)
					{
						$conditionTree->where($subFilter);
					}
					break;

				case "CHANGED_BY":
					$field = new ExpressionField(
						'CHANGED_BY'.mt_rand(1000, 9999),
						'CASE WHEN %1$s IS NULL THEN %2$s ELSE %1$s END',
						["CHANGED_BY", "CREATED_BY"]
					);
					$subFilter = $this->createSubfilter($field, $val, $operation, self::CAST_NUMBER);
					if ($subFilter)
					{
						$conditionTree->where($subFilter);
					}
					break;

				case 'GUID':
				case 'TITLE':
					if ($this->query->needTitleEscape())
					{
						$val = $this->escapeStencilCharacters($val);
					}
					$subFilter = $this->createSubfilter($field, $val, $operation, self::CAST_STRING);
					if ($subFilter)
					{
						$conditionTree->where($subFilter);
					}
					break;

				case 'FULL_SEARCH_INDEX':
				case 'COMMENT_SEARCH_INDEX':
					$subFilter = $this->createSubfilter(TaskQueryBuilder::ALIAS_SEARCH_FULL.'.SEARCH_INDEX', $val, $operation, self::CAST_FULLTEXT);

					if (!$subFilter)
					{
						break;
					}

					$join = Join::on('this.ID', 'ref.TASK_ID');
					if ($this->query->getParam('SEARCH_TASK_ONLY'))
					{
						$join->where('ref.MESSAGE_ID', 0);
					}
					else if (
						$field === 'COMMENT_SEARCH_INDEX'
						|| $this->query->getParam('SEARCH_COMMENT_ONLY')
					)
					{
						$join->where('ref.MESSAGE_ID', '!=', 0);
					}

					$subQuery = (new Query(TaskTable::getEntity()));
					$subQuery->setSelect(['ID']);
					$subQuery->where($subFilter);
					$subQuery->registerRuntimeField(
						TaskQueryBuilder::ALIAS_SEARCH_FULL,
						(new ReferenceField(
							TaskQueryBuilder::ALIAS_SEARCH_FULL,
							SearchIndexTable::getEntity(),
							$join
						))->configureJoinType('inner')
					);

					$subSql = $subQuery->getQuery();
					$conditionTree->whereIn('ID', new SqlExpression($subSql));
					break;

				case 'TAG':
					if (
						empty($val)
					)
					{
						break;
					}

					if (!is_array($val))
					{
						$val = [$val];
					}

					$tags = array_filter(
						array_map(
							function (string $tag) {
								return ($tag ? trim($tag) : false);
							},
							$val
						)
					);

					$tagsCount = count($tags);

					$subQuery = TaskQueryBuilder::createQuery(
						TaskQueryBuilder::ALIAS_TASK_TAG,
						LabelTable::getEntity()
					);

					$join = Join::on('this.ID', 'ref.TAG_ID');

					$subQuery->setSelect([
						TaskQueryBuilder::ALIAS_TASK_TAG . '.TASK_ID',
					]);

					$subQuery->registerRuntimeField(
						TaskQueryBuilder::ALIAS_TASK_TAG,
						(new ReferenceField(
							TaskQueryBuilder::ALIAS_TASK_TAG,
							TaskTagTable::getEntity(),
							$join
						))->configureJoinType('inner')
					);

					$subQuery->registerRuntimeField(
						'CNT',
						new ExpressionField(
							'CNT',
							'COUNT(%s)',
							TaskQueryBuilder::ALIAS_TASK_TAG . '.TASK_ID')
					);

					$subQuery->whereIn('NAME', $tags);
					$subQuery->addGroup(TaskQueryBuilder::ALIAS_TASK_TAG . '.TASK_ID');
					$subQuery->having('CNT', $tagsCount);

					$conditionTree->whereIn('ID', new SqlExpression($subQuery->getQuery()));
					break;

				case 'TAG_ID':
					if ((int)$val < 0)
					{
						break;
					}
					$this->registerRuntimeField(TaskQueryBuilder::ALIAS_TASK_TAG);
					$conditionTree->where(TaskQueryBuilder::ALIAS_TASK_TAG.'.TAG_ID', $val);
					break;

				case 'REAL_STATUS':
					$containCompletedSprint =
						is_array($val) && in_array(EntityForm::STATE_COMPLETED_IN_ACTIVE_SPRINT, $val, true)
					;
					$val = $this->removeStatusForActiveSprint($val);
					$subFilter = $this->createSubfilter('STATUS', $val, $operation, self::CAST_NUMBER);
					if ($containCompletedSprint)
					{
						$scrumFilter = Query::filter()
							->logic('and')
							->whereNotNull(TaskQueryBuilder::ALIAS_SCRUM_ITEM.'.ID')
							->where('STATUS', \CTasks::STATE_COMPLETED);

						if ($subFilter)
						{
							$subFilter = Query::filter()
								->logic('or')
								->where($subFilter)
								->where($scrumFilter);
						}
						else
						{
							$subFilter = $scrumFilter;
						}
					}
					if ($subFilter)
					{
						$conditionTree->where($subFilter);
						$this->registerRuntimeField(TaskQueryBuilder::ALIAS_SCRUM_ITEM);
					}
					break;

				case 'VIEWED':
					$field = new ExpressionField(
						'VIEWED_'.mt_rand(1000, 9999),
						'
							CASE
								WHEN
									%1$s IS NULL
									AND
									(%2$s = '.\CTasks::STATE_NEW.' OR %2$s = '.\CTasks::STATE_PENDING.')
								THEN
									0
								ELSE
									1
							END
						',
						[TaskQueryBuilder::ALIAS_TASK_VIEW.'.USER_ID', 'STATUS']
					);
					$subFilter = $this->createSubfilter($field, $val, $operation, self::CAST_NUMBER);
					if ($subFilter)
					{
						$conditionTree->where($subFilter);
					}
					$this->registerRuntimeField(TaskQueryBuilder::ALIAS_TASK_VIEW);
					break;

				case "STATUS_EXPIRED":
					$subFilter = Query::filter()
						->logic('and')
						->where('DEADLINE', '<', new SqlExpression('NOW()'))
						->whereNot('STATUS', \CTasks::STATE_SUPPOSEDLY_COMPLETED)
						->whereNot('STATUS', \CTasks::STATE_COMPLETED)
						->where(
							Query::filter()
								->logic('or')
								->whereNot('STATUS', \CTasks::STATE_DECLINED)
								->whereNot('RESPONSIBLE_ID', $this->behalfUserId)
						);

					if ($operation === self::OPERATION_NOT)
					{
						$conditionTree->whereNot($subFilter);
					}
					else
					{
						$conditionTree->where($subFilter);
					}
					break;

				case "STATUS_NEW": // viewed by a specified user + status is either new or pending
					$this->registerRuntimeField(TaskQueryBuilder::ALIAS_TASK_VIEW);

					$subFilter = Query::filter()
						->logic('and')
						->whereNull(TaskQueryBuilder::ALIAS_TASK_VIEW.'.USER_ID')
						->whereNot('CREATED_BY', $this->behalfUserId)
						->where(
							Query::filter()
								->logic('or')
								->where('STATUS', \CTasks::STATE_NEW)
								->where('STATUS', \CTasks::STATE_PENDING)
						);

					if ($operation === self::OPERATION_NOT)
					{
						$conditionTree->whereNot($subFilter);
					}
					else
					{
						$conditionTree->where($subFilter);
					}
					break;

				case "STATUS":
					$field = new ExpressionField(
						"STATUS_".mt_rand(1000, 9999),
						'
							CASE WHEN 
								%1$s < DATE_ADD(NOW(), INTERVAL '.Deadline::getDeadlineTimeLimit().' SECOND)
								AND %1$s >= NOW()
								AND %2$s != '.\CTasks::STATE_SUPPOSEDLY_COMPLETED.'
								AND %2$s != '.\CTasks::STATE_COMPLETED.'
								AND (
									%2$s != '.\CTasks::STATE_DECLINED.'
									OR %3$s != '.$this->behalfUserId.'
								)
							THEN
								'.\CTasks::METASTATE_EXPIRED_SOON.'
							WHEN
								%1$s < NOW() 
								AND %2$s != '.\CTasks::STATE_SUPPOSEDLY_COMPLETED.'
								AND %2$s != '.\CTasks::STATE_COMPLETED.'
								AND (
									%2$s != '.\CTasks::STATE_DECLINED.'
									OR %3$s != '.$this->behalfUserId.'
								)
							THEN
								'.\CTasks::METASTATE_EXPIRED.'
							WHEN
								%5$s IS NULL
								AND %4$s != '.$this->behalfUserId.'
								AND (
									%2$s = '.\CTasks::STATE_NEW.'
									OR %2$s = '.\CTasks::STATE_PENDING.'
								)
							THEN
								'.\CTasks::METASTATE_VIRGIN_NEW.'
							ELSE
								%2$s
							END
						',
						["DEADLINE", "STATUS", "RESPONSIBLE_ID", "CREATED_BY", TaskQueryBuilder::ALIAS_TASK_VIEW.".USER_ID"]
					);

					$subFilter = $this->createSubfilter($field, $val, $operation, self::CAST_NUMBER);
					if ($subFilter)
					{
						$conditionTree->where($subFilter);
					}
					$this->registerRuntimeField(TaskQueryBuilder::ALIAS_TASK_VIEW);
					break;

				case 'MARK':
				case 'XML_ID':
				case 'SITE_ID':
				case 'ADD_IN_REPORT':
				case 'ALLOW_TIME_TRACKING':
				case 'ALLOW_CHANGE_DEADLINE':
				case 'MATCH_WORK_TIME':
					$subFilter = $this->createSubfilter($field, $val, $operation, self::CAST_STRING_EQ);
					if ($subFilter)
					{
						$conditionTree->where($subFilter);
					}
					break;

				case "END_DATE_PLAN":
				case "START_DATE_PLAN":
				case "DATE_START":
				case "DEADLINE":
				case "CREATED_DATE":
				case "CLOSED_DATE":
					$subFilter = $this->createSubfilter($field, $val, $operation, self::CAST_DATE);
					if ($subFilter)
					{
						$conditionTree->where($subFilter);
					}
					break;

				case "CHANGED_DATE":
				case "ACTIVITY_DATE":
					$field = new ExpressionField(
						$field."_".mt_rand(1000, 9999),
						'
							CASE 
							WHEN %1$s IS NULL
							THEN %2$s
							ELSE %1$s 
							END
						',
						[$field, "CREATED_DATE"]
					);
					$subFilter = $this->createSubfilter($field, $val, $operation, self::CAST_DATE);
					if ($subFilter)
					{
						$conditionTree->where($subFilter);
					}
					break;

				case "ACCOMPLICE":
				case "AUDITOR":
					if (!is_array($val))
					{
						$val = [$val];
					}
					$val = array_filter($val);
					if (empty($val))
					{
						break;
					}

					$memberType = ($field === 'ACCOMPLICE')
						? MemberTable::MEMBER_TYPE_ACCOMPLICE
						: MemberTable::MEMBER_TYPE_AUDITOR;

					$alias = ($field === 'ACCOMPLICE')
						? TaskQueryBuilder::ALIAS_TASK_MEMBER_ACCOMPLICE
						: TaskQueryBuilder::ALIAS_TASK_MEMBER_AUDITOR;

					if ($operation === self::OPERATION_NOT)
					{
						$conditionTree->where(
							Query::filter()
								->logic('and')
								->where($alias . '.TYPE', $memberType)
								->whereNotIn($alias . '.USER_ID', $val)
						);
					}
					else
					{
						$conditionTree->where(
							Query::filter()
								->logic('and')
								->where($alias . '.TYPE', $memberType)
								->whereIn($alias . '.USER_ID', $val)
						);
					}
					$this->registerRuntimeField($alias);
					break;

				case "PERIOD":
				case "ACTIVE":
					if (
						!$val['START']
						&& !$val['END']
					)
					{
						break;
					}

					$dateStart = null;
					if ($val['START'])
					{
						$dateStart = new DateTime($val['START']);
					}

					$dateEnd = null;
					if ($val['END'])
					{
						$dateEnd = new DateTime($val['END']);
					}

					if (
						$dateStart
						&& $dateEnd
					)
					{
						$subFilter = Query::filter()
							->logic('or')
							->where(
								Query::filter()
									->logic('and')
									->where('CREATED_DATE', '>=', $dateStart)
									->where('CLOSED_DATE', '<=', $dateEnd)
							)
							->where(
								Query::filter()
									->logic('and')
									->where('CHANGED_DATE', '>=', $dateStart)
									->where('CHANGED_DATE', '<=', $dateEnd)
							)
							->where(
								Query::filter()
									->logic('and')
									->where('CREATED_DATE', '<=', $dateStart)
									->whereNull('CLOSED_DATE')
							);
					}
					elseif ($dateStart)
					{
						$subFilter = Query::filter()
							->logic('or')
							->where('CREATED_DATE', '>=', $dateStart)
							->where('CHANGED_DATE', '>=', $dateStart);
					}
					elseif ($dateEnd)
					{
						$subFilter = Query::filter()
							->logic('and')
							->where('CLOSED_DATE', '<=', $dateStart)
							->where('CHANGED_DATE', '<=', $dateEnd);
					}

					$conditionTree->where($subFilter);
					break;

				case 'DOER':
					$conditionTree->where(
						Query::filter()
							->logic('or')
							->where('RESPONSIBLE_ID', $val)
							->where(
								Query::filter()
									->logic('and')
									->where(TaskQueryBuilder::ALIAS_TASK_MEMBER.'.USER_ID', $val)
									->where(TaskQueryBuilder::ALIAS_TASK_MEMBER.'.TYPE', MemberTable::MEMBER_TYPE_ACCOMPLICE)
							)
					);
					$this->registerRuntimeField(TaskQueryBuilder::ALIAS_TASK_MEMBER);
					break;

				case 'MEMBER':
					$conditionTree->where(
						Query::filter()
							->logic('or')
							->where('CREATED_BY', $val)
							->where('RESPONSIBLE_ID', $val)
							->where(
								Query::filter()
									->logic('and')
									->where(TaskQueryBuilder::ALIAS_TASK_MEMBER.'.USER_ID', $val)
							)
					);
					$this->registerRuntimeField(TaskQueryBuilder::ALIAS_TASK_MEMBER);
					break;

				case 'DEPENDS_ON':
					if (empty($val))
					{
						break;
					}
					if (!is_array($val))
					{
						$val = [$val];
					}

					$subQuery = TaskQueryBuilder::createQuery(
						TaskQueryBuilder::ALIAS_TASK_DEPENDS,
						RelatedTable::getEntity()
					);
					$subQuery->setSelect(['*']);
					$subQuery->whereIn('TASK_ID', $val);
					$subQuery->where('DEPENDS_ON_ID', new SqlExpression('%s'));

					$subFilter = Query::filter();
					$subFilter->whereExpr("EXISTS({$subQuery->getQuery()})", ['ID']);

					$conditionTree->where($subFilter);
					break;

				case 'ONLY_ROOT_TASKS':
					if ($val !== 'Y')
					{
						break;
					}

					$where = $this->prepareForRootQuery($filter);
					unset($where['ONLY_ROOT_TASKS']);
					unset($where['SAME_GROUP_PARENT']);
					if (
						array_key_exists('SAME_GROUP_PARENT', $filter)
						&& $filter['SAME_GROUP_PARENT']
					)
					{
						$where['SAME_GROUP_PARENT_EX'] = 'Y';
					}

					$taskQuery = clone $this->query;
					$taskQuery
						->skipUfEscape()
						->skipTitleEscape()
						->setSelect(['ID'])
						->setWhere($where)
						->setLimit(0)
						->setOrder([])
						->setGroupBy([])
					;

					$subQuery = TaskQueryBuilder::build($taskQuery);
					$subSql = $subQuery->getQuery();

					$subFilter = Query::filter()
						->logic('or')
						->whereNull('PARENT_ID')
						->where('PARENT_ID', 0)
						->whereExpr("%2\$s NOT IN ({$subSql})", ["GROUP_ID", "PARENT_ID"]);

					$conditionTree->where($subFilter);
					break;

				case 'SUBORDINATE_TASKS':
					$subQuery = TaskQueryBuilder::createQuery(TaskQueryBuilder::ALIAS_TASK_MEMBER, MemberTable::getEntity());
					$subQuery
						->where('TASK_ID', '%s')
						->where('USER_ID', $this->behalfUserId);

					$subFieldMember = (new ExpressionField(
						"SUBORDINATE_TASKS_MEMBER_SUB",
						"EXISTS({$subQuery->getQuery()})",
						['ID']
					))->configureValueType(BooleanField::class);
					$this->registerRuntimeField("SUBORDINATE_TASKS_MEMBER_SUB", $subFieldMember);

					$subFilter = Query::filter()
						->logic('or')
						->where('CREATED_BY', $this->behalfUserId)
						->where('RESPONSIBLE_ID', $this->behalfUserId)
						->where('SUBORDINATE_TASKS_MEMBER_SUB', true);

					$subQuerySubordinate = \CTasks::GetSubordinateSql('', ['USER_ID' => $this->behalfUserId], ['USE_PLACEHOLDERS' => true]);
					if ($subQuerySubordinate)
					{
						$subFieldSubordinate = (new ExpressionField(
							"SUBORDINATE_TASKS_SUB",
							"EXISTS({$subQuerySubordinate})",
							["RESPONSIBLE_ID", "CREATED_BY", "ID"]
						))->configureValueType(BooleanField::class);
						$this->registerRuntimeField("SUBORDINATE_TASKS_SUB", $subFieldSubordinate);
						$subFilter->where('SUBORDINATE_TASKS_SUB', true);
					}

					$conditionTree->where($subFilter);

					break;

				case 'OVERDUED':
					if ($val !== 'Y')
					{
						break;
					}

					$conditionTree->where(
						Query::filter()
							->logic('and')
							->whereNotNull('CLOSED_DATE')
							->whereNotNull('DEADLINE')
							->whereColumn('DEADLINE', '<', 'CLOSED_DATE')
					);

					break;

				case 'SAME_GROUP_PARENT':
					if (
						$val !== 'Y'
						|| array_key_exists("ONLY_ROOT_TASKS", $filter)
					)
					{
						break;
					}

					$subQuery = TaskQueryBuilder::createQuery(TaskQueryBuilder::ALIAS_TASK, TaskTable::getEntity());
					$subQuery
						->where('ID', new SqlExpression('%1$s'))
						->where(
							Query::filter()
								->logic('or')
								->where('GROUP_ID', new SqlExpression('%2$s'))
								->where(
									Query::filter()
										->logic('and')
										->whereNull('GROUP_ID')
										->whereExpr('%%2$s is null', [])
								)
								->where(
									Query::filter()
										->logic('and')
										->whereNull('GROUP_ID')
										->whereExpr('%%2$s = 0', [])
								)
								->where(
									Query::filter()
										->logic('and')
										->where('GROUP_ID', 0)
										->whereExpr('%%2$s is null', [])
								)
						);

					$conditionTree->whereExpr("EXISTS({$subQuery->getQuery()})", ['PARENT_ID', 'GROUP_ID']);

					break;

				case 'SAME_GROUP_PARENT_EX':
					if ($val !== 'Y')
					{
						break;
					}

					$subFilter = Query::filter()
						->logic('OR')
						->where('GROUP_ID', new SqlExpression('%1$s'))
						->where(
							Query::filter()
								->logic('and')
								->whereNull('GROUP_ID')
								->whereExpr('%%1$s is null', [])
						)
						->where(
							Query::filter()
								->logic('and')
								->whereNull('GROUP_ID')
								->whereExpr('%%1$s = 0', [])
						)
						->where(
							Query::filter()
								->logic('and')
								->where('GROUP_ID', 0)
								->whereExpr('%%1$s is null', [])
						);

					$conditionTree->where($subFilter);

					break;

				case 'DEPARTMENT_ID':
					$subSql = \CTasks::GetDeparmentSql($val, "", [], ['USE_PLACEHOLDERS' => true]);
					$conditionTree->whereExpr("EXISTS({$subSql})", ["RESPONSIBLE_ID", "CREATED_BY", "ID"]);
					break;

				case 'CHECK_PERMISSIONS':
					break;

				case 'FAVORITE':
					$subFilter = $this->createSubfilter(TaskQueryBuilder::ALIAS_TASK_FAVORITE.".TASK_ID", $val, $operation, self::CAST_LEFT_EXIST);
					if ($subFilter)
					{
						$conditionTree->where($subFilter);
					}
					$this->registerRuntimeField(TaskQueryBuilder::ALIAS_TASK_FAVORITE);
					break;

				case 'SORTING':
					$subFilter = $this->createSubfilter(TaskQueryBuilder::ALIAS_TASK_SORT.".TASK_ID", $val, $operation, self::CAST_LEFT_EXIST);
					if ($subFilter)
					{
						$conditionTree->where($subFilter);
					}
					$this->registerRuntimeField(TaskQueryBuilder::ALIAS_TASK_SORT);
					break;

				case 'STAGES_ID':
					$subFilter = $this->createSubfilter(TaskQueryBuilder::ALIAS_TASK_STAGES.".STAGE_ID", $val, $operation, self::CAST_NUMBER);
					if ($subFilter)
					{
						$conditionTree->where($subFilter);
					}
					$this->registerRuntimeField(TaskQueryBuilder::ALIAS_TASK_STAGES);
					break;

				case 'PROJECT_NEW_COMMENTS':
				case 'PROJECT_EXPIRED':
					$subQuery = TaskQueryBuilder::createQuery(TaskQueryBuilder::ALIAS_TASK_COUNTERS, CounterTable::getEntity());
					$subQuery
						->where('GROUP_ID', new SqlExpression('%1$s'))
						->where('TASK_ID', new SqlExpression('%2$s'))
						->where('USER_ID', $this->behalfUserId);

					if ($field === 'PROJECT_NEW_COMMENTS')
					{
						$typesIn = array_merge(
							[CounterDictionary::COUNTER_GROUP_COMMENTS],
							CounterDictionary::MAP_MUTED_COMMENTS
						);

						$subQuery->whereIn('TYPE', CounterDictionary::MAP_COMMENTS);
					}
					else
					{
						$typesIn = array_merge(
							[CounterDictionary::COUNTER_GROUP_EXPIRED],
							CounterDictionary::MAP_MUTED_EXPIRED
						);

						$subQuery->whereIn('TYPE', CounterDictionary::MAP_EXPIRED);
					}

					$subFilter = Query::filter()
						->logic('and')
						->whereNotNull(TaskQueryBuilder::ALIAS_TASK_COUNTERS.'.ID')
						->whereIn(TaskQueryBuilder::ALIAS_TASK_COUNTERS.'.TYPE', $typesIn)
						->whereExpr("NOT EXISTS({$subQuery->getQuery()})", ["GROUP_ID", "ID"]);

					$conditionTree->where($subFilter);
					$this->registerRuntimeField(TaskQueryBuilder::ALIAS_TASK_COUNTERS);

					break;

				case 'WITH_COMMENT_COUNTERS':
					$types = array_merge(
						array_values(CounterDictionary::MAP_COMMENTS),
						array_values(CounterDictionary::MAP_MUTED_COMMENTS)
					);
					$conditionTree->where(
						Query::filter()
							->whereNotNull(TaskQueryBuilder::ALIAS_TASK_COUNTERS.'.ID')
							->whereIn(TaskQueryBuilder::ALIAS_TASK_COUNTERS.'.TYPE', $types)
					);
					$this->registerRuntimeField(TaskQueryBuilder::ALIAS_TASK_COUNTERS);
					break;

				case 'WITH_NEW_COMMENTS':
					if (!Loader::includeModule('forum'))
					{
						throw new LoadModuleException();
					}
					$subFilter = Query::filter()
						->logic('and')
						->where(
							Query::filter()
								->logic('or')
								->where(
									Query::filter()
										->logic('and')
										->whereNotNull(TaskQueryBuilder::ALIAS_TASK_VIEW.'.VIEWED_DATE')
										->whereColumn(TaskQueryBuilder::ALIAS_FORUM_MESSAGE.'.POST_DATE', '>', TaskQueryBuilder::ALIAS_TASK_VIEW.'.VIEWED_DATE')
								)
								->where(
									Query::filter()
										->logic('and')
										->whereNull(TaskQueryBuilder::ALIAS_TASK_VIEW.'.VIEWED_DATE')
										->whereColumn(TaskQueryBuilder::ALIAS_FORUM_MESSAGE.'.POST_DATE', '>=', 'CREATED_DATE')
								)
						)
						->where(TaskQueryBuilder::ALIAS_FORUM_MESSAGE.'.NEW_TOPIC', 'N')
						->where(
							Query::filter()
								->logic('or')
								->where(
									Query::filter()
										->logic('and')
										->whereNot(TaskQueryBuilder::ALIAS_FORUM_MESSAGE.'.AUTHOR_ID', $this->behalfUserId)
										->where(
											Query::filter()
												->logic('or')
												->whereNull(TaskQueryBuilder::ALIAS_FORUM_MESSAGE.'.UF_TASK_COMMENT_TYPE')
												->whereNot(TaskQueryBuilder::ALIAS_FORUM_MESSAGE.'.UF_TASK_COMMENT_TYPE', Comment::TYPE_EXPIRED)
										)
								)
								->where(TaskQueryBuilder::ALIAS_FORUM_MESSAGE.'.UF_TASK_COMMENT_TYPE', Comment::TYPE_EXPIRED_SOON)
						);

					$startCounterDate = \COption::GetOptionString("tasks", "tasksDropCommentCounters", null);
					if ($startCounterDate)
					{
						$subFilter->where(TaskQueryBuilder::ALIAS_FORUM_MESSAGE.'.POST_DATE', '>', DateTime::tryParse($startCounterDate, 'Y-m-d H:i:s'));
					}

					$conditionTree->where($subFilter);
					$this->registerRuntimeField(TaskQueryBuilder::ALIAS_FORUM_MESSAGE);
					$this->registerRuntimeField(TaskQueryBuilder::ALIAS_TASK_VIEW);
					break;

				case 'IS_MUTED':
				case 'IS_PINNED':
				case 'IS_PINNED_IN_GROUP':

					$optionMap = [
						'IS_MUTED' => Option::MUTED,
						'IS_PINNED' => Option::PINNED,
						'IS_PINNED_IN_GROUP' => Option::PINNED_IN_GROUP,
					];

					$subQuery = TaskQueryBuilder::createQuery(TaskQueryBuilder::ALIAS_TASK_OPTION, UserOptionTable::getEntity());
					$subQuery->addSelect('TASK_ID');
					$subQuery->where('OPTION_CODE', $optionMap[$key]);
					$subQuery->where('TASK_ID', new SqlExpression('%1$s'));
					$subQuery->where('USER_ID', $this->behalfUserId);

					if ($val === 'N')
					{
						$conditionTree->whereExpr('%1$s NOT IN ('. $subQuery->getQuery() .')', ["ID"]);
					}
					else
					{
						$conditionTree->whereExpr('%1$s IN ('. $subQuery->getQuery() .')', ["ID"]);
					}

					break;

				case 'SCRUM_TASKS':
					$this->registerRuntimeField(TaskQueryBuilder::ALIAS_SCRUM_ITEM_B);
					break;

				case 'STORY_POINTS':
					if ($val === 'Y')
					{
						$subSql = "NULLIF(%s, '') IS NOT NULL";
					}
					else
					{
						$subSql = "NULLIF(%s, '') IS NULL";
					}

					$conditionTree->whereExpr($subSql, [TaskQueryBuilder::ALIAS_SCRUM_ITEM_C.".STORY_POINTS"]);

					$this->registerRuntimeField(TaskQueryBuilder::ALIAS_SCRUM_ITEM_C);
					break;

				case 'EPIC':
					$this->registerRuntimeField(TaskQueryBuilder::ALIAS_SCRUM_ITEM_D);
					break;

				case 'SCENARIO_NAME':
					$filter = $this->query->getWhere();
					$scenario = (array_key_exists('SCENARIO_NAME', $filter))
						? $filter['SCENARIO_NAME']
						: null;

					if ($scenario === null)
					{
						break;
					}
					// filter by valid values
					if (is_array($scenario))
					{
						$scenario = ScenarioTable::filterByValidScenarios($scenario);
						if (empty($scenario))
						{
							break;
						}
					}
					else
					{
						if (!ScenarioTable::isValidScenario($scenario))
						{
							break;
						}
					}

					$this->registerRuntimeField(TaskQueryBuilder::ALIAS_TASK_SCENARIO);

					if (is_array($scenario))
					{
						$conditionTree->whereIn(TaskQueryBuilder::ALIAS_TASK_SCENARIO.'.SCENARIO', $scenario);
					}
					else
					{
						$conditionTree->where(TaskQueryBuilder::ALIAS_TASK_SCENARIO.'.SCENARIO', $scenario);
					}

					break;

				case 'IM_CHAT_ID':
				case 'IM_CHAT_CHAT_ID':
					if (!Loader::includeModule('im'))
					{
						break;
					}
					$fieldMap = [
						'IM_CHAT_ID' => TaskQueryBuilder::ALIAS_CHAT_TASK.'.ID',
						'IM_CHAT_CHAT_ID' => TaskQueryBuilder::ALIAS_CHAT_TASK.'.CHAT_ID',
					];
					$subFilter = $this->createSubfilter($fieldMap[$field], $val, $operation, self::CAST_NUMBER);
					if (!$subFilter)
					{
						break;
					}
					$this->registerRuntimeField(TaskQueryBuilder::ALIAS_CHAT_TASK);
					$conditionTree->where($subFilter);
					break;

				default:
					if (preg_match('/^UF_/', $field))
					{
						$conditions = $this->translateUfFilter($field, $operation, $val, $this->query->needUfEscape());
						$subFilter = Query::filter();
						if (count($conditions) > 1)
						{
							$subFilter->logic('or');
						}
						foreach ($conditions as $condition)
						{
							$subFilter->addCondition($condition);
						}
						$conditionTree->where($subFilter);
					}
			}
		}

		if (!$conditionTree->hasConditions())
		{
			return null;
		}

		$conditions = $conditionTree->getConditions();
		if (
			$logicOr
			&& count($conditions) < $filterCount
		)
		{
			// we've got OR logic and one or more empty filters
			return null;
		}

		return $conditionTree;
	}

	/**
	 * @param string|ExpressionField $field
	 * @param string|integer|array $values
	 * @param string $operation
	 * @param string $cast
	 * @return ConditionTree|null
	 */
	private function createSubfilter($field, $values, string $operation, string $cast): ?ConditionTree
	{
		if (!is_array($values))
		{
			$values = [$values];
		}
		$values = array_unique(array_values($values));

		if (count($values) === 0)
		{
			return null;
		}

		$filter = Query::filter();
		if ($operation !== self::OPERATION_NOT)
		{
			$filter->logic('or');
		}

		$condition = self::OPERATION_EQUAL;
		if (
			in_array(
				$operation,
				[
					self::OPERATION_GREAT,
					self::OPERATION_GREAT_EQ,
					self::OPERATION_LESS_EQ,
					self::OPERATION_LESS,
					self::OPERATION_NOT_EQUAL
				]
			)
		)
		{
			$condition = $operation;
		}

		if (
			$cast === self::CAST_NUMBER
			&& count($values) > 1
		)
		{
			$values = array_unique(array_map('intval', $values));
			if ($operation === self::OPERATION_NOT)
			{
				$filter->whereNotIn($field, $values);
			}
			else
			{
				$filter->whereIn($field, $values);
			}
			return $filter;
		}

		foreach ($values as $key => $value)
		{
			if (
				$cast === self::CAST_NUMBER
				&& !$value
			)
			{
				$value = 0;
			}

			if (
				!(
					$value === 0
					|| $value <> ''
					|| $value === false
				)
			)
			{
				$allowEmptyCastTypes = [
					self::CAST_NULL_OR_ZERO,
					self::CAST_DATE,
					self::CAST_LEFT_EXIST,
				];

				if (!in_array($cast, $allowEmptyCastTypes, true))
				{
					continue;
				}
			}

			switch ($cast)
			{
				case self::CAST_NUMBER:
					if ($values[$key] === false || !strlen($value))
					{
						($operation === self::OPERATION_NOT)
							? $filter->whereNotNull($field)
							: $filter->whereNull($field);
						break;
					}

					if ($operation === self::OPERATION_NOT)
					{
						$filter->where(
							Query::filter()
								->logic('or')
								->whereNull($field)
								->whereNot(
									Query::filter()
										->where($field, $condition, floatval($value))
								)
						);
					}
					else
					{
						$filter->where($field, $condition, floatval($value));
					}
					break;

				case self::CAST_NUMBER_WO_NULLS:
					if (!is_integer($value))
					{
						$value = floatval($value);
					}

					if ($operation === self::OPERATION_NOT)
					{
						$filter->whereNot(
							Query::filter()
								->where($field, $condition, $value)
						);
					}
					else
					{
						$filter->where($field, $condition, $value);
					}
					break;

				case self::CAST_REFERENCE:
					$value = trim($value);
					if (!preg_match('#^[a-z0-9_]+(\.{1}[a-z0-9_]+)*$#i', $value))
					{
						throw new InvalidFilterException();
					}
					if ($operation === self::OPERATION_DEFAULT)
					{
						$filter->whereColumn($field, '=', $value);
					}
					elseif ($operation === self::OPERATION_NOT)
					{
						$filter->whereColumn($field, '!=', $value);
					}
					elseif ($operation === self::OPERATION_LESS)
					{
						$filter->whereColumn($field, '<', $value);
					}
					elseif ($operation === self::OPERATION_GREAT)
					{
						$filter->whereColumn($field, '>', $value);
					}
					else
					{
						throw new InvalidFilterException();
					}
					break;

				case self::CAST_NULL_OR_ZERO:
					if ($operation === self::OPERATION_NOT)
					{
						$filter->where(
							Query::filter()
								->logic('and')
								->whereNotNull($field)
								->where($field, '!=', 0)
						);
					}
					else
					{
						$filter->where(
							Query::filter()
								->logic('or')
								->whereNull($field)
								->where($field, 0)
						);
					}
					break;

				case self::CAST_STRING:
					if (
						$operation === self::OPERATION_ASK
						|| $operation === self::OPERATION_LIKE
					)
					{
						$filter->whereLike($field, '%'.$value.'%');
					}
					elseif ($operation === self::OPERATION_NOT_LIKE)
					{
						$filter->whereNotLike($field, '%'.$value.'%');
					}
					elseif ($operation === self::OPERATION_MATCH_LIKE)
					{
						$filter->where([
							[self::OPERATION_MATCH_LIKE.$field, $value]
						]);
					}
					elseif (empty($value))
					{
						$subFilter = Query::filter()
							->logic('or')
							->whereNull($field)
							->where(Query::expr()->length($field), '<=', 0);

						($operation === self::OPERATION_NOT)
							? $filter->whereNot($subFilter)
							: $filter->where($subFilter);
					}
					elseif (
						$condition === self::OPERATION_EQUAL
						&& $operation === self::OPERATION_NOT
					)
					{
						$filter->where(
							Query::filter()
								->logic('or')
								->whereNull($field)
								->whereNotLike($field, '%'. $value .'%')
						);
					}
					elseif ($condition === self::OPERATION_EQUAL)
					{
						$filter->whereLike($field, '%'. $value .'%');
					}
					elseif ($operation === self::OPERATION_NOT)
					{
						$filter->where(
							Query::filter()
								->logic('or')
								->whereNull($field)
								->whereNot($field, $condition, $value)
						);
					}
					else
					{
						$filter->where($field, $condition, $value);
					}
					break;

				case self::CAST_FULLTEXT:
					if (
						$operation === self::OPERATION_MATCH_EQ
						|| $operation === self::OPERATION_MATCH
					)
					{
						$filter->whereMatch($field, $value);
					}
					elseif ($operation === self::OPERATION_MATCH_LIKE)
					{
						$filter->where([
							[self::OPERATION_MATCH_LIKE.$field, $value]
						]);
					}
					elseif (
						$operation === self::OPERATION_ASK
						|| $operation === self::OPERATION_LIKE
					)
					{
						if (
							$value === 0
							|| $value <> ''
						)
						{
							$filter->whereLike($field, '%'.$value.'%');
						}
					}
					elseif ($operation === self::OPERATION_NOT_LIKE)
					{
						$filter->where(
							Query::filter()
								->logic('or')
								->whereNull($field)
								->whereNotLike($field, '%'. $value .'%')
						);
					}
					elseif (empty($value))
					{
						$filter->where(
							Query::filter()
								->logic('or')
								->whereNull($field)
								->where(Query::expr()->length($field), '=', 0)
						);
					}
					elseif (
						$condition === '='
						&& $operation === self::OPERATION_NOT
					)
					{
						$filter->where(
							Query::filter()
								->logic('or')
								->whereNull($field)
								->whereNotLike($field, '%'. $value .'%')
						);
					}
					elseif (
						$condition === '='
						&& $operation !== self::OPERATION_EQUAL
						&& $operation !== self::OPERATION_NOT_EQUAL
					)
					{
						$filter->whereLike($field, '%'. $value .'%');
					}
					else
					{
						$filter->where($field, $condition, $value);
					}
					break;

				case self::CAST_STRING_EQ:
					if (empty($value))
					{
						$subFilter = Query::filter()
							->logic('or')
							->whereNull($field)
							->where(Query::expr()->length($field), '<=', 0);
						($operation === self::OPERATION_NOT)
							? $filter->whereNot($subFilter)
							: $filter->where($subFilter);
					}
					else
					{
						$subFilter = Query::filter()
							->where($field, $condition, $value);
						($operation === self::OPERATION_NOT)
							? $filter->where(
								Query::filter()
									->logic('or')
									->whereNull($field)
									->whereNot($subFilter)
							)
							: $filter->where($subFilter);
					}
					break;

				case self::CAST_DATE:
					if (empty($value))
					{
						($operation === self::OPERATION_NOT)
							? $filter->whereNotNull($field)
							: $filter->whereNull($field);
						break;
					}

					$entityFields = (TaskTable::getEntity())->getFields();
					if (
						is_string($field)
						&& array_key_exists($field, $entityFields)
						&& (is_a($entityFields[$field], DateTimeField::class)
							|| is_a($entityFields[$field], \Bitrix\Main\ORM\Fields\DatetimeField::class)
						) && is_string($value)
					)
					{
						$value = DateTime::createFromUserTime($value);
					}
					elseif (
						is_string($field)
						&& array_key_exists($field, $entityFields)
						&& is_a($entityFields[$field], DateField::class)
						&& is_string($value)
					)
					{
						$value = new Date($value);
					}
					elseif (is_string($value))
					{
						$value = DateTime::createFromUserTime($value)->format('Y-m-d H:i:s');
					}

					$subFilter = Query::filter()
						->where($field, $condition, $value);
					($operation === self::OPERATION_NOT)
						? $filter->where(
							Query::filter()
								->logic('or')
								->whereNull($field)
								->whereNot($subFilter)
						)
						: $filter->where($subFilter);

					break;

				case self::CAST_LEFT_EXIST:
					if ($condition !== '=')
					{
						throw new InvalidFilterException();
					}

					if (
						(
							$value === 'Y'
							&& $operation !== self::OPERATION_NOT
						)
						||
						(
							$value === 'N'
							&& $operation === self::OPERATION_NOT
						)
					)
					{
						$filter->whereNotNull($field);
					}
					else
					{
						$filter->whereNull($field);
					}
					break;
			}
		}

		return $filter;
	}

	/**
	 * @param $filter
	 * @return bool
	 */
	private function containCompletedStatus($filter): bool
	{
		$filterValues = $this->getFilteredFields($filter);
		foreach ($filterValues as $filterValue)
		{
			if (
				!is_array($filterValue)
				|| !array_key_exists('REAL_STATUS', $filterValue)
			)
			{
				continue;
			}

			if (!is_array($filterValue['REAL_STATUS']))
			{
				$filterValue['REAL_STATUS'] = [$filterValue['REAL_STATUS']];
			}

			foreach ($filterValue['REAL_STATUS'] as $realStatus)
			{
				if ($realStatus == EntityForm::STATE_COMPLETED_IN_ACTIVE_SPRINT)
				{
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * @param $filter
	 * @return array
	 */
	private function getFilteredFields($filter): array
	{
		$filteredFields = [];
		if (!is_array($filter))
		{
			return $filteredFields;
		}

		foreach ($filter as $key => $value)
		{
			if (
				$key === '::LOGIC'
				|| $key === '::MARKERS'
			)
			{
				continue;
			}

			if ($this->isSubFilter($key))
			{
				$filteredFields = array_merge($filteredFields, $this->getFilteredFields($value));
				continue;
			}

			$operation = $this->parseOperation($key);
			if ($operation !== self::OPERATION_DEFAULT)
			{
				$field = mb_substr($key, mb_strlen($operation));
			}
			if (!empty($field))
			{
				$filteredFields[] = $field;
			}
		}

		return $filteredFields;
	}

	/**
	 * @param $key
	 * @return bool
	 */
	private function isSubFilter($key): bool
	{
		return
			is_numeric($key)
			|| (mb_substr((string)$key, 0, 12) === '::SUBFILTER-');
	}

	/**
	 * @param $values
	 * @return mixed
	 */
	private function removeStatusForActiveSprint($values)
	{
		if (is_array($values))
		{
			foreach ($values as $key => $value)
			{
				if ($value === EntityForm::STATE_COMPLETED_IN_ACTIVE_SPRINT)
				{
					unset($values[$key]);
				}
			}
		}

		return $values;
	}

	/**
	 * @param string $key
	 * @return string
	 */
	private function parseOperation(string $key): string
	{
		$operations = [
			self::OPERATION_EQUAL, // I
			self::OPERATION_NOT_EQUAL, // NI
			self::OPERATION_LIKE, // S
			self::OPERATION_NOT_LIKE, // NS
			self::OPERATION_ASK, // ?
			self::OPERATION_GREAT_LESS, // B
			self::OPERATION_MATCH_EQ, // FTI
			self::OPERATION_MATCH_LIKE, // FTL
			self::OPERATION_MATCH, // FT
			self::OPERATION_NOT_NULL, // NB
			self::OPERATION_GREAT_EQ, // GE
			self::OPERATION_GREAT, // G
			self::OPERATION_LESS_EQ, // LE
			self::OPERATION_LESS, // L
			self::OPERATION_NOT, // N
		];

		foreach ($operations as $prefix)
		{
			if (!preg_match('/^'.preg_quote($prefix).'/', $key))
			{
				continue;
			}
			return $prefix;
		}

		if (preg_match('/^'.preg_quote(self::OPERATION_LEGACY).'/', $key))
		{
			$arManifest = \CTaskFilterCtrl::getManifest();
			$arOperationsMap = $arManifest['Operations map'];

			foreach ($arOperationsMap as $operationCode => $operationPrefix)
			{
				$pattern = '/^'.preg_quote($operationPrefix).'[A-Za-z]/';
				if (preg_match($pattern, $key))
				{
					return self::OPERATION_LEGACY.$operationCode;
				}
			}
		}

		return self::OPERATION_DEFAULT;
	}

	/**
	 * @param string $key
	 * @param $val
	 * @return ConditionTree
	 */
	private function translateFilterTs(string $key, $val): ?ConditionTree
	{
		$timestamp = time();

		if (!\CTimeZone::enabled())
		{
			\CTimeZone::enable();
		}

		$tzOffset = \CTimeZone::getOffset();
		$timestamp += $tzOffset;

		if (!\CTimeZone::enabled())
		{
			\CTimeZone::disable();
		}

		$filter = [
			'::LOGIC' => 'AND'
		];
		$key = ltrim($key);

		$operation = $this->parseOperation($key);
		if (mb_substr($operation, 0, 1) === self::OPERATION_LEGACY)
		{
			$operationCode = (int) mb_substr($operation, 1);

			$arManifest = \CTaskFilterCtrl::getManifest();
			$arOperationsMap = $arManifest['Operations map'];
			$operationPrefix = $arOperationsMap[$operationCode];
			$fieldName = mb_substr($key, mb_strlen($operationPrefix) + 5, -3); // Cutoff operation, prefix "META:" and suffix "_TS"
		}
		else
		{
			$fieldName = mb_substr($key, 0, mb_strlen($key) - 3);
			if ($operation !== self::OPERATION_DEFAULT)
			{
				$fieldName = mb_substr($fieldName, mb_strlen($operation));
			}

			switch ($operation)
			{
				case self::OPERATION_LESS:
					$operationCode = \CTaskFilterCtrl::OP_STRICTLY_LESS;
					break;

				case self::OPERATION_GREAT:
					$operationCode = \CTaskFilterCtrl::OP_STRICTLY_GREATER;
					break;

				case self::OPERATION_LESS_EQ:
					$operationCode = \CTaskFilterCtrl::OP_LESS_OR_EQUAL;
					break;

				case self::OPERATION_GREAT_EQ:
					$operationCode = \CTaskFilterCtrl::OP_GREATER_OR_EQUAL;
					break;

				case self::OPERATION_NOT_EQUAL:
					$operationCode = \CTaskFilterCtrl::OP_NOT_EQUAL;
					break;

				case self::OPERATION_DEFAULT:
				case self::OPERATION_EQUAL:
					$operationCode = \CTaskFilterCtrl::OP_EQUAL;
					break;

				default:
					return null;
			}
		}

		if (
			$operationCode !== \CTaskFilterCtrl::OP_DATE_NEXT_DAYS
			&& $operationCode !== \CTaskFilterCtrl::OP_DATE_LAST_DAYS
		)
		{
			$val += $tzOffset;
		}

		$dateFinish = null;
		$operationB = null;
		$ts2 = null;
		$weekDay = date('N');

		switch ($operationCode)
		{
			case \CTaskFilterCtrl::OP_DATE_TODAY:
				$operationA = self::OPERATION_GREAT_EQ;
				$operationB = self::OPERATION_LESS_EQ;
				$ts1 = $ts2 = $timestamp;
				break;

			case \CTaskFilterCtrl::OP_DATE_YESTERDAY:
				$operationA = self::OPERATION_GREAT_EQ;
				$operationB = self::OPERATION_LESS_EQ;
				$ts1 = $ts2 = $timestamp - 86400;
				break;

			case \CTaskFilterCtrl::OP_DATE_TOMORROW:
				$operationA = self::OPERATION_GREAT_EQ;
				$operationB = self::OPERATION_LESS_EQ;
				$ts1 = $ts2 = $timestamp + 86400;
				break;

			case \CTaskFilterCtrl::OP_DATE_CUR_WEEK:
				$operationA = self::OPERATION_GREAT_EQ;
				$operationB = self::OPERATION_LESS_EQ;
				$ts1 = $timestamp - ($weekDay - 1) * 86400;
				$ts2 = $timestamp + (7 - $weekDay) * 86400;
				break;

			case \CTaskFilterCtrl::OP_DATE_PREV_WEEK:
				$operationA = self::OPERATION_GREAT_EQ;
				$operationB = self::OPERATION_LESS_EQ;
				$ts1 = $timestamp - ($weekDay - 1 + 7) * 86400;
				$ts2 = $timestamp - $weekDay * 86400;
				break;

			case \CTaskFilterCtrl::OP_DATE_NEXT_WEEK:
				$operationA = self::OPERATION_GREAT_EQ;
				$operationB = self::OPERATION_LESS_EQ;
				$ts1 = $timestamp + (7 - $weekDay + 1) * 86400;
				$ts2 = $timestamp + (7 - $weekDay + 7) * 86400;
				break;

			case \CTaskFilterCtrl::OP_DATE_CUR_MONTH:
				$operationA = self::OPERATION_GREAT_EQ;
				$operationB = self::OPERATION_LESS_EQ;
				$ts1 = mktime(0, 0, 0, date('n', $timestamp), 1, date('Y', $timestamp));
				$ts2 = mktime(23, 59, 59, date('n', $timestamp) + 1, 0, date('Y', $timestamp));
				break;

			case \CTaskFilterCtrl::OP_DATE_PREV_MONTH:
				$operationA = self::OPERATION_GREAT_EQ;
				$operationB = self::OPERATION_LESS_EQ;
				$ts1 = mktime(0, 0, 0, date('n', $timestamp) - 1, 1, date('Y', $timestamp));
				$ts2 = mktime(23, 59, 59, date('n', $timestamp), 0, date('Y', $timestamp));
				break;

			case \CTaskFilterCtrl::OP_DATE_NEXT_MONTH:
				$operationA = self::OPERATION_GREAT_EQ;
				$operationB = self::OPERATION_LESS_EQ;
				$ts1 = mktime(0, 0, 0, date('n', $timestamp) + 1, 1, date('Y', $timestamp));
				$ts2 = mktime(23, 59, 59, date('n', $timestamp) + 2, 0, date('Y', $timestamp));
				break;

			case \CTaskFilterCtrl::OP_DATE_LAST_DAYS:
				$operationA = self::OPERATION_GREAT_EQ;
				$operationB = self::OPERATION_LESS_EQ;
				$ts1 = $timestamp - ((int)$val) * 86400; // val in days
				$ts2 = $timestamp;
				break;

			case \CTaskFilterCtrl::OP_DATE_NEXT_DAYS:
				$operationA = self::OPERATION_GREAT_EQ;
				$operationB = self::OPERATION_LESS_EQ;
				$ts1 = $timestamp;
				$ts2 = $timestamp + ((int)$val) * 86400; // val in days
				break;

			case \CTaskFilterCtrl::OP_EQUAL:
				$operationA = self::OPERATION_GREAT_EQ;
				$operationB = self::OPERATION_LESS_EQ;
				$ts1 = mktime(0, 0, 0, date('n', $val), date('j', $val), date('Y', $val));
				$ts2 = mktime(23, 59, 59, date('n', $val), date('j', $val), date('Y', $val));
				break;

			case \CTaskFilterCtrl::OP_LESS_OR_EQUAL:
				$operationA = self::OPERATION_LESS_EQ;
				$ts1 = $val;
				break;

			case \CTaskFilterCtrl::OP_GREATER_OR_EQUAL:
				$operationA = self::OPERATION_GREAT_EQ;
				$ts1 = $val;
				break;

			case \CTaskFilterCtrl::OP_NOT_EQUAL:
				$operationA = self::OPERATION_LESS;
				$operationB = self::OPERATION_GREAT;
				$ts1 = mktime(0, 0, 0, date('n', $val), date('j', $val), date('Y', $val));
				$ts2 = mktime(23, 59, 59, date('n', $val), date('j', $val), date('Y', $val));
				break;

			case \CTaskFilterCtrl::OP_STRICTLY_LESS:
				$operationA = self::OPERATION_LESS;
				$ts1 = $val;
				break;

			case \CTaskFilterCtrl::OP_STRICTLY_GREATER:
				$operationA = self::OPERATION_GREAT;
				$ts1 = $val;
				break;

			default:
				return null;
		}

		$dateStart = ConvertTimeStamp(mktime(0, 0, 0, date('n', $ts1), date('j', $ts1), date('Y', $ts1)), 'FULL');
		if ($ts2)
		{
			$dateFinish = ConvertTimeStamp(mktime(23, 59, 59, date('n', $ts2), date('j', $ts2), date('Y', $ts2)), 'FULL');
		}

		if ($dateStart)
		{
			$arrayKey = $operationA.$fieldName;
			while (isset($filter[$arrayKey]))
			{
				$arrayKey = ' '.$arrayKey;
			}

			$filter[$arrayKey] = $dateStart;
		}

		if (
			$operationB
			&& $dateFinish
		)
		{
			$arrayKey = $operationB.$fieldName;
			while (isset($filter[$arrayKey]))
			{
				$arrayKey = ' '.$arrayKey;
			}

			$filter[$arrayKey] = $dateFinish;
		}

		return $this->translateFilter($filter);
	}

	/**
	 * @param array $filter
	 * @return $this
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function optimizeFilter(array $filter): self
	{
		// get rid of ::SUBFILTER-ROOT if can
		if (
			array_key_exists('::SUBFILTER-ROOT', $filter)
			&& count($filter) === 1
		)
		{
			if ($filter['::LOGIC'] != 'OR')
			{
				// we have only one element in the root, and logic is not "OR". then we could remove subfilter-root
				$filter = $filter['::SUBFILTER-ROOT'];
			}
		}

		// we can optimize only if there is no "or-logic"
		if (
			(
				isset($filter['::LOGIC'])
				&& $filter['::LOGIC'] === 'OR'
			) || (
				isset($filter['LOGIC'])
				&& $filter['LOGIC'] === 'OR'
			)
		)
		{
			return $this;
		}

		$join = Join::on('this.ID', 'ref.TASK_ID');

		// MEMBER
		if (
			array_key_exists('MEMBER', $filter)
			||
			(
				isset($filter[self::FILTER_ROLE_KEY])
				&& array_key_exists('MEMBER', $filter[self::FILTER_ROLE_KEY])
			)
		)
		{
			if (array_key_exists('MEMBER', $filter))
			{
				$member = intval($filter['MEMBER']);
				unset($filter['MEMBER']);
			}
			else
			{
				$member = intval($filter[self::FILTER_ROLE_KEY]['MEMBER']);
				unset($filter[self::FILTER_ROLE_KEY]);
			}

			$join->where('ref.USER_ID', $member);
		}
		// DOER
		elseif (array_key_exists('DOER', $filter))
		{
			$doer = intval($filter['DOER']);
			unset($filter['DOER']);

			$join
				->where('ref.USER_ID', $doer)
				->whereIn('ref.TYPE', [MemberTable::MEMBER_TYPE_RESPONSIBLE, MemberTable::MEMBER_TYPE_ACCOMPLICE]);
		}
		// RESPONSIBLE
		elseif (
			isset($filter[self::FILTER_ROLE_KEY])
			&& array_key_exists('=RESPONSIBLE_ID', $filter[self::FILTER_ROLE_KEY])
		)
		{
			$responsible = (int)$filter[self::FILTER_ROLE_KEY]['=RESPONSIBLE_ID'];
			unset($filter[self::FILTER_ROLE_KEY]);

			$join
				->where('ref.USER_ID', $responsible)
				->where('ref.TYPE', MemberTable::MEMBER_TYPE_RESPONSIBLE);
		}
		// CREATOR
		elseif (
			isset($filter[self::FILTER_ROLE_KEY])
			&& array_key_exists('=CREATED_BY', $filter[self::FILTER_ROLE_KEY])
		)
		{
			$creator = (int)$filter[self::FILTER_ROLE_KEY]['=CREATED_BY'];
			unset($filter[self::FILTER_ROLE_KEY]['=CREATED_BY']);

			if (!empty($filter[self::FILTER_ROLE_KEY]))
			{
				$filter += $filter[self::FILTER_ROLE_KEY];
			}
			unset($filter[self::FILTER_ROLE_KEY]);

			$join
				->where('ref.USER_ID', $creator)
				->where('ref.TYPE', MemberTable::MEMBER_TYPE_ORIGINATOR);
		}
		// ACCOMPLICE
		elseif (
			array_key_exists('ACCOMPLICE', $filter)
			||
			(
				isset($filter[self::FILTER_ROLE_KEY])
				&& array_key_exists('=ACCOMPLICE', $filter[self::FILTER_ROLE_KEY])
			)
		)
		{
			if (array_key_exists('ACCOMPLICE', $filter))
			{
				if (!is_array($filter['ACCOMPLICE'])) // we have single value, not array which will cause "in ()" instead of =
				{
					$accomplice = intval($filter['ACCOMPLICE']);
					unset($filter['ACCOMPLICE']);

					$join
						->where('ref.USER_ID', $accomplice)
						->where('ref.TYPE', MemberTable::MEMBER_TYPE_ACCOMPLICE);
				}
			}
			elseif (!is_array($filter[self::FILTER_ROLE_KEY]['=ACCOMPLICE']))
			{
				$accomplice = intval($filter[self::FILTER_ROLE_KEY]['=ACCOMPLICE']);
				unset($filter[self::FILTER_ROLE_KEY]);

				$join
					->where('ref.USER_ID', $accomplice)
					->where('ref.TYPE', MemberTable::MEMBER_TYPE_ACCOMPLICE);
			}
		}
		// AUDITOR
		elseif (
			array_key_exists('AUDITOR', $filter)
			||
			(
				isset($filter[self::FILTER_ROLE_KEY])
				&& array_key_exists('=AUDITOR', $filter[self::FILTER_ROLE_KEY])
			)
		)
		{
			if (array_key_exists('AUDITOR', $filter))
			{
				if (!is_array($filter['AUDITOR'])) // we have single value, not array which will cause "in ()" instead of =
				{
					$auditor = intval($filter['AUDITOR']);
					unset($filter['AUDITOR']);

					$join
						->where('ref.USER_ID', $auditor)
						->where('ref.TYPE', MemberTable::MEMBER_TYPE_AUDITOR);
				}
			}
			elseif (!is_array($filter[self::FILTER_ROLE_KEY]['=AUDITOR']))
			{
				$auditor = intval($filter[self::FILTER_ROLE_KEY]['=AUDITOR']);
				unset($filter[self::FILTER_ROLE_KEY]);

				$join
					->where('ref.USER_ID', $auditor)
					->where('ref.TYPE', MemberTable::MEMBER_TYPE_AUDITOR);
			}
		}
		else
		{
			return $this;
		}

		$this->registerRuntimeField(
			TaskQueryBuilder::ALIAS_TASK_MEMBER,
			(new ReferenceField(
				TaskQueryBuilder::ALIAS_TASK_MEMBER,
				MemberTable::getEntity(),
				$join
			))->configureJoinType('inner')
		);

		return $this;
	}

	/**
	 * @param string $alias
	 * @param ReferenceField|ExpressionField|null $field
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

	/**
	 * @param string $field
	 * @param string $operation
	 * @param array $uf
	 * @param bool $needEscape
	 * @return Condition
	 * @throws ObjectException
	 */
	private function translateUfFilter(string $field, string $operation, array $uf, bool $needEscape = true): array
	{
		$value = $uf['value'];
		if ($uf['type'] === 'datetime')
		{
			$value = new Date($value);
		}

		if (is_null($value))
		{
			return [new Condition($field, '=', 0), new Condition($field, '=', null)];
		}

		if (in_array($operation, [self::OPERATION_LESS, self::OPERATION_LESS_EQ, self::OPERATION_GREAT, self::OPERATION_GREAT_EQ]))
		{
			return [new Condition($field, $operation, $value)];
		}

		if ($operation === self::OPERATION_LIKE)
		{
			if ($needEscape)
			{
				$value = $this->escapeStencilCharacters($value);
			}
			return [new Condition($field, 'like', '%' . $value . '%')];
		}

		if ($operation === self::OPERATION_NOT)
		{
			return [new Condition($field, '!=', $value)];
		}

		return [new Condition($field, '=', $value)];
	}

	/**
	 * @param string $value
	 * @return string
	 */
	private function prepareForSprintf(string $value): string
	{
		return str_replace('%', '%%', $value);
	}

	/**
	 * @param array $filter
	 * @return array
	 */
	private function prepareForRootQuery(array $filter): array
	{
		foreach ($filter as $filterKey => $field)
		{
			$operation = $this->parseOperation($filterKey);
			if ($operation !== self::OPERATION_DEFAULT)
			{
				$filterKey = mb_substr($filterKey, mb_strlen($operation));
			}
			switch ($filterKey)
			{
				case 'META::ID_OR_NAME':
					$filter[$filterKey] = '%' . $this->prepareForSprintf($field) . '%';
					break;

				case '::SUBFILTER-TITLE':
					foreach ($field as $fieldKey => $fieldValue)
					{
						$operation = $this->parseOperation($fieldKey);
						if ($operation === self::OPERATION_LIKE)
						{
							$filter[$filterKey][$fieldKey] = '%' . $this->prepareForSprintf($fieldValue) . '%';
						}
						else
						{
							$filter[$filterKey][$fieldKey] = $this->prepareForSprintf($fieldValue);
						}
					}
					break;

				case '::SUBFILTER-TAG':
					$tags = [];
					foreach ($field as $fieldKey => $fieldValue)
					{
						if (!is_array($filterKey))
						{
							continue;
						}
						foreach ($fieldValue as $tag)
						{
							$tags[] = $this->prepareForSprintf($tag);
						}
						$filter[$filterKey][$fieldKey] = $tags;
					}
					break;

				default:
					if (strpos($filterKey, 'UF_') === 0)
					{
						if ($operation === self::OPERATION_LIKE)
						{
							$filter[$operation . $filterKey] = '%' . $this->prepareForSprintf($field['value']) . '%';

						}
						else
						{
							$filter[$operation . $filterKey] = $this->prepareForSprintf($field['value']);
						}
					}
			}
		}

		return $filter;
	}

	/**
	 * @param string $value
	 * @return string
	 */
	public function escapeStencilCharacters(string $value): string
	{
		return str_replace(['!', '_', '%'], ['\!', '\_', '\%'], $value);
	}

	private function prepareFilter(array $filter): array
	{
		$userFieldsType = $this->ufManager->getFields(true);

		foreach ($filter as $key => $val)
		{
			$field = ltrim($key);
			$operation = $this->parseOperation($field);
			if ($operation !== self::OPERATION_DEFAULT)
			{
				$field = mb_substr($key, mb_strlen($operation));
			}
			if (strpos($field, 'UF_') === 0)
			{
				$filter[$key] = [
					'value' => $val,
					'type' => $userFieldsType[$field],
				];
			}

		}

		return $filter;
	}
}