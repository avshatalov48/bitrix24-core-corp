<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\Integration\ClientResolver;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Security\Sign\Signer;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type;
use Bitrix\Main\UserField;
use Bitrix\Main\Web\Json;
use Bitrix\Crm\EntityAddress;
use Bitrix\Crm\EntityAddressType;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\EntityPreset;
use Bitrix\Crm\EntityBankDetail;
use Bitrix\Crm\RequisiteAddress;
use Bitrix\Crm\Restriction\RestrictionManager;
use Bitrix\Location\Entity\Address;

Loc::loadMessages(__FILE__);

class CCrmRequisiteDetailsComponent extends CBitrixComponent
{
	/** @var ErrorCollection */
	protected $errors;

	/** @var bool */
	protected $isRestModuleIncluded = false;
	/** @var bool */
	protected $isLocationModuleIncluded = false;
	/** @var bool */
	protected $enableDupControl = false;

	/** @var string */
	protected $mode = '';
	/** @var bool */
	protected $doSave = false;
	/** @var bool */
	protected $doSaveContext = false;
	/** @var bool */
	protected $useExternalData = false;
	/** @var bool */
	protected $useFormData = false;
	/** @var bool */
	protected $isAddressOnly = false;
	/** @var array */
	protected $formData = [];

	/** @var int */
	protected $entityTypeId = 0;
	/** @var int */
	protected $categoryId = 0;
	/** @var int */
	protected $entityId = 0;

	/** @var int */
	protected $presetId = 0;
	/** @var int */
	protected $prevPresetId = 0;
	/** @var int @var string */
	protected $prevPresetName = '';
	/** @var int */
	protected $presetCountryId = 0;
	/** @var int */
	protected $prevPresetCountryId = 0;
	/** @var array */
	protected $presetFields = [];
	/** @var array */
	protected $prevPresetFields = [];
	/** @var array */
	protected $presetFieldTitles = [];

	/** @var EntityRequisite|null */
	protected $requisite = null;
	/** @var int */
	protected $requisiteId = 0;
	/** @var string */
	protected $pseudoId = '';
	/** @var array|null */
	protected $rawRequisiteData = null;
	/** @var array|null */
	protected $requisiteData = null;
	/** @var array @var array|null */
	protected $requisiteFieldTitles = [];

	/** @var UserField\Dispatcher */
	protected $userFieldDispatcher;


	/** @var EntityPreset|null */
	protected $preset = null;
	/** @var array|null */
	protected $rawPresetData = null;

	/** @var EntityBankDetail|null */
	protected $bankDetail = null;
	/** @var array */
	protected $rawBankDetailList = [];
	/** @var array|null */
	protected $bankDetailFieldsInfo = null;
	/** @var array */
	protected $deletedBankDetailMap = [];

	/** @var bool */
	protected $isCreateMode = false;
	/** @var bool */
	protected $isEditMode = false;
	/** @var bool */
	protected $isCopyMode = false;
	/** @var bool */
	protected $isDeleteMode = false;

	/** @var bool */
	protected $isSave = false;
	/** @var bool */
	protected $isReload = false;

	/** @var bool */
	protected $isPresetChange = false;
	/** @var array */
	protected $presetChangeData = [];

	/** @var bool */
	protected $isReadOnly = false;

	/** @var bool */
	protected $isJsonResponse;

	/** @var string */
	protected $externalContextId = '';

	/** @var array */
	protected $fieldsAllowed = [];

	/** @var array|null */
	protected $formFieldsInfo = null;

	/** @var string */
	protected $formSettingsId = '';

	protected string $permissionToken = '';

	public function __construct($component = null)
	{
		parent::__construct($component);

		$this->errors = new ErrorCollection();
		$this->requisite = EntityRequisite::getSingleInstance();
		$this->userFieldDispatcher = UserField\Dispatcher::instance();
		$this->preset = EntityPreset::getSingleInstance();
		$this->bankDetail = EntityBankDetail::getSingleInstance();
	}

	protected function checkModules()
	{
		if (!CModule::IncludeModule('crm'))
		{
			$this->errors[] = new Error(Loc::getMessage('CRM_MODULE_NOT_INSTALLED'));

			return false;
		}

		$this->isRestModuleIncluded = (bool)Loader::includeModule('rest');
		$this->isLocationModuleIncluded = RequisiteAddress::isLocationModuleIncluded();

		return true;
	}

	protected function getApp()
	{
		global $APPLICATION;

		return $APPLICATION;
	}

	/** @return bool */
	public function hasErrors()
	{
		return !$this->errors->isEmpty();
	}

	/**
	 * Getting array of errors.
	 * @return Error[]
	 */
	public function getErrors()
	{
		return $this->errors->toArray();
	}

	protected  function getErrorsAsHtml()
	{
		$result = '';

		/** @var $error Error */
		foreach ($this->errors as $error)
		{
			$result .= $error->getMessage() . '<br>';
		}

		return $result;
	}

