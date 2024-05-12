<?php

namespace Bitrix\Crm\Security\QueryBuilder\Result;

use Bitrix\Main\ORM\Entity;
use Bitrix\Crm\Security\Controller\QueryBuilder\Conditions\RestrictedConditionsList;
use Bitrix\Main\Application;
use Bitrix\Main\ORM\Query\Query;

final class RawQueryResult implements ResultOption
{
	public function __construct(
		private ?string $order = null,
		private ?int $limit = null,
		private bool $useDistinct = false,
		private string $identityColumnName = 'ID',
	)
	{
	}

	public function getIdentityColumnName(): string
	{
		return $this->identityColumnName;
	}

	public function getOrder(): ?string
	{
		return $this->order;
	}

	public function getLimit(): ?int
	{
		return $this->limit;
	}

	public function isUseDistinct(): bool
	{
		return $this->useDistinct;
	}

	public function make(Entity $entity, RestrictedConditionsList $conditions, string $prefix = ''): string
	{
		$query = new Query($entity);
		$query->setCustomBaseTableAlias($prefix . 'P');
		$query->setSelect(['ENTITY_ID']);
		$query->where($conditions->makeOrmConditions());

		if ($this->isUseDistinct())
		{
			$query->setDistinct();
		}

		if ($this->getLimit() > 0)
		{
			$order = $this->getOrder();
			$query->setOrder(['ENTITY_ID' => $order]);
			$query->setLimit($this->getLimit());
		}

		return $query->getQuery();
	}

	public function makeCompatible(string $querySql, string $prefix = ''): string
	{
		if ($this->getLimit() > 0)
		{
			$order = $this->getOrder();

			$querySql = Application::getConnection()->getSqlHelper()->getTopSql(
				"{$querySql} ORDER BY ENTITY_ID {$order}",
				$this->getLimit()
			);
		}

		return $querySql;
	}
}
