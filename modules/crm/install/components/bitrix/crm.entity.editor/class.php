<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Spotlight;
use Bitrix\Crm\Agent\Requisite\CompanyAddressConvertAgent;
use Bitrix\Crm\Agent\Requisite\ContactAddressConvertAgent;
use Bitrix\Crm\Agent\Requisite\CompanyUfAddressConvertAgent;
use Bitrix\Crm\Agent\Requisite\ContactUfAddressConvertAgent;
use Bitrix\Crm\Entity\EntityEditorConfigScope;
use Bitrix\Crm\Restriction\RestrictionManager;
use Bitrix\Crm\Security\EntityAuthorization;

Loc::loadMessages(__FILE__);

class CCrmEntityEditorComponent extends CBitrixComponent
{
	/** @var int */
	protected $userID = 0;
	/** @var int */
	protected $entityTypeID = 0;
	/** @var int */
	protected $entityID = 0;
	/** @var string */
	protected $guid = '';
	/** @var string */
	protected $configID = '';
	/** @var string */
	protected $optionID = '';

	/** @var array */
	protected $errors = array();

	public function executeComponent()
	{
		$this->initialize();
		$this->includeComponentTemplate();
	}

	protected function initialize()
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		if(!Bitrix\Main\Loader::includeModule('crm'))
		{
			$this->errors[] = GetMessage('CRM_MODULE_NOT_INSTALLED');
			return;
		}
		if(!Bitrix\Main\Loader::includeModule('ui'))
		{
			$this->errors[] = GetMessage('UI_MODULE_NOT_INSTALLED');
			return;
		}

		$this->userID = CCrmSecurityHelper::GetCurrentUserID();
		$this->guid = $this->arResult['GUID'] = isset($this->arParams['GUID']) ? $this->arParams['GUID'] : 'entity_editor';
		$this->configID = $this->arResult['CONFIG_ID'] = isset($this->arParams['CONFIG_ID']) ? $this->arParams['CONFIG_ID'] : $this->guid;

		$this->arResult['READ_ONLY'] = isset($this->arParams['~READ_ONLY'])
			&& $this->arParams['~READ_ONLY'];

		$this->arResult['INITIAL_MODE'] = isset($this->arParams['~INITIAL_MODE'])
			? $this->arParams['~INITIAL_MODE'] : '';

		$this->arResult['ENABLE_MODE_TOGGLE'] = !isset($this->arParams['~ENABLE_MODE_TOGGLE'])
			|| $this->arParams['~ENABLE_MODE_TOGGLE'];

		$this->arResult['ENABLE_VISIBILITY_POLICY'] = !isset($this->arParams['~ENABLE_VISIBILITY_POLICY'])
			|| $this->arParams['~ENABLE_VISIBILITY_POLICY'];

		$this->arResult['ENABLE_TOOL_PANEL'] = !isset($this->arParams['~ENABLE_TOOL_PANEL'])
			|| $this->arParams['~ENABLE_TOOL_PANEL'];

		$this->arResult['ENABLE_BOTTOM_PANEL'] = !isset($this->arParams['~ENABLE_BOTTOM_PANEL'])
			|| $this->arParams['~ENABLE_BOTTOM_PANEL'];

		$this->arResult['ENABLE_FIELDS_CONTEXT_MENU'] = !isset($this->arParams['~ENABLE_FIELDS_CONTEXT_MENU'])
			|| $this->arParams['~ENABLE_FIELDS_CONTEXT_MENU'];

		$this->arResult['ENABLE_PAGE_TITLE_CONTROLS'] = !isset($this->arParams['~ENABLE_PAGE_TITLE_CONTROLS'])
			|| $this->arParams['~ENABLE_PAGE_TITLE_CONTROLS'];

		$this->arResult['ENABLE_COMMUNICATION_CONTROLS'] = !isset($this->arParams['~ENABLE_COMMUNICATION_CONTROLS'])
			|| $this->arParams['~ENABLE_COMMUNICATION_CONTROLS'];

		$this->arResult['ENABLE_REQUIRED_FIELDS_INJECTION'] = !isset($this->arParams['~ENABLE_REQUIRED_FIELDS_INJECTION'])
			|| $this->arParams['~ENABLE_REQUIRED_FIELDS_INJECTION'];

