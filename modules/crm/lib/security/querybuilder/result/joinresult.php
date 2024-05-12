<?php

namespace Bitrix\Crm\Security\QueryBuilder\Result;

use Bitrix\Main\ORM\Entity;
use Bitrix\Crm\Security\Controller\QueryBuilder\Conditions\RestrictedConditionsList;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\ORM\Query\Query;

final class JoinResult implements ResultOption
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

		$dataSourceTable = $query->getEntity()->getDBTableName();

		$rawSql =  $query->getQuery();
		$wherePos = mb_strpos($rawSql, 'WHERE');
		if ($wherePos === false)
		{
			throw new ArgumentOutOfRangeException("WHERE condition required");
		}

		$rawSql = mb_substr($rawSql, $wherePos+6, mb_strlen($rawSql));

		$identity = $this->getIdentityColumnName();

		return "INNER JOIN $dataSourceTable {$prefix}P ON $prefix.$identity = {$prefix}P.ENTITY_ID AND ($rawSql)";
	}

	public function makeCompatible(string $querySql, string $prefix = ''): string
	{
		$identityCol = $this->getIdentityColumnName();

		return "INNER JOIN ({$querySql}) {$prefix}GP ON {$prefix}.{$identityCol} = {$prefix}GP.ENTITY_ID";
	}
}
