<?php

namespace Bitrix\Crm\Security\QueryBuilder\Result;

use Bitrix\Main\ORM\Entity;
use Bitrix\Crm\Security\Controller\QueryBuilder\Conditions\RestrictedConditionsList;
use Bitrix\Main\ORM\Query\Query;


final class InConditionResult implements ResultOption
{
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
		$query = new Query($entity);
		$query->setCustomBaseTableAlias($prefix . 'P');
		$query->setSelect(['ENTITY_ID']);
		$query->where($conditions->makeOrmConditions());

		$identity = $this->getIdentityColumnName();

		return "$prefix.$identity IN ({$query->getQuery()})";
	}

	public function makeCompatible(string $querySql, string $prefix = ''): string
	{
		$identityCol = $this->getIdentityColumnName();

		return "{$prefix}.{$identityCol} IN ({$querySql})";
	}
}
