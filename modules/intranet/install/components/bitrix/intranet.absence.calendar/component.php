<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule('intranet'))
	return;

$arParams['VIEW_START'] = isset($arParams['VIEW_START']) ? $arParams['VIEW_START'] : 'month';
if (!isset($arParams['FIRST_DAY']))
	$arParams['FIRST_DAY'] = 1;
else
{
	$arParams['FIRST_DAY'] = intval($arParams['FIRST_DAY']);
	if ($arParams['FIRST_DAY'] < 0 || $arParams['FIRST_DAY'] >= 7)
		$arParams['FIRST_DAY'] = 1;
}

if (!isset($arParams['DAY_START']))
	$arParams['DAY_START'] = 9;
else
{
	$arParams['DAY_START'] = intval($arParams['DAY_START']);
	if ($arParams['DAY_START'] < 0 || $arParams['DAY_START'] >= 24)
		$arParams['DAY_START'] = 9;
}

if (!isset($arParams['DAY_FINISH']))
	$arParams['DAY_FINISH'] = 18;
else
{
	if (preg_match('/^(\d{1,2})(:|\.)(\d)/', trim($arParams['DAY_FINISH']), $matches))
		$arParams['DAY_FINISH'] = $matches[3] > 0 ? $matches[1]+1 : $matches[1];

	$arParams['DAY_FINISH'] = intval($arParams['DAY_FINISH']);
	if ($arParams['DAY_FINISH'] < 0 || $arParams['DAY_FINISH'] >= 24)
		$arParams['DAY_FINISH'] = 18;
}

if ($arParams['DAY_FINISH'] < $arParams['DAY_START'])
{
	$tmp = $arParams['DAY_FINISH'];
	$arParams['DAY_FINISH'] = $arParams['DAY_START'];
	$arParams['DAY_START'] = $tmp;
}

if (!$arParams['SITE_ID'] && defined('SITE_ID'))
	$arParams['SITE_ID'] = SITE_ID;

$arParams['PAGE_NUMBER'] = (int) $arParams['PAGE_NUMBER'];
if ($arParams['PAGE_NUMBER'] < 1)
	$arParams['PAGE_NUMBER'] = 0;
$arParams['PAGE_COUNT'] = 0;

$arParams['DAY_SHOW_NONWORK'] = $arParams['DAY_SHOW_NONWORK'] == 'Y' ? 'Y' : 'N';

$arFullControlsList = array('DATEPICKER', 'TYPEFILTER', 'SHOW_ALL');

if (!CModule::IncludeModule('extranet') || !CExtranet::IsExtranetSite($arParams['SITE_ID']))
	$arFullControlsList[] = 'DEPARTMENT';

if (!isset($arParams['FILTER_CONTROLS']) || !is_array($arParams['FILTER_CONTROLS']))
	$arParams['FILTER_CONTROLS'] = array('DATEPICKER', 'TYPEFILTER', 'DEPARTMENT');

$arResult['CONTROLS'] = array();
foreach ($arFullControlsList as $control)
{
	if (in_array($control, $arParams['FILTER_CONTROLS']))
		$arResult['CONTROLS'][$control] = 'on';
}

$arParams['DETAIL_URL_PERSONAL'] = isset($arParams['DETAIL_URL_PERSONAL']) ? $arParams['DETAIL_URL_PERSONAL'] : '/company/personal/user/#USER_ID#/calendar/?EVENT_ID=#EVENT_ID#';
$arParams['DETAIL_URL_DEPARTMENT'] = isset($arParams['DETAIL_URL_DEPARTMENT']) ? $arParams['DETAIL_URL_DEPARTMENT'] : '/company/structure.php?set_filter_structure=Y&structure_UF_DEPARTMENT=#ID#';

if ($arParams['IBLOCK_TYPE'] == '')
	$arParams['IBLOCK_TYPE'] = COption::GetOptionString('intranet', 'iblock_type');
