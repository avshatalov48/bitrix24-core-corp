<?php

namespace Bitrix\Crm\Entity\Compatibility\Adapter;

use Bitrix\Crm\Entity\Compatibility\Adapter\Permissions\AttributesManager;
use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

/**
 * @internal Do not use!
 * @see \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()
 *
 * Used as compatibility adapter in general/crm_*.php
 */
class Permissions
{
	public function __construct(private readonly int $entityTypeId)
	{
	}

	public function getCurrentUserFromOptions(array $options = []): int
	{
		return (int)($options['CURRENT_USER'] ?? Container::getInstance()->getContext()->getUserId());
	}

	public function isAdmin(int $userId = null): bool
	{
		return Container::getInstance()->getUserPermissions($userId)->isAdmin();
	}

	public function getAttributesHelper(int $userId, string $permissionType): AttributesManager
	{
		return new AttributesManager($this->entityTypeId, $userId, $permissionType);
	}

	public function canRead(int $id, ?int $userId = null): bool
	{
		$userPermissions = Container::getInstance()->getUserPermissions($userId);
		if ($id <= 0)
		{
			return $userPermissions->entityType()->canReadItems($this->entityTypeId);
		}

		return $userPermissions->item()->canRead($this->entityTypeId, $id);
	}

	public function canReadInCategory(int $id, ?int $categoryId, ?int $userId = null): bool
	{
		$userPermissions = Container::getInstance()->getUserPermissions($userId);
		if ($id <= 0)
		{
			return $userPermissions->entityType()->canReadItemsInCategory($this->entityTypeId, (int)$categoryId);
		}
		$itemIdentifier = ItemIdentifier::createByParams($this->entityTypeId, $id, $categoryId);

		return $itemIdentifier && $userPermissions->item()->canReadItemIdentifier($itemIdentifier);
	}

	public function canUpdate(int $id, ?int $userId = null): bool
	{
		$userPermissions = Container::getInstance()->getUserPermissions($userId);
		if ($id <= 0)
		{
			return $userPermissions->entityType()->canUpdateItems($this->entityTypeId);
		}

		return $userPermissions->item()->canUpdate($this->entityTypeId, $id);
	}

	public function canUpdateInCategory(int $id, ?int $categoryId, ?int $userId = null): bool
	{
		$userPermissions = Container::getInstance()->getUserPermissions($userId);
		if ($id <= 0)
		{
			return $userPermissions->entityType()->canUpdateItemsInCategory($this->entityTypeId, (int)$categoryId);
		}
		$itemIdentifier = ItemIdentifier::createByParams($this->entityTypeId, $id, $categoryId);

		return $itemIdentifier && $userPermissions->item()->canUpdateItemIdentifier($itemIdentifier);
	}

	public function canDelete(int $id, ?int $userId = null): bool
	{
		$userPermissions = Container::getInstance()->getUserPermissions($userId);
		if ($id <= 0)
		{
			return $userPermissions->entityType()->canDeleteItems($this->entityTypeId);
		}

		return $userPermissions->item()->canDelete($this->entityTypeId, $id);
	}

	public function canDeleteInCategory(int $id, ?int $categoryId, ?int $userId = null): bool
	{
		$userPermissions = Container::getInstance()->getUserPermissions($userId);
		if ($id <= 0)
		{
			return $userPermissions->entityType()->canDeleteItemsInCategory($this->entityTypeId, (int)$categoryId);
		}
		$itemIdentifier = ItemIdentifier::createByParams($this->entityTypeId, $id, $categoryId);

		return $itemIdentifier && $userPermissions->item()->canDeleteItemIdentifier($itemIdentifier);
	}

	public function canAdd(?int $userId = null): bool
	{
		return Container::getInstance()
			->getUserPermissions($userId)
			->entityType()
			->canAddItems($this->entityTypeId)
		;
	}

	public function canAddInCategory(int $categoryId, ?int $userId = null): bool
	{
		return Container::getInstance()
			->getUserPermissions($userId)
			->entityType()
			->canAddItemsInCategory($this->entityTypeId, $categoryId)
		;
	}

	public function canImport(int $userId = null): bool
	{
		return Container::getInstance()
			->getUserPermissions($userId)
			->entityType()
			->canImportItems($this->entityTypeId)
		;
	}

	public function canImportInCategory(int $categoryId, ?int $userId = null): bool
	{
		return Container::getInstance()
			->getUserPermissions($userId)
			->entityType()
			->canImportItemsInCategory($this->entityTypeId, $categoryId)
		;
	}

