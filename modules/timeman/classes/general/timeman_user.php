<?php

use Bitrix\Main\Context;
use Bitrix\Main\ORM\Fields\FieldTypeMask;
use Bitrix\Main\ORM\Objectify\Values;
use Bitrix\Main\Web\Cookie;
use Bitrix\Timeman\Form\Worktime\WorktimeRecordForm;
use Bitrix\Timeman\Helper\TimeHelper;
use Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord;
use Bitrix\Timeman\Model\Worktime\Record\WorktimeRecordTable;
use Bitrix\Timeman\Service\DependencyManager;
use Bitrix\Timeman\UseCase\Worktime\Manage;
use Bitrix\Timeman\Service\Worktime\Result\WorktimeServiceResult;

class CTimeManUser
{
	public $SITE_ID = SITE_ID;

	public $CACHE_ID = null;

	protected $USER_ID;
	protected $ABSENCE_TYPE_ID = null;
	protected $SETTINGS = null;
	protected $bTasksEnabled;
	protected $UF_DEPARTMENT;

	protected static $instance = null;
	protected static $LAST_ENTRY = [];

	private static array $currentRecordStatus = [];

	public static function instance()
	{
		if (!self::$instance)
		{
			self::$instance = new CTimeManUser();
		}

		return self::$instance;
	}

	public function __construct($USER_ID = 0, $site_id = SITE_ID)
	{
		global $USER;

		$this->USER_ID = (is_numeric($USER_ID) && $USER_ID > 0) ? $USER_ID : (int)$USER?->GetID();
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
			if (abs(time() - $timestamp) > $arSettings['UF_TM_ALLOWED_DELTA'])
			{
				$APPLICATION->ThrowException('Time was changed manually', 'TIME_CHANGE');
				return false;
			}
		}

