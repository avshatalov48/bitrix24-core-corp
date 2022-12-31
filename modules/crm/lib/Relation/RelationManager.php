<?php

namespace Bitrix\Crm\Relation;

use Bitrix\Catalog\v2\Contractor\Provider\Manager;
use Bitrix\Crm\Binding\ContactCompanyTable;
use Bitrix\Crm\Binding\DealContactTable;
use Bitrix\Crm\Binding\LeadContactTable;
use Bitrix\Crm\Conversion\Entity\EntityConversionMapTable;
use Bitrix\Crm\Conversion\Entity\EO_EntityConversionMap;
use Bitrix\Crm\Conversion\Entity\EO_EntityConversionMap_Collection;
use Bitrix\Crm\Integration\Catalog\Contractor\DocumentRelationStorageStrategy;
use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Relation;
use Bitrix\Crm\RelationIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Settings\InvoiceSettings;
use Bitrix\Crm\Settings\QuoteSettings;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;

class RelationManager
{
	public const ERROR_CODE_BIND_TYPES_TYPES_ALREADY_BOUND = 'CRM_BIND_TYPES_TYPES_ALREADY_BOUND';
	public const ERROR_CODE_UNBIND_TYPES_TYPES_NOT_BOUND = 'CRM_UNBIND_TYPES_TYPES_NOT_BOUND';
	public const ERROR_CODE_UNBIND_TYPES_RELATION_IS_PREDEFINED = 'CRM_UNBIND_TYPES_RELATION_IS_PREDEFINED';
	public const ERROR_CODE_UPDATE_TYPES_RELATION_IS_PREDEFINED = 'CRM_UPDATE_TYPES_RELATION_IS_PREDEFINED';
	public const ERROR_CODE_UPDATE_TYPES_RELATION_NOT_BOUND = 'CRM_UPDATE_TYPES_RELATION_NOT_FOUND';
	public const ERROR_CODE_UPDATE_TYPES_RELATION_NOT_FOUND = 'CRM_UPDATE_TYPES_RELATION_NOT_FOUND';

	public const ERROR_CODE_BIND_ITEMS_TYPES_NOT_BOUND = 'CRM_BIND_ITEMS_TYPES_NOT_BOUND';
	public const ERROR_CODE_BIND_ITEMS_ITEMS_ALREADY_BOUND = 'CRM_BIND_ITEMS_ITEMS_ALREADY_BOUND';
	public const ERROR_CODE_UNBIND_ITEMS_TYPES_NOT_BOUND = 'CRM_UNBIND_ITEMS_TYPES_NOT_BOUND';
	public const ERROR_CODE_UNBIND_ITEMS_ITEMS_NOT_BOUND = 'CRM_UNBIND_ITEMS_ITEMS_NOT_BOUND';

	protected const TAB_NAME_RELATION = 'tab_relation_';
	protected const RELATION_TYPE = RelationType::BINDING;

	/** @var EntityConversionMapTable */
	protected $mapTableClass = EntityConversionMapTable::class;
	protected $availableEntityTypes;
	protected $relationsCache = [];
	protected $customRelations;
	protected $predefinedRelations;

	/**
	 * Return array of entities that can be bind.
	 *
	 * @return array[] [entityTypeId => ['title' => string, 'entityTypeId' => int]]
	 */
	protected function getAvailableForBindingEntityTypes(): array
	{
		if ($this->availableEntityTypes === null)
		{
			$this->availableEntityTypes = [];
			$entityTypeIds = [
				\CCrmOwnerType::Lead => \CCrmOwnerType::Lead,
				\CCrmOwnerType::Contact => \CCrmOwnerType::Contact,
				\CCrmOwnerType::Company => \CCrmOwnerType::Company,
				\CCrmOwnerType::Deal => \CCrmOwnerType::Deal,
				\CCrmOwnerType::Order => \CCrmOwnerType::Order,
			];
			if (QuoteSettings::getCurrent()->isFactoryEnabled())
			{
				$entityTypeIds[\CCrmOwnerType::Quote] = \CCrmOwnerType::Quote;
			}
			if (InvoiceSettings::getCurrent()->isSmartInvoiceEnabled())
			{
				$entityTypeIds[\CCrmOwnerType::SmartInvoice] = \CCrmOwnerType::SmartInvoice;
			}
			foreach ($entityTypeIds as $entityTypeId)
			{
				$this->availableEntityTypes[$entityTypeId] = [
					'entityTypeId' => $entityTypeId,
					'title' => \CCrmOwnerType::GetDescription($entityTypeId),
				];
			}
			$typesMap = Container::getInstance()->getDynamicTypesMap();
			$typesMap->load([
				'isLoadStages' => false,
				'isLoadCategories' => false,
			]);
			foreach ($typesMap->getTypes() as $type)
			{
				$entityTypeId = $type->getEntityTypeId();
				$this->availableEntityTypes[$entityTypeId] = [
					'entityTypeId' => $entityTypeId,
					'title' => $type->getTitle(),
				];
			}
		}

		return $this->availableEntityTypes;
	}

