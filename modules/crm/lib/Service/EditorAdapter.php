<?php

namespace Bitrix\Crm\Service;

use Bitrix\Catalog;
use Bitrix\Crm\Attribute\FieldAttributeManager;
use Bitrix\Crm\Binding\EntityBinding;
use Bitrix\Crm\CompanyTable;
use Bitrix\Crm\Component\EntityDetails\BaseComponent;
use Bitrix\Crm\Component\EntityDetails\ComponentMode;
use Bitrix\Crm\Controller\Action\Entity\SearchAction;
use Bitrix\Crm\Controller\Entity;
use Bitrix\Crm\Conversion\EntityConversionWizard;
use Bitrix\Crm\Currency;
use Bitrix\Crm\Entity\Traits\VisibilityConfig;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\EO_Status_Collection;
use Bitrix\Crm\Field;
use Bitrix\Crm\Format\Money;
use Bitrix\Crm\Format\PersonNameFormatter;
use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Requisite\EntityLink;
use Bitrix\Crm\Security\EntityAuthorization;
use Bitrix\Crm\StatusTable;
use Bitrix\Currency\CurrencyTable;
use Bitrix\Main\Error;
use Bitrix\Main\InvalidOperationException;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\UserField\Dispatcher;
use Bitrix\Main\UserField\Types\BooleanType;
use Bitrix\Main\Web\Json;
use CSaleLocation;

class EditorAdapter
{
	use VisibilityConfig;

	public const FIELD_REQUISITE_BINDING = 'REQUISITE_BINDING';
	public const FIELD_REQUISITE_ID = 'REQUISITE_ID';
	public const FIELD_BANK_DETAIL_ID = 'BANK_DETAIL_ID';
	public const FIELD_MY_COMPANY_REQUISITE_ID = 'MC_REQUISITE_ID';
	public const FIELD_MY_COMPANY_BANK_DETAIL_ID = 'MC_BANK_DETAIL_ID';
	public const FIELD_CLIENT = 'CLIENT';
	public const FIELD_PRODUCT_ROW_SUMMARY = 'PRODUCT_ROW_SUMMARY';
	public const FIELD_OPPORTUNITY = 'OPPORTUNITY_WITH_CURRENCY';
	public const FIELD_UTM = 'UTM';
	public const FIELD_FILES = 'FILES';
	public const FIELD_CLIENT_DATA_NAME = 'CLIENT_DATA';
	public const FIELD_PARENT_PREFIX = 'PARENT_ID_';

	public const CONTROLLER_PRODUCT_LIST = 'PRODUCT_LIST';

	public const DATA_FILES = 'DISK_FILES';

	public const CONTEXT_PARENT_TYPE_ID = 'PARENT_TYPE_ID';
	public const CONTEXT_PARENT_ID = 'PARENT_ID';
	public const CONTEXT_PARENT_TYPE_NAME = 'PARENT_TYPE_NAME';

	protected const MY_COMPANIES_COUNT_DISABLE_SEARCH = 1;
	protected const MAX_PRODUCT_ROWS_IN_SUMMARY = 10;

	protected $fieldsCollection;
	protected $stages;
	protected $dependantFieldsMap;
	protected $additionalFields = [];

	protected $entityFields;
	protected $entityUserFields;
	protected $entityData;
	protected $processedEntityFields;
	protected $clientEntityData;
	/** @var array[]|null */
	protected $srcItemProductsEntityData;
	protected $context = [];

	public function __construct(Field\Collection $fieldsCollection, array $dependantFieldsMap = [])
	{
		$this->fieldsCollection = $fieldsCollection;
		$this->dependantFieldsMap = $dependantFieldsMap;
	}

	public function hasData(): bool
	{
		return !empty($this->processedEntityFields);
	}

	public static function isProductListEnabled(): bool
	{
		return (
			Loader::includeModule('catalog')
			&& Catalog\Config\Feature::isCommonProductProcessingEnabled()
		);
	}

	public function processByItem(Item $item, EO_Status_Collection $stages, array $componentParameters = []): self
	{
		$mode = (int)($componentParameters['mode'] ?? ComponentMode::VIEW);
		$componentName = (string)($componentParameters['componentName'] ?? '');
		$fileHandlerUrl = (string)($componentParameters['fileHandlerUrl'] ?? '');
		$componentParameters['titleCreationPlaceholder'] = $item->getTitlePlaceholder();
		/** @var EntityConversionWizard|null $conversionWizard */
		$conversionWizard = $componentParameters['conversionWizard'] ?? null;

		if (($mode === ComponentMode::CONVERSION) && $conversionWizard)
		{
			$this->transferDataFromSrcItemToDstItem($item, $conversionWizard);
		}

		$this->addParentRelationFields(
			$item->getEntityTypeId(),
			$componentParameters['entitySelectorContext']
		);

		$this->stages = $stages;

		$this->entityData = $item->getData();
		$this->entityFields = $this->prepareEntityFields($componentParameters);

		$userFields = [];
		foreach ($this->fieldsCollection as $field)
		{
			if ($field->isUserField())
			{
				$userFields[] = $field->getUserField();
			}
		}
		$this->entityUserFields = static::prepareEntityUserFields(
			$userFields,
			$this->prepareEntityFieldvisibilityConfigs($item->getEntityTypeId()),
			$item->getEntityTypeId(),
			$item->getId(),
			$fileHandlerUrl
		);

		$fields = array_merge($this->entityFields, array_values($this->entityUserFields));

		$this->processedEntityFields = $this->processFieldsAttributes($fields, $mode, $item);

		$itemOwnEditorEntityFields = [];
		foreach ($this->processedEntityFields as $fieldData)
		{
			$fieldName = $fieldData['name'];
			if (!$item->hasField($fieldName))
			{
				continue;
			}

			$itemOwnEditorEntityFields[] = $fieldData;

			$fieldValueNormalized = $this->normalizeFieldValue($item->get($fieldName), $fieldData['type']);

			if ($fieldData['type'] === 'userField')
			{
				$fieldValueNormalized = $this->normalizeUserFieldValue($fieldValueNormalized, $fieldData['data']['fieldInfo']);
			}

			$this->entityData[$fieldName] = $fieldValueNormalized;
		}

		$this->clientEntityData = $this->prepareClientEntityData($item, $componentName);

		$this->entityData = $this->prepareEntityDataForFieldsWithUsers($itemOwnEditorEntityFields, $this->entityData);
		$this->entityData = array_merge(
			$this->entityData,
			$this->getEntityDataForEntityFields(
				$item,
				$itemOwnEditorEntityFields,
				$this->entityData
			)
		);

		if (!$item->isNew())
		{
			$this->entityData = array_merge(
				$this->entityData,
				$this->getEntityDataForParentEntityFields(
					$item,
					$this->additionalFields,
					$this->entityData
				)
			);
		}

		if (isset($this->context['PARENT_TYPE_ID']) && $item->isNew())
		{
			$parentFieldName = self::getParentFieldName($this->context['PARENT_TYPE_ID']);
			$this->entityData = $this->addEntityDataForParentEntityField(
				($this->additionalFields[$parentFieldName] ?? []),
				$this->entityData,
				$this->context['PARENT_ID']
			);
		}

		if (
			$item->hasField(Item::FIELD_NAME_COMPANY_ID)
			&& $item->hasField(Item::FIELD_NAME_CONTACT_ID)
		)
		{
			$this->entityData = array_merge($this->entityData, $this->getClientEntityData());
		}
		if ($item->hasField(Item::FIELD_NAME_OPPORTUNITY))
		{
			$this->entityData[static::FIELD_PRODUCT_ROW_SUMMARY] = $this->getProductsSummaryEntityData($item);
			$this->entityData = array_merge($this->entityData, $this->getOpportunityEntityData($item));
		}

		if ($this->fieldsCollection->hasField(Item::FIELD_NAME_MYCOMPANY_ID))
		{
			$this->entityData = array_merge(
				$this->entityData,
				$this->getMyCompanyRequisitesEntityData($item->getEntityTypeId(), $item->getId())
			);

			$this->entityData[static::FIELD_REQUISITE_BINDING] = $this->prepareRequisiteBindings($item);
		}

		$this->processedEntityFields = array_merge($this->processedEntityFields, array_values($this->additionalFields));

		$requiredByAttributesFieldNames = FieldAttributeManager::prepareEditorFieldInfosWithAttributes(
			$this->getEntityFieldAttributeConfigs($item),
			$this->processedEntityFields
		);

		foreach ($requiredByAttributesFieldNames as $fieldName)
		{
			if ($this->isEntityFieldValueEmpty($fieldName, $item))
			{
				$this->entityData['EMPTY_REQUIRED_SYSTEM_FIELD_MAP'][$fieldName] = true;
			}
		}

		return $this;
	}

