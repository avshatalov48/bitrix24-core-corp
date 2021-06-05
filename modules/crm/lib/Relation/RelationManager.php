<?php

namespace Bitrix\Crm\Relation;

use Bitrix\Crm\Binding\ContactCompanyTable;
use Bitrix\Crm\Binding\DealContactTable;
use Bitrix\Crm\Binding\LeadContactTable;
use Bitrix\Crm\Conversion\Entity\EntityConversionMapTable;
use Bitrix\Crm\Conversion\Entity\EO_EntityConversionMap;
use Bitrix\Crm\Conversion\Entity\EO_EntityConversionMap_Collection;
use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Relation;
use Bitrix\Crm\RelationIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\EditorAdapter;
use Bitrix\Crm\Settings\DynamicSettings;
use Bitrix\Crm\Settings\QuoteSettings;
use Bitrix\Main\Engine\Router;
use Bitrix\Main\Engine\UrlManager;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

class RelationManager
{
	public const ERROR_CODE_BIND_TYPES_TYPES_ALREADY_BOUND = 'CRM_BIND_TYPES_TYPES_ALREADY_BOUND';
	public const ERROR_CODE_UNBIND_TYPES_TYPES_NOT_BOUND = 'CRM_UNBIND_TYPES_TYPES_NOT_BOUND';
	public const ERROR_CODE_UNBIND_TYPES_RELATION_IS_PREDEFINED = 'CRM_UNBIND_TYPES_RELATION_IS_PREDEFINED';
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

