<?php

use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\EntityAddress;
use Bitrix\Crm\EntityAddressType;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\EntityBankDetail;
use Bitrix\Crm\EntityPreset;
use Bitrix\Crm\Integration\ClientResolver;
use Bitrix\Crm\Integration\Rest\AppPlacement;
use Bitrix\Crm\Restriction\RestrictionManager;
use Bitrix\Rest\PlacementTable;

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
	die();

Loc::loadMessages(__FILE__);

class CCrmRequisiteEditComponent extends \CBitrixComponent
{
	protected $site;
	protected $elementId;
	/** @var EntityRequisite $requisite */
	protected $requisite;
	protected $requisiteInfo;
	protected $requisiteData;
	protected $fieldsAllowed;
	protected $fieldsOptional;
	protected $fieldsOptionalSort;
	protected $fieldsOptionalTitles;
	protected $sectionsOptionalTitles;
	protected $fieldsOptionalEnabled;
	protected $fieldsOfActivePresets;
	protected $fieldsOfInactivePresets;
	protected $fieldsOfFixedPresets;
	protected $fields;
	protected $componentId;
	protected $errors;
	protected $readOnlyMode;
	protected $popupMode;
	protected $innerFormMode;
	protected $isLastInForm;
	protected $bInternal;
	protected $bEdit;
	protected $bCopy;
	protected $bVarsFromForm;
	protected $bDeleteAction;
	protected $bCreateFromData;
	protected $refererUrl;
	protected $formAction;
	protected $validActions;
	protected $requisiteFieldTitles;

	protected $entityTypeId;
	protected $entityId;
	protected $entityInfo;
	
	protected $presetId;
	/** @var \Bitrix\Crm\EntityPreset $preset */
	protected $preset;
	protected $presetInfo;
	protected $presetFields;
	protected $presetFieldTitles;
	protected $presetFieldsInShortList;
	protected $presetFieldsSort;
	protected $popupManagerId;
	protected $formSettingsId;
	protected $canEditPreset;

	protected $prefix;
	protected $fieldNameTemplate;
	protected $enableFieldMasquerading;
	protected $pseudoId;
	protected $externalFormData;
	protected $externalContextId;

	/** @var \Bitrix\Crm\EntityBankDetail $bankDetail */
	protected $bankDetail;
	protected $bankDetailInfoList;
	protected $bankDetailFieldsList;
	protected $presetCountryId;
	protected $bankDetailFieldsInfoByCountry;
	protected $deletedBankDetailIds;

	protected $enableDupControl;

	protected $isRestModuleIncluded;

	protected function getOptionalFieldTitle($id, $defaultTitle = '')
	{
		$result = '';

		$optionalFieldTitle = '';
		if ($this->fieldsOptionalEnabled
			&& isset($this->fieldsOptionalTitles[$id])
			&& !empty($this->fieldsOptionalTitles[$id]))
		{
			$optionalFieldTitle = $this->fieldsOptionalTitles[$id];
		}

		if (!empty($optionalFieldTitle))
		{
			$result = $optionalFieldTitle;
		}
		else if (isset($this->presetFieldTitles[$id]) && !empty($this->presetFieldTitles[$id]))
		{
			$result = strval($this->presetFieldTitles[$id]);
		}

		if ($result === '')
		{
			if (!is_string($defaultTitle) || $defaultTitle === '')
			{
				$defaultTitle = isset($this->requisiteFieldTitles[$id]) ? $this->requisiteFieldTitles[$id] : $id;
			}

			$result = $defaultTitle;
		}

		return $result;
	}

	public function __construct($component = null)
	{
		parent::__construct($component);

		$this->site = new CSite();
		$this->elementId = 0;
		$this->requisite = null;
		$this->requisiteInfo = null;
		$this->requisiteData = null;
		$this->fieldsAllowed = array();
		$this->fieldsOptional = array();
		$this->fieldsOptionalSort = array();
		$this->fieldsOptionalTitles = array();
		$this->sectionsOptionalTitles = array();
		$this->fieldsOptionalEnabled = false;
		$this->fieldsOfActivePresets = array();
		$this->fieldsOfInactivePresets = array();
		$this->fieldsOfFixedPresets = array();
		$this->fields = array();

		$this->componentId = $this->randString();
		$this->errors = array();

		$this->readOnlyMode = false;
		$this->popupMode = false;
		$this->innerFormMode = false;
		$this->isLastInForm = false;

		$this->bInternal = false;
		$this->bEdit = false;
		$this->bCopy = false;
		$this->bVarsFromForm = false;
		$this->bDeleteAction = false;
		$this->bCreateFromData = false;
		$this->refererUrl = '';
		$this->formAction = '';
		$this->validActions = array('saveAndView', 'saveAndAdd', 'apply', 'save', 'verify', 'reload');
		$this->requisiteFieldTitles = array();

		$this->entityTypeId = 0;
		$this->entityId = 0;
		$this->entityInfo = null;
		
		$this->presetId = 0;
		$this->preset = null;
		$this->presetInfo = null;
		$this->presetFields = array();
		$this->presetFieldTitles = array();
		$this->presetFieldsInShortList = array();
		$this->presetFieldsSort = array();

		$this->popupManagerId = '';
		$this->formSettingsId = '';
		$this->canEditPreset = false;
		$this->prefix = '';
		$this->fieldNameTemplate = '';
		$this->enableFieldMasquerading = false;
		$this->pseudoId = '';
		$this->externalFormData = null;
		$this->externalContextId = "";

		$this->bankDetail = null;
		$this->bankDetailInfoList = array();
		$this->bankDetailFieldsList = array();
		$this->presetCountryId = 0;
		$this->bankDetailFieldsInfoByCountry = array();
		$this->deletedBankDetailIds = array();

		$this->enableDupControl = false;

		$this->isRestModuleIncluded = false;
	}

	public function executeComponent()
	{
		if (!$this->checkModules())
		{
			$this->showErrors();
			return $this->getComponentResult();
		}

		if (!$this->parseParams())
		{
			$this->showErrors();
			return $this->getComponentResult();
		}

		if ($this->bCreateFromData)
		{
			if (!$this->prepareFieldsFromData())
			{
				$this->showErrors();
				return $this->getComponentResult();
			}
		}
		else if ($this->elementId > 0 && ($this->bEdit || $this->bCopy))
		{
			if (!$this->prepareExistingFields())
			{
				$this->showErrors();
				return $this->getComponentResult();
			}
		}
		else
		{
			if (!$this->prepareFreshFields())
			{
				$this->showErrors();
				return $this->getComponentResult();
			}
		}

		if (!$this->checkEntityReference())
		{
			$this->showErrors();
			return $this->getComponentResult();
		}

		if ($this->bVarsFromForm || $this->bDeleteAction || $this->externalFormData !== null)
		{
			$this->parseFormData();
			$this->processFormAction();

			if (!empty($this->errors))
			{
				$this->showErrors();
			}
			else
			{
				$this->processRedirect();
			}
		}

		$this->prepareResult();

		$this->includeComponentTemplate();

		include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.requisite/include/nav.php');

		return $this->getComponentResult();
	}