	/**
	 * @param int|null $currentEntityId
	 *
	 * @return array[] [entityTypeId => ['title' => string, 'entityTypeId' => int]]
	 */
	public function getAvailableForParentBindingEntityTypes(?int $currentEntityId = null): array
	{
		$availableTypes = $this->getAvailableForBindingEntityTypes();
		if ($currentEntityId)
		{
			unset($availableTypes[$currentEntityId]);
		}
		unset(
			$availableTypes[\CCrmOwnerType::Contact],
			$availableTypes[\CCrmOwnerType::Company],
		);

		return $availableTypes;
	}

	/**
	 * @param int|null $currentEntityId
	 *
	 * @return array[] [entityTypeId => ['title' => string, 'entityTypeId' => int]]
	 */
	public function getAvailableForChildBindingEntityTypes(?int $currentEntityId = null): array
	{
		$availableTypes = $this->getAvailableForBindingEntityTypes();
		if ($currentEntityId)
		{
			unset($availableTypes[$currentEntityId]);
		}
		unset(
			$availableTypes[\CCrmOwnerType::Order]
		);

		return $availableTypes;
	}

	public function getClientFieldEntityTypeIds(): array
	{
		return [
			\CCrmOwnerType::Contact => \CCrmOwnerType::Contact,
			\CCrmOwnerType::Company => \CCrmOwnerType::Company,
		];
	}

	protected function dropRelationsCache(RelationIdentifier $identifier): void
	{
		unset(
			$this->relationsCache[$identifier->getParentEntityTypeId()],
			$this->relationsCache[$identifier->getChildEntityTypeId()],
		);
		$this->customRelations = null;
	}

	/**
	 * Bind the types with each other
	 *
	 * @param Relation $relation
	 *
	 * @return Result
	 */
	public function bindTypes(Relation $relation): Result
	{
		if ($this->areTypesBound($relation->getIdentifier()))
		{
			return (new Result())->addError(
				new Error(
					'The types are bound already',
					static::ERROR_CODE_BIND_TYPES_TYPES_ALREADY_BOUND
				)
			);
		}

		$entityObject = $this->mapTableClass::createObject();

		$entityObject
			->setSrcTypeId($relation->getParentEntityTypeId())
			->setDstTypeId($relation->getChildEntityTypeId())
		;

		$this->dropRelationsCache($relation->getIdentifier());

		return $this->updateEntityObject($relation->getSettings(), $entityObject);
	}

	public function updateTypesBinding(Relation $relation): Result
	{
		$result = new Result();

		$existingRelation = $this->getRelation($relation->getIdentifier());
		if (!$existingRelation)
		{
			return $result->addError(
				new Error(
					'The types are not bound',
					static::ERROR_CODE_UPDATE_TYPES_RELATION_NOT_BOUND,
				)
			);
		}

		if ($existingRelation->isPredefined())
		{
			return $result->addError(
				new Error(
					"A predefined relation can't be updated",
					static::ERROR_CODE_UPDATE_TYPES_RELATION_IS_PREDEFINED,
				)
			);
		}

		$entityObject = $this->fetchEntityObject($relation->getIdentifier());
		if (!$entityObject)
		{
			return $result->addError(
				new Error(
					'Record containing relation is not found',
					static::ERROR_CODE_UPDATE_TYPES_RELATION_NOT_FOUND,
				)
			);
		}

		$this->dropRelationsCache($relation->getIdentifier());

		return $this->updateEntityObject($relation->getSettings(), $entityObject);
	}

