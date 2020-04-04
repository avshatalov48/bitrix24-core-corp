<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

class CIntranetSearchComponent extends CBitrixComponent
{
	protected $filter = null;

	/**
	 * @param $arParams
	 * @return array
	 */
	public function onPrepareComponentParams($arParams)
	{
		$arParams['FILTER_NAME'] = $this->initFilterName($arParams['FILTER_NAME']);
		$this->loadTooltipFromSocialNetwork($arParams);

		$arParams["NAME_TEMPLATE"] = (empty($arParams["NAME_TEMPLATE"]) || !trim($arParams["NAME_TEMPLATE"]))?
			CSite::GetNameFormat():
			$arParams["NAME_TEMPLATE"];

		//set default to Y
		$arParams['SHOW_LOGIN'] = (empty($arParams['SHOW_LOGIN']) || $arParams['SHOW_LOGIN'] != "N") ? "Y" : "N";

		//if not set value, set to default
		$arParams["PM_URL"] = empty($arParams["PM_URL"])?
			"/company/personal/messages/chat/#USER_ID#/":
			$arParams["PM_URL"];

		$arParams["PATH_TO_CONPANY_DEPARTMENT"] = empty($arParams["PATH_TO_CONPANY_DEPARTMENT"])?
			"/company/structure.php?set_filter_structure=Y&structure_UF_DEPARTMENT=#ID#":
			$arParams["PATH_TO_CONPANY_DEPARTMENT"];

		$arParams["PATH_TO_VIDEO_CALL"] = IsModuleInstalled("video") && empty($arParams["PATH_TO_VIDEO_CALL"])?
			"/company/personal/video/#USER_ID#/":
			$arParams["PATH_TO_VIDEO_CALL"];

		TrimArr($arParams['ALPHABET_LANG']);
		$arParams['ALPHABET_LANG'] = empty($arParams['ALPHABET_LANG']) ? array(LANGUAGE_ID) : $arParams['ALPHABET_LANG'];
		$arParams['CURRENT_VIEW']  = $this->getCurrentView($arParams);
		$arParams['LIST_URL']      = $this->getApplication()->GetCurPage();
		if (!$arParams['DETAIL_URL'])
		{
			$arParams['DETAIL_URL'] = $arParams['LIST_URL'] . '?ID=#USER_ID#';
		}

		return parent::onPrepareComponentParams($arParams);
	}

	/**
	 * @param $arParams
	 */
	protected function loadTooltipFromSocialNetwork(&$arParams)
	{
		//for bitrix:main.user.link
		$arTooltipFieldsDefault = serialize(array("EMAIL",
			"PERSONAL_MOBILE",
			"WORK_PHONE",
			"PERSONAL_ICQ",
			"PERSONAL_PHOTO",
			"PERSONAL_CITY",
			"WORK_COMPANY",
			"WORK_POSITION",));

		$arTooltipPropertiesDefault = serialize(array("UF_DEPARTMENT",
			"UF_PHONE_INNER",));

		if (!array_key_exists("SHOW_FIELDS_TOOLTIP", $arParams))
		{
			$arParams["SHOW_FIELDS_TOOLTIP"] = unserialize(COption::GetOptionString("socialnetwork", "tooltip_fields", $arTooltipFieldsDefault));
		}
		if (!array_key_exists("USER_PROPERTY_TOOLTIP", $this->arParams))
		{
			$arParams["USER_PROPERTY_TOOLTIP"] = unserialize(COption::GetOptionString("socialnetwork", "tooltip_properties", $arTooltipPropertiesDefault));
		}
	}

	/**
	 * @param $arParams
	 * @return string
	 */
	protected function getCurrentView($arParams)
	{
		$currentView     = $arParams['DEFAULT_VIEW'] === 'list' ? 'list' : 'table';
		$UserCurrentView = CUserOptions::GetOption('search_structure', 'current_view_' . $arParams['FILTER_NAME']);

		if (isset($_REQUEST['current_view']) && $_REQUEST['current_view'] !== $UserCurrentView)
		{
			$currentView = $_REQUEST['current_view'] === 'list' ? 'list' : 'table';
			CUserOptions::SetOption('search_structure', 'current_view_' . $arParams['FILTER_NAME'], $currentView);
		}
		elseif ($UserCurrentView)
		{
			$currentView = $UserCurrentView === 'list' ? 'list' : 'table';
		}

		return $currentView;
	}

	/**
	 * @param $filterName
	 * @return string
	 */
	protected function initFilterName($filterName)
	{
		if (strlen($filterName) <= 0 || !preg_match("/^[A-Za-z_][A-Za-z0-9_]*$/", $filterName))
		{
			return 'find_';
		}

		return $filterName;
	}

	public function executeComponent()
	{
		CModule::IncludeModule('intranet');
		/** @var CIntranetSearchComponent $this */

		if ($_GET['structure_department'])
		{
			$_REQUEST[$this->arParams['FILTER_NAME'] . '_UF_DEPARTMENT'] = (int)$_GET['structure_department'];
			$_REQUEST['set_filter_' . $this->arParams['FILTER_NAME']]    = 'Y';
		}
		$this->includeComponentTemplate();

		return;
	}

	/**
	 * @return CAllMain
	 */
	public function getApplication()
	{
		global $APPLICATION;

		return $APPLICATION;
	}
}