$arParams['IBLOCK_ID'] = intval($arParams['IBLOCK_ID']);
if ($arParams['IBLOCK_ID'] <= 0)
	$arParams['IBLOCK_ID'] = COption::GetOptionInt('intranet', 'iblock_absence');

$arParams['IBLOCK_CHANGED'] = $arParams['IBLOCK_ID'] != COption::GetOptionInt('intranet', 'iblock_absence');

$arParams['CALENDAR_IBLOCK_ID'] = intval($arParams['CALENDAR_IBLOCK_ID']);
if ($arParams['CALENDAR_IBLOCK_ID'] !== -1)
{
	if ($arParams['CALENDAR_IBLOCK_ID'] <= 0)
		$arParams['CALENDAR_IBLOCK_ID'] = COption::GetOptionInt('intranet', 'iblock_calendar');
}

$arResult['ABSENCE_TYPES'] = array();
$dbTypeRes = CIBlockPropertyEnum::GetList(array("SORT"=>"ASC", "VALUE"=>"ASC"), array('IBLOCK_ID' => $arParams['IBLOCK_ID'], 'PROPERTY_ID' => 'ABSENCE_TYPE'));
while ($arTypeValue = $dbTypeRes->GetNext())
{
	$arResult['ABSENCE_TYPES'][$arTypeValue['ID']] = $arTypeValue;
}

$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);

if (!$arParams['DATE_FORMAT'])
{
	$arParams['DATE_FORMAT'] = (
		isset($_SESSION['intranet_absence_calendar_date_format'])
		? $_SESSION['intranet_absence_calendar_date_format']
		: CComponentUtil::GetDateFormatDefault()
	);
}
elseif (!$arParams['AJAX_CALL'])
{
	$_SESSION['intranet_absence_calendar_date_format'] = $arParams['DATE_FORMAT'];
}

if (!$arParams['DATETIME_FORMAT'])
{
	$arParams['DATETIME_FORMAT'] = (
		isset($_SESSION['intranet_absence_calendar_datetime_format'])
		? $_SESSION['intranet_absence_calendar_datetime_format']
		: CComponentUtil::GetDateTimeFormatDefault()
	);
}
elseif (!$arParams['AJAX_CALL'])
{
	$_SESSION['intranet_absence_calendar_datetime_format'] = $arParams['DATETIME_FORMAT'];
}

