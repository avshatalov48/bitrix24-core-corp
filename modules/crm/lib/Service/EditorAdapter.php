<?php

namespace Bitrix\Crm\Service;

use Bitrix\Catalog;
use Bitrix\Catalog\Access\AccessController;
use Bitrix\Catalog\Access\ActionDictionary;
use Bitrix\Crm\Attribute\FieldAttributeManager;
use Bitrix\Crm\Binding\EntityBinding;
use Bitrix\Crm\CompanyTable;
use Bitrix\Crm\Component\EntityDetails\BaseComponent;
use Bitrix\Crm\Component\EntityDetails\ComponentMode;
use Bitrix\Crm\Controller\Action\Entity\SearchAction;
use Bitrix\Crm\Controller\Entity;
use Bitrix\Crm\Conversion\EntityConversionWizard;
use Bitrix\Crm\Currency;
use Bitrix\Crm\Entity\CommentsHelper;
use Bitrix\Crm\Entity\EntityEditor;
use Bitrix\Crm\Entity\Traits\VisibilityConfig;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\EO_Status_Collection;
use Bitrix\Crm\Field;
use Bitrix\Crm\Format\Money;
use Bitrix\Crm\Format\PersonNameFormatter;
use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Order;
use Bitrix\Crm\Requisite\EntityLink;
use Bitrix\Crm\Security\EntityAuthorization;
use Bitrix\Crm\Security\PermissionToken;
use Bitrix\Crm\StatusTable;
use Bitrix\Crm\UI\EntitySelector;
use Bitrix\Currency\CurrencyTable;
use Bitrix\Main\InvalidOperationException;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Request;
use Bitrix\Main\Result;
use Bitrix\Main\UserField\Dispatcher;
use Bitrix\Main\UserField\Types\BooleanType;
use Bitrix\Main\Web\Json;
use CCrmComponentHelper;
use CCrmOwnerType;

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
	public const FIELD_ACCOUNT_OPPORTUNITY = 'OPPORTUNITY_ACCOUNT_WITH_CURRENCY';
	public const FIELD_UTM = 'UTM';
	public const FIELD_FILES = 'FILES';
	public const FIELD_CLIENT_DATA_NAME = 'CLIENT_DATA';
	public const FIELD_PARENT_PREFIX = 'PARENT_ID_';
	public const FIELD_MY_COMPANY_DATA_NAME = 'MYCOMPANY_ID_DATA';
	public const FIELD_MY_COMPANY_DATA_INFO = 'MYCOMPANY_ID_INFO';

	public const LAST_COMPANY_INFOS = 'LAST_COMPANY_INFOS';
	public const LAST_CONTACT_INFOS = 'LAST_CONTACT_INFOS';
	public const LAST_MYCOMPANY_INFOS = 'LAST_MYCOMPANY_INFOS';

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

	protected $myCompanyRequisites;
	protected $entityFields;
	protected $entityUserFields;
	protected $entityData;
	protected $processedEntityFields;
	protected $clientEntityData;
	/** @var array[]|null */
	protected $srcItemProductsEntityData;
	protected $context = [];

	private bool $isSearchHistoryEnabled = true;

	private Item $item;

	public function __construct(Field\Collection $fieldsCollection, array $dependantFieldsMap = [])
	{
		$this->fieldsCollection = $fieldsCollection;
		$this->dependantFieldsMap = $dependantFieldsMap;
	}

	public function enableSearchHistory(bool $state): EditorAdapter
	{
		$this->isSearchHistoryEnabled = $state;

		return $this;
	}

	/**
	 * Return true if method processByItem has been invoked.
	 *
	 * @return bool
	 */
	public function hasData(): bool
	{
		return !empty($this->processedEntityFields);
	}

	/**
	 * Return true if new products list enabled.
	 *
	 * @return bool
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function isProductListEnabled(): bool
	{
		return (
			Loader::includeModule('catalog')
			&& Catalog\Config\Feature::isCommonProductProcessingEnabled()
		);
	}

	/**
	 * Process information on $item that can be placed on $stages with $componentParameters.
	 * This method does not change $item.
	 *
	 * @param Item $item
	 * @param EO_Status_Collection $stages
	 * @param array $componentParameters
	 * @return $this
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function processByItem(Item $item, EO_Status_Collection $stages, array $componentParameters = []): self
	{
		$this->item = $item;

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

		$this->myCompanyRequisites = $this->loadMyCompanyRequisitesEntityData($item->getEntityTypeId(), $item->getId());

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
				$fieldValueNormalized = $this->normalizeUserFieldValue(
					$fieldValueNormalized,
					$this->isEntityFieldValueEmpty($fieldName, $item),
					$fieldData['data']['fieldInfo'],
				);
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
		elseif ($conversionWizard)
		{
			$this->addParentFieldsEntityData([
					new ItemIdentifier(
						$conversionWizard->getEntityTypeID(),
						$conversionWizard->getEntityID(),
					)
				],
				$this->entityFields,
				$this->entityData,
			);
		}

		$this->entityData = CommentsHelper::prepareFieldsFromEditorAdapterToView(
			$item->getEntityTypeId(),
			$this->entityData,
		);

		if (isset($this->context['PARENT_TYPE_ID']) && $item->isNew())
		{
			$parentFieldName = self::getParentFieldName($this->context['PARENT_TYPE_ID']);
			if (!empty($this->additionalFields[$parentFieldName]['name']))
			{
				$this->entityData = $this->addEntityDataForParentEntityField(
					$this->additionalFields[$parentFieldName],
					$this->entityData,
					$this->context['PARENT_ID']
				);
			}
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
			$this->entityData[static::FIELD_PRODUCT_ROW_SUMMARY] = $this->getProductsSummaryEntityData($item, $mode);
			$this->entityData = array_merge($this->entityData, $this->getOpportunityEntityData($item));
		}

		$this->entityData[static::FIELD_REQUISITE_BINDING] = $this->prepareRequisiteBindings($item);

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

		if ($item->hasField(Item::FIELD_NAME_OPPORTUNITY) && $item->hasField(Item::FIELD_NAME_PRODUCTS))
		{
			$this->entityData['ORDER_LIST'] = static::getOrderList($item);
		}

		$this->fillMyCompanyDataForEmbeddedEditorField($item);

		return $this;
	}

	protected function transferDataFromSrcItemToDstItem(
		Item $destinationItem,
		EntityConversionWizard $conversionWizard
	): void
	{
		if ($conversionWizard->isNewApi())
		{
			$conversionWizard->fillDestinationItemWithDataFromSourceItem($destinationItem);

			if ($destinationItem->hasField(Item::FIELD_NAME_PRODUCTS))
			{
				$productRows = $destinationItem->getProductRows();
				$this->srcItemProductsEntityData = $productRows ? $productRows->toArray() : null;
			}

			return;
		}

		$srcItemData = [];
		$srcItemUserFieldsData = [];
		EntityEditor::prepareConvesionMap(
			$conversionWizard,
			$destinationItem->getEntityTypeId(),
			$srcItemData,
			$srcItemUserFieldsData
		);

		if(isset($srcItemData['CONTACT_IDS']))
		{
			$destinationItem->bindContacts(
				EntityBinding::prepareEntityBindings(CCrmOwnerType::Contact, (array)$srcItemData['CONTACT_IDS'])
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
	 */
	protected function addParentRelationFields(int $childEntityTypeId, ?string $context = null): void
	{
		$parentFieldsInfo = $this->getParentFieldsInfo($childEntityTypeId, $context);

		foreach ($parentFieldsInfo as $parentField)
		{
			$this->addEntityField($parentField);
		}
	}

	/**
	 * Add additional $field to entityFields.
	 *
	 * @param array $field
	 * @return $this
	 */
	public function addEntityField(array $field): self
	{
		$this->additionalFields[$field['name']] = $field;

		return $this;
	}

	/**
	 * Return processed entityData.
	 * If processByItem has not been invoked - throws InvalidOperationException.
	 *
	 * @return array
	 * @throws InvalidOperationException
	 */
	public function getEntityFields(): array
	{
		if (!$this->hasData())
		{
			throw new InvalidOperationException('call EditorAdapter::processByItem() first');
		}
		return $this->processedEntityFields;
	}

	/**
	 * Add additional $value by $name to entityData.
	 * If processByItem has not been invoked - throws InvalidOperationException.
	 *
	 * @param $name
	 * @param $value
	 * @return $this
	 * @throws InvalidOperationException
	 */
	public function addEntityData($name, $value): self
	{
		if (!$this->hasData())
		{
			throw new InvalidOperationException('call EditorAdapter::processByItem() first');
		}
		$this->entityData[$name] = $value;

		return $this;
	}

	/**
	 * Return processed entityData.
	 * If processByItem has not been invoked - throws InvalidOperationException.
	 *
	 * @return array
	 * @throws InvalidOperationException
	 */
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
			$isDisplayed = !\CCrmFieldInfoAttr::isFieldHasAttribute($field, \CCrmFieldInfoAttr::NotDisplayed);
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
			'mergeable' => $this->isFieldMergeable($name, $info),
		];

		$isFlexibleContentType = $info['SETTINGS']['isFlexibleContentType'] ?? false;
		if ($isFlexibleContentType === true)
		{
			$field =
				CommentsHelper::compileFieldDescriptionForDetails(
					$this->item->getEntityTypeId(),
					$name,
					isset($this->entityData['ID']) ? (int)$this->entityData['ID'] : 0,
				)
				+ $field
			;
		}

		if ($type === Field::TYPE_TEXT)
		{
			$field['data']['lineCount'] = 6;
		}
		elseif($type === Field::TYPE_USER)
		{
			$field['data'] = [
				'enableEditInView' => $editable,
				'pathToProfile' => Container::getInstance()->getRouter()->getUserPersonalUrlTemplate(),
			];

			if (\CCrmFieldInfoAttr::isFieldMultiple($info))
			{
				$field['data'] = array_merge($field['data'], [
					'map' => ['data' => $name],
					'infos' => $name . '_INFO',
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
			$enableMyCompanyOnly = (isset($info['SETTINGS']['isMyCompany']) && $info['SETTINGS']['isMyCompany'] === true);
			if ($enableMyCompanyOnly)
			{
				$enableEmbeddedEditor = (isset($info['SETTINGS']['isEmbeddedEditorEnabled']) && $info['SETTINGS']['isEmbeddedEditorEnabled'] === true);
				if ($enableEmbeddedEditor)
				{
					$myCompanyFieldWithEditor = static::getMyCompanyFieldWithEditor();

					$ownerEntityTypeId = $info['SETTINGS']['ownerEntityTypeId'] ?? null;
					$ownerEntityId = is_array($this->entityData) ? ($this->entityData['ID'] ?? null) : null;
					if (!is_null($ownerEntityTypeId))
					{
						$ownerEntityTypeId = (int)$ownerEntityTypeId;
					}
					if (!is_null($ownerEntityId))
					{
						$ownerEntityId = (int)$ownerEntityId;
					}

					if ($ownerEntityTypeId)
					{
						$myCompanyFieldWithEditor['data']['ownerEntityTypeId'] = $ownerEntityTypeId;
						$myCompanyFieldWithEditor['data']['ownerEntityId'] = $ownerEntityId;

						if ($info['SETTINGS']['enableCreationByOwnerEntity'] ?? false)
						{
							$myCompanyFieldWithEditor['data']['enableCreation'] =
								Container::getInstance()->getUserPermissions()->getMyCompanyPermissions()->canAddByOwnerEntity($ownerEntityTypeId);
						}
					}
					if (($info['SETTINGS']['usePermissionToken'] ?? false) && $ownerEntityTypeId)
					{
						$myCompanyFieldWithEditor['data']['permissionToken'] =  PermissionToken::createEditMyCompanyRequisitesToken(
							$ownerEntityTypeId,
							$ownerEntityId
						);
					}

					return $myCompanyFieldWithEditor;
				}
				$myCompaniesCount = CompanyTable::getCount([
					'=IS_MY_COMPANY' => 'Y',
				]);
				if ($myCompaniesCount <= static::MY_COMPANIES_COUNT_DISABLE_SEARCH)
				{
					$enableSearch = false;
				}
			}
			$field['data'] = [
				'typeId' => CCrmOwnerType::Company,
				'entityTypeName' => CCrmOwnerType::CompanyName,
				'enableMyCompanyOnly' => $enableMyCompanyOnly,
				'withRequisites' => $enableMyCompanyOnly,
				'info' => $name . '_INFO',
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
				'typeId' => CCrmOwnerType::Deal,
				'entityTypeName' => CCrmOwnerType::DealName,
				'info' => $name . '_INFO',
				'context' => $entitySelectorContext,
			];
		}
		elseif ($type === Field::TYPE_CRM_LEAD)
		{
			$field['data'] = [
				'typeId' => CCrmOwnerType::Lead,
				'entityTypeName' => CCrmOwnerType::LeadName,
				'info' => $name . '_INFO',
				'context' => $entitySelectorContext,
			];
		}
		elseif ($type === Field::TYPE_CRM_ENTITY && ParentFieldManager::isParentFieldName($name))
		{
			$field['data'] = [
				'typeId' => ParentFieldManager::getEntityTypeIdFromFieldName($name),
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

		$fieldObject = $this->fieldsCollection->getField($name);
		if ($fieldObject instanceof Field\Number)
		{
			$field['type'] = 'document_number';
			$field['showAlways'] = true;
			$number = $fieldObject->previewNextNumber();
			if (!empty($number))
			{
				$field['placeholders']['creation'] = $number;
			}
			if (Container::getInstance()->getUserPermissions()->canWriteConfig())
			{
				$numerator = $fieldObject->getNumerator();
				if ($numerator)
				{
					$numeratorSettingsUrl = Container::getInstance()->getRouter()->getNumeratorSettingsUrl(
						$numerator->getId(),
						$fieldObject->getNumeratorType(),
					);
					if ($numeratorSettingsUrl)
					{
						$numeratorSettingsUrl->addParams([
							'IS_HIDE_NUMERATOR_NAME' => 1,
						]);
						$field['data']['numeratorSettingsUrl'] = $numeratorSettingsUrl->getLocator();
					}
				}
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
			if (isset($fieldDescription['VALUE_TYPE']) && $fieldDescription['VALUE_TYPE'] === Field::VALUE_TYPE_HTML)
			{
				return 'html';
			}
			if (isset($fieldDescription['VALUE_TYPE']) && $fieldDescription['VALUE_TYPE'] === Field::VALUE_TYPE_BB)
			{
				return 'bb';
			}

			return 'text';
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

	protected function isFieldMergeable(string $name, array $info): bool
	{
		$attributes = $info['ATTRIBUTES'] ?? [];

		$defaultlyNotMergeable = [
			Item::FIELD_NAME_OPPORTUNITY,
			Item::FIELD_NAME_STAGE_ID,
			Item::FIELD_NAME_OBSERVERS,
		];

		$isDefaultlyNotMergeable = in_array($name, $defaultlyNotMergeable, true);
		$isAutoGenerated = in_array(\CCrmFieldInfoAttr::AutoGenerated, $attributes, true);
		$isUnique = in_array(\CCrmFieldInfoAttr::Unique, $attributes, true);

		return
			!$isDefaultlyNotMergeable
			&& !$isAutoGenerated
			&& !$isUnique
		;
	}

	/**
	 * Return field description for CLIENT field (with embedded editor)
	 *
	 * @param string $title
	 * @param string|null $fieldName
	 * @param string|null $fieldDataName
	 * @param array $options
	 * @return array
	 */
	public static function getClientField(
		string $title,
		?string $fieldName = self::FIELD_CLIENT,
		?string $fieldDataName = self::FIELD_CLIENT_DATA_NAME,
		array $options = []
	): array
	{
		$showAlways = (bool)($options['showAlways'] ?? true);
		$enableTooltip = !isset($options['enableTooltip']) || (bool)$options['enableTooltip'];

		// TODO: need to detect category ID when will implement real category params feature
		$categoryParams = CCrmComponentHelper::getEntityClientFieldCategoryParams((int)$options['entityTypeId']);

		return [
			'name' => $fieldName,
			'title' => $title,
			'type' => 'client_light',
			'editable' => true,
			'showAlways' => $showAlways,
			'data' => [
				'affectedFields' => [$fieldName . '_INFO'],
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
					],
				],
				'categoryParams' => $categoryParams,
				'map' => ['data' => $fieldDataName],
				'info' => $fieldName . '_INFO',
				'lastCompanyInfos' => static::LAST_COMPANY_INFOS,
				'lastContactInfos' => static::LAST_CONTACT_INFOS,
				'loaders' => [
					'primary' => [
						CCrmOwnerType::CompanyName => [
							'action' => 'GET_CLIENT_INFO',
							'url' => '/bitrix/components/bitrix/crm.company.show/ajax.php?'.bitrix_sessid_get(),
						],
						CCrmOwnerType::ContactName => [
							'action' => 'GET_CLIENT_INFO',
							'url' => '/bitrix/components/bitrix/crm.contact.show/ajax.php?'.bitrix_sessid_get(),
						],
					],
				],
				'clientEditorFieldsParams' =>
					CCrmComponentHelper::prepareClientEditorFieldsParams(
						['categoryParams' => $categoryParams]
					)
				,
				'useExternalRequisiteBinding' => true,
				'enableRequisiteSelection' => true,
				'enableTooltip' => $enableTooltip,
				'duplicateControl' => CCrmComponentHelper::prepareClientEditorDuplicateControlParams(
					['entityTypes' => [CCrmOwnerType::Company, CCrmOwnerType::Contact]]
				),
			],
		];
	}

	/**
	 * Return all parent fields description for $childEntityTypeId.
	 *
	 * @param int $childEntityTypeId
	 * @param string|null $context
	 * @return array
	 */
	public function getParentFieldsInfo(int $childEntityTypeId, ?string $context = EntitySelector::CONTEXT): array
	{
		$relationManager = Container::getInstance()->getRelationManager();
		$customParentRelations = $relationManager->getParentRelations($childEntityTypeId)->filterOutPredefinedRelations();

		$parentFields = [];

		foreach($customParentRelations as $customParentRelation)
		{
			$parentEntityTypeId = $customParentRelation->getParentEntityTypeId();
			if (!CCrmOwnerType::IsDefined($parentEntityTypeId))
			{
				continue;
			}

			$entityDescription = CCrmOwnerType::GetDescription($parentEntityTypeId);

			$parentFields[] = static::getParentField(
				$entityDescription,
				$parentEntityTypeId,
				$context
			);
		}

		return $parentFields;
	}

	/**
	 * Return parent field description.
	 *
	 * @param string $title
	 * @param int $parentEntityTypeId
	 * @param string|null $context
	 * @return array
	 */
	public static function getParentField(
		string $title,
		int $parentEntityTypeId,
		?string $context = null
	): array
	{
		$fieldName = self::getParentFieldName($parentEntityTypeId);
		$entityTypeName = CCrmOwnerType::ResolveName($parentEntityTypeId);

		return [
			'name' => $fieldName,
			'title' => $title,
			'type' => 'crm_entity_tag',
			'editable' => true,
			'data' => [
				'typeId' => $parentEntityTypeId,
				'entityTypeName' => $entityTypeName,
				'info' => $fieldName . '_INFO',
				'context' => $context,
				'parentEntityTypeId' => $parentEntityTypeId,
			],
			'enableAttributes' => true,
		];
	}

	/**
	 * Return entityField for product_row_summary field.
	 *
	 * @param string $title
	 * @param string|null $fieldName
	 * @return array
	 */
	public static function getProductRowSummaryField(string $title, ?string $fieldName = null): array
	{
		$showProductLink = true;
		if (Loader::includeModule('catalog') && !AccessController::getCurrent()->check(ActionDictionary::ACTION_CATALOG_READ))
		{
			$showProductLink = false;
		}

		return [
			'name' => $fieldName ?? static::FIELD_PRODUCT_ROW_SUMMARY,
			'title' => $title,
			'type' => 'product_row_summary',
			'editable' => false,
			'enableAttributes' => false,
			'mergeable' => false,
			'transferable' => false,
			'showAlways' => true,
			'showProductLink' => $showProductLink,
		];
	}

	/**
	 * Return entityField for Opportunity field.
	 *
	 * @param string $title
	 * @param string|null $fieldName
	 * @param bool $isPaymentsEnabled
	 * @return array
	 */
	public static function getOpportunityField(
		string $title,
		?string $fieldName = null,
		bool $isPaymentsEnabled = false
	): array
	{
		$isSalescenterIncluded = Loader::includeModule('salescenter');
		$type = ($isPaymentsEnabled && $isSalescenterIncluded) ? 'moneyPay' : 'money';
		return [
			'name' => $fieldName ?? static::FIELD_OPPORTUNITY,
			'title' => $title,
			'type' => $type,
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
				'isShowPaymentDocuments' => $isPaymentsEnabled,
				'isWithOrdersMode' => \CCrmSaleHelper::isWithOrdersMode(),
				'isSalescenterToolEnabled' =>
					$isSalescenterIncluded
					&& \Bitrix\Salescenter\Restriction\ToolAvailabilityManager::getInstance()->checkSalescenterAvailability()
				,
			],
		];
	}

	/**
	 * Return entityField for UTM field.
	 *
	 * @param string $title
	 * @param string|null $fieldName
	 * @return array
	 */
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

	/**
	 * Return entityField for Location field.
	 *
	 * @param Field $field
	 * @return array
	 */
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

	/**
	 * Returns products summary data from <b>item</b> to display it in product row summary block
	 *
	 * @param Item $item
	 * @param int $mode
	 * @return array
	 */
	public function getProductRowSummaryDataByItem(Item $item, int $mode = ComponentMode::VIEW): array
	{
		if ($item->hasField(Item::FIELD_NAME_OPPORTUNITY))
		{
			return $this->getProductsSummaryEntityData($item, $mode);
		}
		return [];
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
		$currencyList = [];

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
		}

		return $currencyList;
	}

	protected static function getOrderList(Item $item): array
	{
		return Order\EntityBinding::getList([
			'select' => ['ORDER_ID'],
			'filter' => [
				'=OWNER_ID' => $item->getId(),
				'=OWNER_TYPE_ID' => $item->getEntityTypeId(),
			],
		])->fetchAll();
	}

	/**
	 * Return description for product_row_proxy editor controller.
	 *
	 * @param string $productEditorId
	 * @param string|null $fieldName
	 * @return array
	 */
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

	/**
	 * Return description for product_list editor controller.
	 *
	 * @param string $productListId
	 * @param string $currencyId
	 * @param string|null $fieldName
	 * @return array
	 */
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
		$emptyValuesCount = 0;
		foreach ($itemFieldNames as $itemFieldName)
		{
			$field = $this->fieldsCollection->getField($itemFieldName);
			if ($field && $field->isItemValueEmpty($item))
			{
				$emptyValuesCount++;
			}
		}

		return $emptyValuesCount === count($itemFieldNames);
	}

	/**
	 * Generates entityField of the editor for $userFields with $visibilityConfig on item with $entityTypeId and $entityId.
	 *
	 * @param array $userFields
	 * @param array $visibilityConfig
	 * @param int $entityTypeId
	 * @param int $entityId
	 * @param string $fileHandlerUrl
	 * @return array
	 */
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
		$userFieldEntityID = CCrmOwnerType::ResolveUserFieldEntityID($entityTypeId);
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
		elseif (is_numeric($value))
		{
			$value = (string)$value;
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

	protected function normalizeUserFieldValue($fieldValue, bool $isValueEmpty, array $fieldParams): array
	{
		if (!$isValueEmpty)
		{
			if (is_array($fieldValue))
			{
				$fieldValue = array_values($fieldValue);
			}
			elseif ($fieldParams['USER_TYPE_ID'] === BooleanType::USER_TYPE_ID)
			{
				$fieldValue = $fieldValue ? '1' : '0';
			}

			$fieldParams['VALUE'] = $fieldValue;
		}

		$fieldSignature = Dispatcher::instance()->getSignature($fieldParams);
		if ($isValueEmpty)
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
				$fieldName = $fieldData['name'] ?? '';
				if (
					$fieldName === Item::FIELD_NAME_ASSIGNED
					&& empty($entityData[$fieldName])
					&& (
						!isset($entityData[Item::FIELD_NAME_ID])
						|| (int)$entityData[Item::FIELD_NAME_ID] <= 0
					)
				)
				{
					$entityData[$fieldName] = Container::getInstance()->getContext()->getUserId();
				}

				$userId = (int)($entityData[$fieldName] ?? 0);
				$userIdToFieldNamesMap[$userId][] = $fieldName;
			}
			elseif ($fieldData['type'] === 'multiple_user')
			{
				$infosKey = $fieldData['data']['infos'];
				$userIdsKey = $fieldData['data']['map']['data'];

				$userIds = $entityData[$userIdsKey] ?? [];
				if (empty($userIds) && $fieldData['name'] === Item::FIELD_NAME_ASSIGNED)
				{
					$userIds = [Container::getInstance()->getContext()->getUserId()];
				}

				// 'fieldname_info' => [1, 2, 3, 4]
				$multipleFields[$infosKey] = $userIds;
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
	 * @param Item $item
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
			$infoKey = $field['data']['info'] ?? ($fieldName . '_INFO');
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
		$child = ItemIdentifier::createByItem($item);
		$parentElements = $relationManager->getParentElements($child);

		if (!count($parentElements))
		{
			return $entityData;
		}

		$this->addParentFieldsEntityData($parentElements, $entityFields, $entityData);

		return $entityData;
	}

	/**
	 * Adds data about parent fields from $entityFields into $entityData, where $identifiers is array of parent identifiers.
	 *
	 * @param ItemIdentifier[] $identifiers
	 * @param array $entityFields
	 * @param array $entityData
	 */
	public function addParentFieldsEntityData(
		array $identifiers,
		array $entityFields,
		array &$entityData
	): void
	{
		$values = [];
		foreach ($identifiers as $identifier)
		{
			$values[$identifier->getEntityTypeId()] = $identifier->getEntityId();
		}

		foreach($entityFields as $field)
		{
			if (
				(
					$field['type'] !== 'crm_entity_tag'
					&& $field['type'] !== 'crm_entity'
				)
				|| !isset($field['data']['typeId'])
			)
			{
				continue;
			}

			$value = $values[$field['data']['typeId']] ?? null;
			$entityData = $this->addEntityDataForParentEntityField($field, $entityData, $value);
		}
	}

	protected function addEntityDataForParentEntityField(
		array $field,
		array $entityData,
		?int $value
	): array
	{
		$key = ($field['name'] ?? null);
		if (!$key)
		{
			return $entityData;
		}
		$entityData[$key] = $value;

		$fieldName = $field['name'];
		$infoKey = $field['data']['info'] ?? ($fieldName . '_INFO');
		if($value > 0)
		{
			$entityData[$infoKey] = $this->prepareCrmEntityData($field, $value);
		}

		return $entityData;
	}

	public function prepareCrmEntityData(array $field, int $entityId, array $entityRequisiteData = null): ?array
	{
		$entityTypeId = $field['data']['typeId'] ?? null;
		if (!CCrmOwnerType::IsEntity($entityTypeId))
		{
			return null;
		}

		$entityTypeName = $field['data']['entityTypeName'] ?? CCrmOwnerType::ResolveName($entityTypeId);

		if ($entityTypeId === \CCrmOwnerType::Company && \CCrmCompany::isMyCompany($entityId))
		{
			$canRead = Container::getInstance()->getUserPermissions()->getMyCompanyPermissions()->canReadBaseFields($entityId);
		}
		else
		{
			$canRead = EntityAuthorization::checkReadPermission($entityTypeId, $entityId);
		}

		$requireEditRequisiteData = (isset($field['data']['enableMyCompanyOnly']) && $field['data']['enableMyCompanyOnly'] === true);
		if ($requireEditRequisiteData && $entityRequisiteData === null)
		{
			$entityRequisiteData = $this->getMyCompanyRequisitesEntityData();
		}

		$entityInfoParams = [
			'ENTITY_EDITOR_FORMAT' => true,
			'IS_HIDDEN' => !$canRead,
			'REQUIRE_REQUISITE_DATA' => true,
			'REQUIRE_MULTIFIELDS' => true,
			'NAME_TEMPLATE' => PersonNameFormatter::getFormat(),
			'NORMALIZE_MULTIFIELDS' => true,
			'REQUIRE_EDIT_REQUISITE_DATA' => $requireEditRequisiteData,
		];
		if (
			$field['data']['ownerEntityTypeId'] ?? null
			&& $field['data']['ownerEntityId'] ?? null
		)
		{
			$entityInfoParams['ownerEntityTypeId'] = (int)$field['data']['ownerEntityTypeId'];
			$entityInfoParams['ownerEntityId'] = (int)$field['data']['ownerEntityId'];
		}

		$data = \CCrmEntitySelectorHelper::PrepareEntityInfo(
			$entityTypeName,
			$entityId,
			$entityInfoParams
		);

		//in data selected always default requisites, we have to actualize it manually
		if (
			$requireEditRequisiteData
			&& isset($data['advancedInfo']['requisiteData'])
			&& is_array($data['advancedInfo']['requisiteData'])
		)
		{
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
							$bankDetailData['selected'] = ((int)$bankDetailData['pseudoId'] === (int)$entityRequisiteData[static::FIELD_MY_COMPANY_BANK_DETAIL_ID]);
						}
						unset($bankDetailData);
					}
					$requisiteData['requisiteData'] = Json::encode($parsedRequisiteData);
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

	protected function getProductsSummaryEntityData(Item $item, int $mode = ComponentMode::VIEW): array
	{
		$entityTypeId = $item->getEntityTypeId();
		$entityId = $item->getId();

		$isReadOnly = true;
		if (
			(
				$mode === ComponentMode::MODIFICATION
				&& EntityAuthorization::checkUpdatePermission($entityTypeId, $entityId)
			)
			|| EntityAuthorization::checkCreatePermission($entityTypeId)
		)
		{
			$isReadOnly = false;
		}

		$products = $item->getProductRows();
		if (is_null($products))
		{
			return ['isReadOnly' => $isReadOnly];
		}

		$rowData = [];
		$numberOfProcessedProducts = 1;
		/** @var \Bitrix\Crm\ProductRow $product */
		foreach ($products as $product)
		{
			$rowData[] = self::formProductRowData($product, $item->getCurrencyId());

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
			'totalRaw' => [
				'amount' => $total,
				'currency' => $item->getCurrencyId(),
			],
			'items' => $rowData,
			'isReadOnly' => $isReadOnly,
		];
	}

	/**
	 * Returns formed product info from <b>$product</b> for display it in product summary list block
	 *
	 * @param \Bitrix\Crm\ProductRow $product
	 * @param string $currencyId
	 * @param bool $checkTaxes
	 * @return array
	 */
	public static function formProductRowData(\Bitrix\Crm\ProductRow $product, string $currencyId, bool $checkTaxes = false): array
	{
		$url = '';
		if ($product->getProductId() > 0)
		{
			$url = Container::getInstance()->getRouter()->getProductDetailUrl($product->getProductId());
		}

		$sum = 0;
		if ($checkTaxes && $product->getField('TAX_INCLUDED') !== 'Y')
		{
			$sum = round($product->getField('PRICE_EXCLUSIVE') * $product->getField('QUANTITY'), 2) * (1 + $product->getField('TAX_RATE') / 100);
		}
		else
		{
			$sum = $product->getPrice() * $product->getQuantity();
		}

		$productRowData = [
			'PRODUCT_NAME' => $product->getProductName(),
			'SUM' => Money::format($sum, $currencyId),
			'URL' => $url,
		];

		if (Loader::includeModule('catalog'))
		{
			$productData = $product->toArray();
			$sku =
				\Bitrix\Catalog\v2\IoC\ServiceContainer::getRepositoryFacade()
					->loadVariation($productData['PRODUCT_ID'])
			;

			if ($sku)
			{
				$image = $sku->getFrontImageCollection()->getFrontImage();
				$productRowData['PHOTO_URL'] = $image ? $image->getSource() : null;
				$productRowData['VARIATION_INFO'] =
					\Bitrix\Catalog\v2\Helpers\PropertyValue::getSkuPropertyDisplayValues($sku)
				;
			}
		}

		return $productRowData;
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

		$opportunityEntityData['FORMATTED_' . static::FIELD_ACCOUNT_OPPORTUNITY] = Money::format(
			$item->getOpportunityAccount(), $item->getAccountCurrencyId()
		);

		$opportunityEntityData['IS_SALESCENTER_TOOL_ENABLED'] =
			Loader::includeModule('salescenter')
			&& \Bitrix\Salescenter\Restriction\ToolAvailabilityManager::getInstance()->checkSalescenterAvailability()
		;

		return $opportunityEntityData;
	}

	/**
	 * Return processed before entityData for Client field.
	 * If processByItem has not been invoked - throws InvalidOperationException.
	 *
	 * @return array
	 * @throws InvalidOperationException
	 */
	public function getClientEntityData(): array
	{
		if (!$this->hasData())
		{
			throw new InvalidOperationException('call EditorAdapter::processByItem() first');
		}
		return $this->clientEntityData;
	}

	protected function prepareClientEntityData(Item $item, string $componentName): array
	{
		$clientEntityData = [];
		if ($item->getCompanyId() > 0)
		{
			$clientEntityData[static::FIELD_CLIENT . '_INFO']['COMPANY_DATA'][] = $this->generateClientInfo(
				CCrmOwnerType::Company,
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
					CCrmOwnerType::Contact,
					$contact->getId(),
					$isFirstContact
				);
				$isFirstContact = false;
			}
		}

		if ($this->isSearchHistoryEnabled)
		{
			$lastClientInfoMap = [
				static::LAST_COMPANY_INFOS => CCrmOwnerType::Company,
				static::LAST_CONTACT_INFOS => CCrmOwnerType::Contact,
			];
			foreach ($lastClientInfoMap as $arrayKey => $entityTypeId)
			{
				$clientEntityData[$arrayKey] = $this->getRecentlyUsedItems($entityTypeId, $componentName, $item->getEntityTypeId());
			}
		}

		return $clientEntityData;
	}

	protected function generateClientInfo(
		int $clientEntityTypeId,
		int $clientEntityId,
		bool $isEditRequisiteDataRequired = true
	): array
	{
		return $this->getDataForClientField(
			$clientEntityTypeId,
			$clientEntityId,
			$isEditRequisiteDataRequired
		);
	}

	/**
	 * Return entityField for Client field.
	 *
	 * @param int $clientEntityTypeId
	 * @param int $clientEntityId
	 * @param bool $isEditRequisiteDataRequired
	 * @return array
	 */
	public function getDataForClientField(
		int $clientEntityTypeId,
		int $clientEntityId,
		bool $isEditRequisiteDataRequired = true
	): array
	{
		$canReadClient = EntityAuthorization::checkReadPermission($clientEntityTypeId, $clientEntityId);

		$clientInfo = \CCrmEntitySelectorHelper::PrepareEntityInfo(
			CCrmOwnerType::ResolveName($clientEntityTypeId),
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

	protected function getRecentlyUsedItems(int $entityTypeId, string $componentName, ?int $parentEntityTypeId): array
	{
		// TODO: need to detect category ID when will implement real category params feature
		$categoryParams = CCrmComponentHelper::getEntityClientFieldCategoryParams($entityTypeId, 0, $parentEntityTypeId);

		return SearchAction::prepareSearchResultsJson(
			Entity::getRecentlyUsedItems(
				$componentName,
				mb_strtolower(CCrmOwnerType::ResolveName($entityTypeId)),
				[
					'EXPAND_ENTITY_TYPE_ID' => $entityTypeId,
					'EXPAND_CATEGORY_ID' => $categoryParams[$entityTypeId]['categoryId'],
				]
			),
			$parentEntityTypeId ? $this->getRecentlyUsedItemsSearchOptions($parentEntityTypeId, $entityTypeId) : []
		);
	}

	protected function getRecentlyUsedItemsSearchOptions(int $parentEntityTypeId, int $entityTypeId): array
	{
		$clientCategoryParams = \CCrmComponentHelper::getEntityClientFieldCategoryParams($parentEntityTypeId);

		return $clientCategoryParams[$entityTypeId] ?? [];
	}

	protected function getApplication(): \CMain
	{
		global $APPLICATION;
		return $APPLICATION;
	}

	/**
	 * Return entityData for UTM field.
	 *
	 * @param Item $item
	 * @return string
	 */
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

	/**
	 * Return html for location field.
	 *
	 * @param Item $item
	 * @param string $fieldName
	 * @return string|null
	 */
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

	protected function getMyCompanyRequisitesEntityData(): array
	{
		return $this->myCompanyRequisites ?? [];
	}

	protected function loadMyCompanyRequisitesEntityData(int $entityTypeId, int $id): array
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
			static::FIELD_MY_COMPANY_REQUISITE_ID => (int)($currentData[static::FIELD_MY_COMPANY_REQUISITE_ID] ?? null),
			static::FIELD_MY_COMPANY_BANK_DETAIL_ID => (int)($currentData[static::FIELD_MY_COMPANY_BANK_DETAIL_ID] ?? null),
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
		$result = $this->getClientDataFromEmbeddedEditor($clientJson);
		if ($result->isSuccess())
		{
			$data = $result->getData();
			if (isset($data[Item::FIELD_NAME_COMPANY_ID]))
			{
				$item->setCompanyId($data[Item::FIELD_NAME_COMPANY_ID]);
			}
			if (isset($data[Item::FIELD_NAME_CONTACTS]))
			{
				$item->setContactIds($data[Item::FIELD_NAME_CONTACTS]);
			}
		}

		return $result;
	}

	/**
	 * Parses $json with data from Client field from editor, saves data about company and contacts from it.
	 * Return information about new field values, processed entities and requisiteBindings.
	 *
	 * @param string $json
	 * @return Result
	 */
	public function getClientDataFromEmbeddedEditor(string $json): Result
	{
		$processedEntities = [];

		$resultData = [];
		/** @var array $clientData */
		try
		{
			$clientData = \Bitrix\Main\Web\Json::decode($json);
		}
		catch (\Bitrix\Main\ArgumentException $e)
		{
			$clientData = [];
		}
		if (!is_array($clientData))
		{
			$clientData = [];
		}

		$requisiteBinding = null;
		$companyData = $clientData['COMPANY_DATA'][0] ?? [];
		$resultData[Item::FIELD_NAME_COMPANY_ID] = 0;
		if ($companyData)
		{
			$entityResult = $this->saveClientEntity(CCrmOwnerType::Company, $companyData);
			$entityResultData = $entityResult->getData();
			$companyId = (int)($entityResultData['id'] ?? 0);
			if ($companyId > 0)
			{
				$resultData[Item::FIELD_NAME_COMPANY_ID] = $companyId;
				$processedEntities[] = new ItemIdentifier(CCrmOwnerType::Company, $companyId);

				$requisiteBinding = $this->extractRequisiteBinding(
					$companyData,
					$entityResultData,
					[
						static::FIELD_REQUISITE_ID,
						static::FIELD_BANK_DETAIL_ID,
					]
				);
			}
		}

		$contactIds = [];
		$contactData = array_values((array)($clientData['CONTACT_DATA'] ?? []));
		if (!empty($contactData))
		{
			foreach ($contactData as $contactIndex => &$contact)
			{
				$entityResult = $this->saveClientEntity(CCrmOwnerType::Contact, $contact);
				$entityResultData = $entityResult->getData();
				$contactId = (int)($entityResultData['id'] ?? 0);
				if ($contactId > 0)
				{
					if ($contactIndex === 0 && $requisiteBinding === null)
					{
						$requisiteBinding = $this->extractRequisiteBinding(
							$contact,
							$entityResultData,
							[
								static::FIELD_REQUISITE_ID,
								static::FIELD_BANK_DETAIL_ID,
							]
						);
					}

					$contact['id'] = $contactId;
					$processedEntities[] = new ItemIdentifier(CCrmOwnerType::Contact, $contact['id']);
					$contactIds[] = $contactId;
				}
			}
			unset($contact);
		}

		$resultData[Item::FIELD_NAME_CONTACTS] = $contactIds;
		$resultData['processedEntities'] = $processedEntities;
		$resultData['requisiteBinding'] = $requisiteBinding;

		return (new Result())->setData($resultData);
	}

	protected function saveClientEntity(int $entityTypeId, array $data, bool $checkPermissions = true): Result
	{
		$result = new Result();

		$id = isset($data['id']) ? (int)$data['id'] : 0;
		if ($id <= 0)
		{
			return BaseComponent::createClient($entityTypeId,
				$data,
				[
					'startWorkFlows' => true,
					'checkPermissions' => $checkPermissions,
				]
			);
		}
		if (!empty($data['title'])
			|| (isset($data['multifields']) && is_array($data['multifields']))
			|| (isset($data['requisites']) && is_array($data['requisites'])))
		{
			$result = BaseComponent::updateClient(
				new ItemIdentifier(
					$entityTypeId,
					$id
				),
				$data,
				[
					'startWorkFlows' => true,
					'checkPermissions' => $checkPermissions,
				]
			);
		}

		return $result->setData(array_merge($result->getData(), ['id' => $id]));
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
		try
		{
			$productRows = \Bitrix\Main\Web\Json::decode($productsJson);
		}
		catch (\Bitrix\Main\ArgumentException $e)
		{
			$productRows = [];
		}
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
		return ParentFieldManager::getParentFieldName($parentEntityTypeId);
	}

	/**
	 * Find data about parent item in request.
	 * If found - add data about it to context.
	 *
	 * @param array $context
	 * @param Request|null $request
	 * @return bool
	 */
	public static function addParentItemToContextIfFound(array &$context, ?Request $request = null): bool
	{
		$parentItem = ParentFieldManager::tryParseParentItemFromRequest($request);
		if ($parentItem)
		{
			$context[static::CONTEXT_PARENT_TYPE_ID] = $parentItem->getEntityTypeId();
			$context[static::CONTEXT_PARENT_ID] = $parentItem->getEntityId();

			return true;
		}

		return false;
	}

	/**
	 * Try to find data about parent item in data.
	 * If found and appropriate field is not passed - then it fills from context data.
	 *
	 * @param array $data
	 * @return bool
	 */
	public static function fillParentFieldFromContextEnrichedData(array &$data): bool
	{
		if (!isset(
			$data[static::CONTEXT_PARENT_TYPE_ID],
			$data[static::CONTEXT_PARENT_ID],
		))
		{
			return false;
		}

		$parentEntityTypeId = (int)$data[static::CONTEXT_PARENT_TYPE_ID];
		$parentEntityId = (int)$data[static::CONTEXT_PARENT_ID];
		if ($parentEntityId > 0 && CCrmOwnerType::IsDefined($parentEntityTypeId))
		{
			$parentFieldName = ParentFieldManager::getParentFieldName($parentEntityTypeId);
			if (!array_key_exists($parentFieldName, $data))
			{
				$data[$parentFieldName] = $parentEntityId;
				return true;
			}
		}

		return false;
	}

	/**
	 * Return field description for MYCOMPANY_ID field with embedded editor.
	 *
	 * @param array $description
	 * @return array
	 */
	public static function getMyCompanyFieldWithEditor(array $description = []): array
	{
		$name = $description['name'] ?? Item::FIELD_NAME_MYCOMPANY_ID;
		$title = $description['title'] ?? Loc::getMessage('CRM_TYPE_ITEM_FIELD_MYCOMPANY_ID');
		$editable = !isset($description['editable']) || (bool)$description['editable'];
		$companyLegend = $description['companyLegend'] ?? null;
		$fieldDataName = $description['fieldDataName'] ?? static::FIELD_MY_COMPANY_DATA_NAME;
		$infoName = $description['infoName'] ?? static::FIELD_MY_COMPANY_DATA_INFO;
		$showAlways = !isset($description['showAlways']) || (bool)$description['showAlways'];
		$enableTooltip = !isset($description['enableTooltip']) || (bool)$description['enableTooltip'];

		$clientEditorFieldsParams = CCrmComponentHelper::prepareClientEditorFieldsParams(
			['entityTypes' => [CCrmOwnerType::Company]]
		);

		return [
			'name' => $name,
			'title' => $title,
			'type' => 'client_light',
			'editable' => $editable,
			'showAlways' => $showAlways,
			'data' => [
				'affectedFields' => [$infoName],
				'enableMyCompanyOnly' => true,
				'enableRequisiteSelection' => true,
				'enableCreation' => Container::getInstance()->getUserPermissions()->getMyCompanyPermissions()->canAdd(),
				'typeId' => CCrmOwnerType::Company,
				'compound' => [
					[
						'name' => $name,
						'type' => 'company',
						'entityTypeName' => CCrmOwnerType::CompanyName,
						'tagName' => CCrmOwnerType::CompanyName,
					],
				],
				'map' => ['data' => $fieldDataName],
				'info' => $infoName,
				'fixedLayoutType' => 'COMPANY',
				'enableCompanyMultiplicity' => false,
				'lastCompanyInfos' => static::LAST_MYCOMPANY_INFOS,
				'companyLegend' => $companyLegend,
				'loaders' => [
					'primary' => [
						CCrmOwnerType::CompanyName => [
							'action' => 'GET_CLIENT_INFO',
							'url' => '/bitrix/components/bitrix/crm.company.show/ajax.php?'.bitrix_sessid_get(),
						],
					],
				],
				'clientEditorFieldsParams' => $clientEditorFieldsParams,
				'useExternalRequisiteBinding' => true,
				'enableTooltip' => $enableTooltip,
			],
		];
	}

	protected function fillMyCompanyDataForEmbeddedEditorField(Item $item): void
	{
		$myCompanyField = $this->fieldsCollection->getField(Item::FIELD_NAME_MYCOMPANY_ID);
		if (
			!$myCompanyField
			|| !isset($myCompanyField->getSettings()['isEmbeddedEditorEnabled'])
			|| $myCompanyField->getSettings()['isEmbeddedEditorEnabled'] !== true
		)
		{
			return;
		}

		$editorField = null;
		foreach ($this->entityFields as $field)
		{
			if ($field['name'] === Item::FIELD_NAME_MYCOMPANY_ID)
			{
				$editorField = $field;
				break;
			}
		}
		if (!$editorField)
		{
			return;
		}

		$myCompanyId = $item->getMycompanyId();
		if ($myCompanyId > 0)
		{
			$this->entityData[static::FIELD_MY_COMPANY_DATA_INFO] = [
				'COMPANY_DATA' => [
					$this->prepareCrmEntityData(
						$editorField,
						$myCompanyId,
						$this->getMyCompanyRequisitesEntityData()
					),
				],
			];
		}

		$this->entityData[static::LAST_MYCOMPANY_INFOS] = $this->getLastMyCompanyInfos();
	}

	/**
	 * Return entityData for last myCompany elements.
	 *
	 * @return array
	 */
	public function getLastMyCompanyInfos(): array
	{
		$myCompanyItems = [];
		$collection = \Bitrix\Crm\CompanyTable::getList([
			'select' => ['ID'],
			'order' => [
				'TITLE' => 'ASC',
			],
			'filter' => [
				'=IS_MY_COMPANY' => 'Y',
			],
			'cache' => [
				'ttl' => 86400,
			],
		])->fetchCollection();
		foreach ($collection as $company)
		{
			$myCompanyItems[] = [
				'ENTITY_TYPE_ID' => CCrmOwnerType::Company,
				'ENTITY_ID' => $company->getId(),
			];
		}

		return SearchAction::prepareSearchResultsJson($myCompanyItems);
	}

	/**
	 * Saves data about my company from embedded editor.
	 *
	 * @param Item $item
	 * @param string $json
	 * @return Result
	 */
	public function saveMyCompanyDataFromEmbeddedEditor(Item $item, string $json): Result
	{
		$myCompanyPermissions = Container::getInstance()->getUserPermissions()->getMyCompanyPermissions();
		$canEditMyCompany = $item->isNew()
			? $myCompanyPermissions->canAddByOwnerEntity($item->getEntityTypeId())
			: $myCompanyPermissions->canUpdateByOwnerEntity($item->getEntityTypeId(), $item->getId())
		;

		$result = $this->getMyCompanyDataFromEmbeddedEditor($json, !$canEditMyCompany); // check permissions only if no permissions to edit my company
		if ($result->isSuccess())
		{
			$data = $result->getData();
			if (isset($data[Item::FIELD_NAME_MYCOMPANY_ID]))
			{
				$item->setMycompanyId($data[Item::FIELD_NAME_MYCOMPANY_ID]);
			}
		}

		return $result;
	}

	/**
	 * Parses $json with data from myCompany field with embedded editor, saves data about myCompany from it.
	 * Return information about new field value and myCompany requisite bindings.
	 *
	 * @param string $json
	 * @return Result
	 */
	public function getMyCompanyDataFromEmbeddedEditor(string $json, bool $checkPermissions = true): Result
	{
		$result = new Result();

		try
		{
			$data = \Bitrix\Main\Web\Json::decode($json);
		}
		catch (\Bitrix\Main\ArgumentException $e)
		{
			$data = [];
		}
		if (!is_array($data))
		{
			$data = [];
		}

		$resultData = [];
		$companyData = $data['COMPANY_DATA'][0] ?? [];
		if ($companyData)
		{
			$companyData['isMyCompany'] = true;
			$entityResult = $this->saveClientEntity(CCrmOwnerType::Company, $companyData, $checkPermissions);
			$entityData = $entityResult->getData();
			$companyId = (int)($entityData['id'] ?? 0);
			if ($companyId > 0)
			{
				$resultData[Item::FIELD_NAME_MYCOMPANY_ID] = $companyId;
				$requisiteBinding = $this->extractRequisiteBinding(
					$companyData,
					$resultData,
					[
						static::FIELD_MY_COMPANY_REQUISITE_ID,
						static::FIELD_MY_COMPANY_BANK_DETAIL_ID,
					]
				);

				if (count($requisiteBinding) > 0)
				{
					$resultData += $requisiteBinding;
				}
			}
		}
		else
		{
			// $companyData was not sent, but a company is bound with the item. It means the company was unbound from the item.
			$resultData[Item::FIELD_NAME_MYCOMPANY_ID] = 0;
		}

		return $result->setData($resultData);
	}

	/**
	 * Try to find requisites and bank details binding from $entityData, having $resultData with possible added requisites identifiers.
	 *
	 * @param array $entityData
	 * @param array $resultData
	 * @param array $fieldNames
	 * @return array
	 */
	public function extractRequisiteBinding(array $entityData, array $resultData, array $fieldNames): array
	{
		$requisiteBinding = [];

		if (
			isset($entityData['requisites']['BINDING'])
			&& is_array($entityData['requisites']['BINDING'])
		)
		{
			if (array_key_exists('requisiteId', $entityData['requisites']['BINDING']))
			{
				$requisiteId = (int)($resultData['addedRequisites'][$entityData['requisites']['BINDING']['requisiteId']]
					?? $entityData['requisites']['BINDING']['requisiteId']);

				if ($requisiteId > 0)
				{
					$requisiteBinding[$fieldNames[0]] = $requisiteId;
				}
			}
			if (array_key_exists('bankDetailId', $entityData['requisites']['BINDING']))
			{
				$bankDetailId = (int)($resultData['addedBankDetails'][$entityData['requisites']['BINDING']['bankDetailId']]
					?? $entityData['requisites']['BINDING']['bankDetailId']);

				if ($bankDetailId > 0)
				{
					$requisiteBinding[$fieldNames[1]] = $bankDetailId;
				}
			}
		}

		return $requisiteBinding;
	}

	public function getAdditionalField(string $fieldName): ?array
	{
		return $this->additionalFields[$fieldName] ?? null;
	}
}
