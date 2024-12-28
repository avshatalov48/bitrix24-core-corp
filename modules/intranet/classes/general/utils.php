<?php

use Bitrix\HumanResources\Compatibility\Adapter\StructureBackwardAdapter;
use Bitrix\HumanResources\Compatibility\Utils\DepartmentBackwardAccessCode;
use Bitrix\HumanResources\Config\Storage;
use Bitrix\HumanResources\Enum\DepthLevel;
use Bitrix\Main\Application;
use Bitrix\Intranet\MainPage;
use Bitrix\Main\Loader;

IncludeModuleLangFile(__FILE__);

class CIntranetUtils
{
	private static array $cache = [];

	private static $SECTIONS_SETTINGS_CACHE = null;
	private static $SECTIONS_SETTINGS_WITHOUT_EMPLOYEE_CACHE = null;

	/**
	 * @deprecated Use \Bitrix\HumanResources\Contract\Repository\NodeMemberRepository::findAllByEntityIdAndEntityType instead
	 * @param int $userId
	 *
	 * @return mixed|null
	 */
	public static function GetUserDepartments(int $userId)
	{
		if (!isset(self::$cache[$userId])
			&&
			($arRes = CUser::GetList('ID', 'ASC',
				['ID' => $userId],
				['SELECT' => ['UF_DEPARTMENT'], 'FIELDS' => ['ID']]
			)->Fetch())
		)
		{
			self::$cache[$userId] = $arRes['UF_DEPARTMENT'];
		}

		return self::$cache[$userId] ?? null;
	}

	/**
	 * @deprecated Use \Bitrix\HumanResources\Contract\Repository\NodeRepository::getChildOf instead
	 *
	 * @param int $departmentId
	 *
	 * @return array|null
	 * return null (for wrong department) or array of IDs of immediate sub-departments
	 */
	public static function getSubDepartments($departmentId = 0): null|array
	{
		if (self::$SECTIONS_SETTINGS_WITHOUT_EMPLOYEE_CACHE === null)
		{
			self::_GetDeparmentsTreeWithoutEmployee();
		}

		if (isset(self::$SECTIONS_SETTINGS_WITHOUT_EMPLOYEE_CACHE['TREE'][$departmentId]))
		{
			$arDepartmentsIdentifiers = self::$SECTIONS_SETTINGS_WITHOUT_EMPLOYEE_CACHE['TREE'][$departmentId];
		} else
		{
			$arDepartmentsIdentifiers = null;
		}

		return ($arDepartmentsIdentifiers);
	}

	/**
	 * @deprecated Use \Bitrix\HumanResources\Contract\Repository\NodeRepository::getChildOfNodeCollection instead
	 *
	 * @param $arSections
	 *
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
 	*/
	public static function GetIBlockSectionChildren($arSections)
	{
		if (!is_array($arSections))
		{
			$arSections = [ $arSections ];
		}

		if (
			Loader::includeModule('humanresources')
			&& Storage::instance()->isIntranetUtilsDisabled())
		{
			$departments = [];
			array_walk(
				$arSections, function($department) use (&$departments) {
				$departments[] = DepartmentBackwardAccessCode::makeById((int)$department);
			}
			);
			$nodes = \Bitrix\HumanResources\Service\Container::getNodeRepository()->findAllByAccessCodes($departments);
			$childNodes = \Bitrix\HumanResources\Service\Container::getNodeRepository()->getChildOfNodeCollection(
				$nodes,
				DepthLevel::FULL,
			);

			$result = [];
			foreach ($childNodes as $node)
			{
				$result[] = DepartmentBackwardAccessCode::extractIdFromCode($node->accessCode);
			}

			sort($result);
			return $result;
		}

		$dbRes = CIBlockSection::GetList(
			[ 'LEFT_MARGIN' => 'asc' ],
			[ 'ID' => $arSections ],
			false,
			[ 'ID', 'IBLOCK_ID', 'LEFT_MARGIN', 'RIGHT_MARGIN' ]
		);

		$arChildren = [];
		while ($arSection = $dbRes->Fetch())
		{
			if (
				$arSection['RIGHT_MARGIN'] - $arSection['LEFT_MARGIN'] > 1
				&& !in_array((int)$arSection['ID'], $arChildren, true)
			)
			{
				$dbChildren = CIBlockSection::GetList(
					[ 'id' => 'asc' ],
					[
						'IBLOCK_ID' => $arSection['IBLOCK_ID'],
						'ACTIVE' => 'Y',
						'>LEFT_BORDER' => $arSection['LEFT_MARGIN'],
						'<RIGHT_BORDER'=>$arSection['RIGHT_MARGIN'],
					],
					false,
					[ 'ID' ]
				);

				while ($arChild = $dbChildren->Fetch())
				{
					$arChildren[] = (int)$arChild['ID'];
				}
			}
		}

		return array_unique(array_merge($arSections, $arChildren));
	}

	public static function GetIBlockTopSection($SECTION_ID)
	{
		if (is_array($SECTION_ID)) $SECTION_ID = $SECTION_ID[0];
		$dbRes = CIBlockSection::GetNavChain(0, $SECTION_ID);

		$arSection = $dbRes->Fetch(); // hack to check "virtual" root insted of a real one
		$arSection = $dbRes->Fetch();
		if ($arSection)
			return $arSection['ID'];
		else
			return $SECTION_ID;
	}

