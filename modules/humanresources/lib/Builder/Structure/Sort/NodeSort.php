<?php

declare(strict_types=1);

namespace Bitrix\HumanResources\Builder\Structure\Sort;

use Bitrix\HumanResources\Enum\SortDirection;

class NodeSort implements SortInterface
{
	private string $alias = '';

	private array $orderColumns = [];

	public function __construct(
		public readonly ?SortDirection $depth = null,
		public readonly ?SortDirection $sort = null,
		public readonly ?SortDirection $type = null,
	)
	{
	}

	/**
	 * @return array<string, string>
	 */
	public function prepareSort(): array
	{
		$this->addDepthSort();
		$this->addSort();
		$this->addTypeSort();

		return $this->orderColumns;
	}

	private function addDepthSort(): void
	{
		if ($this->depth === null)
		{
			return;
		}

		$this->orderColumns[$this->alias . 'CHILD_NODES.DEPTH'] = $this->depth->value;
	}

	private function addSort(): void
	{
		if ($this->sort === null)
		{
			return;
		}

		$this->orderColumns[$this->alias . 'SORT'] = $this->sort->value;
	}

	private function addTypeSort(): void
	{
		if ($this->type === null)
		{
			return;
		}

		$this->orderColumns[$this->alias . 'TYPE'] = $this->type->value;
	}

	public function setCurrentAlias(string $alias): static
	{
		$this->alias = $alias . '.';

		return $this;
	}
}