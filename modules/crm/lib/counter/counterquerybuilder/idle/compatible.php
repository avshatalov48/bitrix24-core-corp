<?php

namespace Bitrix\Crm\Counter\CounterQueryBuilder\Idle;

use Bitrix\Crm\Counter\CounterQueryBuilder\BuilderParams\QueryParams;
use Bitrix\Crm\Counter\CounterQueryBuilder\CounterQueryBuilder;
use Bitrix\Crm\Counter\CounterQueryBuilder\QueryParts;
use Bitrix\Crm\Counter\CounterQueryBuilder\QueryParts\ResponsibleFilter;
use Bitrix\Crm\Item;
use Bitrix\Crm\Pseudoactivity\Entity\WaitTable;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\UserActivityTable;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ORM\Query\Query;

final class Compatible implements CounterQueryBuilder
{
	public function build(Factory $factory, QueryParams $params): Query
	{
		$query = $factory->getDataClass()::query();

		(new QueryParts\EntitySpecificFilter())->apply($query, $params->entityTypeId(), $params->options());

		$this->applySelect($params, $query);

		$query->registerRuntimeField(
			'',
			new ReferenceField('UA',
				UserActivityTable::getEntity(),
				[
					'=ref.OWNER_ID' => 'this.ID',
					'=ref.OWNER_TYPE_ID' => new SqlExpression($params->entityTypeId()),
					'=ref.USER_ID' => new SqlExpression(0)
				],
				['join_type' => 'LEFT']
			)
		);
		$query->addFilter('==UA.OWNER_ID', null);

		$query->registerRuntimeField(
			'',
			new ReferenceField('W',
				WaitTable::getEntity(),
				[
					'=ref.OWNER_ID' => 'this.ID',
					'=ref.OWNER_TYPE_ID' => new SqlExpression($params->entityTypeId()),
					'=ref.COMPLETED' => new SqlExpression('?s', 'N')
				],
				['join_type' => 'LEFT']
			)
		);
		$query->addFilter('==W.OWNER_ID', null);

		$this->applyResponsibleFilter($factory, $query, $params);

		return $query;
	}

	public function applySelect(QueryParams $params, Query $query): void
	{
		if ($params->getSelectType() === CounterQueryBuilder::SELECT_TYPE_ENTITIES)
		{
			$query->addSelect('ID', 'ENTY');
		}
		else
		{
			$query->registerRuntimeField('', new ExpressionField('QTY', 'COUNT(%s)', 'ID'));
			$query->addSelect('QTY');
		}
	}

	public function applyResponsibleFilter(Factory $factory, Query $query, QueryParams $params): void
	{
		$responsibleFieldName = $factory->getEntityFieldNameByMap(Item::FIELD_NAME_ASSIGNED);
		(new ResponsibleFilter())->apply($query, $params->userParams(), $responsibleFieldName);
	}
}