	/**
	 * @deprecated Use \Bitrix\HumanResources\Contract\Repository\NodeRepository::findAllByAccessCodes instead
	 * @param $arDepartments
	 *
	 * @return array|false
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function GetDepartmentsData($arDepartments)
	{
		global $INTR_DEPARTMENTS_CACHE, $INTR_DEPARTMENTS_CACHE_VALUE;

		$arDep = array();

		if (!is_array($arDepartments))
			return false;

		if (!is_array($INTR_DEPARTMENTS_CACHE))
			$INTR_DEPARTMENTS_CACHE = array();
		if (!is_array($INTR_DEPARTMENTS_CACHE_VALUE))
			$INTR_DEPARTMENTS_CACHE_VALUE = array();

		$arNewDep = array_diff($arDepartments, $INTR_DEPARTMENTS_CACHE);

		if (count($arNewDep) > 0)
		{
			if (
				Loader::includeModule('humanresources')
				&& Storage::instance()->isIntranetUtilsDisabled())
			{
				$departments = [];
				array_walk(
					$arDepartments, function($department) use (&$departments) {
						$departments[] = DepartmentBackwardAccessCode::makeById((int)$department);
					}
				);
				$nodes = \Bitrix\HumanResources\Service\Container::getNodeRepository()
					->findAllByAccessCodes($departments);

				foreach ($nodes as $node)
				{
					$sectionId = DepartmentBackwardAccessCode::extractIdFromCode($node->accessCode);
					$INTR_DEPARTMENTS_CACHE[] = $sectionId;
					$INTR_DEPARTMENTS_CACHE_VALUE[$sectionId] = $node->name;
				}
			}
			else
			{
				$dbRes = CIBlockSection::GetList(array('SORT' => 'ASC'), array('ID' => $arNewDep));

				while ($arSect = $dbRes->Fetch())
				{
					$arParams['IBLOCK_ID'][] = $arSect['IBLOCK_ID'];
					$INTR_DEPARTMENTS_CACHE[] = $arSect['ID'];
					$INTR_DEPARTMENTS_CACHE_VALUE[$arSect['ID']] = $arSect['NAME'];
				}
			}
		}

		foreach ($arDepartments as $key => $sect)
		{
			$arDep[$sect] = $INTR_DEPARTMENTS_CACHE_VALUE[$sect];
		}

		return $arDep;
	}

	public static function IsUserAbsent($USER_ID, $CALENDAR_IBLOCK_ID = null)
	{
		global $CACHE_ABSENCE, $CACHE_MANAGER;
		if (null === $CACHE_ABSENCE)
		{
			$cache_ttl = (24-date('G')) * 3600;
			$cache_dir = '/'.SITE_ID.'/intranet/absence';

			$obCache = new CPHPCache();
			if ($obCache->InitCache($cache_ttl, 'intranet_absence', $cache_dir))
			{
				$arAbsence = $obCache->GetVars();
			}
			else
			{
				if (null == $CALENDAR_IBLOCK_ID)
					$CALENDAR_IBLOCK_ID = COption::GetOptionInt('intranet', 'iblock_calendar', null);

				$dt = ConvertTimeStamp(false, 'SHORT');
				$arAbsence = CIntranetUtils::GetAbsenceData(
					array(
						'CALENDAR_IBLOCK_ID' => $CALENDAR_IBLOCK_ID,
						'DATE_START' => $dt,
						'DATE_FINISH' => $dt,
						'PER_USER' => true,
						'SELECT' => array('DATE_ACTIVE_FROM', 'DATE_ACTIVE_TO'),
						'CHECK_PERMISSIONS' => 'N'
					)
				);

				$obCache->StartDataCache();
				$CACHE_MANAGER->StartTagCache($cache_dir);

				$CACHE_MANAGER->registerTag('calendar_user_' . $USER_ID);
				$CACHE_MANAGER->RegisterTag('iblock_id_' . COption::GetOptionInt('intranet', 'iblock_absence'));

				if($CALENDAR_IBLOCK_ID > 0)
				{
					$CACHE_MANAGER->RegisterTag('iblock_id_' . $CALENDAR_IBLOCK_ID);
				}

				$CACHE_MANAGER->EndTagCache();
				$obCache->EndDataCache($arAbsence);
			}

			$CACHE_ABSENCE = is_array($arAbsence) ? $arAbsence : array();
		}
		else
		{
			$arAbsence = $CACHE_ABSENCE;
		}

		if (is_array($arAbsence[$USER_ID] ?? null))
		{
			$ts = time() + \CTimeZone::getOffset();
			foreach($arAbsence[$USER_ID] as $arEntry)
			{
				$ts_start = MakeTimeStamp($arEntry['DATE_FROM'], FORMAT_DATETIME);
				if ($ts_start < $ts)
				{
					$ts_finish = MakeTimeStamp($arEntry['DATE_TO'], FORMAT_DATETIME);

					if ($ts_finish > $ts)
						return true;

					if (($ts_start+date('Z')) % 86400 == 0 && $ts_start == $ts_finish)
						return true;
				}
			}
		}

		return false;
	}

	public static function IsUserHonoured($USER_ID)
	{
		global $CACHE_HONOUR, $CACHE_MANAGER;

		if (!is_array($CACHE_HONOUR))
		{
			$cache_ttl = (24-date('G')) * 3600;
			$cache_dir = '/'.SITE_ID.'/intranet/honour';

			$obCache = new CPHPCache();
			if ($obCache->InitCache($cache_ttl, 'intranet_honour', $cache_dir))
			{
				$CACHE_HONOUR = $obCache->GetVars();
			}
			else
			{
				$CACHE_HONOUR = array();
				$blockId = intval(COption::GetOptionInt('intranet', 'iblock_honour'));
				$arFilter = array(
					"IBLOCK_ID" => $blockId,
					"ACTIVE_DATE" => 'Y',
				);

				if ($arFilter['IBLOCK_ID'] <= 0)
				{
					return false;
				}

				$dbRes = CIBlockElement::GetList(array('ID' => 'ASC'), $arFilter, array('ID', 'IBLOCK_ID', 'PROPERTY_USER'));
				while ($arRes = $dbRes->Fetch())
				{
					$CACHE_HONOUR[] = $arRes;
				}

				$obCache->StartDataCache();
				$CACHE_MANAGER->StartTagCache($cache_dir);
				$CACHE_MANAGER->RegisterTag('iblock_id_' . $blockId);
				$CACHE_MANAGER->EndTagCache();
				$obCache->EndDataCache($CACHE_HONOUR);
			}
		}

		foreach ($CACHE_HONOUR as $arRes)
		{
			if (isset($arRes['PROPERTY_USER_VALUE']) && $arRes['PROPERTY_USER_VALUE'] == $USER_ID)
				return true;
		}

		return false;
	}

	public static function IsToday($date)
	{
		if ($date && ($arDate = ParseDateTime($date, CSite::GetDateFormat('SHORT'))))
		{
			if (isset($arDate["M"]))
			{
				if (is_numeric($arDate["M"]))
				{
					$arDate["MM"] = intval($arDate["M"]);
				}
				else
				{
					$arDate["MM"] = GetNumMonth($arDate["M"], true);
					if (!$arDate["MM"])
						$arDate["MM"] = intval(date('m', strtotime($arDate["M"])));
				}
			}
			elseif (isset($arDate["MMMM"]))
			{
				if (is_numeric($arDate["MMMM"]))
				{
					$arDate["MM"] = intval($arDate["MMMM"]);
				}
				else
				{
					$arDate["MM"] = GetNumMonth($arDate["MMMM"]);
					if (!$arDate["MM"])
						$arDate["MM"] = intval(date('m', strtotime($arDate["MMMM"])));
				}
			}
			return (intval($arDate['MM']) == date('n')) && (intval($arDate['DD']) == date('j'));
		}
		else
		{
			return false;
		}
	}

	public static function IsDateTime($ts)
	{
		return (($ts + date('Z', $ts)) % 86400 != 0);
	}

	public static function IsOnline($last_date, $interval = 120)
	{
		$ts = $last_date ? MakeTimeStamp($last_date, 'YYYY-MM-DD HH:MI:SS') : 0;
		if ($ts)
			return time() - $ts < $interval;
		else
			return false;
	}

	public static function InitImage($imageID, $imageWidth, $imageHeight = 0, $type = BX_RESIZE_IMAGE_PROPORTIONAL)
	{
		$imageFile = false;
		$imageImg = "";

		if(($imageWidth = intval($imageWidth)) <= 0) $imageWidth = 100;
		if(($imageHeight = intval($imageHeight)) <= 0) $imageHeight = $imageWidth;

		$imageID = intval($imageID);

		$arFileTmp = [];
		if($imageID > 0)
		{
			$imageFile = CFile::GetFileArray($imageID);
			if ($imageFile !== false)
			{
				$arFileTmp = CFile::ResizeImageGet(
					$imageFile,
					array("width" => $imageWidth, "height" => $imageHeight),
					$type,
					false
				);
				$imageImg = CFile::ShowImage($arFileTmp["src"], $imageWidth, $imageHeight, "border=0", "");
			}
		}

		return array("FILE" => $imageFile, "CACHE" => $arFileTmp, "IMG" => $imageImg);
	}

	public static function __absence_sort($a, $b)
	{
		if ($a['DATE_ACTIVE_FROM_TS'] == $b['DATE_ACTIVE_FROM_TS'])
			return 0;

		$check1 = $check2 = 0;

		if (date('Y-m-d', $a['DATE_ACTIVE_FROM_TS']) == date('Y-m-d', $a['DATE_ACTIVE_TO_TS']))
		{
			if (0!=($a['DATE_ACTIVE_FROM_TS']+date('Z'))%86400)
				$check1++;
		}
		if (date('Y-m-d', $b['DATE_ACTIVE_FROM_TS']) == date('Y-m-d', $b['DATE_ACTIVE_TO_TS']))
		{
			if (0!=($b['DATE_ACTIVE_FROM_TS']+date('Z'))%86400)
				$check2++;
		}

		if ($check1 != $check2)
			return ($check1 < $check2) ? 1 : -1;
		elseif ($check1 > 0)
			return ($a['DATE_ACTIVE_FROM_TS'] > $b['DATE_ACTIVE_FROM_TS']) ? 1 : -1;
		else
			return ($a['DATE_ACTIVE_FROM_TS'] < $b['DATE_ACTIVE_FROM_TS']) ? 1 : -1;


		// if ($a['DATE_TO'] == $b['DATE_TO'])
			// return 0;
		// else
			// return (MakeTimeStamp($a['DATE_TO']) > MakeTimeStamp($b['DATE_TO'])) ? 1 : -1;
	}

	/*
	$arParams = array(
		'CALENDAR_IBLOCK_ID' => ID of calendar iblock. Def. - false, no calendar entries will be selected
		'ABSENCE_IBLOCK_ID' => ID of absence iblock. Def. - ID from intranet module options
		'DATE_START' => starting datetime in current format. Def. - current month start
		'DATE_FINISH' => endind datetime in current format. Def. - current month finish
		'USERS' => array of user IDs to get; false means no users filter. Def. - all users (false)
		'PER_USER' => {true|false} - whether to return data as array(USER_ID=>array(USER_ENTRIES)) or simple list. Def. - true
	),
	$MODE may be one of the following: BX_INTRANET_ABSENCE_ALL, BX_INTRANET_ABSENCE_PERSONAL, BX_INTRANET_ABSENCE_HR (bit-masks)
	*/
	public static function GetAbsenceData($arParams = array(), $MODE = BX_INTRANET_ABSENCE_ALL)
	{
		global $DB;

		$arDefaultParams = [
			'CALENDAR_IBLOCK_ID' => false,
			'ABSENCE_IBLOCK_ID' => COption::GetOptionInt('intranet', 'iblock_absence'),
			'DATE_START' => date($DB->DateFormatToPHP(CSite::GetDateFormat('FULL')), strtotime(date('Y-m-01'))),
			'DATE_FINISH' => date($DB->DateFormatToPHP(CSite::GetDateFormat('FULL')), strtotime('+1 month', strtotime(date('Y-m-01')))),
			'USERS' => false,
			'PER_USER' => true,
			'SELECT' => ['ID', 'IBLOCK_ID', 'DATE_ACTIVE_FROM', 'DATE_ACTIVE_TO', 'NAME', 'PREVIEW_TEXT', 'DETAIL_TEXT', 'PROPERTY_USER', 'PROPERTY_FINISH_STATE', 'PROPERTY_STATE', 'PROPERTY_ABSENCE_TYPE'],
			'CHECK_PERMISSIONS' => 'Y',
		];

		foreach ($arDefaultParams as $key => $value)
		{
			if (!isset($arParams[$key]))
				$arParams[$key] = $value;
		}

		$arParams['SELECT'] = array_merge(
			$arParams['SELECT'],
			array_diff(
				[
					'DATE_ACTIVE_FROM',
					'DATE_ACTIVE_TO',
					'PROPERTY_USER',
					'CREATED_BY',
					'MODIFIED_BY',
				],
				$arParams['SELECT'],
			)
		);

		$calendar2 = COption::GetOptionString("intranet", "calendar_2", "N") == "Y";
		$bLoadCalendar = ($arParams['CALENDAR_IBLOCK_ID'] > 0 || $calendar2) && (($MODE & BX_INTRANET_ABSENCE_PERSONAL) > 0);
		$bLoadAbsence = $arParams['ABSENCE_IBLOCK_ID'] > 0;

		$arResult = array();
		$arEntries = array();

		$format = $DB->DateFormatToPHP(CLang::GetDateFormat("FULL"));

		if ($bLoadCalendar)
		{
			$arMethodParams = array(
				'iblockId' => $arParams['CALENDAR_IBLOCK_ID'],
				'arUserIds' => $arParams['USERS'],
				'bList' => true,
				'checkPermissions' => $arParams['CHECK_PERMISSIONS'] !== 'N'
			);

			if ($arParams['DATE_START'])
				$arMethodParams['fromLimit'] = date($format, MakeTimeStamp($arParams['DATE_START'], FORMAT_DATE));
			if ($arParams['DATE_FINISH'])
				$arMethodParams['toLimit'] = date($format, MakeTimeStamp($arParams['DATE_FINISH'], FORMAT_DATE) + 86399);

			if ($calendar2 && CModule::IncludeModule('calendar'))
				$arCalendarEntries = CCalendar::GetAbsentEvents($arMethodParams);
			else
				$arCalendarEntries = CEventCalendar::GetAbsentEvents($arMethodParams);

			if (is_array($arCalendarEntries))
			{
				foreach ($arCalendarEntries as $key => $arEntry)
				{
					$arCalendarEntries[$key]['ENTRY_TYPE'] = BX_INTRANET_ABSENCE_PERSONAL;
				}
				$arEntries = array_merge($arEntries, $arCalendarEntries);
			}

			if ($arParams['PER_USER'])
			{
				foreach ($arEntries as $key => $arEntry)
				{
					if (!isset($arResult[$arEntry['USER_ID']]))
						$arResult[$arEntry['USER_ID']] = array();

					$arResult[$arEntry['USER_ID']][] = $arEntry;
				}
			}
			else
			{
				$arResult = $arEntries;
			}
		}

		if ($bLoadAbsence)
		{
			if ($arParams['USERS'] === false || (is_array($arParams['USERS']) && count($arParams['USERS']) > 0))
			{
				$arFilter = array(
					'IBLOCK_ID' => $arParams['ABSENCE_IBLOCK_ID'],
					'ACTIVE' => 'Y',
					//'PROPERTY_USER_ACTIVE' => 'Y',
				);

				if ($arParams['DATE_START'])
					$arFilter['>=DATE_ACTIVE_TO'] = date($format, MakeTimeStamp($arParams['DATE_START'], FORMAT_DATE));
				if ($arParams['DATE_FINISH'])
					$arFilter['<DATE_ACTIVE_FROM'] = date($format, MakeTimeStamp($arParams['DATE_FINISH'], FORMAT_DATE) + 86399);

				if (is_array($arParams['USERS']))
					$arFilter['=PROPERTY_USER'] = $arParams['USERS'];

				$dbRes = CIBlockElement::GetList(
					array('DATE_ACTIVE_FROM' => 'ASC', 'DATE_ACTIVE_TO' => 'ASC'),
					$arFilter,
					false,
					false,
					$arParams['SELECT']
				);

				while ($arRes = $dbRes->Fetch())
				{
					$arRes['USER_ID'] = $arRes['PROPERTY_USER_VALUE'];
					$arRes['DATE_FROM'] = $arRes['DATE_ACTIVE_FROM'];
					$arRes['DATE_TO'] = $arRes['DATE_ACTIVE_TO'];
					$arRes['ENTRY_TYPE'] = BX_INTRANET_ABSENCE_HR;

					if ($arParams['PER_USER'])
					{
						if (!isset($arResult[$arRes['USER_ID']]))
							$arResult[$arRes['USER_ID']] = array();

						$arResult[$arRes['USER_ID']][] = $arRes;
					}
					else
					{
						$arResult[] = $arRes;
					}
				}
			}
		}

		return $arResult;
	}