	/**
	 * Return array of entities that can be bind.
	 *
	 * @param int|null $currentTypeId
	 * @return array
	 */
	protected function getAvailableForBindingEntityTypes(): array
	{
		if ($this->availableEntityTypes === null)
		{
			$this->availableEntityTypes = [];
			$entityTypeIds = [
				\CCrmOwnerType::Lead => \CCrmOwnerType::Lead,
				\CCrmOwnerType::Deal => \CCrmOwnerType::Deal,
				\CCrmOwnerType::Order => \CCrmOwnerType::Order,
			];
			if (QuoteSettings::getCurrent()->isFactoryEnabled())
			{
				$entityTypeIds[\CCrmOwnerType::Quote] = \CCrmOwnerType::Quote;
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

	public function getAvailableForParentBindingEntityTypes(?int $currentEntityId = null): array
	{
		$availableTypes = $this->getAvailableForBindingEntityTypes();
		if ($currentEntityId)
		{
			unset($availableTypes[$currentEntityId]);
		}

		return $availableTypes;
	}

	public function getAvailableForChildBindingEntityTypes(?int $currentEntityId = null): array
	{
		$availableTypes = $this->getAvailableForBindingEntityTypes();
		if ($currentEntityId)
		{
			unset($availableTypes[$currentEntityId]);
		}
		// for now only dynamic types available
		unset(
			$availableTypes[\CCrmOwnerType::Lead],
			$availableTypes[\CCrmOwnerType::Deal],
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
			->setIsChildrenListEnabled($relation->isChildrenListEnabled())
			->setRelationType(static::RELATION_TYPE)
		;

		return $this->saveEntityObject($entityObject);
	}

	public function updateTypesBinding(Relation $relation): Result
	{
		$entityObject = $this->mapTableClass::getByPrimary([
			'SRC_TYPE_ID' => $relation->getParentEntityTypeId(),
			'DST_TYPE_ID' => $relation->getChildEntityTypeId(),
		])->fetchObject();
		if (!$entityObject)
		{
			return (new Result())->addError(
				new Error(
					'Record containing relation is not found',
					static::ERROR_CODE_UPDATE_TYPES_RELATION_NOT_FOUND
				)
			);
		}

		$entityObject->setIsChildrenListEnabled($relation->isChildrenListEnabled());

		return $this->saveEntityObject($entityObject);
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
		$parentRelations = $this->getParentRelations($entityTypeId);

		return $parentRelations->merge($this->getChildRelations($entityTypeId));
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
		$quoteFactory = Container::getInstance()->getFactory(\CCrmOwnerType::Quote);

		$predefinedRelations = [
			//region Contact child predefined relations
			Relation::createPredefined(\CCrmOwnerType::Contact, \CCrmOwnerType::Invoice)
				->setStorageStrategy(new StorageStrategy\Compatible(\CCrmInvoice::class, 'UF_CONTACT_ID')),

			Relation::createPredefined(\CCrmOwnerType::Contact, \CCrmOwnerType::Quote)
				->setStorageStrategy(new StorageStrategy\ContactToFactory($quoteFactory)),

			Relation::createPredefined(\CCrmOwnerType::Contact, \CCrmOwnerType::Deal)
				->setStorageStrategy(new StorageStrategy\EntityBinding(
					[DealContactTable::class, 'getDealContactIDs'],
					[DealContactTable::class, 'getContactDealIDs'],
					[DealContactTable::class, 'bindContactIDs'],
					[DealContactTable::class, 'unbindContactIDs']
				)),

			Relation::createPredefined(\CCrmOwnerType::Contact, \CCrmOwnerType::Company)
				->setStorageStrategy(new StorageStrategy\EntityBinding(
					[ContactCompanyTable::class, 'getCompanyContactIDs'],
					[ContactCompanyTable::class, 'getContactCompanyIDs'],
					[ContactCompanyTable::class, 'bindContactIDs'],
					[ContactCompanyTable::class, 'unbindContactIDs']
				)),

			Relation::createPredefined(\CCrmOwnerType::Contact, \CCrmOwnerType::Lead)
				->setStorageStrategy(new StorageStrategy\EntityBinding(
					[LeadContactTable::class, 'getLeadContactIDs'],
					[LeadContactTable::class, 'getContactLeadIDs'],
					[LeadContactTable::class, 'bindContactIDs'],
					[LeadContactTable::class, 'unbindContactIDs']
				)),

			Relation::createPredefined(\CCrmOwnerType::Contact, \CCrmOwnerType::Order)
				->setStorageStrategy(new StorageStrategy\ContactToOrder()),
			//endregion

			//region Company child predefined relations
			Relation::createPredefined(\CCrmOwnerType::Company, \CCrmOwnerType::Deal)
				->setStorageStrategy(new StorageStrategy\Compatible(\CCrmDeal::class, 'COMPANY_ID')),

			Relation::createPredefined(\CCrmOwnerType::Company, \CCrmOwnerType::Quote)
				->setStorageStrategy(new StorageStrategy\Factory($quoteFactory, Item::FIELD_NAME_COMPANY_ID)),

			Relation::createPredefined(\CCrmOwnerType::Company, \CCrmOwnerType::Invoice)
				->setStorageStrategy(new StorageStrategy\Compatible(\CCrmInvoice::class, 'UF_COMPANY_ID')),

			Relation::createPredefined(\CCrmOwnerType::Company, \CCrmOwnerType::Contact)
				->setStorageStrategy(new StorageStrategy\EntityBinding(
					[ContactCompanyTable::class, 'getContactCompanyIDs'],
					[ContactCompanyTable::class, 'getCompanyContactIDs'],
					[ContactCompanyTable::class, 'bindCompanyIDs'],
					[ContactCompanyTable::class, 'unbindCompanyIDs']
				)),

			Relation::createPredefined(\CCrmOwnerType::Company, \CCrmOwnerType::Order)
				->setStorageStrategy(new StorageStrategy\CompanyToOrder()),
			//endregion

			//region Deal child predefined relations
			Relation::createPredefined(\CCrmOwnerType::Deal, \CCrmOwnerType::Quote)
				->setStorageStrategy(new StorageStrategy\Factory($quoteFactory, Item\Quote::FIELD_NAME_DEAL_ID)),

			Relation::createPredefined(\CCrmOwnerType::Deal, \CCrmOwnerType::Invoice)
				->setStorageStrategy(new StorageStrategy\Compatible(\CCrmInvoice::class, 'UF_DEAL_ID')),

			Relation::createPredefined(\CCrmOwnerType::Deal, \CCrmOwnerType::Order)
				->setStorageStrategy(new StorageStrategy\DealToOrder()),
			//endregion

			//region Lead child predefined relations
			Relation::createPredefined(\CCrmOwnerType::Lead, \CCrmOwnerType::Company)
				->setStorageStrategy(new StorageStrategy\Compatible(\CCrmCompany::class, 'LEAD_ID')),

			Relation::createPredefined(\CCrmOwnerType::Lead, \CCrmOwnerType::Contact)
				->setStorageStrategy(new StorageStrategy\Compatible(\CCrmContact::class, 'LEAD_ID')),

			Relation::createPredefined(\CCrmOwnerType::Lead, \CCrmOwnerType::Deal)
				->setStorageStrategy(new StorageStrategy\Compatible(\CCrmDeal::class, 'LEAD_ID')),

			Relation::createPredefined(\CCrmOwnerType::Lead, \CCrmOwnerType::Quote)
				->setStorageStrategy(new StorageStrategy\Factory($quoteFactory, Item\Quote::FIELD_NAME_LEAD_ID)),
			//endregion

			//region Quote child predefined relations
			Relation::createPredefined(\CCrmOwnerType::Quote, \CCrmOwnerType::Deal)
				->setStorageStrategy(new StorageStrategy\Compatible(\CCrmDeal::class, 'QUOTE_ID')),

			Relation::createPredefined(\CCrmOwnerType::Quote, \CCrmOwnerType::Invoice)
				->setStorageStrategy(new StorageStrategy\Compatible(\CCrmInvoice::class, 'UF_QUOTE_ID')),
			//endregion
		];

		$this->mixinPredefinedRelationsForDynamic($predefinedRelations);

		return new Relation\Collection($predefinedRelations);
	}

	protected function mixinPredefinedRelationsForDynamic(array &$predefinedRelations): void
	{
		$typesMap = Container::getInstance()->getDynamicTypesMap();
		$typesMap->load([
			'isLoadStages' => false,
			'isLoadCategories' => false,
		]);

		foreach ($typesMap->getTypes() as $type)
		{
			if (!$type->getIsClientEnabled())
			{
				continue;
			}

			$factory = Container::getInstance()->getDynamicFactoryByType($type);

			$predefinedRelations[] =
				Relation::createPredefined(\CCrmOwnerType::Contact, $type->getEntityTypeId())
					->setStorageStrategy(new StorageStrategy\ContactToFactory($factory))
					->setChildrenListEnabled(true)
			;

			$predefinedRelations[] =
				Relation::createPredefined(\CCrmOwnerType::Company, $type->getEntityTypeId())
					->setStorageStrategy(new StorageStrategy\Factory($factory, Item::FIELD_NAME_COMPANY_ID))
					->setChildrenListEnabled(true)
			;
		}
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
		$collection =
			$this->mapTableClass::getList([
				'filter' => [
					'=DST_TYPE_ID' => $childEntityTypeId,
				],
			])
				->fetchCollection()
		;

		return $this->ormCollectionToRelationCollection($collection);
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
		$collection =
			$this->mapTableClass::getList([
				'filter' => [
					'=SRC_TYPE_ID' => $parentEntityTypeId,
				],
			])
				->fetchCollection()
		;

		return $this->ormCollectionToRelationCollection($collection);
	}

	protected function ormCollectionToRelationCollection(
		EO_EntityConversionMap_Collection $collection
	): Relation\Collection
	{
		$relations = new Relation\Collection();
		foreach ($collection as $entityObject)
		{
			$relation = Relation::create(
				$entityObject->getSrcTypeId(),
				$entityObject->getDstTypeId(),
				$entityObject->getIsChildrenListEnabled()
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
		if (!DynamicSettings::getCurrent()->isEnabled())
		{
			return [];
		}
		$tabCodes = $this->getRelationTabCodes($parentEntityTypeId);

		$result = [];
		foreach ($tabCodes as $tabCode => $entityTypeId)
		{
			$detailComponent = EditorAdapter::getDetailComponentName($entityTypeId);
			$factory = Container::getInstance()->getFactory($entityTypeId);
			if ($entityTypeId === \CCrmOwnerType::Quote)
			{
				$result[] = [
					'id' => $tabCode,
					'name' => \CCrmOwnerType::GetDescription(\CCrmOwnerType::Quote),
					'loader' => [
						'serviceUrl' => '/bitrix/components/bitrix/crm.quote.list/lazyload.ajax.php?&site'.SITE_ID.'&'.bitrix_sessid_get(),
						'componentData' => [
							'template' => '',
							'params' => [
								'GRID_ID_SUFFIX' => 'PARENT_' . \CCrmOwnerType::ResolveName($parentEntityTypeId) . '_DETAILS',
								'TAB_ID' => $tabCode,
								'ENABLE_TOOLBAR' => true,
								'PRESERVE_HISTORY' => true,
								'PARENT_ENTITY_TYPE_ID' => $parentEntityTypeId,
								'PARENT_ENTITY_ID' => $parentEntityId,
							]
						]
					]
				];
			}
			elseif ($factory && $detailComponent)
			{
				$serviceUrl = UrlManager::getInstance()->create('children', [
					'c' => $detailComponent,
					'mode' => Router::COMPONENT_MODE_CLASS,
					'sessid' => bitrix_sessid(),
				]);
				$result[] = [
					'id' => $tabCode,
					'name' => $factory->getEntityDescription(),
					'loader' => [
						'serviceUrl' => $serviceUrl->getLocator(),
						'componentData' => [
							'isComponentAjaxAction' => true,
							'detailComponent' => $detailComponent,
							'params' => [
								'PARENT_ENTITY_TYPE_ID' => $parentEntityTypeId,
								'PARENT_ENTITY_ID' => $parentEntityId,
								'ENTITY_TYPE_ID' => $entityTypeId,
							],
						],
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
			&& \CCrmOwnerType::isPossibleDynamicTypeId($relation->getChildEntityTypeId())
		);
	}
}
