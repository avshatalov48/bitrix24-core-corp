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
		$this->loadUserPropertyFromSocialNetwork($arParams);

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
		$arParams['LIST_URL']      = (!empty($arParams['LIST_URL']) ? $arParams['LIST_URL'] : $this->getApplication()->GetCurPage());
		if (!$arParams['DETAIL_URL'])
		{
			$arParams['DETAIL_URL'] = $arParams['LIST_URL'] . '?ID=#USER_ID#';
		}

		return parent::onPrepareComponentParams($arParams);
	}

	/**
	 * @param $arParams
	 */
	protected function loadUserPropertyFromSocialNetwork(&$arParams)
	{
		// process USER_PROPERTY_TABLE
		if (
			!isset($arParams['USER_PROPERTY_TABLE'])
			|| !is_array($arParams['USER_PROPERTY_TABLE'])
		)
		{
			$found = false;
			$val = \Bitrix\Main\Config\Option::get('socialnetwork', 'user_list_user_property_table', false, SITE_ID);
			if (!empty($val))
			{
				$val = unserialize($val, ['allowed_classes' => false]);
				if (
					is_array($val)
					&& !empty($val)
				)
				{
					$found = true;
					$arParams['USER_PROPERTY_TABLE'] = $val;
				}
			}
			if (!$found)
			{
				$arParams['USER_PROPERTY_TABLE'] = (
					\Bitrix\Main\Loader::includeModule('extranet')
					&& CExtranet::isExtranetSite()
						? (
							!empty($arParams['EXTRANET_TYPE'])
							&& $arParams['EXTRANET_TYPE'] == "employees"
								? [
									0 => "PERSONAL_PHOTO",
									1 => "FULL_NAME",
									2 => "PERSONAL_PHONE",
									3 => "WORK_POSITION",
									4 => "UF_DEPARTMENT",
								]
								: [
									0 => "PERSONAL_PHOTO",
									1 => "FULL_NAME",
									2 => "PERSONAL_PHONE",
									3 => "PERSONAL_CITY",
									4 => "PERSONAL_COUNTRY",
									5 => "WORK_POSITION",
									6 => "WORK_COMPANY",
								]
						)
						: [
							0	=>	"PERSONAL_PHOTO",
							1	=>	"FULL_NAME",
							2	=>	"WORK_POSITION",
							3	=>	"WORK_PHONE",
							4	=>	"UF_DEPARTMENT",
							5 	=> 	"UF_PHONE_INNER",
							6	=> 	"UF_SKYPE",
						]
				);
			}
		}
		else
		{
			\Bitrix\Main\Config\Option::set('socialnetwork', 'user_list_user_property_table', serialize($arParams['USER_PROPERTY_TABLE']), SITE_ID);
		}

		// process USER_PROPERTY_EXCEL
		if (
			!isset($arParams['USER_PROPERTY_EXCEL'])
			|| !is_array($arParams['USER_PROPERTY_EXCEL'])
		)
		{
			$found = false;
			$val = \Bitrix\Main\Config\Option::get('socialnetwork', 'user_list_user_property_excel', false, SITE_ID);
			if (!empty($val))
			{
				$val = unserialize($val, ['allowed_classes' => false]);
				if (
					is_array($val)
					&& !empty($val)
				)
				{
					$found = true;
					$arParams['USER_PROPERTY_EXCEL'] = $val;
				}
			}
			if (!$found)
			{
				$arParams['USER_PROPERTY_EXCEL'] = (
					\Bitrix\Main\Loader::includeModule('extranet')
					&& CExtranet::isExtranetSite()
						? (
							!empty($arParams['EXTRANET_TYPE'])
							&& $arParams['EXTRANET_TYPE'] == "employees"
								? [
									0 => "FULL_NAME",
									1 => "EMAIL",
									2 => "PERSONAL_PHONE",
									3 => "PERSONAL_FAX",
									4 => "PERSONAL_MOBILE",
									5 => "WORK_POSITION",
									6 => "UF_DEPARTMENT",
								]
								: [
									0 => "FULL_NAME",
									1 => "EMAIL",
									2 => "PERSONAL_PHONE",
									3 => "PERSONAL_FAX",
									4 => "PERSONAL_MOBILE",
									5 => "WORK_POSITION",
									6 => "WORK_COMPANY",
								]
						)
						: [
							0 => "FULL_NAME",
							1 => "EMAIL",
							2 => "PERSONAL_MOBILE",
							3 => "WORK_PHONE",
							4 => "WORK_POSITION",
							5 => "UF_DEPARTMENT",
							6 => "UF_PHONE_INNER",
							7 => "UF_SKYPE",
						]
				);
			}
		}
		else
		{
			\Bitrix\Main\Config\Option::set('socialnetwork', 'user_list_user_property_excel', serialize($arParams['USER_PROPERTY_EXCEL']), SITE_ID);
		}

		// process USER_PROPERTY_LIST
		if (
			!isset($arParams['USER_PROPERTY_LIST'])
			|| !is_array($arParams['USER_PROPERTY_LIST'])
		)
		{
			$found = false;
			$val = \Bitrix\Main\Config\Option::get('socialnetwork', 'user_list_user_property_list', false, SITE_ID);
			if (!empty($val))
			{
				$val = unserialize($val, ['allowed_classes' => false]);
				if (
					is_array($val)
					&& !empty($val)
				)
				{
					$found = true;
					$arParams['USER_PROPERTY_LIST'] = $val;
				}
			}
			if (!$found)
			{
				$arParams['USER_PROPERTY_LIST'] = (
					\Bitrix\Main\Loader::includeModule('extranet')
					&& CExtranet::isExtranetSite()
					? (
						!empty($arParams['EXTRANET_TYPE'])
						&& $arParams['EXTRANET_TYPE'] == "employees"
							? [
								0 => "EMAIL",
								1 => "PERSONAL_ICQ",
								2 => "PERSONAL_PHONE",
								3 => "PERSONAL_FAX",
								4 => "PERSONAL_MOBILE",
								5 => "UF_DEPARTMENT",
								6 => "PERSONAL_PHOTO",
							]
							: [
								0 => "EMAIL",
								1 => "PERSONAL_ICQ",
								2 => "PERSONAL_PHONE",
								3 => "PERSONAL_FAX",
								4 => "PERSONAL_MOBILE",
								5 => "PERSONAL_CITY",
								6 => "PERSONAL_COUNTRY",
								7 => "WORK_COMPANY",
								8 => "PERSONAL_PHOTO",
							]
					)
					: [
						0 => "EMAIL",
						1 => "PERSONAL_MOBILE",
						2 => "UF_SKYPE",
						3 => "WORK_PHONE",
						4 => "UF_PHONE_INNER",
						5 => "PERSONAL_PHOTO",
						6 => "UF_DEPARTMENT",
					]
				);
			}
		}
		else
		{
			\Bitrix\Main\Config\Option::set('socialnetwork', 'user_list_user_property_list', serialize($arParams['USER_PROPERTY_LIST']), SITE_ID);
		}

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
			$arParams["SHOW_FIELDS_TOOLTIP"] = unserialize(COption::GetOptionString("socialnetwork", "tooltip_fields", $arTooltipFieldsDefault), ["allowed_classes" => false]);
		}
		if (!array_key_exists("USER_PROPERTY_TOOLTIP", $this->arParams))
		{
			$arParams["USER_PROPERTY_TOOLTIP"] = unserialize(COption::GetOptionString("socialnetwork", "tooltip_properties", $arTooltipPropertiesDefault), ["allowed_classes" => false]);
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
		if ($filterName == '' || !preg_match("/^[A-Za-z_][A-Za-z0-9_]*$/", $filterName))
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