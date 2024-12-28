<?

use Bitrix\Main\Context;
use Bitrix\Main\Web\Cookie;
use Bitrix\Timeman\Helper\EntityCodesHelper;
use Bitrix\Timeman\Model\Schedule\Schedule;
use Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord;
use Bitrix\Timeman\Service\DependencyManager;
use Bitrix\Timeman\Integration;

class CTimeMan
{
	private static $SECTIONS_SETTINGS_CACHE = null;
	private static $arWasElapedCache = [];

	public static function CanUse($bAdminAction = false)
	{
		global $USER, $USER_FIELD_MANAGER;
		$userPermissionManager = DependencyManager::getInstance()->getUserPermissionsManager($USER);
		if ($bAdminAction)
		{
			return
				$userPermissionManager->canReadWorktimeAll()
				|| $userPermissionManager->canReadWorktimeSubordinate();
		}

		if ($USER->IsAuthorized() && $userPermissionManager->canManageWorktime())
		{
			$TMUSER = CTimeManUser::instance();
			$arSettings = $TMUSER->GetSettings(['UF_TIMEMAN']);

			return $arSettings['UF_TIMEMAN'];
		}

		return false;
	}

	public static function IsAdmin()
	{
		global $USER;
		$userPermissionManager = DependencyManager::getInstance()->getUserPermissionsManager($USER);
		return $userPermissionManager->canUpdateWorktimeAll() || $userPermissionManager->canManageWorktimeAll();
	}

	public static function getRuntimeInfo($bFull = false)
	{
		global $USER;

		$TMUSER = CTimeManUser::instance();
		$STATE = $TMUSER->State();

		$info = ['ID' => '', 'STATE' => $STATE, 'CAN_EDIT' => 'N'];

		$actionsBuilder = DependencyManager::getInstance()
			->getWorktimeActionList()
			->buildPossibleActionsListForUser($TMUSER->GetID());

		$actions = $actionsBuilder->getAllActions();

		if ($STATE == 'CLOSED')
		{
			$info['CAN_OPEN'] = $TMUSER->OpenAction();
		}
		elseif ($STATE == 'EXPIRED')
		{
			$info['EXPIRED_DATE'] = $TMUSER->GetExpiredRecommendedDate();
		}

		$arSettings = $TMUSER->GetSettings(['UF_TM_REPORT_REQ']);
		$info['REPORT_REQ'] = $arSettings['UF_TM_REPORT_REQ'] ?? null;
		$info['TM_FREE'] = false;
		if ($arInfo = $TMUSER->GetCurrentInfo(count($actions) > 1))
		{
			$record = WorktimeRecord::wakeUpRecord($arInfo);
			foreach ($actionsBuilder->getAllActions() as $worktimeAction)
			{
				$info['TM_FREE'] = $worktimeAction->getSchedule() && $worktimeAction->getSchedule()->isFlextime();
			}
			foreach ($actionsBuilder->getStartActions() as $startAction)
			{
				// for cases, when user had fixed schedule and now user has flextime
				$info['TM_FREE'] = $startAction->getSchedule() && $startAction->getSchedule()->isFlextime();
			}
			$info['ID'] = $arInfo['ID'];

			$info['CAN_EDIT'] = !empty($actionsBuilder->getEditActions()) ? 'Y' : 'N';
			$timeFinish = $arInfo['TIME_FINISH'];
			if ((int)$arInfo['TIME_FINISH'] === 0)
			{
				if (!WorktimeRecord::isRecordPaused($arInfo) && !WorktimeRecord::isRecordClosed($arInfo))
				{
					$timeFinish = null;
				}
			}
			$info['INFO'] = [
				'DATE_START' => MakeTimeStamp($arInfo['DATE_START']) - CTimeZone::GetOffset(),
				'DATE_FINISH' => $arInfo['DATE_FINISH']
					? (MakeTimeStamp($arInfo['DATE_FINISH']) - CTimeZone::GetOffset())
					: '',
				'TIME_START' => $arInfo['TIME_START'],
				'TIME_FINISH' => $timeFinish,
				'DURATION' => $arInfo['RECORDED_DURATION'],
				'TIME_LEAKS' => $arInfo['TIME_LEAKS'],
				'ACTIVE' => ($arInfo['ACTIVE'] == 'Y'),
				'PAUSED' => ($arInfo['PAUSED'] == 'Y'),
				'CURRENT_STATUS' => $arInfo['CURRENT_STATUS'],
			];
			if (!empty($actionsBuilder->getStopActions()))
			{
				foreach ($actionsBuilder->getStopActions() as $stopAction)
				{
					if ($stopAction->getRecord() && $stopAction->getRecord()->getId() === $record->getId()
						&& $stopAction->getRecordManager())
					{
						$info['INFO']['RECOMMENDED_CLOSE_TIMESTAMP'] = $stopAction->getRecordManager()->getRecommendedStopTimestamp();
					}
				}
			}
			if (isset($arInfo['LAST_PAUSE']) && $arInfo['LAST_PAUSE'])
			{
				$info['LAST_PAUSE'] = $arInfo['LAST_PAUSE'];
			}
			elseif (isset($arInfo['PAUSED']) && $arInfo['PAUSED'] === 'Y')
			{
				$info['LAST_PAUSE'] = [
					'DATE_START' => $info['INFO']['DATE_FINISH'],
				];
			}

			$info['SOCSERV_ENABLED'] = IsModuleInstalled('socialservices')
									   && (COption::GetOptionString("socialservices", "allow_send_user_activity", "Y") == 'Y');
			if ($bFull && $info['SOCSERV_ENABLED'])
			{
				$info['SOCSERV_ENABLED_USER'] = $TMUSER->isSocservEnabledByUser();
			}
		}
		else
		{
			foreach ($actionsBuilder->getStartActions() as $worktimeAction)
			{
				$info['TM_FREE'] = $worktimeAction->getSchedule() && $worktimeAction->getSchedule()->isFlextime();
			}
		}

		$info['CHECKIN_COUNTER'] = Integration\Stafftrack\Counter::get();

		$planner = CIntranetPlanner::getData(SITE_ID, $bFull);
		$plannerData = $planner['DATA'];

		$eventsLimit = 50;
		if (is_array($plannerData['EVENTS']) && count($plannerData['EVENTS']) > $eventsLimit)
		{
			$plannerData['EVENTS'] = self::limitEvents($eventsLimit, $plannerData['EVENTS']);

			$planner['DATA'] = $plannerData;
		}

		$info['PLANNER'] = $planner;

		$info["FULL"] = $bFull;

		if (!empty($actionsBuilder->getStartActions()) && !empty($actionsBuilder->getReopenActions()))
		{
			$info["CAN_OPEN_AND_RELAUNCH"] = true;
		}

		return $info;
	}