	/* STATUS: deprecated */
	public static function FormatName($NAME_TEMPLATE, $arUser, $bHTMLSpec = true)
	{
		return CUser::FormatName($NAME_TEMPLATE, $arUser, true, $bHTMLSpec);
	}

	/* STATUS: deprecated */
	public static function GetDefaultNameTemplates()
	{
		return CComponentUtil::GetDefaultNameTemplates();
	}

	public static function getOutlookTimeZone($userId = null)
	{
		return -(intval(date('Z')) + \CTimeZone::GetOffset($userId, true))/60;
	}

	public static function makeGUID($data)
	{
		if (mb_strlen($data) !== 32) return false;
		else return
			'{'.
			mb_substr($data, 0, 8).'-'.mb_substr($data, 8, 4).'-'.mb_substr($data, 12, 4).'-'.mb_substr($data, 16, 4).'-'.mb_substr($data, 20).
			'}';
	}

	public static function checkGUID($data)
	{
		$data = str_replace(array('{', '-', '}'), '', $data);
		if (mb_strlen($data) !== 32 || preg_match('/[^a-z0-9]/i', $data)) return false;
		else return $data;
	}

	/*
	$arSectionParams = array(
		'ID' => 'Section ID',
		'XML_ID' => 'Section external ID' [optional], for calendars
		'CODE' => 'Section external ID' [optional], for tasks
		'IBLOCK_ID' => 'Information block id' [optional],
		'NAME' => 'Calendar name' [optional],
		'PREFIX' => 'Calendar prefix',
		'LINK_URL' => 'Calendar URL' (/company/personal/user/666/calendar/),
	)

	if any of parameters 'XML_ID'|'CODE', 'IBLOCK_ID', 'NAME' are absent, they are taken from DB
	XML_ID|CODE must be 32-digit hexadimal number. if none or other, it would be (re-)generated and (re-)set
	*/
	public static function GetStsSyncURL($arSectionParams, $type = 'calendar', $employees = false)
	{
		global $USER;

		if (!Loader::includeModule('webservice'))
		{
			return sprintf("alert('%s')", CUtil::jsEscape(getMessage('INTR_SYNC_OUTLOOK_NOWEBSERVICE')));
		}

		if (!is_array($arSectionParams))
			$arSectionParams = array('ID' => intval($arSectionParams));

		//if (!$arSectionParams['ID'])
		//	return false;

		$arAllowedTypes = array('calendar', 'tasks', 'contacts');

		if (!in_array($type, $arAllowedTypes))
			$type = 'calendar';

		if ($type == 'calendar')
		{
			$calendar2 = COption::GetOptionString("intranet", "calendar_2", "N") == "Y" && CModule::IncludeModule("calendar");
			$fld_EXTERNAL_ID = 'XML_ID';

			if ($calendar2) // Module 'Calendar'
			{
				// $arSectionParams = array(
					// 'ID' => int
					// 'XML_ID' => string
					// 'NAME' => string
					// 'PREFIX' => string
					// 'LINK_URL' => string
					// 'TYPE' => string
				// )

				if (mb_strlen($arSectionParams['XML_ID']) !== 32)
				{
					$arSectionParams[$fld_EXTERNAL_ID] = md5($arSectionParams['TYPE'].'_'.$arSectionParams['ID'].'_'.RandString(8));
					// Set XML_ID
					CCalendar::SaveSection(array('arFields' => Array('ID' => $arSectionParams['ID'],'XML_ID' => $arSectionParams[$fld_EXTERNAL_ID]), 'bAffectToDav' => false, 'bCheckPermissions' => false));
				}
			}
			else // Old version calendar on iblocks
			{
				if (!$arSectionParams['IBLOCK_ID'] || !$arSectionParams['NAME'] || !$arSectionParams[$fld_EXTERNAL_ID])
				{
					$dbRes = CIBlockSection::GetByID($arSectionParams['ID']);
					$arSection = $dbRes->Fetch();
					if ($arSection)
					{
						$arSectionParams['IBLOCK_ID'] = $arSection['IBLOCK_ID'];
						$arSectionParams['NAME'] = $arSection['NAME'];
						$arSectionParams[$fld_EXTERNAL_ID] = $arSection[$fld_EXTERNAL_ID];
					}
					else
					{
						return false;
					}
				}

				if (mb_strlen($arSectionParams[$fld_EXTERNAL_ID]) !== 32)
				{
					$arSectionParams[$fld_EXTERNAL_ID] = md5($arSectionParams['IBLOCK_ID'].'_'.$arSectionParams['ID'].'_'.RandString(8));

					$obSect = new CIBlockSection();
					if (!$obSect->Update($arSectionParams['ID'], array($fld_EXTERNAL_ID => $arSectionParams[$fld_EXTERNAL_ID]), false, false))
						return false;
				}
			}

			if (!$arSectionParams['PREFIX'])
			{
				$rsSites = CSite::GetByID(SITE_ID);
				$arSite = $rsSites->Fetch();
				if ($arSite["NAME"] <> '')
					$arSectionParams['PREFIX'] = $arSite["NAME"];
				else
					$arSectionParams['PREFIX'] = COption::GetOptionString('main', 'site_name', GetMessage('INTR_OUTLOOK_PREFIX_CONTACTS'));
			}

			$GUID = CIntranetUtils::makeGUID($arSectionParams[$fld_EXTERNAL_ID]);
		}
		elseif($type == 'contacts')
		{
			if (!$arSectionParams['LINK_URL'])
			{
				if (CModule::IncludeModule('extranet') && CExtranet::IsExtranetSite())
					$arSectionParams['LINK_URL'] = SITE_DIR.'contacts/';
				else
					$arSectionParams['LINK_URL'] = SITE_DIR.'company/';
			}

			if (!isset($arSectionParams['NAME']))
			{
				if (CModule::IncludeModule('extranet') && CExtranet::IsExtranetSite() && !$employees)
					$arSectionParams['NAME'] = GetMessage('INTR_OUTLOOK_TITLE_CONTACTS_EXTRANET');
				else
					$arSectionParams['NAME'] = GetMessage('INTR_OUTLOOK_TITLE_CONTACTS');
			}
			if (!isset($arSectionParams['PREFIX']))
			{
				$rsSites = CSite::GetByID(SITE_ID);
				$arSite = $rsSites->Fetch();

				if ($arSite["NAME"] <> '')
					$arSectionParams['PREFIX'] = $arSite["NAME"];
				else
					$arSectionParams['PREFIX'] = COption::GetOptionString('main', 'site_name', GetMessage('INTR_OUTLOOK_PREFIX_CONTACTS'));
			}


			$SERVER_NAME = $_SERVER['SERVER_NAME'];
			$GUID_DATA = $SERVER_NAME.'|'.$type;
			if (CModule::IncludeModule('extranet') && CExtranet::IsExtranetSite())
			{
				$GUID_DATA .= "|extranet";
				if ($employees)
					$GUID_DATA .= "|employees";
			}

			$GUID = CIntranetUtils::makeGUID(md5($GUID_DATA));
		}
		elseif($type == 'tasks')
		{
			if (!$arSectionParams['LINK_URL'])
			{
				if (CModule::IncludeModule('extranet') && CExtranet::IsExtranetSite())
					$arSectionParams['LINK_URL'] = SITE_DIR.'contacts/personal/user/'.$USER->GetID().'/tasks/';
				else
					$arSectionParams['LINK_URL'] = SITE_DIR.'company/personal/user/'.$USER->GetID().'/tasks/';
			}

			$arSectionParams['NAME'] = $arSectionParams['NAME'] ?? GetMessage('INTR_OUTLOOK_TITLE_TASKS');


			if (!($arSectionParams['PREFIX'] ?? null))
			{
				$rsSites = CSite::GetByID(SITE_ID);
				$arSite = $rsSites->Fetch();
				if ($arSite["NAME"] <> '')
					$arSectionParams['PREFIX'] = $arSite["NAME"];
				else
					$arSectionParams['PREFIX'] = COption::GetOptionString('main', 'site_name', GetMessage('INTR_OUTLOOK_PREFIX_CONTACTS'));
			}

			$SERVER_NAME = $_SERVER['SERVER_NAME'];
			$GUID_DATA = $SERVER_NAME.'|'.$type;

			if (CModule::IncludeModule('extranet') && CExtranet::IsExtranetSite())
				$GUID_DATA .= "|extranet";
			$GUID = CIntranetUtils::makeGUID(md5($GUID_DATA));
		}
		else
		{
			return '';
		}

		if (mb_substr($arSectionParams['LINK_URL'], -9) == 'index.php')
			$arSectionParams['LINK_URL'] = mb_substr($arSectionParams['LINK_URL'], 0, -9);

		if (mb_substr($arSectionParams['LINK_URL'], -4) != '.php' && mb_substr($arSectionParams['LINK_URL'], -1) != '/')
			$arSectionParams['LINK_URL'] .= '/';

		// another dirty hack to avoid some M$ stssync protocol restrictions
		if (mb_substr($arSectionParams['LINK_URL'], -1) != '/')
			$arSectionParams['LINK_URL'] .= '/';

		$type_script = $type;
		if (CModule::IncludeModule('extranet') && CExtranet::IsExtranetSite())
		{
			$type_script .= "_extranet";
			if ($employees)
				$type_script .= "_emp";
		}

		return \Bitrix\WebService\StsSync::getUrl($type, $type_script, $arSectionParams['LINK_URL'], $arSectionParams['PREFIX'], $arSectionParams['NAME'], $GUID);
	}

