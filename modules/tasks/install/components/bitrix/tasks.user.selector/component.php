<?
if(!Defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
require_once($_SERVER["DOCUMENT_ROOT"].$componentPath."/functions.php");

if (!CModule::IncludeModule("tasks"))
{
	ShowError(GetMessage("TASKS_MODULE_NOT_FOUND"));
	return;
}

$arParams['MULTIPLE'] = $arParams['MULTIPLE'] == 'Y' ? 'Y' : 'N'; // allow multiple user selection

$arParams['FORM_NAME'] = isset($arParams['FORM_NAME']) && preg_match('/^[a-zA-Z0-9_-]+$/', $arParams['FORM_NAME']) ? $arParams['FORM_NAME'] : false;
$arParams['INPUT_NAME'] = isset($arParams['INPUT_NAME']) && preg_match('/^[a-zA-Z0-9_-]+$/', $arParams['INPUT_NAME']) ? $arParams['INPUT_NAME'] : false;
$arParams['IS_EXTRANET'] = isset($arParams['IS_EXTRANET']) && $arParams['IS_EXTRANET'] == 'Y' ? 'Y' : 'N';
$arParams['SITE_ID'] = isset($arParams['SITE_ID']) ? $arParams['SITE_ID'] : SITE_ID;
$arParams['GROUP_ID_FOR_SITE'] = intval($arParams['GROUP_ID_FOR_SITE']) > 0 ? $arParams['GROUP_ID_FOR_SITE'] : false;

if (isset($arParams['SHOW_INACTIVE_USERS']) && ($arParams['SHOW_INACTIVE_USERS'] === 'Y'))
	$arParams['SHOW_INACTIVE_USERS'] = 'Y';
else
	$arParams['SHOW_INACTIVE_USERS'] = 'N';

$arResult["NAME"] = htmlspecialcharsbx($arParams["NAME"]);
$arResult["~NAME"] = $arParams["NAME"];

if (strlen($arParams["NAME_TEMPLATE"]) <= 0)
	$arParams["NAME_TEMPLATE"] = CSite::GetNameFormat();

$arSubDeps = CTasks::GetSubordinateDeps();

$GLOBALS['GROUP_SITE_ID'] = $arParams['SITE_ID'];

if ($arParams["GROUP_ID_FOR_SITE"] && CModule::IncludeModule("extranet") && CModule::IncludeModule("socialnetwork"))
{
	$arSites = array();
	$rsGroupSite = CSocNetGroup::GetSite($arParams["GROUP_ID_FOR_SITE"]);
	while($arGroupSite = $rsGroupSite->Fetch())
		$arSites[] = $arGroupSite["LID"];

	$extranet_site_id = CExtranet::GetExtranetSiteID();
	if ($extranet_site_id && in_array(CExtranet::GetExtranetSiteID(), $arSites))
		$GLOBALS['GROUP_SITE_ID'] = $extranet_site_id;
}

$arManagers = array();
if (($arDepartments = CTasks::GetUserDepartments($USER->GetID())) && is_array($arDepartments) && count($arDepartments) > 0)
{
	$arManagers = array_keys(CTasks::GetDepartmentManagers($arDepartments, $USER->GetID()));
}

$bSubordinateOnly = (isset($arParams["SUBORDINATE_ONLY"]) && $arParams["SUBORDINATE_ONLY"] == "Y");

$IBlockID = COption::GetOptionInt('intranet', 'iblock_structure');
$arSecFilter = array('IBLOCK_ID' => $IBlockID);
if ($bSubordinateOnly)
{
	if (!$arSubDeps)
	{
		$arSubDeps = array(-1);
	}
	$arSecFilter["ID"] = $arSubDeps;
}

$obCache = new CPHPCache();

$arStructure = array();
$arSections = array();

if (!CModule::IncludeModule('extranet') || CExtranet::IsIntranetUser())
{
	if($obCache->InitCache(CTasksTools::CACHE_TTL_UNLIM, md5(serialize($arSecFilter)) . (string) $arParams['SHOW_INACTIVE_USERS'], "/tasks/subordinatedeps"))
	{
		$vars = $obCache->GetVars();
		$arSections = $vars["SECTIONS"];
		$arStructure = $vars["STRUCTURE"];
	}
	elseif ($obCache->StartDataCache())
	{
		if(CModule::IncludeModule('iblock'))
		{
			global $CACHE_MANAGER;
			$CACHE_MANAGER->StartTagCache($cacheDir);
			$CACHE_MANAGER->RegisterTag("iblock_id_".$IBlockID);

			$dbRes = CIBlockSection::GetList(
				array('left_margin' => 'asc'), 		// order as full expanded tree
				$arSecFilter,
				false, 								// don't count
				array('ID', 'NAME', 'IBLOCK_SECTION_ID')	// fields to be selected
			);

			while ($arRes = $dbRes->Fetch())
			{
				$iblockSectionID = intval($arRes['IBLOCK_SECTION_ID']);
				if ($bSubordinateOnly && !in_array($iblockSectionID, $arSubDeps))
				{
					$iblockSectionID = 0;
				}

				if (!is_array($arStructure[$iblockSectionID]))
					$arStructure[$iblockSectionID] = array($arRes['ID']);
				else
					$arStructure[$iblockSectionID][] = $arRes['ID'];

				$arSections[$arRes['ID']] = $arRes;
			}
			$CACHE_MANAGER->EndTagCache();
			$obCache->EndDataCache(array("SECTIONS" => $arSections, "STRUCTURE" => $arStructure));
		}
		else
		{
			$obCache->AbortDataCache();
		}
	}
}

if (CModule::IncludeModule('extranet'))
{
	$arStructure[0][] = "extranet";
	$arSections["extranet"] = array("ID" => "extranet", "NAME" => GetMessage("TASKS_EMP_EXTRANET"));
}

$arResult["STRUCTURE"] = $arStructure;
$arResult["SECTIONS"] = $arSections;

//last selected users
$arResult["LAST_USERS"] = tasksGetLastSelected($arManagers, $bSubordinateOnly, $arParams["NAME_TEMPLATE"]);

$arResult["LAST_USERS_IDS"] = is_array($arResult["LAST_USERS"]) ? array_slice(array_keys($arResult["LAST_USERS"]), 0, 10) : array();
$arResult['ROOT_DEP_USER'] = CUtil::PhpToJsObject(TasksGetDepartmentUsers($arResult["STRUCTURE"][0][0], $arParams['SITE_ID'], $arSubDeps, $arManagers, $arParams['SHOW_INACTIVE_USERS'], $arParams["NAME_TEMPLATE"]));

// current users
if (!is_array($arParams['VALUE']))
	$arParams['VALUE'] = explode(',', $arParams['VALUE']);

foreach ($arParams['VALUE'] as $key => $ID)
	$arParams['VALUE'][$key] = intval(trim($ID));

$arParams['VALUE'] = array_unique(array_filter($arParams['VALUE']));

$arResult["CURRENT_USERS"] = array();
if (sizeof($arParams["VALUE"]))
{
	$arListedUsers = array();
	$arFilter['!UF_DEPARTMENT'] = false;
	$arFilter['ID'] = implode('|', $arParams['VALUE']);

	// Prevent using users, that doesn't activate it's account
	// http://jabber.bx/view.php?id=29118
	if (IsModuleInstalled('bitrix24'))
		$arFilter['!LAST_LOGIN'] = false;

	$dbRes = CUser::GetList($by = 'last_name', $order = 'asc', $arFilter, array('SELECT' => array('UF_DEPARTMENT')));

	while ($arRes = $dbRes->GetNext())
		$arListedUsers[] = $arRes;

	if (CModule::IncludeModule('extranet') && method_exists('CExtranet', 'GetExtranetUserGroupID'))
	{
		$rc = CExtranet::GetExtranetUserGroupID();
		if ($rc !== false)
		{
			$arExtranetGroups = array((int) $rc);
			$arFilterExtranetUsers = array(
				'ID'        => $arFilter['ID'],
				'GROUPS_ID' => $arExtranetGroups
				);

			// Prevent using users, that doesn't activate it's account
			// http://jabber.bx/view.php?id=29118
			if (IsModuleInstalled('bitrix24'))
				$arFilterExtranetUsers['!LAST_LOGIN'] = false;

			$dbRes = CUser::GetList($by = 'last_name', $order = 'asc', $arFilterExtranetUsers, array('SELECT' => array('UF_DEPARTMENT')));
			while ($arRes = $dbRes->GetNext())
				$arListedUsers[] = $arRes;
		}
	}

	$arListedUniqueUsers = array();
	$arAlreadyPushedUsersIds = array();
	foreach ($arListedUsers as $arUserData)
	{
		if ( in_array( (int) $arUserData['ID'], $arAlreadyPushedUsersIds, true) )
			continue;	// skip already pushed users

		$arListedUniqueUsers[] = $arUserData;
		$arAlreadyPushedUsersIds[] = (int) $arUserData['ID'];
	}

	foreach ($arListedUniqueUsers as $arRes)
	{
		$arPhoto = array('IMG' => '');

		if (!$arRes['PERSONAL_PHOTO'])
		{
			switch ($arRes['PERSONAL_GENDER'])
			{
				case "M":
					$suffix = "male";
					break;
				case "F":
					$suffix = "female";
					break;
				default:
					$suffix = "unknown";
			}
			$arRes['PERSONAL_PHOTO'] = COption::GetOptionInt("socialnetwork", "default_user_picture_".$suffix, false, $arParams['SITE_ID']);
		}

		if ($arRes['PERSONAL_PHOTO'] > 0)
			$arPhoto = CIntranetUtils::InitImage($arRes['PERSONAL_PHOTO'], 30, 0, BX_RESIZE_IMAGE_EXACT);

		$arResult["CURRENT_USERS"][] = array(
			'ID' => $arRes['ID'],
			'NAME' => CUser::FormatName($arParams["NAME_TEMPLATE"], $arRes, true, false),
			'~NAME' => CUser::FormatName($arParams["NAME_TEMPLATE"], array("NAME" => $arRes["~NAME"], "LAST_NAME" => $arRes["~LAST_NAME"], "LOGIN" => $arRes["~LOGIN"], "SECOND_NAME" => $arRes["~SECOND_NAME"]), true, false),
			'LOGIN' => $arRes['LOGIN'],
			'EMAIL' => $arRes['EMAIL'],
			'WORK_POSITION' => htmlspecialcharsBack($arRes['WORK_POSITION'] ? $arRes['WORK_POSITION'] : $arRes['PERSONAL_PROFESSION']),
			'PHOTO' => isset($arPhoto['CACHE']['src']) ? $arPhoto['CACHE']['src'] : "",
			'HEAD' => false,
			'SUBORDINATE' => is_array($arSubDeps) && is_array($arRes['UF_DEPARTMENT']) && array_intersect($arRes['UF_DEPARTMENT'], $arSubDeps) ? 'Y' : 'N',
			'SUPERORDINATE' => in_array($arRes["ID"], $arManagers) ? 'Y' : 'N'
		);
	}
}

$arResult["CURRENT_USERS"] = array_values(array_filter($arResult["CURRENT_USERS"], "FilterViewableUsers"));
$arResult["LAST_USERS"] = array_values(array_filter($arResult["LAST_USERS"], "FilterViewableUsers"));

$APPLICATION->AddHeadScript($this->GetPath().'/templates/.default/users.js');

$this->IncludeComponentTemplate();
?>