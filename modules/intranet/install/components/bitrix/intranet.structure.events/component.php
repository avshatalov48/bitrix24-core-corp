<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule('intranet')) return;

$arParams['NUM_USERS'] = intval($arParams['NUM_USERS']);
$arParams['NUM_USERS'] = $arParams['NUM_USERS'] ? $arParams['NUM_USERS'] : 10;

InitBVar($arParams['SHOW_NAV_TOP']);
InitBVar($arParams['SHOW_NAV_BOTTOM']);

$arParams['DETAIL_URL'] = COption::GetOptionString('intranet', 'search_user_url', '/user/#ID#/');

$arParams['IBLOCK_ID'] = intval($arParams['IBLOCK_ID']);
if (!$arParams['IBLOCK_ID']) $arParams['IBLOCK_ID'] = COption::GetOptionInt('intranet', 'iblock_state_history');
if (!$arParams['IBLOCK_TYPE']) $arParams['IBLOCK_TYPE'] = COption::GetOptionString('intranet', 'iblock_type');

if(!isset($arParams["CACHE_TIME"]))
	$arParams["CACHE_TIME"] = 3600;

if ($arParams['CACHE_TYPE'] == 'A')
	$arParams['CACHE_TYPE'] = COption::GetOptionString("main", "component_cache_on", "Y");

if (strlen($arParams["NAME_TEMPLATE"]) <= 0)
	$arParams["NAME_TEMPLATE"] = CSite::GetNameFormat();

$arParams['SHOW_LOGIN'] = $arParams['SHOW_LOGIN'] != "N" ? "Y" : "N";

if (!array_key_exists("PM_URL", $arParams))
	$arParams["~PM_URL"] = $arParams["PM_URL"] = "/company/personal/messages/chat/#USER_ID#/";
if (!array_key_exists("PATH_TO_CONPANY_DEPARTMENT", $arParams))
	$arParams["~PATH_TO_CONPANY_DEPARTMENT"] = $arParams["PATH_TO_CONPANY_DEPARTMENT"] = "/company/structure.php?set_filter_structure=Y&structure_UF_DEPARTMENT=#ID#";
if (IsModuleInstalled("video") && !array_key_exists("PATH_TO_VIDEO_CALL", $arParams))
	$arParams["~PATH_TO_VIDEO_CALL"] = $arParams["PATH_TO_VIDEO_CALL"] = "/company/personal/video/#USER_ID#/";

if (!$arParams['DATE_FORMAT']) $arParams['DATE_FORMAT'] = CComponentUtil::GetDateFormatDefault();

// for bitrix:main.user.link
$arTooltipFieldsDefault	= serialize(array(
	"EMAIL",
	"PERSONAL_MOBILE",
	"WORK_PHONE",
	"PERSONAL_ICQ",
	"PERSONAL_PHOTO",
	"PERSONAL_CITY",
	"WORK_COMPANY",
	"WORK_POSITION",
));
$arTooltipPropertiesDefault = serialize(array(
	"UF_DEPARTMENT",
	"UF_PHONE_INNER",
));

if (!array_key_exists("SHOW_FIELDS_TOOLTIP", $arParams))
	$arParams["SHOW_FIELDS_TOOLTIP"] = unserialize(COption::GetOptionString("socialnetwork", "tooltip_fields", $arTooltipFieldsDefault));
if (!array_key_exists("USER_PROPERTY_TOOLTIP", $arParams))
	$arParams["USER_PROPERTY_TOOLTIP"] = unserialize(COption::GetOptionString("socialnetwork", "tooltip_properties", $arTooltipPropertiesDefault));

if (!array_key_exists("PATH_TO_CONPANY_DEPARTMENT", $arParams))
	$arParams["PATH_TO_CONPANY_DEPARTMENT"] = "/company/structure.php?set_filter_structure=Y&structure_UF_DEPARTMENT=#ID#";

$IBLOCK_PERMISSION = CIBlock::GetPermission($arParams['IBLOCK_ID']);
$arParams['bAdmin'] = $IBLOCK_PERMISSION >= 'U';

$DEPARTMENT = intval($_REQUEST['department']);

$arParams['bCache'] = $arParams['CACHE_TYPE'] == 'Y' && $arParams['CACHE_TIME'] > 0; // && $DEPARTMENT <= 0;

CPageOption::SetOptionString("main", "nav_page_in_session", "N");

