<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule('intranet')) return;
$bSoNet = CModule::IncludeModule('socialnetwork');

$arParams['FILTER_NAME'] =
		(strlen($arParams["FILTER_NAME"])<=0 || !preg_match("/^[A-Za-z_][A-Za-z0-9_]*$/", $arParams["FILTER_NAME"])) ?
		'find_' : $arParams['FILTER_NAME'];

$arParams['USERS_PER_PAGE'] = intval($arParams['USERS_PER_PAGE']);
//$arParams['USERS_PER_PAGE'] = $arParams['USERS_PER_PAGE'] > 0 ? $arParams['USERS_PER_PAGE'] : 10;

$arParams['NAV_TITLE'] = $arParams['NAV_TITLE'] ? $arParams['NAV_TITLE'] : GetMessage('INTR_ISL_PARAM_NAV_TITLE_DEFAULT');

$arParams['DATE_FORMAT'] = $arParams['DATE_FORMAT'] ? $arParams['DATE_FORMAT'] : CComponentUtil::GetDateFormatDefault(false);
$arParams['DATE_FORMAT_NO_YEAR'] = $arParams['DATE_FORMAT_NO_YEAR'] ? $arParams['DATE_FORMAT_NO_YEAR'] : CComponentUtil::GetDateFormatDefault(true);

InitBVar($arParams['FILTER_1C_USERS']);
InitBVar($arParams['FILTER_SECTION_CURONLY']);

InitBVar($arParams['SHOW_NAV_TOP']);
InitBVar($arParams['SHOW_NAV_BOTTOM']);

InitBVar($arParams['SHOW_UNFILTERED_LIST']);
InitBVar($arParams['SHOW_DEP_HEAD_ADDITIONAL']);
$showDepHeadAdditional = $arParams['SHOW_DEP_HEAD_ADDITIONAL'] == 'Y';

$arParams['DETAIL_URL'] = COption::GetOptionString('intranet', 'search_user_url', '/user/#ID#/');

if (!array_key_exists("PM_URL", $arParams))
	$arParams["~PM_URL"] = $arParams["PM_URL"] = "/company/personal/messages/chat/#USER_ID#/";
if (!array_key_exists("PATH_TO_USER_EDIT", $arParams))
	$arParams["~PATH_TO_USER_EDIT"] = $arParams["PATH_TO_USER_EDIT"] = '/company/personal/user/#user_id#/edit/';
if (!array_key_exists("PATH_TO_CONPANY_DEPARTMENT", $arParams))
	$arParams["~PATH_TO_CONPANY_DEPARTMENT"] = $arParams["PATH_TO_CONPANY_DEPARTMENT"] = "/company/structure.php?set_filter_structure=Y&structure_UF_DEPARTMENT=#ID#";
if (IsModuleInstalled("video") && !array_key_exists("PATH_TO_VIDEO_CALL", $arParams))
	$arParams["~PATH_TO_VIDEO_CALL"] = $arParams["PATH_TO_VIDEO_CALL"] = "/company/personal/video/#USER_ID#/";

if(!isset($arParams["CACHE_TIME"]))
	$arParams["CACHE_TIME"] = 3600;

if ($arParams['CACHE_TYPE'] == 'A')
	$arParams['CACHE_TYPE'] = COption::GetOptionString("main", "component_cache_on", "Y");

$bExcel = $_GET['excel'] == 'yes';
$bNav = $arParams['SHOW_NAV_TOP'] == 'Y' || $arParams['SHOW_NAV_BOTTOM'] == 'Y';

$bDesignMode = $GLOBALS["APPLICATION"]->GetShowIncludeAreas() && is_object($GLOBALS["USER"]) && $GLOBALS["USER"]->IsAdmin();

// prepare list filter
$arFilter = array();
global $USER;
if (!$USER->CanDoOperation("edit_all_users") && isset($arParams["SHOW_USER"]) && $arParams["SHOW_USER"] != "fired")
	$arParams["SHOW_USER"] = "active";
