<?php

namespace Bitrix\Crm\Counter;

use Bitrix\Crm\ActivityBindingTable;
use Bitrix\Crm\ActivityTable;
use Bitrix\Crm\Counter\Monitor\ActivitiesChangesCollection;
use Bitrix\Crm\Counter\Monitor\ActivityChange;
use Bitrix\Crm\Counter\Monitor\CountableActivitySynchronizer;
use Bitrix\Crm\Counter\Monitor\EntitiesChangesCollection;
use Bitrix\Crm\Counter\Monitor\EntityChange;
use Bitrix\Crm\Counter\Monitor\MonitorByActResponsible;
use Bitrix\Crm\Counter\Monitor\MonitorByEntityResponsible;
use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Settings\CounterSettings;
use Bitrix\Main\Application;
use Bitrix\Crm\Traits;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Type\DateTime;

abstract class Monitor
{
	use Traits\Singleton;

	protected array $activitiesBindingsChanges = [];
	protected ActivitiesChangesCollection $activitiesChangesCollection;
	protected EntitiesChangesCollection $entitiesChangesCollection;
	private array $loadedEntitiesData = [];
	private bool $needProcessChangesInBackground = true;

	private function __construct()
	{
		$this->clearChangesCollections();

		Application::getInstance()->addBackgroundJob(fn() => $this->processChangesInBackground());
	}

	abstract protected function processActivitiesChanges(): void;

	abstract protected function processEntitiesChanges(): void;

	abstract protected function processActivityBindingsChanges(): void;

	public static function getInstance(): self
	{
		$code = Container::getIdentifierByClassName(Monitor::class);
		$serviceLocator = ServiceLocator::getInstance();
		if (!$serviceLocator->has($code))
		{
			$isUseActivityResponsible = CounterSettings::getInstance()->useActivityResponsible();

			$instance = $isUseActivityResponsible
				? new MonitorByActResponsible()
				: new MonitorByEntityResponsible()
			;

			$serviceLocator->addInstance($code, $instance);
		}

		return $serviceLocator->get($code);
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

		$this->processChangesIfNeed();
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

		$this->processChangesIfNeed();
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

		$this->processChangesIfNeed();
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

			$this->processChangesIfNeed();
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

			$this->processChangesIfNeed();
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

			$this->processChangesIfNeed();
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

			$this->processChangesIfNeed();
		}
	}

	private function processChanges(): void
	{
		$this->processActivitiesChanges();
		$this->processEntitiesChanges();
		$this->processActivityBindingsChanges();
		$this->clearChangesCollections();
	}

	private function processChangesInBackground(): void
	{
		$this->processChanges();
		$this->needProcessChangesInBackground = false;
	}

	private function processChangesIfNeed(): void
	{
		if (!$this->needProcessChangesInBackground)
		{
			$this->processChanges();
		}
	}

	private function clearChangesCollections(): void
	{
		$this->activitiesChangesCollection = new ActivitiesChangesCollection();
		$this->entitiesChangesCollection = new EntitiesChangesCollection();
		$this->activitiesBindingsChanges = [];
	}

	/**
	 * Load activity responsibility ids for specified identifiers
	 * @param ItemIdentifier[] $identifiers
	 * @return array<string, int[]> key is an ItemIdentifier hash, values is a responsible ids.
	 */
	protected function loadActivityResponsibleIds(array $identifiers): array
	{
		if (empty($identifiers))
		{
			return [];
		}
		$bindQuery = ActivityBindingTable::query()
			->registerRuntimeField(
				'',
				new ReferenceField('A',
					ActivityTable::getEntity(),
					['=ref.ID' => 'this.ACTIVITY_ID'],
					['join_type' => Join::TYPE_INNER])
			)
			->setSelect(['ACTIVITY_ID', 'OWNER_ID', 'OWNER_TYPE_ID'])
			->addSelect('A.RESPONSIBLE_ID', 'RESPONSIBLE_ID');

		$orCt = new ConditionTree();
		$orCt->logic(ConditionTree::LOGIC_OR);
		foreach ($identifiers as $identifier)
		{
			$ct = new ConditionTree();
			$ct->where('OWNER_ID', $identifier->getEntityId());
			$ct->where('OWNER_TYPE_ID', $identifier->getEntityTypeId());

			$orCt->where($ct);
		}
		$bindQuery->where($orCt);
		$bindings = $bindQuery->fetchAll();

		if (empty($bindings))
		{
			return [];
		}

		$result = [];
		foreach ($bindings as $bind)
		{
			$key = (new ItemIdentifier($bind['OWNER_TYPE_ID'], $bind['OWNER_ID']))->getHash();
			if (!isset($result[$key]))
			{
				$result[$key] = [];
			}
			$result[$key][] = (int) $bind['RESPONSIBLE_ID'];
		}

		return array_map(fn($resp) => array_unique($resp), $result);
	}

	protected function loadEntitiesDataForBindings(array $bindings): array
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

	protected function resetCounters(
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
