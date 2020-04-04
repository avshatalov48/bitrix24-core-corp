<?
class CTimeManUser
{
	public $SITE_ID = SITE_ID;

	protected $USER_ID;
	protected $ABSENCE_TYPE_ID = null;
	protected $SETTINGS = null;
	protected $bTasksEnabled;
	protected $UF_DEPARTMENT;

	protected static $instance = null;
	protected static $LAST_ENTRY = array();

	public static function instance()
	{
		if (!self::$instance)
			self::$instance = new CTimeManUser();

		return self::$instance;
	}

	public function __construct($USER_ID = 0, $site_id = SITE_ID)
	{
		$this->USER_ID = $USER_ID > 0 ? $USER_ID : $GLOBALS['USER']->GetID();
		$this->SITE_ID = $site_id;
		$this->bTasksEnabled = CModule::IncludeModule('tasks');
	}

	public function GetID()
	{
		return $this->USER_ID;
	}

	public function isEntryValid($action, $timestamp)
	{
		global $APPLICATION;

		$arSettings = $this->GetSettings();

		if (!$arSettings['UF_TM_FREE'] && $timestamp !== false)
		{
			// $timestamp is in server time yet
			if (abs(time()-$timestamp) > $arSettings['UF_TM_ALLOWED_DELTA'])
			{
				$APPLICATION->ThrowException('Time was changed manually', 'TIME_CHANGE');
				return false;
			}
		}

		return true;
	}

	public function EditDay($arParams)
	{
		global $APPLICATION;

		$arSettings = $this->GetSettings();

		if (
			(!$arSettings['UF_TM_FREE'] && strlen($arParams['REPORT']) < 0)
			|| COption::GetOptionString('timeman', 'workday_can_edit_current', 'Y') !== 'Y'
		)
		{
			if ($this->State() == 'EXPIRED')
			{
				unset($arParams['TIME_START']);
				unset($arParams['TIME_LEAKS']);
			}
			else
			{
				$GLOBALS['APPLICATION']->ThrowException('Access denied');
				return false;
			}
		}

		if (!isset($arParams['TIME_START']) && !isset($arParams['TIME_FINISH']) && !isset($arParams['TIME_LEAKS']))
			return;

		if ($last_entry = $this->_GetLastData(true))
		{
			$arFields = array(
				'ACTIVE' => 'N',
				'REPORTS' => array()
			);

			$strDate = ConvertTimeStamp(MakeTimeStamp($last_entry['DATE_START'], FORMAT_DATETIME), 'SHORT');

			if ($arParams['TIME_START'] !== null)
			{
				$arFields['REPORTS'][] = array(
					'REPORT_TYPE' => 'ERR_OPEN',
					'REPORT' => 'TIME_CHANGE;'.date('c').';Time was changed manually',
				);
				$arFields['REPORTS'][] = array(
					'REPORT_TYPE' => 'REPORT_OPEN',
					'REPORT' => $arParams['REPORT'],
				);

				$timestamp = CTimeMan::ConvertShortTS($arParams['TIME_START'], $strDate);
				$arFields['DATE_START'] = ConvertTimeStamp($timestamp, 'FULL');
			}
			else
			{
				$arFields['DATE_START'] = $last_entry['DATE_START'];
			}

			if ($arParams['TIME_FINISH'] !== null)
			{
				if ($this->State() == 'OPEN' || $this->State() == 'EXPIRED')
				{
					$arFields['IP_CLOSE'] = $_SERVER['REMOTE_ADDR'];
				}

				$arFields['PAUSED'] = 'N';

				$arFields['REPORTS'][] = array(
					'REPORT_TYPE' => 'ERR_CLOSE',
					'REPORT' => 'TIME_CHANGE;'.date('c').';Time was changed manually',
				);
				$arFields['REPORTS'][] = array(
					'REPORT_TYPE' => 'REPORT_CLOSE',
					'REPORT' => $arParams['REPORT'],
				);

				$timestamp = CTimeMan::ConvertShortTS($arParams['TIME_FINISH'], $strDate);
				$arFields['DATE_FINISH'] = ConvertTimeStamp($timestamp, 'FULL');
			}
			else
			{
				$arFields['DATE_FINISH'] = $last_entry['DATE_FINISH'];
			}

			// pause finished
			if ($arParams['TIME_LEAKS'] !== null)
			{
				$arFields['TIME_LEAKS'] = $arParams['TIME_LEAKS'] % 86400;

				$arFields['REPORTS'][] = array(
					'REPORT_TYPE' => 'ERR_DURATION',
					'REPORT' => 'TIME_CHANGE;'.date('c').';Time was changed manually',
				);
				$arFields['REPORTS'][] = array(
					'REPORT_TYPE' => 'REPORT_DURATION',
					'REPORT' => $arParams['REPORT'],
				);
			}

			if(isset($arParams['LAT_CLOSE']))
			{
				$arFields['LAT_CLOSE'] = $arParams['LAT_CLOSE'];
			}

			if(isset($arParams['LON_CLOSE']))
			{
				$arFields['LON_CLOSE'] = $arParams['LON_CLOSE'];
			}

			if ($arSettings['UF_TM_FREE'])
			{
				$arFields['ACTIVE'] = 'Y';
				unset($arFields['REPORTS']);
			}

			if (CTimeManEntry::Update($last_entry['ID'], $arFields))
			{
				if (isset($arFields['ACTIVE']) && $arFields['ACTIVE'] == 'N')
					CTimeManNotify::SendMessage($last_entry['ID']);

				static::clearFullReportCache();

				return $this->_GetLastData(true);
			}
		}
		else
		{
			$GLOBALS['APPLICATION']->ThrowException('WD_NOT_OPEN');
		}

		return false;
	}