	protected function transferDataFromSrcItemToDstItem(
		Item $destinationItem,
		EntityConversionWizard $conversionWizard
	): void
	{
		$srcItemData = [];
		$srcItemUserFieldsData = [];
		\Bitrix\Crm\Entity\EntityEditor::prepareConvesionMap(
			$conversionWizard,
			$destinationItem->getEntityTypeId(),
			$srcItemData,
			$srcItemUserFieldsData
		);

		if(isset($srcItemData['CONTACT_IDS']))
		{
			$destinationItem->bindContacts(
				EntityBinding::prepareEntityBindings(\CCrmOwnerType::Contact, (array)$srcItemData['CONTACT_IDS'])
			);
		}
		// CONTACT_BINDINGS are prioritized over CONTACT_IDS. if they're set, override contacts from CONTACT_IDS
		if (isset($srcItemData[Item::FIELD_NAME_CONTACT_BINDINGS]))
		{
			$destinationItem->bindContacts((array)$srcItemData[Item::FIELD_NAME_CONTACT_BINDINGS]);

			unset($srcItemData[Item::FIELD_NAME_CONTACT_BINDINGS]);
		}

		if (isset($srcItemData[Item::FIELD_NAME_PRODUCTS]))
		{
			$this->srcItemProductsEntityData = (array)$srcItemData[Item::FIELD_NAME_PRODUCTS];

			$currencyId =
				empty($srcItemData[Item::FIELD_NAME_CURRENCY_ID])
					? null
					: (string)$srcItemData[Item::FIELD_NAME_CURRENCY_ID]
			;

			$destinationItem->setCurrencyId($currencyId);

			/** Since we don't save the destination item here, it's okay if IDs of the source item products and
			 * IDs of the destination item products intersect.
			 * Proper save that will avoid accidental products transfer
			 * will be performed in @see EditorAdapter::saveProductsData()
			 */
			$destinationItem->setProductRowsFromArrays($this->srcItemProductsEntityData);

			unset($srcItemData[Item::FIELD_NAME_PRODUCTS]);
		}

		foreach (array_merge($srcItemData, $srcItemUserFieldsData) as $fieldName => $fieldValue)
		{
			if ($this->fieldsCollection->hasField($fieldName))
			{
				$destinationItem->set($fieldName, $fieldValue);
			}
		}
	}

	/**
	 * @param int $childEntityTypeId
	 * @param string|null $context
	 * @throws InvalidOperationException
	 */
	protected function addParentRelationFields(int $childEntityTypeId, ?string $context = null): void
	{
		$relationManager = Container::getInstance()->getRelationManager();
		$customParentRelations = $relationManager->getParentRelations($childEntityTypeId)->filterOutPredefinedRelations();

		foreach($customParentRelations as $customParentRelation)
		{
			$parentEntityTypeId = $customParentRelation->getParentEntityTypeId();

			if (\CCrmOwnerType::isPossibleDynamicTypeId($parentEntityTypeId))
			{
				$parentType = Container::getInstance()->getFactory($parentEntityTypeId);
				if(!$parentType)
				{
					throw new InvalidOperationException(
						'Entity type with id:' . $parentEntityTypeId . ' not found'
					);
				}
				$entityDescription = $parentType->getEntityDescription();
			}
			else
			{
				$entityDescription = \CCrmOwnerType::GetDescription($parentEntityTypeId);
			}

			$this->addEntityField(
				static::getParentField(
					$entityDescription,
					$parentEntityTypeId,
					$context
				)
			);
		}
	}

	public function addEntityField(array $field): self
	{
		$this->additionalFields[$field['name']] = $field;

		return $this;
	}

	public function getEntityFields(): array
	{
		if (!$this->hasData())
		{
			throw new InvalidOperationException('call EditorAdapter::processByItem() first');
		}
		return $this->processedEntityFields;
	}

	public function addEntityData($name, $value): self
	{
		if (!$this->hasData())
		{
			throw new InvalidOperationException('call EditorAdapter::processByItem() first');
		}
		$this->entityData[$name] = $value;

		return $this;
	}

	public function getEntityData(): array
	{
		if (!$this->hasData())
		{
			throw new InvalidOperationException('call EditorAdapter::processByItem() first');
		}

		return $this->entityData;
	}

	protected function prepareEntityFields(array $componentParameters = []): array
	{
		$entityFields = [];
		foreach ($this->fieldsCollection->toArray() as $name => $field)
		{
			// first - process only own fields
			if (empty($field['USER_FIELD']))
			{
				$entityFields[] = $this->prepareFieldByInfo($name, $field, $componentParameters);
			}
		}

		return $entityFields;
	}

	protected function processFieldsAttributes(array $fields, int $mode, Item $item): array
	{
		$fieldsToHide = [];

		foreach($this->fieldsCollection->toArray() as $name => $field)
		{
			$isGenerated = \CCrmFieldInfoAttr::isFieldHasAttribute($field, \CCrmFieldInfoAttr::AutoGenerated);
			$isDisplayed = !\CCrmFieldInfoAttr::isFieldHasAttribute($field, \CCrmFieldInfoAttr::NotDisplayed);
			if($isGenerated && ($item->isNew() || $mode === ComponentMode::COPING))
			{
				$isDisplayed = false;
			}
			if (!$isDisplayed)
			{
				$fieldsToHide[$name] = $name;
			}
		}

		$entityFields = [];
		foreach ($fields as $field)
		{
			if (!isset($fieldsToHide[$field['name']]))
			{
				$entityFields[] = $field;
			}
		}

		return $entityFields;
	}

