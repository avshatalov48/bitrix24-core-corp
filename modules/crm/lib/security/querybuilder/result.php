<?php

namespace Bitrix\Crm\Security\QueryBuilder;

use Bitrix\Main\DB\SqlExpression;

class Result
{
	private $hasAccess = false;
	private $sql = '';

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
}
