<?php

namespace Bitrix\Crm\Merger;

use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Merger\ConflictResolver;
use Bitrix\Crm\Recovery;
use Bitrix\Crm\Relation\EntityRelationTable;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Tracking;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\SystemException;
use Bitrix\Crm\Timeline;
use Bitrix\Crm\Service\Container;
use CCrmActivity;
use CCrmEvent;
use CCrmProductRow;

class FactoryBasedMerger extends EntityMerger
{
	protected Factory $factory;

	public function __construct($entityTypeID, $userID, $enablePermissionCheck = false)
	{
		parent::__construct($entityTypeID, $userID, $enablePermissionCheck);

		$factory = Container::getInstance()->getFactory($this->entityTypeID);
		if (!$factory)
		{
			throw new ArgumentOutOfRangeException('entityTypeId');
		}

		$this->factory = $factory;
	}

	public function getFieldCaption(string $fieldId): string
	{
		return $this->factory->getFieldCaption($fieldId);
	}

	protected function getEntityFieldsInfo(): array
	{
		return $this->factory->getFieldsInfo();
	}

	protected function getEntityUserFieldsInfo(): array
	{
		return $this->factory->getUserFields();
	}

	protected function getEntityResponsibleID($entityID, $roleID): int
	{
		return $this->getItemWithException($entityID, $roleID)->getAssignedById();
	}

	protected function getEntityFields($entityID, $roleID): array
	{
		$data = $this->getItemWithException($entityID, $roleID)->toArray();

		return Container::getInstance()
			->getOrmObjectConverter()
			->convertKeysToUpperCase($data)
		;
	}

	protected function checkEntityReadPermission($entityID, $userPermissions): bool
	{
		$categoryId = $this->factory->getItemCategoryId((int)$entityID);

		return Container::getInstance()
			->getUserPermissions($userPermissions->GetUserID())
			->checkReadPermissions($this->entityTypeID, $entityID, $categoryId)
		;
	}

	protected function checkEntityUpdatePermission($entityID, $userPermissions): bool
	{
		$categoryId = $this->factory->getItemCategoryId((int)$entityID);

		return Container::getInstance()
			->getUserPermissions($userPermissions->GetUserID())
			->checkUpdatePermissions($this->entityTypeID, $entityID, $categoryId)
		;
	}

	protected function checkEntityDeletePermission($entityID, $userPermissions): bool
	{
		$categoryId = $this->factory->getItemCategoryId((int)$entityID);

		return Container::getInstance()
			->getUserPermissions($userPermissions->GetUserID())
			->checkDeletePermissions($this->entityTypeID, $entityID, $categoryId)
		;
	}

	protected function updateEntity($entityID, array &$fields, $roleID, array $options = array()): void
	{
		$item = $this
			->getItemWithException($entityID, $roleID)
			->setFromCompatibleData($fields)
		;

		$result = $this->factory
			->getUpdateOperation($item)
			->launch()
		;

		$this->throwExceptionIfResultFailed(
			$result,
			$entityID,
			$roleID,
			EntityMergerException::UPDATE_FAILED,
		);
	}

	protected function deleteEntity($entityID, $roleID, array $options = []): void
	{
		$item = $this->getItemWithException($entityID, $roleID);
		$result = $this->factory
			->getDeleteOperation($item)
			->launch()
		;

		$this->throwExceptionIfResultFailed(
			$result,
			$entityID,
			$roleID,
			EntityMergerException::DELETE_FAILED,
		);
	}

	/**
	 * @throws EntityMergerException
	 */
	protected function throwExceptionIfResultFailed(
		Result $result,
		int $entityId,
		int $roleId,
		int $exceptionCode,
	): void
	{
		if ($result->isSuccess())
		{
			return;
		}

		$lastErrorMessage = $result->getErrorMessages()[0];
		throw new EntityMergerException(
			$this->entityTypeID,
			$entityId,
			$roleId,
			$exceptionCode,
			'',
			0,
			new SystemException($lastErrorMessage),
		);
	}