	protected function prepareFieldByInfo(string $name, array $info, $componentParameters = []): array
	{
		$entitySelectorContext = $componentParameters['entitySelectorContext'] ?? null;
		$isPageTitleEditable = $componentParameters['isPageTitleEditable'] ?? true;
		$titleCreationPlaceholder = $componentParameters['titleCreationPlaceholder'] ?? null;
		if (isset($info['SETTINGS']['editorDescription']) && is_array($info['SETTINGS']['editorDescription']))
		{
			return $info['SETTINGS']['editorDescription'];
		}

		$type = $info['TYPE'];
		$editable = !\CCrmFieldInfoAttr::isFieldReadOnly($info);

		$field = [
			'name' => $name,
			'title' => $info['TITLE'],
			'type' => $this->getFieldTypeByInfo($info),
			'editable' => $editable,
			'enableAttributes' => $this->isFieldAttributesEnabled($name, $info),
			'mergeable' => false,
		];

		if($type === Field::TYPE_USER)
		{
			$field['data'] = [
				'enableEditInView' => $editable,
				'pathToProfile' => Container::getInstance()->getRouter()->getUserPersonalUrlTemplate(),
			];

			if (\CCrmFieldInfoAttr::isFieldMultiple($info))
			{
				$field['data'] = array_merge($field['data'], [
					'map' => ['data' => $name],
					'infos' => $name . '_info',
					'messages' => ['addObserver' => Loc::getMessage('CRM_EDITORADAPTER_ADD_OBSERVER')],
				]);
			}
			else
			{
				$field['data'] = array_merge($field['data'], [
					'formated' => $name . '_FORMATTED_NAME',
					'position' => $name . '_WORK_POSITION',
					'photoUrl' => $name . '_PHOTO_URL',
					'showUrl' => $name . '_SHOW_URL',
				]);
			}
		}
		elseif($type === Field::TYPE_DATETIME)
		{
			$field['data'] = [
				'enableTime' => true,
			];
		}
		elseif ($type === Field::TYPE_DATE)
		{
			$field['data'] = [
				'enableTime' => false,
			];
		}
		elseif ($type === Field::TYPE_CRM_COMPANY)
		{
			$enableSearch = true;
			$enableMyCompanyOnly = $info['SETTINGS']['isMyCompany'] === true;
			if($enableMyCompanyOnly)
			{
				$myCompaniesCount = CompanyTable::getCount([
					'=IS_MY_COMPANY' => 'Y',
				]);
				if($myCompaniesCount <= static::MY_COMPANIES_COUNT_DISABLE_SEARCH)
				{
					$enableSearch = false;
				}
			}
			$field['data'] = [
				'typeId' => \CCrmOwnerType::Company,
				'entityTypeName' => \CCrmOwnerType::CompanyName,
				'enableMyCompanyOnly' => $enableMyCompanyOnly,
				'withRequisites' => $enableMyCompanyOnly,
				'info' => $name . '_info',
				'context' => $entitySelectorContext,
				'enableSearch' => $enableSearch,
			];
			if($enableMyCompanyOnly)
			{
				$field['data']['requisiteFieldNames'] = [
					'requisiteId' => static::FIELD_MY_COMPANY_REQUISITE_ID,
					'bankDetailId' => static::FIELD_MY_COMPANY_BANK_DETAIL_ID,
				];
			}
		}
		elseif ($type === Field::TYPE_CRM_DEAL)
		{
			$field['data'] = [
				'typeId' => \CCrmOwnerType::Deal,
				'entityTypeName' => \CCrmOwnerType::DealName,
				'info' => $name . '_info',
				'context' => $entitySelectorContext,
			];
		}
		elseif ($type === Field::TYPE_CRM_LEAD)
		{
			$field['data'] = [
				'typeId' => \CCrmOwnerType::Lead,
				'entityTypeName' => \CCrmOwnerType::LeadName,
				'info' => $name . '_info',
				'context' => $entitySelectorContext,
			];
		}
		elseif ($type === Field::TYPE_CRM_STATUS)
		{
			$fakeValue = '';
			$field['data']['items'][] = [
				'NAME' => Loc::getMessage('CRM_COMMON_NOT_SELECTED'),
				'VALUE' => $fakeValue,
			];

			$statusEntityId = $info['CRM_STATUS_TYPE'] ?? null;
			if ($statusEntityId)
			{
				foreach (StatusTable::getStatusesList($statusEntityId) as $statusId => $statusName)
				{
					$field['data']['items'][] = [
						'NAME' => $statusName,
						'VALUE' => $statusId,
					];
				}
				$field['data']['innerConfig'] = \CCrmInstantEditorHelper::prepareInnerConfig(
					$type,
					'crm.status.setItems',
					$statusEntityId,
					[$fakeValue]
				);
			}
		}

		if ($name === Item::FIELD_NAME_TITLE)
		{
			$field['showAlways'] = true;
			$field['isHeading'] = true;
			$field['visibilityPolicy'] = $isPageTitleEditable ? 'edit' : null;
			if ($titleCreationPlaceholder)
			{
				$field['placeholders']['creation'] = $titleCreationPlaceholder;
			}
		}

		if (
			($type === Field::TYPE_CRM_STATUS)
			&& $this->stages !== null
			&& (
				$name === Item::FIELD_NAME_STAGE_ID
				|| $name === Item::FIELD_NAME_PREVIOUS_STAGE_ID
			)
		)
		{
			$field['data']['items'] = [];

			foreach($this->stages->getAll() as $stage)
			{
				$field['data']['items'][] = [
					'NAME' => $stage->getName(),
					'VALUE' => $stage->getStatusId(),
				];
			}
		}

		return $field;
	}

	protected function getFieldTypeByInfo(array $fieldDescription): string
	{
		$type = $fieldDescription['TYPE'] ?? null;

		if($type === Field::TYPE_INTEGER || $type === Field::TYPE_DOUBLE)
		{
			return 'number';
		}
		if ($type === Field::TYPE_STRING)
		{
			return 'text';
		}
		if ($type === Field::TYPE_DATETIME || $type === Field::TYPE_DATE)
		{
			return 'datetime';
		}
		if ($type === Field::TYPE_TEXT)
		{
			return 'html';
		}
		if (
			$type === Field::TYPE_CRM_COMPANY
			|| $type === Field::TYPE_CRM_DEAL
			|| $type === Field::TYPE_CRM_LEAD
		)
		{
			return 'crm_entity_tag';
		}
		if ($type === Field::TYPE_CRM_STATUS)
		{
			return 'list';
		}
		if ($type === Field::TYPE_USER && \CCrmFieldInfoAttr::isFieldMultiple($fieldDescription))
		{
			return 'multiple_user';
		}

		return $type;
	}

	protected function isFieldAttributesEnabled(string $name, array $info): bool
	{
		if (
			$info['TYPE'] === Field::TYPE_DATE
			|| $info['TYPE'] === Field::TYPE_DATETIME
		)
		{
			return true;
		}

		if (
			$name === Item::FIELD_NAME_TITLE
			|| $name === Item::FIELD_NAME_SOURCE_ID
			|| $name === Item::FIELD_NAME_SOURCE_DESCRIPTION
			|| $name === Item::FIELD_NAME_XML_ID
		)
		{
			return true;
		}

		return false;
	}