		return true;
	}

	/**
	 * @param null $eventName
	 * @return WorktimeRecordForm
	 */
	private function createWorktimeRecordForm($eventName = null)
	{
		$recordForm = WorktimeRecordForm::createWithEventForm($eventName);
		$recordForm->userId = $this->USER_ID;

		return $recordForm;
	}

	private function buildEditForm($arParams)
	{
		$recordForm = WorktimeRecordForm::createWithEventForm();
		$recordForm->editedBy = $this->USER_ID;
		$recordForm->userId = $this->USER_ID;
		$recordForm->recordedStartSeconds = $arParams['TIME_START'];
		$recordForm->recordedStartDateFormatted = $arParams['DATE_START'];
		$recordForm->recordedStopSeconds = $arParams['TIME_FINISH'];
		$recordForm->recordedStopDateFormatted = $arParams['DATE_FINISH'];
		$recordForm->recordedBreakLength = $arParams['TIME_LEAKS'];
		$recordForm->device = $arParams['DEVICE'];
		$recordForm->getFirstEventForm()->reason = $arParams['REPORT'];
		if (isset($arParams['LAT_CLOSE']))
		{
			$recordForm->latitudeClose = $arParams['LAT_CLOSE'];
		}
		if (isset($arParams['LON_CLOSE']))
		{
			$recordForm->longitudeClose = $arParams['LAT_CLOSE'];
		}
		$recordForm->ipClose = $_SERVER['REMOTE_ADDR'];
		return $recordForm;
	}

	public function editDay($arParams)
	{
		global $APPLICATION;

		$recordForm = $this->buildEditForm($arParams);

		if (!empty($arParams['RECORD_ID']))
		{
			$recordForm->id = (int)$arParams['RECORD_ID'];
		}

		if ($recordForm->validate())
		{
			$result = (new Manage\Edit\Handler())->handle($recordForm);
			if ($result->isSuccess())
			{
				static::clearFullReportCache();
				return WorktimeRecordTable::convertFieldsCompatible($result->getWorktimeRecord()->collectValues());
			}
			if (!empty($result->getErrors()) && $result->getErrors()[0]->getCode() === WorktimeServiceResult::ERROR_FOR_USER)
			{
				$APPLICATION->ThrowException($result->getErrors()[0]->getMessage(), 'ALERT_WARNING');
			}
			else
			{
				$APPLICATION->ThrowException($result->getErrorMessages()[0]);
			}
			return false;
		}
		if ($recordForm->getFirstError()->getCode() === 'recordedBreakLength')
		{
			$APPLICATION->ThrowException($recordForm->getFirstError()->getMessage(), 'ALERT_WARNING');
		}
		else
		{
			$APPLICATION->ThrowException($recordForm->getFirstError()->getMessage());
		}
		return false;
	}

	private function buildStartForm($timestamp, $report, $extraInformation)
	{
		$recordForm = $this->createWorktimeRecordForm();
		$recordForm->recordedStartSeconds = $timestamp > 0 ? $timestamp : null;
		$recordForm->getFirstEventForm()->reason = $report;
		$recordForm->latitudeOpen = $extraInformation['LAT_OPEN'] ?? null;
		$recordForm->longitudeOpen = $extraInformation['LON_OPEN'] ?? null;
		$recordForm->ipOpen = $extraInformation['IP_OPEN'] ?? null;
		$recordForm->device = $extraInformation['DEVICE'] ?? null;
		$recordForm->recordedStartDateFormatted = $extraInformation['CUSTOM_DATE'] ?? null;

		if (($lastEntry = $this->_getLastData()) && $lastEntry['TASKS'])
		{
			$arTasks = $this->getTasks($lastEntry['TASKS'], true);
			foreach ($arTasks as $task)
			{
				$recordForm->tasks[] = $task['ID'];
			}
		}
		return $recordForm;
	}

	public function openDay($timestamp = false, $report = '', $extraInformation = [])
	{
		global $APPLICATION;

		$recordForm = $this->buildStartForm($timestamp, $report, $extraInformation);

		if (!empty($extraInformation['RECORD_ID']))
		{
			$recordForm->id = (int)$extraInformation['RECORD_ID'];
		}

		if ($recordForm->validate())
		{
			$result = (new Manage\Start\Handler())->handle($recordForm);

			if ($result->isSuccess())
			{
				CUser::setLastActivityDate($this->USER_ID);
				$APPLICATION->resetException();
				$this->deleteLastPauseInfo($this->USER_ID);
				static::clearFullReportCache();

				$data = WorktimeRecordTable::convertFieldsCompatible($result->getWorktimeRecord()->collectValues());

				$e = GetModuleEvents('timeman', 'OnAfterTMDayStart');
				while ($a = $e->Fetch())
				{
					ExecuteModuleEventEx($a, [$data]);
				}

				return $data;
			}
			if ($result->getErrors()[0]->getCode() === WorktimeServiceResult::ERROR_FOR_USER)
			{
				$APPLICATION->ThrowException($result->getErrors()[0]->getMessage(), 'ALERT_WARNING');
			}
			else
			{
				$APPLICATION->ThrowException($result->getErrorMessages()[0]);
			}
			return false;
		}
		$APPLICATION->ThrowException($recordForm->getFirstError()->getMessage());
		return false;
	}

	private function deleteLastPauseInfo(int $userId)
	{
		$response = Context::getCurrent()->getResponse();
		$cookie = new Cookie('TIMEMAN_LAST_PAUSE_'.$userId, '', time() - 24 * 3600);
		$response->addCookie($cookie);
	}

	private function buildStopForm($timestamp, $report, $extraInformation)
	{
		$recordForm = $this->createWorktimeRecordForm();
		$recordForm->recordedStopSeconds = $timestamp > 0 ? $timestamp : null;
		$recordForm->getFirstEventForm()->reason = $report;
		$recordForm->latitudeClose = $extraInformation['LAT_CLOSE'] ?? null;
		$recordForm->longitudeClose = $extraInformation['LON_CLOSE'] ?? null;
		$recordForm->ipClose = $_SERVER['REMOTE_ADDR'];
		$recordForm->device = $extraInformation['DEVICE'] ?? null;
		$recordForm->recordedStopDateFormatted = $extraInformation['CUSTOM_DATE'] ?? null;
		return $recordForm;
	}

	public function closeDay($timestamp = false, $report = '', $bFieldsOnly = false, $extraInformation = [])
	{
		global $APPLICATION;
		if ($this->State() == 'EXPIRED' && !$timestamp)
		{
			$GLOBALS['APPLICATION']->ThrowException('Workday is expired', 'WD_EXPIRED');
			return false;
		}
		$recordForm = $this->buildStopForm($timestamp, $report, $extraInformation);

		if (!empty($extraInformation['RECORD_ID']))
		{
			$recordForm->id = (int)$extraInformation['RECORD_ID'];
		}

		if ($this->State() == 'EXPIRED')
		{
			$recordForm->editedBy = $this->USER_ID;
		}
		if ($recordForm->validate())
		{
			$result = (new Manage\Stop\Handler())->handle($recordForm);

			if ($result->isSuccess())
			{
				CUser::SetLastActivityDate($this->USER_ID);
				$recordFields = WorktimeRecordTable::convertFieldsCompatible($result->getWorktimeRecord()->collectRawValues());

				if (isset($arFields['ACTIVE']) && $recordFields['ACTIVE'] == 'N')
				{
					CTimeManNotify::SendMessage($recordFields['ID']);
				}

				static::clearFullReportCache();

				$e = GetModuleEvents('timeman', 'OnAfterTMDayEnd');
				while ($a = $e->Fetch())
				{
					ExecuteModuleEventEx($a, [$recordFields]);
				}

				return $recordFields;
			}
			else
			{
				foreach ($result->getErrors() as $error)
				{
					if ($error->getCode() === WorktimeServiceResult::ERROR_REASON_NEEDED
						|| $error->getCode() === WorktimeServiceResult::ERROR_EXPIRED_REASON_NEEDED)
					{
						$APPLICATION->ThrowException($error->getMessage(), 'REPORT_NEEDED');
					}
				}
				if ($result->getErrors()[0]->getCode() === WorktimeServiceResult::ERROR_FOR_USER)
				{
					$APPLICATION->ThrowException($result->getErrors()[0]->getMessage(), 'ALERT_WARNING');
				}
			}
		}
		return false;
	}

	private function buildReopenForm($extraInformation)
	{
		$recordForm = $this->createWorktimeRecordForm();
		$recordForm->device = $extraInformation['DEVICE'];
		return $recordForm;
	}

	public function reopenDay($bSkipCheck = false, $site_id = SITE_ID, $extraInformation = [])
	{
		global $APPLICATION;

		$lastEntry = $this->_GetLastData(true);

		$recordForm = $this->buildReopenForm($extraInformation);

		if (!empty($extraInformation['RECORD_ID']))
		{
			$recordForm->id = (int)$extraInformation['RECORD_ID'];
		}

		if ($recordForm->validate())
		{
			$result = (new Manage\Relaunch\Handler())->handle($recordForm);

			if ($result->isSuccess())
			{
				$ts_finish = MakeTimeStamp($lastEntry['DATE_FINISH']) - CTimeZone::GetOffset();
				$leak = time() - $ts_finish;
				CUser::SetLastActivityDate($this->USER_ID);
				CTimeManReport::Reopen($lastEntry['ID']);
				CTimeManReportDaily::Reopen($lastEntry['ID']);

				$this->setLastPauseInfo($this->USER_ID, $ts_finish, $ts_finish + $leak);

				$report = \Bitrix\Timeman\Model\Worktime\Report\WorktimeReport::createReopenReport(
					$lastEntry['USER_ID'],
					$lastEntry['ID']
				);
				$report->save();

				static::clearFullReportCache();

				$data = WorktimeRecordTable::convertFieldsCompatible($result->getWorktimeRecord()->collectValues());

				$e = GetModuleEvents('timeman', 'OnAfterTMDayContinue');
				while ($a = $e->Fetch())
				{
					ExecuteModuleEventEx($a, [$data]);
				}

				return $data;
			}
			if ($result->getErrors()[0]->getCode() === WorktimeServiceResult::ERROR_FOR_USER)
			{
				$APPLICATION->ThrowException($result->getErrors()[0]->getMessage(), 'ALERT_WARNING');
			}
		}

		return false;
	}

	private function getLastPauseInfo(int $userId): array
	{
		$lastPause = Context::getCurrent()->getRequest()->getCookie('TIMEMAN_LAST_PAUSE_'.$userId);
		if ($lastPause)
		{
			$explode = explode('|', $lastPause);
			return [
				'DATE_START' => (int) $explode[0],
				'DATE_FINISH' => (int) $explode[1],
			];
		}
		else
		{
			return [];
		}
	}

	private function setLastPauseInfo(int $userId, int $dateStart, int $dateFinish): void
	{
		$lastPause = $dateStart.'|'.$dateFinish;
		$cookie = new Cookie('TIMEMAN_LAST_PAUSE_'.$userId, $lastPause, 0);
		Context::getCurrent()->getResponse()->addCookie($cookie);
	}

	private function buildPauseForm($extraInformation)
	{
		$recordForm = $this->createWorktimeRecordForm();
		$recordForm->latitudeClose = empty($extraInformation['LAT_CLOSE']) ? null : $extraInformation['LAT_CLOSE'];
		$recordForm->longitudeClose = empty($extraInformation['LON_CLOSE']) ? null : $extraInformation['LON_CLOSE'];
		$recordForm->ipClose = empty($extraInformation['IP_CLOSE']) ? null : $extraInformation['IP_CLOSE'];
		$recordForm->device = $extraInformation['DEVICE'];
		return $recordForm;
	}

	public function pauseDay($extraInformation = [])
	{
		global $APPLICATION;

		$recordForm = $this->buildPauseForm($extraInformation);

		if (!empty($extraInformation['RECORD_ID']))
		{
			$recordForm->id = (int)$extraInformation['RECORD_ID'];
		}

		if ($recordForm->validate())
		{
			$result = (new Manage\Pause\Handler())->handle($recordForm);

			if ($result->isSuccess())
			{
				CUser::SetLastActivityDate($this->USER_ID);

				static::clearFullReportCache();

				$data = WorktimeRecordTable::convertFieldsCompatible($result->getWorktimeRecord()->collectValues());

				$e = GetModuleEvents('timeman', 'OnAfterTMDayPause');
				while ($a = $e->Fetch())
				{
					ExecuteModuleEventEx($a, [$data]);
				}

				return $data;
			}
			if ($result->getErrors()[0]->getCode() === WorktimeServiceResult::ERROR_FOR_USER)
			{
				$APPLICATION->ThrowException($result->getErrors()[0]->getMessage(), 'ALERT_WARNING');
			}
			else
			{
				$APPLICATION->ThrowException('WD_NOT_OPEN');
			}
		}

		return false;
	}

	public function SetReport($report, $report_ts, $entry_id = null)
	{
		global $USER, $APPLICATION;

		if ($last_entry = $this->_GetLastData())
		{
			if ($entry_id && $entry_id != $last_entry['ID'])
			{
				$report_ts = 0;
			}

			$dbRes = CTimeManReport::GetList([], ['ENTRY_ID' => $last_entry['ID'], 'REPORT_TYPE' => 'REPORT']);
			if ($arRes = $dbRes->Fetch())
			{
				$ID = $arRes['ID'];

				$current_report_ts = MakeTimeStamp($arRes['TIMESTAMP_X']);
				if ($current_report_ts > $report_ts)
				{
					return $arRes;
				}

				$arFields = ['REPORT' => $report];
				if (!CTimeManReport::Update($ID, $arFields))
				{
					return false;
				}
			}
			else
			{
				$arFields = [
					'ENTRY_ID' => $last_entry['ID'],
					'USER_ID' => $USER->GetID(), // not $last_entry['USER_ID']!
					'ACTIVE' => 'Y',
					'REPORT_TYPE' => 'REPORT',
					'REPORT' => $report,
				];

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

	/**
	 * The method returns the status value of the last shift.
	 *
	 * @return string
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getCurrentRecordStatus(): string
	{
		if (isset(self::$currentRecordStatus[$this->USER_ID]))
		{
			return self::$currentRecordStatus[$this->USER_ID];
		}

		$queryObject = WorktimeRecordTable::query()
			->addSelect('CURRENT_STATUS')
			->where('USER_ID', $this->USER_ID)
			->setOrder(['DATE_START' => 'DESC'])
			->setLimit(1)
			->exec()
		;
		if ($data = $queryObject->fetch())
		{
			self::$currentRecordStatus[$this->USER_ID] = $data['CURRENT_STATUS'];
		}
		else
		{
			self::$currentRecordStatus[$this->USER_ID] = 'CLOSED';
		}

		return self::$currentRecordStatus[$this->USER_ID];
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
		$recDefaultSeconds = COption::GetOptionInt('timeman', 'workday_finish', 18 * 3600);

		if ($lastEntry = $this->_GetLastData())
		{
			$schedule = DependencyManager::getInstance()
				->getScheduleProvider()
				->getScheduleWithShifts($lastEntry['SCHEDULE_ID']);
			$shift = null;

			if ($schedule && $lastEntry['SHIFT_ID'] > 0)
			{
				$shift = $schedule->obtainShiftByPrimary($lastEntry['SHIFT_ID']);
			}
			$manager = DependencyManager::getInstance()
				->buildWorktimeRecordManager(
					WorktimeRecord::wakeUpRecord($lastEntry),
					$schedule,
					$shift
				);
			$recommendedTimestamp = $manager->getRecommendedStopTimestamp();
			if ($recommendedTimestamp > 0)
			{
				return TimeHelper::getInstance()->convertUtcTimestampToDaySeconds(
					$recommendedTimestamp,
					TimeHelper::getInstance()->getUserTimezone($lastEntry['USER_ID'])
				);
			}
		}

		return $recDefaultSeconds;
	}

	// check if day is currently opened
	public function isDayOpen()
	{
		return WorktimeRecord::isRecordOpened($this->_GetLastData());
	}

	// check if day is paused
	public function isDayPaused()
	{
		return WorktimeRecord::isRecordPaused($this->_GetLastData());
	}

	public function getDayStartOffset($entry, $bTs = false)
	{
		if (!is_array($entry))
		{
			return 0;
		}

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
		$recordData = $this->_GetLastData(true);
		if (!$recordData)
		{
			return false;
		}
		$schedule = DependencyManager::getInstance()
			->getScheduleProvider()
			->getScheduleWithShifts($recordData['SCHEDULE_ID']);
		$shift = null;

		if ($schedule && $recordData['SHIFT_ID'] > 0)
		{
			$shift = $schedule->obtainShiftByPrimary($recordData['SHIFT_ID']);
		}
		$manager = DependencyManager::getInstance()
			->buildWorktimeRecordManager(
				WorktimeRecord::wakeUpRecord($recordData),
				$schedule,
				$shift
			);
		return $manager->isRecordExpired();
	}

	/**
	 * @deprecated
	 */
	public function OpenAction($bSkipCheck = false)
	{
		$list = \Bitrix\Timeman\Service\DependencyManager::getInstance()
			->getWorktimeActionList();
		$actionList = $list->buildPossibleActionsListForUser($this->USER_ID);

		if (empty($actionList->getStartActions()) && empty($actionList->getReopenActions()) && empty($actionList->getContinueActions()))
		{
			return false;
		}
		if (!empty($actionList->getReopenActions()) && !empty($actionList->getStartActions()))
		{
			return 'OPEN'; // as main action
		}
		if (!empty($actionList->getReopenActions()) || !empty($actionList->getContinueActions()))
		{
			return 'REOPEN';
		}
		return 'OPEN';
	}

	public function GetEvents($date)
	{
		$arEvents = [];

		if (CBXFeatures::IsFeatureEnabled('Calendar'))
		{
			$ts = CTimeMan::RemoveHoursTS(MakeTimeStamp($date));

			if ($ts > 0)
			{
				$calendar2 = COption::GetOptionString("intranet", "calendar_2", "N") == "Y" && CModule::IncludeModule('calendar');

				if ($calendar2)
				{
					$arFilter = [
						'arFilter' => [
							"OWNER_ID" => $this->USER_ID,
							"FROM_LIMIT" => ConvertTimeStamp($ts, 'FULL'),
							"TO_LIMIT" => ConvertTimeStamp($ts + 86399, 'FULL'),
						],
						'parseRecursion' => true,
						'userId' => $this->USER_ID,
						'skipDeclined' => true,
						'fetchAttendees' => false,
						'fetchMeetings' => true,
					];

					$arNewEvents = CCalendarEvent::GetList($arFilter);
					if (count($arNewEvents) > 0)
					{
						foreach ($arNewEvents as $arEvent)
						{
							if ($arEvent['RRULE'])
							{
								$ts_from = MakeTimeStamp($arEvent['DT_FROM']);
								$ts_to = MakeTimeStamp($arEvent['DT_TO']);

								if ($ts_to < $ts || $ts_from > $ts + 86399)
								{
									continue;
								}
							}

							$arEvents[] = [
								'ID' => $arEvent['ID'],
								'OWNER_ID' => $this->USER_ID,
								'CREATED_BY' => $arEvent['CREATED_BY'],
								'NAME' => $arEvent['NAME'],
								'DETAIL_TEXT' => $arEvent['DESCRIPTION'],
								'DATE_FROM' => $arEvent['DT_FROM'],
								'DATE_TO' => $arEvent['DT_TO'],
								'IMPORTANCE' => $arEvent['IMPORTANCE'],
								'ACCESSIBILITY' => $arEvent['ACCESSIBILITY'],
							];
						}
					}
				}
				else
				{
					$arEvents = CEventCalendar::GetNearestEventsList(
						[
							'userId' => $this->USER_ID,
							'bCurUserList' => true,
							'fromLimit' => ConvertTimeStamp($ts, 'FULL'),
							'toLimit' => ConvertTimeStamp($ts + 86399, 'FULL'),
							'iblockId' => COption::GetOptionInt('intranet', 'iblock_calendar'),
						]
					);

					foreach ($arEvents as $key => $event)
					{
						if ($event['STATUS'] === 'N')
						{
							unset($arEvents[$key]);
						}
					}
				}

				return array_values($arEvents);
			}
		}

		return false;
	}

	public function GetTasks($arIDs = [], $bOpened = false, $USER_ID = null)
	{
		$res = null;

		if (!is_array($arIDs) && $arIDs <> '')
		{
			$arIDs = unserialize($arIDs, ['allowed_classes' => false]);
		}

		$arIDs = array_values($arIDs);

		if (!$USER_ID)
		{
			$USER_ID = $this->USER_ID;
		}

		if (CBXFeatures::IsFeatureEnabled('Tasks') && CModule::IncludeModule('tasks'))
		{
			$res = [];
			if (count($arIDs) > 0)
			{
				$arFilter = ['ID' => $arIDs];
				if ($bOpened)
				{
					$arFilter['!STATUS'] = [4, 5, 7];
				}

				$dbRes = CTasks::GetList([], $arFilter);
				while ($arRes = $dbRes->Fetch())
				{
					$arRes['ACCOMPLICES'] = $arRes['AUDITORS'] = [];
					$rsMembers = CTaskMembers::GetList(
						[],
						['TASK_ID' => $arRes['ID']]
					);

					while ($arMember = $rsMembers->Fetch())
					{
						if ($arMember['TYPE'] == 'A')
						{
							$arRes['ACCOMPLICES'][] = $arMember['USER_ID'];
						}
						elseif ($arMember['TYPE'] == 'U')
						{
							$arRes['AUDITORS'][] = $arMember['USER_ID'];
						}
					}

					// Permit only for responsible user, accomplices or auditors
					$isPermited = (($arRes['RESPONSIBLE_ID'] == $USER_ID)
								   || in_array($USER_ID, $arRes['ACCOMPLICES'])
								   || in_array($USER_ID, $arRes['AUDITORS'])
					);

					if (!$isPermited)
					{
						continue;
					}

					$res[] = [
						'ID' => $arRes['ID'],
						'PRIORITY' => $arRes['PRIORITY'],
						'STATUS' => $arRes['STATUS'],
						'TITLE' => \Bitrix\Main\Text\Emoji::decode($arRes['TITLE']),
						'TASK_CONTROL' => $arRes['TASK_CONTROL'],
						'URL' => str_replace(
							['#USER_ID#', '#TASK_ID#'],
							[$this->USER_ID, $arRes['ID']],
							COption::GetOptionString('intranet', 'path_task_user_entry', '/company/personal/user/#USER_ID#/tasks/task/view/#TASK_ID#/')
						),
					];
				}
			}
		}

		return $res;
	}

	public function TaskStatus($id, $status)
	{
		global $BX_TIMEMAN_TASKS_MIGRATION_RULES;

		$arTasks = $this->GetTasks([$id]);

		if (is_array($arTasks) && count($arTasks) === 1)
		{
			$current_status = $arTasks[0]['STATUS'];
			if ($status === 5)
			{
				$status = 4;
			}

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
				if ($obt->Update($id, ['STATUS' => $status]))
				{
					return $this->_GetLastData(true);
				}
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
			{
				$arTasks = [];
			}

			if ($arActions['name'] <> '')
			{
				$obt = new CTasks();
				if ($ID = $obt->Add([
					'RESPONSIBLE_ID' => $this->USER_ID,
					'TITLE' => $arActions['name'],
					'TAGS' => [],
					'STATUS' => 2,
					'SITE_ID' => $this->SITE_ID,
				]))
				{
					if (!is_array($arActions['add']))
					{
						$arActions['add'] = [$ID];
					}
					else
					{
						$arActions['add'][] = $ID;
					}
				}
			}

			if (is_array($arActions['add']))
			{
				foreach ($arActions['add'] as $task_id)
				{
					$arTasks[] = intval($task_id);
				}

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

			$arFields = ['TASKS' => []];

			if (count($arTasks) > 0)
			{
				$arCheck = $this->GetTasks($arTasks);
				foreach ($arCheck as $a)
				{
					$arFields['TASKS'][] = $a['ID'];
				}
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
		{
			$arSettings['UF_TIMEMAN'] = $arSettings['UF_TIMEMAN'] == 'Y';
		}
		if (isset($arSettings['UF_TM_MAX_START']) && $arSettings['UF_TM_MAX_START'] == '0')
		{
			$arSettings['UF_TM_MAX_START'] = '';
		}
		if (isset($arSettings['UF_TM_MIN_FINISH']) && $arSettings['UF_TM_MIN_FINISH'] == '0')
		{
			$arSettings['UF_TM_MIN_FINISH'] = '';
		}
		if (isset($arSettings['UF_TM_MIN_DURATION']) && $arSettings['UF_TM_MIN_DURATION'] == '0')
		{
			$arSettings['UF_TM_MIN_DURATION'] = '';
		}
		if (isset($arSettings['UF_TM_FREE']) && $arSettings['UF_TM_FREE'] !== '')
		{
			$arSettings['UF_TM_FREE'] = $arSettings['UF_TM_FREE'] == 'Y';
		}
		if (isset($arSettings['UF_TM_ALLOWED_DELTA']) && $arSettings['UF_TM_ALLOWED_DELTA'] >= 0)
		{
			$arSettings['UF_TM_ALLOWED_DELTA'] = CTimeMan::MakeShortTS($arSettings['UF_TM_ALLOWED_DELTA']);
		}

		return $arSettings;
	}

	protected function __GetSettings($arNeededSettings, $bPersonal = false)
	{
		global $CACHE_MANAGER;

		$cat = intval($bPersonal);

		if (!isset($this->SETTINGS[$cat]) || !is_array($this->SETTINGS[$cat]))
		{
			$this->SETTINGS[$cat] = [];

			$cache_id = 'timeman|structure_settings|u' . $this->USER_ID . '_' . $cat;

			if (CACHED_timeman_settings !== false
				&& $CACHE_MANAGER->Read(
					CACHED_timeman_settings,
					$cache_id,
					"timeman_structure_" . COption::GetOptionInt('intranet', 'iblock_structure', false)
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
		if (is_null($arNeededSettings) ||
			(is_array($arNeededSettings) && array_key_exists('UF_TM_FREE', $arNeededSettings)))
		{
			$this->SETTINGS[$cat]['UF_TM_FREE'] = false;
			$userSchedules = \Bitrix\Timeman\Service\DependencyManager::getInstance()
				->getScheduleProvider()
				->findSchedulesByUserId($this->USER_ID, ['select' => ['ID', 'SCHEDULE_TYPE',]]);
			foreach ($userSchedules as $userSchedule)
			{
				if ($userSchedule->isFlextime())
				{
					$this->SETTINGS[$cat]['UF_TM_FREE'] = true;
					break;
				}
			}
		}

		$arSettings = $this->SETTINGS[$cat];

		if (is_array($arNeededSettings) && count($arNeededSettings) > 0)
		{
			foreach ($arSettings as $set => $value)
			{
				if (!in_array($set, $arNeededSettings))
				{
					unset($arSettings[$set]);
				}
			}
		}

		return $arSettings;
	}

	protected function _GetPersonalSettings()
	{
		global $USER_FIELD_MANAGER;

		$arPersonalSettings = [];

		$dbRes = CUser::GetByID($this->USER_ID);
		if ($arUser = $dbRes->Fetch())
		{
			$arPersonalSettings = [
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
				'UF_DELAY_TIME' => $arUser['UF_DELAY_TIME'],
				'UF_TM_REPORT_TPL' => $arUser['UF_TM_REPORT_TPL'],
				'UF_TM_ALLOWED_DELTA' => $arUser['UF_TM_ALLOWED_DELTA'],
			];

			$this->UF_DEPARTMENT = $arUser['UF_DEPARTMENT'];

			if ($arPersonalSettings['UF_TIMEMAN'] || $arPersonalSettings['UF_TM_REPORT_REQ'] || $arPersonalSettings['UF_REPORT_PERIOD'])
			{
				$arAllFields = $USER_FIELD_MANAGER->GetUserFields('USER');

				if ($arPersonalSettings['UF_TIMEMAN'])
				{
					$dbRes = CUserFieldEnum::GetList([], [
						'USER_FIELD_ID' => $arAllFields['UF_TIMEMAN']['ID'],
						'ID' => $arPersonalSettings['UF_TIMEMAN'],
					]);

					if ($arRes = $dbRes->Fetch())
					{
						$arPersonalSettings['UF_TIMEMAN'] = $arRes['XML_ID'];
					}
				}
				if ($arPersonalSettings['UF_REPORT_PERIOD'])
				{
					$dbRes = CUserFieldEnum::GetList([], [
						'USER_FIELD_ID' => $arAllFields['UF_REPORT_PERIOD']['ID'],
						'ID' => $arPersonalSettings['UF_REPORT_PERIOD'],
					]);

					if ($arRes = $dbRes->Fetch())
					{
						$arPersonalSettings['UF_REPORT_PERIOD'] = $arRes['XML_ID'];
					}

				}
				if ($arPersonalSettings['UF_TM_REPORT_REQ'])
				{
					$dbRes = CUserFieldEnum::GetList([], [
						'USER_FIELD_ID' => $arAllFields['UF_TM_REPORT_REQ']['ID'],
						'ID' => $arPersonalSettings['UF_TM_REPORT_REQ'],
					]);

					if ($arRes = $dbRes->Fetch())
					{
						$arPersonalSettings['UF_TM_REPORT_REQ'] = $arRes['XML_ID'];
					}
				}
			}
		}

		return $arPersonalSettings;
	}

	protected function _GetSettings()
	{
		global $USER_FIELD_MANAGER;

		$arRes = [];

		$arRes = $this->_GetPersonalSettings();
		if ($arRes)
		{
			if ($arRes['UF_TIMEMAN'] === 'N')
			{
				return ['UF_TIMEMAN' => false];
			}

			$cnt = 0;
			if ($arRes['UF_TIMEMAN'] !== 'Y')
			{
				$cnt++;
			}
			foreach ($arRes as $fld => $value)
			{
				if (!$arRes[$fld] || $arRes[$fld] == '00:00')
				{
					$cnt++;
				}
			}

			if ($cnt > 0)
			{
				if (is_array($this->UF_DEPARTMENT) && count($this->UF_DEPARTMENT) > 0)
				{
					$allSet = [
						'UF_TIMEMAN' => $arRes['UF_TIMEMAN'] ? $arRes['UF_TIMEMAN'] : false,
						'UF_TM_MAX_START' => 86401,
						'UF_TM_MIN_FINISH' => false,
						'UF_TM_MIN_DURATION' => false,
						'UF_TM_REPORT_REQ' => false,
						'UF_REPORT_PERIOD' => $arRes['UF_REPORT_PERIOD'],
						'UF_TM_REPORT_DATE' => $arRes['UF_TM_REPORT_DATE'],
						'UF_TM_TIME' => $arRes['UF_TM_TIME'],
						'UF_TM_DAY' => $arRes['UF_TM_DAY'],
						'UF_TM_REPORT_TPL' => [],
						'UF_TM_ALLOWED_DELTA' => -1,
					];

					foreach ($this->UF_DEPARTMENT as $dpt)
					{
						$dptSet = CTimeMan::GetSectionSettings($dpt);

						if ($allSet['UF_TIMEMAN'] !== 'Y' && $dptSet['UF_TIMEMAN'])
						{
							$allSet['UF_TIMEMAN'] = $dptSet['UF_TIMEMAN'];
						}
						if ($dptSet['UF_TM_MAX_START'])
						{
							$allSet['UF_TM_MAX_START'] = min($dptSet['UF_TM_MAX_START'], $allSet['UF_TM_MAX_START']);
						}

						$allSet['UF_TM_MAX_START'] = min($dptSet['UF_TM_MAX_START'], $allSet['UF_TM_MAX_START']);
						$allSet['UF_TM_MIN_FINISH'] = max($dptSet['UF_TM_MIN_FINISH'], $allSet['UF_TM_MIN_FINISH']);
						$allSet['UF_TM_MIN_DURATION'] = max($dptSet['UF_TM_MIN_DURATION'], $allSet['UF_TM_MIN_DURATION']);

						if ($dptSet['UF_TM_REPORT_REQ'])
						{
							$allSet['UF_TM_REPORT_REQ'] = $dptSet['UF_TM_REPORT_REQ'];
						}

						if ((!is_array($allSet['UF_TM_REPORT_TPL']) || count($allSet['UF_TM_REPORT_TPL']) <= 0) && $dptSet['UF_TM_REPORT_TPL'])
						{
							$allSet['UF_TM_REPORT_TPL'] = $dptSet['UF_TM_REPORT_TPL'];
						}

						if ($dptSet['UF_TM_ALLOWED_DELTA'])
						{
							if ($allSet['UF_TM_ALLOWED_DELTA'] == -1 || $dptSet['UF_TM_ALLOWED_DELTA'] < $allSet['UF_TM_ALLOWED_DELTA'])
							{
								$allSet['UF_TM_ALLOWED_DELTA'] = $dptSet['UF_TM_ALLOWED_DELTA'];
							}
						}
					}

					//report fields
					$allSet["UF_REPORT_PERIOD"] = (!$allSet["UF_REPORT_PERIOD"] && $dptSet["UF_REPORT_PERIOD"]) ? $dptSet["UF_REPORT_PERIOD"] : $allSet["UF_REPORT_PERIOD"];
					$allSet["UF_TM_TIME"] = (!$allSet["UF_TM_TIME"] && $dptSet["UF_TM_TIME"]) ? $dptSet["UF_TM_TIME"] : $allSet["UF_TM_TIME"];
					$allSet["UF_TM_DAY"] = (!$allSet["UF_TM_DAY"] && $dptSet["UF_TM_DAY"]) ? $dptSet["UF_TM_DAY"] : $allSet["UF_TM_DAY"];
					$allSet["UF_TM_REPORT_DATE"] = (!$allSet["UF_TM_REPORT_DATE"] && $dptSet["UF_TM_REPORT_DATE"]) ? $dptSet["UF_TM_REPORT_DATE"] : $allSet["UF_TM_REPORT_DATE"];

					if ($arRes['UF_TM_ALLOWED_DELTA'] === '0')
					{
						unset($allSet['UF_TM_ALLOWED_DELTA']);
					}
					foreach ($allSet as $key => $value)
					{
						if (!$arRes[$key] || $arRes[$key] === '00:00')
						{
							$arRes[$key] = $value;
						}
					}

					if ($arRes['UF_TIMEMAN'] === 'N')
					{
						return ($arRes = ['UF_TIMEMAN' => false]);
					}
				}
				elseif ($arRes['UF_TIMEMAN'] !== 'Y')
				{
					// if user is not attached to company structure tm can be allowed only in his own profile
					return ($arRes = ['UF_TIMEMAN' => false]);
				}
			} //if ($cnt > 0)

			$dependencyManager = \Bitrix\Timeman\Service\DependencyManager::getInstance();
			$scheduleProvider = $dependencyManager->getScheduleProvider();

			$schedules = $scheduleProvider->findSchedulesByUserId(
				$this->USER_ID,
				['select' => ['ID', 'SCHEDULE_VIOLATION_RULES']],
			);

			$rules = [];
			foreach ($schedules as $schedule)
			{
				$scheduleViolationRules = $schedule->obtainScheduleViolationRules();
				if ($scheduleViolationRules->getId() > 0)
				{
					$rules = $scheduleViolationRules->collectValues(
						Values::ACTUAL,
						FieldTypeMask::SCALAR,
					);

					break;
				}
			}

			$arRes = $this->prepareSettingsValues($arRes, $rules);
		}
		else
		{
			return ['UF_TIMEMAN' => false];
		}

		return $arRes;
	}

	public function ClearCache()
	{
		return $this->_GetLastData(true);
	}

	protected function clearFullReportCache()
	{
		CUserReportFull::clearReportCache($this->USER_ID);
	}

	public function isSocservEnabledByUser()
	{
		return CUserOptions::GetOption("socialservices", "user_socserv_enable", "N", $this->USER_ID) == 'Y';
	}

	protected function _cacheId()
	{
		if (!empty($this->CACHE_ID))
		{
			return $this->CACHE_ID;
		}
		else
		{
			return ($this->CACHE_ID = 'TIMEMAN_USER_' . $this->USER_ID . '|' . FORMAT_DATETIME);
		}
	}

	protected function _GetLastData($clear = false)
	{
		if (!isset(CTimeManUser::$LAST_ENTRY[$this->USER_ID]) || $clear)
		{
			CTimeManUser::$LAST_ENTRY[$this->USER_ID] = CTimeManEntry::GetLast($this->USER_ID);
		}

		if (!empty(CTimeManUser::$LAST_ENTRY[$this->USER_ID]))
		{
			$lastPauseInfo = $this->getLastPauseInfo($this->USER_ID);
			if ($lastPauseInfo)
			{
				CTimeManUser::$LAST_ENTRY[$this->USER_ID]['LAST_PAUSE'] = $lastPauseInfo;
			}
			else
			{
				unset(CTimeManUser::$LAST_ENTRY[$this->USER_ID]['LAST_PAUSE']);
			}
		}

		return CTimeManUser::$LAST_ENTRY[$this->USER_ID];
	}

	protected function _ReopenGetActivity($entry_id)
	{
		$dbRes = CTimeManReport::GetList(
			['ID' => 'ASC'],
			['ENTRY_ID' => $entry_id, 'REPORT_TYPE' => 'ERR_OPEN', 'ACTIVE' => 'Y']
		);

		if ($arRes = $dbRes->Fetch())
		{
			return false;
		}
		else
		{
			return true;
		}
	}

	public function prepareSettingsValues(array $arRes, array $rules): array
	{
		$arRes['UF_TIMEMAN'] = true; // it can be only Y|null at this moment

		$arRes['UF_TM_MAX_START'] = $this->getSettingsValue(
			$rules['MAX_EXACT_START'] ?? null,
			$arRes['UF_TM_MAX_START'] ?? null,
			COption::GetOptionInt('timeman', 'workday_max_start', 33300)
		);

		$arRes['UF_TM_MIN_FINISH'] = $this->getSettingsValue(
			$rules['MIN_EXACT_END'] ?? null,
			$arRes['UF_TM_MIN_FINISH'] ?? null,
			COption::GetOptionInt('timeman', 'workday_min_finish', 63900)
		);

		$arRes['UF_TM_MIN_DURATION'] = $this->getSettingsValue(
			$rules['MIN_DAY_DURATION'] ?? null,
			$arRes['UF_TM_MIN_DURATION'] ?? null,
			COption::GetOptionInt('timeman', 'workday_min_duration', 28800)
		);

		$arRes['UF_TM_REPORT_REQ'] = (
			$arRes['UF_TM_REPORT_REQ']
			?: COption::GetOptionString('timeman', 'workday_report_required', 'A')
		);

		$arRes['UF_TM_REPORT_TPL'] = $arRes['UF_TM_REPORT_TPL'] ?: [];

		$arRes['UF_TM_ALLOWED_DELTA'] = $this->getSettingsValue(
			$rules['MAX_ALLOWED_TO_EDIT_WORK_TIME'] ?? null,
			$arRes['UF_TM_ALLOWED_DELTA'] ?? null,
			COption::GetOptionInt('timeman', 'workday_allowed_delta', 900)
		);

		return $arRes;
	}

	private function getSettingsValue($ruleValue, $currentValue, $defaultValue)
	{
		if ($currentValue !== null && $currentValue > -1)
		{
			return $currentValue;
		}

		if ($ruleValue !== null && $ruleValue > -1)
		{
			return $ruleValue;
		}

		return $defaultValue;
	}
}

?>