	public function OpenDay($timestamp = false, $report = '')
	{
		global $APPLICATION;

		if ($this->OpenAction() !== 'OPEN')
		{
			return false;
		}

		if ($timestamp <= 0)
			$timestamp = false;
		else
		{
			if (
				$timestamp > COption::GetOptionInt('timeman', 'workday_min_finish', 64800)
				&& $_SESSION['TM_LAST_TIME_OPEN'] != $timestamp
			)
			{
				$_SESSION['TM_LAST_TIME_OPEN'] = $timestamp;
				$APPLICATION->ThrowException(str_replace('#TIME#', CTimeMan::FormatTimeOut($timestamp), GetMessage('TM_CONFIRM_LATE_OPEN')), 'REPORT_NEEDED');
				return false;
			}
			unset($_SESSION['TM_LAST_TIME_OPEN']);

			$timestamp = CTimeMan::ConvertShortTS($timestamp - CTimeZone::GetOffset());
		}
		$arFields = array(
			'USER_ID' => $this->USER_ID,
			'DATE_START' => ConvertTimeStamp(($timestamp ? $timestamp : time()) + CTimeZone::GetOffset(), 'FULL'),
//			'DATE_START' => $GLOBALS['DB']->FormatDate(ConvertTimeStamp(($timestamp ? $timestamp : time()) + CTimeZone::GetOffset(), 'FULL'), FORMAT_DATETIME, "DD.MM.YYYY HH:MI:SS"),
		);

		if ($this->isEntryValid('OPEN', $timestamp))
		{
			$arFields['ACTIVE'] = 'Y';
		}
		else
		{
			$arFields['ACTIVE'] = 'N';

			if (strlen($report) > 0)
			{
				$arFields['REPORTS'] = array();
				if ($ex = $APPLICATION->GetException())
				{
					$arFields['REPORTS'][] = array(
						'REPORT_TYPE' => 'ERR_OPEN',
						'REPORT' => $ex->GetId().';'.date('c').';'.$ex->GetString(),
					);
				}

				$arFields['REPORTS'][] = array(
					'REPORT_TYPE' => 'REPORT_OPEN',
					'REPORT' => $report,
				);
			}
			else
			{
				if ($ex = $APPLICATION->GetException())
				{
					$APPLICATION->ThrowException($ex->GetString(), 'REPORT_NEEDED');
				}

				return false;
			}
		}

		if (($last_entry = $this->_GetLastData()) && $last_entry['TASKS'])
		{
			$arTasks = $this->GetTasks($last_entry['TASKS'], true);
			$arFields['TASKS'] = array();
			foreach ($arTasks as $task)
				$arFields['TASKS'][] = $task['ID'];
		}

		$arFields['IP_OPEN'] = $_SERVER['REMOTE_ADDR'];

		$ENTRY_ID = CTimeManEntry::Add($arFields);
		if ($ENTRY_ID > 0)
		{
			CUser::SetLastActivityDate($this->USER_ID);

			$APPLICATION->ResetException();
			unset($_SESSION['BX_TIMEMAN_LAST_PAUSE_'.$this->USER_ID]);

			if (isset($arFields['ACTIVE']) && $arFields['ACTIVE'] == 'N')
				CTimeManNotify::SendMessage($ENTRY_ID);

			static::clearFullReportCache();

			$data = $this->_GetLastData(true);

			$e = GetModuleEvents('timeman', 'OnAfterTMDayStart');
			while ($a = $e->Fetch())
				ExecuteModuleEventEx($a, array($data));

			return $data;
		}

		return false;
	}

	public function CloseDay($timestamp = false, $report = '', $bFieldsOnly = false)
	{
		global $APPLICATION;

		if (($last_entry = $this->_GetLastData(true)) && (!$last_entry['DATE_FINISH'] || $last_entry['PAUSED'] == 'Y'))
		{
			if ($this->OpenAction() === 'REOPEN')
				$last_entry = $this->ReopenDay();
			if ($timestamp <= 0)
			{
				$timestamp = false;
			}
			else
			{
				$ts = CTimeMan::ConvertShortTS(
					$timestamp - CTimeZone::GetOffset(),
					ConvertTimeStamp(MakeTimeStamp($last_entry['DATE_START'], FORMAT_DATETIME) - $last_entry['TIME_START'] + $timestamp - CTimeZone::GetOffset(), 'SHORT')
				);

				$timestamp = $ts;

			}

			if ($this->State() == 'EXPIRED' && !$timestamp)
			{
				$GLOBALS['APPLICATION']->ThrowException('Workday is expired', 'WD_EXPIRED');
				return false;
			}

			$arFields = array(
				'USER_ID' => $this->USER_ID,
				'DATE_START' => $last_entry['DATE_START'],
				'DATE_FINISH' => ConvertTimeStamp(($timestamp ? $timestamp : time()) + CTimeZone::GetOffset(), 'FULL'),
//				'DATE_FINISH' => $GLOBALS['DB']->FormatDate(ConvertTimeStamp(($timestamp ? $timestamp : time()) + CTimeZone::GetOffset(), 'FULL'), FORMAT_DATETIME, "DD.MM.YYYY HH:MI:SS"),
				'PAUSED' => 'N',
			);

			if (!$this->isEntryValid('CLOSE', $timestamp))
			{
				$arFields['ACTIVE'] = 'N';

				if (strlen($report) > 0)
				{
					$arFields['REPORTS'] = array();
					if ($ex = $APPLICATION->GetException())
					{
						$arFields['REPORTS'][] = array(
							'REPORT_TYPE' => 'ERR_CLOSE',
							'REPORT' => $ex->GetId().';'.date('c').';'.$ex->GetString(),
						);
					}

					$arFields['REPORTS'][] = array(
						'REPORT_TYPE' => 'REPORT_CLOSE',
						'REPORT' => $report,
					);
				}
				else
				{
					if ($ex = $APPLICATION->GetException())
					{
						$APPLICATION->ThrowException($ex->GetString(), 'REPORT_NEEDED');
					}

					return false;
				}
			}

			if ($timestamp === false)
				$timestamp = time();

			if ($timestamp + CTimeZone::GetOffset() < MakeTimeStamp($last_entry['DATE_START']))
				return false;

			$arFields['IP_CLOSE'] = $_SERVER['REMOTE_ADDR'];

			if ($bFieldsOnly)
			{
				return $arFields;
			}
			else
			{
				if (CTimeManEntry::Update($last_entry['ID'], $arFields))
				{
					CUser::SetLastActivityDate($this->USER_ID);

					if (isset($arFields['ACTIVE']) && $arFields['ACTIVE'] == 'N')
						CTimeManNotify::SendMessage($last_entry['ID']);

					static::clearFullReportCache();

					$data = $this->_GetLastData(true);

					$e = GetModuleEvents('timeman', 'OnAfterTMDayEnd');
					while ($a = $e->Fetch())
						ExecuteModuleEventEx($a, array($data));

					return $data;
				}
			}
		}
		else
		{
			$GLOBALS['APPLICATION']->ThrowException('WD_NOT_OPEN');
		}

		return false;
	}

