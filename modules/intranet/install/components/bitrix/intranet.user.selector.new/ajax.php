<?php

define('STOP_STATISTICS', true);
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);
define('BX_SECURITY_SHOW_MESSAGE', true);


//extranet users visibility
const SHOW_ALL = 1;
const SHOW_FROM_MY_GROUPS = 2;
const SHOW_NONE = 3;
const SHOW_FROM_EXACT_GROUP = 4;

/** @var CAllUser $USER */
/** @var CCacheManager $CACHE_MANAGER */

$SITE_ID = '';
if (isset($_GET["SITE_ID"]) && is_string($_GET['SITE_ID']))
	$SITE_ID = substr(preg_replace("/[^a-z0-9_]/i", "", $_GET["SITE_ID"]), 0, 2);

if($SITE_ID != '')
	define("SITE_ID", $SITE_ID);

$showUsers = (isset($_GET["SHOW_USERS"]) && $_GET["SHOW_USERS"] == "N" ? false : true);

$showExtranetUsers = SHOW_ALL;

if(!isset($_GET["SHOW_EXTRANET_USERS"]) || $_GET["SHOW_EXTRANET_USERS"] == "ALL")
{
	$showExtranetUsers = SHOW_ALL;
}
elseif ($_GET["SHOW_EXTRANET_USERS"] == "FROM_MY_GROUPS") //used when inviting to groups
{
	$showExtranetUsers = SHOW_FROM_MY_GROUPS;
}
elseif ($_GET["SHOW_EXTRANET_USERS"] == "FROM_EXACT_GROUP") //used in calendars
{
	if (isset($_GET["EX_GROUP"]) && intval($_GET["EX_GROUP"]) > 0)
	{
		$showExtranetUsers = SHOW_FROM_EXACT_GROUP;
		$exGroupID = intval($_GET["EX_GROUP"]);
	}
	else
		$showExtranetUsers = SHOW_NONE;
}
elseif ($_GET["SHOW_EXTRANET_USERS"] == "NONE")
{
	$showExtranetUsers = SHOW_NONE;
}

if (isset($_GET["GROUP_SITE_ID"]) && is_string($_GET["GROUP_SITE_ID"]))
	$GLOBALS["GROUP_SITE_ID"] = substr(preg_replace("/[^a-z0-9_]/i", "", $_GET["GROUP_SITE_ID"]), 0, 2);
elseif($SITE_ID != '')
	$GLOBALS["GROUP_SITE_ID"] = $SITE_ID;

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
require_once("functions.php");

CModule::IncludeModule('intranet');

if (!$USER->IsAuthorized())
	die;

__IncludeLang(dirname(__FILE__).'/lang/'.LANGUAGE_ID.'/'.basename(__FILE__));


if (isset($_REQUEST["nt"]))
{
	//todo not so good
	preg_match_all("/(#NAME#)|(#NOBR#)|(#\/NOBR#)|(#LAST_NAME#)|(#SECOND_NAME#)|(#NAME_SHORT#)|(#SECOND_NAME_SHORT#)|\s|\,/", urldecode($_REQUEST["nt"]), $matches);
	$nameTemplate = str_replace("#COMMA#", ',', implode("", $matches[0]));
}
else
{
	$nameTemplate = CSite::GetNameFormat(false);
}
$showActiveUsers  = isset($_REQUEST['SHOW_INACTIVE_USERS']) && $_REQUEST['SHOW_INACTIVE_USERS'] == "Y"? '' : 'Y';
$bSubordinateOnly = isset($_REQUEST["S_ONLY"]) && $_REQUEST["S_ONLY"] == "Y";
$bUseLogin        = !(isset($_REQUEST["sl"]) && $_REQUEST["sl"] == "N");
$sectionId        = $_REQUEST['SECTION_ID'];

