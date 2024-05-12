<?php

namespace Bitrix\Crm\Security\QueryBuilder;

use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\ORM\Entity;

class Result
{
	private bool $hasAccess = false;

	private string $sql = '';

	private ?ConditionTree $ormConditions = null;

	private ?Entity $entity = null;

	public function hasRestrictions(): bool
	{
		return (!$this->hasAccess() || $this->getSql() !== '');
	}

	public function hasAccess(): bool
	{
		return $this->hasAccess;
	}

	public function setHasAccess(bool $hasAccess): Result
	{
		$this->hasAccess = $hasAccess;

		return $this;
	}

	public function setOrmConditions(?ConditionTree $ormConditions): void
	{
		$this->ormConditions = $ormConditions;
	}

	public function setEntity(?Entity $entity): void
	{
		$this->entity = $entity;
	}

	public function getSql(): string
	{
		return $this->sql;
	}

	public function getSqlExpression(): SqlExpression
	{
		return new SqlExpression($this->getSql());
	}

	public function setSql(string $sql): Result
	{
		$this->sql = $sql;

		return $this;
	}

	public function getOrmConditions(): ?ConditionTree
	{
		return $this->ormConditions;
	}

	public function isOrmConditionSupport(): bool
	{
		return $this->ormConditions !== null;
	}

	public function getEntity(): ?Entity
	{
		return $this->entity;
	}
}
