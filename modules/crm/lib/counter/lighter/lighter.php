<?php

namespace Bitrix\Crm\Counter\Lighter;

use Bitrix\Crm\Activity\LightCounter\ActCounterLightTimeTable;
use Bitrix\Crm\Counter\EntityCounterManager;
use Bitrix\Crm\Counter\EntityCounterType;
use Bitrix\Crm\Settings\CounterSettings;

final class Lighter
{
	private PushNotification $pushNotification;

	private LighterQueries $queries;

	private CounterManagerResetWrapper $resetWrapper;

	private bool $isUseActivityResponsible;

	public function __construct(LighterQueries $lighterQueries)
	{
		$this->queries = $lighterQueries;
		$this->pushNotification = PushNotification::getInstance();
		$this->resetWrapper = CounterManagerResetWrapper::getInstance();
		$this->isUseActivityResponsible = CounterSettings::getInstance()->useActivityResponsible();
	}

	public function execute(): void
	{
		$activityIds = $this->queries->queryActivityIdsToLightCounters();

		if (empty($activityIds))
		{
			return;
		}

		$activities = $this->queries->queryActivitiesByIds($activityIds);

		$bindingsGroupedByTypeId = $this->queries->queryGroupedBindings($activityIds);

		$entitiesInfo = $this->queries->queryEntitiesData($bindingsGroupedByTypeId);

		$codeGeneratorParams = $this->getCodeGeneratorParams($entitiesInfo, $activities);

		$resetData = $this->codesToResetGenerator($codeGeneratorParams);

		foreach ($resetData as $data)
		{
			[$codes, $responsibleIds] = $data;

			// reset counters also will send push notification to update UI counters.
			$this->resetWrapper->reset($codes, $responsibleIds);
			$this->resetWrapper->resetExcludeUsersCounters($codes, $responsibleIds);
		}

		$this->pushNotification->notifyTimeline($activities);
		$this->pushNotification->notifyKanban($entitiesInfo);
		$this->markAsNotified($activityIds);
	}


	private function codesToResetGenerator(array $codeGeneratorParams): array
	{
		$result = [];

		foreach ($codeGeneratorParams as $ownerTypeId => $categories)
		{
			foreach ($categories as $categoryId => $responsibleIds)
			{
				$extras = [];
				if ($categoryId !== 'None')
				{
					$extras['CATEGORY_ID'] = $categoryId;
				}

				$codes = EntityCounterManager::prepareCodes(
					$ownerTypeId,
					EntityCounterType::getAll(true),
					$extras
				);
				$result[] = [$codes, array_unique($responsibleIds)];
			}
		}

		return $result;
	}

	private function getCodeGeneratorParams(array $entitiesInfo, array $activities): array
	{
		$actResponsibleMap = $this->activityResponsibleMap($activities);

		$codeGeneratorParams = [];
		foreach ($entitiesInfo as $entityInfo)
		{
			$typeId = $entityInfo['OWNER_TYPE_ID'];
			$categoryId = $entityInfo['CATEGORY_ID'] ?? 'None';

			$responsibleIds = $this->getResponsibleIds($entityInfo, $actResponsibleMap);

			if (!isset($codeGeneratorParams[$typeId]))
			{
				$codeGeneratorParams[$typeId] = [];
			}

			if (!isset($codeGeneratorParams[$typeId][$categoryId]))
			{
				$codeGeneratorParams[$typeId][$categoryId] = [];
			}

			$current = $codeGeneratorParams[$typeId][$categoryId] ?? [];

			$codeGeneratorParams[$typeId][$categoryId] = array_merge($current, $responsibleIds);
		}

		return $codeGeneratorParams;
	}

	private function getResponsibleIds(mixed $entityInfo, array $actResponsibleMap): array
	{
		if ($this->isUseActivityResponsible)
		{
			$responsibleIds = [];
			foreach ($entityInfo['ACTIVITY_IDS'] as $activityId)
			{
				if (isset($actResponsibleMap[$activityId]))
				{
					$responsibleIds[] = $actResponsibleMap[$activityId];
				}
			}
		}
		else
		{
			$responsibleIds = [$entityInfo['ASSIGNED_ID']];
		}
		return $responsibleIds;
	}

	private function markAsNotified(array $activitiesIds): void
	{
		ActCounterLightTimeTable::updateMulti($activitiesIds, ['IS_LIGHT_COUNTER_NOTIFIED' => 'Y']);
	}

	private function activityResponsibleMap(array $activities): array
	{
		$result = [];
		foreach ($activities as $activity)
		{
			$result[$activity['ID']] = (int)$activity['RESPONSIBLE_ID'];
		}

		return $result;
	}
}