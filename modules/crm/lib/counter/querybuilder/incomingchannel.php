<?php

namespace Bitrix\Crm\Counter\QueryBuilder;

use Bitrix\Crm\Activity\Entity\IncomingChannelTable;
use Bitrix\Crm\ActivityBindingTable;
use Bitrix\Crm\Counter\QueryBuilder;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity\ReferenceField;

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

		$incomingChannelQuery = IncomingChannelTable::query();
		$this->applyResponsibleFilter($incomingChannelQuery, 'RESPONSIBLE_ID');
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
			$query->registerRuntimeField('', new ExpressionField('QTY', 'COUNT(DISTINCT %s)', 'ID'));
			$query->addSelect('QTY');
		}

		return $query;
	}

	protected function applyReferenceFilter(array &$referenceFilter): void
	{
		if (count($this->userIds) <= 1)
		{
			$referenceFilter['=ref.RESPONSIBLE_ID'] =new SqlExpression('?i', count($this->userIds) ? $this->userIds[0] : 0); // 0 means "All users"
		}
		else
		{
			$referenceFilter['@ref.RESPONSIBLE_ID'] =new SqlExpression(implode(',', $this->userIds));
		}
		$referenceFilter['=ref.IS_INCOMING_CHANNEL'] = new SqlExpression('?', 'Y');
	}
}