	public static function UpdateOWSVersion($IBLOCK_ID, $ID, $value = null)
	{
		if (!defined('INTR_WS_OUTLOOK_UPDATE'))
		{
			if (null === $value)
			{
				$dbRes = CIBlockElement::GetProperty($IBLOCK_ID, $ID, 'sort', 'asc', array('CODE' => 'VERSION'));
				$arProperty = $dbRes->Fetch();
				if ($arProperty)
				{
					$value = intval($arProperty['VALUE']);
					if (!$value) $value = 1;
					$value++;
				}
			}

			if (null !== $value)
			{
				CIBlockElement::SetPropertyValues($ID, $IBLOCK_ID, $value, 'VERSION');
			}
		}
	}

	protected static function __dept_field_replace($str)
	{
		return preg_replace(
			'/<option([^>]*)>'.GetMessage('MAIN_NO').'<\/option>/iu',
			'<option\\1>'.GetMessage('MAIN_ALL').'</option>',
			$str
		);
	}

	public static function ShowDepartmentFilter($arUserField, $bVarsFromForm, $bReturn = false, $ob_callback = array('CIntranetUtils', '__dept_field_replace'))
	{
		ob_start($ob_callback);

		$arUserField['SETTINGS']['ACTIVE_FILTER'] = 'Y';
		$arUserField['SETTINGS']["DEFAULT_VALUE"] = 0;

		$GLOBALS['APPLICATION']->IncludeComponent(
			'bitrix:system.field.edit',
			'iblock_section',
			array(
				"arUserField" => $arUserField,
				'bVarsFromForm' => $bVarsFromForm,
				'mode' => 'main.edit_simple'
			),
			null,
			array('HIDE_ICONS' => 'Y')
		);

		if ($bReturn)
		{
			$str = ob_get_contents();
			ob_end_flush();
			return $str;
		}

		ob_end_flush();
		return true;
	}

	public static function GetIBlockByID($ID)
	{
		if (!CModule::IncludeModule("iblock"))
			return false;

		$ID = intval($ID);

		$dbIBlock = CIBlock::GetByID($ID);
		$arIBlock = $dbIBlock->GetNext();
		if ($arIBlock)
		{
			$arIBlock["NAME_FORMATTED"] = $arIBlock["NAME"];
			return $arIBlock;
		}
		else
			return false;
	}

	public static function ShowIBlockByID($arEntityDesc, $strEntityURL, $arParams)
	{
		$url = str_replace("#SITE_DIR#", SITE_DIR, $arEntityDesc["LIST_PAGE_URL"]);
		if (mb_strpos($url, "/") === 0)
			$url = "/".ltrim($url, "/");

		$name = "<a href=\"".$url."\">".$arEntityDesc["NAME"]."</a>";
		return $name;
	}

	/**
	 * @deprecated Use \Bitrix\HumanResources\Contract\Repository\NodeRepository::getAllByStructureId
	 * @param int $section_id
	 * @param bool $bFlat
	 * @param bool $supportNew
	 *
	 * @return array
	 */
	public static function GetDeparmentsTree($section_id = 0, $bFlat = false, $supportNew = true): array
	{
		if (null == self::$SECTIONS_SETTINGS_WITHOUT_EMPLOYEE_CACHE)
		{
			self::_GetDeparmentsTreeWithoutEmployee($supportNew);
		}

		if (!$section_id)
		{
			if (!$bFlat)
			{
				return self::$SECTIONS_SETTINGS_WITHOUT_EMPLOYEE_CACHE['TREE'];
			} else
			{
				return array_keys(self::$SECTIONS_SETTINGS_WITHOUT_EMPLOYEE_CACHE['DATA']);
			}
		}

		$arSections = self::$SECTIONS_SETTINGS_WITHOUT_EMPLOYEE_CACHE['TREE'][$section_id] ?? null;

		if (is_array($arSections) && !empty($arSections))
		{
			if ($bFlat)
			{
				foreach ($arSections as $subsection_id)
				{
					$arSections = array_merge($arSections, self::GetDeparmentsTree($subsection_id, $bFlat));
				}
			}
			else
			{
				$arSections = array($section_id => $arSections);
				foreach ($arSections[$section_id] as $subsection_id)
				{
					$arSections += self::GetDeparmentsTree($subsection_id, $bFlat);
				}
			}
		}

		return is_array($arSections) ? $arSections : array();
	}

	/**
	 * @deprecated Use \Bitrix\HumanResources\Contract\Repository\NodeRepository::getChildOf instead
	 * @param $sectionId
	 * @param $depth
	 *
	 * @return mixed|void
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function getSubStructure($sectionId, $depth = false)
	{
		global $CACHE_MANAGER;

		static $structures;

		if (empty($structures[intval($sectionId)][intval($depth)]))
		{
			if (
				Loader::includeModule('humanresources')
				&& Storage::instance()->isIntranetUtilsDisabled())
			{
				$subStructure = StructureBackwardAdapter::getStructureWithoutEmployee($sectionId,
					$depth === false
					? null
					: (int) $depth
				);

				if (!empty($subStructure) && !empty($subStructure['DATA']))
				{
					if (!is_array($structures))
						$structures = array();
					if (!isset($structures[intval($sectionId)]) || !is_array($structures[intval($sectionId)]))
						$structures[intval($sectionId)] = array();
					$structures[intval($sectionId)][intval($depth)] = $subStructure;
				}

				return $structures[intval($sectionId)][intval($depth)];
			}

			$iblockId = COption::GetOptionInt('intranet', 'iblock_structure', false);
			if ($iblockId <= 0)
				return;

			$cacheDir = '/intranet/structure/branches';
			$cacheId = 'intranet|structure|'.$iblockId.'|branch|'.intval($sectionId).'|'.intval($depth);

			$obCache = new CPHPCache();

			if ($obCache->InitCache(30*86400, $cacheId, $cacheDir))
			{
				$subStructure = $obCache->GetVars();
			}
			else
			{
				$obCache->StartDataCache();

				$CACHE_MANAGER->StartTagCache($cacheDir);

				$CACHE_MANAGER->RegisterTag('iblock_id_'.$iblockId);
				$CACHE_MANAGER->RegisterTag('intranet_users');
				$CACHE_MANAGER->RegisterTag('intranet_department_structure');

				$subStructure = array(
					'TREE' => array(),
					'DATA' => array(),
				);

				$arFilter = array(
					'IBLOCK_ID' => $iblockId,
					'ACTIVE'    => 'Y'
				);

				if ($sectionId > 0)
				{
					if ($depth == 1)
					{
						$arFilter['SECTION_ID'] = $sectionId;
					}
					else
					{
						$dbSection = CIBlockSection::GetList(
							array('LEFT_MARGIN' => 'ASC'),
							array('IBLOCK_ID' => $iblockId, 'ID' => $sectionId, 'ACTIVE' => 'Y'),
							false,
							array('DEPTH_LEVEL', 'LEFT_MARGIN', 'RIGHT_MARGIN')
						);
						if (!empty($dbSection) && ($currentSection = $dbSection->fetch()))
						{
							$arFilter['>=LEFT_MARGIN']  = $currentSection['LEFT_MARGIN'];
							$arFilter['<=RIGHT_MARGIN'] = $currentSection['RIGHT_MARGIN'];
							$arFilter['>DEPTH_LEVEL']   = $currentSection['DEPTH_LEVEL'];
							if ($depth > 0)
								$arFilter['<=DEPTH_LEVEL'] = $currentSection['DEPTH_LEVEL'] + $depth;
						}
					}
				}
				else if ($depth > 0)
				{
					$arFilter['<=DEPTH_LEVEL'] = $depth;
				}

				$dbSections = CIBlockSection::GetList(
					array('LEFT_MARGIN' => 'ASC'),
					$arFilter,
					false,
					array('ID', 'NAME', 'IBLOCK_SECTION_ID', 'UF_HEAD')
				);
				if (!empty($dbSections))
				{
					while ($section = $dbSections->fetch())
					{
						if (empty($section['IBLOCK_SECTION_ID']))
							$section['IBLOCK_SECTION_ID'] = 0;

						if (!isset($subStructure['TREE'][$section['IBLOCK_SECTION_ID']]))
							$subStructure['TREE'][$section['IBLOCK_SECTION_ID']] = array();

						$subStructure['TREE'][$section['IBLOCK_SECTION_ID']][] = $section['ID'];
						$subStructure['DATA'][$section['ID']] = array(
							'ID'                => $section['ID'],
							'NAME'              => $section['NAME'],
							'IBLOCK_SECTION_ID' => $section['IBLOCK_SECTION_ID'],
							'UF_HEAD'           => $section['UF_HEAD']
						);
					}
				}

				$CACHE_MANAGER->EndTagCache();
				$obCache->EndDataCache($subStructure);
			}

			if (!is_array($structures))
				$structures = array();
			if (!isset($structures[intval($sectionId)]) || !is_array($structures[intval($sectionId)]))
				$structures[intval($sectionId)] = array();
			$structures[intval($sectionId)][intval($depth)] = $subStructure;
		}

		return $structures[intval($sectionId)][intval($depth)];
	}

	/**
	 * @deprecated Use \Bitrix\HumanResources\Contract\Repository\NodeRepository::getAllByStructureId instead
	 * @param bool $supportNew
	 *
	 * @return null
	 */
	public static function GetStructure(bool $supportNew = true)
	{
		if (null == self::$SECTIONS_SETTINGS_CACHE)
			self::_GetDeparmentsTree($supportNew);

		return self::$SECTIONS_SETTINGS_CACHE;
	}

