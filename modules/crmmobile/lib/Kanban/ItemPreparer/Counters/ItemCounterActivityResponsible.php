<?php

namespace Bitrix\CrmMobile\Kanban\ItemPreparer\Counters;

use Bitrix\CrmMobile\Kanban\ItemCounter;
use Bitrix\CrmMobile\Kanban\ItemIndicator;

class ItemCounterActivityResponsible implements ItemCounters
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
		$userId = (int)$params['userId'];

		$userData = $item['activitiesByUser'][$userId] ?? [];
		$activityError = (int)($userData['activityError'] ?? 0);
		$activityIncomingTotal = (int)($userData['incoming'] ?? 0);

		$indicator = $this->getIndicator($item, $activityCounterTotal, $userId);


		if (empty($userData))
		{
			$counters[] = $this->itemCounter->getEmptyCounter($activityCounterTotal);
			return new CountersResult($counters, $activityCounterTotal, $indicator);
		}

		$isReckonActivityLessItems = $params['isReckonActivityLessItems'];

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

		if ($activityError > 0 || $activityIncomingTotal > 0)
		{
			$activityCounterTotal = $activityError + $activityIncomingTotal;
		}

		return new CountersResult($counters, $activityCounterTotal, $indicator);
	}

	/**
	 * @return string[]|null
	 */
	private function getIndicator(array $item, mixed $activityCounterTotal, int $userId): ?array
	{
		$activityProgress = (int)($item['activityProgress'] ?? 0);

		$indicator = null;
		if (!$activityCounterTotal && $activityProgress > 0)
		{
			$activityProgressForCurrentUser = $item['activitiesByUser'][$userId]['activityProgress'] ?? 0;

			$indicator = $activityProgressForCurrentUser > 0
				? $this->itemIndicator->getOwnIndicator()
				: $this->itemIndicator->getSomeoneIndicator();
		}
		return $indicator;
	}
}