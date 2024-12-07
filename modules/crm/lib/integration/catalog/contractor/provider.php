<?php

namespace Bitrix\Crm\Integration\Catalog\Contractor;

use Bitrix\Catalog\v2\Contractor\Provider\IContractor;
use Bitrix\Catalog\v2\Contractor\Provider\IProvider;
use Bitrix\Crm\Component\EntityDetails\BaseComponent;
use Bitrix\Crm\Controller\Action\Entity\SearchAction;
use Bitrix\Crm\Format\PersonNameFormatter;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Settings\EntityEditSettings;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\SystemException;
use Bitrix\Crm\Controller\Entity;
use Bitrix\Main\Web\Json;
use Bitrix\Crm\Binding\ContactCompanyTable;
use Bitrix\Crm\Binding\EntityBinding;
use CCrmOwnerType;

abstract class Provider implements IProvider
{
	private const DETAIL_CARD_ACTION_GET_SECONDARY_ENTITY_INFOS = 'GET_SECONDARY_ENTITY_INFOS';
	private const FILTER_CODE_CONTRACTOR_CRM_COMPANY_ID = 'CONTRACTOR_CRM_COMPANY_ID';
	private const FILTER_CODE_CONTRACTOR_CRM_CONTACT_ID = 'CONTRACTOR_CRM_CONTACT_ID';

	abstract protected static function getComponentName(): string;

	abstract protected static function getDocumentPrimaryField(): string;

	/**
	 * @return string
	 */
	abstract protected static function getTableName(): string;
	
	/**
	 * @inheritDoc
	 */
	public static function getModuleId(): string
	{
		return 'crm';
	}

	/**
	 * @inheritDoc
	 */
	public static function getContractorByDocumentId(int $documentId): ?IContractor
	{
		$documentContractor = self::getPrimaryContractorByDocumentId($documentId);
		if (!$documentContractor)
		{
			return null;
		}

		$itemFactory = Container::getInstance()->getFactory($documentContractor['ENTITY_TYPE_ID']);
		if (!$itemFactory)
		{
			return null;
		}

		/** @var Item $item */
		$item = $itemFactory->getItem($documentContractor['ENTITY_ID']);
		if (!$item)
		{
			return null;
		}

		if (!$item->getId())
		{
			return null;
		}

		if ($item instanceof Item\Company)
		{
			return new Company($item);
		}

		if ($item instanceof Item\Contact)
		{
			return new Contact($item);
		}

		return null;
	}

	/**
	 * @param int $documentId
	 * @return array|null
	 */
	private static function getPrimaryContractorByDocumentId(int $documentId): ?array
	{
		$storeDocumentContractors = static::getTableName()::query()
			->setSelect(['ENTITY_TYPE_ID', 'ENTITY_ID'])
			->where(static::getDocumentPrimaryField(), $documentId)
			->exec()
			->fetchAll()
		;

		$contacts = [];
		foreach ($storeDocumentContractors as $storeDocumentContractor)
		{
			if ((int)$storeDocumentContractor['ENTITY_TYPE_ID'] === CCrmOwnerType::Company)
			{
				return $storeDocumentContractor;
			}

			if ((int)$storeDocumentContractor['ENTITY_TYPE_ID'] === CCrmOwnerType::Contact)
			{
				$contacts[] = $storeDocumentContractor;
			}
		}

		if ($contacts)
		{
			return $contacts[0];
		}

		return null;
	}

	// region Migration

	/**
	 * @inheritDoc
	 */
	public static function isMigrated(): bool
	{
		return Converter::isCompleted();
	}

	/**
	 * @inheritDoc
	 */
	public static function runMigration(): void
	{
		Converter::bind(30);
	}

	public static function showMigrationProgress(): void
	{
		echo Converter::getHtml();
	}

	// endregion

	// region Documents grid

	/**
	 * @inheritDoc
	 */
	public static function getDocumentsGridFilterFields(): array
	{
		$result = [];

		foreach (self::getDocumentsGridFilters() as $filterCode => $filterData)
		{
			$result[] = [
				'CODE' => $filterCode,
				'PARAMS' => $filterData['FIELD_PARAMS']
			];
		}

		return $result;
	}

	/**
	 * @inheritDoc
	 */
	public static function isDocumentsGridFilterFieldSupported(string $fieldId): bool
	{
		return in_array(
			$fieldId,
			[
				self::FILTER_CODE_CONTRACTOR_CRM_COMPANY_ID,
				self::FILTER_CODE_CONTRACTOR_CRM_CONTACT_ID,
			],
			true
		);
	}

