<?php

declare(strict_types=1);

namespace Bitrix\HumanResources\Builder\Structure\Filter\Column;

use Bitrix\HumanResources\Builder\Structure\Filter\BaseFilter;
use Bitrix\HumanResources\Contract\Type\Collection;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;

abstract class BaseColumnFilter extends BaseFilter
{
	public function prepareFilter(): ConditionTree
	{
		$conditionTree = new ConditionTree();

		if (empty($this->getItems()))
		{
			return $conditionTree;
		}

		$conditionTree->whereIn(
			$this->getFieldByQueryContext($this->getFieldName()),
			$this->getItems(),
		);

		return $conditionTree;
	}

	/**
	 * @param Collection $collection
	 *
	 * @return static
	 * @throws ArgumentException
	 */
	public static function fromCollection(Collection $collection): static
	{
		$items = $collection->getItems();

		if (empty($items))
		{
			throw new ArgumentException('Collection is empty');
		}

		return new static(...$items);
	}

	abstract protected function getFieldName(): string;
	abstract protected function getItems(): array;
}