	/**
	 * Unbind the types
	 *
	 * @param RelationIdentifier $identifier
	 *
	 * @return Result
	 */
	public function unbindTypes(RelationIdentifier $identifier): Result
	{
		$result = new Result();

		$existingRelation = $this->getRelation($identifier);

		if (!$existingRelation)
		{
			return $result->addError(
				new Error('The types are not bound', static::ERROR_CODE_UNBIND_TYPES_TYPES_NOT_BOUND)
			);
		}

		if ($existingRelation->isPredefined())
		{
			return $result->addError(
				new Error(
					"A predefined relation can't be unbound",
					static::ERROR_CODE_UNBIND_TYPES_RELATION_IS_PREDEFINED
				)
			);
		}

		$this->dropRelationsCache($identifier);

		return $this->deleteRelation($identifier);
	}

	/**
	 * Returns true if the types are bound
	 *
	 * @param RelationIdentifier $identifier
	 *
	 * @return bool
	 */
	public function areTypesBound(RelationIdentifier $identifier): bool
	{
		return !is_null($this->getRelation($identifier));
	}

	/**
	 * Bind the provided items with each other
	 *
	 * @param ItemIdentifier $parent
	 * @param ItemIdentifier $child
	 *
	 * @return Result
	 */
	public function bindItems(ItemIdentifier $parent, ItemIdentifier $child): Result
	{
		$relation = $this->findExistingRelationByIdentifiers($parent, $child);
		if (!$relation)
		{
			return (new Result())->addError(
				new Error('The types are not bound', static::ERROR_CODE_BIND_ITEMS_TYPES_NOT_BOUND)
			);
		}

		return $relation->bindItems($parent, $child);
	}

	/**
	 * Unbind the provided items
	 *
	 * @param ItemIdentifier $parent
	 * @param ItemIdentifier $child
	 *
	 * @return Result
	 */
	public function unbindItems(ItemIdentifier $parent, ItemIdentifier $child): Result
	{
		$relation = $this->findExistingRelationByIdentifiers($parent, $child);
		if (!$relation)
		{
			return (new Result())->addError(
				new Error('The types are not bound', static::ERROR_CODE_UNBIND_ITEMS_TYPES_NOT_BOUND)
			);
		}

		return $relation->unbindItems($parent, $child);
	}

	/**
	 * Returns true if the items are bound
	 *
	 * @param ItemIdentifier $parent
	 * @param ItemIdentifier $child
	 *
	 * @return bool
	 */
	public function areItemsBound(ItemIdentifier $parent, ItemIdentifier $child): bool
	{
		$relation = $this->findExistingRelationByIdentifiers($parent, $child);
		if (!$relation)
		{
			return false;
		}

		return $relation->areItemsBound($parent, $child);
	}

	protected function findExistingRelationByIdentifiers(ItemIdentifier $parent, ItemIdentifier $child): ?Relation
	{
		return $this->getRelation(
			new RelationIdentifier($parent->getEntityTypeId(), $child->getEntityTypeId())
		);
	}

	/**
	 * Fetch is isolated in a separate method for testing purposes
	 *
	 * @param RelationIdentifier $identifier
	 * @return EO_EntityConversionMap|null
	 */
	protected function fetchEntityObject(RelationIdentifier $identifier): ?EO_EntityConversionMap
	{
		return $this->mapTableClass::getByPrimary([
			'SRC_TYPE_ID' => $identifier->getParentEntityTypeId(),
			'DST_TYPE_ID' => $identifier->getChildEntityTypeId(),
		])->fetchObject();
	}

	protected function updateEntityObject(Relation\Settings $settings, EO_EntityConversionMap $entityObject): Result
	{
		$entityObject->setIsChildrenListEnabled($settings->isChildrenListEnabled());
		$entityObject->setRelationType($settings->getRelationType());

		return $this->saveEntityObject($entityObject);
	}

	/**
	 * Save is isolated in a separate method for testing purposes
	 *
	 * @param EO_EntityConversionMap $entityObject
	 *
	 * @return Result
	 */
	protected function saveEntityObject(EO_EntityConversionMap $entityObject): Result
	{
		return $entityObject->save();
	}

