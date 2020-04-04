<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\EntityPreset;
use Bitrix\Crm\EntityRequisite;

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
	die();

Loc::loadMessages(__FILE__);

class CCrmRequisiteFormEditorComponent extends \CBitrixComponent
{
	protected $componentId;
	protected $errors;

	protected $presetEntityTypeId;
	protected $requisiteEntityId;
	protected $requisiteEntityTypeId;

	protected $externalRequisiteData;

	protected $entityId;
	protected $copyMode;

	protected $requisite;
	protected $preset;

	protected $signer;
	protected $formId;
	protected $formFieldNameTemplate;

	public function __construct($component = null)
	{
		parent::__construct($component);

		$this->componentId = $this->randString();
		$this->errors = array();

		$this->presetEntityTypeId = 0;
		$this->requisiteEntityTypeId = 0;
		$this->requisiteEntityId = 0;

		$this->externalRequisiteData = array();

		$this->entityId = 0;
		$this->copyMode = false;

		$this->requisite = new EntityRequisite();
		$this->preset = new EntityPreset();

		$this->signer = new \Bitrix\Main\Security\Sign\Signer();
		$this->formId = '';
		$this->formFieldNameTemplate = '';
	}

	public function executeComponent()
	{
		if (!$this->checkModules())
		{
			$this->showErrors();
			return;
		}

		if (!$this->parseParams())
		{
			$this->showErrors();
			return;
		}

		$this->prepareResult();

		$this->includeComponentTemplate();
	}

	protected function parseParams()
	{
		$this->arParams['PATH_TO_PRODUCT_LIST'] = CrmCheckPath('PATH_TO_PRODUCT_LIST', $this->arParams['PATH_TO_PRODUCT_LIST'], $this->getApp()->GetCurPage().'?section_id=#section_id#');

		if (isset($this->arParams['PRESET_ENTITY_TYPE_ID']))
			$this->presetEntityTypeId = (int)$this->arParams['PRESET_ENTITY_TYPE_ID'];

		if (!EntityPreset::checkEntityType($this->presetEntityTypeId))
		{
			$this->errors[] = GetMessage('CRM_PRESET_ENTITY_TYPE_INVALID');
			return false;
		}

		if (isset($this->arParams['REQUISITE_ENTITY_ID']))
			$this->requisiteEntityId = (int)$this->arParams['REQUISITE_ENTITY_ID'];
		if (isset($this->arParams['REQUISITE_ENTITY_TYPE_ID']))
			$this->requisiteEntityTypeId = (int)$this->arParams['REQUISITE_ENTITY_TYPE_ID'];
		if (is_array($this->arParams['REQUISITE_DATA']))
			$this->externalRequisiteData = $this->arParams['REQUISITE_DATA'];

		if (!EntityRequisite::checkEntityType($this->requisiteEntityTypeId))
		{
			$this->errors[] = GetMessage('CRM_REQUISITE_ENTITY_TYPE_INVALID');
			return false;
		}

		$this->entityId = isset($this->arParams['ENTITY_ID']) ? (int)$this->arParams['ENTITY_ID'] : 0;
		$this->copyMode = isset($this->arParams['COPY_MODE']) ? $this->arParams['COPY_MODE'] === 'Y' : false;

		$this->formFieldNameTemplate = isset($this->arParams['FORM_FIELD_NAME_TEMPLATE']) ? $this->arParams['FORM_FIELD_NAME_TEMPLATE'] : '';
		$this->formId = isset($this->arParams['FORM_ID']) ? $this->arParams['FORM_ID'] : '';
		if (empty($this->formId))
		{
			$this->errors[] = GetMessage('CRM_REQUISITE_FORM_ID_INVALID');
			return false;
		}

		return true;
	}

