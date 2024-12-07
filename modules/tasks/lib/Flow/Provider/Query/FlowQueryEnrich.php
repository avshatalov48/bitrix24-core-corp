<?php

namespace Bitrix\Tasks\Flow\Provider\Query;

use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Query\Filter\Condition;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Flow\Internal\FlowTable;
use Bitrix\Tasks\Provider\QueryBuilderInterface;
use Bitrix\Tasks\Provider\TaskQueryInterface;

class FlowQueryEnrich implements QueryBuilderInterface
{
	private static string $lastQuery;

	/** @var FlowQuery $flowQuery  */
	private TaskQueryInterface $flowQuery;
	private Query $ormQuery;
	private Condition $primaryCondition;

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
	 * @throws ArgumentException
	 * @throws SystemException
	 */
	public static function build(TaskQueryInterface $query): Query
	{
		$ormQuery = (new static($query))
			->buildSelect()
			->buildPrimaryCondition()
			->buildPrimaryFilter()
			->buildOrderByField()
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
		$this->ormQuery->setDistinct($this->flowQuery->getDistinct());
		$this->ormQuery->setSelect($this->flowQuery->getSelect());
		return $this;
	}

	protected function buildPrimaryFilter(): static
	{
		$this->ormQuery->where($this->primaryCondition);
		return $this;
	}

	/**
	 * @throws SystemException
	 */
	protected function buildOrderByField(): static
	{
		$primaries = $this->primaryCondition->getAtomicValues();
		$helper = Application::getConnection()->getSqlHelper();
		$field = new ExpressionField(
			'ID_SEQUENCE',
			$helper->getOrderByIntField('%s', $primaries, false),
			array_fill(0, count($primaries), 'ID')
		);

		$this->ormQuery
			->registerRuntimeField($field)
			->setOrder($field->getName());

		return $this;
	}

	protected function getQuery(): Query
	{
		return $this->ormQuery;
	}

	/**
	 * @throws ArgumentException
	 */
	protected function buildPrimaryCondition(): static
	{
		$primaryCondition = null;

		foreach ($this->flowQuery->getWhere()->getConditions() as $condition)
		{
			if ($condition->getColumn() === 'ID' && $condition->getOperator() === 'in')
			{
				$primaryCondition = $condition;
				break;
			}
		}

		if (null === $primaryCondition)
		{
			throw new ArgumentException('Cannot enrich empty data');
		}

		$this->primaryCondition = $primaryCondition;

		return $this;
	}
}