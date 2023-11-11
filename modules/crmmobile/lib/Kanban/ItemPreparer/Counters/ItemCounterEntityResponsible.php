<?php

namespace Bitrix\CrmMobile\Kanban\ItemPreparer\Counters;

use Bitrix\CrmMobile\Kanban\ItemCounter;
use Bitrix\CrmMobile\Kanban\ItemIndicator;

class ItemCounterEntityResponsible implements ItemCounters
{
	private ItemCounter $itemCounter;

	private ItemIndicator $itemIndicator;

	public function __construct()
	{
		$this->itemCounter = ItemCounter::getInstance();
		$this->itemIndicator = ItemIndicator::getInstance();
	}

	public function counters(array $item, array $params, ?int $entityAssignedById): CountersResult
	{
		$counters = [];

		$activityCounterTotal = ($item['activityCounterTotal'] ?? 0);
		$isCurrentUserAssigned = ((int)$params['userId'] === $entityAssignedById);

		$indicator = $this->getIndicator($activityCounterTotal, $item, $params['userId']);


		if (!$isCurrentUserAssigned)
		{
			$counters[] = $this->itemCounter->getEmptyCounter($activityCounterTotal);
			return new CountersResult($counters, $activityCounterTotal, $indicator);
		}

		$isReckonActivityLessItems = $params['isReckonActivityLessItems'];
		$activityError = (int)($item['activityError'] ?? 0);
		$activityIncomingTotal = (int)($item['activityIncomingTotal'] ?? 0);

		if ($activityError)
		{
			$counters[] = $this->itemCounter->getErrorCounter($activityError);
		}

		if ($activityIncomingTotal)
		{
			$counters[] = $this->itemCounter->getIncomingCounter($activityIncomingTotal);
		}

		if (empty($counters))
		{
			$counters[] = $isReckonActivityLessItems
				? $this->itemCounter->getErrorCounter(ItemCounters::DEFAULT_COUNT_WITH_RECKON_ACTIVITY)
				: $this->itemCounter->getEmptyCounter(0);
		}

		return new CountersResult($counters, $activityCounterTotal, $indicator);
	}

	/**
	 * @return string[]|null
	 */
	public function getIndicator($activityCounterTotal, array $item, int $userId): ?array
	{
		$indicator = null;
		if (!$activityCounterTotal && !empty($item['activityProgress']))
		{

			$activityProgressForCurrentUser = 0;
			if (isset($item['activitiesByUser'][$userId]))
			{
				$activityProgressForCurrentUser = ($item['activitiesByUser'][$userId]['activityProgress'] ?? 0);
			}

			$indicator = (
			$activityProgressForCurrentUser
				? $this->itemIndicator->getOwnIndicator()
				: $this->itemIndicator->getSomeoneIndicator()
			);
		}
		return $indicator;
	}

}