		$this->arResult['ENABLE_AVAILABLE_FIELDS_INJECTION'] = isset($this->arParams['~ENABLE_AVAILABLE_FIELDS_INJECTION'])
			&& $this->arParams['~ENABLE_AVAILABLE_FIELDS_INJECTION'];

		$this->arResult['ENABLE_EXTERNAL_LAYOUT_RESOLVERS'] = isset($this->arParams['~ENABLE_EXTERNAL_LAYOUT_RESOLVERS'])
			&& $this->arParams['~ENABLE_EXTERNAL_LAYOUT_RESOLVERS'];

		$this->arResult['SHOW_EMPTY_FIELDS'] = isset($this->arParams['~SHOW_EMPTY_FIELDS'])
			&& $this->arParams['~SHOW_EMPTY_FIELDS'];

		$this->entityTypeID = isset($this->arParams['ENTITY_TYPE_ID'])
			? (int)$this->arParams['ENTITY_TYPE_ID'] : CCrmOwnerType::Undefined;
		$this->entityID = isset($this->arParams['ENTITY_ID'])
			? (int)$this->arParams['ENTITY_ID'] : 0;

		$this->arResult['ENTITY_TYPE_ID'] = $this->entityTypeID;
		$this->arResult['ENTITY_ID'] = $this->entityID;

		$this->arResult['ENTITY_DATA'] = isset($this->arParams['~ENTITY_DATA']) && is_array($this->arParams['~ENTITY_DATA'])
			? $this->arParams['~ENTITY_DATA'] : array();

		$this->arResult['ENTITY_FIELDS'] = isset($this->arParams['~ENTITY_FIELDS']) && is_array($this->arParams['~ENTITY_FIELDS'])
			? $this->arParams['~ENTITY_FIELDS'] : array();

		$this->arResult['ENTITY_VALIDATORS'] = isset($this->arParams['~ENTITY_VALIDATORS']) && is_array($this->arParams['~ENTITY_VALIDATORS'])
			? $this->arParams['~ENTITY_VALIDATORS'] : array();

		$this->arResult['DETAIL_MANAGER_ID'] = isset($this->arParams['~DETAIL_MANAGER_ID']) ? $this->arParams['~DETAIL_MANAGER_ID'] : '';

		$config = null;
		if(isset($this->arParams['~SCOPE']) && EntityEditorConfigScope::isDefined($this->arParams['~SCOPE']))
		{
			$configScope = $this->arParams['~SCOPE'];
		}
		else
		{
			$scopePrefix = isset($this->arParams['~SCOPE_PREFIX']) ? $this->arParams['~SCOPE_PREFIX'] : '';
			if($scopePrefix === '')
			{
				$scopePrefix = mb_strtolower($this->configID);
			}

			$configScope = CUserOptions::GetOption(
				'crm.entity.editor',
				"{$scopePrefix}_scope",
				EntityEditorConfigScope::UNDEFINED
			);
		}

		$config = null;
		if(!(isset($this->arParams['~FORCE_DEFAULT_CONFIG']) && $this->arParams['~FORCE_DEFAULT_CONFIG']))
		{
			if($configScope === EntityEditorConfigScope::UNDEFINED)
			{
				$config = CUserOptions::GetOption(
					'crm.entity.editor',
					$this->configID,
					null
				);

				if(is_array($config) && !empty($config))
				{
					$configScope = EntityEditorConfigScope::PERSONAL;
				}
				else
				{
					$config = CUserOptions::GetOption(
						'crm.entity.editor',
						"{$this->configID}_common",
						null,
						0
					);
					$configScope = EntityEditorConfigScope::COMMON;
				}
			}
			elseif($configScope === EntityEditorConfigScope::COMMON)
			{
				$config = CUserOptions::GetOption(
					'crm.entity.editor',
					"{$this->configID}_common",
					null,
					0
				);
			}
			else
			{
				$config = CUserOptions::GetOption(
					'crm.entity.editor',
					$this->configID,
					null
				);
			}
		}
		elseif($configScope === EntityEditorConfigScope::UNDEFINED)
		{
			$configScope = is_array(CUserOptions::GetOption('crm.entity.editor', $this->configID, null))
				? EntityEditorConfigScope::PERSONAL
				: EntityEditorConfigScope::COMMON;
		}