$arSubDeps = CIntranetUtils::getSubordinateDepartments($USER->GetID(), true);
$arManagers = array();
if (($arDepartments = CIntranetUtils::getUserDepartments($USER->GetID())) && is_array($arDepartments) && count($arDepartments) > 0)
{
	$arManagers = array_keys(CIntranetUserSelectorHelper::getDepartmentManagersId($arDepartments, $USER->getID(), true));
}


if (empty($_REQUEST['GROUP_ID']) && $_REQUEST['MODE'] == 'EMPLOYEES'
	&& (!CModule::IncludeModule('extranet') || CExtranet::IsIntranetUser() || $sectionId == 'extranet'))
{
	if ($sectionId != 'extranet')
		$sectionId = intval($sectionId);

	$arFilter = array(
		'ACTIVE' => $showActiveUsers
	);

	if($sectionId == "extranet")
	{
		$arFilter['GROUPS_ID'] = array(COption::GetOptionInt("extranet", "extranet_group", ""));
		$arFilter['UF_DEPARTMENT'] = false;

		$arExternalAuthId = array();
		if (IsModuleInstalled('socialservices'))
		{
			$arExternalAuthId[] = 'replica';
		}
		if (IsModuleInstalled('mail'))
		{
			$arExternalAuthId[] = 'email';
		}
		if (IsModuleInstalled('im'))
		{
			$arExternalAuthId[] = 'bot';
		}
		if (IsModuleInstalled('imconnector'))
		{
			$arExternalAuthId[] = 'imconnector';
		}
		if (!empty($arExternalAuthId))
		{
			$arFilter["!=EXTERNAL_AUTH_ID"] = $arExternalAuthId;
		}

		if (CModule::IncludeModule("extranet"))
		{
			if ($showExtranetUsers == SHOW_FROM_MY_GROUPS)
			{
				$arFilteredUserIDs = CExtranet::GetMyGroupsUsersSimple(CExtranet::GetExtranetSiteID());
			}
			elseif ($showExtranetUsers == SHOW_FROM_EXACT_GROUP)
			{
				if (CModule::IncludeModule("socialnetwork"))
				{
					$dbUsers = CSocNetUserToGroup::GetList(
						array(),
						array(
							"GROUP_ID"    => array($exGroupID),
							"<=ROLE"      => SONET_ROLES_USER,
							"USER_ACTIVE" => "Y"
						),
						false,
						false,
						array("ID", "USER_ID")
					);

					if ($dbUsers)
						while ($arUser = $dbUsers->GetNext())
							$arFilteredUserIDs[] = $arUser["USER_ID"];
				}
			}
		}
	}
	else
	{
		$arStructure = CIntranetUtils::getSubStructure($sectionId, 1);

		if (!empty($arStructure['TREE']))
		{
			if ($bSubordinateOnly)
			{
				$arStructure['TREE'] = array();

				foreach ($arStructure['DATA'] as $k => $item)
				{
					$iblockSectionId = (int) $item['IBLOCK_SECTION_ID'];
					if (($isSub = !in_array($iblockSectionId, $arSubDeps)) && !in_array($item['ID'], $arSubDeps))
					{
						unset($arStructure['DATA'][$k]);
						continue;
					}

					if ($isSub)
						$iblockSectionId = 0;

					if (!isset($arStructure['TREE'][$iblockSectionId]))
						$arStructure['TREE'][$iblockSectionId] = array();

					$arStructure['TREE'][$iblockSectionId][] = $item['ID'];
				}
			}
			CIntranetUserSelectorHelper::drawEmployeeStructure($arStructure['TREE'], $arStructure['DATA'], $sectionId, $selectorName, !$showUsers);
		}

		$arFilter['UF_DEPARTMENT'] = $sectionId;
	}

	$arUsers = array();
	if ($showUsers)
	{
		$arFilter["CONFIRM_CODE"] = false;

		if ($sectionId != "extranet")
		{
			$ufHead = CIntranetUtils::getDepartmentManagerID($sectionId);
			if ($ufHead > 0)
			{
				$arHeadFilter = array(
					'ID' => $ufHead,
					'ACTIVE' => $showActiveUsers,
					'CONFIRM_CODE' => false
				);

				$dbUsers = CUser::GetList(
					$sort_by = 'last_name', $sort_dir = 'asc',
					$arHeadFilter,
					array('SELECT' => array('UF_DEPARTMENT'))
				);

				if ($arRes = $dbUsers->Fetch())
				{
					$arFilter['!ID'] = $arRes['ID'];
					$arUsers[] = array(
						'ID'            => $arRes['ID'],
						'NAME'          => CUser::FormatName($nameTemplate, $arRes, $bUseLogin, false),
						'LOGIN'         => $arRes['LOGIN'],
						'EMAIL'         => $arRes['EMAIL'],
						'WORK_POSITION' => $arRes['WORK_POSITION'] ? $arRes['WORK_POSITION'] : $arRes['PERSONAL_PROFESSION'],
						'PHOTO'         => (string)CIntranetUtils::createAvatar($arRes, array()),
						'HEAD'          => true,
						'UF_DEPARTMENT' => $arRes['UF_DEPARTMENT'],
						'SUBORDINATE'   => is_array($arSubDeps) && is_array($arRes['UF_DEPARTMENT']) && array_intersect($arRes['UF_DEPARTMENT'], $arSubDeps) ? 'Y' : 'N',
						'SUPERORDINATE' => in_array($arRes["ID"], $arManagers) ? 'Y' : 'N'
					);
				}
			}
		}

		$dbRes = CUser::GetList($by = 'last_name', $order = 'asc', $arFilter, array('SELECT' => array('UF_DEPARTMENT')));
		while ($arRes = $dbRes->Fetch())
		{
			//exclude extranet users in accordance with SHOW_EXTRANET_USER parameter
			if (
				($showExtranetUsers == SHOW_FROM_MY_GROUPS || $showExtranetUsers == SHOW_FROM_EXACT_GROUP)
				&& $arRes["UF_DEPARTMENT"] == false
				&& !in_array($arRes["ID"], $arFilteredUserIDs)
			)
				continue;

			$arUsers[] = array(
				'ID'            => $arRes['ID'],
				'NAME'          => CUser::FormatName($nameTemplate, $arRes, $bUseLogin, false),
				'LOGIN'         => $arRes['LOGIN'],
				'EMAIL'         => $arRes['EMAIL'],
				'WORK_POSITION' => $arRes['WORK_POSITION'] ? $arRes['WORK_POSITION'] : $arRes['PERSONAL_PROFESSION'],
				'PHOTO'         => (string)CIntranetUtils::createAvatar($arRes, array()),
				'HEAD'          => false,
				'UF_DEPARTMENT' => $arRes['UF_DEPARTMENT'],
				'SUBORDINATE'   => is_array($arSubDeps) && is_array($arRes['UF_DEPARTMENT']) && array_intersect($arRes['UF_DEPARTMENT'], $arSubDeps) ? 'Y' : 'N',
				'SUPERORDINATE' => in_array($arRes["ID"], $arManagers) ? 'Y' : 'N'
			);
		}
	}

	$APPLICATION->RestartBuffer();
	Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
	echo CUtil::PhpToJsObject(array(
		'STRUCTURE' => empty($arStructure['DATA']) ? array() : array_values($arStructure['DATA']),
		'USERS' => array_values(array_filter($arUsers, array('CIntranetUserSelectorHelper', 'filterViewableUsers')))
	));
	die;
}
elseif($groupId = (int)$_REQUEST['GROUP_ID'])
{
	if(!CModule::IncludeModule("socialnetwork"))
	{
		$APPLICATION->RestartBuffer();
		Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
		echo CUtil::PhpToJsObject(array());
		die;
	}
	$userGroupFilter = array(
		'GROUP_ID' => $groupId,
		'<=ROLE' => SONET_ROLES_USER,
		'USER_ACTIVE' => 'Y',
	);

	$dbUserGroups = CSocNetUserToGroup::GetList(array("GROUP_NAME" => "ASC"), $userGroupFilter, false, false,
		array('USER_ID', 'USER_NAME', 'USER_LAST_NAME', 'USER_SECOND_NAME', 'USER_LOGIN', 'USER_PERSONAL_PHOTO', 'USER_PERSONAL_GENDER', 'USER_LOGIN', 'USER_WORK_POSITION'));
	$groups = array();
	while($row = $dbUserGroups->GetNext())
	{
		$groups[] = array(
			'ID' => $row['USER_ID'],
			'LOGIN' => $row['USER_LOGIN'],
			'EMAIL' => $row['USER_EMAIL'],
			'WORK_POSITION' => $row['USER_WORK_POSITION'],
			'NAME' => CUser::FormatName($nameTemplate, array(
				"NAME" => $row["~USER_NAME"],
				"LAST_NAME" => $row["~USER_LAST_NAME"],
				"LOGIN" => $row["~USER_LOGIN"],
				"SECOND_NAME" => $row["~USER_SECOND_NAME"]
			), true, false),
			'PHOTO' => (string)CIntranetUtils::createAvatar(
				array(
					'PERSONAL_PHOTO' => $row['USER_PERSONAL_PHOTO'],
					'PERSONAL_GENDER' => $row['USER_PERSONAL_GENDER']
				),
				array()
			),
		);
	}
	$APPLICATION->RestartBuffer();
	Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
	//echo CUtil::PhpToJsObject($groups);
	echo CUtil::PhpToJsObject(array_values(array_filter($groups, array('CIntranetUserSelectorHelper', 'filterViewableUsers'))));
	die;
}
elseif ($_REQUEST['MODE'] == 'SEARCH')
{
	CUtil::JSPostUnescape();
	$APPLICATION->RestartBuffer();
	$search = $_REQUEST['SEARCH_STRING'];
	$arUsers = array();

	if (GetFilterQuery("TEST", $search))
	{
		$arSearch = preg_split('/\s+/', trim($search), ($words_limit = 10)+1);
		unset($arSearch[$words_limit]);

		$sortExpr  = '0';
		$sqlHelper = \Bitrix\Main\Application::getConnection()->getSqlHelper();
		foreach ($arSearch as $word)
		{
			$word = str_replace('%', '%%', $word);
			$word = $sqlHelper->forSql($word);

			$sortExpr .= sprintf(
				'+(CASE WHEN %s THEN 3 WHEN %s THEN 2 WHEN %s THEN 1 ELSE 0 END)',
				"(%1\$s LIKE '%%" . $word . "%%')",
				"(%2\$s LIKE '%%" . $word . "%%')",
				"(%3\$s LIKE '%%" . $word . "%%')"
			);
		}

		$sortWeight = new \Bitrix\Main\Entity\ExpressionField('SORT_WEIGHT', $sortExpr, array('LAST_NAME', 'NAME', 'SECOND_NAME'));

		$arFilter = array(
			array(
				'LOGIC' => 'OR',
				'%NAME' => $arSearch,
				'%LAST_NAME' => $arSearch,
				'%SECOND_NAME' => $arSearch,
				'%EMAIL' => $search,
				'%LOGIN' => $search
			)
		);
		
		if ($showActiveUsers == 'Y')
		{
			$arFilter['ACTIVE'] = 'Y';
		}

		$arExternalAuthId = array();
		if (IsModuleInstalled('socialservices'))
		{
			$arExternalAuthId[] = 'replica';
		}
		if (IsModuleInstalled('mail'))
		{
			$arExternalAuthId[] = 'email';
		}
		if (IsModuleInstalled('im'))
		{
			$arExternalAuthId[] = 'bot';
		}
		if (IsModuleInstalled('imconnector'))
		{
			$arExternalAuthId[] = 'imconnector';
		}
		if (!empty($arExternalAuthId))
		{
			$arFilter["!=EXTERNAL_AUTH_ID"] = $arExternalAuthId;
		}

		if (
			(
				IsModuleInstalled("extranet") 
				&& $showExtranetUsers == SHOW_NONE
			)
			|| (
				IsModuleInstalled("bitrix24") 
				&& !IsModuleInstalled("extranet")
			)
			|| (
				is_array($arFilteredUserIDs)
				&& empty($arFilteredUserIDs)
			)
		)
		{
			$arFilter["!UF_DEPARTMENT"] = false;
		}
		elseif (
			IsModuleInstalled("extranet")
			&& $showExtranetUsers != SHOW_ALL
		)
		{
			if (
				$showExtranetUsers == SHOW_FROM_MY_GROUPS
				&& CModule::IncludeModule("extranet")
			)
			{
				$arFilteredUserIDs = CExtranet::GetMyGroupsUsersSimple(CExtranet::GetExtranetSiteID());
			}
			elseif ($showExtranetUsers == SHOW_FROM_EXACT_GROUP)
			{
				$arFilteredUserIDs = array();
				if (CModule::IncludeModule("socialnetwork"))
				{
					$dbUsers = CSocNetUserToGroup::GetList(
						array(),
						array(
							"GROUP_ID" => array($exGroupID),
							"<=ROLE" => SONET_ROLES_USER,
							"USER_ACTIVE" => "Y"
						),
						false,
						false,
						array("ID", "USER_ID")
					);

					if ($dbUsers)
					{
						while ($arUser = $dbUsers->GetNext())
						{
							$arFilteredUserIDs[] = $arUser["USER_ID"];
						}
					}
				}
			}

			if (
				is_array($arFilteredUserIDs)
				&& empty($arFilteredUserIDs)
			)
			{
				$arFilter["!UF_DEPARTMENT"] = false;
			}
			elseif(is_array($arFilteredUserIDs))
			{
				$arFilter[] = array(
					'LOGIC' => 'OR',
					'!UF_DEPARTMENT' => false,
					'ID' => $arFilteredUserIDs
				);
			}
		}

		$arFilter["CONFIRM_CODE"] = false;

		$dbRes = \Bitrix\Main\UserTable::getList(array(
			'order' => array(
				'SORT_WEIGHT' => 'DESC',
				'LAST_NAME' => 'ASC',
				'NAME' => 'ASC',
			),
			'filter' => $arFilter,
			'select' => array("ID", "NAME", "LAST_NAME", "SECOND_NAME", "EMAIL", "LOGIN", "WORK_POSITION", "PERSONAL_PROFESSION", "PERSONAL_PHOTO", "PERSONAL_GENDER", "UF_DEPARTMENT", $sortWeight),
			'limit' => 10,
			'data_doubling' => false
		));

		while ($arRes = $dbRes->fetch())
		{
			$arUsers[] = array(
				'ID' => $arRes['ID'],
				'NAME' => CUser::FormatName($nameTemplate, $arRes, $bUseLogin, false),
				'LOGIN' => $arRes['LOGIN'],
				'EMAIL' => $arRes['EMAIL'],
				'WORK_POSITION' => $arRes['WORK_POSITION'] ? $arRes['WORK_POSITION'] : $arRes['PERSONAL_PROFESSION'],
				'PHOTO' => (string)CIntranetUtils::createAvatar($arRes, array()),
				'HEAD' => false,
				'UF_DEPARTMENT' => $arRes['UF_DEPARTMENT'],
				'SUBORDINATE' => is_array($arSubDeps) && is_array($arRes['UF_DEPARTMENT']) && array_intersect($arRes['UF_DEPARTMENT'], $arSubDeps) ? 'Y' : 'N',
				'SUPERORDINATE' => in_array($arRes["ID"], $arManagers) ? 'Y' : 'N',
			);
		}
	}

	Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
	echo CUtil::PhpToJsObject(array_values(array_filter($arUsers, array('CIntranetUserSelectorHelper', 'filterViewableUsers'))));
	die;
}
?>