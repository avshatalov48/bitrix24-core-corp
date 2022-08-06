<?php

namespace Bitrix\Tasks\Internals\Project;

use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\Query\Join;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\ORM\Query\Filter\Helper;
use Bitrix\Main\Search\Content;
use Bitrix\Socialnetwork\Item\Workgroup;
use Bitrix\Socialnetwork\UserToGroupTable;
use Bitrix\SocialNetwork\WorkgroupTagTable;
use Bitrix\Tasks\Internals\Counter;
use Bitrix\Tasks\Util\User;

class Filter
{
	/** @var ConditionTree */
	private $projectVisibilityCondition;

	protected $userId;
	protected $isScrum = false;

	public function __construct(int $userId = 0)
	{
		$this->userId = ($userId ?: User::getId());
	}

	/**
	 * @param bool $value
	 * @return $this
	 */
	public function setIsScrum(bool $value)
	{
		$this->isScrum = true;
		return $this;
	}

	public function processFilterSearch(Query $query, string $search): Query
	{
		$search = Helper::matchAgainstWildcard(Content::prepareStringToken(trim($search)));

		if ($search !== '')
		{
			$query->whereMatch('SEARCH_INDEX', $search);
		}

		return $query;
	}

	public function processFilterOwner(Query $query, int $ownerId): Query
	{
		$query->where('OWNER_ID', $ownerId);

		return $query;
	}

	public function processFilterMember(Query $query, int $memberId): Query
	{
		$query
			->setDistinct(true)
			->registerRuntimeField(
				'UG2',
				new ReferenceField(
					'UG2',
					UserToGroupTable::getEntity(),
					Join::on('this.ID', 'ref.GROUP_ID'),
					['join_type' => 'left']
				)
			)
			->whereIn('UG2.USER_ID', $memberId)
			->whereNotNull('UG2.ID')
			->whereIn('UG2.ROLE', UserToGroupTable::getRolesMember())
		;

		return $query;
	}

	public function processFilterCounters(Query $query, string $counter): Query
	{
		$query->getFilterHandler()->removeCondition($this->getProjectVisibilityCondition());
		$query
			->setDistinct(true)
			->registerRuntimeField(
				'TS',
				new ReferenceField(
					'TS',
					Counter\CounterTable::getEntity(),
					Join::on('this.ID', 'ref.GROUP_ID')->where('ref.USER_ID', $this->userId),
					['join_type' => 'inner']
				)
			)
			->where(
				Query::filter()
					->whereNotNull('UG.ID')
					->whereIn('UG.ROLE', UserToGroupTable::getRolesMember())
			)
		;

		$typesMap = [
			'EXPIRED' => [
				'INCLUDE' => Counter\CounterDictionary::MAP_EXPIRED,
				'EXCLUDE' => null,
			],
			'NEW_COMMENTS' => [
				'INCLUDE' => Counter\CounterDictionary::MAP_COMMENTS,
				'EXCLUDE' => null,
			],
			'PROJECT_EXPIRED' => [
				'INCLUDE' => array_merge(
					[Counter\CounterDictionary::COUNTER_GROUP_EXPIRED],
					Counter\CounterDictionary::MAP_MUTED_EXPIRED
				),
				'EXCLUDE' => Counter\CounterDictionary::MAP_EXPIRED,
			],
			'PROJECT_NEW_COMMENTS' => [
				'INCLUDE' => array_merge(
					[Counter\CounterDictionary::COUNTER_GROUP_COMMENTS],
					Counter\CounterDictionary::MAP_MUTED_COMMENTS
				),
				'EXCLUDE' => Counter\CounterDictionary::MAP_COMMENTS,
			],
		];
		$type = $typesMap[$counter];

		$condition = Query::filter()->whereIn('TS.TYPE', $type['INCLUDE']);
		if ($type['EXCLUDE'])
		{
			$typesToExclude = "('" . implode("','", $type['EXCLUDE']) . "')";
			$query->registerRuntimeField(
				'EXCLUDED_COUNTER_EXISTS',
				new ExpressionField(
					'EXCLUDED_COUNTER_EXISTS',
					"(
							SELECT 1
							FROM b_tasks_scorer
							WHERE GROUP_ID = %s
							  AND TASK_ID = %s
							  AND USER_ID = {$this->userId}
							  AND TYPE IN {$typesToExclude}
							LIMIT 1
						)",
					['ID', 'TS.TASK_ID']
				)
			);
			$condition->whereNull('EXCLUDED_COUNTER_EXISTS');
		}

		$query->where($condition);

		return $query;
	}

	public function processFilterTags(Query $query, string $tag): Query
	{
		$query
			->registerRuntimeField(
				'WT',
				new ReferenceField(
					'WT',
					WorkgroupTagTable::getEntity(),
					Join::on('this.ID', 'ref.GROUP_ID'),
					['join_type' => 'left']
				)
			)
			->where('WT.NAME', $tag)
		;

		return $query;
	}

	public function processFilterIsClosed(Query $query, string $isClosed): Query
	{
		$query->where('CLOSED', $isClosed);

		return $query;
	}

	public function processFilterIsProject(Query $query, string $isProject): Query
	{
		$query->where('PROJECT', $isProject);

		return $query;
	}

	public function processFilterType(Query $query, string $typeName): Query
	{
		$types = $this->getProjectTypes();
		$type = $types[$typeName];

		if ($type)
		{
			$condition =
				Query::filter()
					->where('OPENED', $type['OPENED'])
					->where('VISIBLE', $type['VISIBLE'])
					->where('PROJECT', $type['PROJECT'])
			;
			if ($type['EXTERNAL'] !== 'N')
			{
				$condition->where('SITE_ID', \CExtranet::GetExtranetSiteID());
			}

			$query->where($condition);
		}

		return $query;
	}

	public function getProjectVisibilityCondition(): ConditionTree
	{
		if (!$this->projectVisibilityCondition)
		{
			$this->projectVisibilityCondition =
				Query::filter()
					->logic('or')
					->where('VISIBLE', 'Y')
					->where(
						Query::filter()
							->whereNotNull('UG.ID')
							->whereIn('UG.ROLE', UserToGroupTable::getRolesMember())
					)
			;
		}

		return $this->projectVisibilityCondition;
	}

	protected function getProjectTypes(): array
	{
		static $types = [];

		if (empty($types))
		{
			$types = Workgroup::getTypes([
				'category' => ['projects', 'groups'],
				'entityOptions' => ['scrum' => false],
			]);
		}

		return $types;
	}
}