	public function ReopenDay($bSkipCheck = false, $site_id = SITE_ID)
	{
		$this->SITE_ID = $site_id;
		if (($last_entry = $this->_GetLastData(true)) && $this->OpenAction($bSkipCheck) === 'REOPEN')
		{
			$arFields = array(
				'DATE_FINISH' => false,
				'TIME_FINISH' => false,
				'DURATION' => 0,
				'PAUSED' => 'N',
				'ACTIVE' => $this->_ReopenGetActivity($last_entry['ID']) ? 'Y' : 'N'
			);

			$ts_finish = MakeTimeStamp($last_entry['DATE_FINISH']) - CTimeZone::GetOffset();
			$leak = time() - $ts_finish;

			if ($leak > BX_TIMEMAN_ALLOWED_TIME_DELTA)
				$arFields['TIME_LEAKS_ADD'] = $leak;

			if (CTimeManEntry::Update($last_entry['ID'], $arFields))
			{
				CUser::SetLastActivityDate($this->USER_ID);

				CTimeManReport::Reopen($last_entry['ID']);
				CTimeManReportDaily::Reopen($last_entry['ID']);

				if ($leak > BX_TIMEMAN_ALLOWED_TIME_DELTA)
				{
					$_SESSION['BX_TIMEMAN_LAST_PAUSE_'.$this->USER_ID] = array(
						'DATE_START' => $ts_finish,
						'DATE_FINISH' => $ts_finish + $leak
					);

					CTimeManReport::Add(array(
						'ENTRY_ID' => $last_entry['ID'],
						'USER_ID' => $last_entry['USER_ID'],
						'ACTIVE' => 'Y',
						'REPORT_TYPE' => 'REOPEN',
						'REPORT' => 'REOPEN;'.date('c').';Entry was reopened.',
					));
				}

				static::clearFullReportCache();

				$data = $this->_GetLastData(true);

				$e = GetModuleEvents('timeman', 'OnAfterTMDayContinue');
				while ($a = $e->Fetch())
					ExecuteModuleEventEx($a, array($data));

				return $data;
			}
		}

		return false;
	}

	public function PauseDay()
	{
		global $APPLICATION;

		if (($last_entry = $this->_GetLastData(true)) && !$last_entry['DATE_FINISH'])
		{
			if (time() + CTimeZone::GetOffset() < MakeTimeStamp($last_entry['DATE_START']))
				return false;

			$arFields = array(
				'USER_ID' => $this->USER_ID,
				'DATE_START' => $last_entry['DATE_START'],
				'DATE_FINISH' => ConvertTimeStamp(time() + CTimeZone::GetOffset(), 'FULL'),
				'IP_CLOSE' => $_SERVER['REMOTE_ADDR'],
				'PAUSED' => 'Y'
			);

			if (CTimeManEntry::Update($last_entry['ID'], $arFields))
			{
				CUser::SetLastActivityDate($this->USER_ID);

				static::clearFullReportCache();

				$data = $this->_GetLastData(true);

				$e = GetModuleEvents('timeman', 'OnAfterTMDayPause');
				while ($a = $e->Fetch())
					ExecuteModuleEventEx($a, array($data));

				return $data;
			}
		}
		else
		{
			$GLOBALS['APPLICATION']->ThrowException('WD_NOT_OPEN');
		}

		return false;

	}

	public function SetReport($report, $report_ts, $entry_id = null)
	{
		global $USER, $APPLICATION;

		if ($last_entry = $this->_GetLastData())
		{
			if ($entry_id && $entry_id != $last_entry['ID'])
				$report_ts = 0;

			$dbRes = CTimeManReport::GetList(array(), array('ENTRY_ID' => $last_entry['ID'], 'REPORT_TYPE' => 'REPORT'));
			if ($arRes = $dbRes->Fetch())
			{
				$ID = $arRes['ID'];

				$current_report_ts = MakeTimeStamp($arRes['TIMESTAMP_X']);
				if ($current_report_ts > $report_ts)
					return $arRes;

				$arFields = array('REPORT' => $report);
				if (!CTimeManReport::Update($ID, $arFields))
				{
					return false;
				}
			}
			else
			{
				$arFields = array(
					'ENTRY_ID' => $last_entry['ID'],
					'USER_ID' => $USER->GetID(), // not $last_entry['USER_ID']!
					'ACTIVE' => 'Y',
					'REPORT_TYPE' => 'REPORT',
					'REPORT' => $report,
				);

				if (!($ID = CTimeManReport::Add($arFields)))
				{
					return false;
				}
			}

			$dbRes = CTimeManReport::GetByID($ID);
			return $dbRes->Fetch();
		}
		else
		{
			$APPLICATION->ThrowException('No entry', 'SAVE_REPORT_NO_ENTRY');
		}
	}