	/**
	 * Deletion is isolated in a separate method for testing purposes
	 *
	 * @param RelationIdentifier $identifier
	 *
	 * @return Result
	 * @throws \Exception
	 */
	protected function deleteRelation(RelationIdentifier $identifier): Result
	{
		return $this->mapTableClass::delete([
			'SRC_TYPE_ID' => $identifier->getParentEntityTypeId(),
			'DST_TYPE_ID' => $identifier->getChildEntityTypeId(),
		]);
	}

	/**
	 * Returns a Relation object that is described by the $identifier.
	 * If such a Relation was not found, returns null
	 *
	 * @param RelationIdentifier $identifier
	 *
	 * @return Relation|null
	 */
	public function getRelation(RelationIdentifier $identifier): ?Relation
	{
		$relations =
			$this->getRelations($identifier->getParentEntityTypeId())
				->merge(
					$this->getRelations($identifier->getChildEntityTypeId())
				)
		;

		return $relations->get($identifier);
	}

	/**
	 * Returns all relations that mention the provided entityTypeId
	 *
	 * @param int $entityTypeId
	 *
	 * @return Relation\Collection
	 */
	public function getRelations(int $entityTypeId): Relation\Collection
	{
		if (isset($this->relationsCache[$entityTypeId]))
		{
			return $this->relationsCache[$entityTypeId];
		}

		$parentRelations = $this->getParentRelations($entityTypeId);
		$this->relationsCache[$entityTypeId] = $parentRelations->merge($this->getChildRelations($entityTypeId));

		return $this->relationsCache[$entityTypeId];
	}

	/**
	 * Returns relations in which the provided entityTypeId is mentioned as child
	 *
	 * @param int $childEntityTypeId
	 *
	 * @return Relation\Collection
	 */
	public function getParentRelations(int $childEntityTypeId): Relation\Collection
	{
		$predefinedRelations = $this->getPredefinedRelations()->filterByChildEntityTypeId($childEntityTypeId);

		return $predefinedRelations->merge($this->getCustomParentRelations($childEntityTypeId));
	}

	/**
	 * Returns relations in which the provided entityTypeId is mentioned as parent
	 *
	 * @param int $parentEntityTypeId
	 *
	 * @return Relation\Collection
	 */
	public function getChildRelations(int $parentEntityTypeId): Relation\Collection
	{
		$predefinedRelations = $this->getPredefinedRelations()->filterByParentEntityTypeId($parentEntityTypeId);

		return $predefinedRelations->merge($this->getCustomChildRelations($parentEntityTypeId));
	}

