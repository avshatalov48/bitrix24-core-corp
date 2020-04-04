<?php

use Bitrix\Main\Localization\Loc;

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

require_once ('helper.php');

Loc::loadMessages(__FILE__);

class CCrmProductSectionCrumbsComponent extends \CBitrixComponent
{
	private $helper;
	private $componentId;
	private $errors;

	private $catalogId;
	private $sectionId;

	private $jsEventsMode;
	private $jsEventsManagerId;


	public function __construct($component = null)
	{
		parent::__construct($component);

		$this->helper = new CCrmProductSectionCrumbsHelper;
		$this->componentId = $this->randString();
		$this->errors = array();

		$this->catalogId = 0;
		$this->sectionId = 0;
	}

	public function executeComponent()
	{
		if (!$this->checkModules())
		{
			$this->showErrors();
			return;
		}

		if (!$this->checkRights())
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

		// prepare URI template
		$matches = array();
		$curParam = $this->getApp()->GetCurParam();
		$curParam = preg_replace('/(?<!\w)list_section_id=\d*(?=([^\d]|$))/', 'list_section_id=#section_id#', $curParam);
		$curParam = preg_replace('/(^|&)tree=\w*(?=(&|$))/', '', $curParam);
		$this->arParams['PAGE_URI_TEMPLATE'] = $this->arParams['PATH_TO_PRODUCT_LIST'].(strlen($curParam) > 0 ? '?'.$curParam.'&tree=Y' : '?tree=Y');
		unset($curParam);

		// Catalog ID
		if (isset($this->arParams['CATALOG_ID']))
			$this->catalogId = intval($this->arParams['CATALOG_ID']);
		if ($this->catalogId <= 0)
			$this->catalogId = CCrmCatalog::GetDefaultID();

		// Section ID
		if (isset($this->arParams['SECTION_ID']))
			$this->sectionId = intval($this->arParams['SECTION_ID']);
		if ($this->sectionId < 0)
			$this->sectionId = 0;

		// JS events mode
		if (isset($this->arParams['JS_EVENTS_MODE']) && $this->arParams['JS_EVENTS_MODE'] === 'Y')
			$this->jsEventsMode = true;

		// JS events manager
		$this->jsEventsManagerId =
			isset($this->arParams['JS_EVENTS_MANAGER_ID']) ? strval($this->arParams['JS_EVENTS_MANAGER_ID']) : '';

		return true;
	}

	protected function prepareResult()
	{
		$this->arResult['CATALOG_ID'] = $this->catalogId;
		$this->arResult['SECTION_ID'] = $this->sectionId;
		$this->arResult['PAGE_URI_TEMPLATE'] = $this->arParams['PAGE_URI_TEMPLATE'];
		$this->arResult['CRUMBS'] = $this->helper->getCrumbs(
			$this->catalogId,
			$this->sectionId,
			$this->jsEventsMode ? '#section_id#' : $this->arResult['PAGE_URI_TEMPLATE']
		);
		$this->arResult['JS_EVENTS_MODE'] = $this->jsEventsMode ? 'Y' : 'N';
		$this->arResult['JS_EVENTS_MANAGER_ID'] = $this->jsEventsManagerId;
	}

	protected function checkModules()
	{
		if (!CModule::IncludeModule('crm'))
		{
			$this->errors[] = GetMessage('CRM_MODULE_NOT_INSTALLED');
			return false;
		}

		if (!CModule::IncludeModule('iblock'))
		{
			$this->errors[] = GetMessage('CRM_IBLOCK_MODULE_NOT_INSTALLED');
			return false;
		}

		return true;
	}

	protected function checkRights()
	{
		if (!$this->helper->checkRights())
		{
			$this->errors[] = GetMessage('CRM_PERMISSION_DENIED');
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

	public function encodeUrn($urn)
	{
		return $this->helper->encodeUrn($urn);
	}

	public function getHelper()
	{
		return $this->helper;
	}
}