	public function GetCurrentInfo($clear = false)
	{
		return $this->_GetLastData($clear);
	}

	public function State()
	{
		return ($this->isDayExpired()
			? 'EXPIRED'
			: ($this->isDayOpen()
				? 'OPENED'
				: ($this->isDayPaused()
					? 'PAUSED'
					: 'CLOSED'
				)
			)
		);
	}

	public function GetExpiredRecommendedDate()
	{
		$rec_time = COption::GetOptionInt('timeman', 'workday_finish', 18*3600);

		if ($last_entry = $this->_GetLastData())
		{
			$ts_start = CTimeMan::GetTimeTS($last_entry['DATE_START']);
			if ($rec_time < $ts_start)
				$rec_time = $ts_start + intval($last_entry['TIME_LEAKS']) + 300;
		}

		return $rec_time;
	}

	// check if day is currently opened
	public function isDayOpen()
	{
		return (is_array($this->_GetLastData()) && !CTimeManUser::$LAST_ENTRY[$this->USER_ID]['DATE_FINISH']);
	}

	// check if day is paused
	public function isDayPaused()
	{
		return (is_array($this->_GetLastData()) && !$this->isDayOpen() && CTimeManUser::$LAST_ENTRY[$this->USER_ID]['PAUSED'] === 'Y');
	}

	public function getDayStartOffset($entry, $bTs = false)
	{
		$ts_start = $bTs ? $entry['DATE_START'] : (MakeTimeStamp($entry['DATE_START']) - CTimeZone::GetOffset());
		$ts_start_day = MakeTimeStamp(ConvertTimeStamp($ts_start, 'SHORT'));

		$time_start = $ts_start - $ts_start_day;

		return $entry['TIME_START'] - $time_start;
	}

	public function isDayOpenedToday()
	{
		// server time at the moment of day start
		$ts_start = !empty(CTimeManUser::$LAST_ENTRY[$this->USER_ID]['DATE_START']) ? (MakeTimeStamp(CTimeManUser::$LAST_ENTRY[$this->USER_ID]['DATE_START']) - CTimeZone::GetOffset()) : time();
		$ts_start_day = MakeTimeStamp(ConvertTimeStamp($ts_start, 'SHORT'));

		// server time that was at the day start
		$time_start = $ts_start - $ts_start_day;

		// server timezone diff with server that was at the day start
		$timezone_diff = CTimeManUser::$LAST_ENTRY[$this->USER_ID]['TIME_START'] - $time_start;

		// current date with such timezone_diff;
		$t = time();
		$date_current = date('Y-m-d', $t + $timezone_diff);
		$date_current_day = date('Y-m-d', $ts_start_day);

		return $date_current == $date_current_day;
	}

	// check if user forgot to close wd
	public function isDayExpired()
	{
		if (!$this->isDayOpen() && !$this->isDayPaused())
			return false;

		return !$this->isDayOpenedToday();
	}

	public function OpenAction($bSkipCheck = false)
	{
		if ($last_entry = $this->_GetLastData())
		{
			if (!$last_entry['DATE_FINISH'])
				return false;

			if ($last_entry['PAUSED'] === 'Y')
				$bSkipCheck = true;

			if ($this->isDayOpenedToday())
			{
				if (
					$bSkipCheck
					|| COption::GetOptionString('timeman', 'workday_close_undo', 'Y') === 'Y'
				)
				{
					return 'REOPEN';
				}

				return false;
			}
		}

		return 'OPEN';
	}

	public function GetEvents($date)
	{
		$arEvents = array();

		if (CBXFeatures::IsFeatureEnabled('Calendar'))
		{
			$ts = CTimeMan::RemoveHoursTS(MakeTimeStamp($date));

			if ($ts > 0)
			{
				$calendar2 = COption::GetOptionString("intranet", "calendar_2", "N") == "Y" && CModule::IncludeModule('calendar');

				if ($calendar2)
				{
					$arFilter = array(
						'arFilter' => array(
							"OWNER_ID" => $this->USER_ID,
							"FROM_LIMIT" => ConvertTimeStamp($ts, 'FULL'),
							"TO_LIMIT" => ConvertTimeStamp($ts+86399, 'FULL')
						),
						'parseRecursion' => true,
						'userId' => $this->USER_ID,
						'skipDeclined' => true,
						'fetchAttendees' => false,
						'fetchMeetings' => true
					);

					$arNewEvents = CCalendarEvent::GetList($arFilter);
					if (count($arNewEvents) > 0)
					{
						foreach ($arNewEvents as $arEvent)
						{
							if ($arEvent['RRULE'])
							{
								$ts_from = MakeTimeStamp($arEvent['DT_FROM']);
								$ts_to = MakeTimeStamp($arEvent['DT_TO']);

								if ($ts_to < $ts || $ts_from > $ts+86399)
									continue;
							}

							$arEvents[] = array(
								'ID' => $arEvent['ID'],
								'OWNER_ID' => $this->USER_ID,
								'CREATED_BY' => $arEvent['CREATED_BY'],
								'NAME' => $arEvent['NAME'],
								'DETAIL_TEXT' => $arEvent['DESCRIPTION'],
								'DATE_FROM' => $arEvent['DT_FROM'],
								'DATE_TO' => $arEvent['DT_TO'],
								'IMPORTANCE' => $arEvent['IMPORTANCE'],
								'ACCESSIBILITY' => $arEvent['ACCESSIBILITY'],
							);
						}
					}
				}
				else
				{
					$arEvents = CEventCalendar::GetNearestEventsList(
						array(
							'userId' => $this->USER_ID,
							'bCurUserList' => true,
							'fromLimit' => ConvertTimeStamp($ts, 'FULL'),
							'toLimit' => ConvertTimeStamp($ts+86399, 'FULL'),
							'iblockId' => COption::GetOptionInt('intranet', 'iblock_calendar'),
						)
					);

					foreach ($arEvents as $key => $event)
					{
						if ($event['STATUS'] === 'N')
							unset($arEvents[$key]);
					}
				}

				return array_values($arEvents);
			}
		}

		return false;
	}