	/**
	 * Returns the collection of predefined (set on the system level) relations
	 *
	 * @return Relation\Collection
	 */
	protected function getPredefinedRelations(): Relation\Collection
	{
		if ($this->predefinedRelations !== null)
		{
			return $this->predefinedRelations;
		}

		$quoteFactory = Container::getInstance()->getFactory(\CCrmOwnerType::Quote);

		$bindingSettings =
			(new Settings())
				->setIsPredefined(true)
				->setIsChildrenListEnabled(true)
				->setRelationType(RelationType::BINDING)
		;

		$conversionSettings =
			(clone $bindingSettings)
				->setRelationType(RelationType::CONVERSION)
		;

		$predefinedRelations = [
			//region Contact child predefined relations
			(new Relation(
				new RelationIdentifier(\CCrmOwnerType::Contact, \CCrmOwnerType::Invoice),
				clone $bindingSettings
			))
				->setStorageStrategy(new StorageStrategy\Compatible(\CCrmInvoice::class, 'UF_CONTACT_ID')),

			(new Relation(
				new RelationIdentifier(\CCrmOwnerType::Contact, \CCrmOwnerType::Quote),
				clone $bindingSettings
			))
				->setStorageStrategy(new StorageStrategy\ContactToFactory($quoteFactory)),

			(new Relation(
				new RelationIdentifier(\CCrmOwnerType::Contact, \CCrmOwnerType::Deal),
				clone $bindingSettings
			))
				->setStorageStrategy(new StorageStrategy\EntityBinding(
					[DealContactTable::class, 'getDealContactIDs'],
					[DealContactTable::class, 'getContactDealIDs'],
					[DealContactTable::class, 'bindContactIDs'],
					[DealContactTable::class, 'unbindContactIDs'],
					static function($fromId, $toId) {
						DealContactTable::rebindAllDeals($fromId, $toId);
						\CCrmDeal::Rebind(\CCrmOwnerType::Contact, $fromId, $toId);
					}
				)),

			(new Relation(
				new RelationIdentifier(\CCrmOwnerType::Contact, \CCrmOwnerType::Lead),
				clone $bindingSettings
			))
				->setStorageStrategy(new StorageStrategy\EntityBinding(
					[LeadContactTable::class, 'getLeadContactIDs'],
					[LeadContactTable::class, 'getContactLeadIDs'],
					[LeadContactTable::class, 'bindContactIDs'],
					[LeadContactTable::class, 'unbindContactIDs'],
					static function($fromId, $toId) {
						LeadContactTable::rebindAllLeads($fromId, $toId);
						\CCrmLead::Rebind(\CCrmOwnerType::Contact, $fromId, $toId);
					}
				)),

			(new Relation(
				new RelationIdentifier(\CCrmOwnerType::Contact, \CCrmOwnerType::Order),
				clone $bindingSettings
			))
				->setStorageStrategy(new StorageStrategy\ContactToOrder()),
			//endregion

			//region Company child predefined relations
			(new Relation(
				new RelationIdentifier(\CCrmOwnerType::Company, \CCrmOwnerType::Lead),
				clone $bindingSettings
			))
				->setStorageStrategy(new StorageStrategy\Compatible(\CCrmLead::class, 'COMPANY_ID')),

			(new Relation(
				new RelationIdentifier(\CCrmOwnerType::Company, \CCrmOwnerType::Deal),
				clone $bindingSettings
			))
				->setStorageStrategy(new StorageStrategy\Compatible(\CCrmDeal::class, 'COMPANY_ID')),

			(new Relation(
				new RelationIdentifier(\CCrmOwnerType::Company, \CCrmOwnerType::Quote),
				clone $bindingSettings
			))
				->setStorageStrategy(new StorageStrategy\Factory($quoteFactory, Item::FIELD_NAME_COMPANY_ID)),

			(new Relation(
				new RelationIdentifier(\CCrmOwnerType::Company, \CCrmOwnerType::Invoice),
				clone $bindingSettings
			))
				->setStorageStrategy(new StorageStrategy\Compatible(\CCrmInvoice::class, 'UF_COMPANY_ID')),

			(new Relation(
				new RelationIdentifier(\CCrmOwnerType::Company, \CCrmOwnerType::Contact),
				clone $bindingSettings
			))
				->setStorageStrategy(new StorageStrategy\EntityBinding(
					[ContactCompanyTable::class, 'getContactCompanyIDs'],
					[ContactCompanyTable::class, 'getCompanyContactIDs'],
					[ContactCompanyTable::class, 'bindCompanyIDs'],
					[ContactCompanyTable::class, 'unbindCompanyIDs'],
					[ContactCompanyTable::class, 'rebindAllContacts']
				)),

			(new Relation(
				new RelationIdentifier(\CCrmOwnerType::Company, \CCrmOwnerType::Order),
				clone $bindingSettings
			))
				->setStorageStrategy(new StorageStrategy\CompanyToOrder()),
			//endregion

			//region Deal child predefined relations
			(new Relation(
				new RelationIdentifier(\CCrmOwnerType::Deal, \CCrmOwnerType::Quote),
				clone $conversionSettings
			))
				->setStorageStrategy(new StorageStrategy\Factory($quoteFactory, Item\Quote::FIELD_NAME_DEAL_ID)),

			(new Relation(
				new RelationIdentifier(\CCrmOwnerType::Deal, \CCrmOwnerType::Invoice),
				clone $conversionSettings
			))
				->setStorageStrategy(new StorageStrategy\Compatible(\CCrmInvoice::class, 'UF_DEAL_ID')),

			(new Relation(
				new RelationIdentifier(\CCrmOwnerType::Deal, \CCrmOwnerType::Order),
				clone $bindingSettings
			))
				->setStorageStrategy(new StorageStrategy\EntityToOrder()),
			//endregion

			//region Lead child predefined relations
			(new Relation(
				new RelationIdentifier(\CCrmOwnerType::Lead, \CCrmOwnerType::Company),
				clone $conversionSettings
			))
				->setStorageStrategy(new StorageStrategy\Compatible(\CCrmCompany::class, 'LEAD_ID')),

			(new Relation(
				new RelationIdentifier(\CCrmOwnerType::Lead, \CCrmOwnerType::Contact),
				clone $conversionSettings
			))
				->setStorageStrategy(new StorageStrategy\Compatible(\CCrmContact::class, 'LEAD_ID')),

			(new Relation(
				new RelationIdentifier(\CCrmOwnerType::Lead, \CCrmOwnerType::Deal),
				clone $conversionSettings
			))
				->setStorageStrategy(new StorageStrategy\Compatible(\CCrmDeal::class, 'LEAD_ID')),

			(new Relation(
				new RelationIdentifier(\CCrmOwnerType::Lead, \CCrmOwnerType::Quote),
				clone $bindingSettings
			))
				->setStorageStrategy(new StorageStrategy\Factory($quoteFactory, Item\Quote::FIELD_NAME_LEAD_ID)),
			//endregion

			//region Quote child predefined relations
			(new Relation(
				new RelationIdentifier(\CCrmOwnerType::Quote, \CCrmOwnerType::Deal),
				clone $conversionSettings
			))
				->setStorageStrategy(new StorageStrategy\Compatible(\CCrmDeal::class, 'QUOTE_ID')),

			(new Relation(
				new RelationIdentifier(\CCrmOwnerType::Quote, \CCrmOwnerType::Invoice),
				clone $conversionSettings
			))
				->setStorageStrategy(new StorageStrategy\Compatible(\CCrmInvoice::class, 'UF_QUOTE_ID')),
			//endregion
		];

		//region SmartInvoice predefined relations
		$factory = Container::getInstance()->getFactory(\CCrmOwnerType::SmartInvoice);
		if ($factory)
		{

			$predefinedRelations[] =
				(new Relation(
					new RelationIdentifier(\CCrmOwnerType::SmartInvoice, \CCrmOwnerType::Order),
					clone $bindingSettings
				))
				->setStorageStrategy(new StorageStrategy\EntityToOrder())
			;

			$predefinedRelations[] =
				(new Relation(
					new RelationIdentifier(\CCrmOwnerType::Contact, \CCrmOwnerType::SmartInvoice),
					clone $bindingSettings
				))
				->setStorageStrategy(new StorageStrategy\ContactToFactory($factory))
			;

			$predefinedRelations[] =
				(new Relation(
					new RelationIdentifier(\CCrmOwnerType::Company, \CCrmOwnerType::SmartInvoice),
					clone $bindingSettings
				))
				->setStorageStrategy(new StorageStrategy\Factory($factory, Item::FIELD_NAME_COMPANY_ID))
			;
		}
		//endregion

		//@TODO collect predefined relations across modules via event
		if (
			Loader::includeModule('catalog')
			&& Manager::isActiveProviderByModule('crm')
		)
		{
			$predefinedRelations[] =
				(new Relation(
					new RelationIdentifier(
						\CCrmOwnerType::Company,
						\CCrmOwnerType::StoreDocument
					),
					clone $bindingSettings
				))
					->setStorageStrategy(new DocumentRelationStorageStrategy(\CCrmOwnerType::Company));

			$predefinedRelations[] =
				(new Relation(
					new RelationIdentifier(
						\CCrmOwnerType::Contact,
						\CCrmOwnerType::StoreDocument
					),
					clone $bindingSettings
				))
					->setStorageStrategy(new DocumentRelationStorageStrategy(\CCrmOwnerType::Contact));
		}

		$this->mixinPredefinedRelationsForDynamic($predefinedRelations);

		$this->predefinedRelations = new Relation\Collection($predefinedRelations);

		return $this->predefinedRelations;
	}

