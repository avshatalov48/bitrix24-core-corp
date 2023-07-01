<?php

namespace Bitrix\Crm\Counter;

use Bitrix\Crm\Settings\Crm;

class EntityCounterSettings
{
	protected $isCountersEnabled = false;
	protected $enabledCountersTypes = [];
	protected $enabledInFilterCountersTypes = [];

	public static function createDefault(bool $isStagesSupported): self
	{
		$isCountersEnabled = \Bitrix\Crm\Settings\CounterSettings::getInstance()->isEnabled();
		$isIdleCounterEnabled = \CCrmUserCounterSettings::GetValue(
			\CCrmUserCounterSettings::ReckonActivitylessItems,
			true
		);

		if (Crm::isUniversalActivityScenarioEnabled())
		{
			$enabledCounters = [
				\Bitrix\Crm\Counter\EntityCounterType::INCOMING_CHANNEL,
				\Bitrix\Crm\Counter\EntityCounterType::CURRENT,
				\Bitrix\Crm\Counter\EntityCounterType::ALL,
			];
			$enabledInFilterCounters = $enabledCounters;
			if ($isStagesSupported)
			{
				$enabledInFilterCounters[] = \Bitrix\Crm\Counter\EntityCounterType::IDLE;
			}
		}
		else
		{
			$enabledCounters = [];
			if ($isStagesSupported && $isIdleCounterEnabled)
			{
				$enabledCounters[] = \Bitrix\Crm\Counter\EntityCounterType::IDLE;
			}
			$enabledCounters[] = \Bitrix\Crm\Counter\EntityCounterType::PENDING;
			$enabledCounters[] = \Bitrix\Crm\Counter\EntityCounterType::OVERDUE;
			$enabledCounters[] = \Bitrix\Crm\Counter\EntityCounterType::CURRENT;
			$enabledCounters[] = \Bitrix\Crm\Counter\EntityCounterType::READY_TODO;
			$enabledCounters[] = \Bitrix\Crm\Counter\EntityCounterType::ALL;
			$enabledInFilterCounters = $enabledCounters;
		}

		return
			(new EntityCounterSettings())
				->setIsCountersEnabled($isCountersEnabled)
				->setEnabledCountersTypes($enabledCounters)
				->setEnabledInFilterCountersTypes($enabledInFilterCounters)
		;
	}

	public function isCountersEnabled(): bool
	{
		return $this->isCountersEnabled;
	}

	public function isCounterTypeEnabled(int $counterType): bool
	{
		return $this->checkIfCounterTypeEnabled($counterType, $this->enabledCountersTypes);
	}

	public function isCounterTypeEnabledInFilter(int $counterType): bool
	{
		return $this->checkIfCounterTypeEnabled($counterType, $this->enabledInFilterCountersTypes);
	}

	public function checkIfCounterTypeEnabled(int $counterType, array $enabledCounterTypes): bool
	{
		$enabled = in_array($counterType, $enabledCounterTypes, true);
		if ($enabled)
		{
			return true;
		}
		if (in_array(
			$counterType,
			[
				\Bitrix\Crm\Counter\EntityCounterType::OVERDUE,
				\Bitrix\Crm\Counter\EntityCounterType::PENDING,
				\Bitrix\Crm\Counter\EntityCounterType::READY_TODO,
			]
		))
		{
			return $this->checkIfCounterTypeEnabled(\Bitrix\Crm\Counter\EntityCounterType::CURRENT, $enabledCounterTypes);
		}

		return false;
	}

	public function isIdleCounterEnabled(): bool
	{
		return $this->isCounterTypeEnabled(\Bitrix\Crm\Counter\EntityCounterType::IDLE);
	}

	public function isOverdueCounterEnabled(): bool
	{
		return $this->isCounterTypeEnabled(\Bitrix\Crm\Counter\EntityCounterType::OVERDUE);
	}

	public function isPendingCounterEnabled(): bool
	{
		return $this->isCounterTypeEnabled(\Bitrix\Crm\Counter\EntityCounterType::PENDING);
	}

	public function isCurrentCounterEnabled(): bool
	{
		return $this->isCounterTypeEnabled(\Bitrix\Crm\Counter\EntityCounterType::CURRENT);
	}

	public function isIncomingCounterEnabled(): bool
	{
		return $this->isCounterTypeEnabled(\Bitrix\Crm\Counter\EntityCounterType::INCOMING_CHANNEL);
	}

	public function isIdleCounterEnabledInFilter(): bool
	{
		return $this->isCounterTypeEnabledInFilter(\Bitrix\Crm\Counter\EntityCounterType::IDLE);
	}

	public function isOverdueCounterEnabledInFilter(): bool
	{
		return $this->isCounterTypeEnabledInFilter(\Bitrix\Crm\Counter\EntityCounterType::OVERDUE);
	}

	public function isPendingCounterEnabledInFilter(): bool
	{
		return $this->isCounterTypeEnabledInFilter(\Bitrix\Crm\Counter\EntityCounterType::PENDING);
	}

	public function isCurrentCounterEnabledInFilter(): bool
	{
		return $this->isCounterTypeEnabledInFilter(\Bitrix\Crm\Counter\EntityCounterType::CURRENT);
	}

	public function isIncomingCounterEnabledInFilter(): bool
	{
		return $this->isCounterTypeEnabledInFilter(\Bitrix\Crm\Counter\EntityCounterType::INCOMING_CHANNEL);
	}

	public function isReadyToDoCounterEnabledInFilter(): bool
	{
		return $this->isCounterTypeEnabledInFilter(\Bitrix\Crm\Counter\EntityCounterType::READY_TODO);
	}

	public function setIsCountersEnabled(bool $isCountersEnabled): self
	{
		$this->isCountersEnabled = $isCountersEnabled;

		return $this;
	}

	public function getEnabledCountersTypes(): array
	{
		return $this->enabledCountersTypes;
	}

	public function setEnabledCountersTypes(array $enabledCountersTypes): self
	{
		$this->enabledCountersTypes = $enabledCountersTypes;

		return $this;
	}

	public function getEnabledInFilterCountersTypes(): array
	{
		return $this->enabledInFilterCountersTypes;
	}

	public function setEnabledInFilterCountersTypes(array $enabledInFilterCountersTypes): self
	{
		$this->enabledInFilterCountersTypes = $enabledInFilterCountersTypes;

		return $this;
	}

	public function getComponentsOfAllCounter(): array
	{
		$result = [];
		$currentTypeIdIsEnabled = in_array(
			\Bitrix\Crm\Counter\EntityCounterType::CURRENT,
			$this->getEnabledCountersTypes()
		);
		foreach ($this->getEnabledCountersTypes() as $typeId)
		{
			if ($typeId === \Bitrix\Crm\Counter\EntityCounterType::ALL)
			{
				continue;
			}
			if (
				$typeId === \Bitrix\Crm\Counter\EntityCounterType::OVERDUE
				&& $currentTypeIdIsEnabled
			)
			{
				continue;
			}
			if (
				$typeId === \Bitrix\Crm\Counter\EntityCounterType::PENDING
				&& $currentTypeIdIsEnabled
			)
			{
				continue;
			}

			$result[] = $typeId;
		}

		return $result;
	}
}