	/**
	 * @inheritDoc
	 */
	public static function getDocumentsGridFilterFieldData(string $fieldId): array
	{
		$documentsGridFilters = self::getDocumentsGridFilters();

		return $documentsGridFilters[$fieldId]['FIELD_DATA'] ?? [];
	}

	/**
	 * @inheritDoc
	 */
	public static function setDocumentsGridFilter(array &$filter): void
	{
		foreach (self::getDocumentsGridFilters() as $filterCode => $filterData)
		{
			if (isset($filter[$filterCode]))
			{
				$documentIds = [0];
				if (is_array($filter[$filterCode]))
				{
					$documentsList = static::getTableName()::query()
						->setSelect([static::getDocumentPrimaryField()])
						->where('ENTITY_TYPE_ID', $filterData['ENTITY_TYPE_ID'])
						->whereIn('ENTITY_ID', array_map('intval', $filter[$filterCode]))
						->exec()
					;

					while ($document = $documentsList->fetch())
					{
						$documentIds[] = (int)$document[static::getDocumentPrimaryField()];
					}
				}

				$filter[] = ['@ID' => $documentIds];

				unset($filter[$filterCode]);
			}
		}
	}

	/**
	 * @return array[]
	 */
	private static function getDocumentsGridFilters(): array
	{
		$companyCategoryId = CategoryRepository::getIdByEntityTypeId(CCrmOwnerType::Company);
		$contactCategoryId = CategoryRepository::getIdByEntityTypeId(CCrmOwnerType::Contact);

		return [
			self::FILTER_CODE_CONTRACTOR_CRM_COMPANY_ID => [
				'ENTITY_TYPE_ID' => CCrmOwnerType::Company,
				'FIELD_PARAMS' => [
					'partial' => true,
					'type' => 'entity_selector',
					'default' => true,
				],
				'FIELD_DATA' => [
					'params' => [
						'multiple' => 'Y',
						'dialogOptions' => [
							'height' => 200,
							'context' => '1contractor_filter',
							'entities' => [
								[
									'id' => 'company',
									'options' => [
										'categoryId' => $companyCategoryId ?: -1,
									],
									'dynamicLoad' => true,
									'dynamicSearch' => true,
								]
							],
							'dropdownMode' => false,
						],
					],
				],
			],
			self::FILTER_CODE_CONTRACTOR_CRM_CONTACT_ID => [
				'ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
				'FIELD_PARAMS' => [
					'partial' => true,
					'type' => 'entity_selector',
					'default' => true,
				],
				'FIELD_DATA' => [
					'params' => [
						'multiple' => 'Y',
						'dialogOptions' => [
							'height' => 200,
							'context' => '2contractor_filter',
							'entities' => [
								[
									'id' => 'contact',
									'options' => [
										'categoryId' => $contactCategoryId ?: -1,
									],
									'dynamicLoad' => true,
									'dynamicSearch' => true,
								]
							],
							'dropdownMode' => false,
						],
					],
				],
			],
		];
	}

	// endregion

	// region Document card

	/**
	 * @return string
	 */
	public static function getEditorFieldType(): string
	{
		return 'client_light';
	}