	protected function parseParams()
	{
		$this->arParams['PATH_TO_REQUISITE_LIST'] = CrmCheckPath('PATH_TO_REQUISITE_LIST', $this->arParams['PATH_TO_REQUISITE_LIST'], $this->getApp()->GetCurPage());
		$this->arParams['PATH_TO_REQUISITE_EDIT'] = CrmCheckPath('PATH_TO_REQUISITE_EDIT', $this->arParams['PATH_TO_REQUISITE_EDIT'], $this->getApp()->GetCurPage().'?id=#id#&edit');

		$this->arParams['PATH_TO_CONTACT_SHOW'] = CrmCheckPath('PATH_TO_CONTACT_SHOW', $this->arParams['PATH_TO_CONTACT_SHOW'], $this->getApp()->GetCurPage().'?contact_id=#contact_id#&show');
		$this->arParams['PATH_TO_COMPANY_SHOW'] = CrmCheckPath('PATH_TO_COMPANY_SHOW', $this->arParams['PATH_TO_COMPANY_SHOW'], $this->getApp()->GetCurPage().'?company_id=#company_id#&show');
		$this->arParams['PATH_TO_USER_PROFILE'] = CrmCheckPath('PATH_TO_USER_PROFILE', $this->arParams['PATH_TO_USER_PROFILE'], '/company/personal/user/#user_id#/');
		$this->arParams['NAME_TEMPLATE'] = empty($this->arParams['NAME_TEMPLATE']) ? $this->site->GetNameFormat(false) : str_replace(array("#NOBR#", "#/NOBR#"), array("", ""), $this->arParams["NAME_TEMPLATE"]);

		$this->arParams['POPUP_MODE'] = (isset($this->arParams['POPUP_MODE']) && mb_strtoupper($this->arParams['POPUP_MODE']) === 'Y') ? 'Y' : 'N';
		$this->popupManagerId = isset($this->arParams['POPUP_MANAGER_ID']) ? strval($this->arParams['POPUP_MANAGER_ID']) : '';
		$this->readOnlyMode = $this->arParams['READ_ONLY_MODE'] === 'Y';
		$this->popupMode = $this->arParams['POPUP_MODE'] === 'Y';

		$this->arParams['INNER_FORM_MODE'] = (isset($this->arParams['INNER_FORM_MODE']) && mb_strtoupper($this->arParams['INNER_FORM_MODE']) === 'Y') ? 'Y' : 'N';
		$this->innerFormMode = ($this->arParams['INNER_FORM_MODE'] === 'Y');

		$this->arParams['IS_LAST_IN_FORM'] = (isset($this->arParams['IS_LAST_IN_FORM']) && mb_strtoupper($this->arParams['IS_LAST_IN_FORM']) === 'Y') ? 'Y' : 'N';
		$this->isLastInForm = (!$this->innerFormMode || $this->arParams['IS_LAST_IN_FORM'] === 'Y');

		$this->requisite = new EntityRequisite();
		$this->preset = new EntityPreset();
		$this->bankDetail = new EntityBankDetail();

		$bEdit = false;
		$bCopy = false;
		$this->elementId = $this->arParams['ELEMENT_ID'] = (int)$this->arParams['ELEMENT_ID'];
		if ($this->elementId > 0)
		{
			$this->requisiteInfo = $this->requisite->getById($this->elementId);
			$this->requisiteInfo[EntityRequisite::ADDRESS] = EntityRequisite::getAddresses($this->elementId);

			// bank details
			$select = array_merge(
				array('ID', 'ENTITY_TYPE_ID', 'ENTITY_ID', 'COUNTRY_ID', 'NAME'),
				$this->bankDetail->getRqFields(),
				array('COMMENTS')
			);
			$res = $this->bankDetail->getList(
				array(
					'order' => array('SORT', 'ID'),
					'filter' => array('=ENTITY_TYPE_ID' => \CCrmOwnerType::Requisite, '=ENTITY_ID' => $this->elementId),
					'select' => $select
				)
			);
			while ($row = $res->fetch())
			{
				$this->bankDetailInfoList[$row['ID']] = $row;
			}
			unset($select, $res, $row);


			$bEdit = true;
		}
		else
		{
			if (isset($this->arParams['PSEUDO_ID']))
			{
				$this->pseudoId = $this->arParams['PSEUDO_ID'];
			}
			else if (isset($_REQUEST['pseudoId'])
				&& is_string($_REQUEST['pseudoId'])
				&& preg_match('/^n\d+$/', $_REQUEST['pseudoId']))
			{
				$this->pseudoId = $_REQUEST['pseudoId'];
			}
			else
			{
				$this->pseudoId = 'n0';
			}
		}

		if (!empty($_REQUEST['copy']))
		{
			$bCopy = true;
			$bEdit = false;
		}
		$this->bEdit = $bEdit;
		$this->bCopy = $bCopy;

		// entity type id
		if (isset($this->arParams['ENTITY_TYPE_ID']))
		{
			$this->entityTypeId = intval($this->arParams['ENTITY_TYPE_ID']);
		}
		else if (isset($_REQUEST['etype']))
		{
			$this->entityTypeId = (int)$_REQUEST['etype'];
		}
		else if (($this->bEdit || $this->bCopy)
			&& is_array($this->requisiteInfo) && isset($this->requisiteInfo['ENTITY_TYPE_ID']))
		{
			$this->entityTypeId = (int)$this->requisiteInfo['ENTITY_TYPE_ID'];
		}
		else
		{
			$this->entityTypeId = 0;
		}

		// entity id
		if (isset($this->arParams['ENTITY_ID']))
		{
			$this->entityId = intval($this->arParams['ENTITY_ID']);
		}
		else if (isset($_REQUEST['eid']))
		{
			$this->entityId =  (int)$_REQUEST['eid'];
		}
		else if (($this->bEdit || $this->bCopy)
			&& is_array($this->requisiteInfo) && isset($this->requisiteInfo['ENTITY_ID']))
		{
			$this->entityId = (int)$this->requisiteInfo['ENTITY_ID'];
		}
		else
		{
			$this->entityId =  0;
		}

		$this->prefix = isset($this->arParams['PREFIX'])
			? $this->arParams['PREFIX'] : '';
		$this->fieldNameTemplate = isset($this->arParams['FIELD_NAME_TEMPLATE'])
			? $this->arParams['FIELD_NAME_TEMPLATE'] : '';
		$this->enableFieldMasquerading = $this->fieldNameTemplate !== '';
		$fieldsAllowedInfo = array_merge(
				$this->requisite->getRqFields(),
				$this->requisite->getUserFields()
		);
		foreach ($fieldsAllowedInfo as $fieldName)
		{
			$this->fieldsAllowed[$this->prepareFieldKey($fieldName)] = true;
		}
		unset($fieldsAllowedInfo, $fieldName);

		$fieldsOfActivePresetsInfo = $this->preset->getSettingsFieldsOfPresets(
			\Bitrix\Crm\EntityPreset::Requisite,
			'active'
		);
		foreach ($fieldsOfActivePresetsInfo as $fieldName)
			$this->fieldsOfActivePresets[$this->prepareFieldKey($fieldName)] = true;
		unset($fieldsOfActivePresetsInfo, $fieldName);

		$fieldsOfInctivePresetsInfo = $this->preset->getSettingsFieldsOfPresets(
			\Bitrix\Crm\EntityPreset::Requisite,
			'inactive'
		);
		foreach ($fieldsOfInctivePresetsInfo as $fieldName)
			$this->fieldsOfInactivePresets[$this->prepareFieldKey($fieldName)] = true;
		unset($fieldsOfInactivePresetsInfo, $fieldName);

		$fieldsOfFixedPresetsInfo = $this->requisite->getFieldsOfFixedPresets();
		foreach ($fieldsOfFixedPresetsInfo as $fieldName)
			$this->fieldsOfFixedPresets[$this->prepareFieldKey($fieldName)] = true;
		unset($fieldsOfFixedPresetsInfo, $fieldName);

		if(isset($this->arParams['~REQUISITE_FORM_DATA'])
			&& is_array($this->arParams['~REQUISITE_FORM_DATA'])
			&& !empty($this->arParams['~REQUISITE_FORM_DATA']))
		{
			$this->externalFormData = $this->arParams['~REQUISITE_FORM_DATA'];
		}

		$this->bVarsFromForm =
			$this->externalFormData === null
			&& $_SERVER['REQUEST_METHOD'] === 'POST' && check_bitrix_sessid()
			&& (isset($_POST['saveAndView'])
				|| isset($_POST['saveAndAdd'])
				|| isset($_POST['apply'])
				|| isset($_POST['save'])
				|| isset($_POST['reload'])
				|| isset($_POST['verify']));
		$this->bDeleteAction = (isset($_REQUEST['delete']) && check_bitrix_sessid());

		if (!$this->bVarsFromForm && !$this->bDeleteAction)
		{
			$requisiteData = '';
			$requisiteDataSign = '';
			if (isset($this->arParams['~REQUISITE_DATA']) && isset($this->arParams['~REQUISITE_DATA_SIGN']))
			{
				$requisiteData = &$this->arParams['~REQUISITE_DATA'];
				$requisiteDataSign = &$this->arParams['~REQUISITE_DATA_SIGN'];
			}
			else if (isset($_REQUEST['requisite_data']) && isset($_REQUEST['requisite_data_sign']))
			{
				$requisiteData = &$_REQUEST['REQUISITE_DATA'];
				$requisiteDataSign = &$_REQUEST['REQUISITE_DATA_SIGN'];
			}
			$signer = new \Bitrix\Main\Security\Sign\Signer();
			$jsonData = null;

			$data = strval($requisiteData);
			if($signer->validate(
				$data,
				strval($requisiteDataSign),
				'crm.requisite.edit-'.$this->entityTypeId))
			{
				$jsonData = $data;
			}
			unset($data, $requisiteData, $requisiteDataSign);

			if ($jsonData)
			{
				try
				{
					$requisiteData = \Bitrix\Main\Web\Json::decode($jsonData);
					$this->requisiteData = $requisiteData;
					$this->bCreateFromData = true;
					unset($requisiteData);
				}
				catch (\Bitrix\Main\SystemException $e)
				{
				}
			}
		}

		// preset id
		if (isset($_REQUEST['pid']))
		{
			$this->presetId = (int)$_REQUEST['pid'];
		}
		else if (isset($this->arParams['PRESET_ID']))
		{
			$this->presetId = intval($this->arParams['PRESET_ID']);
		}
		else if (is_array($this->requisiteInfo) && isset($this->requisiteInfo['PRESET_ID']))
		{
			$this->presetId = (int)$this->requisiteInfo['PRESET_ID'];
		}
		if ($this->bVarsFromForm && isset($_POST['PRESET_ID']))
		{
			// preset id from form
			$this->presetId = (int)$_POST['PRESET_ID'];
		}

		// preset dependent info
		$this->canEditPreset = EntityPreset::checkUpdatePermission();
		if ($this->presetId > 0)
		{
			$presetInfo = $this->preset->getById($this->presetId);
			if (is_array($presetInfo))
			{
				if (isset($presetInfo['COUNTRY_ID']))
					$this->presetCountryId = (int)$presetInfo['COUNTRY_ID'];

				$presetFieldsSort = array(
					'ID' => array(),
					'SORT' => array(),
					'FIELD_NAME' => array()
				);
				$this->presetInfo = $presetInfo;
				if (is_array($this->presetInfo['SETTINGS']))
				{
					$presetFieldsInfo = $this->preset->settingsGetFields($this->presetInfo['SETTINGS']);
					foreach ($presetFieldsInfo as $fieldInfo)
					{
						if (isset($fieldInfo['FIELD_NAME']))
						{
							if($this->enableFieldMasquerading)
							{
								$this->presetFields[$this->prepareFieldKey($fieldInfo['FIELD_NAME'])] = true;
							}
							else
							{
								$this->presetFields[$fieldInfo['FIELD_NAME']] = true;
							}

							$this->presetFieldTitles[$fieldInfo['FIELD_NAME']] =
								(isset($fieldInfo['FIELD_TITLE'])) ? strval($fieldInfo['FIELD_TITLE']) : "";
							if (isset($fieldInfo['IN_SHORT_LIST']) && $fieldInfo['IN_SHORT_LIST'] === 'Y')
								$this->presetFieldsInShortList[$fieldInfo['FIELD_NAME']] = true;

							$presetFieldsSort['ID'][] = isset($fieldInfo['ID']) ? (int)$fieldInfo['ID'] : 0;
							$presetFieldsSort['SORT'][] = isset($fieldInfo['SORT']) ? (int)$fieldInfo['SORT'] : 0;
							$presetFieldsSort['FIELD_NAME'][] = $fieldInfo['FIELD_NAME'];
						}
					}
					unset($presetFieldsInfo);

					if (!empty($presetFieldsSort['FIELD_NAME']))
					{
						if(array_multisort(
							$presetFieldsSort['SORT'], SORT_ASC, SORT_NUMERIC,
							$presetFieldsSort['ID'], SORT_ASC, SORT_NUMERIC,
							$presetFieldsSort['FIELD_NAME']))
						{
							$this->presetFieldsSort = array_flip($presetFieldsSort['FIELD_NAME']);
						}
					}
					unset($presetFieldsSort);
				}
			}

			$this->formSettingsId = "CRM_REQUISITE_EDIT_0_PID{$this->presetId}";
			$formSettings = CUserOptions::GetOption('crm.requisite.edit', $this->formSettingsId, array());
			if (is_array($formSettings)
				&& (!isset($formSettings['settings_disabled'])
					|| $formSettings['settings_disabled'] !== 'Y')
				&& is_array($formSettings['tabs']))
			{
				foreach ($formSettings['tabs'] as $tabInfo)
				{
					if (is_array($tabInfo)
						&& isset($tabInfo['id'])
						&& $tabInfo['id'] === 'tab_1'
						&& is_array($tabInfo['fields']))
					{
						$this->fieldsOptionalEnabled = true;
						$sortIndex = 0;
						$fieldTitle = '';
						$staticFormFields = ['ENTITY_BINDING', 'PRESET_NAME', 'NAME'];
						foreach ($tabInfo['fields'] as $fieldInfo)
						{
							if (is_array($fieldInfo))
							{
								$fieldKey = isset($fieldInfo['id']) ? $this->prepareFieldKey($fieldInfo['id']) : '';
								$fieldType = isset($fieldInfo['type']) ? $fieldInfo['type'] : '';



								if ($fieldType === 'section')
								{
									$sectionTitle = isset($fieldInfo['name']) ? strval($fieldInfo['name']) : '';
									if (!empty($sectionTitle))
									{
										$this->sectionsOptionalTitles[$fieldInfo['id']] = $sectionTitle;
									}
								}
								else if (!empty($fieldKey)
									&& (isset($this->fieldsAllowed[$fieldKey])
										|| in_array($fieldKey, $staticFormFields, true)))
								{
									$this->fieldsOptional[$fieldKey] = $fieldInfo;
									$fieldTitle = isset($fieldInfo['name']) ? strval($fieldInfo['name']) : '';
									if (!empty($fieldTitle))
									{
										$this->fieldsOptionalTitles[$fieldInfo['id']] = $fieldTitle;
									}
									$this->fieldsOptionalSort[$fieldInfo['id']] = $sortIndex++;
								}
							}
						}
						unset($sortIndex, $sectionTitle, $fieldTitle, $staticFormFields);
						break;
					}
				}
			}
		}

		$currentCountryId = EntityPreset::getCurrentCountryId();
		if ($this->presetCountryId <= 0)
			$this->presetCountryId = $currentCountryId;
		unset($currentCountryId);

		$this->bankDetailFieldsInfoByCountry =
			$this->bankDetail->getFormFieldsInfoByCountry($this->presetCountryId);

		$this->requisiteFieldTitles = $this->requisite->getFieldsTitles($this->presetCountryId);

		if (isset($arParams['INTERNAL_FILTER']) && !empty($arParams['INTERNAL_FILTER']))
			$this->bInternal = true;

		if ($this->bVarsFromForm && !empty($_REQUEST['REQUISITE_REFERER']))
		{
			$this->refererUrl = strval($_REQUEST['REQUISITE_REFERER']);
		}
		else if (!empty($_REQUEST['back_url']))
		{
			$this->refererUrl = strval($_REQUEST['back_url']);
		}
		else if ($this->entityTypeId > 0 && $this->entityId > 0 && !empty($GLOBALS['_SERVER']['HTTP_REFERER']))
		{
			$this->refererUrl = strval($_SERVER['HTTP_REFERER']);
		}

		if ($this->popupMode && !$this->bEdit && $this->entityTypeId > 0)
		{
			$this->enableDupControl = Bitrix\Crm\Integrity\DuplicateControl::isControlEnabledFor($this->entityTypeId);
		}

		$this->externalContextId = isset($this->arParams['EXTERNAL_CONTEXT_ID']) ? $this->arParams['EXTERNAL_CONTEXT_ID'] : '';

		return true;
	}

