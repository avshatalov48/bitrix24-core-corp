<?php

namespace Bitrix\Tasks\Provider\Tag;

use Bitrix\Tasks\Provider\Tag\Builders\TagSelectBuilder;
use Bitrix\Tasks\Provider\TaskQueryInterface;

class TagQuery implements TaskQueryInterface
{
	private array $select = [];
	private array $where = [];
	private array $orderBy = [];
	private array $additionalSelect = [];
	private int $limit = 0;
	private int $offset = 0;
	private string $id;

	public function __construct()
	{
		$this->generateId();
	}

	public function getId(): string
	{
		return $this->id;
	}

	public function getSelect(): array
	{
		return empty($this->select) ? TagSelectBuilder::getDefaultSelect() : $this->select;
	}

	public function getWhere(): array
	{
		return $this->where;
	}

	public function getOrderBy(): array
	{
		return $this->orderBy;
	}

	public function getLimit(): int
	{
		return $this->limit;
	}

	public function getOffset(): int
	{
		return $this->offset;
	}

	public function setSelect(array $select): static
	{
		$this->select = $select;
		return $this;
	}

	public function addWhere(string $field, string $alias): static
	{
		$this->additionalSelect[$field] = $alias;
		return $this;
	}

	public function setWhere(array $where): static
	{
		$this->where = $where;

		return $this;
	}

	public function setOrderBy(array $orderBy): static
	{
		$this->orderBy = $orderBy;
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

	public function getDistinct(): bool
	{
		return false;
	}

	public function getGroupBy(): array
	{
		return [];
	}

	public function needAccessCheck(): bool
	{
		return false;
	}

	public function getCountTotal(): int
	{
		return 0;
	}

	public function getUserId(): int
	{
		return 0;
	}

	private function generateId(): void
	{
		$this->id = sha1(microtime(true) + random_int(100000, 999999));
	}
}