if (!(CModule::IncludeModule('extranet') && CExtranet::IsExtranetSite()))  // from intranet
{
	if (!isset($arParams["SHOW_USER"]))
	{
		$arFilter = array('ACTIVE' => 'Y');
	}
	else
	{
		switch ($arParams["SHOW_USER"])
		{
			case "fired":
				$arFilter = array('ACTIVE' => 'N');
				break;
			case "inactive":
				$arFilter = array('ACTIVE' => 'Y', 'LAST_LOGIN' => false);
				break;
			case "extranet":
				if (CModule::IncludeModule('extranet'))
				{
					if (IsModuleInstalled("bitrix24"))
						$arFilter = array('ACTIVE' => 'Y', 'GROUPS_ID' => CExtranet::GetExtranetUserGroupID(), '!LAST_LOGIN' => false);
					else
						$arFilter = array('ACTIVE' => 'Y', 'GROUPS_ID' => CExtranet::GetExtranetUserGroupID());
				}
				break;
			case "active":
				if (IsModuleInstalled("bitrix24"))
					$arFilter = array('ACTIVE' => 'Y', '!LAST_LOGIN' => false);
				else
					$arFilter = array('ACTIVE' => 'Y');
				break;
		}
		$arResult["SHOW_USER"] = $arParams["SHOW_USER"];
	}
}
else   //from extranet
{
	$arFilter["ACTIVE"] = "Y";
	//$arFilter["GROUPS_ID"] = array(CExtranet::GetExtranetUserGroupID());
	if ($arParams["EXTRANET_TYPE"] == "employees")
		$arFilter["!UF_DEPARTMENT"] = false;
	else
		$arFilter["UF_DEPARTMENT"] = false;
}

if ('Y' == $arParams['FILTER_1C_USERS'])
	$arFilter['UF_1C'] = 1;
if ($GLOBALS[$arParams['FILTER_NAME'].'_UF_DEPARTMENT'])
	$arFilter['UF_DEPARTMENT'] =
		$arParams['FILTER_SECTION_CURONLY'] == 'N'
		? CIntranetUtils::GetIBlockSectionChildren($GLOBALS[$arParams['FILTER_NAME'].'_UF_DEPARTMENT'])
		: array($GLOBALS[$arParams['FILTER_NAME'].'_UF_DEPARTMENT']);
elseif ((!CModule::IncludeModule('extranet') || !CExtranet::IsExtranetSite()) && $arParams["SHOW_USER"] != "all")
{
	// only employees for an intranet site
	if ($arParams["SHOW_USER"] == "extranet")
		$arFilter["UF_DEPARTMENT"] = false;
	elseif ($arParams["SHOW_USER"] != "inactive" && $arParams["SHOW_USER"] != "fired")
		$arFilter["!UF_DEPARTMENT"] = false;
}

$cnt_start = count($arFilter); // we'll cache all variants of selection by UF_DEPARTMENT (and GROUPS_ID with extranet)
$cnt_start_cache_id = '';
foreach ($arFilter as $key => $value)
	$cnt_start_cache_id .= '|'.$key.':'.preg_replace("/[\s]*/", "", var_export($value, true));

if ($GLOBALS[$arParams['FILTER_NAME'].'_POST'])
	$arFilter['WORK_POSITION'] = $GLOBALS[$arParams['FILTER_NAME'].'_POST'];
if ($GLOBALS[$arParams['FILTER_NAME'].'_COMPANY'])
	$arFilter['WORK_COMPANY'] = $GLOBALS[$arParams['FILTER_NAME'].'_COMPANY'];

if ($GLOBALS[$arParams['FILTER_NAME'].'_EMAIL'])
	$arFilter['EMAIL'] = $GLOBALS[$arParams['FILTER_NAME'].'_EMAIL'];

if ($GLOBALS[$arParams['FILTER_NAME'].'_FIO'])
	$arFilter['NAME'] = $GLOBALS[$arParams['FILTER_NAME'].'_FIO'];

if ($GLOBALS[$arParams['FILTER_NAME'].'_PHONE'])
	$arFilter['WORK_PHONE'] = $GLOBALS[$arParams['FILTER_NAME'].'_PHONE'];

if ($GLOBALS[$arParams['FILTER_NAME'].'_UF_PHONE_INNER'])
	$arFilter['UF_PHONE_INNER'] = $GLOBALS[$arParams['FILTER_NAME'].'_UF_PHONE_INNER'];

/*
if ($GLOBALS[$arParams['FILTER_NAME'].'_BIRTHDATE_FROM'])
	$arFilter['PERSONAL_BIRTHDAY_1'] = $GLOBALS[$arParams['FILTER_NAME'].'_BIRTHDATE_FROM'];
if ($GLOBALS[$arParams['FILTER_NAME'].'_BIRTHDATE_TO'])
	$arFilter['PERSONAL_BIRTHDAY_2'] = $GLOBALS[$arParams['FILTER_NAME'].'_BIRTHDATE_TO'];
*/

