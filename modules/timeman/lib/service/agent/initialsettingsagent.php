<?php
namespace Bitrix\Timeman\Service\Agent;

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Timeman\Form\Schedule\ScheduleForm;
use Bitrix\Timeman\Helper\TimeHelper;
use Bitrix\Timeman\Model\Schedule\Calendar\CalendarExclusionTable;
use Bitrix\Timeman\Model\Schedule\Calendar\CalendarTable;
use Bitrix\Timeman\Model\Schedule\ScheduleTable;
use Bitrix\Timeman\Model\Schedule\Violation\ViolationRulesTable;
use Bitrix\Timeman\Model\Security\TaskAccessCodeTable;
use Bitrix\Timeman\Service\DependencyManager;
use CTask;

Loc::loadMessages(__FILE__);

class InitialSettingsAgent
{
	public static function installDefaultPermissions()
	{
		global $DB;
		if (!$DB->TableExists(TaskAccessCodeTable::getTableName()))
		{
			return '';
		}
		$cnt = TaskAccessCodeTable::getCount();
		if ($cnt > 0)
		{
			return '';
		}

		$defaultTaskId = CTask::GetIdByLetter('N', 'timeman');
		if ($defaultTaskId <= 0)
		{
			return '';
		}
		$initialTask = CTask::GetByID($defaultTaskId)->Fetch();
		if (!$initialTask)
		{
			return '';
		}

		$taskId = (int) $initialTask['ID'];

		if ($taskId)
		{
			$connection = \Bitrix\Main\Application::getConnection();
			$helper = $connection->getSqlHelper();

			$connection->query(
				$helper->getInsertIgnore(
					'b_timeman_task_access_code',
					'(TASK_ID, ACCESS_CODE)',
					"VALUES ('" . $taskId . "', 'G2')"
				)
			);
		}

		return '';
	}

	public static function installDefaultData()
	{
		$helper = new static();
		$helper->installWorkCalendars();
		$helper->installDefaultWorkSchedule();

		return '';
	}

	public static function installDefaultSchedule()
	{
		// this method was renamed into installDefaultData
		// but someone still may have an agent in database with the old name
		// delete this installDefaultSchedule method after, lets say, Dec 2019
		static::installDefaultData();
	}

	private function createScheduleForm()
	{
		$parentCalendarId = '';
		if ($this->getCurrentPortalZone())
		{
			$systemCalendar = CalendarTable::query()
				->addSelect('ID')
				->where('SYSTEM_CODE', $this->getCurrentPortalZone())
				->exec()
				->fetch();
			if ($systemCalendar)
			{
				$parentCalendarId = $systemCalendar['ID'];
			}
		}

		$scheduleForm = new \Bitrix\Timeman\Form\Schedule\ScheduleForm();

		$scheduleForm->load([
			$scheduleForm->getFormName() => [
				'type' => ScheduleTable::SCHEDULE_TYPE_FIXED,
				'name' => Loc::getMessage('TIMEMAN_DEFAULT_SCHEDULE_FOR_ALL_USERS_NAME'),
				'reportPeriod' => ScheduleTable::REPORT_PERIOD_MONTH,
				'reportPeriodStartWeekDay' => ScheduleTable::REPORT_PERIOD_OPTIONS_START_WEEK_DAY_MONDAY,
				'worktimeRestrictions' => [],
				'assignments' => [ScheduleForm::ALL_USERS],
				'ShiftForm' => [
					[
						'shiftId' => '',
						'workDays' => '12345',
						'name' => '',
						'startTimeFormatted' => TimeHelper::getInstance()->convertSecondsToHoursMinutes(32400),
						'endTimeFormatted' => TimeHelper::getInstance()->convertSecondsToHoursMinutes(64800),
						'breakDurationFormatted' => '00:00',
					],
				],
				'CalendarForm' => [
					'calendarId' => '',
					'parentId' => $parentCalendarId,
					'datesJson' => '{}',
				],

				'ViolationForm' => [
					'scheduleId' => '',
					'maxExactStartFormatted' => TimeHelper::getInstance()->convertSecondsToHoursMinutes(9 * 3600 + 15 * 60),
					'minExactEndFormatted' => TimeHelper::getInstance()->convertSecondsToHoursMinutes(18 * 3600 - 15 * 60),
					'relativeStartFromFormatted' => '--:--',
					'relativeStartToFormatted' => '--:--',
					'relativeEndFromFormatted' => '--:--',
					'relativeEndToFormatted' => '--:--',
					'minDayDurationFormatted' => TimeHelper::getInstance()->convertSecondsToHoursMinutes(8 * 3600),
					'maxAllowedToEditWorkTimeFormatted' => TimeHelper::getInstance()->convertSecondsToHoursMinutes(900),
					'maxShiftStartDelayFormatted' => '--:--',
					'maxWorkTimeLackForPeriod' => '',
					'startEndNotifyUsers' => [],
					'editWorktimeNotifyUsers' => [ViolationRulesTable::USERS_TO_NOTIFY_USER_MANAGER,],
				],
				'controlledActions' => ScheduleTable::CONTROLLED_ACTION_START_AND_END,
				'allowedDevices' => [
					'browser' => 'on',
					'b24time' => 'on',
					'mobile' => 'on',
					'mobileRecordLocation' => '',
				],
			],
		]);
		$scheduleForm->validate();
		$scheduleForm->violationForm->saveAllViolationFormFields = true;
		return $scheduleForm;
	}

