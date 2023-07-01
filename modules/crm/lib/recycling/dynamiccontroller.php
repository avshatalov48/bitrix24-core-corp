<?php

namespace Bitrix\Crm\Recycling;

use Bitrix\Crm;
use Bitrix\Main;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Loader;
use Bitrix\Recyclebin\Internals\Models\RecyclebinTable;

Main\Localization\Loc::loadMessages(__FILE__);

class DynamicController extends BaseController
{
	use ActivityControllerMixin;
	use ProductRowControllerMixin;
	use ObserverControllerMixin;
	use ChatControllerMixin;
	use WaitingControllerMixin;

	protected $entityTypeId;

	/**
	 * Get an instance of the controller
	 *
	 * @param int $entityTypeId
	 * @return static
	 */
	public static function getInstance(int $entityTypeId): self
	{
		$instance = ServiceLocator::getInstance()->get('crm.recycling.dynamicController');
		$instance->setEntityTypeID($entityTypeId);

		return $instance;
	}

	/**
	 * @param int $entityTypeId
	 */
	public function setEntityTypeID(int $entityTypeId): void
	{
		$this->entityTypeId = $entityTypeId;
	}

	/**
	 * @return int
	 */
	public function getEntityTypeID(): int
	{
		return $this->entityTypeId;
	}

	/**
	 * @return int
	 */
	public function getSuspendedEntityTypeID(): int
	{
		return \CCrmOwnerType::ResolveSuspended($this->getEntityTypeID());
	}

	/**
	 * @return string
	 */
	public function getRecyclebinEntityTypeName(): string
	{
		return Crm\Integration\Recyclebin\Dynamic::getEntityName($this->entityTypeId);
	}

	//region ProductRowController

	/**
	 * @inheritDoc
	 */
	public function getProductRowOwnerType(): string
	{
		return \CCrmOwnerTypeAbbr::ResolveByTypeID($this->getEntityTypeID());
	}

	/**
	 * @inheritDoc
	 */
	public function getProductRowSuspendedOwnerType(): string
	{
		return \CCrmOwnerTypeAbbr::ResolveByTypeID($this->getSuspendedEntityTypeID());
	}

	//endregion

	public function getActivityOwnerNotFoundMessage($entityTypeID, $entityID, array $params)
	{
		$entityTitle = Crm\Integration\Recyclebin\RecyclingManager::resolveEntityTitle(
			$entityTypeID,
			$entityID
		);

		return Main\Localization\Loc::getMessage(
			'CRM_DYNAMIC_CTRL_ACTIVITY_OWNER_NOT_FOUND',
			[
				'#OWNER_TITLE#' => $entityTitle,
				'#OWNER_ID#' => $entityID,
				'#ID#' => ($params['ID'] ?? ''),
				'#TITLE#' => ($params['title'] ?? '')
			]
		);
	}

	/**
	 * Returns array of field names that are allowed in entity data
	 *
	 * @return string[]
	 */
	public function getFieldNames(): array
	{
		return $this->getFactory()->getFieldsCollection()->getFieldNameList();
	}

	public function prepareEntityData($entityId, array $params = []): array
	{
		$fields = (isset($params['FIELDS']) && is_array($params['FIELDS']) ? $params['FIELDS'] : null);

		if(empty($fields))
		{
			throw new Main\ObjectNotFoundException("Could not find entity: #{$entityId}.");
		}

		$slots = ['FIELDS' => $this->filterEntityDataFields($fields)];

		$companyId = (int)($fields['COMPANY_ID'] ?? 0);
		if($companyId > 0)
		{
			$slots['COMPANY_ID'] = $companyId;
		}

		$item = $this->getFactory()->getItem($entityId);
		$contacts = $item->getContactBindings();
		if(!empty($contacts))
		{
			foreach($contacts as $contact)
			{
				$slots['CONTACT_IDS'][] = (int)$contact['CONTACT_ID'];
			}
		}

		$slots = array_merge($slots, $this->prepareActivityData($entityId, $params));

		return [
			'TITLE' => $item->getHeading(),
			'SLOTS' => $slots
		];
	}

	protected function filterEntityDataFields(array $fields): array
	{
		$allowedFieldNames = $this->getFieldNames();

		return array_filter(
			$fields,
			static function ($fieldName) use ($allowedFieldNames): bool {
				return in_array((string)$fieldName, $allowedFieldNames, true);
			},
			ARRAY_FILTER_USE_KEY
		);
	}