	protected function prepareFieldKey($fieldName)
	{
		if(!$this->enableFieldMasquerading)
		{
			return $fieldName;
		}
		return str_replace('#FIELD_NAME#', $fieldName, $this->fieldNameTemplate);
	}

	protected function getPrefix()
	{
		return $this->prefix;
	}

	protected function getFieldNameTemplate()
	{
		return $this->fieldNameTemplate;
	}

	protected function prepareExistingFields()
	{
		if (!is_array($this->requisiteInfo))
		{
			$this->errors[] = GetMessage('CRM_REQUISITE_EDIT_ERR_NOT_FOUND', array('#ID#' => $this->elementId));
			return false;
		}

		$fields = $this->requisiteInfo;

		if ($this->bCopy)
		{
			$curDateTime = new \Bitrix\Main\Type\DateTime();
			$curUserId = CCrmSecurityHelper::GetCurrentUserID();
			$fields['ID'] = 0;
			$fields['DATE_CREATE'] = $curDateTime;
			$fields['DATE_MODIFY'] = $curDateTime;
			$fields['CREATED_BY_ID'] = $curUserId;
			$fields['MODIFY_BY_ID'] = $curUserId;
		}

		$this->fields = $fields;

		// bank details
		if (is_array($this->bankDetailInfoList))
		{
			$n = 0;
			foreach ($this->bankDetailInfoList as $bankDetailInfo)
			{
				$fields = $bankDetailInfo;

				if ($this->bCopy)
				{
					$curDateTime = new \Bitrix\Main\Type\DateTime();
					$curUserId = CCrmSecurityHelper::GetCurrentUserID();
					$fields['ID'] = 0;
					$fields['DATE_CREATE'] = $curDateTime;
					$fields['DATE_MODIFY'] = $curDateTime;
					$fields['CREATED_BY_ID'] = $curUserId;
					$fields['MODIFY_BY_ID'] = $curUserId;
				}
				$pseudoId = ($fields['ID'] > 0) ? $fields['ID'] : 'n'.$n++;
				$this->bankDetailFieldsList[$pseudoId] = $fields;
			}
		}

		return true;
	}

	protected function prepareFieldsFromData()
	{
		$fields = (is_array($this->requisiteData) && is_array($this->requisiteData['fields'])) ?
			$this->requisiteData['fields'] : null;

		if (!is_array($fields))
		{
			$this->errors[] = GetMessage('CRM_REQUISITE_EDIT_ERR_INVALID_DATA');
			return false;
		}

		$fields['ENTITY_TYPE_ID'] = $this->entityTypeId;
		$fields['ENTITY_ID'] = $this->entityId;
		$fields['PRESET_ID'] = $this->presetId;

		if ($this->bCopy)
		{
			$curDateTime = new \Bitrix\Main\Type\DateTime();
			$curUserId = CCrmSecurityHelper::GetCurrentUserID();
			$fields['ID'] = 0;
			$fields['DATE_CREATE'] = $curDateTime;
			$fields['DATE_MODIFY'] = $curDateTime;
			$fields['CREATED_BY_ID'] = $curUserId;
			$fields['MODIFY_BY_ID'] = $curUserId;
		}

		$this->fields = $fields;
		
		// bank details
		if (is_array($this->requisiteData['bankDetailFieldsList']))
		{
			$n = 0;
			$this->bankDetailFieldsList = array();
			foreach ($this->requisiteData['bankDetailFieldsList'] as $bankDetailFields)
			{
				$bankDetailFields['ENTITY_TYPE_ID'] = \CCrmOwnerType::Requisite;
				$bankDetailFields['ENTITY_ID'] = $this->elementId;
				$bankDetailFields['COUNTRY_ID'] = $this->presetCountryId;

				if ($this->bCopy)
				{
					$bankDetailFields['ID'] = 0;
					$bankDetailFields['DATE_CREATE'] = $curDateTime;
					$bankDetailFields['DATE_MODIFY'] = $curDateTime;
					$bankDetailFields['CREATED_BY_ID'] = $curUserId;
					$bankDetailFields['MODIFY_BY_ID'] = $curUserId;
				}

				$pseudoId = ($bankDetailFields['ID'] > 0) ? $bankDetailFields['ID'] : 'n'.$n++;
				$this->bankDetailFieldsList[$pseudoId] = $bankDetailFields;
			}
		}

		return true;
	}

	protected function prepareFreshFields()
	{
		$curDateTime = new \Bitrix\Main\Type\DateTime();
		$curUserId = CCrmSecurityHelper::GetCurrentUserID();
		$fields = array(
			'ID' => 0,
			'ENTITY_TYPE_ID' => $this->entityTypeId,
			'ENTITY_ID' => $this->entityId,
			'PRESET_ID' => $this->presetId,
			'DATE_CREATE' => $curDateTime,
			'CREATED_BY_ID' => $curUserId,
			'NAME' => '',
			'ACTIVE' => 'Y',
			'SORT' => 500
		);

		if(is_array($this->presetInfo) && isset($this->presetInfo['NAME']))
		{
			$fields['NAME'] = $this->presetInfo['NAME'];
		}
		foreach ($this->requisite->getRqFields() as $rqFieldName)
		{
			if($rqFieldName === EntityRequisite::ADDRESS)
			{
				$this->fields[$rqFieldName] = array();
			}
			else
			{
				$fields[$rqFieldName] = '';
			}
		}
		$this->fields = $fields;

		return true;
	}

	protected function checkEntityReference()
	{
		$this->entityTypeId = isset($this->fields['ENTITY_TYPE_ID']) ? intval($this->fields['ENTITY_TYPE_ID']) : 0;
		$this->entityId = isset($this->fields['ENTITY_ID']) ? intval($this->fields['ENTITY_ID']) : 0;

		if ($this->entityTypeId !== CCrmOwnerType::Company && $this->entityTypeId !== CCrmOwnerType::Contact)
		{
			$this->errors[] = GetMessage('CRM_REQUISITE_EDIT_ERR_ENTITY_TYPE_ID');
		}
		else
		{
			$entityInfo = null;
			if ($this->entityTypeId === CCrmOwnerType::Company)
			{
				if ($this->entityId > 0)
				{
					$dbRes = CCrmCompany::GetListEx(
						array(),
						array('=ID' => $this->entityId, 'CHECK_PERMISSIONS' => 'N'),
						false,
						false,
						array('TITLE')
					);

					if ($dbRes)
					{
						$entityInfo = $dbRes->Fetch();
					}
				}

				//Suppress errors in popup mode if requisite is bound to new entity (that is not yet saved)
				if($this->entityId > 0 || !($this->popupMode || $this->innerFormMode))
				{
					if (!is_array($entityInfo))
					{
						$this->errors[] = GetMessage('CRM_REQUISITE_EDIT_ERR_COMPANY_NOT_FOUND');
					}
					elseif (!CCrmCompany::CheckUpdatePermission($this->entityId))
					{
						$this->readOnlyMode = true;
						if (!CCrmCompany::CheckReadPermission($this->entityId))
						{
							$this->errors[] = GetMessage('CRM_REQUISITE_EDIT_ERR_COMPANY_EDIT_DENIED');
						}
					}
				}
			}
			else if ($this->entityTypeId === CCrmOwnerType::Contact)
			{
				if ($this->entityId > 0)
				{
					$dbRes = CCrmContact::GetListEx(
						array(),
						array('=ID' => $this->entityId,'CHECK_PERMISSIONS' => 'N'),
						false,
						false,
						array('HONORIFIC', 'NAME', 'SECOND_NAME', 'LAST_NAME')
					);

					if ($dbRes)
					{
						$entityInfo = $dbRes->Fetch();
					}
				}

				//Suppress errors in popup mode if requisite is bound to new entity (that is not yet saved)
				if($this->entityId > 0 || !($this->popupMode || $this->innerFormMode))
				{
					if (!is_array($entityInfo))
					{
						$this->errors[] = GetMessage('CRM_REQUISITE_EDIT_ERR_CONTACT_NOT_FOUND');
					}
					elseif (!CCrmContact::CheckUpdatePermission($this->entityId))
					{
						$this->readOnlyMode = true;
						if (!CCrmContact::CheckReadPermission($this->entityId))
						{
							$this->errors[] = GetMessage('CRM_REQUISITE_EDIT_ERR_CONTACT_EDIT_DENIED');
						}
					}
				}
			}

			if (is_array($entityInfo))
			{
				$this->entityInfo = $entityInfo;
			}
		}

		return empty($this->errors);
	}