	public function GetTasks($arIDs = array(), $bOpened = false, $USER_ID = null)
	{
		$res = null;

		if  (!is_array($arIDs) && strlen($arIDs) > 0)
			$arIDs = unserialize($arIDs);

		$arIDs = array_values($arIDs);

		if (!$USER_ID)
			$USER_ID = $this->USER_ID;

		if (CBXFeatures::IsFeatureEnabled('Tasks') && CModule::IncludeModule('tasks'))
		{
			$res = array();
			if (count($arIDs) > 0)
			{
				$arFilter = array('ID' => $arIDs);
				if ($bOpened)
					$arFilter['!STATUS'] = array(4,5,7);

				$dbRes = CTasks::GetList(array(), $arFilter);
				while ($arRes = $dbRes->Fetch())
				{
					$arRes['ACCOMPLICES'] = $arRes['AUDITORS'] = array();
					$rsMembers = CTaskMembers::GetList(
						array(),
						array('TASK_ID' => $arRes['ID'])
						);

					while ($arMember = $rsMembers->Fetch())
					{
						if ($arMember['TYPE'] == 'A')
							$arRes['ACCOMPLICES'][] = $arMember['USER_ID'];
						elseif ($arMember['TYPE'] == 'U')
							$arRes['AUDITORS'][] = $arMember['USER_ID'];
					}

					// Permit only for responsible user, accomplices or auditors
					$isPermited = ( ($arRes['RESPONSIBLE_ID'] == $USER_ID)
						|| in_array($USER_ID, $arRes['ACCOMPLICES'])
						|| in_array($USER_ID, $arRes['AUDITORS'])
						);

					if ( ! $isPermited )
						continue;

					$res[] = array(
						'ID' => $arRes['ID'],
						'PRIORITY' => $arRes['PRIORITY'],
						'STATUS' => $arRes['STATUS'],
						'TITLE' => $arRes['TITLE'],
						'TASK_CONTROL' => $arRes['TASK_CONTROL'],
						'URL' => str_replace(
							array('#USER_ID#', '#TASK_ID#'),
							array($this->USER_ID, $arRes['ID']),
							COption::GetOptionString('intranet', 'path_task_user_entry', '/company/personal/user/#USER_ID#/tasks/task/view/#TASK_ID#/')
						)
					);
				}
			}
		}

		return $res;
	}

	public function TaskStatus($id, $status)
	{
		global $BX_TIMEMAN_TASKS_MIGRATION_RULES;

		$arTasks = $this->GetTasks(array($id));

		if (is_array($arTasks) && count($arTasks) === 1)
		{
			$current_status = $arTasks[0]['STATUS'];
			if ($status === 5)
				$status = 4;

			if (
				is_array($BX_TIMEMAN_TASKS_MIGRATION_RULES[$current_status])
				&& in_array($status, $BX_TIMEMAN_TASKS_MIGRATION_RULES[$current_status])
			)
			{
				if ($status === 4)
				{
					$status = $arTasks[0]['TASK_CONTROL'] == 'Y' ? 4 : 5;
				}

				$obt = new CTasks();
				if ($obt->Update($id, array('STATUS' => $status)))
					return $this->_GetLastData(true);
			}
		}
	}

	public function TaskActions($arActions, $site_id = SITE_ID)
	{
		if (
			($last_entry = $this->_GetLastData())
			//&& $this->isDayOpen()
			//&& !$this->isDayExpired()
		)
		{
			$this->SITE_ID = $site_id;
			$arTasks = $last_entry['TASKS'];

			if (!is_array($arTasks))
				$arTasks = array();

			if (strlen($arActions['name']) > 0)
			{
				$obt = new CTasks();
				if ($ID = $obt->Add(array(
					'RESPONSIBLE_ID' => $this->USER_ID,
					'TITLE' => $arActions['name'],
					'TAGS' => array(),
					'STATUS' => 2,
					'SITE_ID' => $this->SITE_ID
				)))
				{
					if (!is_array($arActions['add']))
						$arActions['add'] = array($ID);
					else
						$arActions['add'][] = $ID;
				}
			}

			if (is_array($arActions['add']))
			{
				foreach ($arActions['add'] as $task_id)
					$arTasks[] = intval($task_id);

				$GLOBALS['BX_TIMEMAN_RECENTLY_ADDED_TASK_ID'] = $task_id;
			}

			$arTasks = array_unique($arTasks);

			if (is_array($arActions['remove']))
			{
				$arActions['remove'] = array_unique($arActions['remove']);

				foreach ($arActions['remove'] as $task_id)
				{
					$task_id = intval($task_id);

					if (false !== ($key = array_search($task_id, $arTasks)))
					{
						unset($arTasks[$key]);
					}
				}
			}

			$arFields = array('TASKS' => array());

			if (count($arTasks) > 0)
			{
				$arCheck = $this->GetTasks($arTasks);
				foreach ($arCheck as $a)
					$arFields['TASKS'][] = $a['ID'];
			}

			if (CTimeManEntry::Update($last_entry['ID'], $arFields))
			{
				return $this->_GetLastData(true);
			}
		}

		return false;
	}

	public function GetSettings($arNeededSettings = null)
	{
		return $this->__GetSettings($arNeededSettings, false);
	}

