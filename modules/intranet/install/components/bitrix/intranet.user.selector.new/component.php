<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule("intranet"))
{
	ShowError(GetMessage("INTRANET_MODULE_NOT_FOUND"));

	return;
}

/** @var CAllUser $USER */
/** @var CBitrixComponent $this */
/** @var CCacheManager $CACHE_MANAGER */
/** @var array $arParams */
/** @var array $arResult */

require_once('functions.php');

initBvar($arParams['MULTIPLE']); //allow multiple user selection
initBvar($arParams['IS_EXTRANET']);
initBvar($arParams['SHOW_INACTIVE_USERS']);

$arParams['FORM_NAME']           = isset($arParams['FORM_NAME']) && preg_match('/^[a-zA-Z0-9_-]+$/', $arParams['FORM_NAME']) ? $arParams['FORM_NAME'] : false;
$arParams['INPUT_NAME']          = isset($arParams['INPUT_NAME']) && preg_match('/^[a-zA-Z0-9_-]+$/', $arParams['INPUT_NAME']) ? $arParams['INPUT_NAME'] : false;
$arParams['SHOW_EXTRANET_USERS'] = empty($arParams['SHOW_EXTRANET_USERS']) ? "ALL" : $arParams["SHOW_EXTRANET_USERS"];
$arParams['SITE_ID']             = isset($arParams['SITE_ID']) ? $arParams['SITE_ID'] : SITE_ID;
$arParams['GROUP_SITE_ID']       = isset($arParams['GROUP_SITE_ID']) ? $arParams['GROUP_SITE_ID'] : SITE_ID;
$arParams['GROUP_ID_FOR_SITE']   = intval($arParams['GROUP_ID_FOR_SITE']) > 0 ? intval($arParams['GROUP_ID_FOR_SITE']) : false;
$arParams["SHOW_LOGIN"]          = isset($arParams["SHOW_LOGIN"]) && $arParams["SHOW_LOGIN"] != 'N' ? 'Y' : 'N';
$arParams["DISPLAY_TAB_STRUCTURE"] = isset($arParams["DISPLAY_TAB_STRUCTURE"]) && $arParams["DISPLAY_TAB_STRUCTURE"] != 'N' ? 'N' : 'Y';
$arParams["DISPLAY_TABS"]        = !isset($arParams["SHOW_STRUCTURE_ONLY"]) || $arParams["SHOW_STRUCTURE_ONLY"] != 'Y' ? 'Y' : 'N';
$arParams["SHOW_USERS"]        = isset($arParams["SHOW_STRUCTURE_ONLY"]) && $arParams["SHOW_STRUCTURE_ONLY"] == 'Y' ? 'N' : 'Y';
initBVar($arParams["DISPLAY_TAB_GROUP"]);

// current users
$arParams['VALUE'] = !empty($arParams['VALUE']) ? $arParams['VALUE'] : array();
if (!is_array($arParams['VALUE']))
{
	$arParams['VALUE'] = explode(',', $arParams['VALUE']);
}
foreach ($arParams['VALUE'] as &$id)
{
	$id = intval(trim($id));
}
unset($id);
$arParams['VALUE'] = array_unique($arParams['VALUE']);

$GLOBALS['GROUP_SITE_ID'] = $arParams['GROUP_SITE_ID'];
$bSubordinateOnly         = (isset($arParams["SUBORDINATE_ONLY"]) && $arParams["SUBORDINATE_ONLY"] == "Y");

$arResult["NAME"]  = htmlspecialcharsbx($arParams["NAME"]);
$arResult["~NAME"] = $arParams["NAME"];

if (!isset($arParams["NAME_TEMPLATE"]) || strlen($arParams["NAME_TEMPLATE"]) <= 0)
{
	$arParams["NAME_TEMPLATE"] = CSite::GetNameFormat();
}