	protected function parseFormData()
	{
		global $USER_FIELD_MANAGER;

		if($this->externalFormData !== null)
		{
			// bank details
			$requisiteFormData = $this->externalFormData;
			if (array_key_exists('BANK_DETAILS', $requisiteFormData))
			{
				if (is_array($requisiteFormData['BANK_DETAILS']))
				{
					if ($this->elementId > 0)
					{
						foreach ($requisiteFormData['BANK_DETAILS'] as $pseudoId => &$bankDetailFields)
						{
							if (is_array($bankDetailFields))
							{
								$bankDetailFields['ENTITY_TYPE_ID'] = \CCrmOwnerType::Requisite;
								$bankDetailFields['ENTITY_ID'] = $this->elementId;
							}
						}
						unset($bankDetailFields);
					}
					$this->bankDetailFieldsList = $requisiteFormData['BANK_DETAILS'];
				}
				unset($requisiteFormData['BANK_DETAILS']);
			}
			
			$this->fields = $requisiteFormData;
			if ($this->elementId > 0)
			{
				$this->fields['ID'] = $this->elementId;
			}

			$this->formAction = 'reload';
		}
		else if ($this->bVarsFromForm)
		{
			if (isset($_POST['NAME']))
				$this->fields['NAME'] = trim(strval($_POST['NAME']));

			if (isset($_POST['PRESET_ID']))
				$this->fields['PRESET_ID'] = (int) $_POST['PRESET_ID'];

			if (isset($_POST['CODE']))
				$this->fields['CODE'] = trim(strval($_POST['CODE']));

			if (isset($_POST['XML_ID']))
				$this->fields['XML_ID'] = trim(strval($_POST['XML_ID']));

			if (isset($_POST['ACTIVE']))
				$this->fields['ACTIVE'] = ($_POST['ACTIVE'] === 'Y');

			if (isset($_POST['SORT']))
				$this->fields['SORT'] = (int) $_POST['SORT'];

			// RQ fields
			$rqFieldNames = $this->requisite->getRqFields();
			foreach ($rqFieldNames as $rqFieldName)
			{
				if (isset($_POST[$rqFieldName]))
				{
					if($rqFieldName === EntityRequisite::ADDRESS)
					{
						$this->fields[$rqFieldName] = is_array($_POST[$rqFieldName]) ? $_POST[$rqFieldName] : array();
					}
					else
					{
						$this->fields[$rqFieldName] = trim($_POST[$rqFieldName]);
					}
				}
			}

			$USER_FIELD_MANAGER->EditFormAddFields($this->requisite->getUfId(), $this->fields);

			// bank details
			if (is_array($_POST['BANK_DETAILS']) && !empty($_POST['BANK_DETAILS']))
			{
				foreach ($_POST['BANK_DETAILS'] as $pseudoId => $formFields)
				{
					$fields = array();
					$fieldNames = array_merge(
						array('ENTITY_TYPE_ID', 'ENTITY_ID', 'COUNTRY_ID', 'NAME'),
						$this->bankDetail->getRqFields(),
						array('COMMENTS')
					);
					foreach ($fieldNames as $fieldName)
					{
						if (isset($formFields[$fieldName]))
						{
							if ($fieldName === 'ENTITY_TYPE_ID'
								|| $fieldName === 'ENTITY_ID'
								|| $fieldName === 'COUNTRY_ID')
							{
								$fields[$fieldName] = (int)$formFields[$fieldName];
							}
							else
							{
								$fields[$fieldName] = trim(strval($formFields[$fieldName]));
							}
						}

						if(isset($formFields['DELETED']) && $formFields['DELETED'] === 'Y')
							$this->deletedBankDetailIds[$pseudoId] = true;

					}

					if (!is_array($this->bankDetailFieldsList[$pseudoId]))
						$this->bankDetailFieldsList[$pseudoId] = array();
					foreach ($fields as $fieldName => $fieldValue)
						$this->bankDetailFieldsList[$pseudoId][$fieldName] = $fieldValue;
					unset($fields);
				}
			}

			foreach ($this->validActions as $actionName)
			{
				if (isset($_POST[$actionName]))
				{
					$this->formAction = $actionName;
					break;
				}
			}
		}
		else if ($this->bDeleteAction)
		{
			$this->formAction = 'delete';
		}
		
		return true;
	}

	protected function processFormAction()
	{
		if (in_array($this->formAction, $this->validActions, true))
		{
			$dbConnection = Application::getConnection();
			$result = null;
			$saveToDB = true;

			if (($this->popupMode && $this->entityId <= 0)
					|| $this->formAction === 'reload'
					|| $this->formAction === 'verify'
			)
			{
				$saveToDB = false;
			}

			if ($saveToDB && $this->readOnlyMode)
			{
				$entityTypeName = CCrmOwnerType::ResolveName($this->entityTypeId);
				$this->errors[] = GetMessage("CRM_REQUISITE_EDIT_ERR_{$entityTypeName}_EDIT_DENIED");
				return false;
			}

			if ($this->bEdit)
			{
				$this->fields['DATE_MODIFY'] = new \Bitrix\Main\Type\DateTime();
				$this->fields['MODIFY_BY_ID'] = CCrmSecurityHelper::GetCurrentUserID();

				if ($saveToDB)
				{
					$dbConnection->startTransaction();
					$result = $this->requisite->update($this->elementId, $this->fields);
				}
				else if ($this->formAction !== 'reload')
				{
					$result = $this->requisite->checkBeforeUpdate($this->elementId, $this->fields);
				}
			}
			else
			{
				$curDateTime = new \Bitrix\Main\Type\DateTime();
				$curUserId = CCrmSecurityHelper::GetCurrentUserID();
				$this->fields['DATE_CREATE'] = $curDateTime;
				$this->fields['DATE_MODIFY'] = $curDateTime;
				$this->fields['CREATED_BY_ID'] = $curUserId;
				$this->fields['MODIFY_BY_ID'] = $curUserId;

				if ($saveToDB)
				{
					$dbConnection->startTransaction();
					$result = $this->requisite->add($this->fields);
					if($result && $result->isSuccess())
						$this->elementId = $result->getId();
				}
				else if ($this->formAction !== 'reload')
				{
					$result = $this->requisite->checkBeforeAdd($this->fields);
				}
			}

			if($result && $result->isSuccess())
			{
				if(is_array($this->bankDetailFieldsList) && !empty($this->bankDetailFieldsList))
				{
					$bankDetail = new \Bitrix\Crm\EntityBankDetail();
					foreach($this->bankDetailFieldsList as $pseudoId => &$bankDetailFields)
					{
						$bankDetailResult = null;
						if ($saveToDB)
						{
							if(isset($this->deletedBankDetailIds[$pseudoId]))
							{
								if($pseudoId > 0)
								{
									$bankDetailResult = $bankDetail->delete($pseudoId);
								}
							}
							elseif($pseudoId > 0)
							{
								$bankDetailResult = $bankDetail->update($pseudoId, $bankDetailFields);
							}
							else
							{
								$bankDetailFields['ENTITY_TYPE_ID'] = \CCrmOwnerType::Requisite;
								$bankDetailFields['ENTITY_ID'] = $this->elementId;
								$bankDetailResult = $bankDetail->add($bankDetailFields);
								if($bankDetailResult && $bankDetailResult->isSuccess())
									$bankDetailFields['ID'] = $bankDetailResult->getId();
							}
						}
						else
						{
							if($pseudoId > 0)
							{
								$bankDetailResult = $bankDetail->checkBeforeUpdate($pseudoId, $bankDetailFields);
							}
							else
							{
								$bankDetailResult = $bankDetail->checkBeforeAdd($bankDetailFields);
							}
						}

						if($bankDetailResult !== null && !$bankDetailResult->isSuccess())
						{
							$result->addErrors($bankDetailResult->getErrors());
						}
					}
					unset($bankDetailFields);
				}

				if ($result && $result->isSuccess() && is_array($this->fields))
				{
					if ($saveToDB)
						$dbConnection->commitTransaction();

					$dataFields = $this->fields;
					foreach ($dataFields as $fName => $fValue)
					{
						if ($fValue instanceof \Bitrix\Main\Type\DateTime)
							$dataFields[$fName] = $fValue->toString();
					}
					if ($this->elementId > 0)
						$dataFields['ID'] = $this->elementId;
					$fieldsInView = array_intersect_assoc($this->presetFields, $this->fieldsAllowed);
					$this->requisiteData = array(
						'fields' => $dataFields,
						'viewData' => $this->requisite->prepareViewData($dataFields, $fieldsInView),
						'bankDetailFieldsList' => array(),
						'bankDetailViewDataList' => array()
					);
					unset($dataFields, $fieldsInView);

					// bank details
					$n = 0;
					foreach ($this->bankDetailFieldsList as $bankDetailFields)
					{
						foreach ($bankDetailFields as $fName => $fValue)
						{
							if ($fValue instanceof \Bitrix\Main\Type\DateTime)
								$bankDetailFields[$fName] = $fValue->toString();
						}

						$pseudoId = (isset($bankDetailFields['ID']) && $bankDetailFields['ID'] > 0) ?
							$bankDetailFields['ID'] : 'n'.$n++;
						$this->requisiteData['bankDetailFieldsList'][$pseudoId] = $bankDetailFields;

						$this->requisiteData['bankDetailViewDataList'][] = array(
							'pseudoId' => $pseudoId,
							'viewData' => $this->bankDetail->prepareViewData(
								$bankDetailFields,
								array_keys($this->bankDetailFieldsInfoByCountry)
							)
						);
					}

					//Store deleted bank details IDs for future saving.
					if(!$saveToDB && !empty($this->deletedBankDetailIds))
					{
						$this->requisiteData['deletedBankDetailList'] = array_keys($this->deletedBankDetailIds);
					}
				}
			}

			if ($result && !$result->isSuccess() && $saveToDB)
			{
				$dbConnection->rollbackTransaction();
			}

			if ($result && !$result->isSuccess() && $this->formAction !== 'reload')
			{
				foreach ($result->getErrorMessages() as $errMsg)
					$this->errors[] = $errMsg;
				return false;
			}
		}
		else if ($this->formAction === 'delete')
		{
			if ($this->readOnlyMode)
			{
				$entityTypeName = CCrmOwnerType::ResolveName($this->entityTypeId);
				$this->errors[] = GetMessage("CRM_REQUISITE_EDIT_ERR_{$entityTypeName}_EDIT_DENIED");
				return false;
			}

			if ($this->bEdit)
			{
				if (!$this->requisite->delete($this->elementId)->isSuccess())
				{
					$this->errors[] = GetMessage('CRM_DELETE_ERROR');
					return false;
				}
			}
			else
			{
				$this->errors[] = GetMessage('CRM_DELETE_ERROR');
				return false;
			}
		}

		return true;
	}