	public static function getClientField(string $title, ?string $fieldName = null, ?string $fieldDataName = null): array
	{
		$fieldName = $fieldName ?? static::FIELD_CLIENT;
		return [
			'name' => $fieldName,
			'title' => $title,
			'type' => 'client_light',
			'editable' => true,
			'data' => [
				'compound' => [
					[
						'name' => 'COMPANY_ID',
						'type' => 'company',
						'entityTypeName' => \CCrmOwnerType::CompanyName,
						'tagName' => \CCrmOwnerType::CompanyName,
					],
					[
						'name' => 'CONTACT_IDS',
						'type' => 'multiple_contact',
						'entityTypeName' => \CCrmOwnerType::ContactName,
						'tagName' => \CCrmOwnerType::ContactName,
					],
				],
				'map' => ['data' => $fieldDataName ?? static::FIELD_CLIENT_DATA_NAME],
				'info' => $fieldName . '_INFO',
				'lastCompanyInfos' => 'LAST_COMPANY_INFOS',
				'lastContactInfos' => 'LAST_CONTACT_INFOS',
				'loaders' => [
					'primary' => [
						\CCrmOwnerType::CompanyName => [
							'action' => 'GET_CLIENT_INFO',
							'url' => '/bitrix/components/bitrix/crm.company.show/ajax.php?'.bitrix_sessid_get(),
						],
						\CCrmOwnerType::ContactName => [
							'action' => 'GET_CLIENT_INFO',
							'url' => '/bitrix/components/bitrix/crm.contact.show/ajax.php?'.bitrix_sessid_get(),
						],
					],
				],
				'clientEditorFieldsParams' => [
					\CCrmOwnerType::ContactName => [
						'REQUISITES' => \CCrmComponentHelper::getFieldInfoData(\CCrmOwnerType::Contact, 'requisite'),
						'ADDRESS' => \CCrmComponentHelper::getFieldInfoData(\CCrmOwnerType::Contact,'requisite_address'),
					],
					\CCrmOwnerType::CompanyName => [
						'REQUISITES' => \CCrmComponentHelper::getFieldInfoData(\CCrmOwnerType::Company, 'requisite'),
						'ADDRESS' => \CCrmComponentHelper::getFieldInfoData(\CCrmOwnerType::Company,'requisite_address'),
					],
				],
				'useExternalRequisiteBinding' => true,
			],
		];
	}

	public static function getParentField(
		string $title,
		int $parentEntityTypeId,
		?string $context = null
	): array
	{
		$fieldName = self::getParentFieldName($parentEntityTypeId);
		$entityTypeName = \CCrmOwnerType::ResolveName($parentEntityTypeId);

		return [
			'name' => $fieldName,
			'title' => $title,
			'type' => 'crm_entity_tag',
			'editable' => true,
			'data' => [
				'typeId' => $parentEntityTypeId,
				'entityTypeName' => $entityTypeName,
				'info' => $fieldName . '_info',
				'context' => $context,
				'parentEntityTypeId' => $parentEntityTypeId,
			],
			'enableAttributes' => false,
		];
	}

	public static function getProductRowSummaryField(string $title, ?string $fieldName = null): array
	{
		return [
			'name' => $fieldName ?? static::FIELD_PRODUCT_ROW_SUMMARY,
			'title' => $title,
			'type' => 'product_row_summary',
			'editable' => false,
			'enableAttributes' => false,
			'mergeable' => false,
			'transferable' => false,
			'showAlways' => true,
		];
	}

	public static function getOpportunityField(string $title, ?string $fieldName = null): array
	{
		return [
			'name' => $fieldName ?? static::FIELD_OPPORTUNITY,
			'title' => $title,
			'type' => 'money',
			'editable' => true,
			'mergeable' => false,
			'showAlways' => true,
			'data' => [
				'affectedFields' => [Item::FIELD_NAME_CURRENCY_ID, Item::FIELD_NAME_OPPORTUNITY],
				'currency' => [
					'name' => Item::FIELD_NAME_CURRENCY_ID,
					'items' => static::getCurrencyListEscaped(),
				],
				'amount' => Item::FIELD_NAME_OPPORTUNITY,
				'formatted' => 'FORMATTED_' . Item::FIELD_NAME_OPPORTUNITY,
				'formattedWithCurrency' => 'FORMATTED_' . static::FIELD_OPPORTUNITY,
			],
		];
	}

	public static function getUtmField(string $title, ?string $fieldName = null): array
	{
		if (!$fieldName)
		{
			$fieldName = static::FIELD_UTM;
		}
		return [
			'name' => $fieldName,
			'title' => $title,
			'type' => 'custom',
			'data' => [
				'view' => $fieldName . '_VIEW_HTML'
			],
			'editable' => false,
			'enableAttributes' => false,
		];
	}

	public static function getLocationFieldDescription(Field $field): array
	{
		return [
			'name' => $field->getName(),
			'title' => $field->getTitle(),
			'type' => 'custom',
			'data' => [
				'view' => $field->getName() . '_VIEW_HTML',
				'edit' => $field->getName() . '_EDIT_HTML',
			],
			'editable' => true,
			'enableAttributes' => true,
			'required' => $field->isRequired(),
			'showAlways' => true,
		];
	}

	protected static function getCurrencyListEscaped(): array
	{
		$currencyList = [];
		foreach (Currency::getCurrencyList() as $currencyId => $currencyName)
		{
			$currencyList[] = [
				'NAME' => htmlspecialcharsbx($currencyName),
				'VALUE' => $currencyId,
			];
		}

		return $currencyList;
	}

	protected static function getCurrencies(): array
	{
		if (Loader::includeModule('currency'))
		{
			$currencyIterator = CurrencyTable::getList([
				'select' => ['CURRENCY'],
			]);
			/** @var array $currency */
			while ($currency = $currencyIterator->fetch())
			{
				$currencyFormat = \CCurrencyLang::GetFormatDescription($currency['CURRENCY']);
				$currencyList[] = [
					'CURRENCY' => $currency['CURRENCY'],
					'FORMAT' => [
						'FORMAT_STRING' => $currencyFormat['FORMAT_STRING'],
						'DEC_POINT' => $currencyFormat['DEC_POINT'],
						'THOUSANDS_SEP' => $currencyFormat['THOUSANDS_SEP'],
						'DECIMALS' => $currencyFormat['DECIMALS'],
						'THOUSANDS_VARIANT' => $currencyFormat['THOUSANDS_VARIANT'],
						'HIDE_ZERO' => $currencyFormat['HIDE_ZERO'],
					],
				];
			}

			return $currencyList;
		}

		return [];
	}

	public static function getProductRowProxyController(
		string $productEditorId,
		?string $fieldName = 'PRODUCT_ROW_PROXY'
	): array
	{
		return [
			'name' => $fieldName,
			'type' => 'product_row_proxy',
			'config' => [
				'editorId' => $productEditorId,
			],
		];
	}

	public static function getProductListController(
		string $productListId,
		string $currencyId,
		?string $fieldName = self::CONTROLLER_PRODUCT_LIST
	): array
	{
		return [
			'name' => $fieldName,
			'type' => 'product_list',
			'config' => [
				'productListId' => $productListId,
				'currencyList' => static::getCurrencies(),
				'currencyId' => $currencyId,
			],
		];
	}

	protected function getEntityFieldAttributeConfigs(Item $item): array
	{
		return FieldAttributeManager::getEntityConfigurations(
			$item->getEntityTypeId(),
			FieldAttributeManager::getItemConfigScope($item)
		);
	}

	protected function isEntityFieldValueEmpty(string $fieldName, Item $item): bool
	{
		$itemFieldNames = [$fieldName];
		if (isset($this->dependantFieldsMap[$fieldName]))
		{
			$itemFieldNames = (array)$this->dependantFieldsMap[$fieldName];
		}
		foreach ($itemFieldNames as $itemFieldName)
		{
			$field = $this->fieldsCollection->getField($itemFieldName);
			if ($field && $field->isItemValueEmpty($item))
			{
				return true;
			}
		}

		return false;
	}