if ($GLOBALS[$arParams['FILTER_NAME'].'_KEYWORDS'])
	$arFilter['KEYWORDS'] = $GLOBALS[$arParams['FILTER_NAME'].'_KEYWORDS'];

if ($GLOBALS[$arParams['FILTER_NAME'].'_IS_ONLINE'] == 'Y')
{
	$arFilter['LAST_ACTIVITY'] = 120;
}

if ($GLOBALS[$arParams['FILTER_NAME'].'_LAST_NAME'])
{
	$arFilter['LAST_NAME'] = $GLOBALS[$arParams['FILTER_NAME'].'_LAST_NAME'];
	$arFilter['LAST_NAME_EXACT_MATCH'] = 'Y';
}

if ($GLOBALS[$arParams['FILTER_NAME'] . '_LAST_NAME_RANGE'])
{
	$arFilter['LAST_NAME_RANGE'] = $GLOBALS[$arParams['FILTER_NAME'] . '_LAST_NAME_RANGE'];
}

if ($arParams['SHOW_UNFILTERED_LIST'] == 'N' && !$bExcel && $cnt_start == count($arFilter) && !$arFilter['UF_DEPARTMENT'])
{
	$arResult['EMPTY_UNFILTERED_LIST'] = 'Y';
	$this->IncludeComponentTemplate();
	return;
}

$arParams['bCache'] =
	/*$arParams['SHOW_UNFILTERED_LIST'] == 'Y' && */$cnt_start == count($arFilter) // we cache only unfiltered list
	&& !$bExcel
	&& $arParams['CACHE_TYPE'] == 'Y' && $arParams['CACHE_TIME'] > 0;

$arResult['FILTER_VALUES'] = $arFilter;

if (!$bExcel && $bNav)
{
	CPageOption::SetOptionString("main", "nav_page_in_session", "N");
}

if ($arParams['bCache'])
{
	$cache_dir = '/'.SITE_ID.$this->GetRelativePath();
	$cache_dir .= '/'.substr(md5($cnt_start_cache_id), 0, 5);
	$cache_dir .= '/'.trim(CDBResult::NavStringForCache($arParams['USERS_PER_PAGE'], false), '|');

	$cache_id = $this->GetName().'|'.SITE_ID;

	if (CModule::IncludeModule('extranet') && CExtranet::IsExtranetSite())
		$cache_id .= '|'.$USER->GetID().'|'.$arParams['EXTRANET_TYPE'];

	$cache_id .= CDBResult::NavStringForCache($arParams['USERS_PER_PAGE'], false);
	$cache_id .= $cnt_start_cache_id."|".$arParams['USERS_PER_PAGE'];

	$obCache = new CPHPCache();
}

