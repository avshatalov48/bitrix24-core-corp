<?php

namespace Bitrix\Tasks\Provider\Log;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Internals\Task\LogTable;
use Bitrix\Tasks\Provider\QueryBuilderInterface;
use Bitrix\Tasks\Provider\TaskQueryInterface;

class TaskLogQueryBuilder implements QueryBuilderInterface
{
	private static string $lastQuery;

	/** @var TaskLogQuery */
	private TaskQueryInterface $taskQuery;
	private Query $query;

	/**
	 * @throws ArgumentException
	 * @throws SystemException
	 */
	private function __construct(TaskQueryInterface $taskQuery)
	{
		$this->taskQuery = $taskQuery;
		$this->query = LogTable::query();
	}

	/**
	 * @throws ArgumentException
	 * @throws SystemException
	 */
	public static function build(TaskQueryInterface $query): Query
	{
		$ormQuery = (new static($query))
			->buildSelect()
			->buildWhere()
			->buildLimit()
			->buildOffset()
			->buildOrder()
			->buildGroup()
			->countTotal()
			->getQuery();

		static::$lastQuery = $ormQuery->getQuery();

		return $ormQuery;
	}

	public static function getLastQuery(): string
	{
		return static::$lastQuery;
	}

	protected function buildSelect(): static
	{
		$this->query->setDistinct($this->taskQuery->getDistinct());
		$this->query->setSelect($this->taskQuery->getSelect());

		return $this;
	}

	protected function buildWhere(): static
	{
		if ($this->taskQuery->getWhere() !== null)
		{
			$this->query->where($this->taskQuery->getWhere());
		}

		return $this;
	}

	protected function buildLimit(): static
	{
		$limit = $this->taskQuery->getLimit();
		if ($limit > 0)
		{
			$this->query->setLimit($limit);
		}

		return $this;
	}

	protected function buildOffset(): static
	{
		$offset = $this->taskQuery->getOffset();
		if ($offset > 0)
		{
			$this->query->setOffset($offset);
		}

		return $this;
	}

	/**
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	protected function buildOrder(): static
	{
		$order = $this->taskQuery->getOrderBy();
		if (!empty($order))
		{
			$this->query->setOrder($order);
		}

		return $this;
	}

	protected function buildGroup(): static
	{
		$group = $this->taskQuery->getGroupBy();
		if (!empty($group))
		{
			$this->query->setGroup($group);
		}

		return $this;
	}

	protected function countTotal(): static
	{
		$this->query->countTotal($this->taskQuery->getCountTotal());

		return $this;
	}

	protected function getQuery(): Query
	{
		return $this->query;
	}
}