	/**
	 * @inheritDoc
	 */
	public static function getEditorFieldData(): array
	{
		$categoryParams = [
			CCrmOwnerType::Company => [
				'categoryId' => CategoryRepository::getIdByEntityTypeId(CCrmOwnerType::Company) ?? 0,
			],
			CCrmOwnerType::Contact => [
				'categoryId' => CategoryRepository::getIdByEntityTypeId(CCrmOwnerType::Contact) ?? 0,
			],
		];

		return [
			'compound' => [
				[
					'name' => 'COMPANY_ID',
					'type' => 'company',
					'entityTypeName' => CCrmOwnerType::CompanyName,
					'tagName' => CCrmOwnerType::CompanyName,
				],
				[
					'name' => 'CONTACT_IDS',
					'type' => 'multiple_contact',
					'entityTypeName' => CCrmOwnerType::ContactName,
					'tagName' => CCrmOwnerType::ContactName,
				]
			],
			'categoryParams' => $categoryParams,
			'requiredFieldErrorMessage' => Loc::getMessage('CONTRACTORS_PROVIDER_CONTRACTOR_FIELD_REQUIRED'),
			'map' => ['data' => 'CLIENT_DATA'],
			'info' => 'CLIENT_INFO',
			'lastCompanyInfos' => 'LAST_COMPANY_INFOS',
			'lastContactInfos' => 'LAST_CONTACT_INFOS',
			'loaders' => [
				'primary' => [
					CCrmOwnerType::CompanyName => [
						'action' => 'GET_CLIENT_INFO',
						'url' => '/bitrix/components/bitrix/crm.company.show/ajax.php?' . bitrix_sessid_get(),
					],
					CCrmOwnerType::ContactName => [
						'action' => 'GET_CLIENT_INFO',
						'url' => '/bitrix/components/bitrix/crm.contact.show/ajax.php?' . bitrix_sessid_get(),
					]
				],
				'secondary' => [
					CCrmOwnerType::CompanyName => [
						'action' => self::DETAIL_CARD_ACTION_GET_SECONDARY_ENTITY_INFOS,
						'url' => '/bitrix/components/bitrix/' . static::getComponentName() . '/ajax.php?' . bitrix_sessid_get(),
					]
				]
			],
			'clientEditorFieldsParams' =>
				\CCrmComponentHelper::prepareClientEditorFieldsParams(['categoryParams' => $categoryParams])
			,
			'useExternalRequisiteBinding' => true,
		];
	}

	/**
	 * @inheritDoc
	 */
	public static function getEditorEntityData(int $documentId): array
	{
		$companyId = 0;
		$contactBindings = [];

		if ($documentId > 0)
		{
			$storeDocumentContractors = static::getTableName()::query()
				->setSelect(['ENTITY_TYPE_ID', 'ENTITY_ID'])
				->where(static::getDocumentPrimaryField(), $documentId)
				->exec()
			;

			while ($storeDocumentContractor = $storeDocumentContractors->fetch())
			{
				if ((int)$storeDocumentContractor['ENTITY_TYPE_ID'] === CCrmOwnerType::Company)
				{
					$companyId = (int)$storeDocumentContractor['ENTITY_ID'];
				}
				if ((int)$storeDocumentContractor['ENTITY_TYPE_ID'] === CCrmOwnerType::Contact)
				{
					$contactBindings[] = [
						'CONTACT_ID' => (int)$storeDocumentContractor['ENTITY_ID'],
					];
				}
			}
		}

		$clientInfo = [];
		if ($companyId > 0)
		{
			$isEntityReadPermitted = \CCrmCompany::CheckReadPermission($companyId, self::getPermissions());
			$companyInfo = \CCrmEntitySelectorHelper::PrepareEntityInfo(
				CCrmOwnerType::CompanyName,
				$companyId,
				[
					'ENTITY_EDITOR_FORMAT' => true,
					'IS_HIDDEN' => !$isEntityReadPermitted,
					'USER_PERMISSIONS' => self::getPermissions(),
					'REQUIRE_REQUISITE_DATA' => true,
					'REQUIRE_EDIT_REQUISITE_DATA' => true,
					'REQUIRE_MULTIFIELDS' => true,
					'NORMALIZE_MULTIFIELDS' => true,
					'NAME_TEMPLATE' => PersonNameFormatter::getFormat(),
				]
			);

			$clientInfo['COMPANY_DATA'] = [$companyInfo];
		}

		$contactIDs = EntityBinding::prepareEntityIDs(CCrmOwnerType::Contact, $contactBindings);
		$clientInfo['CONTACT_DATA'] = [];
		$iteration= 0;

		foreach($contactIDs as $contactID)
		{
			$isEntityReadPermitted = \CCrmContact::CheckReadPermission($contactID, self::getPermissions());
			$clientInfo['CONTACT_DATA'][] = \CCrmEntitySelectorHelper::PrepareEntityInfo(
				CCrmOwnerType::ContactName,
				$contactID,
				[
					'ENTITY_EDITOR_FORMAT' => true,
					'IS_HIDDEN' => !$isEntityReadPermitted,
					'USER_PERMISSIONS' => self::getPermissions(),
					'REQUIRE_REQUISITE_DATA' => true,
					'REQUIRE_EDIT_REQUISITE_DATA' => ($iteration === 0),
					'REQUIRE_MULTIFIELDS' => true,
					'NORMALIZE_MULTIFIELDS' => true,
					'REQUIRE_BINDINGS' => true,
					'NAME_TEMPLATE' => PersonNameFormatter::getFormat(),
				]
			);
			$iteration++;
		}

		$result = [
			'CLIENT_INFO' => $clientInfo,
			'LAST_COMPANY_INFOS' => SearchAction::prepareSearchResultsJson(
				Entity::getRecentlyUsedItems(
					'crm.store.document.details',
					'company',
					[
						'EXPAND_ENTITY_TYPE_ID' => CCrmOwnerType::Company,
						'EXPAND_CATEGORY_ID' => self::getCategoryIdByEntityType(CCrmOwnerType::Company),
					]
				)
			),
			'LAST_CONTACT_INFOS' => SearchAction::prepareSearchResultsJson(
				Entity::getRecentlyUsedItems(
					'crm.store.document.details',
					'contact',
					[
						'EXPAND_ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
						'EXPAND_CATEGORY_ID' => self::getCategoryIdByEntityType(CCrmOwnerType::Contact),
					]
				)
			),
		];

		\CCrmComponentHelper::prepareMultifieldData(
			CCrmOwnerType::Company,
			[$companyId],
			[
				'PHONE',
				'EMAIL',
				'IM',
			],
			$result
		);

		\CCrmComponentHelper::prepareMultifieldData(
			CCrmOwnerType::Contact,
			$contactIDs,
			[
				'PHONE',
				'EMAIL',
				'IM',
			],
			$result
		);

		return $result;
	}

