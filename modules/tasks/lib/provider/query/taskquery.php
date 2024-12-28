<?php

namespace Bitrix\Tasks\Provider\Query;

use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Access\TaskAccessController;
use Bitrix\Tasks\Provider\Exception\InvalidGroupByException;
use Bitrix\Tasks\Provider\Exception\InvalidOrderException;
use Bitrix\Tasks\Provider\Exception\InvalidSelectException;
use CTasks;

class TaskQuery
	implements TaskQueryInterface
{
	private string $id;
	private int $userId;
	private int $behalfUser = 0;
	private bool $skipAccessCheck = false;
	private bool $skipUfEscape = false;
	private bool $skipTitleEscape = false;
	private bool $makeAccessFilter = false;
	private bool $distinct = true;
	private array $params = [];

	private array $select = [];
	private array $order = [];
	private array $groupBy = [];
	private array $where = [];
	private int $limit = 0;
	private int $offset = 0;

	public function __construct(int $userId = 0)
	{
		$this->userId = $userId;
		$this->generateId();
	}

	/**
	 * @return $this
	 */
	public function skipUfEscape(): self
	{
		$this->skipUfEscape = true;
		return $this;
	}

	/**
	 * @return $this
	 */
	public function skipTitleEscape(): self
	{
		$this->skipTitleEscape = true;
		return $this;
	}

	/**
	 * @return bool
	 */
	public function needUfEscape(): bool
	{
		return !$this->skipUfEscape;
	}

	/**
	 * @return bool
	 */
	public function needTitleEscape(): bool
	{
		return !$this->skipTitleEscape;
	}

	/**
	 * @return bool
	 */
	public function needSeparated(): bool
	{
		return false;
	}

	public function needMakeAccessFilter(): self
	{
		$this->makeAccessFilter = true;
		return $this;
	}

	/**
	 * @return bool
	 */
	public function needAccessCheck(): bool
	{
		if ($this->skipAccessCheck)
		{
			return false;
		}

		if (TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_ADMIN))
		{
			return false;
		}

		if ($this->userId !== $this->behalfUser)
		{
			return true;
		}

		if ($this->makeAccessFilter)
		{
			$runtimeOptions = CTasks::makeAccessFilterRuntimeOptions($this->where, [
				'USER_ID' => $this->userId,
				'VIEWED_USER_ID' => $this->behalfUser,
			]);

			if (!CTasks::checkAccessSqlBuilding($runtimeOptions))
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * @return $this
	 */
	public function skipAccessCheck(): self
	{
		$this->skipAccessCheck = true;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getUserId(): int
	{
		return $this->userId;
	}

	/**
	 * @param int $userId
	 * @return $this
	 */
	public function setBehalfUser(int $userId): self
	{
		$this->behalfUser = $userId;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getBehalfUser(): int
	{
		if (!$this->behalfUser)
		{
			return $this->userId;
		}
		return $this->behalfUser;
	}

	/**
	 * @param array|string $select
	 * @return $this
	 *
	 * alias => column
	 * or
	 * column only
	 */
	public function addSelect($select): self
	{
		$select = $this->prepareSelect($select);
		$this->select = array_merge($this->select, $select);
		return $this;
	}

	/**
	 * @param array|string $select
	 * @return $this
	 */
	public function setSelect($select = []): self
	{
		$this->select = $this->prepareSelect($select);
		return $this;
	}

	/**
	 * @return array
	 */
	public function getSelect(): array
	{
		return $this->select;
	}

	/**
	 * @param $filter
	 * @return $this
	 */
	public function andWhere($filter): self
	{
		$this->where = array_merge($this->where, $filter);
		return $this;
	}

	public function addWhere(string $key, $value): self
	{
		$this->where = array_merge($this->where, [$key => $value]);
		return $this;
	}

	/**
	 * @param $filter
	 * @return $this
	 */
	public function setWhere($filter): self
	{
		$this->where = $filter;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getWhere(): array
	{
		return $this->where;
	}

	/**
	 * @param int $limit
	 * @return $this
	 */
	public function setLimit(int $limit): self
	{
		$this->limit = $limit;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getLimit(): int
	{
		return $this->limit;
	}

	/**
	 * @param int $offset
	 * @return $this
	 */
	public function setOffset(int $offset): self
	{
		$this->offset = $offset;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getOffset(): int
	{
		return $this->offset;
	}

	/**
	 * @param string|array $order
	 * @return $this
	 *
	 * Column or list of columns
	 * [
	 *    column => sort,
	 *    column
	 * ]
	 */
	public function setOrder($order): self
	{
		$this->order = $this->prepareOrder($order);
		return $this;
	}

	/**
	 * @return array
	 */
	public function getOrderBy(): array
	{
		return $this->order;
	}

	/**
	 * @param $groupBy
	 * @return $this
	 * @throws InvalidGroupByException
	 */
	public function setGroupBy($groupBy): self
	{
		$this->groupBy = $this->prepareGroupBy($groupBy);
		return $this;
	}

	public function setDistinct(bool $distinct): self
	{
		$this->distinct = $distinct;
		return $this;
	}

	public function getDistinct(): bool
	{
		return $this->distinct;
	}

	/**
	 * @param $groupBy
	 * @return $this
	 * @throws InvalidGroupByException
	 */
	public function addGroupBy($groupBy): self
	{
		$groupBy = $this->prepareGroupBy($groupBy);
		$this->groupBy = array_merge($this->groupBy, $groupBy);
		return $this;
	}

	/**
	 * @return array
	 */
	public function getGroupBy(): array
	{
		return $this->groupBy;
	}

	/**
	 * @return string
	 */
	public function getId(): string
	{
		return $this->id;
	}

	public function getCountTotal(): int
	{
		return 0;
	}

	/**
	 * @param string $key
	 * @param $value
	 * @return $this
	 */
	public function setParam(string $key, $value): self
	{
		$this->params[$key] = $value;
		return $this;
	}

	/**
	 * @param string $key
	 * @return mixed|null
	 */
	public function getParam(string $key)
	{
		if (!array_key_exists($key, $this->params))
		{
			return null;
		}

		return $this->params[$key];
	}

	/**
	 * @param $order
	 * @return array
	 * @throws InvalidOrderException
	 */
	private function prepareOrder($order): array
	{
		if (is_null($order))
		{
			return [];
		}
		if (
			is_string($order)
		)
		{
			$result = [
				$order => self::SORT_ASC,
			];

			return $result;
		}

		if (!is_array($order))
		{
			throw new InvalidOrderException();
		}

		$result = [];
		foreach ($order as $column => $sort)
		{
			if (is_integer($column))
			{
				$result[$sort] = self::SORT_ASC;
				continue;
			}

			if (is_array($sort))
			{
				$result = array_merge($result, $this->prepareOrder($sort));
				continue;
			}

			$sort = strtoupper($sort);
			if ($sort === 'ASC,NULLS')
			{
				$sort = self::SORT_ASC;
			}
			if ($sort === 'DESC,NULLS')
			{
				$sort = self::SORT_DESC;
			}
			if (!in_array($sort, [self::SORT_ASC, self::SORT_DESC]))
			{
				$sort = self::SORT_DESC;
			}

			$result[$column] = $sort;
		}

		return $result;
	}

	/**
	 * @param $groupBy
	 * @return string[]
	 * @throws InvalidGroupByException
	 */
	private function prepareGroupBy($groupBy): array
	{
		if (is_string($groupBy))
		{
			$groupBy = [$groupBy];
		}

		if (!is_array($groupBy))
		{
			throw new InvalidGroupByException();
		}

		foreach ($groupBy as $column)
		{
			if (!is_string($column))
			{
				throw new InvalidGroupByException();
			}
		}

		return $groupBy;
	}

	/**
	 * @param array|int $select
	 * @return array
	 * @throws InvalidSelectException
	 */
	private function prepareSelect($select): array
	{
		if (is_scalar($select))
		{
			$select = [$select];
		}
		if (!is_array($select))
		{
			throw new InvalidSelectException();
		}

		foreach ($select as $row)
		{
			if (!is_string($row) && !$row instanceof ExpressionField)
			{
				throw new InvalidSelectException();
			}
		}

		return $select;
	}

	/**
	 * @return void
	 */
	private function generateId(): void
	{
		$this->id = sha1(microtime(true) + mt_rand(100000, 999999));
	}
}
