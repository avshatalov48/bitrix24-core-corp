<?php

namespace Bitrix\Crm\Counter\Monitor;

use Bitrix\Crm\Counter\EntityCounterType;
use Bitrix\Crm\Counter\Monitor;
use Bitrix\Crm\ItemIdentifier;

final class MonitorByActResponsible extends Monitor
{

	protected function processActivitiesChanges(): void
	{
		$changedActivities = $this->activitiesChangesCollection->getSignificantlyChangedActivities(true);
		$affectedBindings = $changedActivities->getAffectedBindings();
		$entitiesData = $this->loadEntitiesDataForBindings($affectedBindings);
		/** @var $changedActivity ActivityChange */
		foreach ($changedActivities->getValues() as $changedActivity)
		{

			$affectedResponsibleIds = [];
			if ($changedActivity->getNewResponsibleId() > 0)
			{
				$affectedResponsibleIds[] = $changedActivity->getNewResponsibleId();
			}
			if ($changedActivity->isResponsibleIdChanged() && $changedActivity->getOldResponsibleId() > 0)
			{
				$affectedResponsibleIds[] = $changedActivity->getOldResponsibleId();
			}

			foreach ($affectedResponsibleIds as $responsibleId)
			{

				$affectedTypeIds = $changedActivity->getAffectedCounterTypes(true);
				/** @var $binding ItemIdentifier */
				foreach ($changedActivity->getAffectedBindings() as $binding)
				{
					$entityData = $entitiesData[$binding->getEntityTypeId()][$binding->getEntityId()] ?? [];
					$this->resetCounters(
						$binding->getEntityTypeId(),
						$affectedTypeIds,
						$responsibleId,
						$entityData['categoryId'] ?? null,
					);
				}

			}
		}
	}

	protected function processEntitiesChanges(): void
	{
		$this->resetForCategoryChanged();

		$this->resetForIdleChanged();
	}

	private function resetForIdleChanged()
	{
		$changedEntities = $this->entitiesChangesCollection->onlyIdleSupportedEntities();

		foreach ($changedEntities->getValues() as $changedEntity)
		{
			$entityTypeId = $changedEntity->getIdentifier()->getEntityTypeId();

			if ($changedEntity->wasEntityAdded())
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

				foreach ($affectedResponsibleIds as $responsibleId)
				{
					$this->resetCounters(
						$entityTypeId,
						[
							EntityCounterType::IDLE,
							EntityCounterType::ALL,
						],
						$responsibleId,
						$changedEntity->getActualCategoryId(),
					);
				}
			}
		}
	}

	private function resetForCategoryChanged()
	{
		$changedEntities = $this->entitiesChangesCollection->onlyCategoryChangedEntities();

		if ($changedEntities->isEmpty())
		{
			return;
		}

		$identifiers = array_map(fn(EntityChange $item) => $item->getIdentifier(), $changedEntities->getValues());
		$responsible = $this->loadActivityResponsibleIds($identifiers);

		/** @var $changedEntity EntityChange */
		foreach ($changedEntities as $changedEntity)
		{
			$entityTypeId = $changedEntity->getIdentifier()->getEntityTypeId();
			$iiHash = $changedEntity->getIdentifier()->getHash();

			if (!isset($responsible[$iiHash]))
			{
				return;
			}

			foreach ($responsible[$iiHash] as $responsibleId)
			{
				$this->resetCounters(
					$entityTypeId,
					EntityCounterType::getAll(true),
					$responsibleId,
					$changedEntity->getOldCategoryId(),
				);

				$this->resetCounters(
					$entityTypeId,
					EntityCounterType::getAll(true),
					$responsibleId,
					$changedEntity->getActualCategoryId(),
				);
			}
		}
	}

	protected function processActivityBindingsChanges(): void
	{
		$affectedBindings = array_values($this->activitiesBindingsChanges);

		$entitiesData = $this->loadEntitiesDataForBindings($affectedBindings);

		$responsible = $this->loadActivityResponsibleIds($affectedBindings);


		foreach ($affectedBindings as $binding)
		{
			$entityData = $entitiesData[$binding->getEntityTypeId()][$binding->getEntityId()] ?? [];

			if (!isset($responsible[$binding->getHash()]))
			{
				continue;
			}

			$responsibleIds = $responsible[$binding->getHash()];

			foreach ($responsibleIds as $responsibleId)
			{
				$this->resetCounters(
					$binding->getEntityTypeId(),
					EntityCounterType::getAll(true),
					$responsibleId,
					$entityData['categoryId'] ?? null,
				);
			}
		}
	}

}