	/**
	 * DEPRECATED! Migrated to tasks module.
	 *
	 * @deprecated
	 */
	public static function GetTaskTime($arParams)
	{
		if ($arParams['EXPIRED_DATE'] > 0)
		{
			$arParams['EXPIRED_DATE'] += CTimeMan::RemoveHoursTS($arParams['DATE_START']);
		}

		if (CModule::IncludeModule('tasks'))
		{
			$time = 0;

			$arFilter = ['TASK_ID' => $arParams['TASK_ID'], 'USER_ID' => $arParams['USER_ID'], '>=CREATED_DATE' => ConvertTimeStamp($arParams['DATE_START'], 'FULL')];
			if ($arParams['DATE_FINISH'])
			{
				$arFilter['<CREATED_DATE'] = ConvertTimeStamp($arParams['DATE_FINISH'], 'FULL');
			}
			elseif ($arParams['EXPIRED_DATE'])
			{
				$arFilter['<CREATED_DATE'] = ConvertTimeStamp($arParams['EXPIRED_DATE']);
			}

			$dbRes = CTaskElapsedTime::GetList(['CREATED_DATE' => 'ASC'], $arFilter);

			while ($arRes = $dbRes->Fetch())
			{
				self::$arWasElapedCache[$arRes['TASK_ID']] = true;
				$time += $arRes['MINUTES'] * 60;
			}

			if ($time == 0)
			{
				$arFilter['FIELD'] = 'STATUS';

				$dbRes = CTaskLog::GetList(['CREATED_DATE' => 'ASC'], $arFilter);

				$current_time = $arParams['DATE_START'];
				$last_status = $arParams['TASK_STATUS'];
				while ($arRes = $dbRes->Fetch())
				{
					if ($arRes['FROM_VALUE'] == 3)
					{
						$time += MakeTimeStamp($arRes['CREATED_DATE']) - $current_time;
					}
					elseif ($arRes['TO_VALUE'] == 3)
					{
						$current_time = MakeTimeStamp($arRes['CREATED_DATE']);
					}

					$last_status = $arRes['TO_VALUE'];
				}

				if ($last_status == 3)
				{
					if ($arParams['DATE_FINISH'])
					{
						$time += $arParams['DATE_FINISH'] - $current_time;
					}
					elseif ($arParams['EXPIRED_DATE'])
					{
						$time += $arParams['EXPIRED_DATE'] - $current_time;
					}
					else
					{
						$time += time() + CTimeZone::GetOffset() - $current_time;
					}
				}
			}

			return $time;
		}

		return false;
	}