if ($arParams['bCache'] && $obCache->InitCache($arParams['CACHE_TIME'], $cache_id, $cache_dir))
{
	$bFromCache = true;

	$vars = $obCache->GetVars();
	$arResult['USERS'] = $vars['USERS'];
	$arResult['DEPARTMENTS'] = $vars['DEPARTMENTS'];
	$arResult['DEPARTMENT_HEAD'] = $vars['DEPARTMENT_HEAD'];
	$arResult['USERS_NAV'] = $vars['USERS_NAV'];
	$strUserIDs = $vars['STR_USER_ID'];
}
else
{
	$bFromCache = false;

	if ($arParams['bCache'])
	{
		$obCache->StartDataCache();
		global $CACHE_MANAGER;
		$CACHE_MANAGER->StartTagCache($cache_dir);
		$CACHE_MANAGER->RegisterTag('intranet_users');
	}

	// get users list
	$obUser = new CUser();

	$arSelect = array('ID', 'ACTIVE', 'DEP_HEAD', 'GROUP_ID', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'LOGIN', 'EMAIL', 'LID', 'DATE_REGISTER',  'PERSONAL_PROFESSION', 'PERSONAL_WWW', 'PERSONAL_ICQ', 'PERSONAL_GENDER', 'PERSONAL_BIRTHDATE', 'PERSONAL_PHOTO', 'PERSONAL_PHONE', 'PERSONAL_FAX', 'PERSONAL_MOBILE', 'PERSONAL_PAGER', 'PERSONAL_STREET', 'PERSONAL_MAILBOX', 'PERSONAL_CITY', 'PERSONAL_STATE', 'PERSONAL_ZIP', 'PERSONAL_COUNTRY', 'PERSONAL_NOTES', 'WORK_COMPANY', 'WORK_DEPARTMENT', 'WORK_POSITION', 'WORK_WWW', 'WORK_PHONE', 'WORK_FAX', 'WORK_PAGER', 'WORK_STREET', 'WORK_MAILBOX', 'WORK_CITY', 'WORK_STATE', 'WORK_ZIP', 'WORK_COUNTRY', 'WORK_PROFILE', 'WORK_LOGO', 'WORK_NOTES', 'PERSONAL_BIRTHDAY', 'LAST_ACTIVITY_DATE', 'LAST_LOGIN');

	$arResult['USERS']           = array();
	$arResult['DEPARTMENTS']     = array();
	$arResult['DEPARTMENT_HEAD'] = 0;
	//disable/enable appearing of department head on page
	if ($showDepHeadAdditional && isset($arFilter['UF_DEPARTMENT']) && is_array($arFilter['UF_DEPARTMENT']))
	{
		if ($arParams['bCache'])
		{
			$CACHE_MANAGER->RegisterTag('intranet_department_'.$arFilter['UF_DEPARTMENT'][0]);
		}

		$dbRes = CIBlockSection::GetList(
			array('ID' => 'ASC'),
			array('ID' => $arFilter['UF_DEPARTMENT'][0], 'IBLOCK_ID' => COption::GetOptionInt('intranet', 'iblock_structure')),
			false,
			array('UF_HEAD')
		);
		if (($arSection = $dbRes->GetNext()) && $arSection['UF_HEAD'] > 0)
		{

			$dbUsers = $obUser->GetList(
				($sort_by = 'last_name'), ($sort_dir = 'asc'),
				array('ID' => $arSection['UF_HEAD'], 'ACTIVE' => 'Y'),
				array('SELECT' => array('UF_*'))
			);

			if (($arRes = $dbUsers->Fetch()))
			{
				$arResult['DEPARTMENT_HEAD'] = $arSection['UF_HEAD'];
				$arFilter['!ID'] = $arResult['DEPARTMENT_HEAD'];
				$arResult['USERS'][$arRes['ID']] = $arRes;
			}
		}
	}

	$bDisable = false;
	if (CModule::IncludeModule('extranet'))
	{
		if (CExtranet::IsExtranetSite() && !CExtranet::IsExtranetAdmin())
		{
			$arIDs = array_merge(CExtranet::GetMyGroupsUsers(SITE_ID), CExtranet::GetPublicUsers());

			if ($arParams['bCache'])
			{
				$CACHE_MANAGER->RegisterTag('extranet_public');
				$CACHE_MANAGER->RegisterTag('extranet_user_'.$USER->GetID());
			}

			if (false !== ($key = array_search($USER->GetID(), $arIDs)))
				unset($arIDs[$key]);

			if (count($arIDs) > 0)
				$arFilter['ID'] = implode('|', array_unique($arIDs));
			else
			{
				$bDisable = true;
			}
		}
	}

	$arListParams = array('SELECT' => array('UF_*'));
	if (!$bExcel && $arParams['USERS_PER_PAGE'] > 0)
		$arListParams['NAV_PARAMS'] = array('nPageSize' => $arParams['USERS_PER_PAGE'], 'bShowAll' => false);

	if ($bDisable)
	{
		$dbUsers = new CDBResult();
		$dbUsers->InitFromArray(array());
	}
	else
	{
		if($arFilter['LAST_NAME_RANGE'])
		{
			//input format: a-z (letter - letter)
			$letterRange      = explode('-', $arFilter['LAST_NAME_RANGE'], 2);
			$startLetterRange = array_shift($letterRange);
			$endLetterRange   = array_shift($letterRange);

			$arFilter[] = array(
				'LOGIC' => 'OR',
				array(
					'><F_LAST_NAME' => array(toUpper($startLetterRange), toUpper($endLetterRange)),
				),
				array(
					'><F_LAST_NAME' => array(toLower($startLetterRange), toLower($endLetterRange)),
				),
			);
			unset($arFilter['LAST_NAME_RANGE']);
		}

		$dbUsers = $obUser->GetList(($sort_by = 'last_name'), ($sort_dir = 'asc'), $arFilter, $arListParams);
	}

	$arDepartments = array();
	$strUserIDs = '';
	while ($arUser = $dbUsers->Fetch())
	{
		$arResult['USERS'][$arUser['ID']] = $arUser;
		$strUserIDs .= ($strUserIDs == '' ? '' : '|').$arUser['ID'];
	}
	//head
	$dbRes = CIBlockSection::GetList(
		array(),
		array('UF_HEAD' => array_keys($arResult['USERS']), 'IBLOCK_ID' => COption::GetOptionInt('intranet', 'iblock_structure')),
		false,
		array('ID', 'NAME', 'UF_HEAD')
	);
	while ($arSection = $dbRes->Fetch())
	{
		$arResult['USERS'][$arSection['UF_HEAD']]["DEP_HEAD"][$arSection["ID"]] = $arSection["NAME"];
	}

	if (CModule::IncludeModule('extranet'))
		$extranetGroupID = CExtranet::GetExtranetUserGroupID();

	$arAdmins = array();
	$rsUsers = CUser::GetList($o, $b, array("GROUPS_ID" => array(1)), array("SELECT"=>array("ID")));
	while ($ar = $rsUsers->Fetch())
		$arAdmins[$ar["ID"]] = $ar["ID"];

	$extranetUsers = array();
	if (isset($extranetGroupID))
	{
		$rsUsers = CUser::GetList($o, $b, array("GROUPS_ID" => array($extranetGroupID)), array("SELECT"=>array("ID")));
		while ($ar = $rsUsers->Fetch())
			$extranetUsers[$ar["ID"]] = $ar["ID"];
	}

	foreach ($arResult['USERS'] as $key => $arUser)
	{
		if ($arParams['bCache'])
		{
			$CACHE_MANAGER->RegisterTag('intranet_user_'.$arUser['ID']);
		}

		// cache optimization
		foreach ($arUser as $k => $value)
		{
			if (
				is_array($value) && count($value) <= 0
				|| !is_array($value) && strlen($value) <= 0
				|| !in_array($k, $arSelect) && substr($k, 0, 3) != 'UF_'
			)
			{
				unset($arUser[$k]);
			}
			elseif ($k == "PERSONAL_COUNTRY" || $k == "WORK_COUNTRY")
			{
				$arUser[$k] = GetCountryByID($value);
			}
		}

		//is user admin/extranet
		$arUser['ADMIN'] = array_key_exists($arUser['ID'], $arAdmins);

		$arUser["ACTIVITY_STATUS"] = "active";
		if (array_key_exists($arUser['ID'], $extranetUsers) && count($arUser['UF_DEPARTMENT']) <= 0)
		{
			$arUser["ACTIVITY_STATUS"] = "extranet";
			$arUser['EXTRANET'] = true;
		}
		else
			$arUser['EXTRANET'] = false;

		if ($arUser["ACTIVE"] == "N")
			$arUser["ACTIVITY_STATUS"] = "fired";
		if (IsModuleInstalled("bitrix24") && empty($arUser["LAST_LOGIN"]))
			$arUser["ACTIVITY_STATUS"] = "inactive";

		$arUser['SHOW_USER'] = $arParams["SHOW_USER"];

		$arUser['IS_FEATURED'] = CIntranetUtils::IsUserHonoured($arUser['ID']);

//		if (is_array($arUser['UF_DEPARTMENT']) && count($arUser['UF_DEPARTMENT']) > 0)
//			$arDepartments = array_merge($arDepartments, $arUser['UF_DEPARTMENT']);

		$arResult['USERS'][$key] = $arUser;
	}

	if (count($arResult['USERS']) > 0)
	{
		$dbRes = CIBlockSection::GetList(
			array("left_margin"=>"asc"),
			array(
				'IBLOCK_ID' => COption::GetOptionInt('intranet', 'iblock_structure'),
				//'ID' => array_unique($arDepartments)
			),
			array('ID', 'NAME', 'SECTION_PAGE_URL', 'DEPTH_LEVEL', 'IBLOCK_SECTION_ID','UF_HEAD')
		);

		while ($arSect = $dbRes->Fetch())
		{
			$arSect['USERS'] = array();
			//$arDepartments[$arSect['ID']] = $arSect['NAME'];
			$arResult['DEPARTMENTS'][$arSect['ID']] = $arSect;
		}
	}

	$arResult["USERS_NAV"] = $bNav ? $dbUsers->GetPageNavStringEx($navComponentObject=null, $arParams["NAV_TITLE"]) : '';

	if ($arParams['bCache'])
	{
		$arCache = array(
			'USERS' => $arResult['USERS'],
			'STR_USER_ID' =>  $strUserIDs,
			'DEPARTMENTS' => $arResult['DEPARTMENTS'],
			'DEPARTMENT_HEAD' => $arResult['DEPARTMENT_HEAD'],
			'USERS_NAV' => $arResult['USERS_NAV']
		);

		$CACHE_MANAGER->EndTagCache();
		$obCache->EndDataCache($arCache);
	}
}