	public function GetPersonalSettings($arNeededSettings = null)
	{
		$arSettings = $this->__GetSettings($arNeededSettings, true);

		if (isset($arSettings['UF_TIMEMAN']) && $arSettings['UF_TIMEMAN'] !== '')
			$arSettings['UF_TIMEMAN'] = $arSettings['UF_TIMEMAN'] == 'Y';
		if (isset($arSettings['UF_TM_MAX_START']) && $arSettings['UF_TM_MAX_START'] == '0')
			$arSettings['UF_TM_MAX_START'] = '';
		if (isset($arSettings['UF_TM_MIN_FINISH']) && $arSettings['UF_TM_MIN_FINISH'] == '0')
			$arSettings['UF_TM_MIN_FINISH'] = '';
		if (isset($arSettings['UF_TM_MIN_DURATION']) && $arSettings['UF_TM_MIN_DURATION'] == '0')
			$arSettings['UF_TM_MIN_DURATION'] = '';
		if (isset($arSettings['UF_TM_FREE']) && $arSettings['UF_TM_FREE'] !== '')
			$arSettings['UF_TM_FREE'] = $arSettings['UF_TM_FREE'] == 'Y';
		if (isset($arSettings['UF_TM_ALLOWED_DELTA']) && $arSettings['UF_TM_ALLOWED_DELTA'] >= 0)
			$arSettings['UF_TM_ALLOWED_DELTA'] = CTimeMan::MakeShortTS($arSettings['UF_TM_ALLOWED_DELTA']);

		return $arSettings;
	}

	protected function __GetSettings($arNeededSettings, $bPersonal = false)
	{
		global $CACHE_MANAGER;

		$cat = intval($bPersonal);

		if(!is_array($this->SETTINGS[$cat]))
		{
			$this->SETTINGS[$cat] = array();

			$cache_id = 'timeman|structure_settings|u'.$this->USER_ID.'_'.$cat;

			if(CACHED_timeman_settings !== false
				&& $CACHE_MANAGER->Read(
					CACHED_timeman_settings,
					$cache_id,
					"timeman_structure_".COption::GetOptionInt('intranet', 'iblock_structure', false)
				)
			)
			{
				$this->SETTINGS[$cat] = $CACHE_MANAGER->Get($cache_id);
			}
			else
			{
				$this->SETTINGS[$cat] = $bPersonal ? $this->_GetPersonalSettings() : $this->_GetSettings();

				if (CACHED_timeman_settings !== false)
				{
					$CACHE_MANAGER->Set($cache_id, $this->SETTINGS[$cat]);
				}
			}
		}

		$arSettings = $this->SETTINGS[$cat];

		if (is_array($arNeededSettings) && count($arNeededSettings) > 0)
		{
			foreach ($arSettings as $set => $value)
			{
				if (!in_array($set, $arNeededSettings))
					unset($arSettings[$set]);
			}
		}

		return $arSettings;
	}

	protected function _GetPersonalSettings()
	{
		global $USER_FIELD_MANAGER;

		$arPersonalSettings = array();

		$dbRes = CUser::GetByID($this->USER_ID);
		if ($arUser = $dbRes->Fetch())
		{
			$arFields = array('UF_TM_MAX_START', 'UF_TM_MIN_FINISH','UF_REPORT_PERIOD','UF_LAST_REPORT', 'UF_TM_MIN_DURATION', 'UF_TM_REPORT_REQ', 'UF_TM_REPORT_TPL', 'UF_TM_FREE', 'UF_TM_ALLOWED_DELTA', );
			$arPersonalSettings = array(
				'UF_TIMEMAN' => $arUser['UF_TIMEMAN'],
				'UF_TM_MAX_START' => CTimeMan::MakeShortTS($arUser['UF_TM_MAX_START']),
				'UF_TM_MIN_FINISH' => CTimeMan::MakeShortTS($arUser['UF_TM_MIN_FINISH']),
				'UF_TM_MIN_DURATION' => CTimeMan::MakeShortTS($arUser['UF_TM_MIN_DURATION']),
				'UF_TM_REPORT_REQ' => $arUser['UF_TM_REPORT_REQ'],
				'UF_LAST_REPORT_DATE' => $arUser['UF_LAST_REPORT_DATE'],

				'UF_REPORT_PERIOD' => $arUser['UF_REPORT_PERIOD'],
				'UF_TM_REPORT_DATE' => $arUser['UF_TM_REPORT_DATE'],
				'UF_TM_TIME' => $arUser['UF_TM_TIME'],
				'UF_TM_DAY' => $arUser['UF_TM_DAY'],
				'UF_TM_REPORT_TPL' => $arUser['UF_TM_REPORT_TPL'],
				'UF_TM_FREE' => $arUser['UF_TM_FREE'],
				'UF_TM_ALLOWED_DELTA' => $arUser['UF_TM_ALLOWED_DELTA'],
			);

			$this->UF_DEPARTMENT = $arUser['UF_DEPARTMENT'];

			if ($arPersonalSettings['UF_TIMEMAN'] || $arPersonalSettings['UF_TM_REPORT_REQ'] || $arPersonalSettings['UF_TM_FREE'] || $arPersonalSettings['UF_REPORT_PERIOD'] )
			{
				$arAllFields = $USER_FIELD_MANAGER->GetUserFields('USER');

				if ($arPersonalSettings['UF_TIMEMAN'])
				{
					$dbRes = CUserFieldEnum::GetList(array(), array(
						'USER_FIELD_ID' => $arAllFields['UF_TIMEMAN']['ID'],
						'ID' => $arPersonalSettings['UF_TIMEMAN']
					));

					if ($arRes = $dbRes->Fetch())
						$arPersonalSettings['UF_TIMEMAN'] = $arRes['XML_ID'];
				}
				if ($arPersonalSettings['UF_REPORT_PERIOD'])
				{
					$dbRes = CUserFieldEnum::GetList(array(), array(
						'USER_FIELD_ID' => $arAllFields['UF_REPORT_PERIOD']['ID'],
						'ID' => $arPersonalSettings['UF_REPORT_PERIOD']
					));

					if ($arRes = $dbRes->Fetch())
						$arPersonalSettings['UF_REPORT_PERIOD'] = $arRes['XML_ID'];

				}
				if ($arPersonalSettings['UF_TM_REPORT_REQ'])
				{
					$dbRes = CUserFieldEnum::GetList(array(), array(
						'USER_FIELD_ID' => $arAllFields['UF_TM_REPORT_REQ']['ID'],
						'ID' => $arPersonalSettings['UF_TM_REPORT_REQ']
					));

					if ($arRes = $dbRes->Fetch())
						$arPersonalSettings['UF_TM_REPORT_REQ'] = $arRes['XML_ID'];
				}

				if ($arPersonalSettings['UF_TM_FREE'])
				{
					$dbRes = CUserFieldEnum::GetList(array(), array(
						'USER_FIELD_ID' => $arAllFields['UF_TM_FREE']['ID'],
						'ID' => $arPersonalSettings['UF_TM_FREE']
					));

					if ($arRes = $dbRes->Fetch())
						$arPersonalSettings['UF_TM_FREE'] = $arRes['XML_ID'];
				}
			}
		}

		return $arPersonalSettings;
	}

