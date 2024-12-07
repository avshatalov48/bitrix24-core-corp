<?php

namespace Bitrix\Tasks\Flow\Efficiency;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Flow\Internal\FlowTaskTable;
use Bitrix\Tasks\Flow\Provider\Exception\ProviderException;
use Bitrix\Tasks\Flow\Provider\FlowProvider;
use Bitrix\Tasks\Internals\Effective;
use Bitrix\Tasks\Internals\Log\Logger;
use Bitrix\Tasks\Util\Type\DateTime;
use InvalidArgumentException;

class Efficiency extends Effective
{
	protected const DEFAULT = 100;
	protected const MAX = 500;

	protected array $flowIds;
	protected array $totals;
	protected array $violations;
	protected array $efficiencies;
	protected bool $isCacheDisabled = false;

	protected Range $range;
	protected Cache $cache;

	public static function isEnabled(): bool
	{
		return Option::get('tasks', 'tasks_flow_efficiency_enabled', 'Y') === 'Y';
	}

	public function __construct(Range $range)
	{
		$this->range = $range;
		$this->init();
	}

	public function get(int $flowId): int
	{
		if (!static::isEnabled())
		{
			return static::DEFAULT;
		}

		if ($flowId <= 0)
		{
			return static::DEFAULT;
		}

		if ($this->isCacheDisabled === false)
		{
			$cached = $this->cache->get($flowId, $this->range);

			if ($cached !== null)
			{
				return $cached;
			}
		}

		try
		{
			$this->load($flowId);
		}
		catch (SystemException $e)
		{
			Logger::logThrowable($e);
			return static::DEFAULT;
		}

		return $this->efficiencies[$flowId] ?? static::DEFAULT;
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 * @throws InvalidArgumentException
	 */
	public function load(int ...$flowIds): void
	{
		if (empty($flowIds))
		{
			return;
		}

		$this->flowIds = $flowIds;

		$this->countEfficiencies();

		if ($this->isCacheDisabled === false)
		{
			foreach ($this->efficiencies as $flowId => $efficiency)
			{
				$this->cache->store($flowId, $this->range, $efficiency);
			}
		}
	}

	public function invalidate(int $flowId): void
	{
		if ($flowId <= 0)
		{
			return;
		}

		$this->cache->invalidate($flowId, $this->range);
		// update efficiency with the current value
		$flowProvider = new FlowProvider();
		try
		{
			$flow = $flowProvider->getFlow($flowId);
		}
		catch (ProviderException)
		{
			return;
		}

		$flowProvider->getEfficiency($flow);
	}

	public function disableCache(bool $disable = true): static
	{
		if (Option::get('tasks', 'tasks_flow_efficiency_cache_enabled', 'Y') === 'N')
		{
			$this->isCacheDisabled = true;
		}
		else
		{
			$this->isCacheDisabled = $disable;
		}

		return $this;
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	protected function countTotals(): void
	{
		$query = static::getInProgressQuery(
			DateTime::createFromInstance($this->range->from()),
			DateTime::createFromInstance($this->range->to())
		);

		$flowReference = (new ReferenceField(
			'TMP_FLOW',
			FlowTaskTable::getEntity(),
			Join::on('this.ID', 'ref.TASK_ID')
		))->configureJoinType(Join::TYPE_LEFT);

		$totals = $query
			->setSelect([])
			->addSelect(Query::expr()->countDistinct('ID'), 'CNT')
			->addSelect('TMP_FLOW.FLOW_ID', 'FLOW_ID')
			->whereIn('TMP_FLOW.FLOW_ID', $this->flowIds)
			->registerRuntimeField($flowReference)
			->addGroup('TMP_FLOW.FLOW_ID')
			->fetchAll();

		$this->totals = array_fill_keys($this->flowIds, 0);

		$combo = array_combine(
			array_column($totals, 'FLOW_ID'),
			array_map('intval', array_column($totals, 'CNT'))
		);

		foreach ($this->flowIds as $flowId)
		{
			if (isset($combo[$flowId]))
			{
				$this->totals[$flowId] = $combo[$flowId];
			}
		}
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	protected function countViolations(): void
	{
		$flowReference = (new ReferenceField(
			'TMP_FLOW',
			FlowTaskTable::getEntity(),
			Join::on('this.TASK_ID', 'ref.TASK_ID')
		))->configureJoinType(Join::TYPE_LEFT);

		$query = static::getViolationsQuery(
			DateTime::createFromInstance($this->range->from()),
			DateTime::createFromInstance($this->range->to())
		);

		$violations = $query
			->setSelect([])
			->addSelect(Query::expr()->countDistinct('TASK_ID'), 'CNT')
			->addSelect('TMP_FLOW.FLOW_ID', 'FLOW_ID')
			->whereIn('TMP_FLOW.FLOW_ID', $this->flowIds)
			->whereColumn('T.CREATED_BY', '<>', 'T.RESPONSIBLE_ID')
			->registerRuntimeField($flowReference)
			->addGroup('TMP_FLOW.FLOW_ID')
			->fetchAll();

		$this->violations = array_fill_keys($this->flowIds, 0);

		$combo = array_combine(
			array_column($violations, 'FLOW_ID'),
			array_map('intval', array_column($violations, 'CNT'))

		);

		foreach ($this->flowIds as $flowId)
		{
			if (isset($combo[$flowId]))
			{
				$this->violations[$flowId] = $combo[$flowId];
			}
		}
	}

	/**
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	protected function countEfficiencies(): void
	{
		$this->countTotals();
		$this->countViolations();

		foreach ($this->totals as $flowId => $total)
		{
			if ($total === 0)
			{
				$value = static::DEFAULT;
			}
			else
			{
				$value = (int)round(100 * (1 - $this->violations[$flowId] / $total));
			}

			if ($value < 0)
			{
				$value = 0;
			}

			$this->efficiencies[$flowId] = $value;
		}
	}

	protected function init(): void
	{
		$this->cache = new Cache();

		$this->totals = [];
		$this->violations = [];
		$this->efficiencies = [];

		if (Option::get('tasks', 'tasks_flow_efficiency_cache_enabled', 'Y') === 'N')
		{
			$this->isCacheDisabled = true;
		}
	}
}