if ($arParams['bCache'])
{
	$cache_dir = '/'.SITE_ID.$this->GetRelativePath().'/'.trim(CDBResult::NavStringForCache($arParams['NUM_USERS'], false), '|');
	$cache_id = $this->GetName()
		.'|'.$arParams['NUM_USERS']
		.'|'.$arParams['IBLOCK_ID']
		.'|'.(is_array($arParams['USER_PROPERTY']) ? implode(';', $arParams['USER_PROPERTY']) : '')
		.CDBResult::NavStringForCache($arParams['NUM_USERS'], false);

	if ($DEPARTMENT)
	{
		$cache_dir .= '/'.$DEPARTMENT;
		$cache_id .= '|dpt'.$DEPARTMENT;
	}

	$obCache = new CPHPCache();
}

if ($arParams['bCache'] && $obCache->InitCache($arParams['CACHE_TIME'], $cache_id, $cache_dir))
{
	$bDataFromCache = true;
	$vars = $obCache->GetVars();

	$arResult['ENTRIES'] = $vars['ENTRIES'];
	$arResult['ENTRIES_NAV'] = $vars['ENTRIES_NAV'];
	$arResult['DEPARTMENTS'] = $vars['DEPARTMENTS'];
	$arResult['USERS'] = $vars['USERS'];
}
else
{
	$bDataFromCache = false;

	if ($arParams['bCache'])
	{
		$obCache->StartDataCache();
		global $CACHE_MANAGER;
		$CACHE_MANAGER->StartTagCache($cache_dir);
		$CACHE_MANAGER->RegisterTag('intranet_users');

		if ($DEPARTMENT)
			$CACHE_MANAGER->RegisterTag('intranet_department_'.$DEPARTMENT);
	}

	// prepare list filter
	$arFilter = array('ACTIVE' => 'Y', 'ACTIVE_DATE' => 'Y', 'IBLOCK_ID' => $arParams['IBLOCK_ID'], '!PROPERTY_USER' => false);

	if ($DEPARTMENT > 0)
		$arFilter['PROPERTY_DEPARTMENT'] = CIntranetUtils::GetIBlockSectionChildren(intval($DEPARTMENT));

	$obIB = new CIBlockElement();
	$dbIB = $obIB->GetList(
		array('active_from' => 'desc', 'id' => 'desc'),
		$arFilter,
		false,
		array('nPageSize' => $arParams['NUM_USERS'], 'bShowAll' => false),
		array('IBLOCK_ID', 'NAME', 'PREVIEW_TEXT', 'DATE_ACTIVE_FROM', 'PROPERTY_USER', 'PROPERTY_DEPARTMENT', 'PROPERTY_POST', 'PROPERTY_STATE')
	);

	$arResult['ENTRIES'] = array();
	$arResult["ENTRIES_NAV"] = $dbIB->GetPageNavStringEx($navComponentObject=null, GetMessage('INTR_ISE_USERS_NAV_TITLE'));

	$arDepCacheValue = array();
	$arDepartmentIDs = array();
	$strUserIDs = '';
	while ($arIB = $dbIB->NavNext(false))
	{
		$strUserIDs .= ($strUserIDs == '' ? '' : '|').$arIB['PROPERTY_USER_VALUE'];
		$arDepartmentIDs[] = $arIB['PROPERTY_DEPARTMENT_VALUE'];

		$arResult['ENTRIES'][] = $arIB;
	}

	unset($dbIB);

	$arResult['USERS'] = array();
	$arResult['DEPARTMENTS'] = array();

	if ($strUserIDs != '')
	{
		$dbRes = CIBlockSection::GetList(array('SORT' => 'ASC'), array('IBLOCK_ID' => COption::GetOptionInt('intranet', 'iblock_structure'), 'ID' => array_unique($arDepartmentIDs)));
		while ($arSect = $dbRes->Fetch())
		{
			$arResult['DEPARTMENTS'][$arSect['ID']] = $arSect['NAME'];
		}
		unset($dbRes);

		$dbUsers = CUser::GetList($by = 'ID', $order = "asc", array('ID' => $strUserIDs, '!UF_DEPARTMENT' => false), array('SELECT' => array('UF_*')));
		$arUsedFields = array('PERSONAL_PHOTO', 'FULL_NAME', 'ID','LOGIN','NAME','ACTIVE','SECOND_NAME','LAST_NAME','EMAIL','DATE_REGISTER','PERSONAL_PROFESSION','PERSONAL_WWW','PERSONAL_BIRTHDAY','PERSONAL_ICQ','PERSONAL_GENDER','PERSONAL_PHONE','PERSONAL_FAX','PERSONAL_MOBILE','PERSONAL_PAGER','PERSONAL_STREET','PERSONAL_MAILBOX','PERSONAL_CITY','PERSONAL_STATE','PERSONAL_ZIP','PERSONAL_COUNTRY','WORK_PHONE','PERSONAL_NOTES','ADMIN_NOTES','XML_ID');
		while ($arUser = $dbUsers->Fetch())
		{
			if ($arParams['bCache'])
			{
				$CACHE_MANAGER->RegisterTag('intranet_user_'.$arUser['ID']);
			}

			foreach ($arUser as $key => $value)
			{
				if (!in_array($key, $arUsedFields) && !(is_array($arParams['USER_PROPERTY']) && in_array($key, $arParams['USER_PROPERTY'])))
					unset($arUser[$key]);
			}

			$arUser['IS_FEATURED'] = CIntranetUtils::IsUserHonoured($arUser['ID']);
			//$arUser['IS_ABSENT'] = CIntranetUtils::IsUserAbsent($arUser['ID']);

			$arResult['USERS'][$arUser['ID']] = $arUser;


		}
		unset($dbUsers);
	}

	if ($arParams['bCache'])
	{
		$CACHE_MANAGER->EndTagCache();
		$obCache->EndDataCache(array(
			'ENTRIES' => $arResult['ENTRIES'],
			'ENTRIES_NAV' => $arResult['ENTRIES_NAV'], // may cause problems with additional parameters in URL. we don't use $USER->GetGroups in cache id.
			'DEPARTMENTS' => $arResult['DEPARTMENTS'],
			'USERS' => $arResult['USERS'],
		));
	}
}