	/**
	 * DEPRECATED! Migrated to tasks module.
	 *
	 * @deprecated
	 */
	public static function SetTaskTime($arParams)
	{
		if (!self::$arWasElapedCache[$arParams['TASK_ID']])
		{
			$ob = new CTaskElapsedTime();
			$ob->Add([
				'USER_ID' => $arParams['USER_ID'],
				'TASK_ID' => $arParams['TASK_ID'],
				'MINUTES' => intval($arParams['TIME'] / 60),
				'COMMENT_TEXT' => GetMessage('TIMEMAN_MODULE_NAME'),
			]);
		}
	}

	public static function GetAccessSettings()
	{
		$r = COption::GetOptionString('timeman', 'SUBORDINATE_ACCESS', '');
		if ($r <> '')
		{
			$r = unserialize($r, ['allowed_classes' => false]);
		}

		if (!is_array($r))
		{
			$r = [
				'READ' => ['EMPLOYEE' => 0, 'HEAD' => 1],
				'WRITE' => ['HEAD' => 1],
			];
		}

		return $r;
	}

	public static function GetAccess()
	{
		global $USER;

		// simplest caching. is it enough? maybe...
		static $access = null;

		if (!is_array($access))
		{
			$access = [
				'READ' => [],
				'WRITE' => [],
			];

			$arAccessSettings = null;
			$subordinateList = [];
			$userPermissionManager = DependencyManager::getInstance()->getUserPermissionsManager($USER);

			if ($userPermissionManager->canReadWorktimeAll())
			{
				$access['READ'][] = '*';
			}
			elseif ($userPermissionManager->canReadWorktimeSubordinate())
			{
				$arAccessSettings = self::GetAccessSettings();

				if ($arAccessSettings['READ']['EMPLOYEE'] >= 2)
				{
					$access['READ'][] = '*';
				}
				else
				{
					// everybody can read his own entries
					$access['READ'][] = $USER->GetID();

					if ($arAccessSettings['READ']['EMPLOYEE'] >= 1)
					{
						$dbUsers = CIntranetUtils::GetDepartmentColleagues(null, false, false, 'Y', ['ID']);
						while ($arRes = $dbUsers->Fetch())
						{
							$access['READ'][] = $arRes['ID'];
						}
					}

					$dbUsers = CIntranetUtils::GetSubordinateEmployees($USER->GetID(), $arAccessSettings['READ']['HEAD'] == 1, 'Y', ['ID']);
					while ($arRes = $dbUsers->Fetch())
					{
						if ($arAccessSettings['READ']['HEAD'] == 2)
						{
							$access['READ'] = ['*'];
							break;
						}

						if (!isset($subordinateList[intval($arAccessSettings['READ']['HEAD'])]))
						{
							$subordinateList[intval($arAccessSettings['READ']['HEAD'])] = [];
						}

						$subordinateList[intval($arAccessSettings['READ']['HEAD'])][] = $arRes;
						$access['READ'][] = $arRes['ID'];
					}

					$access['READ'] = array_values(array_unique($access['READ']));
				}
			}

			if ($userPermissionManager->canUpdateWorktimeAll())
			{
				$access['WRITE'][] = '*';
			}
			elseif ($userPermissionManager->canUpdateWorktimeSubordinate())
			{
				if (($arAccessSettings['WRITE']['EMPLOYEE'] ?? 0) >= 2)
				{
					$access['WRITE'][] = '*';
				}
				else
				{
					// check if current user is The Boss.
					$arManagers = self::GetUserManagers($USER->GetID());
					if (count($arManagers) == 1 && $arManagers[0] == $USER->GetID())
					{
						$access['WRITE'][] = $USER->GetID();
					}

					if (!is_array($arAccessSettings))
					{
						$arAccessSettings = self::GetAccessSettings();
					}

					if (isset($subordinateList[intval($arAccessSettings['WRITE']['HEAD'])]))
					{
						foreach ($subordinateList[intval($arAccessSettings['WRITE']['HEAD'])] as $arRes)
						{
							$access['WRITE'][] = $arRes['ID'];
						}
					}
					else
					{
						$dbUsers = CIntranetUtils::GetSubordinateEmployees($USER->GetID(), $arAccessSettings['WRITE']['HEAD'] == 1, 'Y', ['ID']);
						while ($arRes = $dbUsers->Fetch())
						{
							$access['WRITE'][] = $arRes['ID'];
						}
					}

					$access['WRITE'] = array_values(array_unique($access['WRITE']));
				}
			}
		}

		return $access;
	}

