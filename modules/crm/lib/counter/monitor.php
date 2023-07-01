<?php

namespace Bitrix\Crm\Counter;

use Bitrix\Crm\Counter\Monitor\ActivitiesChangesCollection;
use Bitrix\Crm\Counter\Monitor\ActivityChange;
use Bitrix\Crm\Counter\Monitor\CountableActivitySynchronizer;
use Bitrix\Crm\Counter\Monitor\EntitiesChangesCollection;
use Bitrix\Crm\Counter\Monitor\EntityChange;
use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Application;
use Bitrix\Crm\Traits;
use Bitrix\Main\Type\DateTime;

final class Monitor
{
	use Traits\Singleton;

	private array $activitiesBindingsChanges = [];
	private array $activitiesIdsWithBindingsChanges = [];
	private ActivitiesChangesCollection $activitiesChangesCollection;
	private EntitiesChangesCollection $entitiesChangesCollection;
	private array $loadedEntitiesData = [];

	private function __construct()
	{
		$this->activitiesChangesCollection = new ActivitiesChangesCollection();
		$this->entitiesChangesCollection = new EntitiesChangesCollection();

		Application::getInstance()->addBackgroundJob(fn() => $this->processChanges());
	}

	public function onActivityAdd(
		array $activityFields,
		array $activityBindings,
		?DateTime $newLightTimeDate
	): void
	{
		$activityChange = ActivityChange::create(
			(int)$activityFields['ID'],
			[],
			[],
			$activityFields,
			$activityBindings,
			null,
			$newLightTimeDate
		);
		$this->synchronizeEntityCountableTableByActivityChange($activityChange);
		$this->activitiesChangesCollection->add($activityChange);
	}

	public function onActivityUpdate(
		array $oldActivityFields,
		array $newActivityFields,
		array $oldActivityBindings,
		array $newActivityBindings,
		?DateTime $oldLightTimeDate,
		?DateTime $newLightTimeDate
	): void
	{
		$activityChange = ActivityChange::create(
			(int)$oldActivityFields['ID'],
			$oldActivityFields,
			$oldActivityBindings,
			$newActivityFields,
			$newActivityBindings,
			$oldLightTimeDate,
			$newLightTimeDate
		);
		if ($activityChange->hasSignificantChangesForCountable())
		{
			$this->synchronizeEntityCountableTableByActivityChange($activityChange);
		}
		$this->activitiesChangesCollection->add($activityChange);
	}

	public function onActivityDelete(array $activityFields, array $activityBindings, ?DateTime $oldLightTimeDate): void
	{
		$activityChange = ActivityChange::create(
			(int)$activityFields['ID'],
			$activityFields,
			$activityBindings,
			[],
			[],
			$oldLightTimeDate,
			null
		);
		$this->synchronizeEntityCountableTableByActivityChange($activityChange);
		$this->activitiesChangesCollection->add($activityChange);
	}

	public function onChangeActivityBindings(int $activityId, array $oldActivityBindings, array $newActivityBindings): void
	{
		$addedBindings = [];
		$removedBindings = [];
		\CCrmActivity::PrepareBindingChanges($oldActivityBindings, $newActivityBindings, $addedBindings, $removedBindings);

		$changedBindings = array_merge($addedBindings, $removedBindings);
		$changedBindings = \Bitrix\Crm\Counter\Monitor\ActivityChange::prepareBindings($changedBindings);
		foreach ($changedBindings as $binding)
		{
			$this->activitiesBindingsChanges[$binding->getHash()] = $binding;
		}
		if (count($changedBindings))
		{
			CountableActivitySynchronizer::synchronizeByActivityId($activityId);
		}
	}
	public function onChangeActivitySingleBinding(int $activityId, array $oldActivityBinding, array $newActivityBinding): void
	{
		$this->onChangeActivityBindings($activityId, [$oldActivityBinding], [$newActivityBinding]);
	}

	public function onEntityAdd(int $entityTypeId, array $entityFields): void
	{
		$change = EntityChange::create(
			$entityTypeId,
			(int)$entityFields['ID'],
			[],
			$entityFields
		);
		if ($change)
		{
			$this->entitiesChangesCollection->add($change);
			CountableActivitySynchronizer::synchronizeByEntityChange($change);
		}
	}

	public function onEntityUpdate(int $entityTypeId, array $oldEntityFields, array $newEntityFields): void
	{
		$change = EntityChange::create(
			$entityTypeId,
			(int)$oldEntityFields['ID'],
			$oldEntityFields,
			$newEntityFields
		);
		if ($change)
		{
			$this->entitiesChangesCollection->add($change);
			CountableActivitySynchronizer::synchronizeByEntityChange($change);
		}
	}

	public function onEntityDelete(int $entityTypeId, array $entityFields): void
	{
		$change = EntityChange::create(
			$entityTypeId,
			(int)$entityFields['ID'],
			$entityFields,
			[],
		);
		if ($change)
		{
			$this->entitiesChangesCollection->add($change);
			CountableActivitySynchronizer::synchronizeByEntityChange($change);
		}
	}

