<?php

namespace Bitrix\Crm\Counter\CounterQueryBuilder\IncomingChannel;

use Bitrix\Crm\Counter\CounterQueryBuilder\BuilderParams\QueryParams;
use Bitrix\Crm\Counter\CounterQueryBuilder\CounterQueryBuilder;
use Bitrix\Crm\Counter\CounterQueryBuilder\QueryParts;
use Bitrix\Crm\Counter\EntityCountableActivityTable;
use Bitrix\Crm\Service\Factory;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\ORM\Query\Query;


final class CountableBased implements CounterQueryBuilder
{

	private QueryParts\ResponsibleFilter $responsibleFilter;

	public function __construct()
	{
		$this->responsibleFilter = new QueryParts\ResponsibleFilter();
	}

	public function build(Factory $factory, QueryParams $params): Query
	{
		$query = $factory->getDataClass()::query();

		$referenceFilter = (new ConditionTree())
			->whereColumn('ref.ENTITY_ID', 'this.ID')
			->where('ref.ENTITY_TYPE_ID', new SqlExpression($params->entityTypeId()));

		$referenceFilter
			->where('ref.ACTIVITY_IS_INCOMING_CHANNEL', new SqlExpression('?', 'Y'));

		$query->registerRuntimeField(
			'',
			new ReferenceField('A',
				EntityCountableActivityTable::getEntity(),
				$referenceFilter,
				['join_type' => Join::TYPE_INNER]
			)
		);

		if ($params->useActivityResponsible())
		{
			$this->responsibleFilter->apply($referenceFilter, $params->userParams(), 'ref.ACTIVITY_RESPONSIBLE_ID');
		}
		else
		{
			$this->responsibleFilter->apply($query, $params->userParams(), 'A.ENTITY_ASSIGNED_BY_ID');
		}

		(new QueryParts\EntitySpecificFilter())->apply($query, $params->entityTypeId(), $params->options());

		$query = (new QueryParts\SelectFields)->applyForCountable($query, $params);

		return $query;
	}

}