	/**
	 * @inheritDoc
	 */
	public static function processDocumentCardAjaxActions(string $action): void
	{
		$userId = \CCrmSecurityHelper::getCurrentUserID();

		if (
			$action !== self::DETAIL_CARD_ACTION_GET_SECONDARY_ENTITY_INFOS
			|| $userId <= 0
		)
		{
			return;
		}

		$params = isset($_POST['PARAMS']) && is_array($_POST['PARAMS']) ? $_POST['PARAMS'] : [];
		if (
			(
				$params['OWNER_TYPE_NAME'] !== CCrmOwnerType::StoreDocumentName
				&& $params['OWNER_TYPE_NAME'] !== CCrmOwnerType::AgentContractDocumentName
			)
			|| $params['PRIMARY_TYPE_NAME'] !== CCrmOwnerType::CompanyName
			|| $params['SECONDARY_TYPE_NAME'] !== CCrmOwnerType::ContactName
		)
		{
			return;
		}

		$companyId = isset($params['PRIMARY_ID']) ? (int)$params['PRIMARY_ID'] : 0;
		$contactIds = ContactCompanyTable::getCompanyContactIDs($companyId);

		$contactsInfo = [];
		foreach($contactIds as $contactId)
		{
			if (!\CCrmContact::CheckReadPermission($contactId, self::getPermissions()))
			{
				continue;
			}

			$contactsInfo[]  = \CCrmEntitySelectorHelper::PrepareEntityInfo(
				CCrmOwnerType::ContactName,
				$contactId,
				[
					'ENTITY_EDITOR_FORMAT' => true,
					'REQUIRE_REQUISITE_DATA' => true,
					'REQUIRE_MULTIFIELDS' => true,
					'NAME_TEMPLATE' => PersonNameFormatter::getFormat()
				]
			);
		}

		$GLOBALS['APPLICATION']->RestartBuffer();
		header('Content-Type: application/x-javascript; charset=' . LANG_CHARSET);
		echo \CUtil::PhpToJSObject([
			'ENTITY_INFOS' => $contactsInfo
		]);
	}

	/**
	 * @inheritDoc
	 */
	public static function onAfterDocumentDelete(int $documentId): void
	{
		static::getTableName()::deleteByDocumentId($documentId);
	}