	public static function GetDirectAccess($USER_ID = false)
	{
		global $USER;
		$USER_ID = intval($USER_ID);
		if ($USER_ID <= 0)
		{
			$USER_ID = $USER->GetID();
		}
		$arSDeps = CIntranetUtils::GetSubordinateDepartments($USER_ID, true);
		$arStruct = CIntranetUtils::GetStructure();
		$arEmployees = [];
		foreach ($arSDeps as $dpt)
		{
			$arCurDpt = $arStruct['DATA'][$dpt];

			if (!empty($arCurDpt["UF_HEAD"]))
			{
				$employee = $arCurDpt["UF_HEAD"];
			}
			else
			{
				if (
					!empty($arCurDpt["EMPLOYEES"])
					&& is_array($arCurDpt["EMPLOYEES"])
					&& count($arCurDpt["EMPLOYEES"]) > 0
				)
				{
					$employee = $arCurDpt["EMPLOYEES"][0];
				}
				else
				{
					$employee = false;
				}
			}

			if ($employee && $employee == $USER_ID)//this user is a head manager
			{
				foreach ($arCurDpt["EMPLOYEES"] as $empUser)
				{
					$arEmployees[] = $empUser;
				}
			}
			elseif ($employee)//no head manager or this user is no head manager
			{

				$headManager = CTimeMan::GetUserManagers($employee);//find head manager of employee
				if ($USER_ID == $headManager[0])//
				{
					if ($arCurDpt["UF_HEAD"])
					{
						$arEmployees[] = $employee;
					}
					else
					{
						foreach ($arCurDpt["EMPLOYEES"] as $empUser)
						{
							$arEmployees[] = $empUser;
						}
					}
				}
			}
		}

		return array_unique($arEmployees);
	}

	public static function GetSectionPersonalSettings($section_id, $bHideParentLinks = false, $arNeededSettings = null)
	{
		if (null == self::$SECTIONS_SETTINGS_CACHE)
		{
			self::_GetTreeSettings();
		}

		if (!$bHideParentLinks)
		{
			if (!is_array($arNeededSettings))
			{
				return self::$SECTIONS_SETTINGS_CACHE[$section_id];
			}
			else
			{
				$ar = self::$SECTIONS_SETTINGS_CACHE[$section_id];
				foreach ($ar as $key => $value)
				{
					if (!in_array($key, $arNeededSettings))
					{
						unset($ar[$key]);
					}
				}
				return $ar;
			}
		}
		else
		{
			$res = self::$SECTIONS_SETTINGS_CACHE[$section_id];
			foreach ($res as $key => $value)
			{
				if (is_array($arNeededSettings) && !in_array($key, $arNeededSettings))
				{
					unset($res[$key]);
				}
				elseif (mb_substr($res[$key], 0, 8) == '_PARENT_')
				{
					$res[$key] = null;
				}
			}
			return $res;
		}
	}

	public static function GetModuleSettings($arNeededSettings = false)
	{
		$arOptionsSettings = [
			'UF_TIMEMAN' => true,
			'UF_TM_MAX_START' => COption::GetOptionInt('timeman', 'workday_max_start', 33300),
			'UF_TM_MIN_FINISH' => COption::GetOptionInt('timeman', 'workday_min_finish', 63900),
			'UF_TM_MIN_DURATION' => COption::GetOptionInt('timeman', 'workday_min_duration', 28800),
			'UF_TM_REPORT_REQ' => COption::GetOptionString('timeman', 'workday_report_required', 'A'),
			'UF_TM_ALLOWED_DELTA' => COption::GetOptionInt('timeman', 'workday_allowed_delta', '900'),
			'UF_TM_REPORT_TPL' => [],
			'UF_TM_FREE' => false,
		];

		if (!$arNeededSettings)
		{
			return $arOptionsSettings;
		}
		else
		{
			$res = [];
			foreach ($arNeededSettings as $k)
			{
				$res[$k] = $arOptionsSettings[$k];
			}

			return $res;
		}
	}

	public static function GetSectionSettings($section_id, $arNeededSettings = null)
	{
		if (null == self::$SECTIONS_SETTINGS_CACHE)
		{
			self::_GetTreeSettings();
		}

		if ($section_id > 0)
		{
			$res = self::GetSectionPersonalSettings($section_id);

			$arSettings = is_array($arNeededSettings) ? $arNeededSettings : ['UF_TIMEMAN', 'UF_TM_MAX_START', 'UF_TM_MIN_FINISH', 'UF_TM_MIN_DURATION', 'UF_TM_REPORT_REQ', 'UF_TM_REPORT_TPL', 'UF_TM_FREE', 'UF_TM_REPORT_DATE', 'UF_TM_DAY', 'UF_REPORT_PERIOD', 'UF_TM_TIME', 'UF_TM_ALLOWED_DELTA'];

			if (is_array($res) && count($arSettings) > 0)
			{
				$parent = 0;
				foreach ($res as $key => $v)
				{
					if (!in_array($key, $arSettings))
					{
						unset($res[$key]);
					}
				}

				foreach ($arSettings as $k => $key)
				{
					if (!is_array($res[$key]) && mb_substr($res[$key], 0, 8) == '_PARENT_')
					{
						$parent = intval(mb_substr($res[$key], 9));
						unset($res[$key]);
					}
					else
					{
						unset($arSettings[$k]);
					}
				}

				if (count($arSettings) > 0 && $parent > 0)
				{
					$res = array_merge($res, self::GetSectionSettings($parent, $arSettings));
				}

				if ($arNeededSettings === null)
				{
					foreach ($res as $key => $value)
					{
						if (!is_array($res[$key]) && mb_substr($res[$key], 0, 8) == '_PARENT_')
						{
							$res[$key] = '';
						}
					}
				}

				if (isset($res['UF_TIMEMAN']) && !$res['UF_TIMEMAN'])
				{
					$res['UF_TIMEMAN'] = 'Y';
				}
				if (isset($res['UF_TM_REPORT_TPL']) && !is_array($res['UF_TM_REPORT_TPL']))
				{
					$res['UF_TM_REPORT_TPL'] = [];
				}

				return $res;
			}
		}

		return [];
	}

