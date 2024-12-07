<?php

namespace Bitrix\Tasks\Flow\Provider\Query;

use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Tasks\Provider\TaskQueryInterface;

class FlowQuery implements TaskQueryInterface
{
	protected array $select = ['ID'];
	protected ?ConditionTree $where = null;
	protected int $limit = 0;
	protected int $offset = 0;
	protected array $groupBy = [];
	protected array $orderBy = [];
	protected bool $distinct = true;
	protected bool $countTotal = false;
	protected bool $accessCheck = true;
	protected bool $onlyPrimaries = false;

	protected int $userId;

	public function __construct(int $userId = 0)
	{
		$this->userId = $userId;

		$this->init();
	}

	public function setSelect(array $select): static
	{
		$this->select = $select;
		return $this;
	}

	public function setWhere(ConditionTree $where): static
	{
		if (isset($this->where))
		{
			$this->where->where($where); // :D
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

	public function setPageNavigation(PageNavigation $pageNavigation): static
	{
		return $this
			->setLimit($pageNavigation->getLimit())
			->setOffset($pageNavigation->getOffset());
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

	public function setCountTotal(bool $countTotal): static
	{
		$this->countTotal = $countTotal;

		return $this;
	}

	public function setDistinct(bool $distinct = true): static
	{
		$this->distinct = $distinct;
		return $this;
	}

	public function setOnlyPrimaries(bool $onlyPrimaries = true): static
	{
		$this->onlyPrimaries = $onlyPrimaries;
		return $this;
	}

	public function setAccessCheck(bool $check): static
	{
		$this->accessCheck = $check;
		return $this;
	}
	
	public function getId(): string
	{
		return 0;
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

	public function getCountTotal(): int
	{
		return $this->countTotal;
	}

	public function getUserId(): int
	{
		return $this->userId;
	}

	public function needAccessCheck(): bool
	{
		return $this->accessCheck;
	}

	public function getOnlyPrimaries(): bool
	{
		return $this->onlyPrimaries;
	}

	public function isOnlyPrimaries(): bool
	{
		return $this->select === ['ID'];
	}

	protected function init(): void
	{

	}
}