	protected function initialize()
	{
		$this->arParams['PATH_TO_CONTACT_SHOW'] = CrmCheckPath(
			'PATH_TO_CONTACT_SHOW',
			$this->arParams['PATH_TO_CONTACT_SHOW'] ?? '',
			$this->getApp()->GetCurPage().'?contact_id=#contact_id#&show'
		);
		$this->arParams['PATH_TO_COMPANY_SHOW'] = CrmCheckPath(
			'PATH_TO_COMPANY_SHOW',
			$this->arParams['PATH_TO_COMPANY_SHOW'] ?? '',
			$this->getApp()->GetCurPage().'?company_id=#company_id#&show'
		);

		//region Check base params
		if (
			isset($this->arParams['~MODE'])
			&& in_array($this->arParams['~MODE'], ['create', 'edit', 'copy', 'delete'], true)
		)
		{
			$this->mode = $this->arParams['~MODE'];
			switch ($this->mode)
			{
				case 'create':
					$this->isCreateMode = true;
					break;
				case 'edit':
					$this->isEditMode = true;
					break;
				case 'copy':
					$this->isCopyMode = true;
					break;
				case 'delete':
					$this->isDeleteMode = true;
					break;
			}
		}
		else
		{
			$this->errors[] = new Error(Loc::getMessage('CRM_REQUISITE_DETAILS_ERR_MODE_NOT_DEFINED'));
			return false;
		}
		$this->doSaveContext = (isset($this->arParams['~DO_SAVE']) && $this->arParams['~DO_SAVE'] === 'Y');
		$this->isSave = (isset($this->arParams['~IS_SAVE']) && $this->arParams['~IS_SAVE'] === 'Y');
		$this->isReload = (isset($this->arParams['~IS_RELOAD']) && $this->arParams['~IS_RELOAD'] === 'Y');
		$this->isJsonResponse = ($this->isSave || $this->mode === 'delete');
		$this->useExternalData = (
			isset($this->arParams['~USE_EXTERNAL_DATA']) && $this->arParams['~USE_EXTERNAL_DATA'] === 'Y'
		);
		if ($this->useExternalData)
		{
			if (!(isset($this->arParams['~EXTERNAL_DATA']) && is_array($this->arParams['~EXTERNAL_DATA'])))
			{
				$this->errors[] = new Error(Loc::getMessage('CRM_REQUISITE_DETAILS_ERR_EXTERNAL_DATA_NOT_DEFINED'));
				return false;
			}
		}
		$this->useFormData = (isset($this->arParams['~USE_FORM_DATA']) && $this->arParams['~USE_FORM_DATA'] === 'Y');
		if ($this->useFormData)
		{
			if (isset($this->arParams['~FORM_DATA']) && is_array($this->arParams['~FORM_DATA']))
			{
				$this->formData = $this->arParams['~FORM_DATA'];
			}
			else
			{
				$this->errors[] = new Error(Loc::getMessage('CRM_REQUISITE_DETAILS_ERR_INVALID_FORM_DATA'));
				return false;
			}
		}
		$this->permissionToken = $this->arParams['PERMISSION_TOKEN'] ?? '';

		$this->doSave = ($this->doSaveContext && $this->isSave);
		//endregion Check base params

		if (($this->isEditMode || $this->isCopyMode || $this->isDeleteMode)
			&& isset($this->arParams['~REQUISITE_ID'])
			&& $this->arParams['~REQUISITE_ID'] > 0)
		{
			$this->requisiteId = (int)$this->arParams['~REQUISITE_ID'];
		}

		if ($this->requisiteId > 0)
		{
			if (!$this->useExternalData || $this->isDeleteMode)
			{
				// Load requisites
				$this->rawRequisiteData = $this->requisite->getList(
					[
						'filter' => ['=ID' => $this->requisiteId],
						'select' => ['*', 'UF_*'],
						'limit' => 1,
					]
				)->fetch();

				if (!is_array($this->rawRequisiteData))
				{
					$this->errors[] = new Error(
						Loc::getMessage('CRM_REQUISITE_DETAILS_ERR_NOT_FOUND', ['#ID#' => $this->requisiteId])
					);
					return false;
				}

				$isValidEntityIdentification = (
					isset($this->rawRequisiteData['ENTITY_TYPE_ID'])
					&& (
						(int)$this->rawRequisiteData['ENTITY_TYPE_ID'] === CCrmOwnerType::Company
						|| (int)$this->rawRequisiteData['ENTITY_TYPE_ID'] === CCrmOwnerType::Contact
					)
					&& isset($this->rawRequisiteData['ENTITY_ID'])
					&& $this->rawRequisiteData['ENTITY_ID'] > 0
				);

				$hasRights =
					$isValidEntityIdentification
					&& $this->checkReadPermissions($this->rawRequisiteData['ENTITY_TYPE_ID'], $this->rawRequisiteData['ENTITY_ID'])
				;

				// Check permissions
				if (!$hasRights)
				{
					$this->rawRequisiteData = null;
					$this->errors[] = new Error(
						Loc::getMessage(
							'CRM_REQUISITE_DETAILS_ERR_REQUISITE_READ_PERMISSIONS',
							['#ID#' => $this->requisiteId]
						)
					);
					return false;
				}

				if (!$this->isDeleteMode)
				{
					// addresses
					if ($this->isLocationModuleIncluded)
					{
						$this->rawRequisiteData[EntityRequisite::ADDRESS] =
							EntityRequisite::getAddresses($this->requisiteId);
						if (!empty($this->rawRequisiteData[EntityRequisite::ADDRESS]))
						{
							foreach (
								$this->rawRequisiteData[EntityRequisite::ADDRESS]
								as $addressTypeId => $addressFields
							)
							{
								$locationAddress = RequisiteAddress::makeLocationAddressByFields($addressFields);
								if ($locationAddress)
								{
									$this->rawRequisiteData[EntityRequisite::ADDRESS][$addressTypeId]['LOC_ADDR'] =
										$locationAddress;
								}
								unset($locationAddress);
							}
							unset($addressTypeId, $addressFields);
						}
					}

					// bank details
					$select = array_merge(
						array('ID', 'ENTITY_TYPE_ID', 'ENTITY_ID', 'COUNTRY_ID', 'NAME'),
						$this->bankDetail->getRqFields(),
						array('COMMENTS')
					);
					$res = $this->bankDetail->getList(
						array(
							'order' => ['SORT', 'ID'],
							'filter' => [
								'=ENTITY_TYPE_ID' => CCrmOwnerType::Requisite,
								'=ENTITY_ID' => $this->requisiteId
							],
							'select' => $select
						)
					);
					while ($row = $res->fetch())
					{
						$this->rawBankDetailList[$row['ID']] = $row;
					}
					unset($select, $res, $row);
				}
			}
		}
		else
		{
			if (isset($this->arParams['~PSEUDO_ID'], $_REQUEST['pseudoId'])
				&& is_string($_REQUEST['pseudoId'])
				&& preg_match('/^n\d+$/', $_REQUEST['pseudoId']))
			{
				$this->pseudoId = $this->arParams['~PSEUDO_ID'];
			}
			else
			{
				$this->pseudoId = 'n0';
			}
		}

		// entity type id
		if (isset($this->arParams['~ENTITY_TYPE_ID']) && !$this->isDeleteMode)
		{
			$this->entityTypeId = (int)$this->arParams['~ENTITY_TYPE_ID'];
		}
		else if (($this->isEditMode || $this->isCopyMode || $this->isDeleteMode)
			&& is_array($this->rawRequisiteData) && isset($this->rawRequisiteData['ENTITY_TYPE_ID']))
		{
			$this->entityTypeId = (int)$this->rawRequisiteData['ENTITY_TYPE_ID'];
		}
		if ($this->entityTypeId <= 0)
		{
			$this->errors[] = new Error(Loc::getMessage('CRM_REQUISITE_DETAILS_ERR_INVALID_ENTITY_TYPE_ID'));
			return false;
		}
		if ($this->entityTypeId !== CCrmOwnerType::Company && $this->entityTypeId !== CCrmOwnerType::Contact)
		{
			$this->errors[] = new Error(Loc::getMessage('CRM_REQUISITE_DETAILS_ERR_ENTITY_TYPE_ID'));
		}

		// entity id
		if (isset($this->arParams['~ENTITY_ID']) && !$this->isDeleteMode)
		{
			$this->entityId = (int)$this->arParams['~ENTITY_ID'];
		}
		else if (($this->isEditMode || $this->isCopyMode || $this->isDeleteMode)
			&& is_array($this->rawRequisiteData) && isset($this->rawRequisiteData['ENTITY_ID']))
		{
			$this->entityId = (int)$this->rawRequisiteData['ENTITY_ID'];
		}
		if ($this->doSave && $this->entityId <= 0)
		{
			$this->errors[] = new Error(Loc::getMessage('CRM_REQUISITE_DETAILS_ERR_INVALID_ENTITY_ID_TO_SAVE'));
			return false;
		}

		// category id
		if (CCrmOwnerType::IsDefined($this->entityTypeId))
		{
			if ($this->entityId > 0)
			{
				$this->categoryId = $this->getEntityCategoryId($this->entityTypeId, $this->entityId);
			}
			elseif (isset($this->arParams['~CATEGORY_ID']) && $this->arParams['~CATEGORY_ID'] > 0)
			{
				$this->categoryId = (int)$this->arParams['~CATEGORY_ID'];
			}
		}

		// Check read permissions by entity
		if (
			($this->isEditMode || $this->isCopyMode || $this->isDeleteMode)
			&& !$this->checkReadPermissions($this->entityTypeId, $this->entityId, $this->categoryId)
		)
		{
			$this->errors[] = new Error(
				Loc::getMessage(
					'CRM_REQUISITE_DETAILS_ERR_'.CCrmOwnerType::ResolveName($this->entityTypeId).
					'_REQUISITE_READ_DENIED',
					['#ID#' => $this->entityId]
				)
			);
			return false;
		}

		// Detect read-only mode and check write permissions
		if (!$this->checkEditPermissions($this->entityTypeId, $this->entityId, $this->categoryId))
		{
			$this->isReadOnly = true;

			if ($this->doSave || $this->isSave)
			{
				$this->errors[] = new Error(
					Loc::getMessage(
						'CRM_REQUISITE_DETAILS_ERR_'.CCrmOwnerType::ResolveName($this->entityTypeId).
						'_REQUISITE_WRITE_DENIED',
						['#ID#' => $this->entityId]
					)
				);
				return false;
			}
		}

		if ($this->isDeleteMode)
		{
			return true;
		}

		//region External data
		if ($this->useExternalData)
		{
			$isExternalDataSuccess = false;
			if (isset($this->arParams['~EXTERNAL_DATA']['data'])
				&& isset($this->arParams['~EXTERNAL_DATA']['sign'])
				&& is_string($this->arParams['~EXTERNAL_DATA']['data'])
				&& is_string($this->arParams['~EXTERNAL_DATA']['sign'])
				&& $this->arParams['~EXTERNAL_DATA']['data'] !== ''
				&& $this->arParams['~EXTERNAL_DATA']['sign'] !== '')
			{
				$externalData = $this->arParams['~EXTERNAL_DATA']['data'];
				$externalDataSign = $this->arParams['~EXTERNAL_DATA']['sign'];
				$signer = new Signer();
				$jsonData = null;
				if($signer->validate(
					$externalData,
					$externalDataSign,
					'crm.requisite.edit-'.$this->entityTypeId))
				{
					$jsonData = $externalData;
				}
				unset($externalData, $externalDataSign);
				if ($jsonData)
				{
					try
					{
						$externalData = Json::decode($jsonData);
						if (is_array($externalData)
							&& is_array($externalData['fields']))
						{
							if (is_array($externalData['bankDetailFieldsList']))
							{
								$this->rawBankDetailList = $externalData['bankDetailFieldsList'];
							}
							$this->rawRequisiteData = $externalData['fields'];
							EntityRequisite::internalizeAddresses($this->rawRequisiteData);

							if (is_array($externalData['deletedBankDetailList']))
							{
								$this->deletedBankDetailMap = array_fill_keys(
									$externalData['deletedBankDetailList'],
									true
								);
							}

							if (is_array($externalData['presetChangeData']))
							{
								$this->presetChangeData = $externalData['presetChangeData'];
							}

							$isExternalDataSuccess = true;
						}
						unset($externalData);
					}
					catch (SystemException $e)
					{
					}
				}
			}
			if (!$isExternalDataSuccess)
			{
				$this->errors[] = new Error(Loc::getMessage('CRM_REQUISITE_DETAILS_ERR_INVALID_EXTERNAL_DATA'));
				return false;
			}
			unset($isExternalDataSuccess);
		}
		//endregion External data

		//region Preset id
		if ($this->useFormData
			&& isset($this->formData['PRESET_ID'])
			&& $this->formData['PRESET_ID'] > 0)
		{
			$this->presetId = (int)$this->formData['PRESET_ID'];
		}
		else if (isset($this->arParams['~PRESET_ID']) && $this->arParams['~PRESET_ID'] > 0)
		{
			$this->presetId = (int)$this->arParams['~PRESET_ID'];
		}
		else if (is_array($this->rawRequisiteData)
			&& isset($this->rawRequisiteData['PRESET_ID'])
			&& $this->rawRequisiteData['PRESET_ID'] > 0)
		{
			$this->presetId = (int)$this->rawRequisiteData['PRESET_ID'];
		}
		//endregion Preset id

		//region Preset dependent info
		if ($this->presetId <= 0)
		{
			$this->errors[] = new Error(Loc::getMessage('CRM_REQUISITE_DETAILS_ERR_PRESET_ID_NOT_DEFINED'));
			return false;
		}
		$rawPresetData = $this->preset->getById($this->presetId);
		if (!is_array($rawPresetData))
		{
			$this->errors[] = new Error(Loc::getMessage('CRM_REQUISITE_DETAILS_ERR_PRESET_NOT_FOUND'));
			return false;
		}
		if (isset($rawPresetData['COUNTRY_ID']))
		{
			$this->presetCountryId = (int)$rawPresetData['COUNTRY_ID'];
		}
		if ($this->presetCountryId <= 0)
		{
			$this->errors[] = new Error(Loc::getMessage('CRM_REQUISITE_DETAILS_ERR_PRESET_COUNTRY_ID_IS_NOT_DEFINED'));
			return false;
		}
		$this->rawPresetData = $rawPresetData;
		unset($rawPresetData);
		if (is_array($this->rawPresetData['SETTINGS']))
		{
			$presetFieldsInfo = $this->preset->settingsGetFields($this->rawPresetData['SETTINGS']);
			foreach ($presetFieldsInfo as $fieldInfo)
			{
				if (isset($fieldInfo['FIELD_NAME']))
				{
					$this->presetFieldTitles[$fieldInfo['FIELD_NAME']] =
						(isset($fieldInfo['FIELD_TITLE'])) ? (string)$fieldInfo['FIELD_TITLE'] : "";

					$presetFieldsSort['ID'][] = isset($fieldInfo['ID']) ? (int)$fieldInfo['ID'] : 0;
					$presetFieldsSort['SORT'][] = isset($fieldInfo['SORT']) ? (int)$fieldInfo['SORT'] : 0;
					$presetFieldsSort['FIELD_NAME'][] = $fieldInfo['FIELD_NAME'];
				}
			}
			unset($presetFieldsInfo, $fieldInfo);

			if (!empty($presetFieldsSort['FIELD_NAME']))
			{
				if(array_multisort(
					$presetFieldsSort['SORT'], SORT_ASC, SORT_NUMERIC,
					$presetFieldsSort['ID'], SORT_ASC, SORT_NUMERIC,
					$presetFieldsSort['FIELD_NAME']))
				{
					$this->presetFields = array_values($presetFieldsSort['FIELD_NAME']);
				}
			}
			unset($presetFieldsSort);
		}
		$this->formSettingsId = "CRM_REQUISITE_DETAILS_0_PID{$this->presetId}";
		//endregion Preset dependent info

		//region Preset change
		if ($this->isReload
			&& $this->useFormData
			&& isset($this->formData['PREV_PRESET_ID'])
			&& $this->formData['PREV_PRESET_ID'] > 0)
		{
			$prevPresetId = (int)$this->formData['PREV_PRESET_ID'];
			if ($prevPresetId !== $this->prevPresetId)
			{
				$this->prevPresetId = $prevPresetId;
				$this->isPresetChange = true;
			}
			unset($prevPresetId);
		}
		//endregion Preset change

		//region Previous preset dependent info
		if ($this->isPresetChange)
		{
			$prevPresetData = $this->preset->getById($this->prevPresetId);
			if (!is_array($prevPresetData))
			{
				$this->errors[] = new Error(Loc::getMessage('CRM_REQUISITE_DETAILS_ERR_PREV_PRESET_NOT_FOUND'));
				return false;
			}
			if (
				isset($prevPresetData['NAME'])
				&& is_string($prevPresetData['NAME'])
				&& $prevPresetData['NAME'] !== ''
			)
			{
				$this->prevPresetName = $prevPresetData['NAME'];
			}
			if (isset($prevPresetData['COUNTRY_ID']))
			{
				$this->prevPresetCountryId = (int)$prevPresetData['COUNTRY_ID'];
			}
			if ($this->prevPresetCountryId <= 0)
			{
				$this->errors[] = new Error(
					Loc::getMessage('CRM_REQUISITE_DETAILS_ERR_PREV_PRESET_COUNTRY_ID_IS_NOT_DEFINED')
				);
				return false;
			}
			if (is_array($prevPresetData['SETTINGS']))
			{
				$prevPresetFieldsInfo = $this->preset->settingsGetFields($prevPresetData['SETTINGS']);
				foreach ($prevPresetFieldsInfo as $fieldInfo)
				{
					if (isset($fieldInfo['FIELD_NAME']))
					{
						$presetFieldsSort['ID'][] = isset($fieldInfo['ID']) ? (int)$fieldInfo['ID'] : 0;
						$presetFieldsSort['SORT'][] = isset($fieldInfo['SORT']) ? (int)$fieldInfo['SORT'] : 0;
						$presetFieldsSort['FIELD_NAME'][] = $fieldInfo['FIELD_NAME'];
					}
				}
				unset($prevPresetFieldsInfo, $fieldInfo);

				if (!empty($presetFieldsSort['FIELD_NAME']))
				{
					if(array_multisort(
						$presetFieldsSort['SORT'], SORT_ASC, SORT_NUMERIC,
						$presetFieldsSort['ID'], SORT_ASC, SORT_NUMERIC,
						$presetFieldsSort['FIELD_NAME']))
					{
						$this->prevPresetFields = array_values($presetFieldsSort['FIELD_NAME']);
					}
				}
				unset($presetFieldsSort);
			}
			unset($prevPresetData);
		}
		//endregion Previous preset dependent info

		//region ADDRESS_ONLY
		if ($this->useFormData
			&& isset($this->formData['ADDRESS_ONLY'])
			&& in_array($this->formData['ADDRESS_ONLY'], ['Y', 'N']))
		{
			$this->isAddressOnly = ($this->formData['ADDRESS_ONLY'] == 'Y');
		}
		else if (is_array($this->rawRequisiteData)
			&& isset($this->rawRequisiteData['ADDRESS_ONLY'])
			&& in_array($this->rawRequisiteData['ADDRESS_ONLY'], ['Y', 'N']))
		{
			$this->isAddressOnly = ($this->rawRequisiteData['ADDRESS_ONLY'] == 'Y');
		}
		//endregion ADDRESS_ONLY

		//region Prepare requisite data
		$curDateTime = new \Bitrix\Main\Type\DateTime();
		$curUserId = CCrmSecurityHelper::GetCurrentUserID();
		if (is_array($this->rawRequisiteData))
		{
			$this->rawRequisiteData['ENTITY_TYPE_ID'] = $this->entityTypeId;
			$this->rawRequisiteData['ENTITY_ID'] = $this->entityId;
			$this->rawRequisiteData['ID'] = $this->requisiteId;
			$this->rawRequisiteData['PRESET_ID'] = $this->presetId;

			if ($this->isCopyMode)
			{
				$this->rawRequisiteData['ID'] = 0;
				$this->rawRequisiteData['DATE_CREATE'] = $curDateTime;
				$this->rawRequisiteData['DATE_MODIFY'] = $curDateTime;
				$this->rawRequisiteData['CREATED_BY_ID'] = $curUserId;
				$this->rawRequisiteData['MODIFY_BY_ID'] = $curUserId;

				foreach ($this->requisite->getFileFields() as $fileFieldName)
				{ // file fields can't be copied
					unset($this->rawRequisiteData[$fileFieldName]);
				}
			}

			// bank details
			foreach ($this->rawBankDetailList as &$bankDetailData)
			{
				$bankDetailData['ENTITY_TYPE_ID'] = CCrmOwnerType::Requisite;
				$bankDetailData['ENTITY_ID'] = $this->requisiteId;
				$bankDetailData['COUNTRY_ID'] = $this->presetCountryId;

				if ($this->isCopyMode)
				{
					$bankDetailData['ID'] = 0;
					$bankDetailData['DATE_CREATE'] = $curDateTime;
					$bankDetailData['DATE_MODIFY'] = $curDateTime;
					$bankDetailData['CREATED_BY_ID'] = $curUserId;
					$bankDetailData['MODIFY_BY_ID'] = $curUserId;
				}
			}
			unset($bankDetailData);
		}
		else
		{
			$this->rawRequisiteData = [
				'ID' => 0,
				'ENTITY_TYPE_ID' => $this->entityTypeId,
				'ENTITY_ID' => $this->entityId,
				'PRESET_ID' => $this->presetId,
				'DATE_CREATE' => $curDateTime,
				'CREATED_BY_ID' => $curUserId,
				'NAME' => '',
				'ACTIVE' => 'Y',
				'ADDRESS_ONLY' => $this->isAddressOnly ? 'Y' : 'N',
				'SORT' => 500
			];

			if(is_array($this->rawPresetData) && isset($this->rawPresetData['NAME']))
			{
				$this->rawRequisiteData['NAME'] = $this->rawPresetData['NAME'];
			}
			foreach ($this->requisite->getRqFields() as $rqFieldName)
			{
				if($rqFieldName === EntityRequisite::ADDRESS)
				{
					if ($this->isLocationModuleIncluded)
					{
						$this->rawRequisiteData[$rqFieldName] = [];
					}
				}
				else
				{
					$this->rawRequisiteData[$rqFieldName] = '';
				}
			}
		}
		unset($curDateTime, $curUserId);
		//endregion Prepare requisite data

		$this->fieldsAllowed = array_fill_keys(
			array_merge(
				$this->requisite->getRqFields(),
				$this->requisite->getUserFields()
			),
			true
		);

		$this->requisiteFieldTitles = $this->requisite->getFieldsTitles($this->presetCountryId);

		$this->externalContextId = $this->arParams['~EXTERNAL_CONTEXT_ID'] ?? '';

		$this->enableDupControl = (
			!$this->isReadOnly
			&& Bitrix\Crm\Integrity\DuplicateControl::isControlEnabledFor($this->entityTypeId)
		);

		return true;
	}