	/**
	 * @throws EntityMergerException
	 */
	protected function getItemWithException(
		int $id,
		int $roleId,
	): Item
	{
		$item = $this->factory->getItem($id);
		if ($item === null)
		{
			throw new EntityMergerException(
				$this->entityTypeID,
				$id,
				$roleId,
				EntityMergerException::NOT_FOUND,
			);
		}

		return $item;
	}

	protected function prepareCollisionMessageFields(array &$collisions, array &$seed, array &$targ): ?array
	{
		return null;
	}

	protected function getFieldConflictResolver(string $fieldId, string $type): ConflictResolver\Base
	{
		$userDefinedResolver = static::getUserDefinedConflictResolver(
			$this->factory->getEntityTypeId(),
			$fieldId,
			$type,
		);

		if ($userDefinedResolver !== null)
		{
			return $userDefinedResolver;
		}

		// may be the fields need to know how they should be conflict resolve
		$ignoredFields = [
			Item::FIELD_NAME_TITLE,
			'ADDRESS_LOC_ADDR_ID',
			Item::FIELD_NAME_CONTACT_ID,
			Item::FIELD_NAME_TAX_VALUE,
			Item::FIELD_NAME_OPENED,
			Item::FIELD_NAME_OBSERVERS,
			Item::FIELD_NAME_STAGE_ID,
			Item\SmartInvoice::FIELD_NAME_ACCOUNT_NUMBER,
			Item\Quote::FIELD_NAME_NUMBER,
		];

		if (in_array($fieldId, $ignoredFields, true))
		{
			return new ConflictResolver\IgnoredField($fieldId);
		}

		if ($fieldId === Item::FIELD_NAME_NAME)
		{
			$resolver = new ConflictResolver\NameField($fieldId);
			$resolver->setRelatedFieldsCheckRequired(true);

			return $resolver;
		}

		return match($fieldId)
		{
			Item::FIELD_NAME_COMMENTS => new ConflictResolver\HtmlField($fieldId),
			Item::FIELD_NAME_SOURCE_ID => new ConflictResolver\SourceField($fieldId),
			Item::FIELD_NAME_SOURCE_DESCRIPTION => new ConflictResolver\TextField($fieldId),
			Item::FIELD_NAME_OPPORTUNITY => new ConflictResolver\OpportunityField($fieldId, $this->entityTypeID),
			Item::FIELD_NAME_SECOND_NAME,
			Item::FIELD_NAME_LAST_NAME => new ConflictResolver\NameField($fieldId),
			default => parent::getFieldConflictResolver($fieldId, $type),
		};
	}

	/**
	 * @throws SystemException
	 */
	protected static function checkEntityMergePreconditions(array $seed, array $targ): void
	{
		self::checkThatCategoryEquals($seed, $targ);
	}

	/**
	 * @throws SystemException
	 */
	protected static function checkThatCategoryEquals(array $seed, array $targ): void
	{
		$seedCategoryId = $seed[Item::FIELD_NAME_CATEGORY_ID] ?? null;
		$targCategoryId = $targ[Item::FIELD_NAME_CATEGORY_ID] ?? null;

		$isCategoryIdsNotNull = isset($seedCategoryId, $targCategoryId);
		$isCategoryIdsDifferent = $seedCategoryId !== $targCategoryId;

		if ($isCategoryIdsNotNull && $isCategoryIdsDifferent)
		{
			$message = Loc::getMessage('CRM_FACTORY_BASED_MERGER_ELEMENTS_IN_DIFFERENT_CATEGORY_ERROR');
			throw new SystemException($message);
		}
	}

