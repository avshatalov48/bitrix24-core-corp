<?php

namespace Bitrix\Crm\Counter\CounterQueryBuilder\DeadlineBased;

use Bitrix\Crm\Activity\Entity\EntityUncompletedActivityTable;
use Bitrix\Crm\Counter\CounterQueryBuilder\BuilderParams\QueryParams;
use Bitrix\Crm\Counter\CounterQueryBuilder\CounterQueryBuilder;
use Bitrix\Crm\Counter\CounterQueryBuilder\DeadlineBased\DateFilters\DateFilter;
use Bitrix\Crm\Counter\CounterQueryBuilder\QueryParts;
use Bitrix\Crm\Service\Factory;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\ORM\Query\Query;

final class UncompletedBased implements CounterQueryBuilder
{

	private DateFilter $dateFilters;

	private QueryParts\SelectFields $qpSelectFields;

	private QueryParts\EntitySpecificFilter $qpEntitySpecificFilter;

	private QueryParts\ResponsibleFilter $qpResponsibleFilter;

	public function __construct(DateFilter $dateFilters)
	{
		$this->dateFilters = $dateFilters;
		$this->qpSelectFields = new QueryParts\SelectFields();
		$this->qpEntitySpecificFilter = new QueryParts\EntitySpecificFilter();
		$this->qpResponsibleFilter = new QueryParts\ResponsibleFilter();
	}

	public function build(Factory $factory, QueryParams $params): Query
	{
		$query = $factory->getDataClass()::query();

		$this->joinUncompletedTableWithConditions($params, $query);

		$this->qpEntitySpecificFilter->apply($query, $params->entityTypeId(), $params->options());

		if (!$params->useActivityResponsible())
		{
			$this->qpResponsibleFilter->applyByItemFactory($query, $params->userParams(), $factory);
		}

		$this->qpSelectFields->applyForUncompleted($query, $params);

		return $query;
	}

	private function joinUncompletedTableWithConditions(QueryParams $params, Query $query): void
	{
		$referenceFilter = (new ConditionTree())
			->whereColumn('ref.ENTITY_ID', 'this.ID')
			->where('ref.ENTITY_TYPE_ID', new SqlExpression($params->entityTypeId()));


		if ($params->useActivityResponsible())
		{
			$this->filterResponsibleByActivityWay($referenceFilter, $params, $query);
		}
		else
		{
			$referenceFilter->where('ref.RESPONSIBLE_ID', new SqlExpression('?i', 0));
		}

		if (is_null($params->hasAnyIncomingChannel()))
		{
			$referenceFilter->whereIn('ref.HAS_ANY_INCOMING_CHANEL', ['N', 'Y']);
		}
		else
		{
			$referenceFilter
				->where('ref.HAS_ANY_INCOMING_CHANEL',
					new SqlExpression('?', $params->hasAnyIncomingChannel() ? 'Y' : 'N'));
		}

		$this->applyDeadlineReferenceFilter($referenceFilter, $params);

		$query->registerRuntimeField(
			'',
			new ReferenceField('B',
				EntityUncompletedActivityTable::getEntity(),
				$referenceFilter,
				['join_type' => Join::TYPE_INNER]
			)
		);
	}

	private function applyDeadlineReferenceFilter(ConditionTree $referenceFilter, QueryParams $params): void
	{
		$this->dateFilters->applyFilter($referenceFilter, $params);

	}

	private function filterResponsibleByActivityWay(ConditionTree $referenceFilter, QueryParams $params, Query $query): void
	{
		$this->qpResponsibleFilter->apply($referenceFilter, $params->userParams(), 'ref.RESPONSIBLE_ID');

		if ($params->restrictedFrom() && !$params->userParams()->isOnlyOneUser())
		{
			$this->addRestrictedByDeadlineSection($query, $params);
		}
	}

	private function addRestrictedByDeadlineSection(Query $query, QueryParams $params)
	{
		$ct = (new ConditionTree())
			->where('ENTITY_TYPE_ID', new SqlExpression($params->entityTypeId()));

		if ($params->userParams()->isExcluded())
		{
			$ct->whereNotIn('RESPONSIBLE_ID', $params->userParams()->userIds());
		}
		else
		{
			$ct->whereIn('RESPONSIBLE_ID', $params->userParams()->userIds());
		}

		if (is_null($params->hasAnyIncomingChannel()))
		{
			$ct->whereIn('HAS_ANY_INCOMING_CHANEL', ['N', 'Y']);
		}
		else
		{
			$ct->where('HAS_ANY_INCOMING_CHANEL',
					new SqlExpression('?', $params->hasAnyIncomingChannel() ? 'Y' : 'N'));
		}

		$subQuery = EntityUncompletedActivityTable::query()
			->setSelect(['FAKE_ONE'])
			->registerRuntimeField('', new ExpressionField('FAKE_ONE', new SqlExpression('?i', 1)))
			->where($ct)
			->where('ENTITY_ID', new SqlExpression('?#.?#', $query->getInitAlias(), 'ID'));

		$subQuery->where('MIN_DEADLINE', '<', $params->restrictedFrom());


		$query->whereNotExists($subQuery);
	}
}