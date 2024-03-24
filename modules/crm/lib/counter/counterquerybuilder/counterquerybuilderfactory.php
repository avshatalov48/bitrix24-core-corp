<?php

namespace Bitrix\Crm\Counter\CounterQueryBuilder;

use Bitrix\Crm\Counter\CounterQueryBuilder\DeadlineBased\DateFilters\CurrentCompatible;
use Bitrix\Crm\Counter\CounterQueryBuilder\DeadlineBased\DateFilters\CurrentCountable;
use Bitrix\Crm\Counter\CounterQueryBuilder\DeadlineBased\DateFilters\CurrentLightTimeCountable;
use Bitrix\Crm\Counter\CounterQueryBuilder\DeadlineBased\DateFilters\CurrentLightTimeUncompleted;
use Bitrix\Crm\Counter\CounterQueryBuilder\DeadlineBased\DateFilters\CurrentUncompleted;
use Bitrix\Crm\Counter\CounterQueryBuilder\DeadlineBased\DateFilters\OverdueCompatible;
use Bitrix\Crm\Counter\CounterQueryBuilder\DeadlineBased\DateFilters\OverdueCountable;
use Bitrix\Crm\Counter\CounterQueryBuilder\DeadlineBased\DateFilters\OverdueUncompleted;
use Bitrix\Crm\Counter\CounterQueryBuilder\DeadlineBased\DateFilters\PendingCompatible;
use Bitrix\Crm\Counter\CounterQueryBuilder\DeadlineBased\DateFilters\PendingCountable;
use Bitrix\Crm\Counter\CounterQueryBuilder\DeadlineBased\DateFilters\PendingUncompleted;
use Bitrix\Crm\Counter\CounterQueryBuilder\DeadlineBased\DateFilters\ReadyTodoCompatible;
use Bitrix\Crm\Counter\CounterQueryBuilder\DeadlineBased\DateFilters\ReadyTodoCountable;
use Bitrix\Crm\Counter\CounterQueryBuilder\DeadlineBased\DateFilters\ReadyTodoLightTime;
use Bitrix\Crm\Counter\EntityCounterType;
use Bitrix\Main;

final class CounterQueryBuilderFactory
{

	public function make(
		int $counterTypeId,
		FactoryConfig $config
	): CounterQueryBuilder
	{
		switch ($counterTypeId)
		{
			case EntityCounterType::CURRENT:
				return $this->makeCurrent($config);

			case EntityCounterType::READY_TODO:
				return $this->makeReadyTodo($config);

			case EntityCounterType::IDLE:
				return $this->makeIdle($config);

			case EntityCounterType::PENDING:
				return $this->makePending($config);

			case EntityCounterType::OVERDUE:
				return $this->makeOverdue($config);

			case EntityCounterType::INCOMING_CHANNEL:
				return $this->makeIncomingChannel($config);

			default:
				$typeName = EntityCounterType::resolveName($counterTypeId);
				throw new Main\NotSupportedException("The '$typeName' is not supported in current context");
		}
	}

	private function makeIncomingChannel(FactoryConfig $config): CounterQueryBuilder
	{
		if ($config->mustUseUncompleted() && $config->onlyMinIncomingChannel())
		{
			return new DeadlineBased\UncompletedBased(new PendingUncompleted());
		}

		// in this way have to use `countable` way as it possible and ignore `uncountable` flag
		if ($config->readyCountable())
		{
			return new IncomingChannel\CountableBased();
		}

		return new IncomingChannel\Compatible();
	}

	private function makePending(FactoryConfig $config): CounterQueryBuilder
	{
		if ($config->isCompatibleWay())
		{
			return new DeadlineBased\Compatible(new PendingCompatible());
		}

		if ($config->isUncompletedActivityWay())
		{
			return new DeadlineBased\UncompletedBased(new PendingUncompleted());
		}

		return new DeadlineBased\CountableBased(new PendingCountable());
	}

	private function makeOverdue(FactoryConfig $config): CounterQueryBuilder
	{
		if ($config->isCompatibleWay())
		{
			return new DeadlineBased\Compatible(new OverdueCompatible());
		}

		if ($config->isUncompletedActivityWay())
		{
			return new DeadlineBased\UncompletedBased(new OverdueUncompleted());
		}

		return new DeadlineBased\CountableBased(new OverdueCountable());
	}

	private function makeReadyTodo(FactoryConfig $config): CounterQueryBuilder
	{
		if (!$config->readyCountable() || !$config->readyUncompleted())
		{
			return new DeadlineBased\Compatible(new ReadyTodoCompatible());
		}

		if (!$config->readyActCounterLight())
		{
			return new DeadlineBased\CountableBased(new ReadyTodoCountable());
		}

		return new DeadlineBased\CountableBased(new ReadyTodoLightTime());
	}

	private function makeIdle(FactoryConfig $config): CounterQueryBuilder
	{
		if (!$config->readyUncompleted())
		{
			return new Idle\Compatible();
		}

		return new Idle\UncompletedBased();
	}

	private function makeCurrent(FactoryConfig $config): CounterQueryBuilder
	{
		if (!$config->readyUncompleted())
		{
			return new DeadlineBased\Compatible(new CurrentCompatible());
		}

		if (!$config->readyCountable())
		{
			if ($config->mustUseUncompleted())
			{
				return new DeadlineBased\UncompletedBased(new CurrentUncompleted());
			}

			return new DeadlineBased\Compatible(new CurrentCompatible());
		}

		if (!$config->readyActCounterLight())
		{
			if ($config->mustUseUncompleted())
			{
				return new DeadlineBased\UncompletedBased(new CurrentUncompleted());
			}

			return new DeadlineBased\CountableBased(new CurrentCountable());
		}

		if ($config->mustUseUncompleted())
		{
			return new DeadlineBased\UncompletedBased(new CurrentLightTimeUncompleted());
		}

		return new DeadlineBased\CountableBased(new CurrentLightTimeCountable());
	}
}