	/**
	 * @deprecated Use \Bitrix\HumanResources\Contract\Repository\NodeRepository::getAllByStructureId instead
	 * @param bool $supportNew
	 *
	 * @return array|null
	 */
	public static function GetStructureWithoutEmployees(bool $supportNew = true): array|null
	{
		if (null == self::$SECTIONS_SETTINGS_WITHOUT_EMPLOYEE_CACHE)
		{
			self::_GetDeparmentsTreeWithoutEmployee($supportNew);
		}

		return self::$SECTIONS_SETTINGS_WITHOUT_EMPLOYEE_CACHE;
	}

	/**
	 * @deprecated Use \Bitrix\HumanResources\Contract\Service\NodeMemberService::getDefaultHeadRoleEmployees instead
	 * @param $section_id
	 *
	 * @return int|null
	 */
	public static function GetDepartmentManagerID($section_id): null|int
	{
		if (null == self::$SECTIONS_SETTINGS_WITHOUT_EMPLOYEE_CACHE)
		{
			self::_GetDeparmentsTreeWithoutEmployee();
		}

		return self::$SECTIONS_SETTINGS_WITHOUT_EMPLOYEE_CACHE['DATA'][$section_id]['UF_HEAD'];
	}

	/**
	 * @deprecated Use \Bitrix\HumanResources\Contract\Service\NodeMemberService::getDefaultHeadRoleEmployees instead
	 * @param $arDepartments
	 * @param $skipUserId
	 * @param $bRecursive
	 *
	 * @return array
	 */
	public static function GetDepartmentManager($arDepartments, $skipUserId=false, $bRecursive=false)
	{
		if(!is_array($arDepartments) || empty($arDepartments))
		{
			return array();
		}

		if (null == self::$SECTIONS_SETTINGS_WITHOUT_EMPLOYEE_CACHE)
		{
			self::_GetDeparmentsTreeWithoutEmployee();
		}

		$arManagers = array();
		$arManagerIDs = array();
		foreach ($arDepartments as $section_id)
		{
			$arSection = self::$SECTIONS_SETTINGS_WITHOUT_EMPLOYEE_CACHE['DATA'][$section_id];

			if ($arSection['UF_HEAD'] && $arSection['UF_HEAD'] != $skipUserId)
			{
				$arManagers[$arSection['UF_HEAD']] = null;
				$arManagerIDs[] = $arSection['UF_HEAD'];
			}
		}

		if(count($arManagerIDs) > 0)
		{
			$dbRes = CUser::GetList('ID', 'ASC', array('ID' => implode('|', array_unique($arManagerIDs))));
			while($arUser = $dbRes->GetNext())
			{
				$arManagers[$arUser['ID']] = $arUser;
			}
		}

		foreach ($arDepartments as $section_id)
		{
			$arSection = self::$SECTIONS_SETTINGS_WITHOUT_EMPLOYEE_CACHE['DATA'][$section_id];

			$bFound = $arSection['UF_HEAD']
				&& $arSection['UF_HEAD'] != $skipUserId
				&& array_key_exists($arSection['UF_HEAD'], $arManagers);

			if (!$bFound && $bRecursive && $arSection['IBLOCK_SECTION_ID'])
			{
				$ar = CIntranetUtils::GetDepartmentManager(array($arSection['IBLOCK_SECTION_ID']), $skipUserId, $bRecursive);
				$arManagers = $arManagers + $ar;
			}
		}

		return $arManagers;
	}

	/**
	 * @deprecated Use \Bitrix\HumanResources\Contract\Service\NodeMemberService::getPagedEmployees instead
	 * @param $section_id
	 * @param $amount
	 * @param $arAccessUsers
	 *
	 * @return int
	 */
	public static function GetEmployeesCountForSorting($section_id = 0, $amount = 0, $arAccessUsers = false)
	{
		if (null == self::$SECTIONS_SETTINGS_CACHE)
			self::_GetDeparmentsTree();

		if (is_array($arAccessUsers))
		{
			if (count($arAccessUsers) <= 0)
				return 0;
			if (in_array('*', $arAccessUsers))
				$arAccessUsers = false;
		}

		$cnt = 0;

		$arSection = self::$SECTIONS_SETTINGS_CACHE['DATA'][$section_id] ?? null;

		if ($arSection && is_array($arSection['EMPLOYEES']))
		{
			if (!is_array($arAccessUsers))
				$cnt = count($arSection['EMPLOYEES']);
			else
				$cnt += count(array_intersect($arSection['EMPLOYEES'], $arAccessUsers));
		}

		if (
			$arSection
			&& $arSection['UF_HEAD'] > 0
			&& !in_array($arSection['UF_HEAD'], $arSection['EMPLOYEES'])
			&& (
				!$arAccessUsers
				|| $arSection['UF_HEAD'] > 0 && is_array($arAccessUsers) && in_array($arSection['UF_HEAD'], $arAccessUsers)
			)
		)
		{
			$cnt++;
		}

		if (array_key_exists($section_id, self::$SECTIONS_SETTINGS_CACHE['TREE']) && self::$SECTIONS_SETTINGS_CACHE['TREE'][$section_id])
		{
			foreach (self::$SECTIONS_SETTINGS_CACHE['TREE'][$section_id] as $dpt)
				$cnt += self::GetEmployeesCountForSorting ($dpt, 0, $arAccessUsers);
		}

		return $amount > 0 ? intval($cnt/$amount)+($cnt%$amount>0?1:0) : $cnt;
	}

	/**
	 * @deprecated Use \Bitrix\HumanResources\Contract\Service\NodeMemberService::getPagedEmployees instead
	 * @param $page
	 * @param $amount
	 * @param $section_id
	 * @param $arAccessUsers
	 *
	 * @return array
	 */
	public static function GetEmployeesForSorting($page = 1, $amount = 50, $section_id = 0, $arAccessUsers = false)
	{
		if (null == self::$SECTIONS_SETTINGS_CACHE)
			self::_GetDeparmentsTree();

		if (is_array($arAccessUsers))
		{
			if (count($arAccessUsers) <= 0)
				return array();
			if (in_array('*', $arAccessUsers))
				$arAccessUsers = false;
		}

		$start = ($page-1) * $amount;
		$arUserIDs = array();

		self::_GetEmployeesForSorting($section_id, $amount, $start, $arUserIDs, $arAccessUsers);

		return $arUserIDs;
	}

	private static function _GetEmployeesForSorting($section_id, &$amount, &$start, &$arUserIDs, $arAccessUsers)
	{
		if (array_key_exists($section_id, self::$SECTIONS_SETTINGS_CACHE['DATA']) && self::$SECTIONS_SETTINGS_CACHE['DATA'][$section_id])
		{
			if (self::$SECTIONS_SETTINGS_CACHE['DATA'][$section_id]['UF_HEAD'])
			{
				if (!$arAccessUsers || in_array(self::$SECTIONS_SETTINGS_CACHE['DATA'][$section_id]['UF_HEAD'], $arAccessUsers))
				{
					if ($start > 0)
					{
						$start--;
					}
					else if ($amount > 0)
					{
						$arUserIDs[$section_id][] = self::$SECTIONS_SETTINGS_CACHE['DATA'][$section_id]['UF_HEAD'];
						$amount--;
					}
					else
					{
						return false;
					}
				}
			}

			if (self::$SECTIONS_SETTINGS_CACHE['DATA'][$section_id]['EMPLOYEES'])
			{
				foreach (self::$SECTIONS_SETTINGS_CACHE['DATA'][$section_id]['EMPLOYEES'] as $ID)
				{
					if ($ID == self::$SECTIONS_SETTINGS_CACHE['DATA'][$section_id]['UF_HEAD'])
						continue;

					if ($arAccessUsers && !in_array($ID, $arAccessUsers))
						continue;

					if ($start > 0)
					{
						$start--;
					}
					else if ($amount > 0)
					{
						$arUserIDs[$section_id][] = $ID;
						$amount--;
					}
					else
					{
						return false;
					}
				}
			}
		}

		if (self::$SECTIONS_SETTINGS_CACHE['TREE'][$section_id])
		{
			foreach (self::$SECTIONS_SETTINGS_CACHE['TREE'][$section_id] as $dpt)
			{
				if (!self::_GetEmployeesForSorting($dpt, $amount, $start, $arUserIDs, $arAccessUsers))
					return false;
			}
		}
		return true;
	}

