<?php

namespace Bitrix\Crm\Counter\CounterQueryBuilder\DeadlineBased;

use Bitrix\Crm\ActivityBindingTable;
use Bitrix\Crm\ActivityTable;
use Bitrix\Crm\Counter\CounterQueryBuilder\BuilderParams\QueryParams;
use Bitrix\Crm\Counter\CounterQueryBuilder\CounterQueryBuilder;
use Bitrix\Crm\Counter\CounterQueryBuilder\DeadlineBased\DateFilters\DateFilter;
use Bitrix\Crm\Counter\CounterQueryBuilder\QueryParts;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Factory;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\Base;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\ORM\Query\Query;

final class Compatible implements CounterQueryBuilder
{
	private DateFilter $dateFilter;

	public function __construct(DateFilter $dateFilter)
	{
		$this->dateFilter = $dateFilter;
	}

	public function build(Factory $factory, QueryParams $params): Query
	{
		$query = $factory->getDataClass()::query();

		$query->registerRuntimeField(
			'',
			new ReferenceField('B',
				ActivityBindingTable::getEntity(),
				[
					'=ref.OWNER_ID' => 'this.ID',
					'=ref.OWNER_TYPE_ID' => new SqlExpression($params->entityTypeId())
				],
				['join_type' => Join::TYPE_INNER]
			)
		);
		$this->joinActivityTable($params, $query);

		(new QueryParts\EntitySpecificFilter())->apply($query, $params->entityTypeId(), $params->options());

		$this->applyResponsibleFilter($query, $factory, $params);

		$this->applySelect($params, $query);

		return $query;
	}

	private function applyResponsibleFilter(Query $query, Factory $factory, QueryParams $params): void
	{
		$responsibleFieldName = $factory->getEntityFieldNameByMap(Item::FIELD_NAME_ASSIGNED);
		(new QueryParts\ResponsibleFilter)->apply($query, $params->userParams(), $responsibleFieldName);
	}

	private function applySelect(QueryParams $params, Query $query): void
	{
		if ($params->getSelectType() === self::SELECT_TYPE_ENTITIES)
		{
			$query->addSelect('B.OWNER_ID', 'ENTY');
			if ($params->useDistinct())
			{
				$query->addGroup('B.OWNER_ID');
			}
		}
		else
		{
			$query->registerRuntimeField('', QueryParts\SelectFields::getQuantityExpression($params->useDistinct()));
			$query->addSelect('QTY');
		}
	}

	public function joinActivityTable(QueryParams $params, Query $query): void
	{
		$activityQuery = ActivityTable::query();
		// Activity (inner join with correlated query for fix issue #109347)
		$this->applyDeadlineFilter($activityQuery, $params);

		$activityQuery->addFilter('=COMPLETED', 'N');
		$activityQuery->addSelect('ID');

		$query->registerRuntimeField(
			'',
			new ReferenceField('A',
				Base::getInstanceByQuery($activityQuery),
				['=ref.ID' => 'this.B.ACTIVITY_ID'],
				['join_type' => 'INNER']
			)
		);
	}

	private function applyDeadlineFilter(Query $query, QueryParams $params): void
	{
		$ct = new ConditionTree();
		$this->dateFilter->applyFilter($ct, $params);
		$query->where($ct);
	}


}