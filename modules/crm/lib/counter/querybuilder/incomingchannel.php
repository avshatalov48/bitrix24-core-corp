<?php

namespace Bitrix\Crm\Counter\QueryBuilder;

use Bitrix\Crm\Activity\Entity\IncomingChannelTable;
use Bitrix\Crm\ActivityBindingTable;
use Bitrix\Crm\Counter\QueryBuilder;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;

class IncomingChannel extends QueryBuilder
{
	protected function buildCompatible(\Bitrix\Main\ORM\Query\Query $query): \Bitrix\Main\ORM\Query\Query
	{
		$query->registerRuntimeField(
			'',
			new ReferenceField('B',
				ActivityBindingTable::getEntity(),
				[
					'=ref.OWNER_ID' => 'this.ID',
					'=ref.OWNER_TYPE_ID' => new SqlExpression($this->entityTypeId)
				],
				['join_type' => 'INNER']
			)
		);
		$this->applyResponsibleFilter($query, $this->getEntityAssignedColumnName());

		$incomingChannelQuery = IncomingChannelTable::query();
		$incomingChannelQuery->where('COMPLETED', false);
		$incomingChannelQuery->addSelect('ACTIVITY_ID');

		$query->registerRuntimeField(
			'',
			new ReferenceField('A',
				\Bitrix\Main\Entity\Base::getInstanceByQuery($incomingChannelQuery),
				[
					'=ref.ACTIVITY_ID' => 'this.B.ACTIVITY_ID'
				],
				['join_type' => 'INNER']
			)
		);

		if($this->getSelectType() === self::SELECT_TYPE_ENTITIES)
		{
			$query->addSelect('B.OWNER_ID', 'ENTY');
			if($this->isUseDistinct())
			{
				$query->addGroup('B.OWNER_ID');
			}
		}
		else
		{
			$query->registerRuntimeField('', $this->getQuantityExpression());
			$query->addSelect('QTY');
		}

		return $query;
	}

	protected function applyUncompletedActivityTableReferenceFilter(ConditionTree $referenceFilter): void
	{
		$referenceFilter
			->where('ref.RESPONSIBLE_ID', new SqlExpression('?i', 0))
			->where('ref.IS_INCOMING_CHANNEL', new SqlExpression('?', 'Y'))
		;
	}

	protected function applyEntityCountableActivityTableReferenceFilter(ConditionTree $referenceFilter): void
	{
		$referenceFilter
			->where('ref.ACTIVITY_IS_INCOMING_CHANNEL', new SqlExpression('?', 'Y'))
		;
	}
}