$arSubDeps = CIntranetUtils::getSubordinateDepartments($USER->GetID(), true);
if ($arParams["GROUP_ID_FOR_SITE"] && CModule::IncludeModule("extranet") && CModule::IncludeModule("socialnetwork"))
{
	$arSites     = array();
	$rsGroupSite = CSocNetGroup::GetSite($arParams["GROUP_ID_FOR_SITE"]);
	while ($arGroupSite = $rsGroupSite->Fetch())
		$arSites[] = $arGroupSite["LID"];

	$extranetSiteId = CExtranet::GetExtranetSiteID();
	if ($extranetSiteId && in_array($extranetSiteId, $arSites))
	{
		$GLOBALS['GROUP_SITE_ID'] = $extranetSiteId;
	}
}

$arManagers = array();
if (($arDepartments = CIntranetUtils::getUserDepartments($USER->GetID())) && is_array($arDepartments) && count($arDepartments) > 0)
{
	$arManagers = array_keys(CIntranetUserSelectorHelper::getDepartmentManagersId($arDepartments, $USER->getID(), true));
}

$iBlockId    = COption::GetOptionInt('intranet', 'iblock_structure');
$arSecFilter = array('IBLOCK_ID' => $iBlockId);
if ($bSubordinateOnly)
{
	if (!$arSubDeps)
	{
		$arSubDeps = array(-1);
	}
	$arSecFilter["ID"] = $arSubDeps;
}

$arStructure = $arSections = array();
if ($arParams["DISPLAY_TAB_STRUCTURE"] == 'Y' && (!CModule::IncludeModule('extranet') || CExtranet::IsIntranetUser()) )
{
	$arStructure = CIntranetUtils::getSubStructure(0, 1);
	$arSections  = $arStructure['DATA'];
	$arStructure = $arStructure['TREE'];

	if($bSubordinateOnly)
	{
		$arStructure = array();
		foreach ($arSections as $k => $item)
		{
			$iblockSectionId = (int)$item['IBLOCK_SECTION_ID'];
			if( ($isSub = !in_array($iblockSectionId, $arSubDeps)) && !in_array($item['ID'], $arSubDeps))
			{
				unset($arSections[$k]);
				continue;
			}

			if($isSub)
			{
				$iblockSectionId = 0;
			}

			if(!isset($arStructure[$iblockSectionId]))
			{
				$arStructure[$iblockSectionId] = array();
			}
			$arStructure[$iblockSectionId][] = $item['ID'];
		}
		unset($item);
	}
}

if (!$bSubordinateOnly && $arParams["SHOW_EXTRANET_USERS"] != "NONE" && CModule::IncludeModule('extranet'))
{
	$arStructure[0][]       = "extranet";
	$arSections["extranet"] = array(
		"ID" => "extranet",
		"NAME" => GetMessage("INTRANET_EMP_EXTRANET"),
	);
}

$arResult["STRUCTURE"] = $arStructure;
$arResult["SECTIONS"]  = $arSections;