	private function installWorkCalendars()
	{
		if (!Application::getConnection()->isTableExists(CalendarTable::getTableName())
			|| !Application::getConnection()->isTableExists(CalendarExclusionTable::getTableName()))
		{
			return;
		}
		$calendarsExclusions = [];
		$calendarFilePath = \Bitrix\Main\Application::getDocumentRoot() . '/bitrix/modules/timeman/lib/update/calendars/exclusions.php';
		if (\Bitrix\Main\IO\File::isFileExists($calendarFilePath))
		{
			$calendarsExclusions = include $calendarFilePath;
		}
		if (empty($calendarsExclusions))
		{
			return;
		}
		foreach ($calendarsExclusions as $calendarsData)
		{
			if (empty($calendarsData['SYSTEM_CODE'])
				|| empty($calendarsData['NAME'])
				|| empty($calendarsData['EXCLUSIONS']))
			{
				continue;
			}
			$res = Application::getConnection()->query("SELECT ID FROM b_timeman_work_calendar WHERE SYSTEM_CODE = '" . $calendarsData['SYSTEM_CODE'] . "' limit 1");
			if ($calendar = $res->fetch())
			{
				$calendarId = $calendar['ID'];
			}
			else
			{
				Application::getConnection()->query("INSERT INTO b_timeman_work_calendar (NAME, SYSTEM_CODE) 
					VALUES ('" . $calendarsData['NAME'] . "', '" . $calendarsData['SYSTEM_CODE'] . "');");
				$calendarId = Application::getConnection()->getInsertedId();
			}
			if ($calendarId > 0)
			{
				foreach ($calendarsData['EXCLUSIONS'] as $year => $datesJson)
				{
					if ($calendar)
					{
						$res = Application::getConnection()->query("SELECT 'x' FROM b_timeman_work_calendar_exclusion
							 WHERE CALENDAR_ID = $calendarId AND `YEAR` = $year limit 1");
						if ($res->fetch())
						{
							continue;
						}
					}
					$connection = \Bitrix\Main\Application::getConnection();
					$helper = $connection->getSqlHelper();
					$connection->query(
						$helper->getInsertIgnore(
							'b_timeman_work_calendar_exclusion',
							'(CALENDAR_ID, YEAR, DATES)',
						 	"VALUES ($calendarId, $year, '$datesJson')"
						)
					);
				}
			}
		}
	}

	private function installDefaultWorkSchedule()
	{
		if (!Application::getConnection()->isTableExists(ScheduleTable::getTableName()))
		{
			return;
		}
		if (ScheduleTable::getCount(Query::filter()->where('IS_FOR_ALL_USERS', true)) > 0)
		{
			return;
		}

		DependencyManager::getInstance()
			->getScheduleService()
			->add($this->createScheduleForm());
	}

	private function getCurrentPortalZone()
	{
		if (Loader::includeModule('bitrix24'))
		{
			if (!empty(\CBitrix24::getPortalZone()))
			{
				return \CBitrix24::getPortalZone();
			}
		}
		return null;
	}
}