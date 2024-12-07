<?php

namespace Bitrix\Crm\Security\QueryBuilder\Result;

use Bitrix\Crm\Observer\Entity\ObserverTable;
use Bitrix\Crm\Security\QueryBuilder\Result\Traits\UnionUtils;
use Bitrix\Main\ORM\Entity;
use Bitrix\Crm\Security\Controller\QueryBuilder\Conditions\RestrictedConditionsList;
use Bitrix\Main\ORM\Query\Query;

/**
 * Version of JoinResult optimized for using union. Applies when it is explicitly specified to use this method
 * of obtaining the result using the `PERMISSION_BUILDER_OPTION_OBSERVER_JOIN_AS_UNION` parameter.
 * @link http://jabber.bx/view.php?id=181240
 */
final class JoinWithUnionResult implements ResultOption
{
	use UnionUtils;

	public function __construct(
		private string $identityColumnName = 'ID',
	)
	{
	}

	public function getIdentityColumnName(): string
	{
		return $this->identityColumnName;
	}

	public function make(Entity $entity, RestrictedConditionsList $conditions, string $prefix = ''): string
	{
		[$otherConditions, $observerCondition] = $this->separateConditions($conditions->getConditions());

		if (empty($otherConditions) || empty($observerCondition))
		{
			return (new JoinResult())->make($entity, $conditions, $prefix);
		}

		$otherQuery = new Query($entity);
		$otherQuery->setCustomBaseTableAlias($prefix . 'P');
		$otherQuery->setSelect(['ENTITY_ID']);
		$otherQuery->where($this->makeOrmConditions($otherConditions));

		$obsQuery = ObserverTable::query()
			->setSelect(['ENTITY_ID'])
			->where('ENTITY_TYPE_ID', $observerCondition->getEntityTypeID())
			->where('USER_ID', $observerCondition->getUserId());

		$union = ($obsQuery->union($otherQuery))->getQuery();

		$identity = $this->getIdentityColumnName();
		return "INNER JOIN ($union) PERM ON PERM.ENTITY_ID = $prefix.$identity";
	}

	public function makeCompatible(string $querySql, string $prefix = ''): string
	{
		$join = new JoinResult($this->identityColumnName);
		return $join->makeCompatible($querySql, $prefix);
	}
}