//last selected users
$arResult["LAST_USERS"] = CIntranetUserSelectorHelper::getLastSelectedUsers($arManagers, $bSubordinateOnly, $arParams["NAME_TEMPLATE"], $arParams['SITE_ID']);
$arResult["LAST_USERS_IDS"] = !empty($arResult["LAST_USERS"]) ? array_keys(array_slice($arResult["LAST_USERS"], 0, 10, true)) : array();
$arResult["CURRENT_USERS"]  = array();
if (count($arParams["VALUE"]))
{
	$arFilter['!UF_DEPARTMENT'] = false;
	$arFilter['ID']             = implode('|', $arParams['VALUE']);
	$notSelectedUsersId         = $arParams['VALUE'];

	// Prevent using users, that doesn't activate it's account
	// http://jabber.bx/view.php?id=29118
	if (IsModuleInstalled('bitrix24'))
	{
		$arFilter['CONFIRM_CODE'] = false;
	}

	$dbRes = CUser::GetList($by = 'last_name', $order = 'asc', $arFilter, array('SELECT' => array('UF_DEPARTMENT')));
	while ($arRes = $dbRes->GetNext())
	{
		if(($key = array_search($arRes['ID'], $notSelectedUsersId)) !== false)
		{
			//if user already selected, then we not select from extranet
			unset($notSelectedUsersId[$key]);
		}

		$arResult["CURRENT_USERS"][] = array(
			'ID' => $arRes['ID'],
			'NAME' => CUser::FormatName($arParams["NAME_TEMPLATE"], $arRes, true, false),
			'~NAME' => CUser::FormatName($arParams["NAME_TEMPLATE"], array(
				"NAME" => $arRes["~NAME"],
				"LAST_NAME" => $arRes["~LAST_NAME"],
				"LOGIN" => $arRes["~LOGIN"],
				"SECOND_NAME" => $arRes["~SECOND_NAME"]
			), true, false),
			'LOGIN' => $arRes['LOGIN'],
			'EMAIL' => $arRes['EMAIL'],
			'WORK_POSITION' => $arRes['WORK_POSITION'] ? $arRes['WORK_POSITION'] : $arRes['PERSONAL_PROFESSION'],
			'~WORK_POSITION' => $arRes['~WORK_POSITION'] ? $arRes['~WORK_POSITION'] : $arRes['~PERSONAL_PROFESSION'],
			'PHOTO' => (string)CIntranetUtils::createAvatar($arRes, array(), $arParams['SITE_ID']),
			'HEAD' => false,
			'SUBORDINATE' => is_array($arSubDeps) && is_array($arRes['UF_DEPARTMENT']) && array_intersect($arRes['UF_DEPARTMENT'], $arSubDeps) ? 'Y' : 'N',
			'SUPERORDINATE' => in_array($arRes["ID"], $arManagers) ? 'Y' : 'N'
		);
	}

	if ($notSelectedUsersId && CModule::IncludeModule('extranet'))
	{
		foreach (CIntranetUserSelectorHelper::getExtranetUsers(implode('|', $notSelectedUsersId)) as $arRes)
		{
			$arResult["CURRENT_USERS"][] = array(
				'ID' => $arRes['ID'],
				'NAME' => CUser::FormatName($arParams["NAME_TEMPLATE"], $arRes, true, false),
				'~NAME' => CUser::FormatName($arParams["NAME_TEMPLATE"], array(
					"NAME" => $arRes["~NAME"],
					"LAST_NAME" => $arRes["~LAST_NAME"],
					"LOGIN" => $arRes["~LOGIN"],
					"SECOND_NAME" => $arRes["~SECOND_NAME"]
				), true, false),
				'LOGIN' => $arRes['LOGIN'],
				'EMAIL' => $arRes['EMAIL'],
				'WORK_POSITION' => $arRes['WORK_POSITION'] ? $arRes['WORK_POSITION'] : $arRes['PERSONAL_PROFESSION'],
				'~WORK_POSITION' => $arRes['~WORK_POSITION'] ? $arRes['~WORK_POSITION'] : $arRes['~PERSONAL_PROFESSION'],
				'PHOTO' => (string)CIntranetUtils::createAvatar($arRes, array(), $arParams['SITE_ID']),
				'HEAD' => false,
				'SUBORDINATE' => is_array($arSubDeps) && is_array($arRes['UF_DEPARTMENT']) && array_intersect($arRes['UF_DEPARTMENT'], $arSubDeps) ? 'Y' : 'N',
				'SUPERORDINATE' => in_array($arRes["ID"], $arManagers) ? 'Y' : 'N'
			);
		}
		unset($arRes);
	}
}
$arResult['FIXED_USERS'] = isset($arParams['FIXED_USERS']) && is_array($arParams['FIXED_USERS']) ? $arParams['FIXED_USERS'] : array();
$groups = array();
if($arParams["DISPLAY_TAB_GROUP"] == 'Y')
{
	$groups = CIntranetUserSelectorHelper::getUserGroups($USER->GetID());
}

$arResult["GROUPS"] = $groups;
$arResult["CURRENT_USERS"] = array_values(array_filter($arResult["CURRENT_USERS"], array('CIntranetUserSelectorHelper', 'filterViewableUsers')));
$arResult["LAST_USERS"]    = array_values(array_filter($arResult["LAST_USERS"], array('CIntranetUserSelectorHelper', 'filterViewableUsers')));

Bitrix\Main\UI\Extension::load("ui.tooltip");
$APPLICATION->addHeadScript('/bitrix/js/main/utils.js');

$this->IncludeComponentTemplate();