	public static function prepareEntityUserFields(
		array $userFields,
		array $visibilityConfig,
		int $entityTypeId,
		int $entityId,
		string $fileHandlerUrl = ''
	): array
	{
		$entityUserFields = [];
		$enumerationFields = [];
		$userFieldEntityID = \CCrmOwnerType::ResolveUserFieldEntityID($entityTypeId);
		foreach($userFields as $userField)
		{
			$fieldName = $userField['FIELD_NAME'];
			$fieldInfo = [
				'USER_TYPE_ID' => $userField['USER_TYPE_ID'],
				'ENTITY_ID' => $userFieldEntityID,
				'ENTITY_VALUE_ID' => $entityId,
				'FIELD' => $fieldName,
				'MULTIPLE' => $userField['MULTIPLE'],
				'MANDATORY' => $userField['MANDATORY'],
				'SETTINGS' => $userField['SETTINGS'] ?? null,
			];

			if($userField['USER_TYPE_ID'] === 'enumeration')
			{
				$enumerationFields[$fieldName] = $userField;
			}
			elseif($userField['USER_TYPE_ID'] === 'file')
			{
				$fieldInfo['ADDITIONAL'] = [
					'URL_TEMPLATE' => \CComponentEngine::MakePathFromTemplate(
						$fileHandlerUrl,
						[
							'owner_id' => $entityId,
							'field_name' => $fieldName,
						]
					),
				];
			}

			$entityUserFields[$fieldName] = [
				'name' => $fieldName,
				'title' => $userField['EDIT_FORM_LABEL'] ?? $fieldName,
				'type' => 'userField',
				'data' => ['fieldInfo' => $fieldInfo],
			];

			if(isset($userField['MANDATORY']) && $userField['MANDATORY'] === 'Y')
			{
				$entityUserFields[$fieldName]['required'] = true;
			}

			if ($userField['USER_TYPE_ID'] === 'crm_status')
			{
				if (
					is_array($userField['SETTINGS'])
					&& isset($userField['SETTINGS']['ENTITY_TYPE'])
					&& is_string($userField['SETTINGS']['ENTITY_TYPE'])
					&& $userField['SETTINGS']['ENTITY_TYPE'] !== ''
				)
				{
					$entityUserFields[$fieldName]['data']['innerConfig'] =
						\CCrmInstantEditorHelper::prepareInnerConfig(
							$userField['USER_TYPE_ID'],
							'crm.status.setItems',
							$userField['SETTINGS']['ENTITY_TYPE'],
							['']
						)
					;
				}
				unset($statusEntityId);
			}

			if(isset($visibilityConfig[$fieldName]))
			{
				$entityUserFields[$fieldName]['data']['visibilityConfigs'] = $visibilityConfig[$fieldName];
			}
		}

		if(!empty($enumerationFields))
		{
			$enumInfos = \CCrmUserType::PrepareEnumerationInfos($enumerationFields);
			foreach($enumInfos as $fieldName => $enums)
			{
				if(isset($entityUserFields[$fieldName]['data']['fieldInfo'])
				)
				{
					$entityUserFields[$fieldName]['data']['fieldInfo']['ENUM'] = $enums;
				}
			}
		}

		return $entityUserFields;
	}

	protected function normalizeFieldValue($value, string $fieldType)
	{
		if (is_array($value))
		{
			$result = [];
			foreach ($value as $singleValue)
			{
				$result[] = $this->prepareSingleValue($singleValue, $fieldType);
			}
		}
		else
		{
			$result = $this->prepareSingleValue($value, $fieldType);
		}

		return $result;
	}

	protected function prepareSingleValue($value, string $type)
	{
		if(is_float($value))
		{
			$value = sprintf('%f', $value);
			$value = rtrim($value, '0');
			$value = rtrim($value, '.');
		}
		elseif(is_object($value) && method_exists($value, '__toString'))
		{
			$value = $value->__toString();
		}
		elseif ($type === Field::TYPE_BOOLEAN)
		{
			if ($value !== 'Y' && $value !== 'N')
			{
				$value = $value ? 'Y' : 'N';
			}
		}
		elseif($value === false)
		{
			$value = '';
		}

		return $value;
	}

	protected function normalizeUserFieldValue($fieldValue, array $fieldParams): array
	{
		$isEmptyField = true;

		if ((is_string($fieldValue) && $fieldValue !== '')
			|| (is_numeric($fieldValue) && ($fieldValue !== 0 || $fieldParams['USER_TYPE_ID'] === BooleanType::USER_TYPE_ID))
			|| (is_array($fieldValue) && !empty($fieldValue))
			|| (is_object($fieldValue)))
		{
			if (is_array($fieldValue))
			{
				$fieldValue = array_values($fieldValue);
			}
			$fieldParams['VALUE'] = $fieldValue;
			$isEmptyField = false;
		}

		$fieldSignature = Dispatcher::instance()->getSignature($fieldParams);
		if ($isEmptyField)
		{
			$result = [
				'SIGNATURE' => $fieldSignature,
				'IS_EMPTY' => true,
			];
		}
		else
		{
			$result = [
				'VALUE' => $fieldValue,
				'SIGNATURE' => $fieldSignature,
				'IS_EMPTY' => false,
			];
		}

		return $result;
	}

	protected function prepareEntityDataForFieldsWithUsers(array $entityFields, array $entityData): array
	{
		$userIdToFieldNamesMap = [];
		$multipleFields = [];
		foreach ($entityFields as $fieldData)
		{
			if ($fieldData['type'] === 'user')
			{
				$fieldName = $fieldData['name'];
				$userId = (int)$entityData[$fieldName];
				$userIdToFieldNamesMap[$userId][] = $fieldName;
			}
			elseif ($fieldData['type'] === 'multiple_user')
			{
				$infosKey = $fieldData['data']['infos'];
				$userIdsKey = $fieldData['data']['map']['data'];
				// 'fieldname_info' => [1, 2, 3, 4]
				$multipleFields[$infosKey] = $entityData[$userIdsKey];
			}
		}

		$allPossibleUserIds = array_merge(array_keys($userIdToFieldNamesMap), ...array_values($multipleFields));
		$allPossibleUserIds = array_unique(array_filter($allPossibleUserIds));
		$users = Container::getInstance()->getUserBroker()->getBunchByIds($allPossibleUserIds);

		foreach ($userIdToFieldNamesMap as $userId => $arrayOfFieldNames)
		{
			$user = $users[$userId] ?? null;
			if (empty($user))
			{
				continue;
			}

			foreach ($arrayOfFieldNames as $fieldName)
			{
				$entityData[$fieldName.'_FORMATTED_NAME'] = $user['FORMATTED_NAME'];
				$entityData[$fieldName.'_WORK_POSITION'] = $user['WORK_POSITION'];
				$entityData[$fieldName.'_PHOTO_URL'] = $user['PHOTO_URL'];
				$entityData[$fieldName.'_SHOW_URL'] = $user['SHOW_URL'];
			}
		}

		foreach ($multipleFields as $infosKey => $userIds)
		{
			foreach ($userIds as $userId)
			{
				$user = $users[$userId] ?? null;
				if ($user)
				{
					$entityData[$infosKey][] = $user;
				}
			}
		}

		return $entityData;
	}

