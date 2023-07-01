<?php

namespace Bitrix\Crm\Counter\Lighter;

use Bitrix\Crm\Activity\LightCounter\ActCounterLightTimeTable;
use Bitrix\Crm\Counter\EntityCounterManager;
use Bitrix\Crm\Counter\EntityCounterType;
use Bitrix\Crm\Service\Container;
use Generator;

final class Lighter
{
	private PushNotification $pushNotification;

	private LighterQueries $queries;

	public function __construct(LighterQueries $lighterQueries)
	{
		$this->queries = $lighterQueries;
		$this->pushNotification = new PushNotification();
	}

	public function execute(): void
	{
		$activityIds = $this->queries->queryActivityIdsToLightCounters();

		if (empty($activityIds))
		{
			return;
		}

		$groupedBindings = $this->queries->queryGroupedBindings($activityIds);
		$entitiesInfo = $this->queries->queryEntitiesInfo($groupedBindings);


		foreach ($this->codesToResetGenerator($entitiesInfo) as $data)
		{
			[$codes, $responsibleIds] = $data;

			// reset counters also will send push notification to update UI counters.
			EntityCounterManager::reset($codes, $responsibleIds);
			EntityCounterManager::resetExcludeUsersCounters($codes, $responsibleIds);
		}

		$this->pushNotification->notifyTimeline($this->queries->queryActivitiesByIds($activityIds));

		$this->pushNotification->notifyKanban($entitiesInfo);

		$this->markAsNotified($activityIds);
	}

	/**
	 * @param EntitiesInfo $entitiesInfo
	 * @return Generator|array<string[], int[]> Generator An iterator that yields a tuple of entity counter codes
	 * and an array of unique responsible IDs for each entity type and category
	 */
	private function codesToResetGenerator(EntitiesInfo $entitiesInfo): Generator
	{
		$ownerTypeIds = $entitiesInfo->uniqueOwnerTypeIds();

		foreach ($ownerTypeIds as $ownerTypeId)
		{
			$categoriesWithResponsible = $entitiesInfo->getAssignedIdsByCategory($ownerTypeId);

			foreach ($categoriesWithResponsible as $row)
			{
				$categoryId = $row['CATEGORY_ID'];
				$responsibleIds = $row['ASSIGNED_IDS'];

				$factory = Container::getInstance()->getFactory($ownerTypeId);

				$extras = [];
				if ($factory->isCategoriesEnabled())
				{
					$extras['CATEGORY_ID'] = $categoryId;
				}

				$codes = EntityCounterManager::prepareCodes(
					$ownerTypeId,
					EntityCounterType::getAll(true),
					$extras
				);

				yield [$codes, array_unique($responsibleIds)];
			}
		}
	}

	private function markAsNotified(array $activitiesIds)
	{
		ActCounterLightTimeTable::updateMulti($activitiesIds, ['IS_LIGHT_COUNTER_NOTIFIED' => 'Y']);
	}

}