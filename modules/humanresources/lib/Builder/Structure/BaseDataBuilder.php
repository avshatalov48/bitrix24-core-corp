<?php

declare(strict_types=1);

namespace Bitrix\HumanResources\Builder\Structure;

use Bitrix\HumanResources\Builder\Structure\Sort\SortInterface;
use Bitrix\HumanResources\Contract\Builder\Filter;

use Bitrix\HumanResources\Contract\Builder;
use Bitrix\HumanResources\Contract\Item;
use Bitrix\HumanResources\Contract\ItemCollection;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\Validation\ValidationService;

/**
 * @template ItemT of Item
 * @template CollectionT of ItemCollection<ItemT>
 */
abstract class BaseDataBuilder implements Builder
{
	/**
	 * @var Builder\Filter[]
	 */
	protected array $filters = [];

	protected string $logic = ConditionTree::LOGIC_AND;
	protected ?SortInterface $sort = null;

	protected int $limit = 0;
	protected int $offset = 0;
	protected int $cacheTtl = 0;

	protected array $select = [];

	protected ValidationService $validationService;

	public function __construct()
	{
		$this->validationService = ServiceLocator::getInstance()->get('main.validation.service');
	}

	public function setLimit(int $limit): static
	{
		$this->limit = $limit;

		return $this;
	}

	public function getLimit(): int
	{
		return $this->limit;
	}

	public function getOffset(): int
	{
		return $this->offset;
	}

	public function setOffset(int $offset): static
	{
		$this->offset = $offset;

		return $this;
	}

	/**
	 * @param Filter $filter
	 *
	 * @return $this
	 */
	public function setFilter(Builder\Filter $filter): static
	{
		if (!$this->validate($filter))
		{
			throw new \InvalidArgumentException();
		}

		$this->filters = [$filter];

		return $this;
	}

	/**
	 * @param Filter $filter
	 *
	 * @return $this
	 * @throws \InvalidArgumentException
	 */
	public function addFilter(Builder\Filter $filter): static
	{
		if (!$this->validate($filter))
		{
			throw new \InvalidArgumentException();
		}

		$this->filters[] = $filter;

		return $this;
	}

	/**
	 * @return Filter[]
	 */
	public function getFilters(): array
	{
		return $this->filters;
	}

	/**
	 * @return CollectionT
	 */
	public function getAll(): ItemCollection
	{
		return $this->getData();
	}

	/**
	 * @return ?ItemT
	 */
	public function get(): ?Item
	{
		if ($this->limit === 0)
		{
			$this->setLimit(1);
		}

		return $this->getData()->getFirst();
	}

	public function getCacheTtl(): int
	{
		return $this->cacheTtl;
	}

	public function setCacheTtl(int $cacheTtl): static
	{
		$this->cacheTtl = $cacheTtl;

		return $this;
	}

	public function getSelect(): array
	{
		return $this->select;
	}

	public function setSelect(array $select): static
	{
		$this->select = $select;

		return $this;
	}

	/**
	 * @return SortInterface|null
	 */
	public function getSort(): ?SortInterface
	{
		return $this->sort;
	}

	/**
	 * @param SortInterface|null $sort
	 *
	 * @return BaseDataBuilder
	 */
	public function setSort(?SortInterface $sort): static
	{
		$this->sort = $sort;

		return $this;
	}

	/**
	 * @return CollectionT
	 */
	abstract protected function getData(): ItemCollection;
	abstract protected function validate(Builder\Filter $filter): bool;
}