	/**
	 * @param array $entityFields
	 * @param array $entityData
	 * @return array
	 */
	protected function getEntityDataForEntityFields(
		Item $item,
		array $entityFields,
		array $entityData
	): array
	{
		foreach($entityFields as $field)
		{
			if ($field['type'] !== 'crm_entity' && $field['type'] !== 'crm_entity_tag')
			{
				continue;
			}
			$fieldName = $field['name'];
			$value = (int)$item->get($fieldName);
			$infoKey = $field['data']['info'] ?? ($field . '_info');
			if ($value > 0)
			{
				$entityData[$infoKey] = $this->prepareCrmEntityData($field, $value);
			}
		}

		return $entityData;
	}

	protected function getEntityDataForParentEntityFields(
		Item $item,
		array $entityFields,
		array $entityData
	): array
	{
		$relationManager = Container::getInstance()->getRelationManager();
		$child = new ItemIdentifier($item->getEntityTypeId(), $item->getId());
		$parentElements = $relationManager->getParentElements($child);

		if (!count($parentElements))
		{
			return $entityData;
		}

		$relations = [];
		foreach ($parentElements as $relation)
		{
			$relations[$relation->getEntityTypeId()] = $relation->getEntityId();
		}

		foreach($entityFields as $field)
		{
			if($field['type'] !== 'crm_entity_tag' || !isset($field['data']['typeId']))
			{
				continue;
			}

			$value = $relations[$field['data']['typeId']];
			$entityData = $this->addEntityDataForParentEntityField($field, $entityData, $value);
		}

		return $entityData;
	}

	protected function addEntityDataForParentEntityField(
		array $field,
		array $entityData,
		?int $value
	): array
	{
		$key = ($field['name'] ?? $field);
		$entityData[$key] = $value;

		$infoKey = $field['data']['info'] ?? ($field . '_info');
		if($value > 0)
		{
			$entityData[$infoKey] = $this->prepareCrmEntityData($field, $value);
		}

		return $entityData;
	}

	protected function prepareCrmEntityData(array $field, int $entityId): ?array
	{
		$entityTypeId = $field['data']['typeId'] ?? null;
		if(!\CCrmOwnerType::IsEntity($entityTypeId))
		{
			return null;
		}

		$entityTypeName = $field['data']['entityTypeName'] ?? \CCrmOwnerType::ResolveName($entityTypeId);

		$canRead = EntityAuthorization::checkReadPermission($entityTypeId, $entityId);

		$requireEditRequisiteData = (isset($field['data']['enableMyCompanyOnly']) && $field['data']['enableMyCompanyOnly'] === true);

		$data = \CCrmEntitySelectorHelper::PrepareEntityInfo(
			$entityTypeName,
			$entityId,
			[
				'ENTITY_EDITOR_FORMAT' => true,
				'IS_HIDDEN' => !$canRead,
				'REQUIRE_REQUISITE_DATA' => true,
				'REQUIRE_MULTIFIELDS' => true,
				'NAME_TEMPLATE' => PersonNameFormatter::getFormat(),
				'REQUIRE_EDIT_REQUISITE_DATA' => $requireEditRequisiteData,
			]
		);

		//in data selected always default requisites, we have to actualize it manually
		if ($requireEditRequisiteData)
		{
			if (
				isset($data['advancedInfo']['requisiteData'])
				&& is_array($data['advancedInfo']['requisiteData'])
			)
			{
				$entityRequisiteData = $this->getMyCompanyRequisitesEntityData($entityTypeId, $entityId);
				if ($entityRequisiteData[static::FIELD_MY_COMPANY_REQUISITE_ID] <= 0)
				{
					return $data;
				}
				foreach ($data['advancedInfo']['requisiteData'] as &$requisiteData)
				{
					$isSelected = (
						(int)$requisiteData['requisiteId']
						=== (int)$entityRequisiteData[static::FIELD_MY_COMPANY_REQUISITE_ID]
					);
					$requisiteData['selected'] = $isSelected;
					$requisiteData['bankDetailIdSelected'] = $entityRequisiteData[static::FIELD_MY_COMPANY_BANK_DETAIL_ID];
					if (
						$entityRequisiteData[static::FIELD_MY_COMPANY_BANK_DETAIL_ID] > 0
						&& $isSelected
						&& $requisiteData['requisiteData']
					)
					{
						$parsedRequisiteData = Json::decode($requisiteData['requisiteData']);
						if(is_array($parsedRequisiteData['bankDetailViewDataList']))
						{
							foreach($parsedRequisiteData['bankDetailViewDataList'] as &$bankDetailData)
							{
								$bankDetailData['selected'] = ((int)$bankDetailData['pseudoId'] === $entityRequisiteData[static::FIELD_MY_COMPANY_BANK_DETAIL_ID]);
							}
							unset($bankDetailData);
						}
						$requisiteData['requisiteData'] = Json::encode($parsedRequisiteData);
					}
				}
			}
		}

		return $data;
	}

	/**
	 * Returns products entity data, that was received from a source item
	 * If the editor is not in @see ComponentMode::CONVERSION mode or if products are empty, returns null
	 *
	 * @return array[]|null
	 */
	public function getSrcItemProductsEntityData(): ?array
	{
		return $this->srcItemProductsEntityData;
	}

	protected function getProductsSummaryEntityData(Item $item): array
	{
		$products = $item->getProductRows();
		if (is_null($products))
		{
			return [];
		}

		$rowData = [];
		$numberOfProcessedProducts = 1;
		foreach ($products as $product)
		{
			$url = '';
			if ($product->getProductId() > 0)
			{
				$url = Container::getInstance()->getRouter()->getProductDetailUrl($product->getProductId());
			}

			$rowData[] = [
				'PRODUCT_NAME' => $product->getProductName(),
				'SUM' => Money::format($product->getPrice() * $product->getQuantity(), $item->getCurrencyId()),
				'URL' => $url,
			];

			$numberOfProcessedProducts++;
			if ($numberOfProcessedProducts >= static::MAX_PRODUCT_ROWS_IN_SUMMARY)
			{
				break;
			}
		}

		$total = 0;
		if (count($products) > 0)
		{
			$total = Container::getInstance()->getAccounting()->calculateByItem($item)->getPrice();
		}

		return [
			'count' => count($products),
			'total' => Money::format($total, $item->getCurrencyId()),
			'items' => $rowData,
		];
	}

	protected function getOpportunityEntityData(Item $item): array
	{
		// OPPORTUNITY and CURRENCY_ID themselves are not displayed, but their data is used for OPPORTUNITY_WITH_CURRENCY
		// IS_MANUAL_OPPORTUNITY is used for switching calculation mode in the editor
		$specialFields = [
			Item::FIELD_NAME_OPPORTUNITY,
			Item::FIELD_NAME_CURRENCY_ID,
			Item::FIELD_NAME_IS_MANUAL_OPPORTUNITY,
		];

		$opportunityEntityData = [];
		foreach ($specialFields as $fieldName)
		{
			$field = $this->fieldsCollection->getField($fieldName);
			if ($field)
			{
				$fieldType = $field->getType();
				$fieldValue = $item->get($fieldName);
				$opportunityEntityData[$fieldName] = $this->normalizeFieldValue($fieldValue, $fieldType);
			}
		}

		$opportunityEntityData['FORMATTED_' . Item::FIELD_NAME_OPPORTUNITY] = Money::formatWithCustomTemplate(
			$item->getOpportunity(), $item->getCurrencyId()
		);
		$opportunityEntityData['FORMATTED_' . static::FIELD_OPPORTUNITY] = Money::format(
			$item->getOpportunity(), $item->getCurrencyId()
		);

		return $opportunityEntityData;
	}