	protected function mixinPredefinedRelationsForDynamic(array &$predefinedRelations): void
	{
		$typesMap = Container::getInstance()->getDynamicTypesMap();
		$typesMap->load([
			'isLoadStages' => false,
			'isLoadCategories' => false,
		]);

		$bindingSettings =
			(new Settings())
				->setIsPredefined(true)
				->setIsChildrenListEnabled(true)
				->setRelationType(RelationType::BINDING)
		;

		foreach ($typesMap->getTypes() as $type)
		{
			$factory = Container::getInstance()->getDynamicFactoryByType($type);
			if ($factory->isPaymentsEnabled())
			{
				$predefinedRelations[] =
					(new Relation(
						new RelationIdentifier($type->getEntityTypeId(), \CCrmOwnerType::Order),
						clone $bindingSettings
					))
						->setStorageStrategy(new StorageStrategy\EntityToOrder())
				;
			}

			if (!$type->getIsClientEnabled())
			{
				continue;
			}

			$predefinedRelations[] =
				(new Relation(
					new RelationIdentifier(\CCrmOwnerType::Contact, $type->getEntityTypeId()),
					clone $bindingSettings
				))
				->setStorageStrategy(new StorageStrategy\ContactToFactory($factory))
			;

			$predefinedRelations[] =
				(new Relation(
					new RelationIdentifier(\CCrmOwnerType::Company, $type->getEntityTypeId()),
					clone $bindingSettings
				))
				->setStorageStrategy(new StorageStrategy\Factory($factory, Item::FIELD_NAME_COMPANY_ID))
			;
		}
	}

