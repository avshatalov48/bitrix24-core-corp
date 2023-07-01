<?php

namespace Bitrix\Crm\Counter\CounterQueryBuilder\DeadlineBased;

use Bitrix\Crm\Activity\Entity\EntityUncompletedActivityTable;
use Bitrix\Crm\Counter\CounterQueryBuilder\CounterQueryBuilder;
use Bitrix\Crm\Counter\CounterQueryBuilder\DeadlineBased\DateFilters\DateFilter;
use Bitrix\Crm\Counter\CounterQueryBuilder\BuilderParams\QueryParams;
use Bitrix\Crm\Service\Factory;
use Bitrix\Main\Application;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Crm\Counter\CounterQueryBuilder\QueryParts;

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

		$this->qpResponsibleFilter->applyByItemFactory($query, $params, $factory);

		$this->qpSelectFields->applyForUncompleted($query, $params);

		return $query;
	}

	private function joinUncompletedTableWithConditions(QueryParams $params, Query $query): void
	{
		$referenceFilter = (new ConditionTree())
			->whereColumn('ref.ENTITY_ID', 'this.ID')
			->where('ref.ENTITY_TYPE_ID', new SqlExpression($params->entityTypeId()));

		$referenceFilter->where('ref.RESPONSIBLE_ID', new SqlExpression('?i', 0));

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
}