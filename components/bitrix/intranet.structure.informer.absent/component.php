<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule('intranet'))
	return;

$arParams['IBLOCK_ID'] = intval($arParams['IBLOCK_ID']);
if (!$arParams['IBLOCK_ID']) 
	$arParams['IBLOCK_ID'] = COption::GetOptionInt('intranet', 'iblock_absence');

$arParams['IBLOCK_TYPE'] = $arParams['IBLOCK_TYPE'];
if (!$arParams['IBLOCK_TYPE']) 
	$arParams['IBLOCK_TYPE'] = COption::GetOptionString('intranet', 'iblock_type');


$arParams['CALENDAR_IBLOCK_ID'] = intval($arParams['CALENDAR_IBLOCK_ID']);
if ($arParams['CALENDAR_IBLOCK_ID'] <= 0) 
	$arParams['CALENDAR_IBLOCK_ID'] = COption::GetOptionInt('intranet', 'iblock_calendar');

$arParams['DETAIL_URL'] = COption::GetOptionString('intranet', 'search_user_url', '/company/personal/user/#ID#/');

$arParams['NUM_USERS'] = intval($arParams['NUM_USERS']);
if ($arParams['NUM_USERS'] <= 0) 
	$arParams['NUM_USERS'] = 5;

$arParams['DEPARTMENT'] = intval($arParams['DEPARTMENT']);

if (trim($arParams["NAME_TEMPLATE"]) == '')
	$arParams["NAME_TEMPLATE"] = CSite::GetNameFormat();
$arParams['SHOW_LOGIN'] = $arParams['SHOW_LOGIN'] != "N" ? "Y" : "N";

if (!array_key_exists("PM_URL", $arParams))
	$arParams["~PM_URL"] = $arParams["PM_URL"] = "/company/personal/messages/chat/#USER_ID#/";
if (!array_key_exists("PATH_TO_CONPANY_DEPARTMENT", $arParams))
	$arParams["~PATH_TO_CONPANY_DEPARTMENT"] = $arParams["PATH_TO_CONPANY_DEPARTMENT"] = "/company/structure.php?set_filter_structure=Y&structure_UF_DEPARTMENT=#ID#";
if (IsModuleInstalled("video") && !array_key_exists("PATH_TO_VIDEO_CALL", $arParams))
	$arParams["~PATH_TO_VIDEO_CALL"] = $arParams["PATH_TO_VIDEO_CALL"] = "/company/personal/video/#USER_ID#/";


$arParams['SHOW_YEAR'] = $arParams['SHOW_YEAR'] == 'Y' ? 'Y' : ($arParams['SHOW_YEAR'] == 'M' ? 'M' : 'N');

if (!$arParams['DATE_FORMAT']) $arParams['DATE_FORMAT'] = CComponentUtil::GetDateFormatDefault();
if (!$arParams['DATE_TIME_FORMAT']) $arParams['DATE_TIME_FORMAT'] = CComponentUtil::GetDateTimeFormatDefault();

$arResult['MODES_LIST'] = array(/*'all', */'now', 'today', 'tomorrow'/*, 'after_tomorrow'*/);
$arParams['mode'] = $_GET['absence_mode'];
if (!in_array($arParams['mode'], $arResult['MODES_LIST']))
	//$arParams['mode'] = 'all';
	$arParams['mode'] = 'now';


if(!isset($arParams["CACHE_TIME"]))
	$arParams["CACHE_TIME"] = 3600;
if(!isset($arParams["CACHE_TYPE"]))
	$arParams["CACHE_TYPE"] = 'A';