	public function canExport(?int $userId = null): bool
	{
		return Container::getInstance()
			->getUserPermissions($userId)
			->entityType()
			->canExportItems($this->entityTypeId)
			;
	}

	public function canExportInCategory(int $categoryId, ?int $userId = null): bool
	{
		return Container::getInstance()
			->getUserPermissions($userId)
			->entityType()
			->canExportItemsInCategory($this->entityTypeId, $categoryId)
		;
	}

	public function canChangeStage(int $id, ?int $categoryId, string $fromStageId, string $toStageId, ?int $userId = null): bool
	{
		return Container::getInstance()
			->getUserPermissions($userId)
			->item()
			->canChangeStage(
				new \Bitrix\Crm\ItemIdentifier($this->entityTypeId, $id, $categoryId),
				$fromStageId,
				$toStageId,
			)
		;
	}

	public function getFirstAvailableStageIdForAdd(?int $categoryId, array $stageIds, ?int $userId = null): string
	{
		$stagePermissions = Container::getInstance()
			->getUserPermissions($userId)
			->stage()
		;

		foreach ($stageIds as $stageId)
		{
			if ($stagePermissions->canAddInStage($this->entityTypeId, $categoryId, $stageId))
			{
				return $stageId;
			}
		}

		return '';
	}

	public function checkRelatedEntitiesPermissions(array $arFields, int $userId): Result
	{
		Container::getInstance()->getLocalization()->loadMessages();

		$userPermissions = Container::getInstance()->getUserPermissions($userId);

		$result = new Result();

		$fieldCandidates = [
			Item::FIELD_NAME_CONTACT_ID => \CCrmOwnerType::Contact,
			Item::FIELD_NAME_CONTACT_IDS => \CCrmOwnerType::Contact,
			Item::FIELD_NAME_COMPANY_ID => \CCrmOwnerType::Company,
			Item::FIELD_NAME_LEAD_ID => \CCrmOwnerType::Lead,
			Item\Quote::FIELD_NAME_DEAL_ID => \CCrmOwnerType::Deal,
			Item\Deal::FIELD_NAME_QUOTE_ID => \CCrmOwnerType::Quote,
			Item\Contact::FIELD_NAME_COMPANY_IDS => \CCrmOwnerType::Company,
		];

		$parentEntityFields = Container::getInstance()->getParentFieldManager()->getParentFieldsInfo($this->entityTypeId);
		foreach ($parentEntityFields as $parentFieldName => $parentFieldParams)
		{
			$fieldCandidates[$parentFieldName] = $parentFieldParams['SETTINGS']['parentEntityTypeId'];
		}

		$factory = Container::getInstance()->getFactory($this->entityTypeId);
		$entityFieldsCollection = $factory->getFieldsCollection();

		foreach ($fieldCandidates as $fieldName => $relatedEntityTypeId)
		{
			if ($factory->isFieldExists($fieldName) && isset($arFields[$fieldName]))
			{
				if ( // to avoid duplicated errors in CONTACT_ID and CONTACT_IDS fields
					$fieldName === Item::FIELD_NAME_CONTACT_ID
					&& $factory->isFieldExists(Item::FIELD_NAME_CONTACT_IDS)
					&& in_array($arFields[$fieldName], (array)$arFields[Item::FIELD_NAME_CONTACT_IDS])
				)
				{
					continue;
				}
				if (  // to avoid duplicated errors in COMPANY_ID and COMPANY_IDS fields
					$fieldName === Item::FIELD_NAME_COMPANY_ID
					&& $factory->isFieldExists(Item\Contact::FIELD_NAME_COMPANY_IDS)
					&& in_array($arFields[$fieldName], (array)$arFields[Item\Contact::FIELD_NAME_COMPANY_IDS])
				)
				{
					continue;
				}

				$values = (array)$arFields[$fieldName];

				foreach ($values as $value)
				{
					$value = (int)$value;

					if ($value < 0)
					{
						$result->addError($entityFieldsCollection->getField($fieldName)?->getValueNotValidError() ?? new Error('Wrong value'));
					}

					if ($value > 0 && !$userPermissions->item()->canRead($relatedEntityTypeId, $value))
					{
						$result->addError(
							new Error(
								sprintf(
									'[%s #%s] %s',
									\CCrmOwnerType::GetDescription($relatedEntityTypeId),
									$value,
									Loc::getMessage('CRM_COMMON_READ_ACCESS_DENIED')
								)
							)
						);
					}
				}
			}
		}

		return $result;
	}
}
