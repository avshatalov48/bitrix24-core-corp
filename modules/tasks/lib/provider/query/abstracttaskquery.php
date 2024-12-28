<?php

namespace Bitrix\Tasks\Provider\Query;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Tasks\Provider\QueryInterface;

abstract class AbstractTaskQuery implements QueryInterface
{
	protected array $select = ['ID'];
	protected ?ConditionTree $where = null;
	protected int $limit = 0;
	protected int $offset = 0;
	protected array $groupBy = [];
	protected array $orderBy = [];
	protected bool $distinct = true;

	public function setSelect(array $select): static
	{
		$this->select = $select;

		return $this;
	}

	/**
	 * @throws ArgumentException
	 */
	public function setWhere(ConditionTree $where): static
	{
		if (isset($this->where))
		{
			$this->where->where($where);
		}
		else
		{
			$this->where = $where;
		}

		return $this;
	}

	public function setLimit(int $limit): static
	{
		$this->limit = $limit;

		return $this;
	}

	public function setOffset(int $offset): static
	{
		$this->offset = $offset;

		return $this;
	}

	public function setGroupBy(array $groupBy): static
	{
		$this->groupBy = $groupBy;

		return $this;
	}

	public function setOrderBy(array $orderBy): static
	{
		$this->orderBy = $orderBy;

		return $this;
	}

	public function setDistinct(bool $distinct = true): static
	{
		$this->distinct = $distinct;

		return $this;
	}

	public function getWhere(): ?ConditionTree
	{
		return $this->where;
	}

	public function getOrderBy(): array
	{
		return $this->orderBy;
	}

	public function getSelect(): array
	{
		return $this->select;
	}

	public function getLimit(): int
	{
		return $this->limit;
	}

	public function getOffset(): int
	{
		return $this->offset;
	}

	public function getGroupBy(): array
	{
		return $this->groupBy;
	}

	public function getDistinct(): bool
	{
		return $this->distinct;
	}
}