	private static function _GetTreeSettings()
	{
		global $USER_FIELD_MANAGER, $CACHE_MANAGER;

		self::$SECTIONS_SETTINGS_CACHE = [];

		$ibDept = COption::GetOptionInt('intranet', 'iblock_structure', false);

		$cache_id = 'timeman|structure_settings|' . $ibDept;

		if (CACHED_timeman_settings !== false
			&& $CACHE_MANAGER->Read(CACHED_timeman_settings, $cache_id, "timeman_structure_" . $ibDept))
		{
			self::$SECTIONS_SETTINGS_CACHE = $CACHE_MANAGER->Get($cache_id);
		}
		else
		{
			$arAllFields = $USER_FIELD_MANAGER->GetUserFields('IBLOCK_' . $ibDept . '_SECTION');

			$arUFValues = [];

			$arEnumFields = ['UF_TIMEMAN', 'UF_TM_REPORT_REQ', 'UF_REPORT_PERIOD'];
			foreach ($arEnumFields as $fld)
			{
				$dbRes = CUserFieldEnum::GetList([], [
					'USER_FIELD_ID' => $arAllFields[$fld]['ID'],
				]);
				while ($arRes = $dbRes->Fetch())
				{
					$arUFValues[$arRes['ID']] = $arRes['XML_ID'];
				}
			}

			$arSettings = ['UF_TIMEMAN', 'UF_TM_MAX_START', 'UF_TM_MIN_FINISH', 'UF_TM_MIN_DURATION', 'UF_TM_REPORT_REQ', 'UF_TM_REPORT_TPL', 'UF_TM_REPORT_DATE', 'UF_TM_DAY', 'UF_REPORT_PERIOD', 'UF_TM_TIME', 'UF_TM_ALLOWED_DELTA'];
			$arReportSettings = ['UF_TM_REPORT_DATE', 'UF_TM_DAY', 'UF_TM_TIME'];
			$dbRes = CIBlockSection::GetList(
				["LEFT_MARGIN" => "ASC"],
				['IBLOCK_ID' => $ibDept, 'ACTIVE' => 'Y'],
				false,
				['ID', 'IBLOCK_SECTION_ID', 'UF_TIMEMAN', 'UF_TM_MAX_START', 'UF_TM_MIN_FINISH', 'UF_TM_MIN_DURATION', 'UF_TM_REPORT_REQ', 'UF_TM_REPORT_TPL', 'UF_REPORT_PERIOD', 'UF_TM_REPORT_DATE', 'UF_TM_DAY', 'UF_TM_TIME', 'UF_TM_ALLOWED_DELTA']
			);
			while ($arRes = $dbRes->Fetch())
			{
				$arSectionSettings = [];
				foreach ($arSettings as $key)
				{
					$arSectionSettings[$key] = (
						$arRes[$key] && $arRes[$key] != '00:00'
						? (
							(
								!is_array($arRes[$key])
								&& isset($arUFValues[$arRes[$key]])
								&& !in_array($key, $arReportSettings)
							)
								? $arUFValues[$arRes[$key]]
								: (
									in_array($key, $arReportSettings)
										? $arRes[$key]
										: (
											is_array($arRes[$key])
												? $arRes[$key]
												: self::MakeShortTS($arRes[$key])
										)
								)
						)
						: (
						$arRes['IBLOCK_SECTION_ID'] > 0
							? '_PARENT_|' . $arRes['IBLOCK_SECTION_ID']
							: ''
						)
					);
				}
				$arSectionSettings['UF_TM_FREE'] = 'N';
				self::$SECTIONS_SETTINGS_CACHE[$arRes['ID']] = $arSectionSettings;
			}

			if (CACHED_timeman_settings !== false)
			{
				$CACHE_MANAGER->Set($cache_id, self::$SECTIONS_SETTINGS_CACHE);
			}
		}
		$departmentIds = array_keys(self::$SECTIONS_SETTINGS_CACHE);
		if (!empty($departmentIds))
		{
			$schedules = DependencyManager::getInstance()->getScheduleRepository()
				->findSchedulesByEntityCodes(EntityCodesHelper::buildDepartmentCodes($departmentIds));
			foreach ($schedules as $code => $depSchedules)
			{
				foreach ($depSchedules as $depSchedule)
				{
					/** @var Schedule $depSchedule */
					if ($depSchedule->isFlextime())
					{
						$depId = (string)EntityCodesHelper::getDepartmentId($code);
						self::$SECTIONS_SETTINGS_CACHE[$depId]['UF_TM_FREE'] = 'Y';
					}
				}
			}
		}
	}