	/**
	 * @deprecated Use Department to get the departmental structure and Employee to get the employees of the department.
	 */
	private static function _GetDeparmentsTree(bool $supportNew = true)
	{
		global $CACHE_MANAGER, $DB;

		if ($supportNew && Loader::includeModule('humanresources'))
		{
			$structure = StructureBackwardAdapter::getStructure();

			if (!empty($structure) && !empty($structure['DATA']))
			{
				self::$SECTIONS_SETTINGS_CACHE = $structure;

				return;
			}
		}

		self::$SECTIONS_SETTINGS_CACHE = array(
			'TREE' => array(),
			'DATA' => array(),
		);

		$ibDept = COption::GetOptionInt('intranet', 'iblock_structure', false);
		if ($ibDept <= 0)
			return;

		$cache_dir = '/intranet/structure';
		$cache_id = 'intranet|structure2|'.$ibDept;

		$obCache = new CPHPCache();

		if ($obCache->InitCache(30*86400, $cache_id, $cache_dir))
		{
			self::$SECTIONS_SETTINGS_CACHE = $obCache->GetVars();
		}
		else
		{
			$obCache->StartDataCache();

			$CACHE_MANAGER->StartTagCache($cache_dir);

			$CACHE_MANAGER->RegisterTag("iblock_id_".$ibDept);
			$CACHE_MANAGER->RegisterTag("intranet_users");
			$CACHE_MANAGER->RegisterTag("intranet_department_structure");

			$dbRes = CIBlockSection::GetList(
				array("LEFT_MARGIN"=>"ASC"),
				array('IBLOCK_ID' => $ibDept, 'ACTIVE' => 'Y'),
				false,
				array('ID', 'NAME', 'IBLOCK_SECTION_ID', 'UF_HEAD', 'SECTION_PAGE_URL', 'DEPTH_LEVEL',)
			);

			while ($arRes = $dbRes->Fetch())
			{
				if (empty($arRes['IBLOCK_SECTION_ID']))
					$arRes['IBLOCK_SECTION_ID'] = 0;

				if (empty(self::$SECTIONS_SETTINGS_CACHE['TREE'][$arRes['IBLOCK_SECTION_ID']]))
					self::$SECTIONS_SETTINGS_CACHE['TREE'][$arRes['IBLOCK_SECTION_ID']] = array();

				self::$SECTIONS_SETTINGS_CACHE['TREE'][$arRes['IBLOCK_SECTION_ID']][] = $arRes['ID'];
				self::$SECTIONS_SETTINGS_CACHE['DATA'][$arRes['ID']] = array(
					'ID' => $arRes['ID'],
					'NAME' => $arRes['NAME'],
					'IBLOCK_SECTION_ID' => $arRes['IBLOCK_SECTION_ID'],
					'UF_HEAD' => $arRes['UF_HEAD'],
					'SECTION_PAGE_URL' => $arRes['SECTION_PAGE_URL'],
					'DEPTH_LEVEL' => $arRes['DEPTH_LEVEL'],
					'EMPLOYEES' => array()
				);
			}

			$dbRes = $DB->query("
				SELECT BUF.VALUE_ID AS ID, BUF.VALUE_INT AS UF_DEPARTMENT
					FROM b_utm_user BUF
						LEFT JOIN b_user_field UF ON BUF.FIELD_ID = UF.ID
						LEFT JOIN b_user U ON BUF.VALUE_ID = U.ID
					WHERE ( U.ACTIVE = 'Y' )
						AND ( UF.FIELD_NAME = 'UF_DEPARTMENT' )
						AND ( BUF.VALUE_INT IS NOT NULL AND BUF.VALUE_INT <> 0 )
			");
			while ($arRes = $dbRes->fetch())
			{
				$dpt = $arRes['UF_DEPARTMENT'];
				if (isset(self::$SECTIONS_SETTINGS_CACHE['DATA'][$dpt]) && is_array(self::$SECTIONS_SETTINGS_CACHE['DATA'][$dpt]))
					self::$SECTIONS_SETTINGS_CACHE['DATA'][$dpt]['EMPLOYEES'][] = $arRes['ID'];
			}

			$CACHE_MANAGER->EndTagCache();
			$obCache->EndDataCache(self::$SECTIONS_SETTINGS_CACHE);
		}
	}

	/**
	 * @param bool $supportNew
	 *
	 * @return void
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\SystemException
	 */
	private static function _GetDeparmentsTreeWithoutEmployee(bool $supportNew = true): void
	{
		if ($supportNew
			&& Loader::includeModule('humanresources'))
		{
			$structure = StructureBackwardAdapter::getStructureWithoutEmployee();

			if (!empty($structure) && !empty($structure['DATA']))
			{
				self::$SECTIONS_SETTINGS_WITHOUT_EMPLOYEE_CACHE = $structure;

				return;
			}
		}

		self::$SECTIONS_SETTINGS_WITHOUT_EMPLOYEE_CACHE = array(
			'TREE' => array(),
			'DATA' => array(),
		);

		$ibDept = COption::GetOptionInt('intranet', 'iblock_structure', false);
		if ($ibDept <= 0) {
			return;
		}

		$cacheDir = '/intranet/structure';
		$cacheKey = 'intranet|structure3|' . $ibDept;
		$cache = Application::getInstance()->getCache();

		if ($cache->initCache(30*86400, $cacheKey, $cacheDir))
		{
			self::$SECTIONS_SETTINGS_WITHOUT_EMPLOYEE_CACHE = $cache->getVars();
		}
		else
		{
			$cache->startDataCache();

			$taggedCache = Application::getInstance()->getTaggedCache();
			$taggedCache->startTagCache($cacheDir);
			$taggedCache->registerTag('intranet_department_structure');
			$taggedCache->registerTag('iblock_id_' . $ibDept);
			$taggedCache->endTagCache();

			$dbRes = CIBlockSection::GetList(
				array("LEFT_MARGIN" => "ASC"),
				array('IBLOCK_ID' => $ibDept, 'ACTIVE' => 'Y'),
				false,
				array('ID', 'NAME', 'IBLOCK_SECTION_ID', 'UF_HEAD', 'SECTION_PAGE_URL', 'DEPTH_LEVEL')
			);

			while ($arRes = $dbRes->Fetch())
			{
				if (empty($arRes['IBLOCK_SECTION_ID']))
					$arRes['IBLOCK_SECTION_ID'] = 0;

				if (empty(self::$SECTIONS_SETTINGS_WITHOUT_EMPLOYEE_CACHE['TREE'][$arRes['IBLOCK_SECTION_ID']]))
					self::$SECTIONS_SETTINGS_WITHOUT_EMPLOYEE_CACHE['TREE'][$arRes['IBLOCK_SECTION_ID']] = array();

				self::$SECTIONS_SETTINGS_WITHOUT_EMPLOYEE_CACHE['TREE'][$arRes['IBLOCK_SECTION_ID']][] = $arRes['ID'];
				self::$SECTIONS_SETTINGS_WITHOUT_EMPLOYEE_CACHE['DATA'][$arRes['ID']] = array(
					'ID' => $arRes['ID'],
					'NAME' => $arRes['NAME'],
					'IBLOCK_SECTION_ID' => $arRes['IBLOCK_SECTION_ID'],
					'UF_HEAD' => $arRes['UF_HEAD'],
					'SECTION_PAGE_URL' => $arRes['SECTION_PAGE_URL'],
					'DEPTH_LEVEL' => $arRes['DEPTH_LEVEL'],
					'EMPLOYEES' => array()
				);
			}

			$cache->endDataCache(self::$SECTIONS_SETTINGS_WITHOUT_EMPLOYEE_CACHE);
		}
	}

	public static function getDepartmentColleagues($USER_ID = null, $bRecursive = false, $bSkipSelf = false, $onlyActive = 'Y', $arSelect = null)
	{
		global $USER;

		if (!$USER_ID)
			$USER_ID = $USER->GetID();

		$dbRes = CUser::GetList('ID', 'ASC', array('ID' => $USER_ID), array('SELECT' => array('UF_DEPARTMENT')));
		if (($arRes = $dbRes->Fetch()) && is_array($arRes['UF_DEPARTMENT']) && count($arRes['UF_DEPARTMENT']) > 0)
		{
			return CIntranetUtils::getDepartmentEmployees($arRes['UF_DEPARTMENT'], $bRecursive, $bSkipSelf, $onlyActive, $arSelect);
		}

		return new CDBResult();
	}

	/**
	 * @deprecated Use \Bitrix\HumanResources\Contract\Service\NodeMemberService::getPagedEmployees instead
	 * @param $arDepartments
	 * @param $bRecursive
	 * @param $bSkipSelf
	 * @param $onlyActive
	 * @param $arSelect
	 *
	 * @return CDBResult|false
	 */
	public static function getDepartmentEmployees($arDepartments, $bRecursive = false, $bSkipSelf = false, $onlyActive = 'Y', $arSelect = null)
	{
		if (empty($arDepartments))
		{
			return new CDBResult();
		}

		global $USER;

		return \Bitrix\Intranet\Util::getDepartmentEmployees(array(
			'DEPARTMENTS' => $arDepartments,
			'RECURSIVE' => ($bRecursive ? 'Y' : 'N'),
			'ACTIVE' => ($onlyActive === 'Y' ? 'Y' : 'N'),
			'SKIP' => ($bSkipSelf && is_object($USER) && $USER->IsAuthorized() ? $USER->GetID() : array()),
			'SELECT' => $arSelect
		));
	}

	/**
	 * @deprecated Use \Bitrix\HumanResources\Contract\Repository\NodeRepository::findAllByUserIdAndRoleId instead
	 * @param null $USER_ID
	 * @param bool $bRecursive
	 *
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function GetSubordinateDepartments($USER_ID = null, $bRecursive = false): array
	{
		global $USER;

		$arSections = array();

		if (!$USER_ID && $USER)
			$USER_ID = $USER->GetID();

		if ($USER_ID)
		{
			if (null == self::$SECTIONS_SETTINGS_WITHOUT_EMPLOYEE_CACHE)
			{
				self::_GetDeparmentsTreeWithoutEmployee();
			}

			foreach (self::$SECTIONS_SETTINGS_WITHOUT_EMPLOYEE_CACHE['DATA'] as $arSection)
			{
				if ($arSection['UF_HEAD'] == $USER_ID)
				{
					$arSections[] = $arSection['ID'];
				}
			}

			if ($bRecursive && !empty($arSections))
			{
				foreach ($arSections as $section_id)
				{
					$arSections  = array_merge($arSections, self::GetDeparmentsTree($section_id, true));
				}
			}
		}

		return $arSections;
	}

	/**
	 * @deprecated Use \Bitrix\HumanResources\Contract\Repository\NodeRepository::findAllByUserIdAndRoleId instead
	 * @param $USER_ID
	 *
	 * @return CIBlockResult
	 */
	public static function GetSubordinateDepartmentsList($USER_ID)
	{
		return CIBlockSection::GetList(
			array('SORT' => 'ASC', 'NAME' => 'ASC'),
			array('IBLOCK_ID' => COption::GetOptionInt('intranet', 'iblock_structure', 0), 'UF_HEAD' => $USER_ID, 'ACTIVE' => 'Y'),
			false,
			array('ID', 'NAME', 'UF_HEAD'));
	}

	public static function getSubordinateEmployees($USER_ID = null, $bRecursive = false, $onlyActive = 'Y', $arSelect = null)
	{
		$arDepartments = CIntranetUtils::GetSubordinateDepartments($USER_ID, $bRecursive);
		return CIntranetUtils::getDepartmentEmployees($arDepartments, false, true, $onlyActive, $arSelect);
	}

	public static function GetSubordinateDepartmentsOld($USER_ID = null, $bRecursive = false)
	{
		global $USER;

		$arDpts = array();

		if (!$USER_ID)
			$USER_ID = $USER->GetID();

		if ($USER_ID)
		{
			$dbRes = CIntranetUtils::GetSubordinateDepartmentsList($USER_ID);
			while ($arRes = $dbRes->Fetch())
			{
				$arDpts[] = $arRes['ID'];
			}

			if ($bRecursive && count($arDpts) > 0)
			{
				$arDpts = CIntranetUtils::GetIBlockSectionChildren($arDpts);
			}
		}

		return $arDpts;
	}


	public static function GetDepartmentManagerOld($arDepartments, $skipUserId=false, $bRecursive=false)
	{
		if(!is_array($arDepartments) || empty($arDepartments))
			return array();

		$arManagers = array();
		$dbSections = CIBlockSection::GetList(array('SORT' => 'ASC'), array('ID' =>$arDepartments, 'IBLOCK_ID' => COption::GetOptionInt('intranet', 'iblock_structure', 0)), false, array('ID', 'UF_HEAD', 'IBLOCK_SECTION_ID'));
		while($arSection = $dbSections->Fetch())
		{
			$bFound = false;
			if($arSection["UF_HEAD"] > 0)
			{
				$dbUser = CUser::GetByID($arSection["UF_HEAD"]);
				$arUser = $dbUser->GetNext();
				if ($arUser)
				{
					if($arUser["ID"] <> $skipUserId)
					{
						$arManagers[$arUser["ID"]] = $arUser;
						$bFound = true;
					}
				}
			}
			if(!$bFound && $bRecursive && $arSection['IBLOCK_SECTION_ID'] > 0)
			{
				$ar = CIntranetUtils::GetDepartmentManagerOld(array($arSection['IBLOCK_SECTION_ID']), $skipUserId, $bRecursive);
				$arManagers = $arManagers + $ar;
			}
		}
		return $arManagers;
	}

	/**
	 * @param $fields
	 * @param $params
	 * @param $siteId
	 * @return string|null
	 */
	public static function createAvatar($fields, $params = array(), $siteId = SITE_ID)
	{
		if(!isset($params['AVATAR_SIZE']))
		{
			$params['AVATAR_SIZE'] = 100;
		}

		if (CModule::IncludeModule('socialnetwork'))
		{
			return CSocNetLogTools::FormatEvent_CreateAvatar($fields, $params, '', $siteId);
		}

		static $cachedAvatars = array();
		if (intval($fields['PERSONAL_PHOTO']) > 0)
		{
			if (empty($cachedAvatars[$params['AVATAR_SIZE']][$fields['PERSONAL_PHOTO']]))
			{
				$imageFile = CFile::getFileArray($fields['PERSONAL_PHOTO']);
				if ($imageFile !== false)
				{
					$file = CFile::resizeImageGet($imageFile, array(
						"width"  => $params['AVATAR_SIZE'],
						"height" => $params['AVATAR_SIZE']
					), BX_RESIZE_IMAGE_EXACT, false);

					$avatarPath = $file['src'];
					$cachedAvatars[$params['AVATAR_SIZE']][$fields['PERSONAL_PHOTO']] = $avatarPath;
				}
			}
		}

		return empty($cachedAvatars[$params['AVATAR_SIZE']][$fields['PERSONAL_PHOTO']])? null : $cachedAvatars[$params['AVATAR_SIZE']][$fields['PERSONAL_PHOTO']];
	}

	/**
	 * duplicate CIMMail::IsExternalMailAvailable()
	 * for performance reasons
	 */
	public static function IsExternalMailAvailable()
	{
		global $USER;

		if (!is_object($USER) || !$USER->IsAuthorized())
			return false;

		if (!IsModuleInstalled('mail'))
			return false;

		if (COption::GetOptionString('intranet', 'allow_external_mail', 'Y') != 'Y')
			return false;

		if (CModule::IncludeModule('extranet') && CExtranet::IsExtranetSite())
			return false;

		if (isset(\Bitrix\Main\Application::getInstance()->getKernelSession()['aExtranetUser_'.$USER->GetID()][SITE_ID]))
		{
			if (!\Bitrix\Main\Application::getInstance()->getKernelSession()['aExtranetUser_'.$USER->GetID()][SITE_ID])
				return false;
		}
		else if (CModule::IncludeModule('extranet') && !CExtranet::IsIntranetUser())
			return false;

		if (!IsModuleInstalled('dav'))
			return true;

		if (COption::GetOptionString('dav', 'exchange_server', '') == '')
			return true;

		if (COption::GetOptionString('dav', 'agent_mail', 'N') != 'Y')
			return true;

		if (COption::GetOptionString('dav', 'exchange_use_login', 'Y') == 'Y')
			return false;

		if (!CUserOptions::GetOption('global', 'davex_mailbox'))
		{
			$arUser = CUser::GetList(
				'ID', 'ASC',
				array('ID_EQUAL_EXACT' => $USER->GetID()),
				array('SELECT' => array('UF_BXDAVEX_MAILBOX'), 'FIELDS' => array('ID'))
			)->Fetch();

			CUserOptions::SetOption('global', 'davex_mailbox', empty($arUser['UF_BXDAVEX_MAILBOX']) ? 'N' : 'Y');
		}

		if (CUserOptions::GetOption('global', 'davex_mailbox') == 'Y')
			return false;

		return true;
	}

	public static function checkMailDomain($service_id, $user_id, $cnt = 1)
	{
		$service_id = intval($service_id);
		$user_id    = intval($user_id);
		$cnt        = intval($cnt);

		if (!CModule::includeModule('mail'))
			return '';

		$arAdmin = CUser::getList(
			'', '',
			array('ID' => $user_id, 'GROUPS_ID' => 1, 'ACTIVE' => 'Y'),
			array('FIELDS' => array('ID', 'TIME_ZONE_OFFSET'))
		)->fetch();
		if (empty($arAdmin))
			return '';

		$service = \Bitrix\Mail\MailServicesTable::getList(array(
			'filter' => array('=ID' => $service_id)
		))->fetch();

		if (empty($service) || $service['ACTIVE'] != 'Y' || !in_array($service['SERVICE_TYPE'], array('domain', 'crdomain')))
			return '';

		if ($service['SERVICE_TYPE'] == 'domain')
		{
			$result = CMailDomain2::getDomainStatus($service['TOKEN'], $service['SERVER']);
			$stage  = empty($result['stage']) ? null : $result['stage'];
		}
		else // if ($service['SERVICE_TYPE'] == 'crdomain')
		{
			$crResponse = CControllerClient::executeEvent('OnMailControllerCheckMemberDomain', array('DOMAIN' => $service['SERVER']));
			$stage      = empty($crResponse['result']['stage']) ? null : $crResponse['result']['stage'];
		}

		if (!in_array($stage, array('none', 'owner-check', 'mx-check', 'added')))
		{
			return false;
		}
		else
		{
			if (in_array($stage, array('none', 'added')))
			{
				if ($stage == 'added')
				{
					if (CModule::includeModule('im'))
					{
						includeModuleLangFile(__FILE__);

						$siteUrl = sprintf(
							'http%s://%s%s',
							CMain::isHTTPS() ? 's' : '', $_SERVER['SERVER_NAME'],
							in_array($_SERVER['SERVER_PORT'], array(80, 443)) ? '' : ':' . $_SERVER['SERVER_PORT']
						);

						CIMNotify::add(array(
							'TO_USER_ID'     => $user_id,
							'FROM_USER_ID'   => 0,
							'NOTIFY_TYPE'    => IM_NOTIFY_SYSTEM,
							'NOTIFY_MODULE'  => 'intranet',
							'NOTIFY_MESSAGE' => str_replace(
								array('#DOMAIN#', '#SERVER#'),
								array(htmlspecialcharsbx($service['SERVER']), $siteUrl),
								getMessage('INTR_MAIL_DOMAINREADY_NOTICE')
							)
						));
					}

					$timeout = new DateTime(intval($arAdmin['TIME_ZONE_OFFSET']).' seconds +7 days');
					if ($timeout->format('N') > 5)
						$timeout->modify('next monday');

					CAgent::addAgent(
						'CIntranetUtils::notifyMailDomain("nomailbox", '.$service_id.', '.$user_id.');',
						'intranet', 'N',
						$timeout->getTimestamp()-intval($arAdmin['TIME_ZONE_OFFSET'])-time()
					);
				}

				return '';
			}
			else
			{
				if ($cnt > 100)
					return '';

				global $pPERIOD;

				$pPERIOD = $pPERIOD * $cnt;
				if ($pPERIOD > 3600 * 4)
					$pPERIOD = 3600 * 4;

				return 'CIntranetUtils::checkMailDomain('.$service_id.', '.$user_id.', '.++$cnt.');';
			}
		}
	}

	public static function notifyMailDomain($type, $sid, $user_id, $cnt = 0)
	{
		$user_id    = intval($user_id);
		$cnt        = intval($cnt);

		if (!CModule::includeModule('mail'))
			return '';

		$arAdmin = CUser::getList(
			'', '',
			array('ID' => $user_id, 'GROUPS_ID' => 1, 'ACTIVE' => 'Y'),
			array('FIELDS' => array('ID', 'EMAIL'))
		)->fetch();
		if (empty($arAdmin))
			return '';

		if ($cnt == 0)
			return 'CIntranetUtils::notifyMailDomain("'.$type.'", "'.$sid.'", '.$user_id.', '.++$cnt.');';

		includeModuleLangFile(__FILE__);

		if (isModuleInstalled('bitrix24'))
		{
			$learnmoreLink = getMessage('INTR_MAIL_DOMAIN_LEARNMOREB24_LINK');
			$supportLink   = getMessage('INTR_MAIL_DOMAIN_SUPPORTB24_LINK_MSGVER_1');
		}
		else
		{
			$learnmoreLink = getMessage('INTR_MAIL_DOMAIN_LEARNMORE_LINK');
			$supportLink   = getMessage('INTR_MAIL_DOMAIN_SUPPORT_LINK');
		}

		if (in_array($type, array('nocomplete', 'nomailbox')))
		{
			$sid = intval($sid);
			$service = \Bitrix\Mail\MailServicesTable::getList(array(
				'filter' => array('=ID' => $sid)
			))->fetch();

			if (empty($service) || $service['ACTIVE'] != 'Y' || !in_array($service['SERVICE_TYPE'], array('domain', 'crdomain')))
				return '';

			if ($service['SERVICE_TYPE'] == 'domain')
			{
				$result = CMailDomain2::getDomainStatus($service['TOKEN'], $service['SERVER'], $error);
				$stage  = empty($result['stage']) ? null : $result['stage'];
			}
			else
			{
				$crResponse = CControllerClient::executeEvent('OnMailControllerCheckMemberDomain', array('DOMAIN' => $service['SERVER']));
				$stage      = empty($crResponse['result']['stage']) ? null : $crResponse['result']['stage'];
			}

			if ($type == 'nocomplete')
			{
				if (in_array($stage, array('owner-check', 'mx-check')))
				{
					CEvent::send('INTRANET_MAILDOMAIN_NOCOMPLETE', array($service['SITE_ID']), array(
						'EMAIL_TO' => $arAdmin['EMAIL'],
						'LEARNMORE_LINK' => $learnmoreLink,
						'SUPPORT_LINK'   => $supportLink
					));

					if ($cnt == 1)
					{
						global $pPERIOD;

						$pPERIOD = 3600 * 24 * 4;

						return 'CIntranetUtils::notifyMailDomain("nocomplete", '.$sid.', '.$user_id.', '.++$cnt.');';
					}
				}
			}
			else // if ($type == 'nomailbox')
			{
				if ($stage == 'added')
				{
					$dbMailboxes = CMailbox::getList(
						array(),
						array(
							'ACTIVE'     => 'Y',
							'!USER_ID'   => 0,
							'SERVICE_ID' => $sid
						)
					);

					$adminMailbox  = false;
					$userMailboxes = false;
					while ($arMailbox = $dbMailboxes->fetch())
					{
						if ($arMailbox['USER_ID'] == $user_id)
						{
							$adminMailbox = true;
						}
						else
						{
							$userMailboxes = true;
							break;
						}
					}

					if (!$userMailboxes)
					{
						$eventType = $adminMailbox ? 'INTRANET_MAILDOMAIN_NOMAILBOX2' : 'INTRANET_MAILDOMAIN_NOMAILBOX';
						CEvent::send($eventType, array($service['SITE_ID']), array(
							'EMAIL_TO'       => $arAdmin['EMAIL'],
							'LEARNMORE_LINK' => $learnmoreLink,
							'SUPPORT_LINK'   => $supportLink
						));

						if ($cnt == 1)
						{
							global $pPERIOD;

							$pPERIOD = 3600 * 24 * 21;

							return 'CIntranetUtils::notifyMailDomain("nomailbox", '.$sid.', '.$user_id.', '.++$cnt.');';
						}
					}
				}
			}
		}
		else if ($type == 'noreg')
		{
			$dbServices = \Bitrix\Mail\MailServicesTable::getList(array(
				'filter' => array('ACTIVE' => 'Y', 'SERVICE_TYPE' => 'crdomain')
			));

			while ($service = $dbServices->fetch())
			{
				if ($service['FLAGS'] & CMail::F_DOMAIN_REG)
					return '';
			}

			$r = CEvent::send('INTRANET_MAILDOMAIN_NOREG', array($sid), array(
				'EMAIL_TO'       => $arAdmin['EMAIL'],
				'LEARNMORE_LINK' => $learnmoreLink,
				'SUPPORT_LINK'   => $supportLink
			));
		}

		return '';
	}

	public static function LoadCustomMessages()
	{
		//loads custom language messages for organization types
		$organizationType = \Bitrix\Main\Config\Option::get("intranet", "organization_type");
		if($organizationType <> '' && !preg_match('/[^a-z0-9_-]/', $organizationType))
		{
			\Bitrix\Main\Localization\Loc::loadCustomMessages(__DIR__."/../../organization/".$organizationType.".php");
		}
	}

	public static function getB24Host()
	{
		switch (LANGUAGE_ID)
		{
			case 'la':
				return 'www.bitrix24.es';
			case 'br':
				return 'www.bitrix24.com.br';
			case 'tc':
			case 'sc':
				return 'www.bitrix24.cn';
			case 'ru':
			case 'de':
			case 'ua':
				return 'www.bitrix24.'.LANGUAGE_ID;
			default:
				return 'www.bitrix24.com';
		}
	}

	public static function getHostName()
	{
		static $host;

		if (is_null($host))
		{
			$ttl = (CACHED_b_lang !== false ? CACHED_b_lang : 0);

			$site = Bitrix\Main\SiteTable::getList(array(
				'filter' => defined('SITE_ID') ? array('=LID' => SITE_ID) : array(),
				'order'  => array('ACTIVE' => 'DESC', 'DEF' => 'DESC', 'SORT' => 'ASC'),
				'select' => array('SERVER_NAME'),
				'cache' => [ 'ttl' => $ttl ],
			))->fetch();

			$host = isModuleInstalled('bitrix24') && defined('BX24_HOST_NAME') ? BX24_HOST_NAME
				: ($site['SERVER_NAME'] ?: COption::getOptionString('main', 'server_name', ''));
		}

		return $host;
	}

	protected static function getB24Referral($source)
	{
		$params = 'c='.self::getHostName();

		if (Loader::includeModule('bitrix24'))
		{
			$pid = (int) COption::getOptionInt('bitrix24', 'partner_id', 0);
			$isNfr = \CBitrix24::IsNfrLicense();

			if ($pid > 0 && $isNfr)
			{
				$params .= '&p='.$pid;
				if ($source)
				{
					$params .= '&p1=' . urlencode($source);
				}
			}
		}

		return $params;
	}

	public static function getB24CreateLink($src = false)
	{
		return 'https://'.self::getB24Host().'/create.php?'.self::getB24Referral($src);
	}

	public static function getB24Link($src = false)
	{
		return 'https://'.self::getB24Host().'/?'.self::getB24Referral($src);
	}

	/**
	 * @deprecated use FirstPage\Page::createInstance()->getLink()
	 */
	public static function getB24FirstPageLink()
	{
		return \Bitrix\Intranet\Portal\FirstPage::getInstance()->getLink();
	}

	public static function getCurrentDateTimeFormat($params = array())
	{
		$woYear = (!empty($params['woYear']) && $params['woYear']);
		$woTime = (!empty($params['woTime']) && $params['woTime']);

		$culture = \Bitrix\Main\Context::getCurrent()->getCulture();
		$currentDateTimeFormat = ($woYear ? $culture->getDayMonthFormat() : $culture->getLongDateFormat());
		if (!$woTime)
		{
			$currentDateTimeFormat .= ' '.$culture->getShortTimeFormat();
		}

		return $currentDateTimeFormat;
	}

	public static function clearMenuCache()
	{
		if (\Bitrix\Main\ModuleManager::isModuleInstalled("bitrix24"))
		{
			if (defined("BX_COMP_MANAGED_CACHE"))
			{
				global $CACHE_MANAGER;
				$CACHE_MANAGER->ClearByTag("bitrix24_left_menu");
			}
		}
		else
		{
			$GLOBALS["CACHE_MANAGER"]->CleanDir("menu");
			CBitrixComponent::clearComponentCache("bitrix:menu");
		}
	}

	/**
	 * @see \Bitrix\Main\Application::getLicense
	 * @see \Bitrix\Main\License::getRegion
	 * @deprecated Use Application::getInstance()->getLicense()->getRegion() instead.
	 * @return string
	 */
	public static function getPortalZone()
	{
		$portalZone = COption::GetOptionString("main", "vendor", "1c_bitrix_portal");

		switch ($portalZone)
		{
			case "ua_bitrix_portal":
				return "ua";
			case "bitrix_portal":
				return "en";
			case "1c_bitrix_portal":
			default:
				return "ru";
		}
	}
}
