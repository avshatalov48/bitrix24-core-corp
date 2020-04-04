<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule('intranet')) return;

$arParams['NUM_USERS'] = intval($arParams['NUM_USERS']);

if (strlen(trim($arParams["NAME_TEMPLATE"])) <= 0)
	$arParams["NAME_TEMPLATE"] = CSite::GetNameFormat();
$arParams['SHOW_LOGIN'] = $arParams['SHOW_LOGIN'] != "N" ? "Y" : "N";

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

if (!array_key_exists("PM_URL", $arParams))
	$arParams["~PM_URL"] = $arParams["PM_URL"] = "/company/personal/messages/chat/#USER_ID#/";
if (!array_key_exists("PATH_TO_CONPANY_DEPARTMENT", $arParams))
	$arParams["~PATH_TO_CONPANY_DEPARTMENT"] = $arParams["PATH_TO_CONPANY_DEPARTMENT"] = "/company/structure.php?set_filter_structure=Y&structure_UF_DEPARTMENT=#ID#";
if (IsModuleInstalled("video") && !array_key_exists("PATH_TO_VIDEO_CALL", $arParams))
	$arParams["~PATH_TO_VIDEO_CALL"] = $arParams["PATH_TO_VIDEO_CALL"] = "/company/personal/video/#USER_ID#/";

$arParams['DETAIL_URL'] = trim($arParams['DETAIL_URL']);
if (!$arParams['DETAIL_URL'])
	$arParams['DETAIL_URL'] = COption::GetOptionString('intranet', 'search_user_url', '/user/#ID#/');

$arParams['IBLOCK_TYPE'] = trim($arParams['IBLOCK_TYPE']);
$arParams['IBLOCK_ID'] = intval($arParams['IBLOCK_ID']);

if (!$arParams['IBLOCK_TYPE'])
	$arParams['IBLOCK_TYPE'] = COption::GetOptionString('intranet', 'iblock_type');
if (!$arParams['IBLOCK_ID'])
	$arParams['IBLOCK_ID'] = COption::GetOptionInt('intranet', 'iblock_honour');

if(!isset($arParams["CACHE_TIME"]))
	$arParams["CACHE_TIME"] = 3600;

if ($arParams['CACHE_TYPE'] == 'A')
	$arParams['CACHE_TYPE'] = COption::GetOptionString("main", "component_cache_on", "Y");

$arParams['bCache'] = $arParams['CACHE_TYPE'] == 'Y' && $arParams['CACHE_TIME'] > 0;

if ($arParams['bCache'])
{
	$cache_dir = '/'.SITE_ID.$this->GetRelativePath();
	$cache_id = $this->GetName().'|'.$arParams['NUM_USERS'].'|'.$arParams['IBLOCK_ID'];//.'|'.$USER->GetGroups();
	$obCache = new CPHPCache();
}
if ($arParams['bCache'] && $obCache->InitCache($arParams['CACHE_TIME'], $cache_id, $cache_dir))
{
	$vars = $obCache->GetVars();
	$arResult['bUsersCached'] = true;
	$arResult['USERS'] = $vars['USERS'];
	$arResult['ENTRIES'] = $vars['ENTRIES'];
}
else
{
	$arResult['bUsersCached'] = false;

	if ($arParams['bCache'])
	{
		$obCache->StartDataCache();
		global $CACHE_MANAGER;
		$CACHE_MANAGER->StartTagCache($cache_dir);
		//$CACHE_MANAGER->RegisterTag('intranet_users'); // we don't have to recache this component during all users recache
	}

	$arFilter = array('IBLOCK_ID' => $arParams['IBLOCK_ID'], 'ACTIVE' => 'Y', '<=DATE_ACTIVE_FROM' => date($DB->DateFormatToPHP(CLang::GetDateFormat("FULL"))), '>=DATE_ACTIVE_TO' => date($DB->DateFormatToPHP(CLang::GetDateFormat("FULL"))));

	$dbRes = CIBlockElement::GetList(
		array('active_from' => 'desc', 'active_to' => 'asc'),
		$arFilter,
		false,
		array('nTopCount' => $arParams['NUM_USERS']),
		array('IBLOCK_ID', 'NAME', 'PREVIEW_TEXT', 'PREVIEW_TEXT_TYPE', 'DETAIL_TEXT', 'PROPERTY_USER', 'DATE_ACTIVE_FROM', 'DATE_ACTIVE_TO')
	);

	$arResult['ENTRIES'] = array();
	$arUserIDs = array();
	while ($arRes = $dbRes->Fetch())
	{
		$arUserIDs[] = $arRes['PROPERTY_USER_VALUE'];
		$arResult['ENTRIES'][] = $arRes;
	}
	unset($dbRes);

	$arResult['USERS'] = array();
	if (count($arUserIDs) > 0)
	{
		$dbUsers = CUser::GetList(
			$by = 'ID',
			$order = 'asc',
			array('ID' => implode('|', $arUserIDs), 'ACTIVE' => 'Y', '!UF_DEPARTMENT' => false),
			array('SELECT' => array('UF_*'))
		);
		while ($arUser = $dbUsers->Fetch())
		{
			if ($arParams['bCache'])
			{
				$CACHE_MANAGER->RegisterTag('intranet_user_'.$arUser['ID']);
			}

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
	}

	foreach ($arResult['ENTRIES'] as $k => $entry)
	{
		if (!array_key_exists($entry['PROPERTY_USER_VALUE'], $arResult['USERS']))
			unset($arResult['ENTRIES'][$k]);
	}

	if ($arParams['bCache'])
	{
		$CACHE_MANAGER->EndTagCache();
		$obCache->EndDataCache(array(
			'USERS' => $arResult['USERS'],
			'ENTRIES' => $arResult['ENTRIES'],
		));
	}
}

$this->IncludeComponentTemplate();

if ($APPLICATION->GetShowIncludeAreas() && $USER->IsAdmin())
{
	// define additional icons for Site Edit mode
	$arIcons = array(
		// form template edit icon
		array(
			'URL' => "javascript:".$APPLICATION->GetPopupLink(
				array(
					'URL' => "/bitrix/admin/iblock_element_edit.php?lang=".LANGUAGE_ID."&bxpublic=Y&from_module=intranet&type=".$arParams['IBLOCK_TYPE']."&IBLOCK_ID=".$arParams['IBLOCK_ID']."&back_url=".urlencode($_SERVER["REQUEST_URI"]),
					'PARAMS' => array(
						'width' => 700,
						'height' => 500,
						'resize' => false,
					)
				)
			),
			'ICON' => 'bx-context-toolbar-edit-icon',
			'TITLE' => GetMessage("INTR_ISH_ICON_ADD"),
		),
	);

	$this->AddIncludeAreaIcons($arIcons);
}
?>