<?php

declare(strict_types=1);

namespace Bitrix\Booking\Provider\Params;

use Bitrix\Main\ORM\Query\Filter\ConditionTree;

class GridParams
{
	public function __construct(
		public int|null $limit = null,
		public int $offset = 0,
		public FilterInterface|null $filter = null,
		public SortInterface|null $sort = null,
		public SelectInterface|null $select = null,
	)
	{
	}

	public function getFilter(): ConditionTree|null
	{
		return $this->filter?->prepareFilter();
	}

	public function getSort(): array|null
	{
		return $this->sort?->prepareSort();
	}

	public function getSelect(): array|null
	{
		return $this->select?->prepareSelect();
	}
}