	protected function prepareResult()
	{
		$this->arResult['REQUISITE_ENTITY_TYPE_ID'] = $this->requisiteEntityTypeId;
		if ($this->requisiteEntityTypeId === CCrmOwnerType::Company)
		{
			$this->arResult['REQUISITE_ENTITY_TYPE_MNEMO'] = 'COMPANY';
		}
		else
		{
			$this->arResult['REQUISITE_ENTITY_TYPE_MNEMO'] = 'CONTACT';
		}
		$this->arResult['REQUISITE_ENTITY_ID'] = $this->copyMode ? 0 : $this->requisiteEntityId;
		$this->arResult['FORM_FIELD_NAME_TEMPLATE'] = $this->formFieldNameTemplate;

		$currentCountryID = EntityPreset::getCurrentCountryId();
		$this->arResult['COUNTRY_ID'] = $currentCountryID;

		$presetList = array();
		$presetFilter = array(
			'=ENTITY_TYPE_ID' => $this->presetEntityTypeId,
			'=ACTIVE' => 'Y'
		);
		if (!EntityPreset::isUTFMode())
		{
			$presetFilter['=COUNTRY_ID'] = EntityPreset::getCurrentCountryId();
		}
		$res = $this->preset->getList(array(
			'order' => array('SORT' => 'ASC', 'ID' => 'ASC'),
			'filter' => $presetFilter,
			'select' => array('ID', 'NAME')
		));
		while ($row = $res->fetch())
		{
			$presetTitle = trim(strval($row['NAME']));
			if (empty($presetTitle))
				$presetTitle = '['.$row['ID'].'] - '.GetMessage('CRM_REQUISITE_PRESET_NAME_EMPTY');
			$presetList[] = array('id' => $row['ID'], 'title' => $presetTitle);
		}
		$this->arResult['PRESET_LIST'] = $presetList;

		$presetLastSelectedId = 0;
		$optionData = \CUserOptions::GetOption('crm', 'crm_preset_last_selected', array());
		if (is_array($optionData) && !empty($optionData) && isset($optionData[$this->requisiteEntityTypeId]))
		{
			$presetLastSelectedId = (int)$optionData[$this->requisiteEntityTypeId];
			if ($presetLastSelectedId < 0)
				$presetLastSelectedId = 0;
		}
		$this->arResult['PRESET_LAST_SELECTED_ID'] = $presetLastSelectedId;
		unset($presetLastSelectedId, $optionData);

		if(isset($this->arParams['REQUISITE_FORM_DATA']) && !empty($this->arParams['REQUISITE_FORM_DATA']))
		{
			$this->arResult['REQUISITE_FORM_DATA'] = $this->arParams['REQUISITE_FORM_DATA'];
		}
		else
		{
			if ($this->requisiteEntityId > 0)
			{
				$requisiteDataList = \CCrmEntitySelectorHelper::PrepareRequisiteData(
					$this->requisiteEntityTypeId,
					$this->requisiteEntityId,
					array('COPY_MODE' => $this->copyMode)
				);
			}
			else
			{
				$requisiteDataList = $this->prepareRequisiteDataListExternal();
			}
			$this->arResult['REQUISITE_DATA_LIST'] = $requisiteDataList;
		}
	}

	protected function prepareRequisiteDataListExternal()
	{
		$result = array();

		foreach ($this->externalRequisiteData as $data)
		{
			if (isset($data['REQUISITE_ID'])
				&& isset($data['REQUISITE_DATA'])
				&& is_string($data['REQUISITE_DATA'])
				&& !empty($data['REQUISITE_DATA'])
				&& isset($data['REQUISITE_DATA_SIGN'])
				&& is_string($data['REQUISITE_DATA_SIGN'])
				&& !empty($data['REQUISITE_DATA_SIGN']))
			{
				$isValidData = false;

				if($this->signer->validate(
					$data['REQUISITE_DATA'],
					$data['REQUISITE_DATA_SIGN'],
					'crm.requisite.edit-'.$this->requisiteEntityTypeId))
				{
					$isValidData = true;
				}

				if ($isValidData)
				{
					$result[] = array(
						'requisiteId' => $this->copyMode ? 0 : (int)$data['REQUISITE_ID'],
						'requisiteData' => $data['REQUISITE_DATA'],
						'requisiteDataSign' => $data['REQUISITE_DATA_SIGN']
					);
				}
			}
		}

		return $result;
	}

	protected function checkModules()
	{
		if (!CModule::IncludeModule('crm'))
		{
			$this->errors[] = GetMessage('CRM_MODULE_NOT_INSTALLED');
			return false;
		}

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

	public function getFormId()
	{
		return $this->formId;
	}

	public function getFormFieldNameTemplate()
	{
		return $this->formFieldNameTemplate;
	}


}
