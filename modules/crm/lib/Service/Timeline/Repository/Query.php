<?php

namespace Bitrix\Crm\Service\Timeline\Repository;

use Bitrix\Main\Type\DateTime;

class Query
{
	protected int $offsetId = 0;
	protected ?DateTime $offsetTime = null;
	protected int $limit = 10;
	protected bool $searchForFixedItems = false;
	protected array $filter = [];

	public function getOffsetId(): int
	{
		return $this->offsetId;
	}

	public function setOffsetId(int $offsetId): self
	{
		$this->offsetId = $offsetId;

		return $this;
	}

	public function getOffsetTime(): ?DateTime
	{
		return $this->offsetTime;
	}

	public function setOffsetTime(?DateTime $offsetTime): self
	{
		$this->offsetTime = $offsetTime;

		return $this;
	}

	public function getLimit(): int
	{
		return $this->limit;
	}

	public function setLimit(int $limit): self
	{
		$this->limit = $limit;

		return $this;
	}

	public function getFilter(): array
	{
		return $this->filter;
	}

	public function setFilter(array $filter): self
	{
		$this->filter = $filter;

		return $this;
	}

	public function isSearchForFixedItems(): bool
	{
		return $this->searchForFixedItems;
	}

	public function setSearchForFixedItems(bool $searchForFixed): self
	{
		$this->searchForFixedItems = $searchForFixed;

		return $this;
	}
}