$ptime = getmicrotime();
$timeLimitResize = 5;
foreach ($arResult['USERS'] as $arUser)
{
	$arDep = array();
	if (is_array($arUser['UF_DEPARTMENT']))
	{
		foreach ($arUser['UF_DEPARTMENT'] as $key => $sect)
		{
			$arDep[$sect] = $arResult['DEPARTMENTS'][$sect]['NAME'];
		}
	}

	$arUser['UF_DEPARTMENT'] = $arDep;

	if ($arParams['DETAIL_URL'])
		$arUser['DETAIL_URL'] = str_replace(array('#ID#', '#USER_ID#'), $arUser['ID'], $arParams['DETAIL_URL']);

	if (!$arUser['PERSONAL_PHOTO'])
	{
		switch ($arUser['PERSONAL_GENDER'])
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
		$arUser['PERSONAL_PHOTO'] = COption::GetOptionInt("socialnetwork", "default_user_picture_".$suffix, false, SITE_ID);
	}

	if($arUser['PERSONAL_PHOTO'])
	{
		$arUser['PERSONAL_PHOTO_SOURCE'] = $arUser['PERSONAL_PHOTO'];
		if ($bExcel)
		{
			$arUser['PERSONAL_PHOTO'] = CFile::GetPath($arUser['PERSONAL_PHOTO']);
		}
		else
		{
			if (round(getmicrotime()-$ptime, 3)>$timeLimitResize)
			{
				$arUser['PERSONAL_PHOTO'] = CFile::ShowImage($arUser['PERSONAL_PHOTO'], 9999, 100);
			}
			else
			{
				$arImage = CIntranetUtils::InitImage($arUser['PERSONAL_PHOTO'], 100);
				$arUser['PERSONAL_PHOTO'] = $arImage['IMG'];
			}
		}
	}

	if (!$bFromCache)
	{
		$arUser['IS_ONLINE'] = CIntranetUtils::IsOnline($arUser['LAST_ACTIVITY_DATE'], 120);
	}

	$arUser['IS_BIRTHDAY'] = CIntranetUtils::IsToday($arUser['PERSONAL_BIRTHDAY']);
	$arUser['IS_ABSENT'] = CIntranetUtils::IsUserAbsent($arUser['ID']);

	// emulate list flags ;-)
	/*
	$arUser['IS_ONLINE'] |= rand(1, 100) <= 75;
	$arUser['IS_ABSENT'] |= rand(1, 100) <= 20;
	$arUser['IS_FEATURED'] |= rand(1, 100) <= 5;
	$arUser['IS_BIRTHDAY'] |= rand(1, 100) <= 2;
	*/

	$arResult['USERS'][$arUser['ID']] = $arUser;
}