	/* time functions */
	public static function RemoveHoursTS($ts)
	{
		return $ts - self::GetTimeTS($ts, true);
	}

	public static function GetTimeTS($datetime, $bTS = false)
	{
		$ts = $bTS ? $datetime : MakeTimeStamp($datetime);

		if ($ts < 86400) // partial time
		{
			return $ts;
		}
		else
		{
			return ($ts + date('Z')) % 86400;
		}
	}

	public static function FormatTime($ts, $bTS = false)
	{
		$ts = self::GetTimeTS($ts, $bTS);
		return str_pad(intval($ts / 3600), 2, '0', STR_PAD_LEFT) . ':' . str_pad(intval(($ts % 3600) / 60), 2, '0', STR_PAD_LEFT);
	}

	public static function FormatTimeOut($ts)
	{
		$ts = MakeTimeStamp(ConvertTimeStamp()) + $ts % 86400;
		return FormatDate(IsAmPmMode() ? 'h:i a' : 'H:i', $ts);
	}

	public static function MakeShortTS($time)
	{
		static $arCoefs = [3600, 60, 1];

		if ($time === intval($time))
		{
			return $time % 86400;
		}

		$amPmTime = explode(' ', $time ?? '');
		if (count($amPmTime) > 1)
		{
			$time = $amPmTime[0];
			$mt = $amPmTime[1];
		}

		$arValues = explode(':', $time ?? '');

		$cnt = count($arValues);
		if ($cnt <= 1)
		{
			return 0;
		}
		elseif ($cnt <= 2)
		{
			$arValues[] = 0;
		}

		// if time as AmPm
		if (!empty($mt) && strcasecmp($mt, 'pm') === 0)
		{
			if ($arValues[0] < 12)
			{
				$arValues[0] = $arValues[0] + 12;
			}
		}

		$ts = 0;
		for ($i = 0; $i < 3; $i++)
		{
			$ts += intval($arValues[$i] * $arCoefs[$i]);
		}

		return $ts % 86400;
	}

	public static function ConvertShortTS($ts, $strDate = false)
	{
		if (!$strDate)
		{
			$strDate = ConvertTimeStamp(false, 'SHORT');
		};

		return MakeTimeStamp($strDate) + $ts % 86400;
	}

	public static function GetUserManagers($USER_ID, $bCheckExistance = true)
	{
		$arStruct = CIntranetUtils::GetStructure();

		$arHeads = [];

		foreach ($arStruct['DATA'] as $dpt => $arDpt)
		{
			if (in_array($USER_ID, $arDpt['EMPLOYEES']))
			{
				$arCurDpt = $arDpt;

				while (
					(
						!$arCurDpt['UF_HEAD']
						|| $arCurDpt['UF_HEAD'] == $USER_ID
						|| (
							$bCheckExistance
							&& (
								!($arUser = CUser::getList('ID', 'ASC',
									['ID'=> $arCurDpt['UF_HEAD']], ['FIELDS' => ['ID', 'ACTIVE']])->fetch())
								|| $arUser['ACTIVE'] === 'N'
							)
						)
					)
					&& $arCurDpt['IBLOCK_SECTION_ID'] > 0
				)
				{
					$arCurDpt = $arStruct['DATA'][$arCurDpt['IBLOCK_SECTION_ID']];
				}

				if ($arCurDpt['UF_HEAD'])
				{
					$arHeads[] = $arCurDpt['UF_HEAD'];
				}
			}
		}

		return array_unique($arHeads);
	}

	private static function limitEvents(int $limit, array $listEvents): array
	{
		return array_slice($listEvents, 0, $limit);
	}
}

/********************** calendars interface ********************/
abstract class ITimeManCalendar
{
	abstract public function Add($arParams);

	abstract public function Get($arParams);
}

class CTimeManCalendar
{
	private static $cal = null;

