<?php

namespace Bitrix\Crm\Counter\CounterQueryBuilder\IncomingChannel;

use Bitrix\Crm\Activity\Entity\IncomingChannelTable;
use Bitrix\Crm\ActivityBindingTable;
use Bitrix\Crm\Counter\CounterQueryBuilder\CounterQueryBuilder;
use Bitrix\Crm\Counter\CounterQueryBuilder\BuilderParams\QueryParams;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Factory;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\Base;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Crm\Counter\CounterQueryBuilder\QueryParts;

final class Compatible implements CounterQueryBuilder
{
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

		$this->applyResponsibleFilter($factory, $query, $params);

		$incomingChannelQuery = IncomingChannelTable::query();
		$incomingChannelQuery->where('COMPLETED', false);
		$incomingChannelQuery->addSelect('ACTIVITY_ID');

		$query->registerRuntimeField(
			'',
			new ReferenceField('A',
				Base::getInstanceByQuery($incomingChannelQuery),
				[
					'=ref.ACTIVITY_ID' => 'this.B.ACTIVITY_ID'
				],
				['join_type' => Join::TYPE_INNER]
			)
		);

		$this->applySelect($params, $query);

		(new QueryParts\EntitySpecificFilter())->apply($query, $params->entityTypeId(), $params->options());

		return $query;
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
			$query->registerRuntimeField('', $this->getQuantityExpression($params));
			$query->addSelect('QTY');
		}
	}

	private function getQuantityExpression(QueryParams $params): ExpressionField
	{
		if ($params->useDistinct())
		{
			return new ExpressionField('QTY', 'COUNT(DISTINCT %s)', 'ID');
		}
		return new ExpressionField('QTY', 'COUNT(%s)', 'ID');
	}

	private function applyResponsibleFilter(Factory $factory, Query $query, QueryParams $params): void
	{
		$responsibleFieldName = $factory->getEntityFieldNameByMap(Item::FIELD_NAME_ASSIGNED);
		(new QueryParts\ResponsibleFilter)->apply($query, $params, $responsibleFieldName);
	}
}