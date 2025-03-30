<?php

namespace Bitrix\Crm\Agent\Activity;

use Bitrix\Crm\ActivityTable;
use Bitrix\Crm\Agent\AgentBase;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Type\DateTime;

class CompleteOldActivities extends AgentBase
{
	private const COUNTER_LIMIT_THRESHOLD = 0.8;

	public static function doRun(): bool
	{
		$instance = new self();
		$instance->execute();

		return true;
	}

	public function execute(): void
	{
		if (!$this->shouldCompleteOldActivities())
		{
			return;
		}
		\Bitrix\Crm\Integration\PullManager::getInstance()->setEnabled(false);

		$processedCount = 0;
		$activitiesIdsWithOldDeadline = $this->getActivitiesIdsWithOldDeadline();
		$this->completeActivities($activitiesIdsWithOldDeadline);
		$processedCount += count($activitiesIdsWithOldDeadline);

		if (!$processedCount)
		{
			$activitiesIdsWithOldCreatedDate = $this->getActivitiesIdsWithOldCreatedDate();
			$this->completeActivities($activitiesIdsWithOldCreatedDate);
			$processedCount += count($activitiesIdsWithOldCreatedDate);
		}
		if ($processedCount > 0)
		{
			$this->forceNextAgentExecution();
			if ($processedCount < $this->getLimit()) // clear cache on last step
			{
				$this->cleanTariffLimitCache();
			}
		}
	}

	private function completeActivities(array $activitiesIds): void
	{
		if (empty($activitiesIds))
		{
			return;
		}

		$responsibleIds = $this->getResponsibleIds($activitiesIds);
		foreach ($activitiesIds as $activityId)
		{
			\CCrmActivity::Complete($activityId, true, [
				'REGISTER_SONET_EVENT' => false,
				'SKIP_ASSOCIATED_ENTITY' => true,
				'SKIP_CALENDAR_EVENT' => true,
				'CURRENT_USER' => $responsibleIds[$activityId] ?? 0,
			]);
		}
	}

	private function getActivitiesIdsWithOldDeadline(): array
	{
		return ActivityTable::query()
			->where('DEADLINE', '<', $this->getMinDate())
			->where('COMPLETED', 'N')
			->setSelect(['ID'])
			->setOrder(['DEADLINE' => 'ASC'])
			->setLimit($this->getLimit())
			->fetchCollection()
			->getIdList()
		;
	}

	private function getActivitiesIdsWithOldCreatedDate(): array
	{
		return ActivityTable::query()
			->where('CREATED', '<', $this->getMinDate())
			->where('DEADLINE', \CCrmDateTimeHelper::getMaxDatabaseDateObject())
			->where('COMPLETED', 'N')
			->setSelect(['ID'])
			->setOrder(['ID' => 'ASC'])
			->setLimit($this->getLimit())
			->fetchCollection()
			->getIdList()
		;
	}

	private function getResponsibleIds(array $activitiesIds): array
	{
		$result = [];
		if (empty($activitiesIds))
		{
			return $result;
		}

		$activities = ActivityTable::query()
			->whereIn('ID', $activitiesIds)
			->setSelect(['ID', 'RESPONSIBLE_ID'])
			->fetchCollection()
		;

		foreach ($activities as $activity)
		{
			$result[$activity->getId()] = $activity->getResponsibleId();
		}

		return $result;
	}

	private function getMinDate(): DateTime
	{
		$daysOffset = $this->getMinDayOffset();

		return (new DateTime())->add("-{$daysOffset}D");
	}

	private function getMinDayOffset(): int
	{
		return (int)Option::get('crm', 'CompleteOldActivitiesMinDayOffset', 365);
	}

	private function getLimit(): int
	{
		return (int)Option::get('crm', 'CompleteOldActivitiesLimit', 100);
	}

	private function forceNextAgentExecution(): void
	{
		global $pPERIOD;

		$pPERIOD = 30; // some magic to run the agent next time in 30 seconds
	}

	private function shouldCompleteOldActivities(): bool
	{
		$counterLimit = \Bitrix\Crm\Settings\CounterSettings::getInstance()->getCounterLimitValue();
		if ($counterLimit > 0)
		{
			return \Bitrix\Crm\Settings\CounterSettings::getInstance()->isCounterCurrentValueExceeded($counterLimit * self::COUNTER_LIMIT_THRESHOLD);
		}

		return false;
	}

	private function cleanTariffLimitCache(): void
	{
		\Bitrix\Crm\Settings\CounterSettings::getInstance()->cleanCounterLimitCache();
	}
}