	protected function processRedirect()
	{
		if($this->popupMode || $this->innerFormMode)
		{
			return;
		}

		switch ($this->formAction)
		{
			case 'reload':
				break;

			case 'saveAndAdd':
				$url = CHTTP::urlAddParams(
					CComponentEngine::MakePathFromTemplate(
						$this->arParams['PATH_TO_REQUISITE_EDIT'],
						array('id' => 0)
					),
					array(
						'etype' => isset($this->entityTypeId) ? $this->entityTypeId : 0,
						'eid' => isset($this->entityId) ? $this->entityId : 0
					)
				);
				if (!empty($this->refererUrl))
					$url = CHTTP::urlAddParams($url, array('back_url' => urlencode($this->refererUrl)));
				LocalRedirect($url);
				break;

			case 'apply':
				$url = CComponentEngine::MakePathFromTemplate(
					$this->arParams['PATH_TO_REQUISITE_EDIT'],
					array('id' => $this->elementId)
				);
				if (!empty($this->refererUrl))
					$url = CHTTP::urlAddParams($url, array('back_url' => urlencode($this->refererUrl)));
				LocalRedirect($url);
				break;

			default:
				$url = '';
				if (empty($this->refererUrl))
				{
					if ($this->entityTypeId === CCrmOwnerType::Company)
					{
						$url = CComponentEngine::MakePathFromTemplate(
							$this->arParams['PATH_TO_COMPANY_SHOW'],
							array('company_id' => $this->entityId)
						);
					}
					else if ($this->entityTypeId === CCrmOwnerType::Contact)
					{
						$url = CComponentEngine::MakePathFromTemplate(
							$this->arParams['PATH_TO_CONTACT_SHOW'],
							array('contact_id' => $this->entityId)
						);
					}
				}
				else
				{
					$url = $this->refererUrl;
				}
				LocalRedirect($url);
		}
	}

