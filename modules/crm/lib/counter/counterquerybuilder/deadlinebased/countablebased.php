<?php

namespace Bitrix\Crm\Counter\CounterQueryBuilder\DeadlineBased;


use Bitrix\Crm\Counter\CounterQueryBuilder\CounterQueryBuilder;
use Bitrix\Crm\Counter\CounterQueryBuilder\DeadlineBased\DateFilters\DateFilter;
use Bitrix\Crm\Counter\EntityCountableActivityTable;
use Bitrix\Crm\Counter\CounterQueryBuilder\BuilderParams\QueryParams;
use Bitrix\Crm\Service\Factory;
use Bitrix\Main\Application;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Crm\Counter\CounterQueryBuilder\QueryParts;

final class CountableBased implements CounterQueryBuilder
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

		$this->joinCountableActivity($params, $query);

		$this->qpResponsibleFilter->apply($query, $params, 'A.ENTITY_ASSIGNED_BY_ID');

		$this->qpEntitySpecificFilter->apply($query, $params->entityTypeId(), $params->options());

		$query = $this->qpSelectFields->applyForCountable($query, $params);

		return $query;
	}

	private function joinCountableActivity(QueryParams $params, Query $query): void
	{
		$referenceFilter = (new ConditionTree())
			->whereColumn('ref.ENTITY_ID', 'this.ID')
			->where('ref.ENTITY_TYPE_ID', new SqlExpression($params->entityTypeId()));

		$referenceFilter->where('ref.ACTIVITY_IS_INCOMING_CHANNEL', new SqlExpression('?', 'N'));

		$this->applyDeadlineReferenceFilter($referenceFilter, $params);

		$query->registerRuntimeField(
			'',
			new ReferenceField('A',
				EntityCountableActivityTable::getEntity(),
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
