<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
{
	die();
}

use Bitrix\Crm\Agent\Requisite\CompanyAddressConvertAgent;
use Bitrix\Crm\Agent\Requisite\CompanyUfAddressConvertAgent;
use Bitrix\Crm\Agent\Requisite\ContactAddressConvertAgent;
use Bitrix\Crm\Agent\Requisite\ContactUfAddressConvertAgent;
use Bitrix\Crm\Attribute\FieldAttributeManager;
use Bitrix\Crm\Entity\EntityEditorConfigScope;
use Bitrix\Crm\FieldContext\ContextManager;
use Bitrix\Crm\FieldContext\Repository;
use Bitrix\Crm\Integration\UI\EntitySelector\CountryProvider;
use Bitrix\Crm\Restriction\RestrictionManager;
use Bitrix\Crm\Security\EntityAuthorization;
use Bitrix\Crm\Service\Container;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Spotlight;
use Bitrix\UI;
use Bitrix\Ui\EntityForm\Scope;
use Bitrix\UI\Form\EntityEditorConfiguration;

Loc::loadMessages(__FILE__);

if(!Bitrix\Main\Loader::includeModule('crm'))
{
	ShowError(Bitrix\Main\Localization\Loc::getMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}
if(!Bitrix\Main\Loader::includeModule('ui'))
{
	ShowError(Bitrix\Main\Localization\Loc::getMessage('UI_MODULE_NOT_INSTALLED'));
	return;
}

CBitrixComponent::includeComponentClass("bitrix:ui.form");

class CCrmEntityEditorComponent extends UIFormComponent
{
	/** @var int */
	protected $entityTypeID = 0;

	/** @var int|null */
	protected $categoryId;

	private static function isAdminForEntity(mixed $entityTypeID): bool
	{
		if (is_numeric($entityTypeID))
		{
			return Container::getInstance()->getUserPermissions()->isAdminForEntity((int)$entityTypeID);
		}
		return false;
	}

	protected function emitOnUIFormInitializeEvent(): void
	{
		$event = new Main\Event('crm', 'onCrmEntityEditorInitialize', ['TEMPLATE' => $this->getTemplateName()]);
		$event->send();
	}

	protected function getDefaultParameters(): array
	{
		return array_merge(
			parent::getDefaultParameters(),
			[
				'GUID' =>'entity_editor',
				'ENABLE_PAGE_TITLE_CONTROLS' => true,
				'ENABLE_COMMUNICATION_CONTROLS' => true,
				'ENABLE_REQUIRED_FIELDS_INJECTION' => true,
				'ENABLE_AVAILABLE_FIELDS_INJECTION' => false,
				'ENABLE_EXTERNAL_LAYOUT_RESOLVERS' => false,
				'SHOW_EMPTY_FIELDS' => false,
				'ENTITY_TYPE_ID' => CCrmOwnerType::Undefined,
				'DUPLICATE_CONTROL' => [],
				'USER_FIELD_PREFIX' => 'CRM',
				'ENTITY_TYPE_TITLE' => '',
				'ADDITIONAL_FIELDS_DATA' => [],
				'CUSTOM_TOOL_PANEL_BUTTONS' => [],
				'TOOL_PANEL_BUTTONS_ORDER' => [
					'VIEW' => [],
					'EDIT' => [
						UI\EntityEditor\Action::DEFAULT_ACTION_BUTTON_ID,
						UI\EntityEditor\Action::CANCEL_ACTION_BUTTON_ID,
					],
				],
			]
		);
	}

	protected function getConfigurationCategoryName(): string
	{
		return \Bitrix\Crm\Entity\EntityEditorConfig::CATEGORY_NAME;
	}

	protected function getConfigurationOptionCategoryName(): string
	{
		return $this->getConfigurationCategoryName();
	}

	protected function getFieldsInfo(array $entityFields, array $entityData): array
	{
		$availableFields = [];
		$requiredFields = array();
		$hasEmptyRequiredFields = false;
		$hasBBCodeFields = false;
		$htmlFieldNames = [];
		$bbFieldNames = [];
		$isUfAddressConverterEnabled = $this->isUfAddressConvertionEnabled();
		foreach($this->arResult['ENTITY_FIELDS'] as $index => $field)
		{
			$name = $field['name'] ?? '';
			if($name === '')
			{
				continue;
			}

			$typeName = $field['type'] ?? '';
			if($typeName === 'html')
			{
				$htmlFieldNames[] = $name;
			}
			if ($typeName === 'bb')
			{
				$bbFieldNames[] = $name;
			}

			if ($typeName === 'bbcode')
			{
				$hasBBCodeFields = true;
			}

			if ($name === 'LINK' && $typeName === 'multifield')
			{
				continue;
			}

			if (
				$isUfAddressConverterEnabled
				&& $typeName === 'userField'
				&& is_array($field['data'])
				&& is_array($field['data']['fieldInfo'])
				&& isset($field['data']['fieldInfo']['USER_TYPE_ID'])
				&& $field['data']['fieldInfo']['USER_TYPE_ID'] === 'address'
				&& (!isset($field['data']['fieldInfo']['MULTIPLE'])
					|| $field['data']['fieldInfo']['MULTIPLE'] === 'N'))
			{
				if (!is_array($field['data']['options'] ?? null))
				{
					$this->arResult['ENTITY_FIELDS'][$index]['data']['options'] = [];
				}
				$this->arResult['ENTITY_FIELDS'][$index]['data']['options']['canActivateUfAddressConverter'] =
				$field['data']['options']['canActivateUfAddressConverter'] = 'Y';
			}

			$availableFields[$name] = $field;
			if(
				(isset($field['required']) && $field['required'] === true)
				||
				(
					isset($field['data'])
					&& is_array($field['data'])
					&& isset($field['data']['isRequiredByAttribute'])
					&& $field['data']['isRequiredByAttribute']
				)
			)
			{
				$requiredFields[$name] = $field;

				if($hasEmptyRequiredFields)
				{
					continue;
				}

				//HACK: Skip if user field of type Boolean. Absence of value is treated as equivalent to FALSE.
				$fieldType = $field['type'] ?? '';
				if ($fieldType === 'userField')
				{
					$fieldInfo = $field['data']['fieldInfo'] ?? [];

					if(isset($fieldInfo['USER_TYPE_ID']) && $fieldInfo['USER_TYPE_ID'] === 'boolean')
					{
						continue;
					}
				}
				else if (isset($this->arResult['ENTITY_DATA']['EMPTY_REQUIRED_SYSTEM_FIELD_MAP'][$name]))
				{
					$hasEmptyRequiredFields = true;
					continue;
				}

				if (
					isset($this->arResult['ENTITY_DATA'][$name])
					&& is_array($this->arResult['ENTITY_DATA'][$name])
					&& isset($this->arResult['ENTITY_DATA'][$name]['IS_EMPTY'])
					&& $this->arResult['ENTITY_DATA'][$name]['IS_EMPTY']
				)
				{
					$hasEmptyRequiredFields = true;
				}
			}
		}

		return [
			'available' => $availableFields,
			'required' => $requiredFields,
			'hasEmptyRequiredFields' => $hasEmptyRequiredFields,
			'hasBBCodeFields' => $hasBBCodeFields,
			'html' => $htmlFieldNames,
			'bb' => $bbFieldNames,
		];
	}

	protected function processScheme(
		array $config,
		array $defaultConfig,
		string $configScope,
		array &$fieldsInfo
	): array
	{
		$availableFields = $fieldsInfo['available'];
		$requiredFields = $fieldsInfo['required'];

		$primaryColumnIndex = 0;
		$primarySectionIndex = 0;
		$serviceColumnIndex = -1;
		$serviceSectionIndex = -1;
		$additionalColumnIndex = -1;
		$additionalSectionIndex = -1;
		$scheme = [];
		foreach ($config as $j => $column)
		{
			$columnScheme = [];

			foreach ($column['elements'] as $i => $configItem)
			{
				$type = $configItem['type'] ?? '';

				if ($type !== self::SECTION_TYPE && $type !== self::INCLUDED_AREA_TYPE)
				{
					continue;
				}

				$sectionName = $configItem['name'] ?? '';
				if ($sectionName === static::SECTION_MAIN)
				{
					$primaryColumnIndex = $j;
					$primarySectionIndex = $i;
				}
				elseif ($sectionName === static::SECTION_REQUIRED)
				{
					$serviceColumnIndex = $j;
					$serviceSectionIndex = $i;
				}
				elseif ($sectionName === static::SECTION_ADDITIONAL)
				{
					$additionalColumnIndex = $j;
					$additionalSectionIndex = $i;
				}

				if (
					isset($defaultConfig[$sectionName])
					&& is_array($defaultConfig[$sectionName])
					&& !empty($defaultConfig[$sectionName]['data'])
				)
				{
					$configItem['data'] = $defaultConfig[$sectionName]['data'];
				}

				$elements = isset($configItem['elements']) && is_array($configItem['elements'])
					? $configItem['elements'] : array();

				$schemeElements = array();
				foreach($elements as $configElement)
				{
					$name = $configElement['name'] ?? '';
					if ($name === '') {
						continue;
					}

					$schemeElement = $availableFields[$name] ?? [];
					$fieldType = $schemeElement['type'] ?? '';

					$title = '';
					if (!($configScope === EntityEditorConfigScope::COMMON && $fieldType === 'userField')) {
						$title = $configElement['title'] ?? null;
					}

					if ($title !== '') {
						if (isset($schemeElement['title'])) {
							$schemeElement['originalTitle'] = $schemeElement['title'];
						}
						$schemeElement['title'] = $title;
					}

					$optionFlags = isset($configElement['optionFlags']) ? (int)$configElement['optionFlags'] : 0;
					if ($optionFlags > 0) {
						$schemeElement['optionFlags'] = $optionFlags;
					}

					$schemeElement['options'] = (isset($configElement['options']) && is_array($configElement['options']))
						? $configElement['options']
						: [];

					// set default country
					if (
						empty($schemeElement['options'])
						&& in_array($name, ['PHONE', 'CLIENT', 'COMPANY', 'CONTACT', 'MYCOMPANY_ID'])
					)
					{
						$schemeElement['options'] = [
							'defaultCountry' => CountryProvider::getDefaultCountry()
						];
					}

					if ($name === 'OPPORTUNITY_WITH_CURRENCY')
					{
						$schemeElement = self::setOpportunityWithCurrencyDefaultOptions($schemeElement);
					}

					$schemeElements[] = $schemeElement;
					unset($availableFields[$name]);
					unset($requiredFields[$name]);
				}

				$columnScheme[] = array_merge($configItem, ['elements' => $schemeElements]);
			}

			$scheme[] = array_merge($column, ['elements' => $columnScheme]);
		}

		$hasEmptyRequiredFields = $fieldsInfo['hasEmptyRequiredFields'];

		//Add section 'Required Fields'
		if(!$this->arResult['READ_ONLY'])
		{
			//Force Edit mode if empty required fields are found.
			if($hasEmptyRequiredFields)
			{
				$this->arResult['INITIAL_MODE'] = 'edit';
			}

			if(!empty($requiredFields) && $this->arResult['ENABLE_REQUIRED_FIELDS_INJECTION'])
			{
				$schemeElements = array();
				if($serviceSectionIndex >= 0)
				{
					$configItem = $config[$serviceColumnIndex]['elements'][$serviceSectionIndex];
					if(isset($scheme[$serviceColumnIndex]['elements'][$serviceSectionIndex]['elements'])
						&& is_array($scheme[$serviceColumnIndex]['elements'][$serviceSectionIndex]['elements'])
					)
					{
						$schemeElements = $scheme[$serviceColumnIndex]['elements'][$serviceSectionIndex]['elements'];
					}
				}
				else
				{
					$configItem = array(
						'name' => 'required',
						'title' => Loc::getMessage('CRM_ENTITY_ED_REQUIRED_FIELD_SECTION'),
						'type' => 'section',
						'elements' => array()
					);

					$serviceSectionIndex = $primarySectionIndex + 1;
					$serviceColumnIndex = $primaryColumnIndex;
					array_splice(
						$config[$serviceColumnIndex]['elements'],
						$serviceSectionIndex,
						0,
						array($configItem)
					);

					array_splice(
						$scheme[$serviceColumnIndex]['elements'],
						$serviceSectionIndex,
						0,
						array(array_merge($configItem, array('elements' => array())))
					);
				}

				foreach($requiredFields as $fieldName => $fieldInfo)
				{
					$configItem['elements'][] = array('name' => $fieldName);
					$schemeElements[] = $fieldInfo;
				}

				$scheme[$serviceColumnIndex]['elements'][$serviceSectionIndex]['elements'] = $schemeElements;
			}
		}

		if(!empty($availableFields) && $this->arResult['ENABLE_AVAILABLE_FIELDS_INJECTION'])
		{
			$schemeElements = array();
			if($additionalSectionIndex >= 0)
			{
				$configItem = $config[$additionalColumnIndex]['elements'][$additionalSectionIndex];
				if(isset($scheme[$additionalColumnIndex]['elements'][$additionalSectionIndex]['elements'])
					&& is_array($scheme[$additionalColumnIndex]['elements'][$additionalSectionIndex]['elements'])
				)
				{
					$schemeElements = $scheme[$additionalColumnIndex]['elements'][$additionalSectionIndex]['elements'];
				}
			}
			else
			{
				$configItem = array(
					'name' => 'additional',
					'title' => Loc::getMessage('CRM_ENTITY_ED_ADDITIONAL_FIELD_SECTION'),
					'type' => 'section',
					'elements' => array()
				);

				if ($serviceSectionIndex >= 0)
				{
					$additionalColumnIndex = $serviceColumnIndex;
					$additionalSectionIndex = $serviceSectionIndex;
				}
				else
				{
					$additionalColumnIndex = $primaryColumnIndex;
					$additionalSectionIndex = $primarySectionIndex;
				}
				array_splice(
					$config[$additionalColumnIndex]['elements'],
					$additionalSectionIndex,
					0,
					array($configItem)
				);

				array_splice(
					$scheme[$additionalColumnIndex]['elements'],
					$additionalSectionIndex,
					0,
					array(array_merge($configItem, array('elements' => array())))
				);
			}

			foreach($availableFields as $fieldName => $fieldInfo)
			{
				if($fieldName === 'ID')
				{
					continue;
				}

				$configItem['elements'][] = array('name' => $fieldName);
				$schemeElements[] = $fieldInfo;
				unset($availableFields[$fieldName]);
			}

			$scheme[$additionalColumnIndex]['elements'][$additionalSectionIndex]['elements'] = $schemeElements;
		}

		$fieldsInfo['available'] = $availableFields;

		return $scheme;
	}

	protected function initialize()
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		$this->userID = CCrmSecurityHelper::GetCurrentUserID();

		$this->arResult = $this->prepareParameters($this->arParams);

		$this->guid = $this->arResult['GUID'];
		$this->configID = $this->arResult['CONFIG_ID'] ?? $this->guid;

		$this->entityID = $this->arResult['ENTITY_ID'];

		if (!empty($this->arResult['ENTITY_TYPE_ID']))
		{
			$this->entityTypeID = $this->arResult['ENTITY_TYPE_ID'];
			$this->entityTypeName = CCrmOwnerType::ResolveName($this->entityTypeID);
			$this->arResult['ENTITY_TYPE_NAME'] = $this->entityTypeName;
		}
		elseif ($this->arResult['ENTITY_TYPE_NAME'])
		{
			$this->entityTypeName = $this->arResult['ENTITY_TYPE_NAME'];
			$this->entityTypeID = CCrmOwnerType::ResolveID($this->entityTypeName);
			$this->arResult['ENTITY_TYPE_ID'] = $this->entityTypeID;
		}

		$this->categoryId = isset($this->arParams['EXTRAS']['CATEGORY_ID'])
			? (int)$this->arParams['EXTRAS']['CATEGORY_ID']
			: 0;

		if (empty($this->arResult['ENTITY_TYPE_TITLE']))
		{
			$this->arResult['ENTITY_TYPE_TITLE'] = Main\Text\HtmlFilter::encode(
				CCrmOwnerType::GetDescription($this->entityTypeID)
			);
		}

		if (empty($this->arResult['ADDITIONAL_FIELDS_DATA']))
		{
			$this->arResult['ADDITIONAL_FIELDS_DATA'] = $this->getAdditionalFieldsData();
		}

		$this->prepareConfig();

		$this->arResult['ENABLE_SETTINGS_FOR_ALL'] = CCrmAuthorizationHelper::CanEditOtherSettings();

		//region CAN_UPDATE_PERSONAL_CONFIGURATION && CAN_UPDATE_COMMON_CONFIGURATION
		$this->arResult['CAN_UPDATE_PERSONAL_CONFIGURATION'] = true;
		$this->arResult['CAN_UPDATE_COMMON_CONFIGURATION'] = self::isAdminForEntity($this->entityTypeID);

		if(!isset($this->arParams['~ENABLE_CONFIGURATION_UPDATE']))
		{
			if(isset($this->arParams['~ENABLE_PERSONAL_CONFIGURATION_UPDATE']))
			{
				$this->arResult['CAN_UPDATE_PERSONAL_CONFIGURATION'] = $this->arParams['~ENABLE_PERSONAL_CONFIGURATION_UPDATE'];
			}

			if($this->arResult['CAN_UPDATE_COMMON_CONFIGURATION']
				&& isset($this->arParams['~ENABLE_COMMON_CONFIGURATION_UPDATE'])
				&& !$this->arParams['~ENABLE_COMMON_CONFIGURATION_UPDATE']
			)
			{
				$this->arResult['CAN_UPDATE_COMMON_CONFIGURATION'] = false;
			}
		}
		elseif(!$this->arParams['~ENABLE_CONFIGURATION_UPDATE'])
		{
			$this->arResult['CAN_UPDATE_PERSONAL_CONFIGURATION'] = false;
			if($this->arResult['CAN_UPDATE_COMMON_CONFIGURATION'])
			{
				$this->arResult['CAN_UPDATE_COMMON_CONFIGURATION'] = false;
			}
		}
		//endregion

		$this->arResult['ENTITY_CONFIG_SIGNED_PARAMS'] = method_exists($this, 'getSignedConfigParameters') ? $this->getSignedConfigParameters() : '';

		$this->initPull();

		$this->arResult['PATH_TO_ENTITY_DETAILS'] = CCrmOwnerType::GetDetailsUrl(
			$this->entityTypeID,
			$this->entityID,
			true,
			array()
		);
		$this->arResult['PATH_TO_CONTACT_CREATE'] = CComponentEngine::makePathFromTemplate(
			\CrmCheckPath(
				'PATH_TO_CONTACT_DETAILS',
				$this->arParams['~PATH_TO_CONTACT_DETAILS'] ?? '',
				$APPLICATION->GetCurPage().'?contact_id=#contact_id#&details'
			),
			array('contact_id' => 0)
		);
		$this->arResult['PATH_TO_CONTACT_EDIT'] = CComponentEngine::makePathFromTemplate(
			\CrmCheckPath(
				'PATH_TO_CONTACT_DETAILS',
				$this->arParams['~PATH_TO_CONTACT_DETAILS'] ?? '',
				$APPLICATION->GetCurPage().'?contact_id=#contact_id#&details'
			),
			array('contact_id' => '#id#')
		);
		$this->arResult['PATH_TO_CONTACT_REQUISITE_SELECT'] = \CrmCheckPath(
			'PATH_TO_CONTACT_REQUISITE_SELECT',
			$this->arParams['~PATH_TO_CONTACT_REQUISITE_SELECT'] ?? '',
			$APPLICATION->GetCurPage().'?contact_id=#contact_id#&requisiteselect'
		);
		$this->arResult['PATH_TO_COMPANY_CREATE'] = CComponentEngine::makePathFromTemplate(
			\CrmCheckPath(
				'PATH_TO_COMPANY_DETAILS',
				$this->arParams['~PATH_TO_COMPANY_DETAILS'] ?? '',
				$APPLICATION->GetCurPage().'?company_id=#company_id#&details'
			),
			array('company_id' => 0)
		);
		$this->arResult['PATH_TO_COMPANY_EDIT'] = CComponentEngine::makePathFromTemplate(
			\CrmCheckPath(
				'PATH_TO_COMPANY_DETAILS',
				$this->arParams['~PATH_TO_COMPANY_DETAILS'] ?? '',
				$APPLICATION->GetCurPage().'?company_id=#company_id#&details'
			),
			array('company_id' => "#id#")
		);
		$this->arResult['PATH_TO_COMPANY_REQUISITE_SELECT'] = \CrmCheckPath(
			'PATH_TO_COMPANY_REQUISITE_SELECT',
			$this->arParams['~PATH_TO_COMPANY_REQUISITE_SELECT'] ?? '',
			$APPLICATION->GetCurPage().'?company_id=#company_id#&requisiteselect'
		);

		$this->arResult['PATH_TO_REQUISITE_EDIT'] = '/bitrix/components/bitrix/crm.requisite.details/slider.ajax.php?requisite_id=#requisite_id#&'.bitrix_sessid_get();

		$this->arResult['USER_FIELD_FILE_URL_TEMPLATE'] = ($this->arParams['~USER_FIELD_FILE_URL_TEMPLATE'] ?? null);

		//region Permissions
		$userPermissions = CCrmPerms::GetCurrentUserPermissions();

		$clientCategoryParams = \CCrmComponentHelper::getEntityClientFieldCategoryParams((int)$this->entityTypeID, $this->categoryId);

		$this->arResult['CAN_CREATE_CONTACT'] = \CCrmContact::CheckCreatePermission($userPermissions, $clientCategoryParams[CCrmOwnerType::Contact]['categoryId'] ?? 0);
		$this->arResult['CAN_CREATE_COMPANY'] = \CCrmCompany::CheckCreatePermission($userPermissions, $clientCategoryParams[CCrmOwnerType::Company]['categoryId'] ?? 0);
		//endregion

		$this->arResult['LANGUAGES'] = $this->loadLanguages();

		//region Attribute configuration
		$this->arResult['ATTRIBUTE_CONFIG'] = null;
		if(self::isAdminForEntity($this->entityTypeID))
		{
			$arParamsAttributeConfig = $this->arParams['~ATTRIBUTE_CONFIG'] ?? null;
			$this->arResult['ATTRIBUTE_CONFIG'] = is_array($arParamsAttributeConfig) ?
				$this->arParams['~ATTRIBUTE_CONFIG'] : null;
			if(isset($this->arResult['ATTRIBUTE_CONFIG']))
			{
				$isPermitted = FieldAttributeManager::isEnabled();
				$isPhaseDependent = FieldAttributeManager::isPhaseDependent();
				$isEntitySupported = FieldAttributeManager::isEntitySupported((int)$this->entityTypeID);
				$this->arResult['ATTRIBUTE_CONFIG']['IS_PERMITTED'] = $isPermitted;
				$this->arResult['ATTRIBUTE_CONFIG']['IS_PHASE_DEPENDENT'] = $isPhaseDependent;
				$this->arResult['ATTRIBUTE_CONFIG']['IS_ATTR_CONFIG_BUTTON_HIDDEN'] = !$isEntitySupported;
				if(!($isPermitted && $isPhaseDependent))
				{
					$this->arResult['ATTRIBUTE_CONFIG']['LOCK_SCRIPT'] =
						RestrictionManager::getAttributeConfigRestriction()->prepareInfoHelperScript();
				}
				unset($isPermitted, $isPhaseDependent, $isEntitySupported);
			}
		}
		//endregion

		//Bizproc
		$this->arResult['BIZPROC_MANAGER_CONFIG'] = [];
		$bizprocEventType = $this->entityID === 0 ? CCrmBizProcEventType::Create : CCrmBizProcEventType::Edit;
		if (CCrmBizProcHelper::HasParameterizedAutoWorkflows($this->entityTypeID, $bizprocEventType))
		{
			$bizprocStarterData = [
				'hasParameters' => true,
				'moduleId' => 'crm',
				'entity' => CCrmBizProcHelper::ResolveDocumentName($this->entityTypeID),
				'documentType' => CCrmOwnerType::ResolveName($this->entityTypeID),
				'autoExecuteType' => $bizprocEventType,
				'fieldName' => 'bizproc_parameters',
			];

			if (class_exists(\Bitrix\Bizproc\Controller\Workflow\Starter::class))
			{
				$bizprocStarterData['signedDocumentType'] = CBPDocument::signDocumentType([
					$bizprocStarterData['moduleId'], $bizprocStarterData['entity'], $bizprocStarterData['documentType']
				]);

				if ($this->entityID > 0)
				{
					$bizprocStarterData['signedDocumentId'] = CBPDocument::signDocumentType(
						[
							$bizprocStarterData['moduleId'],
							$bizprocStarterData['entity'],
							CCrmBizProcHelper::ResolveDocumentId($this->entityTypeID, $this->entityID),
						],
					);
				}

				unset($bizprocStarterData['moduleId'], $bizprocStarterData['entity'], $bizprocStarterData['documentType']);
			}

			$this->arResult['BIZPROC_MANAGER_CONFIG'] = $bizprocStarterData;
		}
		//end Bizproc

		//Rest placement and userfield types
		$this->arResult['REST_USE'] = false;
		$this->arResult['REST_PLACEMENT_TAB_CONFIG'] = array();
		$this->arResult['USERFIELD_TYPE_ADDITIONAL'] = array();
		if(
			$this->arParams['ENABLE_USER_FIELD_CREATION'] !== false
			&& in_array(
				$this->entityTypeID,
				array(
					CCrmOwnerType::Company,
					CCrmOwnerType::Contact,
					CCrmOwnerType::Deal,
					CCrmOwnerType::Lead,
					CCrmOwnerType::Quote,
					CCrmOwnerType::Invoice,
				))
			&& Main\ModuleManager::isModuleInstalled('rest')
		)
		{
			$this->arResult['REST_USE'] = true;

			$this->arResult['USERFIELD_TYPE_ADDITIONAL'] = $this->getAdditionalUserFieldTypeList();
			$this->arResult['USERFIELD_TYPE_REST_CREATE_URL'] = \Bitrix\Rest\Marketplace\Url::getBookletUrl(
				'crm_field',
				'crm_' . $this->configID . '_add_field'
			);
			$this->arResult['REST_PLACEMENT_TAB_CONFIG'] = array(
				'entity' => CCrmOwnerType::ResolveName($this->entityTypeID),
				'placement' => \Bitrix\Rest\Api\UserFieldType::PLACEMENT_UF_TYPE,
			);
		}
		//end Rest placement and userfield types

		$this->arResult['ENTITY_CONFIG_OPTIONS'] = $this->getEntityConfigOptions();

		$this->arResult['EDITOR_OPTIONS'] = array('show_always' => 'Y');
		$this->arResult['ENTITY_CONFIG_CATEGORY_NAME'] = $this->getConfigurationCategoryName();

		if ($this->arResult['READ_ONLY'])
		{
			//region Spotlight
			$this->arResult['ENABLE_INLINE_EDIT_SPOTLIGHT'] = false;
			//endregion
		}
		else
		{
			//region Spotlight
			$this->arResult['INLINE_EDIT_SPOTLIGHT_ID'] = "crm-entity-editor-inline-edit-hint";
			$spotlight = new Spotlight($this->arResult['INLINE_EDIT_SPOTLIGHT_ID']);
			$spotlight->setUserType(Spotlight::USER_TYPE_OLD);
			$this->arResult['ENABLE_INLINE_EDIT_SPOTLIGHT'] = $spotlight->isAvailable();
			//endregion
		}

		if (\Bitrix\Crm\Integration\Calendar::isResourceBookingAvailableForEntity($this->arParams['USER_FIELD_ENTITY_ID']))
		{
			\Bitrix\Main\Loader::includeModule('calendar');
		}

		$this->arResult['CONTEXT']['EDITOR_CONFIG_ID'] = $this->configID;

		$this->arResult['MESSAGES'] = (array)($this->arParams['MESSAGES'] ?? []);

		$this->prepareRestrictions();
		$this->prepareEntityData();
	}

	protected function prepareEntityData(): void
	{
		$data = \Bitrix\Crm\Component\Utils\JsonCompatibleConverter::convert($this->arResult['ENTITY_DATA'] ?? []);
		if (isset($data['sort']) && is_array($data['sort']))
		{
			$data['sort'] = array_map('intval', $data['sort']);
		}

		$this->arResult['PREPARED_ENTITY_DATA'] = $data;
	}

	protected function initPull(): void
	{
		if ($this->entityID > 0)
		{
			$pullManager = Container::getInstance()->getPullManager();
			$this->arResult['PULL_TAG'] = $pullManager->subscribeOnItemUpdate(
				$this->entityTypeID,
				$this->entityID
			);
			$this->arResult['PULL_MODULE_ID'] = 'crm';
			$this->arResult['CAN_USE_PULL'] = Main\Config\Option::get('crm', 'can_use_pull_in_entity_editor', 'Y');
		}
	}

	protected function getDefaultFieldOptionFlags(int $entityTypeId, string $fieldName): int
	{
		if (CCrmOwnerType::IsDefined($entityTypeId))
		{
			if ($fieldName === 'CLIENT')
			{
				return 1; // showAlways
			}
		}

		return 0;
	}

	protected function prepareDefaultOptionFlags()
	{
		$entityTypeId =
			isset($this->arResult['ENTITY_TYPE_ID'])
				? (int)$this->arResult['ENTITY_TYPE_ID']
				: CCrmOwnerType::Undefined
		;

		if (!CCrmOwnerType::IsDefined($entityTypeId))
		{
			return;
		}

		$configs = [];
		if (is_array($this->arParams['~ENTITY_CONFIG']))
		{
			$configs[] = &$this->arParams['~ENTITY_CONFIG'];
		}
		if (is_array($this->arResult['ENTITY_CONFIG']))
		{
			$configs[] = &$this->arResult['ENTITY_CONFIG'];
		}

		foreach ($configs as &$config)
		{
			foreach ($config as &$section)
			{
				if (
					isset($section['type'])
					&& $section['type'] === 'section'
					&& is_array($section['elements'])
				)
				{
					foreach ($section['elements'] as &$element)
					{
						if (isset($element['name']))
						{
							$optionFlags = $this->getDefaultFieldOptionFlags($entityTypeId, $element['name']);
							if ($optionFlags > 0)
							{
								$element['optionFlags'] = $optionFlags;
							}
						}
					}
				}
			}
		}
	}

	protected function prepareConfig()
	{
		$this->prepareDefaultOptionFlags();
		parent::prepareConfig();
	}

	protected function getDefaultEntityConfigOptions(): array
	{
		return [
			'client_layout' => \Bitrix\Crm\Settings\LayoutSettings::getCurrent()->getClientLayoutType()
		];
	}

	protected function getAdditionalUserFieldTypeList(): array
	{
		$typeList = array();
		if(Main\Loader::includeModule('rest'))
		{
			$handlerList = \Bitrix\Rest\PlacementTable::getHandlersList(\Bitrix\Rest\Api\UserFieldType::PLACEMENT_UF_TYPE);
			foreach($handlerList as $handlerInfo)
			{
				$typeList[] = array(
					'USER_TYPE_ID' => \Bitrix\Rest\UserField\Callback::getUserTypeId($handlerInfo),
					'TITLE' => $handlerInfo['TITLE'],
					'LEGEND' => $handlerInfo['COMMENT']
				);
			}
		}

		return $typeList;
	}

	protected function isUfAddressConvertionEnabled()
	{
		$result = false;

		if (($this->entityTypeID === CCrmOwnerType::Lead
				|| $this->entityTypeID === CCrmOwnerType::Deal
				|| $this->entityTypeID === CCrmOwnerType::Contact
				|| $this->entityTypeID === CCrmOwnerType::Company)
			&& EntityAuthorization::isAuthorized()
			&& CCrmAuthorizationHelper::CheckConfigurationUpdatePermission())
		{
			$companyAgent = CompanyAddressConvertAgent::getInstance();
			$companyUfAgent = CompanyUfAddressConvertAgent::getInstance();
			$companyUfAgentAllowed = (
				!$companyAgent->isEnabled() &&
				$companyUfAgent->isEnabled() &&
				!$companyUfAgent->isActive() &&
				in_array($this->entityTypeID, $companyUfAgent->getAllowedEntityTypes(), true)
			);
			$contactAgent = ContactAddressConvertAgent::getInstance();
			$contactUfAgent = ContactUfAddressConvertAgent::getInstance();
			$contactUfAgentAllowed = (
				!$contactAgent->isEnabled() &&
				$contactUfAgent->isEnabled() &&
				!$contactUfAgent->isActive() &&
				in_array($this->entityTypeID, $contactUfAgent->getAllowedEntityTypes(), true)
			);
			switch ($this->entityTypeID)
			{
				case CCrmOwnerType::Lead:
				case CCrmOwnerType::Deal:
					$result = ($companyUfAgentAllowed && $contactUfAgentAllowed);
					break;
				case CCrmOwnerType::Company:
					$result = $companyUfAgentAllowed;
					break;
				case CCrmOwnerType::Contact:
					$result = $contactUfAgentAllowed;
					break;
			}
		}

		return $result;
	}

	protected function prepareRestrictions(): void
	{
		$ufAccessRightsRestriction = RestrictionManager::getUfAccessRightsRestriction();
		$ufAddRestriction = RestrictionManager::getUserFieldAddRestriction();
		$ufResourceBookingRestriction = RestrictionManager::getResourceBookingRestriction();

		$isUfAccessRightsPermitted = $ufAccessRightsRestriction->hasPermission();
		$isUfAddPermitted = !$ufAddRestriction->isExceeded($this->entityTypeID);
		$isResourceBookingPermitted = $ufResourceBookingRestriction->hasPermission();

		$this->arResult['RESTRICTIONS'] = [
			'userFieldAccessRights' => [
				'isPermitted' => $isUfAccessRightsPermitted,
				'restrictionCallback' => $isUfAccessRightsPermitted ? '' : $ufAccessRightsRestriction->prepareInfoHelperScript(),
			],
			'userFieldAdd' => [
				'isPermitted' => $isUfAddPermitted,
				'restrictionCallback' => $isUfAddPermitted ? '' : $ufAddRestriction->prepareInfoHelperScript(),
			],
			'userFieldResourceBooking' => [
				'isPermitted' => $isResourceBookingPermitted,
				'restrictionCallback' => $isResourceBookingPermitted ? '' : $ufResourceBookingRestriction->prepareInfoHelperScript(),
			],
		];
	}

	private function getAdditionalFieldsData(): array
	{
		$entityId = $this->entityID;
		if ($entityId <= 0)
		{
			return [];
		}

		$repository = Repository::createFromId($this->entityTypeID, $entityId);
		if (!$repository)
		{
			return [];
		}

		$contextManager = new ContextManager();

		return [
			'context' => [
				'data' => $contextManager->getData(),
				'fields' => $repository->getFieldsData(),
			],
		];
	}

	private static function setOpportunityWithCurrencyDefaultOptions(array $schemeElement): array {
		$schemeElement['options']['isPayButtonVisible'] = $schemeElement['options']['isPayButtonVisible'] ?? 'true';
		$schemeElement['options']['isPaymentDocumentsVisible'] = $schemeElement['options']['isPaymentDocumentsVisible'] ?? 'true';

		return $schemeElement;
	}

	protected function getSavedScopeAndConfiguration(EntityEditorConfiguration $configuration, $configScope, bool $isForceDefaultConfig): array
	{
		$scopeConfigId = (empty($this->arResult['SCOPE_PREFIX'])
			? $this->configID
			: $this->arResult['SCOPE_PREFIX']
		);
		if (!$configScope)
		{
			$configScope = EntityEditorConfigScope::UNDEFINED;
		}
		if (!EntityEditorConfigScope::isDefined($configScope))
		{
			$configScope = $configuration->getScope($scopeConfigId);
		}

		$userScopeId = null;
		if (is_array($configScope))
		{
			$userScopeId = $configScope['userScopeId'];
			$configScope = $configScope['scope'];
		}

		$configScope = $this->rewriteConfigScopeByUserPermission($configScope);

		$userScopes = null;
		if (isset($scopeConfigId))
		{
			$moduleId = ($this->arParams['MODULE_ID'] ?? null);
			$userScopes = method_exists(\Bitrix\Ui\EntityForm\Scope::class, 'getAllUserScopes')
				? Scope::getInstance()->getAllUserScopes($scopeConfigId, $moduleId, false)
				: Scope::getInstance()->getUserScopes($scopeConfigId, $moduleId, false)
			;
		}

		$config = null;
		if (!$isForceDefaultConfig)
		{
			[$config, $configScope] = $this->getConfigByScope($configuration, $configScope, $userScopeId, $userScopes);
		}

		if (
			(!$config && $configScope === EntityEditorConfigScope::CUSTOM)
			|| $configScope === EntityEditorConfigScope::UNDEFINED
		)
		{
			$configScope = is_array($configuration->get($this->configID, EntityEditorConfigScope::PERSONAL))
				? EntityEditorConfigScope::PERSONAL
				: EntityEditorConfigScope::COMMON;
		}

		return [$configScope, $config, $userScopes, $userScopeId];
	}

	private function rewriteConfigScopeByUserPermission(?string $configScope): ?string
	{
		$isPersonalViewAllowed = Container::getInstance()->getUserPermissions()->isPersonalViewAllowed($this->entityTypeID, $this->categoryId);

		if ($configScope === EntityEditorConfigScope::PERSONAL && !$isPersonalViewAllowed)
		{
			$configScope = EntityEditorConfigScope::COMMON;
		}

		$this->arResult['PERSONAL_VIEW_ALLOWED'] = $isPersonalViewAllowed;

		return $configScope;
	}

	/**
	 * @param EntityEditorConfiguration $configuration
	 * @param string|null $configScope
	 * @param mixed $userScopeId
	 * @param array|null $userScopes
	 * @return array
	 */
	private function getConfigByScope(EntityEditorConfiguration $configuration, ?string $configScope, mixed $userScopeId, ?array $userScopes): array
	{
		$config = null;

		if ($configScope === UI\Form\EntityEditorConfigScope::CUSTOM)
		{
			if (array_key_exists($userScopeId, $userScopes))
			{
				$config = Scope::getInstance()->getScopeById($userScopeId);
			}
			if (!$config)
			{
				$configScope = UI\Form\EntityEditorConfigScope::UNDEFINED;
			}
		}

		if (!$config && UI\Form\EntityEditorConfigScope::isDefined($configScope))
		{
			$config = $configuration->get($this->configID, $configScope);
		} elseif (!$config)
		{
			//Try to resolve current scope by stored configuration
			$config = $configuration->get($this->configID, UI\Form\EntityEditorConfigScope::PERSONAL);
			if (is_array($config) && !empty($config))
			{
				$configScope = UI\Form\EntityEditorConfigScope::PERSONAL;
			} else
			{
				$config = $configuration->get($this->configID, UI\Form\EntityEditorConfigScope::COMMON);
				$configScope = UI\Form\EntityEditorConfigScope::COMMON;
			}
		}

		return array($config, $configScope);
	}
}
