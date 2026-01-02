<?php

declare(strict_types=1);

namespace Bitrix\HumanResources\Builder\Structure;

use Bitrix\HumanResources\Builder\Structure\Filter\NodeFilter;
use Bitrix\HumanResources\Contract\Builder\Filter;
use Bitrix\HumanResources\Exception\NodeAccessFilterException;
use Bitrix\HumanResources\Internals\Repository\Mapper\NodeMapper;
use Bitrix\HumanResources\Item\Collection\NodeCollection;
use Bitrix\HumanResources\Item\Node;
use Bitrix\HumanResources\Model\NodeTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\SystemException;

/**
 * @extends BaseDataBuilder<Node, NodeCollection>
 */
final class NodeDataBuilder extends BaseDataBuilder
{
	private readonly NodeMapper $mapper;
	protected int $cacheTtl = 86400;

	protected array $select = [
		'ID',
		'TYPE',
		'PARENT_ID',
		'STRUCTURE_ID',
		'ACTIVE',
		'GLOBAL_ACTIVE',
		'NAME',
		'DESCRIPTION',
		'ACCESS_CODE',
		'COLOR_NAME',
	];


	public function __construct()
	{
		parent::__construct();

		$this->mapper = new NodeMapper();
	}

	public static function createWithFilter(
		NodeFilter $filter,
	): static
	{
		return (new self())->addFilter($filter);
	}

	/**
	 * @return Query
	 * @throws NodeAccessFilterException
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function prepareQuery(): Query
	{
		$query = NodeTable::query();

		if (!empty($this->select))
		{
			$query->setSelect($this->select);
		}

		if ($this->limit > 0)
		{
			$query->setLimit($this->limit);
		}

		if ($this->offset > 0)
		{
			$query->setOffset($this->offset);
		}

		if ($this->cacheTtl >= 0)
		{
			$query->setCacheTtl($this->cacheTtl);
		}

		if ($this->sort !== null)
		{
			$query->setOrder($this->sort->prepareSort());
		}

		$conditionTree = new ConditionTree();
		$conditionTree->logic($this->logic);

		if (!empty($this->filters))
		{
			foreach ($this->filters as $filter)
			{
				if ($filter instanceof NodeFilter)
				{
					if ($filter->direction !== null)
					{
						$query->addSelect('CHILD_NODES');
					}
				}

				$conditionTree->addCondition($filter->prepareFilter());
			}
		}

		$query->where($conditionTree);

		return $query;
	}

	protected function getData(): NodeCollection
	{
		try
		{
			return $this->getList(
				$this->prepareQuery(),
			);
		}
		catch (NodeAccessFilterException)
		{
			return new NodeCollection();
		}
	}

	protected function validate(Filter $filter): bool
	{
		return $filter instanceof NodeFilter;
	}

	protected function getList(Query $query): NodeCollection
	{
		$result = $query->fetchAll();
		$nodeCollection = new NodeCollection();

		foreach ($result as $node)
		{
			$nodeCollection->add($this->mapper->convertFromOrmArray($node));
		}

		return $nodeCollection;
	}
}