	protected function applyPresetChangeData()
	{
		foreach ($this->presetFields as $fieldName)
		{
			if (isset($this->presetChangeData[$fieldName]))
			{
				$this->rawRequisiteData[$fieldName] = $this->presetChangeData[$fieldName];
				unset($this->presetChangeData[$fieldName]);
			}
		}

		if (is_array($this->presetChangeData['BANK_DETAILS']))
		{
			$bankDetailFields = null;
			foreach ($this->presetChangeData['BANK_DETAILS'] as $pseudoId => $bankDetailData)
			{
				if ($bankDetailFields === null)
				{
					$bankDetailFields = array_keys($this->getBankDetailFieldsInfo());
				}
				$checkEmpty = false;
				foreach ($bankDetailFields as $fieldName)
				{
					if (isset($bankDetailData[$fieldName])
						&& is_array($this->rawBankDetailList[$pseudoId]))
					{
						$this->rawBankDetailList[$pseudoId][$fieldName] = $bankDetailData[$fieldName];
						unset($this->presetChangeData['BANK_DETAILS'][$pseudoId][$fieldName]);
						$checkEmpty = true;
					}
				}
				if ($checkEmpty && empty($this->presetChangeData['BANK_DETAILS'][$pseudoId]))
				{
					unset($this->presetChangeData['BANK_DETAILS'][$pseudoId]);
				}
			}
			if (empty($this->presetChangeData['BANK_DETAILS']))
			{
				unset($this->presetChangeData['BANK_DETAILS']);
			}
		}
	}