	public function getClientEntityData(): array
	{
		return $this->clientEntityData;
	}

	protected function prepareClientEntityData(Item $item, string $componentName): array
	{
		$clientEntityData = [];
		if ($item->getCompanyId() > 0)
		{
			$clientEntityData[static::FIELD_CLIENT . '_INFO']['COMPANY_DATA'][] = $this->generateClientInfo(
				\CCrmOwnerType::Company,
				$item->getCompanyId()
			);
		}

		// If item is not new and contacts are fetched
		if (!is_null($item->getContacts()))
		{
			$isFirstContact = true;
			foreach ($item->getContacts() as $contact)
			{
				// Load full edit requisites only for the first Contact (performance optimization)
				$clientEntityData[static::FIELD_CLIENT . '_INFO']['CONTACT_DATA'][] = $this->generateClientInfo(
					\CCrmOwnerType::Contact,
					$contact->getId(),
					$isFirstContact
				);
				$isFirstContact = false;
			}
		}

		$lastClientInfoMap = ['LAST_COMPANY_INFOS' => \CCrmOwnerType::Company, 'LAST_CONTACT_INFOS' => \CCrmOwnerType::Contact];
		foreach ($lastClientInfoMap as $arrayKey => $entityTypeId)
		{
			$clientEntityData[$arrayKey] = $this->getRecentlyUsedItems($entityTypeId, $componentName);
		}

		return $clientEntityData;
	}

	protected function generateClientInfo(
		int $clientEntityTypeId,
		int $clientEntityId,
		bool $isEditRequisiteDataRequired = true
	): array
	{
		$canReadClient = EntityAuthorization::checkReadPermission($clientEntityTypeId, $clientEntityId);

		$clientInfo = \CCrmEntitySelectorHelper::PrepareEntityInfo(
			\CCrmOwnerType::ResolveName($clientEntityTypeId),
			$clientEntityId,
			[
				'ENTITY_EDITOR_FORMAT' => true,
				'IS_HIDDEN' => !$canReadClient,
				'USER_PERMISSIONS' => Container::getInstance()->getUserPermissions()->getCrmPermissions(),
				'REQUIRE_REQUISITE_DATA' => true,
				'REQUIRE_EDIT_REQUISITE_DATA' => $isEditRequisiteDataRequired,
				'REQUIRE_MULTIFIELDS' => true,
				'NORMALIZE_MULTIFIELDS' => true,
				'NAME_TEMPLATE' => PersonNameFormatter::getFormat(),
			]
		);

		if (isset($clientInfo['notFound']) && $clientInfo['notFound'] === true)
		{
			return [];
		}

		return $clientInfo;
	}

	protected function getRecentlyUsedItems(int $entityTypeId, string $componentName): array
	{
		return SearchAction::prepareSearchResultsJson(
			Entity::getRecentlyUsedItems(
				$componentName,
				mb_strtolower(\CCrmOwnerType::ResolveName($entityTypeId)),
				['EXPAND_ENTITY_TYPE_ID' => $entityTypeId]
			)
		);
	}

	protected function getApplication(): \CAllMain
	{
		global $APPLICATION;
		return $APPLICATION;
	}

	public static function getUtmEntityData(Item $item): string
	{
		ob_start();
		global $APPLICATION;
		$APPLICATION->IncludeComponent(
			'bitrix:crm.utm.entity.view',
			'',
			['FIELDS' => $item->getUtm()],
			false,
			['HIDE_ICONS' => 'Y', 'ACTIVE_COMPONENT' => 'Y']
		);

		return ob_get_clean();
	}

	public static function getLocationFieldHtml(Item $item, string $fieldName): ?string
	{
		if (!Loader::includeModule('sale'))
		{
			return null;
		}
		ob_start();
		\CSaleLocation::proxySaleAjaxLocationsComponent(
			[
				'AJAX_CALL' => 'N',
				'COUNTRY_INPUT_NAME' => $fieldName . '_COUNTRY',
				'REGION_INPUT_NAME' => $fieldName . '_REGION',
				'CITY_INPUT_NAME' => $fieldName,
				'CITY_OUT_LOCATION' => 'Y',
				'LOCATION_VALUE' => $item->get($fieldName),
				'ORDER_PROPS_ID' => "",
				'SHOW_QUICK_CHOOSE' => 'Y',
			],
			[
				"CODE" => $item->get($fieldName),
				"ID" => "",
				"PROVIDE_LINK_BY" => "code",
			],
			'popup'
		);

		return ob_get_clean();
	}

	protected function getMyCompanyRequisitesEntityData(int $entityTypeId, int $id): array
	{
		if($id > 0)
		{
			$currentData = (array) EntityLink::getByEntity($entityTypeId, $id);
		}
		else
		{
			$currentData = [];
		}

		return [
			static::FIELD_MY_COMPANY_REQUISITE_ID => (int) $currentData[static::FIELD_MY_COMPANY_REQUISITE_ID],
			static::FIELD_MY_COMPANY_BANK_DETAIL_ID => (int) $currentData[static::FIELD_MY_COMPANY_BANK_DETAIL_ID],
		];
	}

	protected function prepareRequisiteBindings(Item $item): array
	{
		$requisite = new EntityRequisite();

		return $requisite->getEntityRequisiteBindings(
			$item->getEntityTypeId(),
			$item->getId(),
			$item->getCompanyId(),
			$item->getContactId()
		);
	}

	/**
	 * Saves data with clients from editor.
	 * @see \Bitrix\Crm\Service\EditorAdapter::getClientField()
	 *
	 * @param Item $item - where clients should be bind to.
	 * @param string $clientJson - data from editor.
	 * @return Result
	 */
	public function saveClientData(Item $item, string $clientJson): Result
	{
		$processedEntities = [];

		/** @var array $clientData */
		$clientData = \CUtil::JsObjectToPhp($clientJson);
		if (!is_array($clientData))
		{
			$clientData = [];
		}

		$companyData = $clientData['COMPANY_DATA'][0] ?? [];
		if ($companyData)
		{
			$entityResult = $this->saveClientEntity(\CCrmOwnerType::Company, $companyData);
			if ($entityResult->isSuccess() && $entityResult->getData()['id'])
			{
				$companyId = $entityResult->getData()['id'];
				$item->setCompanyId($companyId);
				$processedEntities[] = new ItemIdentifier(\CCrmOwnerType::Company, $companyId);
			}
		}
		elseif (!empty($item->getCompanyId()))
		{
			// $companyData was not sent, but a company is bound with the item. It means the company was unbound from the item.
			$item->setCompanyId(0);
		}

		$contactData = $clientData['CONTACT_DATA'] ?? [];
		if ($contactData)
		{
			foreach ($contactData as &$contact)
			{
				$entityResult = $this->saveClientEntity(\CCrmOwnerType::Contact, $contact);
				if (!$entityResult->isSuccess())
				{
					continue;
				}

				$contact['id'] = $entityResult->getData()['id'];
				$processedEntities[] = new ItemIdentifier(\CCrmOwnerType::Contact, $contact['id']);
			}
			unset($contact);

			$contactBindings = EntityBinding::prepareEntityBindings(\CCrmOwnerType::Contact, array_column($contactData, 'id'));
			$item->bindContacts($contactBindings);
		}

		if (!empty($item->getContacts()) && count($item->getContacts()) > 0)
		{
			$deletedContactIds = $this->findDeletedEntries($item->getContacts(), $contactData, 'id');
			$deletedContactsBindings = EntityBinding::prepareEntityBindings(\CCrmOwnerType::Contact, $deletedContactIds);
			$item->unbindContacts($deletedContactsBindings);
		}

		return (new Result())->setData([
			'processedEntities' => $processedEntities,
		]);
	}

