<?php

namespace Bitrix\Tasks\Provider\Tag;

use Bitrix\Main\ORM\Query\Query;
use Bitrix\Tasks\Internals\Task\LabelTable;
use Bitrix\Tasks\Provider\QueryBuilderInterface;
use Bitrix\Tasks\Provider\Tag\Builders\TagFilterBuilder;
use Bitrix\Tasks\Provider\Tag\Builders\TagOrderBuilder;
use Bitrix\Tasks\Provider\Tag\Builders\TagSelectBuilder;
use Bitrix\Tasks\Provider\TaskQueryInterface;

class TagQueryBuilder implements QueryBuilderInterface
{
	private Query $query;
	private TagFilterBuilder $filterBuilder;
	private TagSelectBuilder $selectBuilder;
	private TagOrderBuilder $orderBuilder;

	public static string $lastBuiltSql = '';

	public function __construct(private TaskQueryInterface $tagQuery)
	{
		$this->query = LabelTable::query();
		$this->initBuilders();
	}

	public static function build(TaskQueryInterface $tagQuery): Query
	{
		$builder = new static($tagQuery);
		$builder
			->buildOrder()
			->buildSelect()
			->buildWhere()
			->buildLimit()
			->buildOffset()
		;

		$query = $builder->getQuery();
		self::$lastBuiltSql = $query->getQuery();

		return $query;
	}

	private function buildSelect(): static
	{
		$select = $this->selectBuilder->buildSelect($this->tagQuery->getSelect());
		foreach ($select as $field => $alias)
		{
			$this->query->addSelect(is_string($field) ? $field : $alias, is_string($field) ? $alias : '');
		}

		return $this;
	}

	private function buildWhere(): static
	{
		$this->query->where($this->filterBuilder->buildFilter($this->tagQuery->getWhere()));
		return $this;
	}

	private function buildOrder(): static
	{
		$this->query->setOrder($this->orderBuilder->buildOrder($this->tagQuery->getOrderBy()));
		return $this;
	}

	private function buildLimit(): static
	{
		$this->query->setLimit($this->tagQuery->getLimit());
		return $this;
	}

	private function buildOffset(): static
	{
		$this->query->setOffset($this->tagQuery->getOffset());
		return $this;
	}

	private function initBuilders(): void
	{
		$this->selectBuilder = new TagSelectBuilder();
		$this->filterBuilder = new TagFilterBuilder();
		$this->orderBuilder = new TagOrderBuilder();
	}

	private function getQuery(): Query
	{
		return $this->query;
	}
}