	protected function applyFormData()
	{
		/** @var CUserTypeManager */
		global $USER_FIELD_MANAGER;

		if (isset($this->formData['NAME']))
		{
			$requisiteName = trim(strval($this->formData['NAME']));
			if (
				$this->isPresetChange
				&& isset($this->rawRequisiteData['NAME'])
				&& is_string($this->rawRequisiteData['NAME'])
				&& $this->rawRequisiteData['NAME'] !== ''
				&& $requisiteName === $this->prevPresetName
			)
			{
				$requisiteName = $this->rawPresetData['NAME'];
			}
			$this->rawRequisiteData['NAME'] = $requisiteName;
		}

		if (isset($this->formData['PRESET_ID']))
		{
			$this->rawRequisiteData['PRESET_ID'] = (int) $this->formData['PRESET_ID'];
		}

		if (isset($this->formData['CODE']))
		{
			$this->rawRequisiteData['CODE'] = trim(strval($this->formData['CODE']));
		}

		if (isset($this->formData['XML_ID']))
		{
			$this->rawRequisiteData['XML_ID'] = trim(strval($this->formData['XML_ID']));
		}

		if (isset($this->formData['ACTIVE']))
		{
			$this->rawRequisiteData['ACTIVE'] = ($this->formData['ACTIVE'] === 'Y');
		}

		if (isset($this->formData['SORT']))
		{
			$this->rawRequisiteData['SORT'] = (int) $this->formData['SORT'];
		}
		if (isset($this->formData['ADDRESS_ONLY']))
		{
			$this->rawRequisiteData['ADDRESS_ONLY'] = ($this->formData['ADDRESS_ONLY'] === 'Y') ? 'Y' : 'N';
		}

		// RQ fields
		$rqFieldNames = $this->requisite->getRqFields();
		$fileFields = $this->requisite->getFileFields();
		foreach ($rqFieldNames as $rqFieldName)
		{
			if (isset($this->formData[$rqFieldName]))
			{
				if (in_array($rqFieldName, $fileFields))
				{
					$fileFieldValue = $this->formData[$rqFieldName];
					if ($fileFieldValue)
					{
						$allowedFileIds = \Bitrix\Main\UI\FileInputUtility::instance()->checkFiles(
							mb_strtolower($rqFieldName) . '_uploader', [$fileFieldValue]
						);
						if (in_array($fileFieldValue, $allowedFileIds))
						{
							$this->rawRequisiteData[$rqFieldName] = $fileFieldValue;
						}
					}
					if ($this->formData[$rqFieldName . '_del'])
					{
						$this->rawRequisiteData[$rqFieldName] = null;
					}
				}
				elseif($rqFieldName === EntityRequisite::ADDRESS)
				{
					if ($this->isLocationModuleIncluded)
					{
						$this->rawRequisiteData[$rqFieldName] = [];
						if (is_array($this->formData[$rqFieldName]))
						{
							$allowedRqAddrTypeMap = array_fill_keys(EntityAddressType::getAllIDs(), true);
							foreach ($this->formData[$rqFieldName] as $addressTypeId => $addressJson)
							{
								$addressTypeId = (int)$addressTypeId;
								$locationAddress = null;
								if (isset($allowedRqAddrTypeMap[$addressTypeId]))
								{
									if (is_array($addressJson)
										&& isset($addressJson['DELETED'])
										&& $addressJson['DELETED'] === 'Y')
									{
										$this->rawRequisiteData[$rqFieldName][$addressTypeId] = ['DELETED' => 'Y'];
									}
									else if (is_string($addressJson) && $addressJson !== '')
									{
										$locationAddress = Address::fromJson(
											EntityAddress::prepareJsonValue($addressJson)
										);
										if ($locationAddress)
										{
											$this->rawRequisiteData[$rqFieldName][$addressTypeId] = [
												'LOC_ADDR' => $locationAddress
											];
										}
									}
								}
							}
							unset($allowedRqAddrTypeMap, $addressTypeId, $addressJson);
						}
					}
				}
				else
				{
					$this->rawRequisiteData[$rqFieldName] = trim($this->formData[$rqFieldName]);
				}
			}
		}

		$USER_FIELD_MANAGER->EditFormAddFields(
			$this->requisite->getUfId(),
			$this->rawRequisiteData,
			['FORM' => $this->formData]
		);

		// bank details
		if (isset($this->formData['BANK_DETAILS']) && is_array($this->formData['BANK_DETAILS']))
		{
			foreach ($this->formData['BANK_DETAILS'] as $pseudoId => $formFields)
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
						if (
							$fieldName === 'ENTITY_TYPE_ID'
							|| $fieldName === 'ENTITY_ID'
							|| $fieldName === 'COUNTRY_ID'
						)
						{
							$fields[$fieldName] = (int)$formFields[$fieldName];
						}
						else
						{
							$fields[$fieldName] = trim(strval($formFields[$fieldName]));
						}
					}

					if (isset($formFields['DELETED']) && $formFields['DELETED'] === 'Y')
					{
						$this->deletedBankDetailMap[$pseudoId] = true;
					}
				}

