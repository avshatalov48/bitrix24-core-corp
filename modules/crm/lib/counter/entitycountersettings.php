<?php

namespace Bitrix\Crm\Counter;

class EntityCounterSettings
{
	protected $isCountersEnabled = false;
	protected $enabledCountersTypes = [];

	public static function createDefault(bool $isStagesSupported): self
	{
		$isCountersEnabled = \Bitrix\Crm\Settings\CounterSettings::getCurrent()->isEnabled();
		$isIdleCounterEnabled = \CCrmUserCounterSettings::GetValue(
			\CCrmUserCounterSettings::ReckonActivitylessItems,
			true
		);

		$enabledCounters = [
		//	\Bitrix\Crm\Counter\EntityCounterType::INCOMING_CHANNEL, // temporary disabled
			\Bitrix\Crm\Counter\EntityCounterType::PENDING,
			\Bitrix\Crm\Counter\EntityCounterType::OVERDUE,
			\Bitrix\Crm\Counter\EntityCounterType::CURRENT,
			\Bitrix\Crm\Counter\EntityCounterType::ALL,
		];

		if ($isStagesSupported && $isIdleCounterEnabled)
		{
			$enabledCounters[] = \Bitrix\Crm\Counter\EntityCounterType::IDLE;
		}

		return
			(new EntityCounterSettings())
				->setIsCountersEnabled($isCountersEnabled)
				->setEnabledCountersTypes( $enabledCounters)
		;
	}

	public function isCountersEnabled(): bool
	{
		return $this->isCountersEnabled;
	}

	public function isCounterTypeEnabled(int $counterType): bool
	{
		$enabled = in_array($counterType, $this->enabledCountersTypes, true);
		if ($enabled)
		{
			return true;
		}
		if (in_array(
			$counterType,
			[
				\Bitrix\Crm\Counter\EntityCounterType::OVERDUE,
				\Bitrix\Crm\Counter\EntityCounterType::PENDING,
			]
		))
		{
			return $this->isCounterTypeEnabled(\Bitrix\Crm\Counter\EntityCounterType::CURRENT);
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
}