if (count($arResult['USERS']) > 0)
{
	foreach ($arResult['USERS'] as $USER_ID => $arUser)
	{
		$arResult['USERS'][$USER_ID]['IS_ABSENT'] = CIntranetUtils::IsUserAbsent($USER_ID);
	}
}

if ($bDataFromCache)
{
	$dbRes = CUser::GetList($by='id', $order='asc', array('ID' => implode('|', array_keys($arResult['USERS'])), '!UF_DEPARTMENT' => false, 'LAST_ACTIVITY' => 120));
	while ($arRes = $dbRes->Fetch())
	{
		$arResult['USERS'][$arRes['ID']]['IS_ONLINE'] = true;
	}
	unset($dbRes);
}

foreach ($arResult['USERS'] as $key => $arUser)
{
	if (!$bDataFromCache)
		$arResult['USERS']['IS_ONLINE'] = CIntranetUtils::IsOnline($arUser['LAST_ACTIVITY_DATE']);

	$arUser['DETAIL_URL'] = str_replace(array('#ID#', '#USER_ID#'), $arUser['ID'], $arParams['DETAIL_URL']);

	$arUser['IS_BIRTHDAY'] = CIntranetUtils::IsToday($arUser['PERSONAL_BIRTHDAY']);

	// this component is an exception for this hack - such garbage isnt't so useless here
	//$arUser['ACTIVE'] = 'Y'; // simple hack to help not to catch useless garbage to a component cache

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

	if ($arUser['PERSONAL_PHOTO'])
	{
		$arImage = CIntranetUtils::InitImage($arUser['PERSONAL_PHOTO'], 100);
		$arUser['PERSONAL_PHOTO'] = $arImage['IMG'];
	}

	$arResult['USERS'][$key] = $arUser;
}

$this->IncludeComponentTemplate();

if ($APPLICATION->GetShowIncludeAreas() && $USER->IsAdmin())
{
	// define additional icons for Site Edit mode
	$arIcons = array(
		array(
			'URL' => "javascript:".$APPLICATION->GetPopupLink(
				array(
					'URL' => "/bitrix/admin/iblock_element_edit.php?lang=".LANGUAGE_ID."&bxpublic=Y&from_module=intranet&type=".COption::GetOptionString('intranet', 'iblock_type')."&IBLOCK_ID=".COption::GetOptionInt('intranet', 'iblock_state_history')."&back_url=".urlencode($_SERVER["REQUEST_URI"]),
					'PARAMS' => array(
						'width' => 700,
						'height' => 500,
						'resize' => false,
					)
				)
			),
			'ICON' => 'bx-context-toolbar-edit-icon',
			'TITLE' => GetMessage("INTR_ISE_ICON_ADD"),
		),
	);

	$this->AddIncludeAreaIcons($arIcons);
}
?>