	private function processChanges(): void
	{
		$this->processActivitiesChanges();
		$this->processEntitiesChanges();
		$this->processActivityBindingsChanges();
	}

	private function processActivitiesChanges(): void
	{
		$changedActivities = $this->activitiesChangesCollection->getSignificantlyChangedActivities();
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

	private function processEntitiesChanges(): void
	{
		$changedEntities = $this->entitiesChangesCollection->getSignificantlyChangedEntities();
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

	private function processActivityBindingsChanges(): void
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

	private function loadEntitiesDataForBindings(array $bindings): array
	{
		if (empty($bindings))
		{
			return [];
		}
		$loadedEntitiesData = $this->loadedEntitiesData;

		/** @var ItemIdentifier $binding */
		foreach ($bindings as $binding)
		{
			$value = $loadedEntitiesData[$binding->getEntityTypeId()][$binding->getEntityId()] ?? null;
			if (!is_array($value))
			{
				$loadedEntitiesData[$binding->getEntityTypeId()][$binding->getEntityId()] = [];
			}
		}

		// get fields from entities changes
		/** @var EntityChange $entityChange */
		foreach ($this->entitiesChangesCollection->getValues() as $entityChange)
		{
			$entityTypeId = $entityChange->getIdentifier()->getEntityTypeId();
			$entityId = $entityChange->getIdentifier()->getEntityId();
			if (isset($loadedEntitiesData[$entityTypeId][$entityId]))
			{
				$loadedEntitiesData[$entityTypeId][$entityId] = [
					'assignedBy' => $entityChange->getActualAssignedById(),
					'categoryId' => $entityChange->getActualCategoryId(),
				];
			}
		}
		// load fields for not modified entities
		foreach ($loadedEntitiesData as $entityTypeId => $entityTypeBindings)
		{
			$factory = Container::getInstance()->getFactory($entityTypeId);
			if (!$factory)
			{
				unset($loadedEntitiesData[$entityTypeId]);

				continue;
			}
			$entitiesToLoad = [];
			foreach ($entityTypeBindings as $entityId => $entityData)
			{
				if (empty($entityData))
				{
					$entitiesToLoad[] = (int)$entityId;
				}
			}
			if (!empty($entitiesToLoad))
			{
				$assignedByFiledName = $factory->getEntityFieldNameByMap(Item::FIELD_NAME_ASSIGNED);
				$categoryIdFieldName = $factory->getEntityFieldNameByMap(Item::FIELD_NAME_CATEGORY_ID);

				$select = [
					Item::FIELD_NAME_ID,
				];
				if ($factory->isFieldExists(Item::FIELD_NAME_ASSIGNED))
				{
					$select[] = $assignedByFiledName;
				}
				if ($factory->isFieldExists(Item::FIELD_NAME_CATEGORY_ID))
				{
					$select[] = $categoryIdFieldName;
				}

				$entitiesData = $factory->getDataClass()::query()
					->setSelect($select)
					->whereIn('ID', $entitiesToLoad)
					->fetchAll()
				;
				foreach ($entitiesData as $entityData)
				{
					if (isset($loadedEntitiesData[$entityTypeId][$entityData['ID']]))
					{
						$loadedEntitiesData[$entityTypeId][$entityData['ID']] = [
							'assignedBy' => $entityData[$assignedByFiledName] ?? null,
							'categoryId' => $entityData[$categoryIdFieldName] ?? null,
						];

						$this->loadedEntitiesData[$entityTypeId][$entityData['ID']] = $loadedEntitiesData[$entityTypeId][$entityData['ID']];
					}
				}
			}
		}

		return $loadedEntitiesData;
	}

	private function resetCounters(
		int $entityTypeId,
		array $counterTypeIds,
		?int $responsibleId,
		?int $categoryId
	): void
	{
		if (empty($counterTypeIds) || !$responsibleId)
		{
			return;
		}
		$extras = [];
		if (!is_null($categoryId))
		{
			$extras['CATEGORY_ID'] = $categoryId;
		}
		// $counterCodes = EntityCounterManager::prepareCodes(\CCrmOwnerType::Activity, EntityCounterType::CURRENT);
		$counterCodes = EntityCounterManager::prepareCodes(
			$entityTypeId,
			$counterTypeIds,
			$extras
		);

		if(!empty($counterCodes))
		{
			EntityCounterManager::reset($counterCodes, [$responsibleId]);
			EntityCounterManager::resetExcludeUsersCounters($counterCodes, [$responsibleId]);
		}
	}

	private function synchronizeEntityCountableTableByActivityChange(ActivityChange $changedActivity): void
	{
		if ($changedActivity->wasActivityDeleted())
		{
			EntityCountableActivityTable::deleteByActivity($changedActivity->getId());
		}
		elseif ($changedActivity->areBindingsChanged())
		{
			CountableActivitySynchronizer::synchronizeByActivityId($changedActivity->getId());
		}
		else
		{
			$entitiesData = $this->loadEntitiesDataForBindings($changedActivity->getAffectedBindings());
			CountableActivitySynchronizer::synchronizeByActivityChange($changedActivity, $entitiesData);
		}

	}
}