		$defaultConfig = array();
		if(!is_array($config) || empty($config))
		{
			$config = isset($this->arParams['~ENTITY_CONFIG']) && is_array($this->arParams['~ENTITY_CONFIG'])
				? $this->arParams['~ENTITY_CONFIG'] : array();
		}
		elseif(isset($this->arParams['~ENTITY_CONFIG']) && is_array($this->arParams['~ENTITY_CONFIG']))
		{
			foreach($this->arParams['~ENTITY_CONFIG'] as $element)
			{
				$defaultConfig[$element['name']] = $element;
			}
		}

		$this->arResult['ENTITY_CONTROLLERS'] = isset($this->arParams['~ENTITY_CONTROLLERS']) && is_array($this->arParams['~ENTITY_CONTROLLERS'])
				? $this->arParams['~ENTITY_CONTROLLERS'] : array();

		$availableFields = array();
		$requiredFields = array();
		$hasEmptyRequiredFields = false;
		$htmlFieldNames = array();
		$isUfAddressConverterEnabled = $this->isUfAddressConvertionEnabled();
		foreach($this->arResult['ENTITY_FIELDS'] as $index => $field)
		{
			$name = isset($field['name']) ? $field['name'] : '';
			if($name === '')
			{
				continue;
			}

			$typeName = isset($field['type']) ? $field['type'] : '';
			if($typeName === 'html')
			{
				$htmlFieldNames[] = $name;
			}

			if ($isUfAddressConverterEnabled
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
			if(isset($field['required']) && $field['required'] === true)
			{
				$requiredFields[$name] = $field;
				if($hasEmptyRequiredFields)
				{
					continue;
				}

				//HACK: Skip if user field of type Boolean. Absence of value is treated as equivalent to FALSE.
				$fieldType = isset($field['type']) ? $field['type'] : '';
				if($fieldType === 'userField')
				{
					$fieldInfo = isset($field['data']) && isset($field['data']['fieldInfo'])
						? $field['data']['fieldInfo'] : array();

					if(isset($fieldInfo['USER_TYPE_ID']) && $fieldInfo['USER_TYPE_ID'] === 'boolean')
					{
						continue;
					}
				}

				if(isset($this->arResult['ENTITY_DATA'][$name])
					&& is_array($this->arResult['ENTITY_DATA'][$name])
					&& isset($this->arResult['ENTITY_DATA'][$name]['IS_EMPTY'])
					&& $this->arResult['ENTITY_DATA'][$name]['IS_EMPTY']
				)
				{
					$hasEmptyRequiredFields = true;
				}
			}
		}

		$primarySectionIndex = 0;
		$serviceSectionIndex = -1;
		$additionalSectionIndex = -1;
		$scheme = array();
		for($i = 0, $configQty = count($config); $i < $configQty; $i++)
		{
			$configItem = $config[$i];
			$type = isset($configItem['type']) ? $configItem['type'] : '';
			if($type !== 'section')
			{
				continue;
			}

			$sectionName = isset($configItem['name']) ? $configItem['name'] : '';
			if($sectionName === 'main')
			{
				$primarySectionIndex = $i;
			}
			elseif($sectionName === 'required')
			{
				$serviceSectionIndex = $i;
			}
			elseif($sectionName === 'additional')
			{
				$additionalSectionIndex = $i;
			}

			if (is_array($defaultConfig[$sectionName]) && !empty($defaultConfig[$sectionName]['data']))
			{
				$configItem['data'] = $defaultConfig[$sectionName]['data'];
			}

			$elements = isset($configItem['elements']) && is_array($configItem['elements'])
				? $configItem['elements'] : array();

			$schemeElements = array();
			for($j = 0, $elementQty = count($elements); $j < $elementQty; $j++)
			{
				$configElement = $elements[$j];
				$name = isset($configElement['name']) ? $configElement['name'] : '';
				if($name === '')
				{
					continue;
				}

				$schemeElement = $availableFields[$name];
				$fieldType = isset($schemeElement['type']) ? $schemeElement['type'] : '';

				$title = '';
				if(!($configScope === EntityEditorConfigScope::COMMON && $fieldType === 'userField'))
				{
					$title = isset($configElement['title']) ? $configElement['title'] : '';
				}

				if($title !== '')
				{
					if(isset($schemeElement['title']))
					{
						$schemeElement['originalTitle'] = $schemeElement['title'];
					}
					$schemeElement['title'] = $title;
				}

				$optionFlags = isset($configElement['optionFlags']) ? (int)$configElement['optionFlags'] : 0;
				if($optionFlags > 0)
				{
					$schemeElement['optionFlags'] = $optionFlags;
				}

				$schemeElements[] = $schemeElement;
				unset($availableFields[$name]);

				if(isset($requiredFields[$name]))
				{
					unset($requiredFields[$name]);
				}
			}
			$scheme[] = array_merge($configItem, array('elements' => $schemeElements));
		}

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
					$configItem = $config[$serviceSectionIndex];
					if(isset($scheme[$serviceSectionIndex]['elements'])
						&& is_array($scheme[$serviceSectionIndex]['elements'])
					)
					{
						$schemeElements = $scheme[$serviceSectionIndex]['elements'];
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
					array_splice(
						$config,
						$serviceSectionIndex,
						0,
						array($configItem)
					);

					array_splice(
						$scheme,
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

				$scheme[$serviceSectionIndex]['elements'] = $schemeElements;
			}
		}

		if(!empty($availableFields) && $this->arResult['ENABLE_AVAILABLE_FIELDS_INJECTION'])
		{
			$schemeElements = array();
			if($additionalSectionIndex >= 0)
			{
				$configItem = $config[$additionalSectionIndex];
				if(isset($scheme[$additionalSectionIndex]['elements'])
					&& is_array($scheme[$additionalSectionIndex]['elements'])
				)
				{
					$schemeElements = $scheme[$additionalSectionIndex]['elements'];
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

				$additionalSectionIndex = ($serviceSectionIndex >= 0 ? $serviceSectionIndex : $primarySectionIndex) + 1;
				array_splice(
					$config,
					$additionalSectionIndex,
					0,
					array($configItem)
				);

				array_splice(
					$scheme,
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

			$scheme[$additionalSectionIndex]['elements'] = $schemeElements;
		}

		$this->arResult['ENABLE_CONFIG_SCOPE_TOGGLE'] = !isset($this->arParams['~ENABLE_CONFIG_SCOPE_TOGGLE'])
			|| $this->arParams['~ENABLE_CONFIG_SCOPE_TOGGLE'];
		$this->arResult['ENTITY_CONFIG_SCOPE'] = $configScope;
		$this->arResult['ENTITY_CONFIG'] = $config;
		$this->arResult['ENTITY_SCHEME'] = $scheme;

		$this->arResult['ENTITY_AVAILABLE_FIELDS'] = array_values($availableFields);
		$this->arResult['ENTITY_HTML_FIELD_NAMES'] = $htmlFieldNames;

		$this->arResult['ENABLE_AJAX_FORM'] = !isset($this->arParams['~ENABLE_AJAX_FORM'])
			|| $this->arParams['~ENABLE_AJAX_FORM'];

		$this->arResult['ENABLE_REQUIRED_USER_FIELD_CHECK'] = !isset($this->arParams['~ENABLE_REQUIRED_USER_FIELD_CHECK'])
			|| $this->arParams['~ENABLE_REQUIRED_USER_FIELD_CHECK'];

		$this->arResult['ENABLE_USER_FIELD_CREATION'] = isset($this->arParams['~ENABLE_USER_FIELD_CREATION'])
			&& $this->arParams['~ENABLE_USER_FIELD_CREATION'];
		$this->arResult['USER_FIELD_ENTITY_ID'] = isset($this->arParams['~USER_FIELD_ENTITY_ID'])
			? $this->arParams['~USER_FIELD_ENTITY_ID'] : '';
		$this->arResult['USER_FIELD_CREATE_PAGE_URL'] = isset($this->arParams['~USER_FIELD_CREATE_PAGE_URL'])
			? $this->arParams['~USER_FIELD_CREATE_PAGE_URL'] : '';
		$this->arResult['USER_FIELD_CREATE_SIGNATURE'] = isset($this->arParams['~USER_FIELD_CREATE_SIGNATURE'])
			? $this->arParams['~USER_FIELD_CREATE_SIGNATURE'] : '';

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

		$this->arResult['ENABLE_SECTION_EDIT'] = isset($this->arParams['~ENABLE_SECTION_EDIT'])
			&& $this->arParams['~ENABLE_SECTION_EDIT'];

		$this->arResult['ENABLE_SECTION_CREATION'] = isset($this->arParams['~ENABLE_SECTION_CREATION'])
			&& $this->arParams['~ENABLE_SECTION_CREATION'];

		$this->arResult['SERVICE_URL'] = isset($this->arParams['~SERVICE_URL'])
			? $this->arParams['~SERVICE_URL'] : '';

		$this->arResult['IS_EMBEDDED'] = isset($this->arParams['~IS_EMBEDDED'])
			&& $this->arParams['~IS_EMBEDDED'];

		$this->arResult['EXTERNAL_CONTEXT_ID'] = isset($this->arParams['~EXTERNAL_CONTEXT_ID']) ? $this->arParams['~EXTERNAL_CONTEXT_ID'] : '';
		$this->arResult['CONTEXT_ID'] = isset($this->arParams['~CONTEXT_ID']) ? $this->arParams['~CONTEXT_ID'] : '';

		$this->arResult['CONTEXT'] = isset($this->arParams['~CONTEXT']) && is_array($this->arParams['~CONTEXT'])
			? $this->arParams['~CONTEXT'] : array();
		$this->arResult['CONTEXT']['EDITOR_CONFIG_ID'] = $this->configID;

		$this->arResult['DUPLICATE_CONTROL'] = isset($this->arParams['~DUPLICATE_CONTROL']) && is_array($this->arParams['~DUPLICATE_CONTROL'])
			? $this->arParams['~DUPLICATE_CONTROL'] : array();

		$this->arResult['PATH_TO_ENTITY_DETAILS'] = \CCrmOwnerType::GetDetailsUrl(
			$this->entityTypeID,
			$this->entityID,
			true,
			array()
		);
		$this->arResult['PATH_TO_CONTACT_CREATE'] = CComponentEngine::makePathFromTemplate(
			\CrmCheckPath(
				'PATH_TO_CONTACT_DETAILS',
				isset($this->arParams['~PATH_TO_CONTACT_DETAILS']) ? $this->arParams['~PATH_TO_CONTACT_DETAILS'] : '',
				$APPLICATION->GetCurPage().'?contact_id=#contact_id#&details'
			),
			array('contact_id' => 0)
		);
		$this->arResult['PATH_TO_CONTACT_EDIT'] = CComponentEngine::makePathFromTemplate(
			\CrmCheckPath(
				'PATH_TO_CONTACT_DETAILS',
				isset($this->arParams['~PATH_TO_CONTACT_DETAILS']) ? $this->arParams['~PATH_TO_CONTACT_DETAILS'] : '',
				$APPLICATION->GetCurPage().'?contact_id=#contact_id#&details'
			),
			array('contact_id' => '#id#')
		);
		$this->arResult['PATH_TO_CONTACT_REQUISITE_SELECT'] = \CrmCheckPath(
			'PATH_TO_CONTACT_REQUISITE_SELECT',
			isset($this->arParams['~PATH_TO_CONTACT_REQUISITE_SELECT']) ? $this->arParams['~PATH_TO_CONTACT_REQUISITE_SELECT'] : '',
			$APPLICATION->GetCurPage().'?contact_id=#contact_id#&requisiteselect'
		);
		$this->arResult['PATH_TO_COMPANY_CREATE'] = CComponentEngine::makePathFromTemplate(
			\CrmCheckPath(
				'PATH_TO_COMPANY_DETAILS',
				isset($this->arParams['~PATH_TO_COMPANY_DETAILS']) ? $this->arParams['~PATH_TO_COMPANY_DETAILS'] : '',
				$APPLICATION->GetCurPage().'?company_id=#company_id#&details'
			),
			array('company_id' => 0)
		);
		$this->arResult['PATH_TO_COMPANY_EDIT'] = CComponentEngine::makePathFromTemplate(
			\CrmCheckPath(
				'PATH_TO_COMPANY_DETAILS',
				isset($this->arParams['~PATH_TO_COMPANY_DETAILS']) ? $this->arParams['~PATH_TO_COMPANY_DETAILS'] : '',
				$APPLICATION->GetCurPage().'?company_id=#company_id#&details'
			),
			array('company_id' => "#id#")
		);
		$this->arResult['PATH_TO_COMPANY_REQUISITE_SELECT'] = \CrmCheckPath(
			'PATH_TO_COMPANY_REQUISITE_SELECT',
			isset($this->arParams['~PATH_TO_COMPANY_REQUISITE_SELECT']) ? $this->arParams['~PATH_TO_COMPANY_REQUISITE_SELECT'] : '',
			$APPLICATION->GetCurPage().'?company_id=#company_id#&requisiteselect'
		);

		$this->arResult['PATH_TO_REQUISITE_EDIT'] = '/bitrix/components/bitrix/crm.requisite.details/slider.ajax.php?requisite_id=#requisite_id#&'.bitrix_sessid_get();

		//region Permissions
		$userPermissions = CCrmPerms::GetCurrentUserPermissions();
		$this->arResult['CAN_CREATE_CONTACT'] = \CCrmContact::CheckCreatePermission($userPermissions);
		$this->arResult['CAN_CREATE_COMPANY'] = \CCrmCompany::CheckCreatePermission($userPermissions);
		//endregion

		//region Languages
		$this->arResult['LANGUAGES'] = array();
		$dbResultLangs = \CLanguage::GetList($by = '', $order = '');
		while($lang = $dbResultLangs->Fetch())
		{
			$this->arResult['LANGUAGES'][] = array('LID' => $lang['LID'], 'NAME' => $lang['NAME']);
		}
		//endregion

		//region Attribute configuration
		$this->arResult['ATTRIBUTE_CONFIG'] = null;
		if(CCrmAuthorizationHelper::CheckConfigurationUpdatePermission())
		{
			$this->arResult['ATTRIBUTE_CONFIG'] = isset($this->arParams['~ATTRIBUTE_CONFIG']) && is_array($this->arParams['~ATTRIBUTE_CONFIG'])
				? $this->arParams['~ATTRIBUTE_CONFIG'] : null;
			if(isset($this->arResult['ATTRIBUTE_CONFIG']))
			{
				$restriction = RestrictionManager::getAttributeConfigRestriction();
				$this->arResult['ATTRIBUTE_CONFIG']['IS_PERMITTED'] = $restriction->hasPermission();
				if(!$this->arResult['ATTRIBUTE_CONFIG']['IS_PERMITTED'])
				{
					$this->arResult['ATTRIBUTE_CONFIG']['LOCK_SCRIPT'] = $restriction->prepareInfoHelperScript();
				}
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
			$this->arResult['REST_PLACEMENT_TAB_CONFIG'] = array(
				'entity' => \CCrmOwnerType::ResolveName($this->entityTypeID),
				'placement' => \Bitrix\Rest\Api\UserFieldType::PLACEMENT_UF_TYPE,
			);
		}
		//end Rest placement and userfield types

		$optionPrefix = isset($this->arParams['~OPTION_PREFIX']) ? $this->arParams['~OPTION_PREFIX'] : '';
		if($optionPrefix === '')
		{
			$optionPrefix = mb_strtolower($this->configID);
		}
		if($configScope === EntityEditorConfigScope::COMMON)
		{
			$this->optionID = $this->arResult['OPTION_ID'] = "{$optionPrefix}_common_opts";
			$this->arResult['ENTITY_CONFIG_OPTIONS'] = \CUserOptions::GetOption(
				'crm.entity.editor',
				$this->optionID,
				array('client_layout' => \Bitrix\Crm\Settings\LayoutSettings::getCurrent()->getClientLayoutType()),
				0
			);
		}
		else
		{
			$this->optionID = $this->arResult['OPTION_ID'] = "{$optionPrefix}_opts";
			$this->arResult['ENTITY_CONFIG_OPTIONS'] = \CUserOptions::GetOption(
				'crm.entity.editor',
				$this->optionID,
				array('client_layout' => \Bitrix\Crm\Settings\LayoutSettings::getCurrent()->getClientLayoutType())
			);
		}

		$this->arResult['EDITOR_OPTIONS'] = array('show_always' => 'Y');

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
	}

	protected function getAdditionalUserFieldTypeList()
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
}