	/**
	 * @param int $entityID
	 * @param array $params
	 */
	public function moveToBin($entityID, array $params = []): void
	{
		if(!Main\Loader::includeModule('recyclebin'))
		{
			throw new Main\InvalidOperationException("Could not load module RecycleBin.");
		}

		$fields = isset($params['FIELDS']) && is_array($params['FIELDS']) ? $params['FIELDS'] : null;
		if(empty($fields))
		{
			$fields = $params['FIELDS'] = $this->getEntityFields($entityID);
		}

		if(empty($fields))
		{
			throw new Main\ObjectNotFoundException("Could not find entity: #{$entityID}.");
		}

		$entityData = $this->prepareEntityData($entityID, $params);

		$recyclingEntity = Crm\Integration\Recyclebin\Dynamic::createRecycleBinEntity(
			$entityID,
			$this->entityTypeId
		);
		$recyclingEntity->setTitle($entityData['TITLE']);

		$slots = (
		isset($entityData['SLOTS']) && is_array($entityData['SLOTS'])
			? $entityData['SLOTS']
			: []
		);

		$relations = DynamicRelationManager::getInstance($this->getEntityTypeID())
			->buildCollection($entityID, $slots);

		foreach($slots as $slotKey => $slotData)
		{
			$recyclingEntity->add($slotKey, $slotData);
		}

		$result = $recyclingEntity->save();
		$errors = $result->getErrors();
		if(!empty($errors))
		{
			throw new Main\SystemException($errors[0]->getMessage(), $errors[0]->getCode());
		}

		$recyclingEntityID = $recyclingEntity->getId();

		$this->suspendActivities($entityData, $entityID, $recyclingEntityID);
		$this->suspendDependenceElements($entityID, $recyclingEntityID);

		//region Relations
		foreach($relations as $relation)
		{
			/** @var Relation $relation */
			$relation->setRecycleBinID($this->getEntityTypeID(), $entityID, $recyclingEntityID);
			$relation->save();
		}

		DynamicRelationManager::getInstance($this->getEntityTypeID())
			->registerRecycleBin($recyclingEntityID, $entityID, $slots);
		//endregion
	}

	protected function getEntityFields(int $entityId): ?array
	{
		$item = $this->getFactory()->getItem($entityId);
		if ($item)
		{
			return $item->getCompatibleData();
		}

		return null;
	}

	protected function suspendDependenceElements(int $entityID, int $recyclingEntityID): void
	{
		$this->suspendTimeline($entityID, $recyclingEntityID);
		$this->suspendDocuments($entityID, $recyclingEntityID);
		$this->suspendLiveFeed($entityID, $recyclingEntityID);
		$this->suspendUtm($entityID, $recyclingEntityID);
		$this->suspendTracing($entityID, $recyclingEntityID);
		$this->suspendObservers($entityID, $recyclingEntityID);
		$this->suspendWaitings($entityID, $recyclingEntityID);
		$this->suspendChats($entityID, $recyclingEntityID);
		$this->suspendProductRows($entityID, $recyclingEntityID);
		$this->suspendScoringHistory($entityID, $recyclingEntityID);
		$this->suspendCustomRelations($entityID, $recyclingEntityID);
		$this->suspendBadges($entityID, $recyclingEntityID);
	}

	/**
	 * @param int $entityID
	 * @param array $params
	 * @return bool
	 */
	public function recover($entityID, array $params = []): bool
	{
		if($entityID <= 0)
		{
			return false;
		}

		$recyclingEntityID = (int)($params['ID'] ?? 0);
		if($recyclingEntityID <= 0)
		{
			return false;
		}

		$slots = ($params['SLOTS'] ?? null);
		if(!is_array($slots))
		{
			return false;
		}

		$fields = ($slots['FIELDS'] ?? null);
		if(!(is_array($fields) && !empty($fields)))
		{
			return false;
		}

		unset($fields['ID'], $fields['COMPANY_ID'], $fields['CONTACT_ID'], $fields['CONTACT_IDS'], $fields['PRODUCT_ROWS']);

		$relationMap = RelationMap::createByEntity($this->getEntityTypeID(), $entityID, $recyclingEntityID);
		$relationMap->build();

		DynamicRelationManager::getInstance($this->getEntityTypeID())
			->prepareRecoveryFields($fields, $relationMap);

		$item = $this->createItem($fields);

		$context = clone Crm\Service\Container::getInstance()->getContext();
		$context->setItemOption('PRESERVE_CONTENT_TYPE', true);

		$operation = $this->getFactory()->getRestoreOperation($item, $context);
		$operation
			->disableAllChecks()
		;

		$result = $operation->launch();
		if (!$result->isSuccess())
		{
			return false;
		}

		$newEntityID = $item->getId();
		if($newEntityID <= 0)
		{
			return false;
		}

		//region Relation
		Relation::updateEntityID($this->getEntityTypeID(), $entityID, $newEntityID, $recyclingEntityID);
		//endregion

		$this->recoverDependenceElements($recyclingEntityID, $newEntityID);

		$requisiteLinks = isset($slots['REQUISITE_LINKS']) ? $slots['REQUISITE_LINKS'] : null;
		if(is_array($requisiteLinks) && !empty($requisiteLinks))
		{
			for($i = 0, $length = count($requisiteLinks); $i < $length; $i++)
			{
				$requisiteLinks[$i]['ENTITY_TYPE_ID'] = $this->getEntityTypeID();
				$requisiteLinks[$i]['ENTITY_ID'] = $newEntityID;
			}
			Crm\EntityRequisite::setLinks($requisiteLinks);
		}
		$this->recoverActivities($recyclingEntityID, $entityID, $newEntityID, $params, $relationMap);

		//region Relation
		Relation::unregisterRecycleBin($recyclingEntityID);
		Relation::deleteJunks();
		//endregion

		return true;
	}