	protected function prepareResult()
	{
		global $USER_FIELD_MANAGER;

		$this->arResult['POPUP_MODE'] = $this->popupMode ? 'Y' : 'N';
		$this->arResult['POPUP_MANAGER_ID'] = $this->popupManagerId;
		$this->arResult['INNER_FORM_MODE'] = $this->innerFormMode ? 'Y' : 'N';
		$this->arResult['IS_LAST_IN_FORM'] = $this->isLastInForm ? 'Y' : 'N';
		$this->arResult['INTERNAL'] = $this->bInternal;
		$this->arResult['ENABLE_FIELD_MASQUERADING'] = $this->enableFieldMasquerading ? 'Y' : 'N';
		$this->arResult['PREFIX'] = 'REQUISITE';
		$this->arResult['GRID_ID'] = 'CRM_REQUISITE_LIST_V15';
		$this->arResult['COUNTRY_ID'] = $this->presetId > 0 && isset($this->presetInfo['COUNTRY_ID'])
			? (int)$this->presetInfo['COUNTRY_ID'] : 0;
		$this->arResult['ENABLE_CLIENT_RESOLUTION'] = ClientResolver::isEnabled($this->arResult['COUNTRY_ID']);
		$elementId = $this->elementId > 0 ? $this->elementId : $this->pseudoId;
		$this->arResult['FORM_ID'] = "CRM_REQUISITE_EDIT_{$elementId}_PID{$this->presetId}";
		$this->arResult['FORM_SETTINGS_ID'] = $this->formSettingsId;
		$this->arResult['CAN_EDIT_PRESET'] = $this->canEditPreset ? 'Y' : 'N';

		$this->arResult['EXTERNAL_CONTEXT_ID'] = $this->externalContextId;
		$this->arResult['PSEUDO_ID'] = $this->pseudoId;

		// fields
		$this->arResult['FIELDS'] = array('tab_1' => array());

		if ($this->innerFormMode)
		{
			$this->arResult['FIELDS']['tab_1'][] = array(
				'id' => 'section_requisite_name',
				'name' => isset($this->fields['NAME']) ? $this->fields['NAME'] : '',
				'type' => 'section',
				'associatedField' => array(
					'id' => 'NAME',
					'value' => isset($this->fields['NAME']) ? $this->fields['NAME'] : ''
				)
			);
		}
		else
		{
			$sectionId = 'section_requisite_info';
			if ($this->fieldsOptionalEnabled
				&& isset($this->sectionsOptionalTitles[$sectionId])
				&& $this->sectionsOptionalTitles[$sectionId] <> '')
			{
				$sectionTitle = $this->sectionsOptionalTitles[$sectionId];
			}
			else if ($this->entityTypeId === CCrmOwnerType::Company)
			{
				$sectionTitle = GetMessage('CRM_SECTION_COMPANY_REQUISITE_INFO');
			}
			else
			{
				$sectionTitle = GetMessage('CRM_SECTION_CONTACT_REQUISITE_INFO');
			}
			$this->arResult['FIELDS']['tab_1'][] = array(
				'id' => $sectionId,
				'name' => $sectionTitle,
				'type' => 'section'
			);
			unset($sectionId, $sectionTitle);

			$entityBindingValue = '';
			if ($this->entityTypeId === CCrmOwnerType::Contact && is_array($this->entityInfo))
			{
				$entityBindingValue = CCrmViewHelper::PrepareEntityBaloonHtml(
					array(
						'ENTITY_TYPE_ID' => $this->entityTypeId,
						'ENTITY_ID' => $this->entityId,
						'PREFIX' => 'crm_contact_link_'.$this->componentId,
						'TITLE' => CCrmContact::PrepareFormattedName($this->entityInfo),
						'CLASS_NAME' => 'crm-fld-block-readonly'
					)
				);
			}
			else if ($this->entityTypeId === CCrmOwnerType::Company && is_array($this->entityInfo))
			{
				$entityBindingValue = CCrmViewHelper::PrepareEntityBaloonHtml(
					array(
						'ENTITY_TYPE_ID' => $this->entityTypeId,
						'ENTITY_ID' => $this->entityId,
						'PREFIX' => 'crm_company_link_'.$this->componentId,
						'TITLE' => $this->entityInfo['TITLE'],
						'CLASS_NAME' => 'crm-fld-block-readonly'
					)
				);
			}

			if (!empty($entityBindingValue))
			{
				$this->arResult['FIELDS']['tab_1'][] = array(
					'id' => 'ENTITY_BINDING',
					'name' => GetMessage('CRM_REQUISITE_EDIT_ENTITY_BINDING'),
					'type' => 'custom',
					'value' => $entityBindingValue
				);
			}

			if ($this->presetId > 0 && is_array($this->presetInfo)
				&& isset($this->presetInfo['NAME']) && !empty($this->presetInfo['NAME']))
			{
				$this->arResult['FIELDS']['tab_1'][] = array(
					'id' => 'PRESET_NAME',
					'name' => GetMessage('CRM_REQUISITE_EDIT_PRESET_NAME'),
					'type' => 'label',
					'value' => $this->presetInfo['NAME']
				);
			}
		}

		if (!$this->innerFormMode)
		{
			$this->arResult['FIELDS']['tab_1'][] = array(
				'id' => 'NAME',
				'name' => isset($this->requisiteFieldTitles['NAME']) ? $this->requisiteFieldTitles['NAME'] : 'NAME',
				'type' => 'text',
				'value' => isset($this->fields['NAME']) ? $this->fields['NAME'] : '',
				'required' => true
			);
		}

		if (!$this->popupMode && !$this->innerFormMode)
		{
			$this->arResult['FIELDS']['tab_1'][] = array(
				'id' => 'SORT',
				'name' => isset($this->requisiteFieldTitles['SORT']) ? $this->requisiteFieldTitles['SORT'] : 'SORT',
				'type' => 'text',
				'value' => isset($this->fields['SORT']) ? $this->fields['SORT'] : ''
			);
		}

		// form custom HTML
		$formCustomHtml = '';
		if($this->innerFormMode)
		{
			$elementIdKey = $this->enableFieldMasquerading ? $this->prepareFieldKey('ID') : 'ID';
			$formCustomHtml .= '<input type="hidden" name="'.htmlspecialcharsbx($elementIdKey).'" value="'.$this->elementId.'"/>'.PHP_EOL;

			if($this->pseudoId !== '')
			{
				$pseudoIdKey = $this->enableFieldMasquerading ? $this->prepareFieldKey('PSEUDO_ID') : 'PSEUDO_ID';
				$formCustomHtml .= '<input type="hidden" name="'.htmlspecialcharsbx($pseudoIdKey).'" value="'.$this->pseudoId.'"/>'.PHP_EOL;
			}

			if ($this->presetId > 0)
			{
				$presetIdKey = $this->enableFieldMasquerading ? $this->prepareFieldKey('PRESET_ID') : 'PRESET_ID';
				$formCustomHtml .= '<input type="hidden" name="'.htmlspecialcharsbx($presetIdKey).'" value="'.$this->presetId.'"/>'.PHP_EOL;
			}

			$sortKey = $this->enableFieldMasquerading ? $this->prepareFieldKey('SORT') : 'SORT';
			$sort = isset($this->fields['SORT']) ? (int)$this->fields['SORT'] : 500;
			$formCustomHtml .= '<input type="hidden" name="'.htmlspecialcharsbx($sortKey).'" value="'.$sort.'"/>'.PHP_EOL;
		}
		else if($this->entityTypeId > 0 && $this->entityId > 0 && !empty($this->refererUrl))
		{
			$formCustomHtml .=
				'<input type="hidden" name="REQUISITE_REFERER" value="'.htmlspecialcharsbx($this->refererUrl).'" />'.PHP_EOL;
		}

		$this->arResult['FIELDS']['tab_1'][] = array(
			'id' => 'CUSTOM_FORM_HTML',
			'name' => GetMessage('CRM_REQUISITE_EDIT_CUSTOM_FORM_HTML'),
			'type' => 'custom',
			'visible' => false,
			'value' => $formCustomHtml
		);
		unset($formCustomHtml);

		// popup response
		$requisiteData = '';
		$requisiteDataSign = '';
		if (is_array($this->requisiteData))
		{
			$jsonData = null;
			try
			{
				$jsonData = \Bitrix\Main\Web\Json::encode($this->requisiteData);
			}
			catch (\Bitrix\Main\SystemException $e)
			{
			}

			if ($jsonData)
			{
				$signer = new \Bitrix\Main\Security\Sign\Signer();
				$requisiteDataSign = '';
				try
				{
					$requisiteDataSign = $signer->getSignature(
						$jsonData,
						'crm.requisite.edit-'.$this->entityTypeId
					);
				}
				catch (\Bitrix\Main\SystemException $e)
				{
				}

				if (!empty($requisiteDataSign))
				{
					$requisiteData = $jsonData;
				}
			}
			unset($jsonData);
		}
		$this->arResult['NEED_CLOSE_POPUP'] = (
			$this->popupMode && $this->formAction === 'save' && !$this->hasErrors()
			&& !(empty($requisiteDataSign) || empty($requisiteData))
		);

		if ($this->popupMode)
		{
			$this->arResult['FIELDS']['tab_1'][] = array(
				'id' => 'POPUP_RESPONSE',
				'name' => GetMessage('CRM_REQUISITE_EDIT_POPUP_RESPONSE'),
				'type' => 'custom',
				'visible' => false,
				'value' => '<div id="'.htmlspecialcharsbx($this->popupManagerId).'_response" class="popup-response-wrapper" style="display: none;">'.PHP_EOL.
					"\t".'<input type="hidden" name="REQUISITE_ID" value="'.$this->elementId.'" />'.PHP_EOL.
					"\t".'<input type="hidden" name="REQUISITE_DATA" value="'.
					((empty($requisiteDataSign) || empty($requisiteData)) ? '' : htmlspecialcharsbx($requisiteData)).'" />'.PHP_EOL.
					"\t".'<input type="hidden" name="REQUISITE_DATA_SIGN" value="'.
					((empty($requisiteDataSign) || empty($requisiteData)) ? '' : htmlspecialcharsbx($requisiteDataSign)).'" />'.PHP_EOL.
					'</div>'.PHP_EOL
			);
		}

		if (!$this->innerFormMode)
		{
			$sectionId = 'section_requisite_values';
			if ($this->fieldsOptionalEnabled
				&& isset($this->sectionsOptionalTitles[$sectionId])
				&& $this->sectionsOptionalTitles[$sectionId] <> '')
			{
				$sectionTitle = $this->sectionsOptionalTitles[$sectionId];
			}
			else if ($this->entityTypeId === CCrmOwnerType::Company)
			{
				$sectionTitle = GetMessage('CRM_SECTION_COMPANY_REQUISITE_VALUES');
			}
			else
			{
				$sectionTitle = GetMessage('CRM_SECTION_CONTACT_REQUISITE_VALUES');
			}
			$this->arResult['FIELDS']['tab_1'][] = array(
				'id' => $sectionId,
				'name' => $sectionTitle,
				'type' => 'section'
			);
			unset($sectionId, $sectionTitle);
		}

		// rq fields
		$fieldsInfo = $this->requisite->getFormFieldsInfo($this->presetCountryId);
		$rqFields = array();
		
		$addressFieldScheme = [
			['name' => 'ADDRESS_1', 'type' => 'multilinetext'],
			['name' => 'ADDRESS_2', 'type' => 'text'],
			['name' => 'CITY', 'type' => 'text'],
			['name' => 'REGION', 'type' => 'text'],
			['name' => 'PROVINCE', 'type' => 'text'],
			['name' => 'POSTAL_CODE', 'type' => 'text'],
			['name' => 'COUNTRY', 'type' => 'text'],
			[
				'name' => 'COUNTRY_CODE',
				'type' => 'locality',
				'related' => 'COUNTRY',
				'params' => ['locality' => 'COUNTRY']
			]
		];

		foreach ($fieldsInfo as $fieldName => $fieldInfo)
		{
			if ($fieldInfo['isRQ'] && !$fieldInfo['isUF'])
			{
				$fieldTitle = isset($this->requisiteFieldTitles[$fieldName]) ?
					$this->requisiteFieldTitles[$fieldName] : '';

				if (!empty($fieldTitle))
				{
					if ($fieldName === EntityRequisite::ADDRESS)
					{
						$addressTypes = EntityAddressType::getDescriptionsByZonesOrValues(
							[
								EntityAddress::getZoneId(),
								EntityRequisite::getAddressZoneByCountry($this->presetCountryId)
							],
							is_array($this->fields[$fieldName]) ? array_keys($this->fields[$fieldName]) : []
						);
						$addressTypeInfos = [];
						foreach ($addressTypes as $addId => $descr)
						{
							$addressTypeInfos[] = [
								'id' => $addId,
								'name' => $descr
							];
						}
						unset($addressTypes, $addId, $descr);
						$rqFields[] = array(
							'id' => $fieldName,
							'name' => $fieldTitle,
							'type' => 'multiple_address',
							'options' => array('nohover' => true),
							'componentParams' => array(
								'ADDRESS_TYPE_INFOS' => $addressTypeInfos,
								'SERVICE_URL' => '/bitrix/components/bitrix/crm.requisite.edit/ajax.php?siteID='.
									SITE_ID.'&'.bitrix_sessid_get(),
								'SCHEME' => $addressFieldScheme,
								'FIELD_NAME_TEMPLATE' => $this->prepareFieldKey($fieldName).'[#TYPE_ID#][#FIELD_NAME#]',
								'DATA' => is_array($this->fields[$fieldName]) ? $this->fields[$fieldName] : array()
							)
						);
					}
					else
					{
						if ($fieldInfo['type'] === 'boolean' || $fieldInfo['formType'] === 'checkbox')
						{
							$rqFields[] = array(
								'id' => $fieldName,
								'name' => $fieldTitle,
								'type' => 'checkbox',
								'value' => isset($this->fields[$fieldName]) && $this->fields[$fieldName] === 'Y'
									? 'Y' : 'N',
								'params' => array('data-requisite' => 'field')
							);
						}
						elseif ($this->requisite->isRqListField($fieldName))
						{
							$items = [];
							foreach ($this->requisite->getRqListFieldItems($fieldName, $this->presetCountryId) as $item)
							{
								$items[$item['VALUE']] = $item['NAME'];
							}
							$rqFields[] = array(
								'id' => $fieldName,
								'name' => $fieldTitle,
								'type' => 'list',
								'items' => $items,
								'value' => isset($this->fields[$fieldName]) ? $this->fields[$fieldName] : ''
							);
						}
						else
						{
							$rqFields[] = array(
								'id' => $fieldName,
								'name' => $fieldTitle,
								'type' => 'text',
								'value' => isset($this->fields[$fieldName]) ? $this->fields[$fieldName] : '',
								'params' => array('data-requisite' => 'field')
							);
						}
					}
				}
			}
		}
		unset($fieldName, $fieldInfo, $fieldTitle);

		// append user fields
		$fileViewer = new \Bitrix\Crm\Requisite\FileViewer();
		$userType = new CCrmUserType($USER_FIELD_MANAGER, $this->requisite->getUfId());
		$options = array('FILE_VIEWER' => $fileViewer);

		if ($this->enableFieldMasquerading)
		{
			$options['FIELD_NAME_TEMPLATE'] = $this->fieldNameTemplate;
		}

		if ($this->bCreateFromData || $this->externalFormData !== null)
		{
			$options['DEFAULT_VALUES'] = &$this->fields;
		}

		$userType->AddFields(
			$rqFields,
			$this->elementId,
			$this->arResult['FORM_ID'],
			$this->bVarsFromForm,
			false,
			false,
			$options
		);
		unset($options);

		// sort fields by preset
		$rqFieldsSorted = array();
		$rqFieldsSortedIndex = array();
		$rqFieldsUnsortedIndex = array();
		foreach ($rqFields as $rqFieldIndex => &$rqField)
		{
			if (isset($this->presetFieldsSort[$rqField['id']]))
				$rqFieldsSortedIndex[$this->presetFieldsSort[$rqField['id']]] = $rqFieldIndex;
			else
				$rqFieldsUnsortedIndex[] = $rqFieldIndex;
		}
		unset($rqFieldIndex, $rqField);
		if (!empty($rqFieldsSortedIndex))
		{
			ksort($rqFieldsSortedIndex, SORT_NUMERIC);
			foreach ($rqFieldsSortedIndex as $rqFieldIndex)
				$rqFieldsSorted[] = &$rqFields[$rqFieldIndex];
			unset($rqFieldIndex);
		}
		unset($rqFieldsSortedIndex);
		if (!empty($rqFieldsUnsortedIndex))
		{
			foreach ($rqFieldsUnsortedIndex as $rqFieldIndex)
				$rqFieldsSorted[] = &$rqFields[$rqFieldIndex];
			unset($rqFieldIndex);
		}
		unset($rqFieldsUnsortedIndex);

		// sort fields by form settings
		if ($this->fieldsOptionalEnabled)
		{
			$rqFields = $rqFieldsSorted;
			$rqFieldsSorted = array();
			$rqFieldsSortedIndex = array();
			$rqFieldsUnsortedIndex = array();
			foreach ($rqFields as $rqFieldIndex => &$rqField)
			{
				if (isset($this->fieldsOptionalSort[$rqField['id']]))
					$rqFieldsSortedIndex[$this->fieldsOptionalSort[$rqField['id']]] = $rqFieldIndex;
				else
					$rqFieldsUnsortedIndex[] = $rqFieldIndex;
			}
			unset($rqFieldIndex, $rqField);
			if (!empty($rqFieldsSortedIndex))
			{
				ksort($rqFieldsSortedIndex, SORT_NUMERIC);
				foreach ($rqFieldsSortedIndex as $rqFieldIndex)
					$rqFieldsSorted[] = &$rqFields[$rqFieldIndex];
				unset($rqFieldIndex);
			}
			unset($rqFieldsSortedIndex);
			if (!empty($rqFieldsUnsortedIndex))
			{
				foreach ($rqFieldsUnsortedIndex as $rqFieldIndex)
					$rqFieldsSorted[] = &$rqFields[$rqFieldIndex];
				unset($rqFieldIndex);
			}
			unset($rqFieldsUnsortedIndex);
		}

		$this->arResult['FIELDS']['tab_1'] = array_merge($this->arResult['FIELDS']['tab_1'], $rqFieldsSorted);
		unset($rqFieldsSorted);

		// rewrite name, inShortList, isRq
		$optionalFieldTitle = '';
		foreach ($this->arResult['FIELDS']['tab_1'] as &$fieldInfo)
		{
			if (isset($fieldInfo['id']) && !empty($fieldInfo['id']))
			{
				$fieldInfo['name'] = $this->getOptionalFieldTitle($fieldInfo['id'], $fieldInfo['name']);
				if (isset($this->fieldsAllowed[$fieldInfo['id']]))
					$fieldInfo['isRQ'] = true;
				if (isset($this->presetFieldsInShortList[$fieldInfo['id']]))
					$fieldInfo['inShortList'] = true;

				if ($this->enableFieldMasquerading)
				{
					$fieldInfo['rawId'] = $fieldInfo['id'];
					$fieldInfo['id'] = $this->prepareFieldKey($fieldInfo['id']);
					if ($fieldInfo['type'] === 'section'
						&& isset($fieldInfo['associatedField'])
						&& is_array($fieldInfo['associatedField'])
						&& isset($fieldInfo['associatedField']['id']))
					{
						$fieldInfo['associatedField']['id'] =
							$this->prepareFieldKey($fieldInfo['associatedField']['id']);
					}
				}
			}
		}
		unset($optionalFieldTitle, $fieldInfo);

		$this->arResult['USER_FIELD_ENTITY_ID'] = $this->requisite->getUfId();
		$this->arResult['ELEMENT'] = $this->fields;
		$this->arResult['ELEMENT_ID'] = $this->elementId;
		$this->arResult['FIELD_NAME_TEMPLATE'] = $this->fieldNameTemplate;

		$availableFields = array();
		foreach ($this->arResult['FIELDS'] as $tabId => $tabFields)
		{
			$aFields = array();
			foreach ($tabFields as $fieldIndex => $field)
			{
				$fieldId = $field['id'];
				$fieldType = $field['type'];
				if ($fieldType === 'section')
				{
					$aFields[$fieldId] = $field;
					continue;
				}

				$fieldIsOptionalHidden = (
					$this->fieldsOptionalEnabled
					&& !isset($this->fieldsOptional[$fieldId])
					&& isset($this->presetFields[$fieldId])
				);
				if (isset($this->fieldsAllowed[$fieldId])
					&& (!isset($this->presetFields[$fieldId]) || $fieldIsOptionalHidden))
				{
					if ($fieldIsOptionalHidden
						|| ($this->canEditPreset
							&& (isset($this->fieldsOfActivePresets[$fieldId])
								|| !(isset($this->fieldsOfInactivePresets[$fieldId])
								|| isset($this->fieldsOfFixedPresets[$fieldId])))))
					{
						$availableFields[$fieldId] = array(
							'id' => $fieldId,
							'rawId' => $field['rawId'],
							'name' => $field['name'],
							'type' => $field['type'],
							'isRQ' => true
						);
					}
					unset($this->arResult['FIELDS'][$tabId][$fieldIndex]);
				}
				else
				{
					$aFields[$fieldId] = $field;
				}
				unset($fieldId);
			}

			$this->arResult['FIELDS'][$tabId] = $aFields;
		}

		$this->arResult['AVAILABLE_FIELDS'] = $availableFields;

		// bank details
		$bankDetailsContainerId = "CRM_REQUISITE_EDIT_{$elementId}_BANK_DETAIL_CONT";
		$bankDetailFieldList = array();
		$bankDetailFieldList[] = array(
			'name' => 'ENTITY_ID',
			'title' => '',
			'type' => 'hidden',
			'defaultValue' => $this->elementId
		);
		$bankDetailFieldList[] = array(
			'name' => 'ENTITY_TYPE_ID',
			'title' => '',
			'type' => 'hidden',
			'defaultValue' => \CCrmOwnerType::Requisite
		);
		$bankDetailFieldList[] = array(
			'name' => 'COUNTRY_ID',
			'title' => '',
			'type' => 'hidden',
			'defaultValue' => $this->presetCountryId
		);
		foreach ($this->bankDetailFieldsInfoByCountry as $fieldName => $fieldInfo)
		{
			$bankDetailFieldList[] = array(
				'name' => $fieldName,
				'title' => $fieldInfo['title'],
				'type' => $fieldInfo['formType'],
				'required' => $fieldInfo['required'],
				'defaultValue' => ''
			);
			$bankDetailFieldsInView[] = $fieldName;
		}
		$bankDetailDataList = array();
		foreach ($this->bankDetailFieldsList as $bankDetailFields)
		{
			// force to set country id from preset
			$bankDetailFields['COUNTRY_ID'] = $this->presetCountryId;

			$bankDetailDataList[] = $bankDetailFields;
		}
		$bankDetailEditorId = $this->prepareFieldKey('BANK_DETAILS_EDITOR');
		$bankDetailEditorTitle = GetMessage('CRM_REQUISITE_BANK_DETAILS_EDITOR_FIELD');
		$this->arResult['FIELDS']['tab_1'][] = array(
			'id' => $bankDetailEditorId,
			'name' => $bankDetailEditorTitle,
			'type' => 'bank_details',
			'options' => array('nohover' => true),
			'componentParams' => array(
				'CONTAINER_ID' => $bankDetailsContainerId,
				'PRESET_COUNTRY_ID' => $this->presetCountryId,
				'FIELD_LIST' => $bankDetailFieldList,
				'DATA_LIST' => $bankDetailDataList,
				'FIELD_NAME_TEMPLATE' => $this->prepareFieldKey('BANK_DETAILS').'[#ELEMENT_ID#][#FIELD_NAME#]',
				'IS_LAST_IN_FORM' => $this->arResult['IS_LAST_IN_FORM']
			)
		);

		$this->arResult['PRESET_ID'] = $this->presetId;
		$this->arResult['ENTITY_ID'] = $this->entityId;
		$this->arResult['ENTITY_TYPE_ID'] = $this->entityTypeId;
		$this->arResult['ENTITY_TYPE_MNEMO'] = '';
		if ($this->entityTypeId === CCrmOwnerType::Company)
			$this->arResult['ENTITY_TYPE_MNEMO'] = 'COMPANY';
		else if ($this->entityTypeId === CCrmOwnerType::Contact)
			$this->arResult['ENTITY_TYPE_MNEMO'] = 'CONTACT';

		$this->arResult['DUPLICATE_CONTROL'] = array();
		$this->arResult['DUPLICATE_CONTROL']['ENABLED'] = $this->enableDupControl;
		if ($this->enableDupControl)
		{
			$countriesInfo = Bitrix\Crm\EntityPreset::getCountriesInfo();

			$this->arResult['DUPLICATE_CONTROL']['REQUISITE_DUP_FIELDS_MAP'] =
				EntityRequisite::getDuplicateCriterionFieldsMap();
			$this->arResult['DUPLICATE_CONTROL']['REQUISITE_DUP_FIELDS_DESCR'] =
				$this->requisite->getDuplicateCriterionFieldsDescriptions(false);
			$requisiteDupCountriesInfo = array();
			foreach (array_keys($this->arResult['DUPLICATE_CONTROL']['REQUISITE_DUP_FIELDS_MAP']) as $countryId)
				$requisiteDupCountriesInfo[$countryId] = $countriesInfo[$countryId];
			$this->arResult['DUPLICATE_CONTROL']['REQUISITE_DUP_COUNTRIES_INFO'] = $requisiteDupCountriesInfo;

			$this->arResult['DUPLICATE_CONTROL']['BANK_DETAIL_DUP_FIELDS_MAP'] =
				EntityBankDetail::getDuplicateCriterionFieldsMap();
			$this->arResult['DUPLICATE_CONTROL']['BANK_DETAIL_DUP_FIELDS_DESCR'] =
				$this->bankDetail->getDuplicateCriterionFieldsDescriptions(false);
			$bankDetailDupCountriesInfo = array();
			foreach (array_keys($this->arResult['DUPLICATE_CONTROL']['BANK_DETAIL_DUP_FIELDS_MAP']) as $countryId)
				$bankDetailDupCountriesInfo[$countryId] = $countriesInfo[$countryId];
			$this->arResult['DUPLICATE_CONTROL']['BANK_DETAIL_DUP_COUNTRIES_INFO'] = $bankDetailDupCountriesInfo;
		}

		// External requisite search handlers
		if ($this->isRestModuleIncluded)
		{
			$externalSearchHandlers = [];
			$placementCode = AppPlacement::REQUISITE_EDIT_FORM;
			$placementHandlerList = PlacementTable::getHandlersList($placementCode);
			foreach($placementHandlerList as $placementHandler)
			{
				$externalSearchHandlers[] = [
					'ID' => $placementHandler['ID'],
					'CODE' => $placementCode,
					'TITLE' => $placementHandler['TITLE'],
					'COMMENT' => $placementHandler['COMMENT'],
					'GROUP_NAME' => $placementHandler['GROUP_NAME'],
					'APP_ID' => $placementHandler['APP_ID'],
					'APP_NAME' => $placementHandler['APP_NAME']
				];
			}
			if (!empty($externalSearchHandlers))
			{
				$this->arResult['EXTERNAL_REQUISITE_SEARCH_CONFIG'] = [];
				$this->arResult['EXTERNAL_REQUISITE_SEARCH_CONFIG']['enabled'] = true;
				$this->arResult['EXTERNAL_REQUISITE_SEARCH_CONFIG']['entityTypeId'] = $this->arResult['ENTITY_TYPE_ID'];
				$this->arResult['EXTERNAL_REQUISITE_SEARCH_CONFIG']['presetId'] = $this->presetId;
				$this->arResult['EXTERNAL_REQUISITE_SEARCH_CONFIG']['countryId'] = $this->arResult['COUNTRY_ID'];
				$this->arResult['EXTERNAL_REQUISITE_SEARCH_CONFIG']['entityId'] = $this->arResult['ENTITY_ID'];
				$this->arResult['EXTERNAL_REQUISITE_SEARCH_CONFIG']['requisitePseudoId'] = $elementId;
				$this->arResult['EXTERNAL_REQUISITE_SEARCH_CONFIG']['formId'] = $this->arResult['FORM_ID'];
				$this->arResult['EXTERNAL_REQUISITE_SEARCH_CONFIG']['bankDetailAreaId'] = $bankDetailEditorId.'_area';
				$this->arResult['EXTERNAL_REQUISITE_SEARCH_CONFIG']['placementCode'] = $placementCode;
				$this->arResult['EXTERNAL_REQUISITE_SEARCH_CONFIG']['handlers'] = $externalSearchHandlers;
				$isAddressExist = false;
				$addressEditorTitle = '';
				$resultFieldMap = [];
				$fields = [];
				$fieldIdPrefix = "REQUISITE.{$elementId}.";
				if ($this->innerFormMode)
				{
					$nameFieldInfo = [
						'id' => $fieldIdPrefix.'NAME',
						'formId' => $this->prepareFieldKey('NAME'),
						'name' => $this->getOptionalFieldTitle('NAME'),
						'inputType' => 'text',
						'dataType' => 'string',
						'active' => false,
						'changeable' => true
					];
					$fields[] = $nameFieldInfo;
					$resultFieldMap[] = [
						'id' => 'NAME',
						'name' => $nameFieldInfo['name'],
						'inputType' => $nameFieldInfo['inputType'],
						'dataType' => $nameFieldInfo['dataType'],
						'fieldMap' => null
					];
					unset($nameFieldInfo);
				}
				$addressLabels = [];
				$fieldTypeMap = [];
				$ufMap = [];
				$field = null;
				$isUF = false;

				foreach ($fieldsInfo as $fieldName => $fieldInfo)
				{
					$fieldTypeMap[$fieldName] = $fieldInfo['type'];
					if ($fieldInfo['isUF'])
					{
						$ufMap[$fieldName] = true;
					}
				}
				unset($fieldsInfo, $fieldName, $fieldInfo);

				foreach ($this->arResult['FIELDS']['tab_1'] as $fieldInfo)
				{
					if ($fieldInfo['type'] !== 'multiple_address' && $fieldInfo['type'] !== 'bank_details')
					{
						$fieldId = $fieldInfo[$this->enableFieldMasquerading ? 'rawId' : 'id'];
						$field = [
							'id' => $fieldIdPrefix.$fieldId,
							'formId' => $fieldInfo['id'],
							'name' => $fieldInfo['name'],
							'inputType' => $fieldInfo['type'],
							'dataType' => isset($fieldTypeMap[$fieldId]) ? $fieldTypeMap[$fieldId] : 'string',
							'active' => false,
							'changeable' => false
						];

						$isUF = false;
						if ($fieldInfo['type'] === 'custom' && isset($ufMap[$fieldId]))
						{
							if (isset($fieldTypeMap[$fieldId]))
							{
								switch ($field['dataType'])
								{
									case 'boolean':
										$field['inputType'] = 'checkbox';
										break;
									case 'string':
									case 'datetime':
									case 'double':
										$field['inputType'] = 'text';
										break;
								}
							}
							$isUF = true;
						}

						if ($fieldInfo['type'] === 'text'
							|| ($isUF
								&& $field['dataType'] === 'string'
								|| $field['dataType'] === 'datetime'
								|| $field['dataType'] === 'double')
							|| $field['dataType'] === 'boolean')
						{
							$field['active'] = true;
						}

						if ($isUF
							|| ($fieldInfo['type'] !== 'section'
								&& $fieldInfo['type'] !== 'label'
								&& $fieldInfo['type'] !== 'custom'))
						{
							$field['changeable'] = true;
						}

						if ($field['active'] || $field['changeable'])
						{
							$fields[] = $field;
						}

						if ($field['changeable'])
						{
							$resultFieldMap[] = [
								'id' => $fieldId,
								'name' => $field['name'],
								'inputType' => $field['inputType'],
								'dataType' => $field['dataType'],
								'fieldMap' => null
							];
						}
					}
					else if ($fieldInfo['type'] === 'multiple_address'
						&& $fieldInfo[$this->enableFieldMasquerading ? 'rawId' : 'id'] === EntityRequisite::ADDRESS)
					{
						if (is_array($fieldInfo['componentParams']['DATA'])
							&& count($fieldInfo['componentParams']['DATA']) > 0
							&& isset($fieldInfo['componentParams']['FIELD_NAME_TEMPLATE'])
							&& is_array($fieldInfo['componentParams']['SCHEME']))
						{
							foreach (array_keys($fieldInfo['componentParams']['DATA']) as $addressTypeId)
							{
								if (!isset($addressLabels[$addressTypeId]))
								{
									$addressLabels[$addressTypeId] =
										Bitrix\Crm\RequisiteAddress::getLabels(/*$addressTypeId*/);
								}

								foreach ($fieldInfo['componentParams']['SCHEME'] as $addressFieldInfo)
								{
									$field = [
										'id' => $fieldIdPrefix.$fieldInfo[$this->enableFieldMasquerading ?
												'rawId' : 'id'].'.'.$addressTypeId.'.'.$addressFieldInfo['name'],
										'formId' => str_replace(
											['#TYPE_ID#', '#FIELD_NAME#'],
											[$addressTypeId, $addressFieldInfo['name']],
											$fieldInfo['componentParams']['FIELD_NAME_TEMPLATE']
										),
										'name' => (
										isset($addressLabels[$addressTypeId][$addressFieldInfo['name']]) ?
											$addressLabels[$addressTypeId][$addressFieldInfo['name']] :
											$addressFieldInfo['name']
										),
										'inputType' => ($addressFieldInfo['type'] === 'multilinetext') ?
											"textarea" : $addressFieldInfo['type'],
										'dataType' => 'string',
										'active' => false,
										'changeable' => false
									];

									if ($addressFieldInfo['type'] === 'text'
										|| $addressFieldInfo['type'] === 'multilinetext' )
									{
										$field['active'] = true;
									}

									if ($addressFieldInfo['type'] !== 'locality')
									{
										$field['changeable'] = true;
									}

									if ($field['active'] || $field['changeable'])
									{
										$fields[] = $field;
									}
								}
								unset($addressFieldInfo);
							}
							unset($addressTypeId);
						}

						if (!$isAddressExist)
						{
							$isAddressExist = true;
							$addressEditorTitle = $fieldInfo['name'];
						}
					}
					else if ($fieldInfo['type'] === 'bank_details'
						&& is_array($fieldInfo['componentParams']['DATA_LIST'])
						&& count($fieldInfo['componentParams']['DATA_LIST']) > 0
						&& isset($fieldInfo['componentParams']['FIELD_NAME_TEMPLATE'])
						&& is_array($fieldInfo['componentParams']['FIELD_LIST']))
					{
						foreach ($fieldInfo['componentParams']['DATA_LIST'] as $bankDetailData)
						{
							if (isset($bankDetailData['ID']))
							{
								$bankDetailId = $bankDetailData['ID'];
								foreach ($fieldInfo['componentParams']['FIELD_LIST'] as $bankDetailFieldInfo)
								{
									$field = [
										'id' => $fieldIdPrefix.'BANK_DETAILS.'.$bankDetailId.'.'.
											$bankDetailFieldInfo['name'],
										'formId' => str_replace(
											['#ELEMENT_ID#', '#FIELD_NAME#'],
											[$bankDetailId, $bankDetailFieldInfo['name']],
											$fieldInfo['componentParams']['FIELD_NAME_TEMPLATE']
										),
										'name' => $bankDetailFieldInfo['title'],
										'inputType' => $bankDetailFieldInfo['type'],
										'dataType' => 'string',
										'active' => false,
										'changeable' => false
									];

									if ($bankDetailFieldInfo['name'] !== 'NAME'
										&& ($bankDetailFieldInfo['type'] === 'text'
											|| $bankDetailFieldInfo['type'] === 'textarea' ))
									{
										$field['active'] = true;
									}

									if ($bankDetailFieldInfo['type'] !== 'hidden')
									{
										$field['changeable'] = true;
									}

									if ($field['active'] || $field['changeable'])
									{
										$fields[] = $field;
									}
								}
								unset($bankDetailFieldInfo, $bankDetailId);
							}
						}
						unset($bankDetailData);
					}
				}

				if ($isAddressExist)
				{
					$addressFieldMap = [];
					$addressFieldMap[] = [
						'id' => 'TYPE_ID',
						'name' => GetMessage('CRM_REQUISITE_ADDRESS_TYPE_TITLE'),
						'inputType' => 'none',
						'dataType' => 'integer'
					];
					foreach ($addressFieldScheme as $addressFieldInfo)
					{
						if ($addressFieldInfo['type'] === 'text'
							|| $addressFieldInfo['type'] === 'multilinetext' )
						{
							$addressFieldMap[] = [
								'id' => $addressFieldInfo['name'],
								'name' => $addressFieldInfo['name'],
								'inputType' => ($addressFieldInfo['type'] === 'multilinetext') ?
									"textarea" : $addressFieldInfo['type'],
								'dataType' => 'string'
							];
						}
					}
					$resultFieldMap[] = [
						'id' => "RQ_ADDR",
						'name' => $addressEditorTitle,
						'inputType' => 'none',
						'dataType' => 'multiple_address',
						'fieldMap' => $addressFieldMap
					];
					unset($addressFieldInfo, $addressFieldMap);
				}
				unset($isAddressExist, $addressEditorTitle);

				$bankDetailFieldMap = [];
				foreach ($bankDetailFieldList as $bankDetailFieldInfo)
				{
					if ($bankDetailFieldInfo['type'] !== 'hidden')
					{
						$bankDetailFieldMap[] = [
							'id' => $bankDetailFieldInfo['name'],
							'name' => $bankDetailFieldInfo['title'],
							'inputType' => $bankDetailFieldInfo['type'],
							'dataType' => 'string'
						];
					}
				}
				$resultFieldMap[] = [
					'id' => "BANK_DETAILS",
					'name' => $bankDetailEditorTitle,
					'inputType' => 'none',
					'dataType' => 'bank_details',
					'fieldMap' => $bankDetailFieldMap
				];
				unset($bankDetailEditorTitle, $bankDetailFieldMap, $bankDetailFieldInfo);

				$this->arResult['EXTERNAL_REQUISITE_SEARCH_CONFIG']['fieldMap'] = $resultFieldMap;
				$this->arResult['EXTERNAL_REQUISITE_SEARCH_CONFIG']['fields'] = $fields;
				unset($addressLabels, $fieldTypeMap, $ufMap, $fields, $field, $isUF);
			}
		}

		// Details search feautures
		$restrictionInn = RestrictionManager::getDetailsSearchByInnRestriction();
		$restrictionEdrpou = RestrictionManager::getDetailsSearchByEdrpouRestriction();
		$this->arResult['FEATURES'] = [
			'detailsSearchByInn' => ($restrictionInn->hasPermission() ? 'Y' : 'N'),
			'detailsSearchByEdrpou' => ($restrictionEdrpou->hasPermission() ? 'Y' : 'N'),
			'detailsSearchByInnInfoScript' => $restrictionInn->prepareInfoHelperScript(),
			'detailsSearchByEdrpouInfoScript' => $restrictionEdrpou->prepareInfoHelperScript()
		];
		unset($restrictionInn, $restrictionEdrpou);
	}

	protected function checkModules()
	{
		if (!CModule::IncludeModule('crm'))
		{
			$this->errors[] = GetMessage('CRM_MODULE_NOT_INSTALLED');
			return false;
		}

		$this->isRestModuleIncluded = (bool)Bitrix\Main\Loader::includeModule('rest');

		return true;
	}

	protected function hasErrors()
	{
		return (count($this->errors) > 0);
	}

	protected function showErrors()
	{
		if (count($this->errors) > 0)
			foreach ($this->errors as $errMsg)
				ShowError($errMsg);
	}

	protected function getApp()
	{
		global $APPLICATION;

		return $APPLICATION;
	}

	public function getComponentId()
	{
		return $this->componentId;
	}

	public function getComponentResult()
	{
		return array('ENTITY_TYPE_ID' => $this->entityTypeId, 'ENTITY_ID' => $this->entityId, 'REFERER_URL' => $this->refererUrl);
	}
}
