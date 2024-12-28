<?php

namespace Bitrix\Tasks\Flow\Provider\Query;

use Bitrix\Main\Access\User\UserSubordinate;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Access\Model\UserModel;
use Bitrix\Tasks\Flow\Internal\FlowTable;
use Bitrix\Tasks\Integration;
use Bitrix\Tasks\Provider\QueryBuilderInterface;
use Bitrix\Tasks\Provider\TaskQueryInterface;

class FlowQueryBuilder implements QueryBuilderInterface
{
	private static string $lastQuery;

	/** @var FlowQuery $flowQuery  */
	private TaskQueryInterface $flowQuery;
	private Query $ormQuery;
	private UserModel $userModel;

	/**
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	private function __construct(TaskQueryInterface $flowQuery)
	{
		$this->flowQuery = $flowQuery;
		$this->ormQuery = FlowTable::query();
	}

	/**
	 * Fetch only primaries.
	 * @see FlowQueryEnrich
	 *
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
			->buildAccess()
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
		if ($this->flowQuery->getOnlyPrimaries())
		{
			return $this->buildPrimarySelect();
		}

		$this->ormQuery->setDistinct($this->flowQuery->getDistinct());
		$this->ormQuery->setSelect($this->flowQuery->getSelect());
		return $this;
	}

	protected function buildPrimarySelect(): static
	{
		$this->ormQuery->setDistinct($this->flowQuery->getDistinct());
		$this->ormQuery->setSelect(['ID']);
		return $this;
	}

	protected function buildWhere(): static
	{
		if ($this->flowQuery->getWhere() !== null)
		{
			$this->ormQuery->where($this->flowQuery->getWhere());
		}
		return $this;
	}

	protected function buildLimit(): static
	{
		$limit = $this->flowQuery->getLimit();
		if ($limit > 0)
		{
			$this->ormQuery->setLimit($limit);
		}

		return $this;
	}

	protected function buildOffset(): static
	{
		$offset = $this->flowQuery->getOffset();
		if ($offset > 0)
		{
			$this->ormQuery->setOffset($offset);
		}

		return $this;
	}

	/**
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	protected function buildOrder(): static
	{
		$order = $this->flowQuery->getOrderBy();
		if (!empty($order))
		{
			$this->ormQuery->setOrder($order);
		}

		return $this;
	}

	protected function buildGroup(): static
	{
		$group = $this->flowQuery->getGroupBy();
		if (!empty($group))
		{
			$this->ormQuery->setGroup($group);
		}

		return $this;
	}

	/**
	 * @throws ArgumentException
	 * @throws SystemException
	 */
	protected function buildAccess(): static
	{
		if (!$this->flowQuery->needAccessCheck())
		{
			return $this;
		}

		if ($this->getUserModel()->isAdmin())
		{
			return $this;
		}

		$this->ormQuery->where($this->getAccessCodeFiler());

		return $this;
	}

	protected function countTotal(): static
	{
		$this->ormQuery->countTotal($this->flowQuery->getCountTotal());
		return $this;
	}

	protected function getQuery(): Query
	{
		return $this->ormQuery;
	}

	/**
	 * @throws ArgumentException
	 * @throws SystemException
	 */
	protected function getAccessCodeFiler(): ConditionTree
	{
		$accessCodes = ['U' . $this->flowQuery->getUserId()];

		if (!$this->getUserModel()->isExtranet())
		{
			$accessCodes = array_merge($accessCodes, ['UA']);
		}

		$accessCodes = array_merge($accessCodes, $this->getUserDepartmentsFlat());
		$accessCodes = array_merge($accessCodes, $this->getUserDepartmentsRecursive());

		return Query::filter()
			->logic(ConditionTree::LOGIC_AND)
			->whereIn('MEMBERS.ACCESS_CODE', $accessCodes);
	}

	protected function getUserDepartmentsFlat(): array
	{
		return array_map(
			static fn (int $departmentId): string => 'D' . $departmentId,
			$this->getUserModel()->getUserDepartments()
		);
	}

	protected function getUserDepartmentsRecursive(): array
	{
		$departments = $this->getUserModel()->getUserDepartments();
		$allDepartments = [];
		foreach ($departments as $departmentId)
		{
			$allDepartments = array_merge(
				[$departmentId],
				$allDepartments,
				UserSubordinate::getParentDepartments($departmentId)
			);
		}

		$allDepartments = array_unique($allDepartments);

		return array_map(static fn (int $departmentId): string => 'DR' . $departmentId, $allDepartments);
	}

	protected function getUserModel(): UserModel
	{
		$this->userModel ??= UserModel::createFromId($this->flowQuery->getUserId());
		return $this->userModel;
	}
}