	protected function _GetSettings()
	{
		global $USER_FIELD_MANAGER;

		$arRes = array();

		$arRes = $this->_GetPersonalSettings();
		if ($arRes)
		{
			if ($arRes['UF_TIMEMAN'] === 'N')
			{
				return array('UF_TIMEMAN' => false);
			}

			$cnt = 0;
			if ($arRes['UF_TIMEMAN'] !== 'Y') $cnt++;
			foreach ($arRes as $fld => $value)
			{
				if (!$arRes[$fld] || $arRes[$fld] == '00:00')
					$cnt++;
			}

			if ($cnt > 0)
			{
				if (is_array($this->UF_DEPARTMENT) && count($this->UF_DEPARTMENT) > 0)
				{
					$allSet = array(
						'UF_TIMEMAN' => $arRes['UF_TIMEMAN'] ? $arRes['UF_TIMEMAN'] : false,
						'UF_TM_MAX_START' => 86401,
						'UF_TM_MIN_FINISH' => false,
						'UF_TM_MIN_DURATION' => false,
						'UF_TM_REPORT_REQ' => false,
						'UF_REPORT_PERIOD' => $arRes['UF_REPORT_PERIOD'],
						'UF_TM_REPORT_DATE' => $arRes['UF_TM_REPORT_DATE'],
						'UF_TM_TIME' => $arRes['UF_TM_TIME'],
						'UF_TM_DAY' => $arRes['UF_TM_DAY'],
						'UF_TM_REPORT_TPL' => array(),
						'UF_TM_FREE' => false,
						'UF_TM_ALLOWED_DELTA' => -1,
					);

					foreach ($this->UF_DEPARTMENT as $dpt)
					{
						$dptSet = CTimeMan::GetSectionSettings($dpt);

						if ($allSet['UF_TIMEMAN'] !== 'Y' && $dptSet['UF_TIMEMAN'])
							$allSet['UF_TIMEMAN'] = $dptSet['UF_TIMEMAN'];
						if ($dptSet['UF_TM_MAX_START'])
							$allSet['UF_TM_MAX_START'] = min($dptSet['UF_TM_MAX_START'], $allSet['UF_TM_MAX_START']);

						$allSet['UF_TM_MAX_START'] = min($dptSet['UF_TM_MAX_START'], $allSet['UF_TM_MAX_START']);
						$allSet['UF_TM_MIN_FINISH'] = max($dptSet['UF_TM_MIN_FINISH'], $allSet['UF_TM_MIN_FINISH']);
						$allSet['UF_TM_MIN_DURATION'] = max($dptSet['UF_TM_MIN_DURATION'], $allSet['UF_TM_MIN_DURATION']);

						if ($dptSet['UF_TM_REPORT_REQ'])
							$allSet['UF_TM_REPORT_REQ'] = $dptSet['UF_TM_REPORT_REQ'];

						if ((!is_array($allSet['UF_TM_REPORT_TPL']) || count($allSet['UF_TM_REPORT_TPL']) <= 0) && $dptSet['UF_TM_REPORT_TPL'])
							$allSet['UF_TM_REPORT_TPL'] = $dptSet['UF_TM_REPORT_TPL'];

						if ($dptSet['UF_TM_FREE'])
							$allSet['UF_TM_FREE'] = $dptSet['UF_TM_FREE'];

						if ($dptSet['UF_TM_ALLOWED_DELTA'])
						{
							if ($allSet['UF_TM_ALLOWED_DELTA'] == -1 || $dptSet['UF_TM_ALLOWED_DELTA'] < $allSet['UF_TM_ALLOWED_DELTA'])
								$allSet['UF_TM_ALLOWED_DELTA'] = $dptSet['UF_TM_ALLOWED_DELTA'];
						}
					}

					//report fields
					$allSet["UF_REPORT_PERIOD"] = (!$allSet["UF_REPORT_PERIOD"] && $dptSet["UF_REPORT_PERIOD"])?$dptSet["UF_REPORT_PERIOD"]:$allSet["UF_REPORT_PERIOD"];
					$allSet["UF_TM_TIME"] = (!$allSet["UF_TM_TIME"] && $dptSet["UF_TM_TIME"])?$dptSet["UF_TM_TIME"]:$allSet["UF_TM_TIME"];
					$allSet["UF_TM_DAY"] = (!$allSet["UF_TM_DAY"] && $dptSet["UF_TM_DAY"])?$dptSet["UF_TM_DAY"]:$allSet["UF_TM_DAY"];
					$allSet["UF_TM_REPORT_DATE"] = (!$allSet["UF_TM_REPORT_DATE"] && $dptSet["UF_TM_REPORT_DATE"])?$dptSet["UF_TM_REPORT_DATE"]:$allSet["UF_TM_REPORT_DATE"];

					if ($arRes['UF_TM_ALLOWED_DELTA'] === '0')
						unset($allSet['UF_TM_ALLOWED_DELTA']);
					foreach  ($allSet as $key => $value)
					{
						if (!$arRes[$key] || $arRes[$key] === '00:00')
							$arRes[$key] = $value;
					}

					if ($arRes['UF_TIMEMAN'] === 'N')
						return ($arRes = array('UF_TIMEMAN' => false));
				}
				elseif ($arRes['UF_TIMEMAN'] !== 'Y')
				{
// if user is not attached to company structure tm can be allowed only in his own profile
					return ($arRes = array('UF_TIMEMAN' => false));
				}
			} //if ($cnt > 0)

			$arRes['UF_TIMEMAN'] = true; // it can be only Y|null at this moment
			$arRes['UF_TM_MAX_START'] = $arRes['UF_TM_MAX_START'];
			$arRes['UF_TM_MAX_START'] = $arRes['UF_TM_MAX_START'] > 0
				? $arRes['UF_TM_MAX_START']
				: COption::GetOptionInt('timeman', 'workday_max_start', 33300);
			$arRes['UF_TM_MIN_FINISH'] = $arRes['UF_TM_MIN_FINISH'];
			$arRes['UF_TM_MIN_FINISH'] = $arRes['UF_TM_MIN_FINISH'] > 0
				? $arRes['UF_TM_MIN_FINISH']
				: COption::GetOptionInt('timeman', 'workday_min_finish', 63900);
			$arRes['UF_TM_MIN_DURATION'] = $arRes['UF_TM_MIN_DURATION'];
			$arRes['UF_TM_MIN_DURATION'] = $arRes['UF_TM_MIN_DURATION'] > 0
				? $arRes['UF_TM_MIN_DURATION']
				: COption::GetOptionInt('timeman', 'workday_min_duration', 28800);
			$arRes['UF_TM_REPORT_REQ'] = $arRes['UF_TM_REPORT_REQ']
				? $arRes['UF_TM_REPORT_REQ']
				: COption::GetOptionString('timeman', 'workday_report_required', 'A');
			$arRes['UF_TM_REPORT_TPL'] = $arRes['UF_TM_REPORT_TPL']
				? $arRes['UF_TM_REPORT_TPL']
				: array();
			$arRes['UF_TM_FREE'] = $arRes['UF_TM_FREE']
				? $arRes['UF_TM_FREE'] == 'Y'
				: false;
			$arRes['UF_TM_ALLOWED_DELTA'] = $arRes['UF_TM_ALLOWED_DELTA'] > -1
				? $arRes['UF_TM_ALLOWED_DELTA']
				: COption::GetOptionInt('timeman', 'workday_allowed_delta', '900');
		}
		else
		{
			return array('UF_TIMEMAN' => false);
		}

		return $arRes;
	}

