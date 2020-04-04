<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule('intranet'))
	return;

$arParams['DETAIL_URL'] = COption::GetOptionString('intranet', 'search_user_url', '/user/#ID#/');

$arParams['NUM_USERS'] = intval($arParams['NUM_USERS']);
if ($arParams['NUM_USERS'] <= 0) $arParams['NUM_USERS'] = 5;

$arParams['DEPARTMENT'] = intval($arParams['DEPARTMENT']);
$arParams['bShowFilter'] = ($USER->GetID() > 0) && ($arParams['DEPARTMENT'] <= 0);

$arParams['IBLOCK_ID'] = intval($arParams['IBLOCK_ID']);
if ($arParams['IBLOCK_ID'] <= 0)
	$arParams['IBLOCK_ID'] = COption::GetOptionInt('intranet', 'iblock_state_history');
$arParams['IBLOCK_TYPE'] = $arParams['IBLOCK_TYPE'];
if (!$arParams['IBLOCK_TYPE'])
	$arParams['IBLOCK_TYPE'] = COption::GetOptionString('intranet', 'iblock_type');

if (strlen(trim($arParams["NAME_TEMPLATE"])) <= 0)
	$arParams["NAME_TEMPLATE"] = CSite::GetNameFormat();
$arParams['SHOW_LOGIN'] = $arParams['SHOW_LOGIN'] != "N" ? "Y" : "N";

if (!array_key_exists("PM_URL", $arParams))
	$arParams["~PM_URL"] = $arParams["PM_URL"] = "/company/personal/messages/chat/#USER_ID#/";
if (!array_key_exists("PATH_TO_CONPANY_DEPARTMENT", $arParams))
	$arParams["~PATH_TO_CONPANY_DEPARTMENT"] = $arParams["PATH_TO_CONPANY_DEPARTMENT"] = "/company/structure.php?set_filter_structure=Y&structure_UF_DEPARTMENT=#ID#";
if (IsModuleInstalled("video") && !array_key_exists("PATH_TO_VIDEO_CALL", $arParams))
	$arParams["~PATH_TO_VIDEO_CALL"] = $arParams["PATH_TO_VIDEO_CALL"] = "/company/personal/video/#USER_ID#/";


if (!$arParams['DATE_FORMAT']) $arParams['DATE_FORMAT'] = CComponentUtil::GetDateFormatDefault();

$arParams['SHOW_YEAR'] = $arParams['SHOW_YEAR'] == 'Y' ? 'Y' : ($arParams['SHOW_YEAR'] == 'M' ? 'M' : 'N');

if(!isset($arParams["CACHE_TIME"]))
	$arParams["CACHE_TIME"] = 3600;
if(!isset($arParams["CACHE_TYPE"]))
	$arParams["CACHE_TYPE"] = 'A';

$arFilter = array(
		'IBLOCK_ID' => $arParams['IBLOCK_ID'],
		'ACTIVE' => 'Y',
		'!PROPERTY_USER_ACTIVE' => 'N',
		'ACTIVE_DATE' => 'Y',
	);

$arResult['ONLY_MINE'] = ($_REQUEST['only_mine'] == 'Y') ? 'Y' : 'N';
$cacheID = (($arParams['bShowFilter'] && $arResult['ONLY_MINE']=='Y') || !$arParams['bShowFilter'] ? 'FILTERED' : 'NON_FILTERED').'|'.intval($arParams['DEPARTMENT']).'|'.$USER->GetID();

if ($this->StartResultCache(false, $cacheID))
{
	global $CACHE_MANAGER;
	$CACHE_MANAGER->RegisterTag('intranet_users');

	if ($arParams['bShowFilter'])
	{
		$dbCurrentUser = CUser::GetByID($USER->GetID());
		if (($arResult['CURRENT_USER'] = $dbCurrentUser->Fetch()) && $arResult['CURRENT_USER']['UF_DEPARTMENT'])
		{
			$arResult['CURRENT_USER']['DEPARTMENT_TOP'] = CIntranetUtils::GetIBlockTopSection($arResult['CURRENT_USER']['UF_DEPARTMENT']);
			
			if ($arResult['ONLY_MINE'] == 'Y')
			{
				$arParams['DEPARTMENT'] = $arResult['CURRENT_USER']['DEPARTMENT_TOP'];
			}
		}
		else
		{
			$arParams['bShowFilter'] = false;
		}
	}

	$dbEnum = CIBlockPropertyEnum::GetList(array("DEF"=>"DESC", "SORT"=>"ASC"), array('IBLOCK_ID' => $arParams['IBLOCK_ID'], 'CODE' => 'STATE', 'EXTERNAL_ID' => 'ACCEPTED'));
	if ($arEnum = $dbEnum->Fetch())
	{
		$arFilter['PROPERTY_STATE'] = $arEnum['ID'];
	}
	else
	{
		// are dinos alive?
		$arFilter['PREVIEW_TEXT'] = '%'.GetMessage('INTR_ISIN_ACCEPTED').'%';
	}
	unset($dbEnum);

	if ($arParams['DEPARTMENT'])
	{
		$arFilter['PROPERTY_DEPARTMENT'] = CIntranetUtils::GetIBlockSectionChildren($arParams['DEPARTMENT']);
	}

	$arResult['USERS'] = array();
	$arResult['ENTRIES'] = array();
	$arUserIDs = array();

	$dbEntries = CIBlockElement::GetList(
		array('active_from' => 'desc'),
		$arFilter,
		false,
		array('nTopCount' => $arParams['NUM_USERS']),
		array('IBLOCK_ID', 'NAME', 'DATE_ACTIVE_FROM', 'PROPERTY_USER')
	);

	while ($arRes = $dbEntries->Fetch())
	{
		$arUserIDs[] = $arRes['PROPERTY_USER_VALUE'];
		$arResult['ENTRIES'][] = $arRes;
	}
	unset($dbEntries);

	if (count($arResult['ENTRIES']) > 0)
	{
		$dbUsers = CUser::GetList(
			$by = 'id', 
			$order = 'asc', 
			array(
				'ID' => implode('|', $arUserIDs), 
				'!UF_DEPARTMENT' => false,
				'ACTIVE' => 'Y'
			)
		);
		while ($arUser = $dbUsers->Fetch())
		{
			$CACHE_MANAGER->RegisterTag('intranet_user_'.$arUser['ID']);

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

	CUtil::InitJSCore(array('tooltip'));

	$this->IncludeComponentTemplate();
}
?>