<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

/*
Parameters:

NUM_USERS => 5 - number of users to show
DATE_INTERVAL => 60 - date interval to show (days) // not yet
*/

if (!CModule::IncludeModule('intranet'))
{
	return;
}

$arParams['NUM_USERS'] = intval($arParams['NUM_USERS']);

if (strlen(trim($arParams["NAME_TEMPLATE"])) <= 0)
{
	$arParams["NAME_TEMPLATE"] = CSite::GetNameFormat();
}
$arParams['SHOW_LOGIN'] = $arParams['SHOW_LOGIN'] != "N" ? "Y" : "N";

if (!array_key_exists("PM_URL", $arParams))
	$arParams["~PM_URL"] = $arParams["PM_URL"] = "/company/personal/messages/chat/#USER_ID#/";
if (!array_key_exists("PATH_TO_CONPANY_DEPARTMENT", $arParams))
	$arParams["~PATH_TO_CONPANY_DEPARTMENT"] = $arParams["PATH_TO_CONPANY_DEPARTMENT"] = "/company/structure.php?set_filter_structure=Y&structure_UF_DEPARTMENT=#ID#";
if (IsModuleInstalled("video") && !array_key_exists("PATH_TO_VIDEO_CALL", $arParams))
	$arParams["~PATH_TO_VIDEO_CALL"] = $arParams["PATH_TO_VIDEO_CALL"] = "/company/personal/video/#USER_ID#/";

$arParams['SHOW_YEAR'] = $arParams['SHOW_YEAR'] == 'Y' ? 'Y' : ($arParams['SHOW_YEAR'] == 'M' ? 'M' : 'N');

if (!$arParams['DATE_FORMAT']) $arParams['DATE_FORMAT'] = CComponentUtil::GetDateFormatDefault();
if (!$arParams['DATE_FORMAT_NO_YEAR']) $arParams['DATE_FORMAT_NO_YEAR'] = CComponentUtil::GetDateFormatDefault(true);

$arParams['DETAIL_URL'] = trim($arParams['DETAIL_URL']);
if (!$arParams['DETAIL_URL'])
	$arParams['~DETAIL_URL'] = $arParams['DETAIL_URL'] = COption::GetOptionString('intranet', 'search_user_url', '/user/#ID#/');

$arParams['DEPARTMENT'] = intval($arParams['DEPARTMENT']);
$arParams['bShowFilter'] = $arParams['DEPARTMENT'] <= 0;