	private static function _Init()
	{
		if (COption::GetOptionString("intranet", "calendar_2", "N") == "Y" && CModule::IncludeModule('calendar'))
		{
			self::$cal = new _CTimeManCalendarNew();
		}
		else
		{
			self::$cal = new _CTimeManCalendarOld();
		}
	}

	public static function Add($arParams)
	{
		if (!self::$cal)
		{
			self::_Init();
		}
		return self::$cal->Add($arParams);
	}

	public static function Get($arParams)
	{
		if (!self::$cal)
		{
			self::_Init();
		}
		return self::$cal->Get($arParams);
	}
}

class _CTimeManCalendarNew extends ITimeManCalendar
{
	public function Add($arParams)
	{
		global $USER;

		$today = CTimeMan::RemoveHoursTS(time());
		$data = [
			'CAL_TYPE' => 'user',
			'OWNER_ID' => $USER->GetID(),
			'NAME' => $arParams['name'],
			'DT_FROM' => ConvertTimeStamp($today + CTimeMan::MakeShortTS($arParams['from']), 'FULL'),
			'DT_TO' => ConvertTimeStamp($today + CTimeMan::MakeShortTS($arParams['to']), 'FULL'),
		];
		if ($arParams['absence'] == 'Y')
		{
			$data['ACCESSIBILITY'] = 'absent';
		}

		return CCalendar::SaveEvent([
			'arFields' => $data,
			'userId' => $USER->GetID(),
			'autoDetectSection' => true,
			'autoCreateSection' => true,
		]);
	}

	public function Get($arParams)
	{
		global $USER;

		$arEvents = CCalendarEvent::GetList(
			[
				'arFilter' => [
					"ID" => $arParams['ID'],
					"DELETED" => "N",
				],
				'parseRecursion' => true,
				'fetchAttendees' => true,
				'checkPermissions' => true,
			]
		);

		if (is_array($arEvents) && count($arEvents) > 0)
		{
			$arEvent = $arEvents[0];
			if ($arEvent['IS_MEETING'])
			{
				$arEvent['GUESTS'] = [];
				if (is_array($arEvent['ATTENDEE_LIST']))
				{
					$userIndex = CCalendarEvent::getUserIndex();
					foreach ($arEvent['ATTENDEE_LIST'] as $attendee)
					{
						if (isset($userIndex[$attendee["id"]]))
						{
							$arEvent['GUESTS'][] = [
								'id' => $attendee['id'],
								'name' => $userIndex[$attendee["id"]]['DISPLAY_NAME'],
								'status' => $attendee['status'],
								'accessibility' => $arEvent['ACCESSIBILITY'],
								'bHost' => $attendee['id'] == $arEvent['MEETING_HOST'],
							];

							if ($attendee['id'] == $USER->GetID())
							{
								$arEvent['STATUS'] = $attendee['status'];
							}
						}
					}
				}
				elseif (is_array($arEvent['~ATTENDEES']))
				{
					foreach ($arEvent['~ATTENDEES'] as $guest)
					{
						$arEvent['GUESTS'][] = [
							'id' => $guest['USER_ID'],
							'name' => CUser::FormatName(CSite::GetNameFormat(false), $guest, true),
							'status' => $guest['STATUS'],
							'accessibility' => $guest['ACCESSIBILITY'],
							'bHost' => $guest['USER_ID'] == $arEvent['MEETING_HOST'],
						];

						if ($guest['USER_ID'] == $USER->GetID())
						{
							$arEvent['STATUS'] = $guest['STATUS'];
						}
					}
				}
			}

			$set = CCalendar::GetSettings();
			$url = str_replace(
					   '#user_id#', $arEvent['CREATED_BY'], $set['path_to_user_calendar']
				   ) . '?EVENT_ID=' . $arEvent['ID'];

			return [
				'ID' => $arEvent['ID'],
				'NAME' => $arEvent['NAME'],
				'DETAIL_TEXT' => $arEvent['DESCRIPTION'],
				'DATE_FROM' => $arEvent['DATE_FROM'],
				'DATE_TO' => $arEvent['DATE_TO'],
				'ACCESSIBILITY' => $arEvent['ACCESSIBILITY'],
				'IMPORTANCE' => $arEvent['IMPORTANCE'],
				'STATUS' => $arEvent['STATUS'] ?? null,
				'IS_MEETING' => $arEvent['IS_MEETING'] ? 'Y' : 'N',
				'GUESTS' => $arEvent['GUESTS'] ?? null,
				'URL' => $url,
			];
		}
	}
}