				if (!is_array($this->rawBankDetailList[$pseudoId]))
				{
					$this->rawBankDetailList[$pseudoId] = [
						'ENTITY_TYPE_ID' => CCrmOwnerType::Requisite,
						'ENTITY_ID' => $this->requisiteId,
						'COUNTRY_ID' => $this->presetCountryId
					];
				}
				foreach ($fields as $fieldName => $fieldValue)
				{
					$this->rawBankDetailList[$pseudoId][$fieldName] = $fieldValue;
				}
				unset($fields);
			}
		}
	}

	protected function validateData()
	{
		if ($this->isEditMode)
		{
			$this->rawRequisiteData['DATE_MODIFY'] = new Type\DateTime();
			$this->rawRequisiteData['MODIFY_BY_ID'] = CCrmSecurityHelper::GetCurrentUserID();
			$result = $this->requisite->checkBeforeUpdate($this->requisiteId, $this->rawRequisiteData);
		}
		else    // create, copy
		{
			$curDateTime = new \Bitrix\Main\Type\DateTime();
			$curUserId = CCrmSecurityHelper::GetCurrentUserID();
			$this->rawRequisiteData['DATE_CREATE'] = $curDateTime;
			$this->rawRequisiteData['DATE_MODIFY'] = $curDateTime;
			$this->rawRequisiteData['CREATED_BY_ID'] = $curUserId;
			$this->rawRequisiteData['MODIFY_BY_ID'] = $curUserId;
			$result = $this->requisite->checkBeforeAdd($this->rawRequisiteData);
		}

		if($result && $result->isSuccess())
		{
			if(is_array($this->rawBankDetailList) && !empty($this->rawBankDetailList))
			{
				foreach($this->rawBankDetailList as $pseudoId => &$bankDetailFields)
				{
					if (isset($this->deletedBankDetailMap[$pseudoId]))
					{
						continue;
					}

					if ($pseudoId > 0)
					{
						$bankDetailResult = $this->bankDetail->checkBeforeUpdate($pseudoId, $bankDetailFields);
					}
					else
					{
						$bankDetailResult = $this->bankDetail->checkBeforeAdd($bankDetailFields);
					}

					if($bankDetailResult !== null && !$bankDetailResult->isSuccess())
					{
						$result->addErrors($bankDetailResult->getErrors());
					}
				}
				unset($bankDetailFields);
			}
		}

		if ($result && !$result->isSuccess())
		{
			$this->errors->add($result->getErrors());
		}
	}

	protected function doDelete()
	{
		$result = $this->requisite->delete($this->requisiteId);
		if (!$result->isSuccess())
		{
			$this->errors->add($result->getErrors());
		}
	}

	protected function saveData()
	{
		$dbConnection = Application::getConnection();
		if ($this->isEditMode)
		{
			$this->rawRequisiteData['DATE_MODIFY'] = new Type\DateTime();
			$this->rawRequisiteData['MODIFY_BY_ID'] = CCrmSecurityHelper::GetCurrentUserID();
			$dbConnection->startTransaction();
			$result = $this->requisite->update($this->requisiteId, $this->rawRequisiteData);
		}
		else
		{
			$curDateTime = new \Bitrix\Main\Type\DateTime();
			$curUserId = CCrmSecurityHelper::GetCurrentUserID();
			$this->rawRequisiteData['DATE_CREATE'] = $curDateTime;
			$this->rawRequisiteData['DATE_MODIFY'] = $curDateTime;
			$this->rawRequisiteData['CREATED_BY_ID'] = $curUserId;
			$this->rawRequisiteData['MODIFY_BY_ID'] = $curUserId;
			$dbConnection->startTransaction();
			$result = $this->requisite->add($this->rawRequisiteData);
			if($result && $result->isSuccess())
			{
				$this->requisiteId = $result->getId();
				$this->rawRequisiteData['ID'] = $this->requisiteId;
			}
		}

		if($result && $result->isSuccess())
		{
			if ($this->isAddressOnly)
			{
				$this->bankDetail->deleteByEntity(\CCrmOwnerType::Requisite, $this->requisiteId);
			}
			elseif(is_array($this->rawBankDetailList) && !empty($this->rawBankDetailList))
			{
				$changeKeyMap = [];
				foreach($this->rawBankDetailList as $pseudoId => &$bankDetailFields)
				{
					$bankDetailResult = null;
					if(isset($this->deletedBankDetailMap[$pseudoId]))
					{
						if($pseudoId > 0)
						{
							$bankDetailResult = $this->bankDetail->delete($pseudoId);
							if ($bankDetailResult->isSuccess())
							{
								unset($this->rawBankDetailList[$pseudoId]);
							}
							unset($this->deletedBankDetailMap[$pseudoId]);
						}
					}
					elseif ((int)$pseudoId > 0)
					{
						$bankDetailResult = $this->bankDetail->update($pseudoId, $bankDetailFields);
					}
					else
					{
						$bankDetailFields['ENTITY_TYPE_ID'] = CCrmOwnerType::Requisite;
						$bankDetailFields['ENTITY_ID'] = $this->requisiteId;
						$bankDetailResult = $this->bankDetail->add($bankDetailFields);
						if($bankDetailResult && $bankDetailResult->isSuccess())
						{
							$bankDetailFields['ID'] = $changeKeyMap[$pseudoId] = $bankDetailResult->getId();
						}
					}

					if($bankDetailResult !== null && !$bankDetailResult->isSuccess())
					{
						$result->addErrors($bankDetailResult->getErrors());
					}
				}
				unset($bankDetailFields);

				// Change pseudo identifiers of bank details after save
				if (!empty($changeKeyMap))
				{
					$rawBankDetailList = [];
					foreach(array_keys($this->rawBankDetailList) as $key)
					{
						if (isset($changeKeyMap[$key]))
						{
							$rawBankDetailList[$changeKeyMap[$key]] = $this->rawBankDetailList[$key];
						}
						else
						{
							$rawBankDetailList[$key] = $this->rawBankDetailList[$key];
						}
					}
					$this->rawBankDetailList = $rawBankDetailList;
				}
				unset($changeKeyMap, $rawBankDetailList, $key);
			}
		}

		if ($result)
		{
			if ($result->isSuccess())
			{
				$dbConnection->commitTransaction();
			}
			else
			{
				$dbConnection->rollbackTransaction();
				$this->errors->add($result->getErrors());
			}
		}
	}

	public function executeComponent()
	{
		$this->checkModules();

		if (!$this->hasErrors())
		{
			$this->initialize();
		}

		if (!$this->hasErrors() && $this->isDeleteMode)
		{
			if ($this->doSave)
			{
				$this->doDelete();
			}
		}
		else
		{
			if (!$this->hasErrors() && $this->isPresetChange)
			{
				$this->applyPresetChangeData();
			}

			if (!$this->hasErrors() && $this->useFormData)
			{
				$this->applyFormData();
			}

			if (!$this->hasErrors())
			{
				if ($this->doSave)
				{
					$this->saveData();
				}
				elseif ($this->isJsonResponse)
				{
					$this->validateData();
				}
			}
		}

		if ($this->isJsonResponse)
		{
			$this->getApp()->RestartBuffer();
			header('Content-Type:application/json; charset=UTF-8');

			echo $this->prepareJsonResponse();
			CMain::FinalActions();
			die;
		}

		if (!$this->hasErrors())
		{
			$this->prepareResult();
		}

		$this->includeComponentTemplate();
	}

	protected function getFormFieldsInfo()
	{
		if ($this->formFieldsInfo === null)
		{
			$this->formFieldsInfo = $this->requisite->getFormFieldsInfo($this->presetCountryId);
		}

		return $this->formFieldsInfo;
	}

	protected function getBankDetailFieldsInfo()
	{
		if ($this->bankDetailFieldsInfo === null)
		{
			$this->bankDetailFieldsInfo = $this->bankDetail->getFormFieldsInfoByCountry($this->presetCountryId);
		}

		return $this->bankDetailFieldsInfo;
	}

	protected function prepareBankDetailsFields()
	{
		$fields = [];

		$bankDetailFields = $this->getBankDetailFieldsInfo();
		foreach ($bankDetailFields as $fieldName => $fieldInfo)
		{
			$fields[] = array(
				'name' => $fieldName,
				'title' => $fieldInfo['title'],
				'type' => $fieldInfo['formType'],
				'required' => $fieldInfo['required']
			);
		}

		return $fields;
	}

	protected function getNextBankDetailPseudoIndex($bankDetailList)
	{
		$maxIndex = -1;

		if (is_array($bankDetailList) && !empty($bankDetailList))
		{
			foreach (array_keys($bankDetailList) as $pseudoId)
			{
				$matches = [];
				if (preg_match('#^n(\d{1,8})$#', $pseudoId, $matches))
				{
					$maxIndex = max($maxIndex, (int)$matches[1]);
				}
			}
		}

		return $maxIndex + 1;
	}

	protected function getNextBankDetailIndex()
	{
		return max(
			$this->getNextBankDetailPseudoIndex($this->deletedBankDetailMap),
			$this->getNextBankDetailPseudoIndex($this->rawBankDetailList)
		);
	}

	protected function prepareFormFields()
	{
		$fields = [];

		$fields[] = [
			'title' => Loc::getMessage('CRM_REQUISITE_DETAILS_AUTOCOMPLETE'),
			'name' => 'AUTOCOMPLETE',
			'type' => 'requisite_autocomplete',
			'editable' => true,
			'enabledMenu' => false,
			'data' => CCrmComponentHelper::getRequisiteAutocompleteFieldInfoData($this->presetCountryId)
		];

		if ($this->presetId > 0)
		{
			$fields[] = [
				'title' => Loc::getMessage('CRM_REQUISITE_DETAILS_PRESET'),
				'name' => 'PRESET_ID',
				'type' => 'list',
				'data' => array(
					'items'=> CCrmInstantEditorHelper::PrepareListOptions(
						EntityPreset::getActiveItemList(),
						['DEFAULT_PRESET_ID' => $this->presetId]
					)
				),
				'editable' => true,
				'enabledMenu' => false
			];
		}

		$fields[] = [
			'title' => isset($this->requisiteFieldTitles['NAME']) ? $this->requisiteFieldTitles['NAME'] : 'NAME',
			'name' => 'NAME',
			'type' => 'text',
			'required' => true
		];

		// rq fields
		$fieldsInfo = $this->getFormFieldsInfo();
		foreach ($this->presetFields as $fieldName)
		{
			if (isset($this->fieldsAllowed[$fieldName]) && isset($fieldsInfo[$fieldName]))
			{
				$fieldInfo = $fieldsInfo[$fieldName];
				if ($fieldInfo['isRQ'])
				{
					//region Field title
					if (isset($this->presetFieldTitles[$fieldName])
						&& $this->presetFieldTitles[$fieldName] !== '')
					{
						$fieldTitle = $this->presetFieldTitles[$fieldName];
					}
					else if (isset($this->requisiteFieldTitles[$fieldName])
						&& $this->requisiteFieldTitles[$fieldName] !== '')
					{
						$fieldTitle = $this->requisiteFieldTitles[$fieldName];
					}
					else
					{
						$fieldTitle = $fieldName;
					}
					//endregion Field title

					if ($fieldInfo['isUF'])
					{
						$ufInfo = [
							'title' => $fieldTitle,
							'name' => $fieldName,
							'type' => 'userField',
							'required' => $fieldInfo['required'],
							'data' => [
								'fieldInfo' => [
									'USER_TYPE_ID' => $fieldInfo['type'],
									'ENTITY_ID' => $this->requisite->getUfId(),
									'ENTITY_VALUE_ID' => $this->requisiteId,
									'FIELD' => $fieldName,
									'MULTIPLE' => $fieldInfo['multiple'],
									'MANDATORY' => $fieldInfo['required'],
									'SETTINGS' => isset($fieldInfo['settings']) ? $fieldInfo['settings'] : null
								]
							]
						];
						$fields[] = $ufInfo;
						unset($ufInfo);
					}
					else
					{
						if ($fieldName === EntityRequisite::ADDRESS)
						{
							if ($this->isLocationModuleIncluded)
							{
								$fields[] = [
									'title' => $fieldTitle,
									'name' => $fieldName,
									'type' => 'crm_address',
									'editable' => true,
									'data' =>
										CCrmComponentHelper::getRequisiteAddressFieldData(
											$this->entityTypeId,
											$this->categoryId
										)
										+ ['countryId' => $this->presetCountryId]
									,
								];
								unset($defaultAddressType);
							}
						}
						else
						{
							switch ($fieldInfo['type'])
							{
								case 'boolean':
								case 'checkbox':
									$fieldType = 'boolean';
									break;
								case 'image':
									$fieldType = 'crm_image';
									break;
								default:
									$fieldType = 'text';
							}

							$fieldFormConfig = [
								'title' => $fieldTitle,
								'name' => $fieldName,
								'type' => $fieldType
							];

							if ($fieldInfo['formType'] === 'crm_status')
							{
								$fieldFormConfig['type'] = 'list';
								$fieldFormConfig['data'] = $this->requisite->getRqListFieldFormData(
									$fieldName,
									$this->presetCountryId
								);
							}
							if ($fieldType === 'crm_image')
							{
								$fieldFormConfig['data'] = [
									'ownerEntityTypeId' => $this->entityTypeId,
									'ownerEntityId' => $this->entityId,
									'ownerEntityCategoryId' => $this->categoryId,
									'permissionToken' => $this->permissionToken,
								];
							}

							$fields[] = $fieldFormConfig;

							unset($fieldFormConfig);
						}
					}
				}
			}
		}

		// Bank details
		$fields[] = [
			'name' => 'BANK_DETAILS',
			'type' => 'bankDetails',
			'multiple' => true,
			'enabledMenu' => false,
			'transferable' => false,
			'data' => [
				'fields' => $this->prepareBankDetailsFields(),
				'nextIndex' => $this->getNextBankDetailIndex()
			]
		];

		return $fields;
	}

	protected function prepareSingleUserFieldValue($value)
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

		return $value;
	}

	protected function prepareBankDetailsData()
	{
		$data = [];

		if (!empty($this->rawBankDetailList))
		{
			$bankDetailFields = null;
			$n = 0;
			foreach ($this->rawBankDetailList as $pseudoId => $rawBankDetail)
			{
				if ($bankDetailFields === null)
				{
					$bankDetailFields = $this->bankDetail->getFormFieldsInfoByCountry($this->presetCountryId);
				}
				$fields = [
					'ID' => $pseudoId,
					'ENTITY_TYPE_ID' => CCrmOwnerType::Requisite,
					'ENTITY_ID' => $this->requisiteId,
					'COUNTRY_ID' => $this->presetCountryId
				];
				if (is_array($bankDetailFields))
				{
					foreach ($bankDetailFields as $fieldName => $fieldInfo)
					{
						$fields[$fieldName] = isset($rawBankDetail[$fieldName]) ? $rawBankDetail[$fieldName] : '';
					}
				}
				if (isset($this->deletedBankDetailMap[$pseudoId]))
				{
					$fields['DELETED'] = 'Y';
				}
				$data[] = $fields;
			}
		}

		return $data;
	}

	protected function prepareFormData()
	{
		$data = [];

		// Preset ID
		if ($this->presetId > 0)
		{
			$data['PRESET_ID'] = $this->presetId;
		}

		// Requisite name
		if (isset($this->rawRequisiteData['NAME'])
			&& is_string($this->rawRequisiteData['NAME'])
			&& $this->rawRequisiteData['NAME'] !== '')
		{
			$data['NAME'] = $this->rawRequisiteData['NAME'];
		}
		else if ($this->isCreateMode
			&& isset($this->rawPresetData['NAME'])
			&& is_string($this->rawPresetData['NAME'])
			&& $this->rawPresetData['NAME'] !== '')
		{
			$data['NAME'] = $this->rawPresetData['NAME'];
		}
		else
		{
			$data['NAME'] = '';
		}

		// Autocomplete
		$data['AUTOCOMPLETE'] = trim(strval($this->formData['AUTOCOMPLETE']));

		// rq fields
		$fieldsInfo = $this->getFormFieldsInfo();
		foreach ($this->presetFields as $fieldName)
		{
			if (isset($this->fieldsAllowed[$fieldName]) && isset($fieldsInfo[$fieldName]))
			{
				$fieldInfo = $fieldsInfo[$fieldName];
				if ($fieldInfo['isRQ'])
				{
					if ($fieldInfo['isUF'])
					{
						$fieldValue = (
							isset($this->rawRequisiteData[$fieldName]) ?
								$this->rawRequisiteData[$fieldName] : null
						);

						if(is_array($fieldValue))
						{
							foreach($fieldValue as &$value)
							{
								$value = $this->prepareSingleUserFieldValue($value);
							}
						}
						else
						{
							$fieldValue = $this->prepareSingleUserFieldValue($fieldValue);
						}

						$isEmptyField = true;
						$fieldParams = [
							'USER_TYPE_ID' => $fieldInfo['type'],
							'ENTITY_ID' => $this->requisite->getUfId(),
							'ENTITY_VALUE_ID' => $this->requisiteId,
							'FIELD' => $fieldName,
							'MULTIPLE' => $fieldInfo['multiple'],
							'MANDATORY' => $fieldInfo['required'],
							'SETTINGS' => isset($fieldInfo['settings']) ? $fieldInfo['settings'] : null
						];
						if((is_string($fieldValue) && $fieldValue !== '')
							|| (is_numeric($fieldValue) && $fieldValue !== 0)
							|| (is_array($fieldValue) && !empty($fieldValue))
							|| (is_object($fieldValue))
						)
						{
							if(is_array($fieldValue))
							{
								$fieldValue = array_values($fieldValue);
							}
							$fieldParams['VALUE'] = $fieldValue;
							$isEmptyField = false;
						}

						$fieldSignature = $this->userFieldDispatcher->getSignature($fieldParams);
						if($isEmptyField)
						{
							$data[$fieldName] = [
								'SIGNATURE' => $fieldSignature,
								'IS_EMPTY' => true
							];
						}
						else
						{
							$data[$fieldName] = [
								'VALUE' => $fieldValue,
								'SIGNATURE' => $fieldSignature,
								'IS_EMPTY' => false
							];
						}
					}
					else
					{
						if ($fieldName === EntityRequisite::ADDRESS)
						{
							if ($this->isLocationModuleIncluded)
							{
								$data[$fieldName] = [];
								if (is_array($this->rawRequisiteData[$fieldName]))
								{
									foreach ($this->rawRequisiteData[$fieldName] as $addressTypeId => $addressFields)
									{
										if (is_array($addressFields))
										{
											$addressTypeId = (int)$addressTypeId;
											$isDeleted = (
												isset($addressFields['DELETED']) && $addressFields['DELETED'] === 'Y'
											);
											if ($isDeleted)
											{
												$dataFields[$fieldName][$addressTypeId] = ['DELETED' => 'Y'];
											}
											else if (isset($addressFields['LOC_ADDR'])
												&& $addressFields['LOC_ADDR'] instanceof Address)
											{
												/** @var $locationAddress Address */
												$locationAddress = $addressFields['LOC_ADDR'];
												$data[$fieldName][$addressTypeId] = $locationAddress->toJson();
												unset($locationAddress);
											}
										}
									}
									unset($addressTypeId, $addressFields);
								}
							}
						}
						else
						{
							switch ($fieldInfo['type'])
							{
								case 'boolean':
								case 'checkbox':
									$data[$fieldName] = (
										(isset($this->rawRequisiteData[$fieldName])
											&& $this->rawRequisiteData[$fieldName] === 'Y') ? 'Y' : 'N'
									);
									break;
								default:
									$data[$fieldName] = (
										isset($this->rawRequisiteData[$fieldName]) ?
											$this->rawRequisiteData[$fieldName] : ''
									);
							}
						}
					}
				}
			}
		}

		$data['BANK_DETAILS'] = $this->prepareBankDetailsData();

		return $data;
	}

	protected function prepareFormParams()
	{
		$params = [];

		$params['FORM_TITLE'] = (
			$this->isReadOnly ?
				Loc::getMessage('CRM_REQUISITE_DETAILS_FORM_TITLE_READ_ONLY') :
				Loc::getMessage('CRM_REQUISITE_DETAILS_FORM_TITLE')
		);
		$params['FORM_ID'] = $this->getFormId();
		$params['CONFIG_ID'] = $this->getFormConfigId();
		$params['FIELDS'] = $this->prepareFormFields();
		$params['DATA'] = $this->prepareFormData();
		$params['READ_ONLY'] = $this->isReadOnly;
		$params['USER_FIELD_ENTITY_ID'] = $this->requisite->getUfId();
		$params['USER_FIELD_CREATE_SIGNATURE'] = UserField\SignatureHelperCreate::getSignature(
			new UserField\SignatureManager(),
			['ENTITY_ID' => $this->requisite->getUfId()]
		);
		$params['CONTEXT'] = [
			'sessid' => bitrix_sessid(),
			'mode' => $this->mode,
			'etype' => $this->entityTypeId,
			'cid' => $this->categoryId,
			'eid' => $this->entityId,
			'requisite_id' => $this->requisiteId,
			'pseudoId' => $this->pseudoId,
			'pid' => $this->presetId,
			'presetCountryId' => $this->presetCountryId,
			'externalData' => $this->prepareExternalData(),
			'external_context_id' => $this->externalContextId,
			'ADDRESS_ONLY' => 'N',
			'permissionToken' => $this->permissionToken,
		];
		if ($this->doSaveContext)
		{
			$params['CONTEXT']['doSave'] = 'Y';
		}

		return $params;
	}

	protected function getFormId()
	{
		$formElementId = $this->requisiteId > 0 ? $this->requisiteId : $this->pseudoId;
		return "CRM_REQUISITE_EDIT_{$formElementId}_PID{$this->presetId}";
	}

	protected function getFormConfigId()
	{
		return "CRM_REQUISITE_EDIT_0_PID{$this->presetId}";
	}

	protected function prepareJsParams()
	{
		$params = [];

		$params['entityEditorId'] = $this->getFormId();
		if ($this->isReload)
		{
			$params['markPresetAsChanged'] = true;
		}
		if ($this->arParams['ADD_BANK_DETAILS_ITEM'] === 'Y')
		{
			$params['autoAddBankDetailsItem'] = true;
		}
		$params['duplicateControlEnabled'] = $this->enableDupControl;
		if ($this->enableDupControl)
		{
			$requisiteFieldsMap = EntityRequisite::getDuplicateCriterionFieldsMap();
			$bankDetailsFieldsMap =  EntityBankDetail::getDuplicateCriterionFieldsMap();
			$params['duplicateControl'] = [
				'serviceUrl' => '/bitrix/components/bitrix/crm.requisite.details/ajax.php',
				'entityTypeName' => \CCrmOwnerType::ResolveName($this->entityTypeId),
				'entityId' => $this->entityId,
				'requisiteId' => $this->requisiteId,
				'presetId' => $this->presetId,
				'requisiteFieldsMap' => is_array($requisiteFieldsMap[$this->presetCountryId]) ?
					$requisiteFieldsMap[$this->presetCountryId] : [],
				'bankDetailsFieldsMap' => is_array($bankDetailsFieldsMap[$this->presetCountryId]) ?
					$bankDetailsFieldsMap[$this->presetCountryId] : [],
			];
		}
		return $params;
	}

	protected function prepareResult()
	{
		$this->arResult['ENTITY_TYPE_ID'] = $this->entityTypeId;
		$this->arResult['CATEGORY_ID'] = $this->categoryId;
		$this->arResult['ENTITY_ID'] = $this->entityId;
		$this->arResult['REQUISITE_ID'] = $this->requisiteId;

		if ($this->entityTypeId === CCrmOwnerType::Company)
		{
			$this->arResult['ENTITY_TYPE_MNEMO'] = 'COMPANY';
		}
		else if ($this->entityTypeId === CCrmOwnerType::Contact)
		{
			$this->arResult['ENTITY_TYPE_MNEMO'] = 'CONTACT';
		}
		else
		{
			$this->arResult['ENTITY_TYPE_MNEMO'] = '';
		}

		$this->arResult['FORM_PARAMS'] = $this->prepareFormParams();
		$this->arResult['JS_PARAMS'] = $this->prepareJsParams();
	}

	protected function prepareExternalValues($fields)
	{
		$dataFields = $fields;
		foreach ($dataFields as $fieldName => $value)
		{
			if ($value instanceof Type\Date
				|| $value instanceof Type\DateTime)
			{
				$dataFields[$fieldName] = $value->toString();
			}
			else if ($fieldName === EntityRequisite::ADDRESS && is_array($value) && !empty($value))
			{
				foreach ($value as $addressTypeId => $addressFields)
				{
					$isDeleted = (
						is_array($addressFields)
						&& isset($addressFields['DELETED'])
						&& $addressFields['DELETED'] === 'Y'
					);
					if ($isDeleted)
					{
						$dataFields[$fieldName][$addressTypeId] = ['DELETED' => 'Y'];
					}
					else
					{
						$locationAddress = null;
						if ($this->isLocationModuleIncluded)
						{
							$locationAddress = RequisiteAddress::makeLocationAddressByFields($addressFields);
						}
						if ($locationAddress)
						{
							$dataFields[$fieldName][$addressTypeId] = $locationAddress->toJson();
						}
						else
						{
							unset($dataFields[$fieldName][$addressTypeId]);
						}
						unset($locationAddress);
					}
				}
				unset($addressTypeId, $addressFields);
			}
		}

		return $dataFields;
	}

	protected function appendPresetChangeData(array $presetChangeData = [])
	{
		$hiddenFields = [];
		$hiddenBankDetailFields = [];

		if ($this->isPresetChange)
		{
			$hiddenFields = array_diff($this->presetFields, $this->prevPresetFields);
			if ($this->presetCountryId !== $this->prevPresetCountryId)
			{
				$prevBankDetailFields = array_keys(
					$this->bankDetail->getFormFieldsInfoByCountry($this->prevPresetCountryId)
				);
				$curBankDetailFields = array_keys($this->getBankDetailFieldsInfo());
				$hiddenBankDetailFields = array_diff($prevBankDetailFields, $curBankDetailFields);
			}
		}

		if (!empty($hiddenFields) || !empty($hiddenBankDetailFields))
		{
			if (!empty($hiddenFields))
			{
				$dataFields = [];
				foreach ($hiddenFields as $fieldName)
				{
					if (isset($this->rawRequisiteData[$fieldName]))
					{
						$dataFields[$fieldName] = $this->rawRequisiteData[$fieldName];
					}
				}
				$dataFields = $this->prepareExternalValues($dataFields);
				$presetChangeData = array_merge($presetChangeData, $dataFields);
			}

			if (!empty($hiddenBankDetailFields))
			{
				$n = 0;
				foreach ($this->rawBankDetailList as $pseudoId => $bankDetailFields)
				{
					$dataFields = [];
					foreach ($hiddenBankDetailFields as $fieldName)
					{
						if (isset($bankDetailFields[$fieldName]))
						{
							$dataFields[$fieldName] = $bankDetailFields[$fieldName];
						}
					}
					$dataFields = $this->prepareExternalValues($dataFields);
					if (!is_array($presetChangeData['BANK_DETAILS']))
					{
						$presetChangeData['BANK_DETAILS'] = [];
					}
					$presetChangeData['BANK_DETAILS'][$pseudoId] = $dataFields;
				}
			}
		}

		return $presetChangeData;
	}

	protected function prepareExternalData()
	{
		// Requisites
		$dataFields = $this->prepareExternalValues($this->rawRequisiteData);
		$fieldsInView = array_intersect_assoc(array_fill_keys($this->presetFields, true), $this->fieldsAllowed);
		$data = array(
			'fields' => $dataFields,
			'viewData' => $this->requisite->prepareViewDataFormatted($dataFields, $fieldsInView),
			'bankDetailFieldsList' => array(),
			'bankDetailViewDataList' => array()
		);
		unset($dataFields, $fieldName, $value, $fieldsInView);

		// Bank details
		$n = 0;
		foreach ($this->rawBankDetailList as $pseudoId => $bankDetailFields)
		{
			$bankDetailFields = $this->prepareExternalValues($bankDetailFields);
			$data['bankDetailFieldsList'][$pseudoId] = $bankDetailFields;
			$data['bankDetailViewDataList'][] = [
				'pseudoId' => $pseudoId,
				'deleted' => !!$this->deletedBankDetailMap[$pseudoId],
				'viewData' => $this->bankDetail->prepareViewData(
					$bankDetailFields,
					array_keys($this->getBankDetailFieldsInfo())
				)
			];
		}
		unset($bankDetailFields, $fieldName, $value, $pseudoId);

		//Store deleted bank details IDs for future saving.
		if(!$this->doSave && !empty($this->deletedBankDetailMap))
		{
			$data['deletedBankDetailList'] = array_keys($this->deletedBankDetailMap);
		}

		// Preset change data
		$this->presetChangeData = $this->appendPresetChangeData($this->presetChangeData);
		$data['presetChangeData'] = $this->presetChangeData;

		// JSON data
		$requisiteData = '';
		$requisiteDataSign = '';
		if (is_array($data))
		{
			$jsonData = null;
			try
			{
				$jsonData = Json::encode($data);
			}
			catch (SystemException $e)
			{
			}

			if ($jsonData)
			{
				$signer = new Signer();
				$requisiteDataSign = '';
				try
				{
					$requisiteDataSign = $signer->getSignature(
						$jsonData,
						'crm.requisite.edit-'.$this->entityTypeId
					);
				}
				catch (SystemException $e)
				{
				}

				if (!empty($requisiteDataSign))
				{
					$requisiteData = $jsonData;
				}
			}
			unset($jsonData);
		}

		return [
			'data' => $requisiteData,
			'sign' => $requisiteDataSign
		];
	}

	protected function prepareJsonResponse()
	{
		$result = [];

		if ($this->hasErrors())
		{
			$result['ERROR'] = $this->getErrorsAsHtml();
		}
		else
		{
			$result['ENTITY_DATA'] = [];

			if (!$this->isDeleteMode)
			{
				$externalData = $this->prepareExternalData();

				if (is_array($externalData)
					&& is_string($externalData['data']) && $externalData['data'] !== ''
					&& is_string($externalData['sign']) && $externalData['sign'] !== '')
				{
					$result['ENTITY_DATA']['REQUISITE_DATA'] = $externalData['data'];
					$result['ENTITY_DATA']['REQUISITE_DATA_SIGN'] = $externalData['sign'];
				}
			}
		}

		return Json::encode($result);
	}

	protected function getEntityCategoryId(int $entityTypeId, int $entityId): int
	{
		$result = 0;

		if (CCrmOwnerType::IsDefined($entityTypeId) && $entityId > 0)
		{
			$factory = Container::getInstance()->getFactory($entityTypeId);
			if ($factory)
			{
				$categoryId = 0;
				if ($factory->isCategoriesSupported())
				{
					$item = $factory->getItem($entityId);
					if ($item)
					{
						$categoryId = $item->getCategoryId();
					}
				}
				if ($categoryId > 0 && $factory->isCategoryAvailable($categoryId))
				{
					$result = $categoryId;
				}
			}
		}

		return $result;
	}

	private function checkReadPermissions(int $entityTypeId, int $entityId, ?int $categoryId = null): bool
	{
		$canReadRequisite = EntityRequisite::checkReadPermissionOwnerEntity($entityTypeId, $entityId, $categoryId);
		if ($canReadRequisite)
		{
			return true;
		}

		return \Bitrix\Crm\Security\PermissionToken::canEditRequisites($this->permissionToken, $entityTypeId, $entityId);
	}

	private function checkEditPermissions(int $entityTypeId, int $entityId, ?int $categoryId = null): bool
	{
		$canEditRequisite = (
			$entityId > 0
			&& EntityRequisite::checkUpdatePermissionOwnerEntity($this->entityTypeId, $this->entityId)
		)
		|| (
			!$entityId
			&& EntityRequisite::checkCreatePermissionOwnerEntity($this->entityTypeId, $categoryId)
		);
		if ($canEditRequisite)
		{
			return true;
		}

		return \Bitrix\Crm\Security\PermissionToken::canEditRequisites($this->permissionToken, $entityTypeId, $entityId);
	}
}
