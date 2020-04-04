<?
define("STOP_STATISTICS", true);
define("BX_SECURITY_SHOW_MESSAGE", true);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

// we shouldn't check any access rights here 
// if(!($USER->CanDoOperation('view_subordinate_users') || $USER->CanDoOperation('view_all_users')))
	// $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

CModule::IncludeModule('intranet');

if (!$USER->IsAuthorized()/* || CModule::IncludeModule('extranet') && !CExtranet::IsIntranetUser() && !$USER->IsAdmin()*/)
	die();

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

if ($_REQUEST['MODE'] == 'SEARCH')
{
	CUtil::JSPostUnescape();
	$APPLICATION->RestartBuffer();
	
	$EXTERNAL = isset($_GET['EXTERNAL']) && $_GET['EXTERNAL'] != 'I' && CModule::IncludeModule('extranet') ? $_GET['EXTERNAL'] : 'I';
	$site = $EXTERNAL == 'I' ? '' : $_GET['SITE_ID'];
	$search = $_REQUEST['search'];

	if (isset($_GET['SOCNET_GROUP_ID']))
		$group_id = $_GET['SOCNET_GROUP_ID'];

	if ($EXTERNAL == "E" && strlen($site) > 0 && !CExtranet::IsIntranetUser())
	{
		$arUsersInMyGroupsID = CExtranet::GetMyGroupsUsers($site);
		$arPublicUsersID = CExtranet::GetPublicUsers();
		$arUsersToFilter = array_merge($arUsersInMyGroupsID, $arPublicUsersID);
	}
	elseif ($EXTERNAL == "EA" && $GLOBALS["APPLICATION"]->GetGroupRight("socialnetwork") >= "K")
	{
		$arExtranetUsersID = CExtranet::GetExtranetGroupUsers();
		$arIntranetUsersID = CExtranet::GetIntranetUsers();
		$arUsersToFilter = array_diff($arExtranetUsersID, $arIntranetUsersID);
	}
	elseif ($EXTERNAL == "EA")
	{
		$arUsersInMyGroupsID = CExtranet::GetMyGroupsUsers($site);
		$arIntranetUsersID = CExtranet::GetIntranetUsers();
		$arUsersToFilter = array_diff($arUsersInMyGroupsID, $arIntranetUsersID);
	}
	elseif ($EXTERNAL == 'I' && CModule::IncludeModule('extranet') && CExtranet::IsIntranetUser())
		$arUsersToFilter = CExtranet::GetIntranetUsers();
	elseif (intval($group_id) > 0)
	{
		$arSonetGroup = CSocNetGroup::GetByID($group_id);
		if (!$arSonetGroup)
		{
			require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_after.php");
			die();
		}
		elseif (IsModuleInstalled("extranet"))
		{
			// check if current user is a member of extranet workgroup
			if (CModule::IncludeModule("extranet") && CExtranet::IsExtranetSite($arSonetGroup["SITE_ID"]) && !CSocNetUser::IsCurrentUserModuleAdmin())
			{
				$dbRequests = CSocNetUserToGroup::GetList(
					array(),
					array(
						"GROUP_ID" => $group_id,
						"USER_ID" => $GLOBALS["USER"]->GetID(),
						"<=ROLE" => SONET_ROLES_USER,
					),
					false,
					false,
					array("ID")
				);
				if ($dbRequests)
				{
					if (!$arRequests = $dbRequests->Fetch())
					{
						require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_after.php");
						die();
					}
				}
			}
		}
		
		$arUsersToFilter = array();
		$dbRequests = CSocNetUserToGroup::GetList(
			array(),
			array(
				"GROUP_ID" => $group_id,
				"<=ROLE" => SONET_ROLES_USER,
				"USER_ACTIVE" => "Y"
			),
			false,
			false,
			array("ID", "USER_ID", "ROLE")
		);
		if ($dbRequests)
		{
			while ($arRequests = $dbRequests->Fetch())
				$arUsersToFilter[] = $arRequests["USER_ID"];
		}
	}
	elseif ($EXTERNAL == 'A' && strlen($site) > 0 && CModule::IncludeModule("extranet") && CExtranet::IsExtranetSite($site))
	{
		if ($GLOBALS["APPLICATION"]->GetGroupRight("socialnetwork") >= "W"):
			$arUsersToFilter = CExtranet::GetExtranetGroupUsers();
		else:
			$arUsersToFilter = CExtranet::GetMyGroupsUsers($site);
		endif;
	}
	elseif ($EXTERNAL == 'A' && CModule::IncludeModule("extranet") && CExtranet::IsIntranetUser())
		$arUsersToFilter = CExtranet::GetIntranetUsers();
	elseif (IsModuleInstalled('extranet'))
	{
		require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_after.php");
		die();
	}
	
	$matches = array();
	if (preg_match('/^(.*?)<[a-z0-9.\-_]+@[a-z0-9.\-]+> \[([\d]+?)\]/i', $search, $matches))
	{
		$matches[2] = intval($matches[2]);
		
		if ($matches[2] > 0 && (!is_array($arUsersToFilter) || in_array($matches[2], $arUsersToFilter)))
		{
			$dbRes = CUser::GetList(
				($by='id'),
				($order='asc'),
				array('ID'=> $matches[2]),
				array(
					'FIELDS'=> array('ID', 'LOGIN', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'TITLE', 'EMAIL'),
					'SELECT' => array('UF_DEPARTMENT')
				)
			);

			if ($arRes = $dbRes->Fetch())
			{
				$arUsers = array(
					array(
						'ID' => $arRes['ID'],
						'NAME' => str_replace(array(';', ','), ' ', CIntranetUtils::FormatName(CSite::GetNameFormat(false), $arRes, false)).' <'.$arRes['EMAIL'].'>',
						'UF_DEPARTMENT' => $arRes['UF_DEPARTMENT'],
						'READY' => 'Y',
					)
				);
			
				Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
				echo CUtil::PhpToJsObject($arUsers);
				die();
			}
		}
		elseif (strlen($matches[1]) > 0)
		{
			$search = $matches[1];
		}
	}

	$arFilter = array('ACTIVE' => 'Y', 'NAME_SEARCH' => $search);

	if ('I' == $EXTERNAL)
		$arFilter['!UF_DEPARTMENT'] = false;

	$dbRes = CUser::GetList(
		$by = 'last_name', $order = 'asc',
		$arFilter,
		array(
			'FIELDS'=> array('ID', 'LOGIN', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'TITLE', 'EMAIL'),
			'SELECT' => array('UF_DEPARTMENT'),
			'NAV_PARAMS' => array('nTopCount' => 10)
		)
	);

	while ($arRes = $dbRes->GetNext())
	{
		$arUsers[] = array(
			'ID' => $arRes['ID'],
			'NAME' => str_replace(array(';', ','), ' ', CIntranetUtils::FormatName(CSite::GetNameFormat(false), $arRes, false)).' <'.$arRes['EMAIL'].'>',
			'UF_DEPARTMENT' => $arRes['UF_DEPARTMENT'],
		);
	}
	
	Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
	echo CUtil::PhpToJsObject($arUsers);
	die();
}
?>