//foreach($arResult['USERS'] as $key => $arUser)
//    $arResult['USERS'][$key]['IS_ABSENT'] = CIntranetUtils::IsUserAbsent($arUser['ID']);

if (!$bExcel)
{
	if ($bFromCache && $strUserIDs)
	{
		$dbRes = CUser::GetList($by='id', $order='asc', array('ID' => $strUserIDs, 'LAST_ACTIVITY' => 120), array('FIELDS' => array('ID')));
		while ($arRes = $dbRes->Fetch())
		{
			if ($arResult['USERS'][$arRes['ID']])
				$arResult['USERS'][$arRes['ID']]['IS_ONLINE'] = true;
		}
		unset($dbRes);
	}

	$arResult['bAdmin'] = $USER->CanDoOperation('edit_all_users') || $USER->CanDoOperation('edit_subordinate_users');

	$this->IncludeComponentTemplate();
}
else
{
	$APPLICATION->RestartBuffer();

	// hack. any '.default' customized template should contain 'excel' page
	$this->__templateName = '.default';

	Header("Content-Type: application/force-download");
	Header("Content-Type: application/octet-stream");
	Header("Content-Type: application/download");
	//Header("Content-Type: application/vnd.ms-excel; charset=".LANG_CHARSET);
	Header("Content-Disposition: attachment;filename=users.xls");
	Header("Content-Transfer-Encoding: binary");

	$this->IncludeComponentTemplate('excel');

	die();
}
?>