// for bitrix:main.user.link
$arTooltipFieldsDefault = serialize(array(
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

if (
	!array_key_exists("USER_PROPERTY", $arParams)
	|| !is_array($arParams["USER_PROPERTY"])
	|| empty($arParams["USER_PROPERTY"])
)
{
	$arParams["USER_PROPERTY"] = array("WORK_POSITION");
}
// don't show department filter when extranet

if (CModule::IncludeModule('extranet') && CExtranet::IsExtranetSite())
	$arParams['bShowFilter'] = false;

if(!isset($arParams["CACHE_TIME"]))
	$arParams["CACHE_TIME"] = 3600;

if ($arParams['CACHE_TYPE'] == 'A')
	$arParams['CACHE_TYPE'] = COption::GetOptionString("main", "component_cache_on", "Y");

$arParams['bCache'] = $arParams['CACHE_TYPE'] == 'Y' && $arParams['CACHE_TIME'] > 0;

if ($arParams['bCache'])
{
	$cache_dir = '/'.SITE_ID.$this->GetRelativePath();
	$cache_id = $this->GetName().'|'.$arParams['NUM_USERS'].'|'.SITE_ID.'|'.CTimeZone::GetOffset();//.'|'.$USER->GetGroups();
	$obCache = new CPHPCache();
}

$arResult['CURRENT_USER'] = array();

$arResult['DEPARTMENT'] = $arParams['DEPARTMENT'] > 0 ? $arParams['DEPARTMENT'] : (intval($_REQUEST['department']) > 0 ? intval($_REQUEST['department']) : 0);

if ($arParams['bCache'] && $arResult['DEPARTMENT'] > 0)
{
	$cache_id .= '|'.$arResult['DEPARTMENT'];
}
if (CModule::IncludeModule('extranet') && CExtranet::IsExtranetSite())
{
	$cache_id .= '|'.$GLOBALS["USER"]->GetID();
}

// and only from now on we can start caching ;-)
if ($arParams['bCache'] && $obCache->InitCache($arParams['CACHE_TIME'], $cache_id, $cache_dir))
{
	$vars = $obCache->GetVars();

	$arResult['bUsersCached'] = true;
	$arResult['USERS'] = $vars['USERS'];
}
else
{
	$arResult['bUsersCached'] = false;

	if ($arParams['bCache'])
	{
		$obCache->StartDataCache();
		global $CACHE_MANAGER;
		$CACHE_MANAGER->StartTagCache($cache_dir);

		if (defined("BX_COMP_MANAGED_CACHE"))
		{
			$CACHE_MANAGER->RegisterTag('intranet_users');
			$CACHE_MANAGER->RegisterTag('intranet_birthday');
		}
	}

	$arFilter = array(
		'ACTIVE' => 'Y',
		'!EXTERNAL_AUTH_ID' => array('replica', 'email', 'bot', 'imconnector'),
	);

	if ($arResult['DEPARTMENT'] > 0 && (!CModule::IncludeModule('extranet') || !CExtranet::IsExtranetSite()))
	{
		$arFilter['UF_DEPARTMENT'] = CIntranetUtils::GetIBlockSectionChildren(intval($arResult['DEPARTMENT']));
	}
	elseif (!CModule::IncludeModule('extranet') || !CExtranet::IsExtranetSite())
		$arFilter["!UF_DEPARTMENT"] = false;

	if (CModule::IncludeModule('extranet') && CExtranet::IsExtranetSite())
	{
		$arIDs = array_merge(CExtranet::GetMyGroupsUsers(SITE_ID), CExtranet::GetPublicUsers());

		if ($arParams['bCache'] && defined("BX_COMP_MANAGED_CACHE"))
		{
			$CACHE_MANAGER->RegisterTag('extranet_public');
			$CACHE_MANAGER->RegisterTag('extranet_user_'.$USER->GetID());
		}

		if (count($arIDs) > 0)
			$arFilter['ID'] = implode('|', array_unique($arIDs));
		else
		{
			$bDisable = true;
		}
	}

	$arNavParams = array('nTopCount' => $arParams['NUM_USERS']);

	$arRequiredFields = array('ID', 'PERSONAL_BIRTHDAY');
	$arSelectFields = !empty($arParams['SELECT_FIELDS']) && is_array($arParams['SELECT_FIELDS'])
		? array_merge($arRequiredFields, $arParams['SELECT_FIELDS'])
		: array('*', 'UF_*');

	$arResult['USERS'] = array();

	if (in_array('*', $arSelectFields) || in_array('UF_*', $arSelectFields))
	{
		$dbUsers = CUser::getList(
			$by = 'CURRENT_BIRTHDAY', $order = 'ASC',
			$arFilter,
			array(
				'SELECT'     => $arRequiredFields,
				'NAV_PARAMS' => $arNavParams,
				'FIELDS'     => $arRequiredFields
			)
		);

		$num = 0;
		while ($arUser = $dbUsers->fetch())
		{
			if (!$arUser['PERSONAL_BIRTHDAY'])
				continue;

			if (++$num > $arParams['NUM_USERS'])
				break;

			$arResult['USERS'][$arUser['ID']] = $arUser['ID'];
		}

		if (!empty($arResult['USERS']))
		{
			$dbUsers = CUser::getList(
				$by = 'ID', $order = 'DESC',
				array(
					'ID' => join('|', $arResult['USERS'])
				),
				array(
					'SELECT' => $arSelectFields,
					'FIELDS' => $arSelectFields
				)
			);
		}
		else
		{
			$dbUsers = new \CDBResult();
			$dbUsers->initFromArray(array());
		}
	}
	else
	{
		$dbUsers = CUser::getList(
			$by = 'CURRENT_BIRTHDAY', $order = 'ASC',
			$arFilter,
			array(
				'SELECT'     => $arSelectFields,
				'NAV_PARAMS' => $arNavParams,
				'FIELDS'     => $arSelectFields
			)
		);
	}

	$num = 0;
	while ($arUser = $dbUsers->Fetch())
	{
		if (!$arUser['PERSONAL_BIRTHDAY'])
			continue;

		if ((++$num) > $arParams['NUM_USERS'])
			break;

		if ($arParams['bCache'] && defined("BX_COMP_MANAGED_CACHE"))
		{
			$CACHE_MANAGER->RegisterTag('intranet_user_'.$arUser['ID']);
		}

		$arBirthDate = ParseDateTime($arUser['PERSONAL_BIRTHDAY'], CSite::GetDateFormat('SHORT'));
		if (isset($arBirthDate["M"]))
		{
			if (is_numeric($arBirthDate["M"]))
			{
				$arBirthDate["MM"] = intval($arBirthDate["M"]);
			}
			else
			{
				$arBirthDate["MM"] = GetNumMonth($arBirthDate["M"], true);
				if (!$arBirthDate["MM"])
					$arBirthDate["MM"] = intval(date('m', strtotime($arBirthDate["M"])));
			}
		}
		elseif (isset($arBirthDate["MMMM"]))
		{
			if (is_numeric($arBirthDate["MMMM"]))
			{
				$arBirthDate["MM"] = intval($arBirthDate["MMMM"]);
			}
			else
			{
				$arBirthDate["MM"] = GetNumMonth($arBirthDate["MMMM"]);
				if (!$arBirthDate["MM"])
					$arBirthDate["MM"] = intval(date('m', strtotime($arBirthDate["MMMM"])));
			}
		}
		$arUser['IS_BIRTHDAY'] = (intval($arBirthDate['MM']) == date('n', time()+CTimeZone::GetOffset())) && (intval($arBirthDate['DD']) == date('j', time()+CTimeZone::GetOffset()));

		$arUser['arBirthDate'] = $arBirthDate;

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

		$arResult['USERS'][$arUser['ID']] = $arUser;
	}

	unset($dbUsers);

	if ($arParams['bCache'])
	{
		$CACHE_MANAGER->EndTagCache();
		$obCache->EndDataCache(array(
			'USERS' => $arResult['USERS'],
		));
	}
}

//echo '<pre>'; print_r($arResult['USERS']); echo '</pre>';
$this->IncludeComponentTemplate();
?>