	/**
	 * @inheritDoc
	 */
	public static function onBeforeDocumentSave(array $fields): Result
	{
		$requestClientData = $fields['CLIENT_DATA'] ?? '';

		$createdEntities = [];
		$updatedEntities = [];

		$clientData = null;
		try
		{
			$clientData = Json::decode($requestClientData);
		}
		catch (SystemException $e) {}

		$clientData = is_array($clientData) ? $clientData : [];

		$companyId = 0;
		$companyIds = null;
		if (
			isset($clientData['COMPANY_DATA'])
			&& is_array($clientData['COMPANY_DATA'])
		)
		{
			$companyIds = [];
			$companyData = $clientData['COMPANY_DATA'];
			if (!empty($companyData))
			{
				$companyItem = $companyData[0];
				$companyId = isset($companyItem['id']) ? (int)$companyItem['id'] : 0;

				if ($companyId <= 0)
				{
					$companyId = BaseComponent::createEntity(
						CCrmOwnerType::Company,
						$companyItem,
						[
							'userPermissions' => self::getPermissions(),
							'startWorkFlows' => true
						]
					);

					if ($companyId > 0)
					{
						$createdEntities[CCrmOwnerType::Company] = [$companyId];
					}
				}
				elseif (
					$companyItem['title']
					|| (isset($companyItem['multifields']) && is_array($companyItem['multifields']))
				)
				{
					if (!isset($updatedEntities[CCrmOwnerType::Company]))
					{
						$updatedEntities[CCrmOwnerType::Company] = [];
					}
					$updatedEntities[CCrmOwnerType::Company][$companyId] = $companyItem;
				}
			}

			if ($companyId > 0)
			{
				$companyIds[] = $companyId;

				Entity::addLastRecentlyUsedItems(
					'crm.store.document.details',
					'company',
					[
						[
							'ENTITY_TYPE_ID' => CCrmOwnerType::Company,
							'ENTITY_ID' => $companyId,
							'CATEGORY_ID' => self::getCategoryIdByEntityType(CCrmOwnerType::Company),
						]
					]
				);
			}
		}

		$contactIds = null;
		$contactsToCompanyBindings = null;
		if (
			isset($clientData['CONTACT_DATA'])
			&& is_array($clientData['CONTACT_DATA'])
		)
		{
			$contactIds = [];
			$contactData = $clientData['CONTACT_DATA'];

			foreach ($contactData as $contactItem)
			{
				if (!is_array($contactItem))
				{
					continue;
				}

				$contactId = isset($contactItem['id']) ? (int)$contactItem['id'] : 0;
				if ($contactId <= 0)
				{
					$contactId = BaseComponent::createEntity(
						CCrmOwnerType::Contact,
						$contactItem,
						[
							'userPermissions' => self::getPermissions(),
							'startWorkFlows' => true,
						]
					);

					if ($contactId > 0)
					{
						if (!is_array($contactsToCompanyBindings))
						{
							$contactsToCompanyBindings = [];
						}
						$contactsToCompanyBindings[] = $contactId;

						if (!isset($createdEntities[CCrmOwnerType::Contact]))
						{
							$createdEntities[CCrmOwnerType::Contact] = [];
						}
						$createdEntities[CCrmOwnerType::Contact][] = $contactId;
					}
				}
				elseif (
					$contactItem['title']
					|| (isset($contactItem['multifields']) && is_array($contactItem['multifields']))
					|| (isset($contactItem['requisites']) && is_array($contactItem['requisites']))
				)
				{
					if (!isset($updatedEntities[CCrmOwnerType::Contact]))
					{
						$updatedEntities[CCrmOwnerType::Contact] = [];
					}

					$updatedEntities[CCrmOwnerType::Contact][$contactId] = $contactItem;
				}

				if ($contactId > 0)
				{
					$contactIds[] = $contactId;
				}
			}

			if (!empty($contactIds))
			{
				$contactIds = array_unique($contactIds);
			}

			$fields['CONTACT_IDS'] = $contactIds;
			if (!empty($fields['CONTACT_IDS']))
			{
				$contactBindings = [];
				foreach($fields['CONTACT_IDS'] as $contactId)
				{
					$contactBindings[] = [
						'ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
						'ENTITY_ID' => $contactId,
						'CATEGORY_ID' => self::getCategoryIdByEntityType(CCrmOwnerType::Contact),
					];
				}

				Entity::addLastRecentlyUsedItems(
					'crm.store.document.details',
					'contact',
					$contactBindings
				);

			}
		}

		return (new Result())->setData([
			'COMPANY_ID' => $companyId,
			'DOCUMENT_BINDINGS' => [
				CCrmOwnerType::Contact => $contactIds,
				CCrmOwnerType::Company => $companyIds,
			],
			'CONTACT_COMPANY_BINDINGS' => $contactsToCompanyBindings,
			'CREATED_ENTITIES' => $createdEntities,
			'UPDATE_ENTITIES' => $updatedEntities,
		]);
	}

