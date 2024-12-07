<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\BIConnector\Superset\UI\SettingsPanel\Controller\EntityEditorController;
use Bitrix\BIConnector\Superset\UI\SettingsPanel\Section\EntityEditorSection;
use Bitrix\Main\Loader;

Loader::includeModule("biconnector");

class ApacheSupersetSettingsPanelComponent
	extends CBitrixComponent
{
	/** @var EntityEditorSection[] */
	private array $sectionList = [];

	/** @var EntityEditorController[] */
	private array $panelControllerList = [];

	public function executeComponent()
	{
		$this->arResult['FORM_PARAMETERS'] = $this->getFormParameters();
		$this->includeComponentTemplate();
	}

	public function onPrepareComponentParams($arParams)
	{
		if (!isset($arParams['COMPONENT_AJAX_DATA']) || !is_array($arParams['COMPONENT_AJAX_DATA']))
		{
			$arParams['COMPONENT_AJAX_DATA'] = null;
		}

		if (!isset($arParams['GUID']) || !is_string($arParams['GUID']))
		{
			$arParams['GUID'] = 'BICONNECTOR_SUPERSET_SETTINGS';
		}

		if (!isset($arParams['ENTITY_TYPE_NAME']) || !is_string($arParams['ENTITY_TYPE_NAME']))
		{
			$arParams['ENTITY_TYPE_NAME'] = 'supersetSettings';
		}

		if (!isset($arParams['INITIAL_MODE']) || !is_string($arParams['INITIAL_MODE']))
		{
			$arParams['INITIAL_MODE'] = 'edit';
		}

		if (!isset($arParams['ENTITY_ID']))
		{
			$arParams['ENTITY_ID'] = null;
		}

		if (isset($arParams['SECTION_LIST']) && is_array($arParams['SECTION_LIST']))
		{
			foreach ($arParams['SECTION_LIST'] as $section)
			{
				if (!$section instanceof EntityEditorSection)
				{
					throw new \Bitrix\Main\ArgumentTypeException('SECTION_LIST', EntityEditorSection::class);
				}
			}

			$this->sectionList = $arParams['SECTION_LIST'];
		}

		if (isset($arParams['ENTITY_CONTROLLERS']) && is_array($arParams['ENTITY_CONTROLLERS']))
		{
			foreach ($arParams['ENTITY_CONTROLLERS'] as $controller)
			{
				if (!$controller instanceof EntityEditorController)
				{
					throw new \Bitrix\Main\ArgumentTypeException('ENTITY_CONTROLLERS', EntityEditorController::class);
				}
			}

			$this->panelControllerList = $arParams['ENTITY_CONTROLLERS'];
		}

		return parent::onPrepareComponentParams($arParams);
	}

	private function getFormParameters(): array
	{
		return [
			'GUID' => $this->arParams['GUID'],
			'INITIAL_MODE' => $this->arParams['INITIAL_MODE'],
			'ENTITY_ID' => $this->arParams['ENTITY_ID'],
			'ENTITY_TYPE_NAME' => $this->arParams['ENTITY_TYPE_NAME'],
			'ENTITY_FIELDS' => $this->getEntityFields(),
			'ENTITY_CONFIG' => $this->getEntityConfig(),
			'ENTITY_DATA' => $this->getEntityData(),
			'ENTITY_CONTROLLERS' => $this->getEntityControllers(),
			'ENABLE_PAGE_TITLE_CONTROLS' => true,
			'ENABLE_COMMON_CONFIGURATION_UPDATE' => true,
			'ENABLE_PERSONAL_CONFIGURATION_UPDATE' => true,
			'ENABLE_SECTION_DRAG_DROP' => false,
			'ENABLE_CONFIG_CONTROL' => false,
			'ENABLE_FIELD_DRAG_DROP' => false,
			'ENABLE_FIELDS_CONTEXT_MENU' => false,
			'IS_IDENTIFIABLE_ENTITY' => false,
			'ENABLE_MODE_TOGGLE' => false,
			'COMPONENT_AJAX_DATA' => $this->arParams['COMPONENT_AJAX_DATA'],
		];
	}

	private function getEntityFields(): array
	{
		$fieldList = [];
		foreach ($this->sectionList as $section)
		{
			$sectionFieldList = $section->getFields();
			foreach ($sectionFieldList as $sectionField)
			{
				$fieldList[] = $sectionField->getFieldInfo();
			}
		}

		return $fieldList;
	}

	private function getEntityControllers(): array
	{
		$controllerList = [];
		foreach ($this->panelControllerList as $controller)
		{
			$controllerList[] = $controller->getData();
		}

		return $controllerList;
	}

	private function getEntityConfig(): array
	{
		$configList = [];

		foreach ($this->sectionList as $section)
		{
			$configList[] = $section->getConfig();
		}

		return $configList;
	}

	private function getEntityData(): array
	{
		$entityData = [];

		foreach ($this->sectionList as $section)
		{
			foreach ($section->getFields() as $field)
			{
				$entityData += $field->getFieldInitialData();
			}
		}

		return $entityData;
	}
}