	public function ClearCache()
	{
		return $this->_GetLastData(true);
	}

	protected function clearFullReportCache()
	{
		global $CACHE_MANAGER;

		$cacheId = CUserReportFull::getInfoCacheId($this->USER_ID);
		$CACHE_MANAGER->Clean($cacheId, 'timeman_report_info');
	}

	public function isSocservEnabledByUser()
	{
		return CUserOptions::GetOption("socialservices", "user_socserv_enable", "N", $this->USER_ID) == 'Y';
	}

	protected function _cacheId()
	{
		if ($this->CACHE_ID)
			return $this->CACHE_ID;
		else
			return ($this->CACHE_ID = 'TIMEMAN_USER_'.$this->USER_ID.'|'.FORMAT_DATETIME);
	}

	protected function _GetLastData($clear = false)
	{
		global $CACHE_MANAGER;

		if ($clear)
		{
			CTimeManUser::$LAST_ENTRY[$this->USER_ID] = CTimeManEntry::GetLast($this->USER_ID);
			$CACHE_MANAGER->Clean($this->_cacheId(), 'b_timeman_entries');
		}
		else if (!CTimeManUser::$LAST_ENTRY[$this->USER_ID])
		{
			if ($CACHE_MANAGER->Read(86400, $this->_cacheId(), 'b_timeman_entries'))
			{
				$DATA = $CACHE_MANAGER->Get($this->_cacheId());
			}
			else
			{
				$DATA = CTimeManEntry::GetLast($this->USER_ID);
				$CACHE_MANAGER->Set($this->_cacheId(), $DATA);
			}

			CTimeManUser::$LAST_ENTRY[$this->USER_ID] = $DATA;
		}

		if (!empty(CTimeManUser::$LAST_ENTRY[$this->USER_ID]) && isset($_SESSION['BX_TIMEMAN_LAST_PAUSE_'.$this->USER_ID]))
		{
			CTimeManUser::$LAST_ENTRY[$this->USER_ID]['LAST_PAUSE'] = $_SESSION['BX_TIMEMAN_LAST_PAUSE_'.$this->USER_ID];
			// CTimeManUser::$LAST_ENTRY['LAST_PAUSE']['DATE_START'] += CTimeZone::GetOffset();
			// CTimeManUser::$LAST_ENTRY['LAST_PAUSE']['DATE_FINISH'] += CTimeZone::GetOffset();
		}
		else
		{
			unset(CTimeManUser::$LAST_ENTRY[$this->USER_ID]['LAST_PAUSE']);
		}

		return CTimeManUser::$LAST_ENTRY[$this->USER_ID];
	}

	protected function _ReopenGetActivity($entry_id)
	{
		$dbRes = CTimeManReport::GetList(
			array('ID' => 'ASC'),
			array('ENTRY_ID' => $entry_id, 'REPORT_TYPE' => 'ERR_OPEN', 'ACTIVE' => 'Y')
		);

		if ($arRes = $dbRes->Fetch())
			return false;
		else
			return true;
	}
}
?>