class _CTimeManCalendarOld extends ITimeManCalendar
{
	public function Add($arParams)
	{
		global $USER;

		$res = null;

		$calendar_id = $arParams['calendar_id'];

		$calIblock = COption::GetOptionInt('intranet', 'iblock_calendar', null, $arParams['site_id']);
		$calIblockSection = CEventCalendar::GetSectionIDByOwnerId($USER->GetID(), 'USER', $calIblock);

		if (!$calendar_id)
		{
			$calendar_id = CUserOptions::GetOption('timeman', 'default_calendar', 0);
		}

		if ($calIblockSection > 0)
		{
			$arCalendars = CEventCalendar::GetCalendarList([$calIblock, $calIblockSection, 0, 'USER']);

			if (count($arCalendars) == 1)
			{
				if (
					$calendar_id
					&& $calendar_id != $arCalendars[0]['ID']
				)
				{
					CUserOptions::DeleteOption('timeman', 'default_calendar');
				}

				$calendar_id = $arCalendars[0]['ID'];
			}
			else
			{
				$bCalendarFound = false;

				$arCalsList = [];
				foreach ($arCalendars as $cal)
				{
					if ($cal['ID'] == $calendar_id)
					{
						$bCalendarFound = true;
						break;
					}

					$arCalsList[] = [
						'ID' => $cal['ID'],
						'NAME' => $cal['NAME'],
						'COLOR' => $cal['COLOR'],
					];
				}

				if (!$bCalendarFound)
				{
					$bReturnRes = true;
					$res = ['error_id' => 'CHOOSE_CALENDAR', 'error' => ['TEXT' => GetMessage('TM_CALENDAR_CHOOSE'), 'CALENDARS' => $arCalsList]];
				}
			}
		}

		if (!$bReturnRes)
		{
			if (!$calIblockSection)
			{
				$calIblockSection = 'none';
			}

			$today = CTimeMan::RemoveHoursTS(time());

			$data = [
				'DATE_FROM' => $today + CTimeMan::MakeShortTS($arParams['from']),
				'DATE_TO' => $today + CTimeMan::MakeShortTS($arParams['to']),
				'NAME' => $arParams['name'],
				'ABSENCE' => $arParams['absence'] == 'Y',
			];

			$obCalendar = new CEventCalendar();
			$obCalendar->Init([
				'ownerType' => 'USER',
				'ownerId' => $USER->GetID(),
				'bOwner' => true,
				'iblockId' => $calIblock,
				'bCache' => false,
			]);

			$arPermissions = $obCalendar->GetPermissions(
				[
					'setProperties' => true,
				]
			);

			$arRes = [
				'iblockId' => $obCalendar->iblockId,
				'ownerType' => $obCalendar->ownerType,
				'ownerId' => $obCalendar->ownerId,
				'bNew' => true,
				'fullUrl' => $obCalendar->fullUrl,
				'userId' => $obCalendar->userId,
				'pathToUserCalendar' => $obCalendar->pathToUserCalendar,
				'pathToGroupCalendar' => $obCalendar->pathToGroupCalendar,
				'userIblockId' => $obCalendar->userIblockId,
				'calendarId' => $calendar_id,
				'sectionId' => $calIblockSection,

				'dateFrom' => ConvertTimeStamp($data['DATE_FROM'], 'FULL'),
				'dateTo' => ConvertTimeStamp($data['DATE_TO'], 'FULL'),
				'name' => $data['NAME'],
				'desc' => '',
				'prop' => [
					'ACCESSIBILITY' => $data['ABSENCE'] ? 'absent' : 'busy',
				],
				'notDisplayCalendar' => true,
			];

			if ($GLOBALS['BX_TIMEMAN_RECENTLY_ADDED_EVENT_ID'] = $obCalendar->SaveEvent($arRes))
			{
				if ($_REQUEST['cal_set_default'] == 'Y')
				{
					CUserOptions::SetOption('timeman', 'default_calendar', $calendar_id);
				}
			}
		}

		return $res;
	}

	public function Get($arParams)
	{
		$ID = intval($arParams['ID']);
		$site_id = $arParams['site_id'];

		$calIblock = COption::GetOptionInt('intranet', 'iblock_calendar', null, $site_id);

		$dbRes = CIBlockElement::GetByID($ID);
		if ($arRes = $dbRes->Fetch())
		{
			$calIblockSection = $arRes['IBLOCK_SECTION_ID'];
		}
		else
		{
			return false;
		}

		CModule::IncludeModule('socialnetwork');

		$obCalendar = new CEventCalendar();
		$obCalendar->Init([
			'ownerType' => 'USER',
			'ownerId' => $arRes['CREATED_BY'],
			'bOwner' => true,
			'iblockId' => $calIblock,
			'userIblockId' => $calIblock,
		]);

		$arPermissions = $obCalendar->GetPermissions(
			[
				'setProperties' => true,
			]
		);

		$arEvents = $obCalendar->GetEvents([
			'iblockId' => $calIblock,
			'sectionId' => $calIblockSection,
			'eventId' => $ID,
			'bLoadAll' => true,
			'ownerType' => 'USER',
		]);

		return $arEvents[0];
	}
}