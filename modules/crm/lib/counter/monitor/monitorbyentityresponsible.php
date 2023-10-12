<?php

namespace Bitrix\Crm\Counter\Monitor;

use Bitrix\Crm\Counter\EntityCounterType;
use Bitrix\Crm\Counter\Monitor;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;

final class MonitorByEntityResponsible extends Monitor
{
	protected function processActivitiesChanges(): void
	{
		$changedActivities = $this->activitiesChangesCollection->getSignificantlyChangedActivities(false);
		$affectedBindings = $changedActivities->getAffectedBindings();
		$entitiesData = $this->loadEntitiesDataForBindings($affectedBindings);
		/** @var $changedActivity ActivityChange */
		foreach ($changedActivities->getValues() as $changedActivity)
		{
			$affectedTypeIds = $changedActivity->getAffectedCounterTypes();

			/** @var $binding ItemIdentifier */
			foreach ($changedActivity->getAffectedBindings() as $binding)
			{
				$entityData = $entitiesData[$binding->getEntityTypeId()][$binding->getEntityId()] ?? [];
				$this->resetCounters(
					$binding->getEntityTypeId(),
					$affectedTypeIds,
					$entityData['assignedBy'] ?? null,
					$entityData['categoryId'] ?? null,
				);
			}
		}
	}

	protected function processEntitiesChanges(): void
	{
		$changedEntities = $this->entitiesChangesCollection->significantlyChangedEntitiesForEntityResponsible();
		/** @var $changedEntity EntityChange */
		foreach ($changedEntities->getValues() as $changedEntity)
		{
			$entityTypeId = $changedEntity->getIdentifier()->getEntityTypeId();
			if ($changedEntity->wasEntityAdded())
			{
				$factory = Container::getInstance()->getFactory($entityTypeId);
				// if entity was added, it only affects the idle counter
				if (
					$factory && $factory
						->getCountersSettings()
						->isIdleCounterEnabled()
				)
				{
					$this->resetCounters(
						$entityTypeId,
						[
							EntityCounterType::IDLE,
							EntityCounterType::ALL,
						],
						$changedEntity->getActualAssignedById(),
						$changedEntity->getActualCategoryId()
					);
				}
			}
			else
			{
				$affectedResponsibleIds = [];
				if ($changedEntity->getNewAssignedById() > 0)
				{
					$affectedResponsibleIds[] = $changedEntity->getNewAssignedById();
				}
				if ($changedEntity->isAssignedByChanged() && $changedEntity->getOldAssignedById() > 0)
				{
					$affectedResponsibleIds[] = $changedEntity->getOldAssignedById();
				}

				$categoryWasChanged = $changedEntity->isCategoryIdChanged() && !is_null($changedEntity->getOldCategoryId());
				foreach ($affectedResponsibleIds as $responsibleId)
				{
					$this->resetCounters(
						$entityTypeId,
						EntityCounterType::getAll(true),
						$responsibleId,
						$changedEntity->getActualCategoryId(),
					);

					if ($categoryWasChanged)
					{
						$this->resetCounters(
							$entityTypeId,
							EntityCounterType::getAll(true),
							$responsibleId,
							$changedEntity->getOldCategoryId(),
						);
					}
				}
			}
		}
	}

	protected function processActivityBindingsChanges(): void
	{
		$affectedBindings = array_values($this->activitiesBindingsChanges);
		$entitiesData = $this->loadEntitiesDataForBindings($affectedBindings);
		foreach ($affectedBindings as $binding)
		{
			$entityData = $entitiesData[$binding->getEntityTypeId()][$binding->getEntityId()] ?? [];
			$this->resetCounters(
				$binding->getEntityTypeId(),
				EntityCounterType::getAll(true),
				$entityData['assignedBy'] ?? null,
				$entityData['categoryId'] ?? null,
			);
		}
	}
}