if ($this->StartResultCache(false, $arParams['mode'].'|'.($arParams['DEPARTMENT'] > 0 ? $arParams['DEPARTMENT'] : '')))
{
	global $CACHE_MANAGER;
	$CACHE_MANAGER->RegisterTag('intranet_users');

	$format = $DB->DateFormatToPHP(CLang::GetDateFormat("SHORT"));

	$USERS = false;
	if ($arParams['DEPARTMENT'])
	{
		$USERS = array();
		$dbRes = CUser::GetList('ID', "ASC", array(
				'ACTIVE' => 'Y',
				'UF_DEPARTMENT' => CIntranetUtils::GetIBlockSectionChildren($arParams['DEPARTMENT']),
			)
		);
		while ($arRes = $dbRes->Fetch())
			$USERS[] = $arRes['ID'];
	}

	switch($arParams['mode'])
	{
		case 'today':
			$date_start = $date_finish = date($format);
		break;
		case 'tomorrow':
			$date_start = $date_finish = date($format, strtotime('+1 day'));
		break;
		case 'after_tomorrow':
			$date_start = $date_finish = date($format, strtotime('+2 day'));
		break;
		case 'now':
		default: 
			$date_start = $date_finish = date($format);
		break;
	}
	
	$arFilter = array(
			'CALENDAR_IBLOCK_ID' => $arParams['CALENDAR_IBLOCK_ID'],
			'ABSENCE_IBLOCK_ID' => $arParams['IBLOCK_ID'],
			'DATE_START' => $date_start,
			'DATE_FINISH' => $date_finish,
			'USERS' => $USERS,
			'PER_USER' => false,
		);

	//echo '<pre>'; print_r($arFilter); echo '</pre>';
	
	$arResult['ENTRIES'] = CIntranetUtils::GetAbsenceData(
		$arFilter, BX_INTRANET_ABSENCE_ALL
	);

	$arUserIDs = array();
	foreach ($arResult['ENTRIES'] as $key => $arEntry)
	{
		$arUserIDs[] = $arEntry['USER_ID'];
		$arResult['ENTRIES'][$key]['DATE_ACTIVE_FROM_TS'] = MakeTimeStamp($arEntry['DATE_FROM'], CSite::GetDateFormat('FULL'));
		$arResult['ENTRIES'][$key]['DATE_ACTIVE_TO_TS'] = MakeTimeStamp($arEntry['DATE_TO'], CSite::GetDateFormat('FULL'));
		
		$arResult['ENTRIES'][$key]['DATE_FROM'] = FormatDate(
			$arParams['DATE'.(CIntranetUtils::IsDateTime($arResult['ENTRIES'][$key]['DATE_ACTIVE_FROM_TS']) ? '_TIME' : '').'_FORMAT'], 
			$arResult['ENTRIES'][$key]['DATE_ACTIVE_FROM_TS']
		);
		$arResult['ENTRIES'][$key]['DATE_TO'] = FormatDate(
			$arParams['DATE'.(CIntranetUtils::IsDateTime($arResult['ENTRIES'][$key]['DATE_ACTIVE_TO_TS']) ? '_TIME' : '').'_FORMAT'], 
			$arResult['ENTRIES'][$key]['DATE_ACTIVE_TO_TS']
		);
		
		if ($arParams['mode'] == 'now')
		{
			$test1 = $arResult['ENTRIES'][$key]['DATE_ACTIVE_FROM_TS'];
			$test2 = $arResult['ENTRIES'][$key]['DATE_ACTIVE_TO_TS'];
			
			if (0==($test2+date('Z'))%86400) $test2+=86399;

			$ts = time() + \CTimeZone::getOffset();
			if ($test1 > $ts || $test2 < $ts)
				unset($arResult['ENTRIES'][$key]);
		}
	}

	// foreach ($arResult['ENTRIES'] as $key => $arEntry)
		// echo $arEntry['DATE_FROM'].' - '.$arEntry['DATE_TO'].'<br />';
	
	usort($arResult['ENTRIES'], array('CIntranetUtils', '__absence_sort'));
	// echo '<hr />';
	// foreach ($arResult['ENTRIES'] as $key => $arEntry)
		// echo $arEntry['DATE_FROM'].' - '.$arEntry['DATE_TO'].'<br />';

	$arResult['USERS'] = array();
	if (count($arUserIDs) > 0)
	{
		$arFilter = array('ID' => implode('|', $arUserIDs), 'ACTIVE' => 'Y', '!UF_DEPARTMENT' => false);
		
		$dbUsers = CUser::GetList('ID', 'desc', $arFilter);
		while ($arUser = $dbUsers->Fetch())
		{
			$CACHE_MANAGER->RegisterTag('intranet_user_'.$arUser['ID']);
			
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
	}
	
	//echo '<pre>'; print_r($arResult); echo '</pre>';
	$this->IncludeComponentTemplate();
}

$APPLICATION->SetAdditionalCSS('/bitrix/themes/.default/pubstyles.css');
$APPLICATION->AddHeadScript('/bitrix/js/main/utils.js');

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
			'TITLE' => GetMessage("INTR_ISIA_ICON_ADD"),
		),
	);
	
	$this->AddIncludeAreaIcons($arIcons);
}
?>