	protected function getCustomRelations(): Collection
	{
		if ($this->customRelations === null)
		{
			$collection = $this->mapTableClass::getList([
				'cache' => [
					'ttl' => 86400,
				],
			])->fetchCollection();
			foreach ($collection as $entityObject)
			{
				if (
					!\CCrmOwnerType::IsDefined($entityObject->getSrcTypeId())
					|| !\CCrmOwnerType::IsDefined($entityObject->getDstTypeId())
					)
				{
					$collection->remove($entityObject);
				}
			}

			$this->customRelations = $this->ormCollectionToRelationCollection($collection);
		}

		return $this->customRelations;
	}

	/**
	 * Returns parent relations that were created via RelationManager::bindTypes
	 *
	 * @param int $childEntityTypeId
	 *
	 * @return Relation\Collection
	 */
	protected function getCustomParentRelations(int $childEntityTypeId): Relation\Collection
	{
		return $this->getCustomRelations()->filterByChildEntityTypeId($childEntityTypeId);
	}

	/**
	 * Returns child relations that were created via RelationManager::bindTypes
	 *
	 * @param int $parentEntityTypeId
	 *
	 * @return Relation\Collection
	 */
	protected function getCustomChildRelations(int $parentEntityTypeId): Relation\Collection
	{
		return $this->getCustomRelations()->filterByParentEntityTypeId($parentEntityTypeId);
	}

	protected function ormCollectionToRelationCollection(
		EO_EntityConversionMap_Collection $collection
	): Relation\Collection
	{
		$relations = new Relation\Collection();
		foreach ($collection as $entityObject)
		{
			$relation = new Relation(
				new RelationIdentifier(
					$entityObject->getSrcTypeId(),
					$entityObject->getDstTypeId()
				),
				Settings::createByEntityRelationObject($entityObject)
			);
			$relation->setStorageStrategy(new StorageStrategy\EntityRelationTable());

			$relations->add($relation);
		}

		return $relations;
	}

	/**
	 * Returns all ItemIdentifier objects of items that are bound to the provided item
	 * The items are collected from all the relations that mention $identifier->getEntityTypeId
	 *
	 * @param ItemIdentifier $identifier
	 *
	 * @return ItemIdentifier[]
	 */
	public function getElements(ItemIdentifier $identifier): array
	{
		return array_unique(
			array_merge($this->getParentElements($identifier), $this->getChildElements($identifier))
		);
	}

	/**
	 * Returns all ItemIdentifier objects of items that are parents to the provided child
	 * The items are collected from all the relations
	 * that mention $child->getEntityTypeId as Relation::getChildEntityTypeId
	 *
	 * @param ItemIdentifier $child
	 *
	 * @return ItemIdentifier[]
	 */
	public function getParentElements(ItemIdentifier $child): array
	{
		$parentsArrays = [];
		foreach ($this->getParentRelations($child->getEntityTypeId()) as $relation)
		{
			$parentsArrays[] = $relation->getParentElements($child);
		}

		return $this->flattenArray($parentsArrays);
	}