	protected function recoverDependenceElements(int $recyclingEntityID, int $newEntityID): void
	{
		$this->recoverTimeline($recyclingEntityID, $newEntityID);
		$this->recoverDocuments($recyclingEntityID, $newEntityID);
		$this->recoverLiveFeed($recyclingEntityID, $newEntityID);
		$this->recoverUtm($recyclingEntityID, $newEntityID);
		$this->recoverTracing($recyclingEntityID, $newEntityID);
		$this->recoverObservers($recyclingEntityID, $newEntityID);
		$this->recoverWaitings($recyclingEntityID, $newEntityID);
		$this->recoverChats($recyclingEntityID, $newEntityID);
		$this->recoverProductRows($recyclingEntityID, $newEntityID);
		$this->recoverScoringHistory($recyclingEntityID, $newEntityID);
		$this->recoverCustomRelations($recyclingEntityID, $newEntityID);
		$this->recoverBadges($recyclingEntityID, $newEntityID);
	}

	protected function createItem(array $fields): Crm\Item
	{
		$factory = $this->getFactory();

		$item = $factory->createItem();
		// remove parent field values, because actual values will be restored in recoverCustomRelations
		foreach ($fields as $name => $value)
		{
			if (Crm\Service\ParentFieldManager::isParentFieldName($name))
			{
				unset($fields[$name]);
			}
			$field = $factory->getFieldsCollection()->getField($name);
			if (
				$field
				&& $field->getType() === Crm\Field::TYPE_DATETIME
				&& !$field->isValueEmpty($value)
			)
			{
				if (is_array($value))
				{
					$values = [];
					foreach ($value as $singleValue)
					{
						$values[] = Main\Type\DateTime::createFromUserTime($singleValue);
					}
					$fields[$name] = $values;
				}
				else
				{
					$fields[$name] = Main\Type\DateTime::createFromUserTime($value);
				}
			}
		}
		$item->setFromCompatibleData($fields);

		if($item)
		{
			$categoryId = $item->getCategoryId();

			if(!$factory->getCategory((int)$categoryId))
			{
				$item->setCategoryId($factory->getDefaultCategory()->getId());
			}

			if(!$factory->getStage((string)$item->getStageId()))
			{
				$stages = $factory->getStages($item->getCategoryId())->getAll();
				$item->setStageId($stages[0]->getStatusId());
			}

			return $item;
		}

		throw new Main\ArgumentException('Could not create factory', 'entityTypeId');
	}

	/**
	 * @param int $entityID
	 * @param array $params
	 */
	public function erase($entityID, array $params = []): void
	{
		if($entityID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero.', 'entityID');
		}

		$recyclingEntityID = (int)($params['ID'] ?? 0);
		if($recyclingEntityID <= 0)
		{
			throw new Main\ArgumentException('Could not find parameter named: "ID".', 'params');
		}

		$relationMap = RelationMap::createByEntity($this->getEntityTypeID(), $entityID, $recyclingEntityID);
		$relationMap->build();

		$this->eraseActivities($recyclingEntityID, $params, $relationMap);
		$this->eraseDependenceElements($recyclingEntityID);

		Relation::deleteByRecycleBin($recyclingEntityID);
	}

	protected function eraseDependenceElements(int $recyclingEntityID): void
	{
		$this->eraseSuspendProductRows($recyclingEntityID);
		$this->eraseSuspendedTimeline($recyclingEntityID);
		$this->eraseSuspendedDocuments($recyclingEntityID);
		$this->eraseSuspendedLiveFeed($recyclingEntityID);
		$this->eraseSuspendedUtm($recyclingEntityID);
		$this->eraseSuspendedTracing($recyclingEntityID);
		$this->eraseSuspendedObservers($recyclingEntityID);
		$this->eraseSuspendedWaitings($recyclingEntityID);
		$this->eraseSuspendedChats($recyclingEntityID);
		$this->eraseSuspendedScoringHistory($recyclingEntityID);
		$this->eraseSuspendedCustomRelations($recyclingEntityID);
		$this->eraseSuspendedBadges($recyclingEntityID);
	}

	public function eraseAll(): void
	{
		if (Loader::includeModule('recyclebin'))
		{
			$entityType = Crm\Integration\Recyclebin\Dynamic::getEntityName($this->getEntityTypeID());
			Crm\Agent\Recyclebin\EraseStepper::bind(0, [$entityType]);
		}
	}

	public function countItemsInRecycleBin(): int
	{
		if (Loader::includeModule('recyclebin'))
		{
			return RecyclebinTable::getCount([
				'=MODULE_ID' => 'crm',
				'=ENTITY_TYPE' => $this->getRecyclebinEntityTypeName(),
			]);
		}

		return 0;
	}

	private function getFactory(): Crm\Service\Factory
	{
		$factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($this->getEntityTypeID());
		if (!$factory)
		{
			throw new Main\ObjectNotFoundException('No factory found');
		}

		return $factory;
	}
}