	protected function saveClientEntity(int $entityTypeId, array $data): Result
	{
		$result = new Result();

		$id = isset($data['id']) ? (int)$data['id'] : 0;
		if ($id <= 0)
		{
			$id = BaseComponent::createEntity($entityTypeId,
				$data,
				[
					'userPermissions' => Container::getInstance()->getUserPermissions()->getCrmPermissions(),
					'startWorkFlows' => true,
				]);

			if ($id <= 0)
			{
				$entityName = \CCrmOwnerType::ResolveName($entityTypeId);
				return $result->addError(new Error("Can't create a new $entityName"));
			}
		}
		elseif (!empty($data['title'])
			|| (isset($data['multifields']) && is_array($data['multifields']))
			|| (isset($data['requisites']) && is_array($data['requisites'])))
		{
			BaseComponent::updateEntity($entityTypeId,
				$id,
				$data,
				[
					'userPermissions' => Container::getInstance()->getUserPermissions()->getCrmPermissions(),
					'startWorkFlows' => true,
				]);
		}

		return $result->setData(['id' => $id]);
	}

	/**
	 * Saves data about products from interface.
	 * Data generated by component crm.entity.product.list.
	 * @see \Bitrix\Crm\Component\EntityDetails\FactoryBased::getDefaultTabInfoByCode() with 'tab_products'
	 *
	 * @param Item $item
	 * @param string $productsJson
	 *
	 * @return Result
	 */
	public function saveProductsData(Item $item, string $productsJson): Result
	{
		/** @var array[] $productRows */
		$productRows = \CUtil::JsObjectToPhp($productsJson);
		if (!is_array($productRows))
		{
			$productRows = [];
		}

		// When coping or converting, products from a source item are sent here. To avoid accidental rebinding of the products
		// from the source item to this item, we should unset ID to ensure that the products are treated as new ones
		if ($item->isNew())
		{
			foreach ($productRows as &$productRow)
			{
				unset($productRow['ID']);
			}
		}

		return $item->setProductRowsFromArrays($productRows);
	}

	public function saveRelations(Item $item, array $data): void
	{
		$child = ItemIdentifier::createByItem($item);
		$relationManager = Container::getInstance()->getRelationManager();

		$oldParentElements = $this->prepareParentElements($relationManager->getParentElements($child));
		$newParentElements = $this->prepareParentElements($this->getParentElements($data));

		$parentTypeId = (int)$data['PARENT_TYPE_ID'];
		$parentId = (int)$data['PARENT_ID'];
		if ($parentTypeId > 0 && $parentId > 0 && !isset($newParentElements[$parentTypeId]))
		{
			$newParentElements[$parentTypeId] = new ItemIdentifier($parentTypeId, $parentId);
		}

		$parentPrefix = self::FIELD_PARENT_PREFIX;

		// delete removed relations
		foreach ($data as $name => $value)
		{
			if (empty($value) && mb_strpos($name, $parentPrefix) === 0)
			{
				/** @var string $entityTypeId */
				$entityTypeId = str_replace($parentPrefix, '', $name);
				if (isset($oldParentElements[$entityTypeId]))
				{
					$relationManager->unbindItems($oldParentElements[$entityTypeId], $child);
				}
			}
		}

		// bind new items and change existing (not deleted) relations
		foreach (array_diff($newParentElements, $oldParentElements) as $bindItem)
		{
			$oldParent = ($oldParentElements[$bindItem->getEntityTypeId()] ?? null);
			if ($oldParent && $bindItem->getEntityId() !== $oldParent->getEntityId())
			{
				$relationManager->unbindItems($oldParentElements[$bindItem->getEntityTypeId()], $child);
			}
			$relationManager->bindItems($bindItem, $child);
		}
	}

	/**
	 * @param ItemIdentifier[] $parentElements
	 * @return ItemIdentifier[]
	 */
	protected function prepareParentElements(array $parentElements): array
	{
		$results = [];
		foreach ($parentElements as $element)
		{
			$results[$element->getEntityTypeId()] = $element;
		}

		return $results;
	}

	protected function getParentElements(array $data): array
	{
		$newParentElements = [];
		foreach($data as $name => $value)
		{
			if (($value > 0) && (mb_strpos($name, self::FIELD_PARENT_PREFIX) === 0))
			{
				$parentEntityTypeId = str_replace(self::FIELD_PARENT_PREFIX, '', $name);
				$newParentElements[] = new ItemIdentifier(
					$parentEntityTypeId,
					(int)$value
				);
			}
		}

		return $newParentElements;
	}

	protected function findDeletedEntries(array $existingEntries, array $sentEntries, $arrayKey): array
	{
		// crm editor sends all existing entries (e.g., all products, contacts).
		// If some of them were not sent, it means they were deleted.
		$deletedEntries = [];
		foreach ($existingEntries as $existing)
		{
			$wasFound = false;
			foreach ($sentEntries as $sent)
			{
				if (isset($sent[$arrayKey]) && $sent[$arrayKey] === $existing[$arrayKey])
				{
					$wasFound = true;
				}
			}

			if (!$wasFound)
			{
				$deletedEntries[] = $existing[$arrayKey];
			}
		}

		return $deletedEntries;
	}

	public function getContext(): array
	{
		return $this->context;
	}

	public function setContext(array $context): void
	{
		$this->context = $context;
	}

	public static function combineConfigIntoOneSection(array $entityConfig, string $title = null): array
	{
		$resultSection = [
			'name' => 'main',
			'title' => $title,
			'type' => 'section',
			'elements' => [],
			'data' => [
				'enableTitle' => !empty($title),
				'isRemovable' => false,
			],
		];

		foreach ($entityConfig as $section)
		{
			if (!empty($section['elements']))
			{
				$resultSection['elements'] = array_merge($resultSection['elements'], $section['elements']);
			}
		}

		return [$resultSection];
	}

	public static function markFieldsAsRequired(array $entityFields, array $requiredFieldNames): array
	{
		foreach ($requiredFieldNames as $fieldName)
		{
			foreach ($entityFields as &$fieldInfo)
			{
				if ($fieldInfo['name'] === $fieldName)
				{
					$fieldInfo['required'] = true;
					if (isset($fieldInfo['data']['fieldInfo']))
					{
						$fieldInfo['data']['fieldInfo']['MANDATORY'] = 'Y';
					}
				}
			}
		}

		return $entityFields;
	}

	public static function getParentFieldName(int $parentEntityTypeId): string
	{
		return static::FIELD_PARENT_PREFIX . $parentEntityTypeId;
	}
}