	/**
	 * @inheritDoc
	 */
	public static function onAfterDocumentSaveSuccess(int $documentId, Result $result, array $options = []): void
	{
		$resultData = $result->getData();

		$companyId = $resultData['COMPANY_ID'] ?? 0;
		$documentBindings = $resultData['DOCUMENT_BINDINGS'] ?? [];
		$contactCompanyBindings = $resultData['CONTACT_COMPANY_BINDINGS'] ?? [];

		/** @var EntityEditSettings $entityEditorSettings */
		$entityEditorSettings = $options['entityEditorSettings'] ?? null;

		if (
			$entityEditorSettings
			&& $entityEditorSettings->isClientCompanyEnabled()
			&& $entityEditorSettings->isClientContactEnabled()
			&& $companyId > 0
			&& is_array($contactCompanyBindings) &&
			!empty($contactCompanyBindings)
		)
		{
			self::bindContactsToCompany($companyId, $contactCompanyBindings);
		}

		if (
			isset($resultData['UPDATE_ENTITIES'])
			&& is_array($resultData['UPDATE_ENTITIES'])
		)
		{
			self::updateEntities($resultData['UPDATE_ENTITIES']);
		}

		self::bindEntitiesToDocument($documentId, $documentBindings);
	}

	/**
	 * @inheritDoc
	 */
	public static function onAfterDocumentSaveFailure(?int $documentId, Result $result, array $options = []): void
	{
		self::deleteCreatedEntities($result);
	}

	/**
	 * @inheritDoc
	 */
	public static function onAfterDocumentSaveSuccessForMobile(int $documentId, array $data): void
	{
		self::bindEntitiesToDocument(
			$documentId,
			[
				CCrmOwnerType::Company => $data['COMPANY_ID'] ?? [],
				CCrmOwnerType::Contact => $data['CONTACT_IDS'] ?? [],
			]
		);
	}

	/**
	 * @param int $companyId
	 * @param array $contactCompanyBindings
	 * @return void
	 */
	private static function bindContactsToCompany(int $companyId, array $contactCompanyBindings): void
	{
		ContactCompanyTable::bindContactIDs($companyId, $contactCompanyBindings);
	}

	/**
	 * @param array $updatedEntities
	 * @return void
	 */
	private static function updateEntities(array $updatedEntities): void
	{
		foreach ($updatedEntities as $entityTypeId => $entityData)
		{
			foreach ($entityData as $entityId => $entityInfo)
			{
				BaseComponent::updateEntity(
					$entityTypeId,
					$entityId,
					$entityInfo,
					[
						'userPermissions' => self::getPermissions(),
						'startWorkFlows' => true,
					]
				);
			}
		}
	}

	/**
	 * @param int $documentId
	 * @param array $bindings
	 */
	private static function bindEntitiesToDocument(int $documentId, array $bindings): void
	{
		foreach ($bindings as $entityTypeId => $entityIds)
		{
			if (is_null($entityIds))
			{
				continue;
			}

			foreach ($entityIds as $entityId)
			{
				$existingBinding = static::getTableName()::query()
					->where(static::getDocumentPrimaryField(), $documentId)
					->where('ENTITY_ID', $entityId)
					->where('ENTITY_TYPE_ID', $entityTypeId)
					->exec()
					->fetch()
				;

				if (!$existingBinding)
				{
					static::getTableName()::add([
						static::getDocumentPrimaryField() => $documentId,
						'ENTITY_ID' => $entityId,
						'ENTITY_TYPE_ID' => $entityTypeId,
					]);
				}
			}

			static::getTableName()::deleteBindings($documentId, $entityTypeId, $entityIds);
		}
	}

	/**
	 * @param Result $result
	 * @return Result
	 */
	private static function deleteCreatedEntities(Result $result): Result
	{
		$resultData = $result->getData();

		$createdEntities = $resultData['CREATED_ENTITIES'] ?? [];
		foreach ($createdEntities as $entityTypeId => $entityIds)
		{
			foreach ($entityIds as $entityID)
			{
				BaseComponent::deleteEntity($entityTypeId, $entityID);
			}
		}

		return new Result();
	}

	// endregion

	/**
	 * @return \CCrmPerms
	 */
	private static function getPermissions(): \CCrmPerms
	{
		return \CCrmPerms::GetCurrentUserPermissions();
	}

	/**
	 * @param int $entityTypeId
	 * @return int
	 */
	private static function getCategoryIdByEntityType(int $entityTypeId): int
	{
		$categoryId = CategoryRepository::getIdByEntityTypeId($entityTypeId);
		if (!$categoryId)
		{
			return -1;
		}

		return $categoryId;
	}
}