	protected function setupRecoveryData(Recovery\EntityRecoveryData $recoveryData, array &$fields): void
	{
		if (isset($fields[Item::FIELD_NAME_TITLE]))
		{
			$recoveryData->setTitle($fields[Item::FIELD_NAME_TITLE]);
		}

		if (isset($fields[Item::FIELD_NAME_ASSIGNED]))
		{
			$recoveryData->setResponsibleID($fields[Item::FIELD_NAME_ASSIGNED]);
		}
	}

	protected function mergeBoundEntitiesBatch(
		array &$seeds,
		array &$targ,
		$skipEmpty = false,
		array $options = [],
	): void
	{
		$targ[Item::FIELD_NAME_OBSERVERS] = $this->getMergedObservers($seeds, $targ);
		$targ[Item::FIELD_NAME_PRODUCTS] = $this->getMergedProductRows($seeds, $targ);

		parent::mergeBoundEntitiesBatch($seeds, $targ, $skipEmpty, $options);
	}

	protected function getMergedObservers(array $seedItems, array $targetItem): array
	{
		if (!$this->factory->isObserversEnabled())
		{
			return [];
		}

		$observerIds = [];

		$items = [...$seedItems, $targetItem];
		foreach ($items as $item)
		{
			$itemObserverIds = $item[Item::FIELD_NAME_OBSERVERS] ?? [];
			array_push($observerIds, ...$itemObserverIds);
		}

		return array_unique($observerIds);
	}

	protected function getMergedProductRows(array $seedItems, array $targetItem): array
	{
		if (!$this->factory->isLinkWithProductsEnabled())
		{
			return [];
		}

		$items = [...$seedItems, $targetItem];
		$itemIds = array_column($items, Item::FIELD_NAME_ID);
		$itemIds = array_map(static fn (mixed $id) => (int)$id, $itemIds);

		$items = $this->factory->getItems([
			'select' => [Item::FIELD_NAME_ID, Item::FIELD_NAME_PRODUCTS],
			'filter' => ['@' . Item::FIELD_NAME_ID => $itemIds],
		]);

		$resultProductRows = [];
		foreach ($items as $item)
		{
			$productRows = $item->getProductRows()?->toArray() ?? [];
			CCrmProductRow::Merge($productRows, $resultProductRows);
		}

		return $resultProductRows;
	}

	protected function rebind($seedID, $targID): void
	{
		$seedID = (int)$seedID;
		$targID = (int)$targID;

		CCrmActivity::Rebind($this->entityTypeID, $seedID, $targID);
		CCrmEvent::Rebind($this->entityTypeID, $seedID, $targID);
		EntityRequisite::rebind($this->entityTypeID, $seedID, $targID);
		Tracking\Entity::rebindTrace(
			$this->entityTypeID, $seedID,
			$this->entityTypeID, $targID,
		);

		$this->rebindTimelineEntries($seedID, $targID);
		$this->rebindEntityRelations($seedID, $targID);

		parent::rebind($seedID, $targID);
	}

	protected function rebindTimelineEntries(int $seedId, int $targetId): void
	{
		Timeline\ActivityEntry::rebind($this->entityTypeID, $seedId, $targetId);
		Timeline\CreationEntry::rebind($this->entityTypeID, $seedId, $targetId);
		Timeline\MarkEntry::rebind($this->entityTypeID, $seedId, $targetId);
		Timeline\CommentEntry::rebind($this->entityTypeID, $seedId, $targetId);
		Timeline\LogMessageEntry::rebind($this->entityTypeID, $seedId, $targetId);
		Timeline\AI\Call\Entry::rebind($this->entityTypeID, $seedId, $targetId);
	}

	protected function rebindEntityRelations(int $seedId, int $targetId): void
	{
		$oldItem = ItemIdentifier::createByParams($this->entityTypeID, $seedId);
		$newItem = ItemIdentifier::createByParams($this->entityTypeID, $targetId);
		if ($oldItem === null || $newItem === null)
		{
			return;
		}

		EntityRelationTable::rebindWhereItemIsChild($oldItem, $newItem);
	}
}
