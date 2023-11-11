<?php

namespace Bitrix\CrmMobile\Kanban\ItemPreparer\Counters;

use Bitrix\CrmMobile\Kanban\ItemCounter;

final class CountersResult
{
	/**
	 * @param ItemCounter[] $counters
	 * @param int $activityCounterTotal
	 * @param array|null $indicator data from \Bitrix\CrmMobile\Kanban\ItemIndicator::getIndicators
	 */
	public function __construct(
		private array $counters,
		private int $activityCounterTotal,
		private ?array $indicator
	) {

	}

	public function getCounters(): array
	{
		return $this->counters;
	}

	public function getActivityCounterTotal(): int
	{
		return $this->activityCounterTotal;
	}

	public function getIndicator(): ?array
	{
		return $this->indicator;
	}

}