if ($arParams['AJAX_CALL'] == 'DATA')
{
	if (!check_bitrix_sessid())
		return;

	if ($arParams['IBLOCK_ID'] <= 0)
		return;

	$arParams['FILTER_SECTION_CURONLY'] = $arParams['FILTER_SECTION_CURONLY'] == 'Y' ? 'Y' : 'N';
	$arParams['DETAIL_URL'] = COption::GetOptionString('intranet', 'search_user_url', '/company/personal/user/#ID#/', $arParams['SITE_ID']);

	$arParams['CALLBACK'] = trim($arParams['CALLBACK']);
	$arParams['TS_START'] = date('U', $arParams['TS_START']);
	$arParams['TS_FINISH'] = date('U', $arParams['TS_FINISH']);

	$arParams['TYPES'] = $arParams['TYPES'] ? explode(',', $arParams['TYPES']) : array();

	$arParams['DEPARTMENT'] = $arParams['DEPARTMENT'] ? intval($arParams['DEPARTMENT']) : 0;

	$arParams['SHORT_EVENTS'] = $arParams['SHORT_EVENTS'] == 'N' ? 'N' : 'Y';
	$arParams['USERS_ALL'] = $arParams['USERS_ALL'] == 'Y' ? 'Y' : 'N';

	$arResult['ERROR_CODE'] = '';
	if ($arParams['CALLBACK'] == '')
		$arResult['ERROR_CODE'] = 'ERROR_NO_CALLBACK';
	elseif ($arParams['TS_START'] <= 0)
		$arResult['ERROR_CODE'] = 'ERROR_NO_TS_START';
	elseif ($arParams['TS_FINISH'] <= 0)
		$arResult['ERROR_CODE'] = 'ERROR_NO_TS_FINISH';

	$arResult['USERS'] = array();

	if ($arResult['ERROR_CODE'] == '')
	{
		$MODE = 0;
		if (count($arParams['TYPES']) <= 0)
		{
			$MODE = BX_INTRANET_ABSENCE_ALL;
		}
		else
		{
			foreach ($arParams['TYPES'] as $type)
			{
				if ($type == 'PERSONAL')
					$MODE |= BX_INTRANET_ABSENCE_PERSONAL;
				else
					$MODE |= BX_INTRANET_ABSENCE_HR;

				if ($MODE == BX_INTRANET_ABSENCE_ALL)
					break;
			}
		}

		$arIBlockElements = CIntranetUtils::GetAbsenceData(
			array(
				'CALENDAR_IBLOCK_ID' => $arParams['CALENDAR_IBLOCK_ID'],
				'ABSENCE_IBLOCK_ID' => $arParams['IBLOCK_ID'],
				'DATE_START' => ConvertTimeStamp($arParams['TS_START'], 'FULL'),
				'DATE_FINISH' => ConvertTimeStamp($arParams['TS_FINISH'], 'FULL'),
				'USERS' => false,
				'PER_USER' => true,
			), $MODE
		);

		$arUserIDs = array_keys($arIBlockElements);

		if ($arParams['USERS_ALL'] == 'Y' || count($arUserIDs) > 0)
		{
			$bExtranetInstalled = CModule::IncludeModule('extranet');
			$bExtranetSite = false || ($bExtranetInstalled && CExtranet::IsExtranetSite($arParams['SITE_ID']));

			if ($bExtranetInstalled && !$bExtranetSite && !CExtranet::IsIntranetUser($arParams['SITE_ID']))
				die;

			$allUserIds = array();

			if ($ufId = $DB->query("SELECT ID FROM b_user_field WHERE ENTITY_ID = 'USER' AND FIELD_NAME = 'UF_DEPARTMENT'")->fetch())
			{
				if ($arParams['DEPARTMENT'] > 0)
				{
					$deptIds = $arParams['FILTER_SECTION_CURONLY'] == 'N'
						? \CIntranetUtils::getIBlockSectionChildren($arParams['DEPARTMENT'])
						: array($arParams['DEPARTMENT']);

					$deptIds = array_unique(array_filter(array_map(
						function($id)
						{
							$id = (int) $id;
							return $id > 0 ? $id : 0;
						},
						$deptIds
					)));
				}

				$dbRes = $DB->query(sprintf(
					"SELECT BUF.VALUE_ID AS ID FROM b_utm_user BUF LEFT JOIN b_user U ON BUF.VALUE_ID = U.ID
						WHERE BUF.FIELD_ID = %u AND BUF.VALUE_INT %s AND U.ACTIVE = 'Y' ORDER BY U.LAST_NAME ASC",
					$ufId['ID'], empty($deptIds) ? '> 0' : sprintf('IN (%s)', join(',', $deptIds))
				));

				while ($item = $dbRes->fetch())
					$allUserIds[] = $item['ID'];

				$allUserIds = array_unique($allUserIds);
			}

			$arUserIDs = $arParams['USERS_ALL'] == 'Y' ? $allUserIds : array_intersect($allUserIds, $arUserIDs);

			if (count($arUserIDs) > 0 && $bExtranetSite)
			{
				$extAllUserIds = array_unique(array_merge(
					\CExtranet::getMyGroupsUsers($arParams['SITE_ID']),
					\CExtranet::getPublicUsers(),
					array($GLOBALS['USER']->getId())
				));

				$arUserIDs = array_intersect($arUserIDs, $extAllUserIds);
			}

			if (count($arUserIDs) > 0)
			{
				if ($arParams['PAGE_NUMBER']*100 >= count($arUserIDs))
					$arParams['PAGE_NUMBER'] = 0;
				$arParams['PAGE_COUNT'] = ceil(count($arUserIDs)/100);

				$arUserIDs = array_slice($arUserIDs, $arParams['PAGE_NUMBER']*100, 100);

				$dbUsers = \Bitrix\Main\UserTable::getList(array(
					'select' => array('ID', 'LOGIN', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'PERSONAL_PROFESSION', 'WORK_POSITION'),
					'filter' => array('=ID' => $arUserIDs),
				));

				$arUsers = array_combine($arUserIDs, array_fill(0, count($arUserIDs), false));
				while ($arUser = $dbUsers->Fetch())
				{
					$arUsers[$arUser['ID']] = array(
						'ID' => $arUser['ID'],
						'LOGIN' => $arUser['LOGIN'],
						'NAME' => $arUser['NAME'],
						'LAST_NAME' => $arUser['LAST_NAME'],
						'SECOND_NAME' => $arUser['SECOND_NAME'],
						'PERSONAL_PROFESSION' => $arUser['PERSONAL_PROFESSION'] ? $arUser['PERSONAL_PROFESSION'] : $arUser['WORK_POSITION'],
						'DATA' => is_array($arIBlockElements[$arUser['ID']]) ? $arIBlockElements[$arUser['ID']] : array(),
						'DETAIL_URL' => str_replace(array('#ID#', '#USER_ID#'), $arUser['ID'], $arParams['DETAIL_URL']),
					);

					foreach ($arUsers[$arUser['ID']]['DATA'] as $key => $arEntry)
					{
						if ($arEntry['ENTRY_TYPE'] == BX_INTRANET_ABSENCE_HR)
						{
							$arEntry['DATE_ACTIVE_FROM'] = MakeTimeStamp($arEntry['DATE_ACTIVE_FROM']);
							$arEntry['DATE_ACTIVE_TO']   = MakeTimeStamp($arEntry['DATE_ACTIVE_TO']);

							$arEntry['TYPE'] = is_array($arResult['ABSENCE_TYPES'][$arEntry['PROPERTY_ABSENCE_TYPE_ENUM_ID']])
								? $arResult['ABSENCE_TYPES'][$arEntry['PROPERTY_ABSENCE_TYPE_ENUM_ID']]['XML_ID'] : '';
						}
						elseif ($arEntry['ENTRY_TYPE'] == BX_INTRANET_ABSENCE_PERSONAL)
						{
							$arEntry['IBLOCK_ID'] = $arParams['CALENDAR_IBLOCK_ID'];

							$arEntry['DATE_ACTIVE_FROM'] = MakeTimeStamp($arEntry['DATE_FROM']);
							$arEntry['DATE_ACTIVE_TO']   = MakeTimeStamp($arEntry['DATE_TO']);

							$arEntry['TYPE'] = 'PERSONAL';
						}

						$arEntry['DATE_FROM'] = FormatDate(
							$DB->DateFormatToPhp(CSite::GetDateFormat(
								CIntranetUtils::IsDateTime($arEntry['DATE_ACTIVE_FROM']) ? 'FULL' : 'SHORT', $arParams['SITE_ID'], true
							)),
							$arEntry['DATE_ACTIVE_FROM']
						);
						$arEntry['DATE_TO'] = FormatDate(
							$DB->DateFormatToPhp(CSite::GetDateFormat(
								CIntranetUtils::IsDateTime($arEntry['DATE_ACTIVE_TO']) ? 'FULL' : 'SHORT', $arParams['SITE_ID'], true
							)),
							$arEntry['DATE_ACTIVE_TO']
						);

						if (!is_array($arParams['TYPES']) || count($arParams['TYPES'])<=0 || in_array($arEntry['TYPE'], $arParams['TYPES']))
						{

							if (
								$arParams['SHORT_EVENTS'] == 'N'
								&& $arEntry['DATE_ACTIVE_TO'] > $arEntry['DATE_ACTIVE_FROM']
								&& date('Y-m-d', $arEntry['DATE_ACTIVE_FROM']) == date('Y-m-d', $arEntry['DATE_ACTIVE_TO'])
							)
								unset($arUsers[$arUser['ID']]['DATA'][$key]);
							else
								$arUsers[$arUser['ID']]['DATA'][$key] = $arEntry;
						}
						else
							unset($arUsers[$arUser['ID']]['DATA'][$key]);
					}

					if ($arParams['USERS_ALL'] == 'N' && count($arUsers[$arUser['ID']]['DATA']) <= 0)
						unset($arUsers[$arUser['ID']]);
					elseif(is_array($arUsers[$arUser['ID']]['DATA']))
						$arUsers[$arUser['ID']]['DATA'] = array_values($arUsers[$arUser['ID']]['DATA']);
				}

				$arResult['USERS'] = array_filter($arUsers);
			}
		}
	}

	$APPLICATION->RestartBuffer();
	Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
	if ($arResult['ERROR_CODE'] != '')
		echo 'alert(\''.CUtil::JSEscape($arResult['ERROR_CODE']).'\')';
	else
		echo $arParams['CALLBACK'].'('.CUtil::PhpToJsObject(array_values($arResult['USERS'])).', '.$arParams['CURRENT_DATA_ID'].', '.$arParams['PAGE_NUMBER'].', '.$arParams['PAGE_COUNT'].')';
	die();
}
/////////////////////////////////////////
// TODO: make extranet checks here too //
/////////////////////////////////////////
elseif ($arParams['AJAX_CALL'] == 'INFO')
{
	if (!check_bitrix_sessid())
		return;

	$arParams['ID'] = intval($arParams['ID']);
	$arParams['TYPE'] = intval($arParams['TYPE']);
	$arParams['TYPE'] = $arParams['TYPE'] == 2 ? 2 : 1;

	$arParams['DETAIL_URL'] = COption::GetOptionString('intranet', 'search_user_url', '/user/#ID#/', $arParams['SITE_ID']);

	$bExtranetInstalled = CModule::IncludeModule('extranet');
	$bExtranetSite = $bExtranetInstalled && CExtranet::IsExtranetSite($arParams['SITE_ID']);

	if ($bExtranetInstalled && !$bExtranetSite && !CExtranet::IsIntranetUser($arParams['SITE_ID']))
		die();

	$calendar2 = false;
	if ($arParams['TYPE'] == 2)
	{
		$calendar2 = COption::GetOptionString("intranet", "calendar_2", "N") == "Y" && CModule::IncludeModule('calendar');
	}

	if ($arParams['TYPE'] == 1 || !$calendar2)
	{
		if ($arParams['IBLOCK_ID'] <= 0)
			$arParams['IBLOCK_ID'] = COption::GetOptionInt('intranet', $arParams['TYPE'] == 1 ? 'iblock_absence' : 'iblock_calendar');

		$arSelect = array('ID', 'IBLOCK_ID', 'IBLOCK_SECTION_ID', 'DATE_ACTIVE_FROM', 'DATE_ACTIVE_TO', 'NAME', 'PREVIEW_TEXT', 'DETAIL_TEXT');

		if ($arParams['TYPE'] == 1)
		{
			$arSelect = array_merge($arSelect, array('PROPERTY_USER', 'PROPERTY_FINISH_STATE', 'PROPERTY_STATE', 'PROPERTY_ABSENCE_TYPE'));
		}
		else
		{
			$arSelect = array_merge($arSelect, array('PROPERTY_PERIOD_TYPE', 'PROPERTY_PERIOD_COUNT', 'PROPERTY_EVENT_LENGTH', 'PROPERTY_PERIOD_ADDITIONAL'));
		}

		$dbRes = CIBlockElement::GetList(array(), array('IBLOCK_ID' => $arParams['IBLOCK_ID'], 'ID' => $arParams['ID']), false, false, $arSelect);
		$arResult['ENTRY'] = $dbRes->Fetch();

		$arResult['ENTRY']['DATE_ACTIVE_FROM'] = MakeTimeStamp($arResult['ENTRY']['DATE_ACTIVE_FROM']);
		$arResult['ENTRY']['DATE_ACTIVE_TO'] = MakeTimeStamp($arResult['ENTRY']['DATE_ACTIVE_TO']);
	}
	else
	{
		$arEvent = CCalendarEvent::GetById($arParams['ID']);

		$arResult['ENTRY'] = array(
			'ID' => $arEvent['ID'],
			'NAME' => $arEvent['NAME'],
			'PREVIEW_TEXT' => '',
			'DETAIL_TEXT' => $arEvent['DESCRIPTION'],
			'DATE_ACTIVE_FROM' => MakeTimeStamp($arEvent['DATE_FROM']),
			'DATE_ACTIVE_TO' => MakeTimeStamp($arEvent['DATE_TO']),
			'USER' => $arEvent['CREATED_BY'],
			'PROPERTY_PERIOD_TYPE_VALUE' => 'NONE', // check!
		);

		if ($arEvent['DT_SKIP_TIME'] != 'Y')
		{
			$arResult['ENTRY']['DATE_ACTIVE_FROM'] -= $arEvent['~USER_OFFSET_FROM'];
			$arResult['ENTRY']['DATE_ACTIVE_TO']   -= $arEvent['~USER_OFFSET_TO'];
		}

		if ($arEvent['RRULE'])
		{
			$arRRule = array();
			$arRRuleStr = explode(';', $arEvent['RRULE']);
			foreach ($arRRuleStr as $str)
			{
				list($param, $value) = explode('=', $str);
				$arRRule[$param] = $value;
			}

			$arResult['ENTRY']['PROPERTY_PERIOD_TYPE_VALUE'] = $arRRule['FREQ'];
		}
	}

	if ($arResult['ENTRY'])
	{
		$arResult['ENTRY']['DATE_ACTIVE_FROM'] = FormatDate(
			$DB->DateFormatToPhp(CSite::GetDateFormat(
				CIntranetUtils::IsDateTime($arResult['ENTRY']['DATE_ACTIVE_FROM']) ? 'FULL' : 'SHORT', $arParams['SITE_ID'], true
			)),
			$arResult['ENTRY']['DATE_ACTIVE_FROM']
		);
		$arResult['ENTRY']['DATE_ACTIVE_TO'] = FormatDate(
			$DB->DateFormatToPhp(CSite::GetDateFormat(
				CIntranetUtils::IsDateTime($arResult['ENTRY']['DATE_ACTIVE_TO']) ? 'FULL' : 'SHORT', $arParams['SITE_ID'], true
			)),
			$arResult['ENTRY']['DATE_ACTIVE_TO']
		);

		if ($arParams['TYPE'] == 1)
		{
			$arResult['ENTRY']['TYPE'] =
				is_array($arResult['ABSENCE_TYPES'][$arResult['ENTRY']['PROPERTY_ABSENCE_TYPE_ENUM_ID']])
				? $arResult['ABSENCE_TYPES'][$arResult['ENTRY']['PROPERTY_ABSENCE_TYPE_ENUM_ID']]['XML_ID']
				: '';

			$dbRes = CUser::GetByID($arResult['ENTRY']['PROPERTY_USER_VALUE']);
		}
		else
		{
			$arResult['ENTRY']['TYPE'] = 'PERSONAL';

			if (!$arResult['ENTRY']['USER'])
			{
				$dbRes = CIBlockSection::GetByID($arResult['ENTRY']['IBLOCK_SECTION_ID']);
				$arSection = $dbRes->Fetch();
				$dbRes = CUser::GetByID($arSection['CREATED_BY']);
			}
			else
			{
				$dbRes = CUser::GetByID($arResult['ENTRY']['USER']);
			}
		}

		if ($arUser = $dbRes->Fetch())
		{
			if (!is_array($arUser['UF_DEPARTMENT']))
				$arUser['UF_DEPARTMENT'] = array();

			if (!$bExtranetSite && count($arUser["UF_DEPARTMENT"]) <= 0)
				die();

			if ($bExtranetSite)
			{
				$arUsersInMyGroupsID = CExtranet::GetMyGroupsUsers($arParams['SITE_ID']);
				$arPublicUsersID = CExtranet::GetPublicUsers();
			}

			if ($bExtranetSite && !in_array($arUser["ID"], $arUsersInMyGroupsID) && !in_array($arUser["ID"], $arPublicUsersID) && $arUser["ID"] != $GLOBALS["USER"]->GetID())
				die();

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
				$arUser['PERSONAL_PHOTO'] = COption::GetOptionInt("socialnetwork", "default_user_picture_".$suffix, false, $arParams['SITE_ID']);
			}

			$arResult['USER'] = array(
				'ID' => $arUser['ID'],
				'LOGIN' => $arUser['LOGIN'],
				'NAME' => $arUser['NAME'],
				'LAST_NAME' => $arUser['LAST_NAME'],
				'SECOND_NAME' => $arUser['SECOND_NAME'],
				'PERSONAL_PROFESSION' => $arUser['PERSONAL_PROFESSION'],
				'PERSONAL_PHOTO' => $arUser['PERSONAL_PHOTO'],
				'WORK_POSITION' => $arUser['WORK_POSITION'],
				'UF_DEPARTMENT' => $arUser['UF_DEPARTMENT'],
				'DETAIL_URL' => str_replace(array('#ID#', '#USER_ID#'), $arUser['ID'], $arParams['DETAIL_URL'])
			);

			if ($arResult['USER']['PERSONAL_PHOTO'])
			{
				$arImage = CIntranetUtils::InitImage($arResult['USER']['PERSONAL_PHOTO'], 100);
				$arResult['USER']['PERSONAL_PHOTO'] = $arImage['IMG'];
			}
			else
			{
				$arResult['USER']['PERSONAL_PHOTO'] = false;
			}

			if (is_array($arResult['USER']['UF_DEPARTMENT']) && count($arResult['USER']['UF_DEPARTMENT']) > 0)
			{
				$dbRes = CIBlockSection::GetList(array('SORT' => 'ASC'), array('ID' => $arResult['USER']['UF_DEPARTMENT']));
				$arResult['USER']['UF_DEPARTMENT'] = array();
				while ($arSect = $dbRes->Fetch())
				{
					$arResult['USER']['UF_DEPARTMENT'][] = array('ID' => $arSect['ID'], 'NAME' => $arSect['NAME']);
				}
			}

			$APPLICATION->RestartBuffer();
			Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);

			echo CUtil::PhpToJsObject(array('USER' => $arResult['USER'], 'ENTRY' => $arResult['ENTRY']));
		}
	}

	die();
}

$arParams['bAdmin'] = CIBlockRights::UserHasRightTo($arParams['IBLOCK_ID'], $arParams['IBLOCK_ID'], 'element_edit');
$arParams['bAdminX'] = CIBlockRights::UserHasRightTo($arParams['IBLOCK_ID'], $arParams['IBLOCK_ID'], 'iblock_export');

$arUserFields = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields('USER', 0, LANGUAGE_ID);
$arResult['UF_DEPARTMENT_field'] = $arUserFields['UF_DEPARTMENT'];
$arResult['UF_DEPARTMENT_field']['FIELD_NAME'] = 'department';
$arResult['UF_DEPARTMENT_field']['MULTIPLE'] = 'N';
$arResult['UF_DEPARTMENT_field']['SETTINGS']['LIST_HEIGHT'] = 1;

$APPLICATION->SetAdditionalCSS('/bitrix/themes/.default/pubstyles.css');
$APPLICATION->AddHeadScript('/bitrix/js/main/utils.js');
CJSCore::Init(array('window', 'ajax', 'date'));

if ($arParams['bAdmin'] || $arParams['bAdminX'])
	CJSCore::Init(array('popup'));

$this->IncludeComponentTemplate();
?>
