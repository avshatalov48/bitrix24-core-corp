<?php

namespace Bitrix\Crm\Security\Controller\QueryBuilder;

use Bitrix\Crm\Security\Controller\QueryBuilder\Conditions\RestrictedConditionsList;
use Bitrix\Main\ORM\Entity;

class QueryBuilderData
{
	public function __construct(
		private string $sql,
		private ?RestrictedConditionsList $conditions = null,
		private ?Entity $entity = null,
	)
	{
	}

	public function getSql(): string
	{
		return $this->sql;
	}

	public function getConditions(): ?RestrictedConditionsList
	{
		return $this->conditions;
	}

	public function getEntity(): ?Entity
	{
		return $this->entity;
	}
}