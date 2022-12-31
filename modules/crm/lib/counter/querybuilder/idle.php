<?php

namespace Bitrix\Crm\Counter\QueryBuilder;

use Bitrix\Crm\Counter\QueryBuilder;
use Bitrix\Crm\Pseudoactivity\Entity\WaitTable;
use Bitrix\Crm\UserActivityTable;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity\ReferenceField;

/**
 * Idle counter.
 * Counts entities without activities and without wait entities.
 */
class Idle extends QueryBuilder
{
	protected function getJoinType(): string
	{
		return \Bitrix\Main\ORM\Query\Join::TYPE_LEFT;
	}

	protected function useEntityCountableActivityTable(): bool
	{
		// EntityCountableTable can not be used for idle counter
		return false;
	}

	protected function applyCounterTypeFilter(\Bitrix\Main\ORM\Query\Query $query): void
	{
		$query->whereNull('B.ENTITY_ID');

		$query->registerRuntimeField(
			'',
			new ReferenceField('W',
				WaitTable::getEntity(),
				[
					'=ref.OWNER_ID' => 'this.ID',
					'=ref.OWNER_TYPE_ID' => new SqlExpression($this->entityTypeId),
					'=ref.COMPLETED' => new SqlExpression('?s', 'N')
				],
				['join_type' => 'LEFT']
			)
		);
		$query->whereNull('W.OWNER_ID');
	}

	protected function applyUncompletedActivityTableReferenceFilter(\Bitrix\Main\ORM\Query\Filter\ConditionTree $referenceFilter): void
	{
		$referenceFilter->where('ref.RESPONSIBLE_ID', new SqlExpression('?i', 0)); // 0 means "All users"
	}

	public function buildCompatible(\Bitrix\Main\ORM\Query\Query $query): \Bitrix\Main\ORM\Query\Query
	{
		if($this->getSelectType() === self::SELECT_TYPE_ENTITIES)
		{
			$query->addSelect('ID', 'ENTY');
		}
		else
		{
			$query->registerRuntimeField('', new ExpressionField('QTY', 'COUNT(%s)', 'ID'));
			$query->addSelect('QTY');
		}

		$query->registerRuntimeField(
			'',
			new ReferenceField('UA',
				UserActivityTable::getEntity(),
				[
					'=ref.OWNER_ID' => 'this.ID',
					'=ref.OWNER_TYPE_ID' => new SqlExpression($this->entityTypeId),
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
					'=ref.OWNER_TYPE_ID' => new SqlExpression($this->entityTypeId),
					'=ref.COMPLETED' => new SqlExpression('?s', 'N')
				],
				['join_type' => 'LEFT']
			)
		);
		$query->addFilter('==W.OWNER_ID', null);

		$this->applyResponsibleFilter($query, $this->getEntityAssignedColumnName());

		return $query;
	}
}