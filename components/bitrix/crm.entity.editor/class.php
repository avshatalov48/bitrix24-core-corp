<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Spotlight;
use Bitrix\Crm\Agent\Requisite\CompanyAddressConvertAgent;
use Bitrix\Crm\Agent\Requisite\ContactAddressConvertAgent;
use Bitrix\Crm\Agent\Requisite\CompanyUfAddressConvertAgent;
use Bitrix\Crm\Agent\Requisite\ContactUfAddressConvertAgent;
use Bitrix\Crm\Attribute\FieldAttributeManager;
use Bitrix\Crm\Entity\EntityEditorConfigScope;
use Bitrix\Crm\Restriction\RestrictionManager;
use Bitrix\Crm\Security\EntityAuthorization;

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
		$htmlFieldNames = [];
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
				if (!is_array($field['data']['options']))
				{
					$this->arResult['ENTITY_FIELDS'][$index]['data']['options'] = [];
				}
				$this->arResult['ENTITY_FIELDS'][$index]['data']['options']['canActivateUfAddressConverter'] =
				$field['data']['options']['canActivateUfAddressConverter'] = 'Y';
			}

			$availableFields[$name] = $field;
			if(isset($field['required']) && $field['required'] === true
				|| is_array($field['data'])
					&& isset($field['data']['isRequiredByAttribute'])
					&& $field['data']['isRequiredByAttribute'])
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

				if (is_array($this->arResult['ENTITY_DATA'][$name])
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
			'html' => $htmlFieldNames,
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

				if (is_array($defaultConfig[$sectionName]) && !empty($defaultConfig[$sectionName]['data'])) {
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

					$schemeElement = $availableFields[$name];
					$fieldType = $schemeElement['type'] ?? '';

					$title = '';
					if (!($configScope === EntityEditorConfigScope::COMMON && $fieldType === 'userField')) {
						$title = $configElement['title'];
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
		$this->entityTypeID = $this->arResult['ENTITY_TYPE_ID'];
		$this->entityTypeName = CCrmOwnerType::ResolveName($this->entityTypeID);
		$this->arResult['ENTITY_TYPE_NAME'] = $this->entityTypeName;

		$this->prepareConfig();

		$this->arResult['ENABLE_SETTINGS_FOR_ALL'] = CCrmAuthorizationHelper::CanEditOtherSettings();

		//region CAN_UPDATE_PERSONAL_CONFIGURATION && CAN_UPDATE_COMMON_CONFIGURATION
		$this->arResult['CAN_UPDATE_PERSONAL_CONFIGURATION'] = true;
		$this->arResult['CAN_UPDATE_COMMON_CONFIGURATION'] = CCrmAuthorizationHelper::CheckConfigurationUpdatePermission();

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

		$this->arResult['PATH_TO_ENTITY_DETAILS'] = \CCrmOwnerType::GetDetailsUrl(
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
		$this->arResult['CAN_CREATE_CONTACT'] = \CCrmContact::CheckCreatePermission($userPermissions);
		$this->arResult['CAN_CREATE_COMPANY'] = \CCrmCompany::CheckCreatePermission($userPermissions);
		//endregion

		$this->arResult['LANGUAGES'] = $this->loadLanguages();

		//region Attribute configuration
		$this->arResult['ATTRIBUTE_CONFIG'] = null;
		if(CCrmAuthorizationHelper::CheckConfigurationUpdatePermission())
		{
			$this->arResult['ATTRIBUTE_CONFIG'] = is_array($this->arParams['~ATTRIBUTE_CONFIG']) ?
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
				unset($isPermitted, $isPhaseDependent);
			}
		}
		//endregion

		//Bizproc
		$this->arResult['BIZPROC_MANAGER_CONFIG'] = array();
		$bizprocEventType = $this->entityID === 0 ? CCrmBizProcEventType::Create : CCrmBizProcEventType::Edit;
		if (CCrmBizProcHelper::HasParameterizedAutoWorkflows($this->entityTypeID, $bizprocEventType))
		{
			$this->arResult['BIZPROC_MANAGER_CONFIG'] = array(
				"hasParameters" => true,
				"moduleId" => 'crm',
				"entity" => CCrmBizProcHelper::ResolveDocumentName($this->entityTypeID),
				"documentType" => CCrmOwnerType::ResolveName($this->entityTypeID),
				"autoExecuteType" => $bizprocEventType,
				'fieldName' => 'bizproc_parameters'
			);
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
				'entity' => \CCrmOwnerType::ResolveName($this->entityTypeID),
				'placement' => \Bitrix\Rest\Api\UserFieldType::PLACEMENT_UF_TYPE,
			);
		}
		//end Rest placement and userfield types

		$this->arResult['ENTITY_CONFIG_OPTIONS'] = $this->getEntityConfigOptions();

		$this->arResult['EDITOR_OPTIONS'] = array('show_always' => 'Y');
		$this->arResult['ENTITY_CONFIG_CATEGORY_NAME'] = $this->getConfigurationCategoryName();

		//region Spotlight
		$this->arResult['INLINE_EDIT_SPOTLIGHT_ID'] = "crm-entity-editor-inline-edit-hint";
		$spotlight = new Spotlight($this->arResult['INLINE_EDIT_SPOTLIGHT_ID']);
		$spotlight->setUserType(Spotlight::USER_TYPE_OLD);
		$this->arResult['ENABLE_INLINE_EDIT_SPOTLIGHT'] = $spotlight->isAvailable();
		//endregion

		if (\Bitrix\Crm\Integration\Calendar::isResourceBookingAvailableForEntity($this->arParams['USER_FIELD_ENTITY_ID']))
		{
			\Bitrix\Main\Loader::includeModule('calendar');
		}

		$this->arResult['CONTEXT']['EDITOR_CONFIG_ID'] = $this->configID;

		$this->arResult['MESSAGES'] = (array)($this->arParams['MESSAGES'] ?? []);

		$this->prepareUfAccessRightRestriction();
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

	protected function prepareUfAccessRightRestriction(): void
	{
		$restriction = RestrictionManager::getUfAccessRightsRestriction();

		$this->arResult['USER_FIELD_ACCESS_RIGHTS']['IS_PERMITTED'] = $restriction->hasPermission();

		if(!$this->arResult['USER_FIELD_ACCESS_RIGHTS']['IS_PERMITTED'])
		{
			$this->arResult['USER_FIELD_ACCESS_RIGHTS']['LOCK_SCRIPT'] = $restriction->prepareInfoHelperScript();
		}
	}
}