	/**
	 * Returns all ItemIdentifier objects of items that are children to the provided parent
	 * The items are collected from all the relations
	 * that mention $parent->getEntityTypeId as Relation::getParentEntityTypeID
	 *
	 * @param ItemIdentifier $parent
	 *
	 * @return ItemIdentifier[]
	 */
	public function getChildElements(ItemIdentifier $parent): array
	{
		$childrenArrays = [];
		foreach ($this->getChildRelations($parent->getEntityTypeId()) as $relation)
		{
			$childrenArrays[] = $relation->getChildElements($parent);
		}

		return $this->flattenArray($childrenArrays);
	}

	/**
	 * Is used to avoid calling 'array_merge' in a loop
	 *
	 * @param array $arrayOfArrays
	 *
	 * @return array
	 */
	protected function flattenArray(array $arrayOfArrays): array
	{
		$flatArray = [];
		if (!empty($arrayOfArrays))
		{
			$flatArray = array_merge(...$arrayOfArrays);
		}

		return $flatArray;
	}

	/**
	 * @param int $parentEntityTypeId
	 * @param int $parentEntityId
	 * @param bool $isNew
	 *
	 * @return array
	 */
	public function getRelationTabsForDynamicChildren(
		int $parentEntityTypeId,
		int $parentEntityId,
		bool $isNew = false
	): array
	{
		$tabCodes = $this->getRelationTabCodes($parentEntityTypeId);

		$result = [];
		foreach ($tabCodes as $tabCode => $entityTypeId)
		{
			$router = Container::getInstance()->getRouter();
			$serviceUrl = $router->getChildrenItemsListUrl(
				$entityTypeId,
				$parentEntityTypeId,
				$parentEntityId
			);
			$factory = Container::getInstance()->getFactory($entityTypeId);
			if ($factory && $serviceUrl)
			{
				$result[] = [
					'id' => $tabCode,
					'name' => $factory->getEntityDescriptionInPlural(),
					'loader' => [
						'serviceUrl' => $serviceUrl,
						'componentData' => [
							'template' => '',
							'signedParameters' => $router->signChildrenItemsComponentParams(
								$entityTypeId,
								[
									'GRID_ID_SUFFIX' => 'PARENT_' . \CCrmOwnerType::ResolveName($parentEntityTypeId) . '_DETAILS',
									'TAB_ID' => $tabCode,
									'ENABLE_TOOLBAR' => true,
									'PRESERVE_HISTORY' => true,
									'PARENT_ENTITY_TYPE_ID' => $parentEntityTypeId,
									'PARENT_ENTITY_ID' => $parentEntityId,
								]
							)
						]
					],
					'enabled' => !$isNew,
				];
			}
		}

		return $result;
	}

	/**
	 * @param int $parentEntityTypeId
	 *
	 * @return array
	 */
	protected function getRelationTabCodes(int $parentEntityTypeId): array
	{
		$relations = $this->getChildRelations($parentEntityTypeId);

		$tabCodes = [];
		foreach ($relations as $relation)
		{
			if ($this->isShowChildrenTab($parentEntityTypeId, $relation))
			{
				$childEntityTypeId = $relation->getChildEntityTypeId();
				$tabCode = mb_strtolower(
					self::TAB_NAME_RELATION
					. \CCrmOwnerType::ResolveName($childEntityTypeId)
				);
				$tabCodes[$tabCode] = $childEntityTypeId;
			}
		}

		return $tabCodes;
	}

	protected function isShowChildrenTab(int $parentEntityTypeId, Relation $relation): bool
	{
		if (!$relation->isChildrenListEnabled())
		{
			return false;
		}

		if (!$relation->isPredefined())
		{
			return true;
		}

		return (
			isset($this->getClientFieldEntityTypeIds()[$parentEntityTypeId])
			&& \CCrmOwnerType::isUseDynamicTypeBasedApproach($relation->getChildEntityTypeId())
		);
	}
}
