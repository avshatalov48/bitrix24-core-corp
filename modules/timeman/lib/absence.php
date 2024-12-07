<?php
namespace Bitrix\Timeman;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Web\Json;

Loc::loadMessages(__FILE__);

class Absence
{
	const TYPE_IDLE = 'IDLE';
	const TYPE_OFFLINE = 'OFFLINE';
	const TYPE_DESKTOP_OFFLINE = 'DESKTOP_OFFLINE';
	const TYPE_DESKTOP_ONLINE = 'DESKTOP_ONLINE';
	const TYPE_DESKTOP_START = 'DESKTOP_START';
	const TYPE_TM_START = 'TM_START';
	const TYPE_TM_PAUSE = 'TM_PAUSE';
	const TYPE_TM_CONTINUE = 'TM_CONTINUE';
	const TYPE_TM_END = 'TM_END';

	const REPORT_TYPE_WORK = 'WORK';
	const REPORT_TYPE_PRIVATE = 'PRIVATE';
	const REPORT_TYPE_NONE = 'NONE';

	const SOURCE_ONLINE_EVENT = 'ONLINE_EVENT';
	const SOURCE_OFFLINE_AGENT = 'OFFLINE_AGENT';
	const SOURCE_DESKTOP_OFFLINE_AGENT = 'DESKTOP_OFFLINE_AGENT';
	const SOURCE_DESKTOP_ONLINE_EVENT = 'DESKTOP_ONLINE_EVENT';
	const SOURCE_DESKTOP_START_EVENT = 'DESKTOP_START_EVENT';
	const SOURCE_IDLE_EVENT = 'IDLE_EVENT';
	const SOURCE_TM_EVENT = 'TM_EVENT';

	const TYPE_NONE = 'NONE';
	const TYPE_ALL = 'ALL';
	const TYPE_FOR_USER = 'USER';

	const DEFAULT_IDLE_TIME = 15;
	const DEFAULT_WORKDAY_HOURS = 8;

	/* options */

	public static function isActive()
	{
		return (bool)\Bitrix\Main\Config\Option::get('timeman', 'register_user_absence', false);
	}

	public static function setOptionActive($result = true)
	{
		\Bitrix\Main\Config\Option::set('timeman', 'register_user_absence', $result);

		return true;
	}

	public static function isRegisterOffline()
	{
		return (bool)\Bitrix\Main\Config\Option::get('timeman', 'register_user_offline', true);
	}

	public static function setOptionRegisterOffline($result = true)
	{
		\Bitrix\Main\Config\Option::set('timeman', 'register_user_offline', $result);

		return true;
	}

	public static function isRegisterIdle()
	{
		return (bool)\Bitrix\Main\Config\Option::get('timeman', 'register_user_idle', true);
	}

	public static function setOptionRegisterIdle($result = true)
	{
		\Bitrix\Main\Config\Option::set('timeman', 'register_user_idle', $result);

		return true;
	}

	public static function isRegisterDesktop()
	{
		return (bool)\Bitrix\Main\Config\Option::get('timeman', 'register_user_desktop', true);
	}

	public static function setOptionRegisterDesktop($result = true)
	{
		\Bitrix\Main\Config\Option::set('timeman', 'register_user_desktop', $result);

		return true;
	}

	public static function isReportEnable()
	{
		$requestReport = (bool)\Bitrix\Main\Config\Option::get('timeman', 'request_report', "0");
		return $requestReport !== false;
	}

	public static function getOptionReportEnableType()
	{
		$requestReport = \Bitrix\Main\Config\Option::get('timeman', 'request_report', "0");
		if ($requestReport == "1")
		{
			return self::TYPE_ALL;
		}
		else if ($requestReport == "0")
		{
			return self::TYPE_NONE;
		}
		else
		{
			return self::TYPE_FOR_USER;
		}
	}

	public static function getOptionReportEnableUsers()
	{
		if (self::getOptionReportEnableType() == self::TYPE_FOR_USER)
		{
			$requestReport = \Bitrix\Main\Config\Option::get('timeman', 'request_report', "0");
			return Json::decode($requestReport);
		}
		else
		{
			return Array();
		}
	}

	public static function isReportEnableForUser($userId, $idleMinutes = null)
	{
		$userId = intval($userId);
		if (!\Bitrix\Main\Loader::includeModule('pull'))
		{
			return false;
		}
		if ($userId <= 0 || !self::isActive() || !self::isReportEnable())
		{
			return false;
		}

		$requestReport = \Bitrix\Main\Config\Option::get('timeman', 'request_report', "0");
		if ($requestReport == "1")
		{
			$skipReport = \Bitrix\Main\Config\Option::get('timeman', 'skip_report', "0");
			if ($skipReport == "0")
			{
				$result = true;
			}
			else
			{
				$skipReport = Json::decode($skipReport);
				$result = !$skipReport || !in_array($userId, $skipReport);
			}
		}
		else if ($requestReport == "0")
		{
			$result = false;
		}
		else
		{
			$requestReport = Json::decode($requestReport);
			$result = $requestReport && in_array($userId, $requestReport);
		}

		if ($result && !is_null($idleMinutes) && self::getMinimumIdleForReport() > $idleMinutes)
		{
			return false;
		}

		return $result;
	}

	public static function setOptionRequestReport($result = true)
	{
		if (is_bool($result))
		{
			$result = $result? "1": "0";
		}
		else if (is_array($result))
		{
			$result = Json::encode($result);
		}
		else
		{
			return false;
		}

		\Bitrix\Main\Config\Option::set('timeman', 'request_report', $result);

		return true;
	}

	private static function setOptionSkipReport($userIds): bool
	{
		if (!is_array($userIds))
		{
			return false;
		}

		\Bitrix\Main\Config\Option::set('timeman', 'skip_report', Json::encode($userIds));

		return true;
	}

	public static function getOptionReportListSimpleType()
	{
		$requestReport = \Bitrix\Main\Config\Option::get('timeman', 'request_report_list_simple', "1");
		if ($requestReport == "1")
		{
			return self::TYPE_ALL;
		}
		else
		{
			return self::TYPE_FOR_USER;
		}
	}

	public static function getOptionReportListSimpleUsers()
	{
		if (self::getOptionReportListSimpleType() == self::TYPE_FOR_USER)
		{
			$requestReport = \Bitrix\Main\Config\Option::get('timeman', 'request_report_list_simple', "1");
			return Json::decode($requestReport);
		}
		else
		{
			return Array();
		}
	}

	public static function isReportListSimpleEnableForUser($userId)
	{
		$userId = intval($userId);
		if (!\Bitrix\Main\Loader::includeModule('pull'))
		{
			return false;
		}
		if ($userId <= 0 || !self::isActive())
		{
			return false;
		}

		if (self::isReportListFullEnableForUser($userId))
		{
			return true;
		}

		if (self::getOptionReportListSimpleType() == self::TYPE_ALL)
		{
			$result = true;
		}
		else
		{
			$result = in_array($userId, self::getOptionReportListSimpleUsers());
		}

		return $result;
	}

	public static function setOptionReportListSimple($result = true)
	{
		if (is_bool($result))
		{
			$result = $result? "1": "0";
		}
		else if (is_array($result))
		{
			$result = Json::encode($result);
		}
		else
		{
			return false;
		}

		\Bitrix\Main\Config\Option::set('timeman', 'request_report_list_simple', $result);

		return true;
	}

	public static function getOptionReportListFullType()
	{
		$requestReport = \Bitrix\Main\Config\Option::get('timeman', 'request_report_list_full', "0");
		if ($requestReport == "1")
		{
			return self::TYPE_ALL;
		}
		else
		{
			return self::TYPE_FOR_USER;
		}
	}

	public static function getOptionReportListFullUsers(): array
	{
		if (self::getOptionReportListFullType() == self::TYPE_FOR_USER)
		{
			$requestReport = \Bitrix\Main\Config\Option::get('timeman', 'request_report_list_full', "0");
			$data = Json::decode($requestReport);

			return is_array($data) ? $data : [$data];
		}
		else
		{
			return [];
		}
	}

	public static function isReportListFullEnableForUser($userId)
	{
		$userId = intval($userId);
		if (!\Bitrix\Main\Loader::includeModule('pull'))
		{
			return false;
		}
		if ($userId <= 0 || !self::isActive())
		{
			return false;
		}

		if (self::getOptionReportListFullType() == self::TYPE_ALL)
		{
			$result = true;
		}
		else
		{
			$result = in_array($userId, self::getOptionReportListFullUsers());
		}

		return $result;
	}

	public static function setOptionReportListFull($result = true)
	{
		if (is_bool($result))
		{
			$result = $result? "1": "0";
		}
		else if (is_array($result))
		{
			$result = Json::encode($result);
		}
		else
		{
			return false;
		}

		\Bitrix\Main\Config\Option::set('timeman', 'request_report_list_full', $result);

		return true;
	}

	public static function getMinimumIdleForReport()
	{
		return (int)\Bitrix\Main\Config\Option::get('timeman', 'request_report_min_idle', self::DEFAULT_IDLE_TIME);
	}

	public static function setOptionMinimumIdleForReport($minutes = self::DEFAULT_IDLE_TIME)
	{
		$minutes = (int)$minutes;
		$minutes = $minutes > 0? $minutes: 1;

		\Bitrix\Main\Config\Option::set('timeman', 'request_report_min_idle', $minutes);

		return true;
	}




	/* main methods */

	public static function setDesktopOnline($userId, $currentDate, $lastDate = null)
	{
		$userId = intval($userId);
		if ($userId <= 0 || !self::isActive() || !self::isRegisterDesktop())
			return false;

		if (
			$currentDate && $lastDate
			&& $currentDate->getTimestamp() - $lastDate->getTimestamp() < \Bitrix\Main\UserTable::getSecondsForLimitOnline()
		)
		{
			return false;
		}

		$dateStart = (new \Bitrix\Main\Type\DateTime())->format('Y-m-d').' 00:00:00';
		$orm = \Bitrix\Timeman\Model\EntriesTable::getList(Array(
			'select' => Array(
				'ID',
				'USER_ID'
			),
			'filter' => Array(
				'=USER_ID' => $userId,
				'>=DATE_START' => new \Bitrix\Main\DB\SqlExpression('?', $dateStart),
				'=DATE_FINISH' => null,
			),
		));

		$todayStart = new \Bitrix\Main\Type\DateTime((new \Bitrix\Main\Type\DateTime())->format('Y-m-d').' 00:00:00', 'Y-m-d H:i:s');

		$entryId = 0;
		if ($entry = $orm->fetch())
		{
			$entryId = $entry['ID'];
		}
		if (!$entryId)
		{
			return false;
		}

		$currentDateTime = $currentDate->getTimestamp() - $todayStart->getTimestamp();

		if ($lastDate)
		{
			$systemText = Loc::getMessage('TIMEMAN_ABSENCE_TEXT_DESKTOP_ONLINE_REPORT', [
				'#OFFLINE_DATE#' => $lastDate->format(DateTime::getFormat()),
				'#ONLINE_DATE#' => $currentDate->format(DateTime::getFormat()),
				'#DURATION#' => self::formatDuration($currentDate->getTimestamp() - $lastDate->getTimestamp()),
				'#BR#' => "\n\r"
			]);
		}
		else
		{
			$systemText = Loc::getMessage('TIMEMAN_ABSENCE_TEXT_DESKTOP_ONLINE_FIRST_TIME');
		}

		\Bitrix\Timeman\Model\AbsenceTable::add(Array(
			'ENTRY_ID' => $entry['ID'],
			'USER_ID' => $entry['USER_ID'],
			'DATE_START' => $currentDate,
			'TIME_START' => $currentDateTime,
			'DATE_FINISH' => $currentDate,
			'TIME_FINISH' => $currentDateTime,
			'DURATION' => 0,
			'ACTIVE' => 'N',
			'SYSTEM_TEXT' => $systemText,
			'TYPE' => self::TYPE_DESKTOP_ONLINE,
			'SOURCE_START' => self::SOURCE_DESKTOP_ONLINE_EVENT,
			'IP_START' => $_SERVER['REMOTE_ADDR'],
			'IP_FINISH' => $_SERVER['REMOTE_ADDR'],
		));

		return true;
	}

	public static function setDesktopStart($userId)
	{
		$userId = intval($userId);
		if ($userId <= 0 || !self::isActive() || !self::isRegisterDesktop())
			return false;

		$dateStart = (new \Bitrix\Main\Type\DateTime())->format('Y-m-d').' 00:00:00';
		$orm = \Bitrix\Timeman\Model\EntriesTable::getList(Array(
			'select' => Array(
				'ID',
				'USER_ID'
			),
			'filter' => Array(
				'=USER_ID' => $userId,
				'>=DATE_START' => new \Bitrix\Main\DB\SqlExpression('?', $dateStart),
				'=DATE_FINISH' => null,
			),
		));

		$todayStart = new \Bitrix\Main\Type\DateTime((new \Bitrix\Main\Type\DateTime())->format('Y-m-d').' 00:00:00', 'Y-m-d H:i:s');

		$entryId = 0;
		if ($entry = $orm->fetch())
		{
			$entryId = $entry['ID'];
		}
		if (!$entryId)
		{
			return false;
		}

		$currentDate = new \Bitrix\Main\Type\DateTime();
		$currentDateTime = $currentDate->getTimestamp() - $todayStart->getTimestamp();

		\Bitrix\Timeman\Model\AbsenceTable::add(Array(
			'ENTRY_ID' => $entry['ID'],
			'USER_ID' => $entry['USER_ID'],
			'DATE_START' => $currentDate,
			'TIME_START' => $currentDateTime,
			'DATE_FINISH' => $currentDate,
			'TIME_FINISH' => $currentDateTime,
			'DURATION' => 0,
			'ACTIVE' => 'N',
			'TYPE' => self::TYPE_DESKTOP_START,
			'SOURCE_START' => self::SOURCE_DESKTOP_START_EVENT,
			'IP_START' => $_SERVER['REMOTE_ADDR'],
			'IP_FINISH' => $_SERVER['REMOTE_ADDR'],
		));

		return true;
	}

	public static function setStatusIdle($userId, $result = true, $idleStart = null)
	{
		$userId = intval($userId);
		if ($userId <= 0 || !self::isActive() || !self::isRegisterIdle())
			return false;

		$addIdleStatus = (bool)$result;

		$dateStart = (new \Bitrix\Main\Type\DateTime())->format('Y-m-d').' 00:00:00';
		$orm = \Bitrix\Timeman\Model\EntriesTable::getList(Array(
			'select' => Array(
				'ID',
				'USER_ID',
				'ABSENCE_ID' => 'ABSENCE.ID',
				'ABSENCE_TIME_START' => 'ABSENCE.TIME_START',
				'ABSENCE_DATE_START' => 'ABSENCE.DATE_START',
				'ABSENCE_TYPE' => 'ABSENCE.TYPE',
			),
			'filter' => Array(
				'=USER_ID' => $userId,
				'>=DATE_START' => new \Bitrix\Main\DB\SqlExpression('?', $dateStart),
				'=DATE_FINISH' => null,
			),
			'runtime' => Array(
				new \Bitrix\Main\Entity\ReferenceField(
					'ABSENCE',
					'\Bitrix\Timeman\Model\AbsenceTable',
					array(
						"=ref.USER_ID" => "this.USER_ID",
						"=ref.ACTIVE" => new \Bitrix\Main\DB\SqlExpression('?', 'Y'),
						">=ref.DATE_START" => new \Bitrix\Main\DB\SqlExpression('?', $dateStart),
					),
					array("join_type"=>"left")
				)
			)
		));

		$todayStart = new \Bitrix\Main\Type\DateTime((new \Bitrix\Main\Type\DateTime())->format('Y-m-d').' 00:00:00', 'Y-m-d H:i:s');

		$dayIsOpen = false;
		$entryId = 0;
		while ($entry = $orm->fetch())
		{
			$dayIsOpen = true;
			$entryId = $entry['ID'];
			if ($entry['ABSENCE_ID'])
			{
				if ($entry['ABSENCE_TYPE'] == self::TYPE_IDLE && $addIdleStatus)
				{
					$addIdleStatus = false;
					continue;
				}

				$intersect = self::getIntersectWithCalendar($userId);
				$autoAgree = false;

				if (
					(bool)$intersect
					&& $entry['ABSENCE_DATE_START']->getTimestamp() < $intersect['DATE_FROM']->getTimestamp()
					&& $intersect['DATE_FROM']->getTimestamp() > $todayStart->getTimestamp()
				)
				{
					$dateFinish = $intersect['DATE_FROM'];
				}
				else
				{
					$dateFinish = new \Bitrix\Main\Type\DateTime();
					$autoAgree = (bool)$intersect;
				}

				$timeFinish = $dateFinish->getTimestamp() - $todayStart->getTimestamp();
				$duration = $timeFinish - $entry['ABSENCE_TIME_START'];

				$fields = Array(
					'ACTIVE' => 'N',
					'DATE_FINISH' => $dateFinish,
					'TIME_FINISH' => $timeFinish,
					'DURATION' => $duration,
					'SOURCE_FINISH' => self::SOURCE_IDLE_EVENT,
					'IP_FINISH' => $_SERVER['REMOTE_ADDR'],
				);
				if ($autoAgree)
				{
					$fields['REPORT_TYPE'] = self::REPORT_TYPE_WORK;
					$fields['REPORT_TEXT'] = Loc::getMessage('TIMEMAN_ABSENCE_REPORT_FROM_CALENDAR', ['#TITLE#' => $intersect['TITLE']]);
					$fields['REPORT_CALENDAR_ID'] = $intersect['ID'];
				}

				\Bitrix\Timeman\Model\AbsenceTable::update($entry['ABSENCE_ID'], $fields);

				$martaSend = false;
				if (!$autoAgree && self::isReportEnableForUser($entry['USER_ID'], self::convertSecondsToMinutes($duration)))
				{
					$martaSend = true;
					\Bitrix\Pull\Event::add($entry['USER_ID'], Array(
						'module_id' => 'timeman',
						'command' => 'timeControlCommitAbsence',
						'params' => Array(
							'absenceId' => $entry['ABSENCE_ID'],
							'dateStart' => date('c', $entry['ABSENCE_DATE_START']->getTimestamp()),
							'dateFinish' => date('c', $dateFinish->getTimestamp()),
							'duration' => $duration
						)
					));
				}

				if (
					false // TODO remove debug case
					&& self::isReportEnableForUser($entry['USER_ID'])
				)
				{
					\Bitrix\Main\IO\File::putFileContents(
						$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/timecontrol-debug.log",
						print_r([
							'ACTION' => 'FINISH',
							'USER_ID' => $entry['USER_ID'],
							'MARTA_SEND' => $martaSend? 'Y':'N',
							'AUTO_AGREE' => $autoAgree? 'Y':'N',
							Array(
								'absenceId' => $entry['ABSENCE_ID'],
								'dateStart' => date('c', $entry['ABSENCE_DATE_START']->getTimestamp()),
								'dateFinish' => date('c', $dateFinish->getTimestamp()),
								'duration' => $duration
							)
						], 1),
						\Bitrix\Main\IO\File::APPEND
					);
				}
			}
		}

		if ($dayIsOpen && $addIdleStatus)
		{
			if (!$idleStart)
			{
				$idleStart = new \Bitrix\Main\Type\DateTime();
			}
			$timeStart = $idleStart->getTimestamp() - $todayStart->getTimestamp();

			$result = \Bitrix\Timeman\Model\AbsenceTable::add(Array(
				'ENTRY_ID' => $entryId,
				'USER_ID' => $userId,
				'TYPE' => self::TYPE_IDLE,
				'DATE_START' => $idleStart,
				'TIME_START' => $timeStart,
				'SOURCE_START' => self::SOURCE_IDLE_EVENT,
				'IP_START' => $_SERVER['REMOTE_ADDR'],
			));

			if (
				false // TODO remove debug case
				&& $result && self::isReportEnableForUser($userId)
			)
			{
				\Bitrix\Main\IO\File::putFileContents(
					$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/timecontrol-debug.log",
					print_r([
						'ACTION' => 'START',
						'USER_ID' => $userId,
						Array(
							'absenceId' => $result->getId(),
							'dateStart' => date('c', $idleStart->getTimestamp()),
						)
					], 1),
					\Bitrix\Main\IO\File::APPEND
				);
			}
		}

		return true;
	}

	public static function setStatusOnline($users, $ip = null)
	{
		if (!self::isActive() || !self::isRegisterOffline())
			return false;

		$dateStart = (new \Bitrix\Main\Type\DateTime())->format('Y-m-d').' 00:00:00';

		$orm = \Bitrix\Timeman\Model\EntriesTable::getList(Array(
			'select' => Array(
				'USER_ID',
				'ABSENCE_ID' => 'ABSENCE.ID',
				'ABSENCE_DATE_START' => 'ABSENCE.DATE_START',
				'ABSENCE_TIME_START' => 'ABSENCE.TIME_START',
				'ABSENCE_TYPE' => 'ABSENCE.TYPE',
			),
			'filter' => Array(
				'=USER_ID' => $users,
				'>=DATE_START' => new \Bitrix\Main\DB\SqlExpression('?', $dateStart),
				'=DATE_FINISH' => null,
				'>=ABSENCE.DATE_START' => new \Bitrix\Main\DB\SqlExpression('?', $dateStart),
			),
			'runtime' => Array(
				new \Bitrix\Main\Entity\ReferenceField(
					'ABSENCE',
					'\Bitrix\Timeman\Model\AbsenceTable',
					array(
						"=ref.USER_ID" => "this.USER_ID",
						"=ref.ACTIVE" => new \Bitrix\Main\DB\SqlExpression('?', 'Y'),
						"=ref.TYPE" => new \Bitrix\Main\DB\SqlExpression('?', self::TYPE_OFFLINE),
					),
					array("join_type"=>"inner")
				)
			)
		));

		$todayStart = new \Bitrix\Main\Type\DateTime((new \Bitrix\Main\Type\DateTime())->format('Y-m-d').' 00:00:00', 'Y-m-d H:i:s');

		while ($entry = $orm->fetch())
		{
			$dateFinish = new \Bitrix\Main\Type\DateTime();
			$timeFinish = $dateFinish->getTimestamp() - $todayStart->getTimestamp();
			$duration = $timeFinish - $entry['ABSENCE_TIME_START'];

			$fields = Array(
				'ACTIVE' => 'N',
				'DATE_FINISH' => $dateFinish,
				'TIME_FINISH' => $timeFinish,
				'DURATION' => $timeFinish - $entry['ABSENCE_TIME_START'],
				'SOURCE_FINISH' => self::SOURCE_ONLINE_EVENT,
			);
			if ($ip)
			{
				$fields['IP_FINISH'] = $ip;
			}
			\Bitrix\Timeman\Model\AbsenceTable::update($entry['ABSENCE_ID'], $fields);

			if (self::isReportEnableForUser($entry['USER_ID'], self::convertSecondsToMinutes($duration)))
			{
				\Bitrix\Pull\Event::add($entry['USER_ID'], Array(
					'module_id' => 'timeman',
					'command' => 'timeControlCommitAbsence',
					'params' => Array(
						'absenceId' => $entry['ABSENCE_ID'],
						'dateStart' => date('c', $entry['ABSENCE_DATE_START']->getTimestamp()),
						'dateFinish' => date('c', $dateFinish->getTimestamp()),
						'duration' => $duration
					)
				));
			}
		}

		return true;
	}

	public static function addReport($absenceId, $text, $type = self::REPORT_TYPE_WORK, $addToCalendar = true, $userId = null)
	{
		if (is_null($userId) && $GLOBALS['USER'])
		{
			$userId = $GLOBALS['USER']->GetId();
		}

		$userId = intval($userId);
		if ($userId <= 0)
		{
			return false;
		}

		$text = trim($text);
		if ($text == '')
		{
			return false;
		}

		$result = \Bitrix\Timeman\Model\AbsenceTable::getById($absenceId)->fetch();
		if ($result['USER_ID'] != $userId)
		{
			return false;
		}

		$calendarId = 0;
		if ($addToCalendar)
		{
			$calendarId = self::addCalendarEntry($userId, $text, $result['DATE_START'], $result['DATE_FINISH'], $type == self::REPORT_TYPE_PRIVATE);
		}

		\Bitrix\Timeman\Model\AbsenceTable::update($absenceId, Array(
			'REPORT_TYPE' => $type,
			'REPORT_TEXT' => $text,
			'REPORT_CALENDAR_ID' => $calendarId,
		));

		return true;
	}

	public static function getMonthReport($userId, $year, $month, $workdayHours = self::DEFAULT_WORKDAY_HOURS, $idleMinutes = null)
	{
		$userId = intval($userId);
		if ($userId <= 0)
			return false;

		$year = intval($year);
		$year = $year > 3000 || $year < 1900? date('Y'): $year;

		$month = intval($month);
		$month = $month > 12 || $month < 1? date('n'): $month;

		$idleMinutes = !is_null($idleMinutes)? intval($idleMinutes): self::getMinimumIdleForReport();
		$idleMinutes = $idleMinutes > 0? $idleMinutes: 0;

		$workdayHours = intval($workdayHours);
		$workdayHours = $workdayHours > 0? $workdayHours: 1;
		$workdayHoursInSeconds = $workdayHours*60*60;

		$dateStart = new \Bitrix\Main\Type\DateTime($year.'-'.str_pad($month, 2, '0', STR_PAD_LEFT).'-01 00:00:00', 'Y-m-d H:i:s');
		$dateFinish = new \Bitrix\Main\Type\DateTime(($year+($month==12? 1: 0)).'-'.str_pad(($month==12? 1: $month+1), 2, '0', STR_PAD_LEFT).'-01 00:00:00', 'Y-m-d H:i:s');
		$dateFinish->add('-1 SECONDS');

		$workDays = Array();

		$orm = \Bitrix\Timeman\Model\AbsenceTable::getList(Array(
			'select' => Array(
					'ID', 'USER_ID', 'TYPE', 'DATE_START', 'DATE_FINISH', 'DURATION', 'ACTIVE', 'ENTRY_ID', 'REPORT_TYPE', 'REPORT_TEXT', 'SYSTEM_TEXT', 'SOURCE_START', 'SOURCE_FINISH', 'IP_START', 'IP_FINISH',
					'ENTRIES_DATE_START' => 'ENTRIES.DATE_START',
					'ENTRIES_DATE_FINISH' => 'ENTRIES.DATE_FINISH',
					'ENTRIES_TIME_LEAKS' => 'ENTRIES.TIME_LEAKS',
					'ENTRIES_DURATION' => 'ENTRIES.DURATION',
			),
			'filter' => Array(
				'=USER_ID' => $userId,
				'>=DATE_START' => $dateStart,
				'<=DATE_START' => $dateFinish
			),
			'runtime' => Array(
				new \Bitrix\Main\Entity\ReferenceField(
					'ENTRIES',
					'\Bitrix\Timeman\Model\EntriesTable',
					array(
						"=ref.ID" => "this.ENTRY_ID",
					),
					array("join_type"=>"left")
				)
			)
		));
		while ($entry = $orm->fetch())
		{
			$entry['ACTIVE'] = $entry['ACTIVE'] !== 'N';
			$entry['DURATION'] = (int)$entry['DURATION'];

			if (
				$entry['ENTRIES_DURATION'] == 0
				&& $entry['ENTRIES_DATE_START'] instanceof \Bitrix\Main\Type\DateTime
				&& $entry['ENTRIES_DATE_FINISH'] instanceof \Bitrix\Main\Type\DateTime
			)
			{
				$entry['ENTRIES_DURATION'] = $entry['ENTRIES_DATE_FINISH']->getTimestamp() - $entry['ENTRIES_DATE_START']->getTimestamp() - (int)$entry['ENTRIES_TIME_LEAKS'];
			}

			$index = $entry['DATE_START'] instanceof \Bitrix\Main\Type\DateTime? $entry['DATE_START']->format('Ymd'): time();

			$duration = $entry['DURATION'];

			if ($entry['ENTRIES_DATE_FINISH'] instanceof \Bitrix\Main\Type\DateTime)
			{
				$entry['ENTRIES_DAY_COMPLETE'] = true;
			}
			else
			{
				$entry['ENTRIES_DAY_COMPLETE'] = false;
				$entry['ENTRIES_DATE_FINISH'] = new \Bitrix\Main\Type\DateTime();
				$entry['ENTRIES_DURATION'] = $entry['ENTRIES_DATE_FINISH']->getTimestamp() - $entry['ENTRIES_DATE_START']->getTimestamp();
			}

			if (!$entry['DATE_FINISH'] && $entry['ENTRIES_DATE_FINISH'] instanceof \Bitrix\Main\Type\DateTime)
			{
				$entry['DATE_FINISH'] = $entry['ENTRIES_DATE_FINISH'];
				$duration = $entry['DURATION'] = $entry['DATE_FINISH']->getTimestamp() - $entry['DATE_START']->getTimestamp();
			}

			if ($entry['ENTRIES_DATE_FINISH'] instanceof \Bitrix\Main\Type\DateTime)
			{
				if (
					$entry['DATE_START'] instanceof \Bitrix\Main\Type\DateTime
					&& $entry['DATE_START']->getTimestamp() > $entry['ENTRIES_DATE_FINISH']->getTimeStamp()
				)
				{
					continue;
				}
				else if (
					$entry['DATE_FINISH'] instanceof \Bitrix\Main\Type\DateTime
					&& $entry['DATE_FINISH']->getTimestamp() > $entry['ENTRIES_DATE_FINISH']->getTimeStamp()
				)
				{
					$entry['DATE_FINISH'] = $entry['ENTRIES_DATE_FINISH'];

					$duration = $entry['DURATION'] = $entry['DATE_FINISH']->getTimestamp() - $entry['DATE_START']->getTimestamp();
				}
			}

			$workDays[$index]['INDEX'] = $index;
			$workDays[$index]['DAY_TITLE'] = $entry['DATE_START'] instanceof \Bitrix\Main\Type\DateTime? $entry['DATE_START']->format(\Bitrix\Main\Type\Date::getFormat()): '';
			$workDays[$index]['WORKDAY_DATE_START'] = $entry['ENTRIES_DATE_START'] instanceof \Bitrix\Main\Type\DateTime? $entry['ENTRIES_DATE_START']: null;
			$workDays[$index]['WORKDAY_DATE_FINISH'] = $entry['ENTRIES_DATE_FINISH'] instanceof \Bitrix\Main\Type\DateTime? $entry['ENTRIES_DATE_FINISH']: null;
			$workDays[$index]['WORKDAY_COMPLETE'] = $entry['ENTRIES_DAY_COMPLETE'];
			$workDays[$index]['WORKDAY_TIME_LEAKS_USER'] = (int)$entry['ENTRIES_TIME_LEAKS'];
			$workDays[$index]['WORKDAY_TIME_LEAKS_FINAL'] = 0;
			$workDays[$index]['WORKDAY_DURATION'] = (int)$entry['ENTRIES_DURATION'];
			$workDays[$index]['WORKDAY_DURATION_FINAL'] = 0;
			$workDays[$index]['WORKDAY_DURATION_CONFIG'] = $workdayHoursInSeconds;

			if (!isset($workDays[$index]['REPORTS']))
			{
				$workDays[$index]['REPORTS'] = [];
				$workDays[$index]['WORKDAY_TIME_LEAKS_REAL'] = 0;
			}

			$duration = (int)$duration;

			$entry['DATE_START'] = $entry['DATE_START'] instanceof \Bitrix\Main\Type\DateTime? $entry['DATE_START']: null;
			$entry['DATE_FINISH'] = $entry['DATE_FINISH'] instanceof \Bitrix\Main\Type\DateTime? $entry['DATE_FINISH']: null;

			if (
				$entry['SOURCE_START'] == self::SOURCE_TM_EVENT
				|| $entry['SOURCE_START'] == self::SOURCE_DESKTOP_OFFLINE_AGENT
				|| $entry['SOURCE_START'] == self::SOURCE_DESKTOP_ONLINE_EVENT
				|| $entry['SOURCE_START'] == self::SOURCE_DESKTOP_START_EVENT && !\Bitrix\Timeman\Common::isNetworkRange($entry['IP_START'])
			)
			{
				// ok
			}
			else if (
				$entry['DATE_FINISH'] && !$duration
				|| $duration && $duration <= $idleMinutes*60
			)
			{
				continue;
			}

			$entry['IP_START_NETWORK'] = false;
			if ($entry['IP_START'])
			{
				if ($result = \Bitrix\Timeman\Common::isNetworkRange($entry['IP_START']))
				{
					$entry['IP_START_NETWORK'] = $result;
				}
			}

			$entry['IP_FINISH_NETWORK'] = false;
			if ($entry['IP_FINISH'])
			{
				if ($result = \Bitrix\Timeman\Common::isNetworkRange($entry['IP_FINISH']))
				{
					$entry['IP_FINISH_NETWORK'] = $result;
				}
			}


			if ($entry['REPORT_TYPE'] != self::REPORT_TYPE_WORK)
			{
				$workDays[$index]['WORKDAY_TIME_LEAKS_REAL'] += $duration;
			}
			else
			{
				$workDays[$index]['WORKDAY_TIME_LEAKS_REAL'] += 0;
			}

			if ($workDays[$index]['WORKDAY_DATE_START'])
			{
//				$workdayHours
				if (!$workDays[$index]['WORKDAY_COMPLETE'] && $entry['ENTRIES_DURATION'] > 0)
				{
					$workDays[$index]['WORKDAY_DURATION'] = $workDays[$index]['WORKDAY_DURATION'] - $workDays[$index]['WORKDAY_TIME_LEAKS_USER'];
				}
				if ($workDays[$index]['WORKDAY_DATE_FINISH'] && $entry['ENTRIES_DURATION'] > 0)
				{
					if ($workDays[$index]['WORKDAY_DURATION'] > $workdayHoursInSeconds)
					{
						$workDays[$index]['WORKDAY_TIME_LEAKS_FINAL'] = $workDays[$index]['WORKDAY_DURATION'] - $workdayHoursInSeconds - $workDays[$index]['WORKDAY_TIME_LEAKS_REAL'];
						$workDays[$index]['WORKDAY_TIME_LEAKS_FINAL'] = $workDays[$index]['WORKDAY_TIME_LEAKS_FINAL'] * -1;
					}
					else
					{
						$workDays[$index]['WORKDAY_TIME_LEAKS_FINAL'] = $workdayHoursInSeconds - $workDays[$index]['WORKDAY_DURATION'] + $workDays[$index]['WORKDAY_TIME_LEAKS_REAL'];
					}

					if ($workDays[$index]['WORKDAY_TIME_LEAKS_FINAL'] > $workdayHoursInSeconds)
					{
						$workDays[$index]['WORKDAY_TIME_LEAKS_FINAL'] = $workdayHoursInSeconds;
					}
				}
			}

			if ($entry['ENTRIES_DURATION'] > 0)
			{
				$workDays[$index]['WORKDAY_DURATION_FINAL'] = $workdayHoursInSeconds-$workDays[$index]['WORKDAY_TIME_LEAKS_FINAL'];
			}

			unset($entry['ENTRIES_DATE_FINISH']);
			unset($entry['ENTRIES_DATE_START']);
			unset($entry['ENTRIES_DAY_COMPLETE']);
			unset($entry['ENTRIES_DURATION']);
			unset($entry['ENTRIES_TIME_LEAKS']);

			$workDays[$index]['REPORTS'][] = $entry;
		}

		if (!empty($workDays))
		{
			foreach ($workDays as $key => $row)
			{
				\Bitrix\Main\Type\Collection::sortByColumn(
					$workDays[$key]['REPORTS'],
					array('ID' => SORT_ASC)
				);
			}
			\Bitrix\Main\Type\Collection::sortByColumn(
				$workDays,
				array('INDEX' => SORT_ASC)
			);
		}

		return Array(
			'REPORT' => Array(
				'MONTH_TITLE' => Loc::getMessage('MONTH_'.$month),
				'DATE_START' => $dateStart,
				'DATE_FINISH' => $dateFinish,
				'DAYS' => $workDays,
			),
			'USER' => self::getUserData($userId)
		);
	}


	/* private methods */
	private static function addCalendarEntry($userId, $text, $dateStart, $dateEnd, $private = false)
	{
		if (!\Bitrix\Main\Loader::includeModule("calendar"))
			return false;

		$eventId = \CCalendar::SaveEvent(array(
			'arFields' => array(
				'CAL_TYPE' => 'user',
				'OWNER_ID' => $userId,
				'NAME' => \CTextParser::clearAllTags($text),
				'SKIP_TIME' => false,
				'DATE_FROM' => $dateStart,
				'DATE_TO' => $dateEnd,
				'PRIVATE_EVENT' => $private,
				'COLOR' => $private? '#F87396': ''
			),
			'userId' => $userId,
			'autoDetectSection' => true,
			'autoCreateSection' => true
		));

		if (!$eventId)
		{
			return false;
		}

		return $eventId;
	}

	private static function convertSecondsToMinutes($seconds)
	{
		return floor($seconds / 60);
	}

	private static function getIntersectWithCalendar($userId)
	{
		$userId = intval($userId);
		if ($userId <= 0)
		{
			return false;
		}

		if (!\Bitrix\Main\Loader::includeModule("calendar"))
		{
			return false;
		}

		$result = null;

		$now = (new \Bitrix\Main\Type\DateTime())->getTimestamp();
		$today = (new \Bitrix\Main\Type\Date())->toString();

		$entries = \CCalendar::GetAccessibilityForUsers(array(
			'users' => [$userId],
			'from' => $today,
			'to' => $today,
		));

		foreach ($entries[$userId] as $entry)
		{
			$tzFrom = $entry['TZ_FROM']? new \DateTimeZone($entry['TZ_FROM']): null;
			$tzTo = $entry['TZ_TO']? new \DateTimeZone($entry['TZ_TO']): null;

			$entryFrom = new \Bitrix\Main\Type\DateTime($entry['DATE_FROM'], null, $tzFrom);
			$entryTo = new \Bitrix\Main\Type\DateTime($entry['DATE_TO'], null, $tzTo);

			if ($entryFrom->getTimestamp() < $now && $now < $entryTo->getTimestamp())
			{
				$result = Array(
					'ID' => $entry['ID'],
					'TITLE' => $entry['NAME']? $entry['NAME']: Loc::getMessage('TIMEMAN_ABSENCE_CALENDAR_ENTRY_TITLE'),
					'DATE_FROM' => $entryFrom,
					'DATE_TO' => $entryTo,
					'ABSENCE_SCHEDULE' => $entry['FROM_HR']? true: false,
				);
				break;
			}
		}

		return $result;
	}




	/* absence events */

	public static function onImUserStatusSet(\Bitrix\Main\Event $event)
	{
		if (!self::isActive() || !self::isRegisterIdle())
		{
			return true;
		}

		$currentValues = $event->getParameters();
		$previousValues = $currentValues['PREVIOUS_VALUES'];
		unset($currentValues['PREVIOUS_VALUES']);

		if ($currentValues['IDLE'] != $previousValues['IDLE'])
		{
			if ($currentValues['IDLE'] instanceof \Bitrix\Main\Type\DateTime)
			{
				$idleDate = $currentValues['IDLE']->add('1 MINUTE');
				self::setStatusIdle($currentValues['USER_ID'], true, $idleDate);
			}
			else
			{
				self::setStatusIdle($currentValues['USER_ID'], false);
			}
		}

		if ($currentValues['DESKTOP_LAST_DATE'])
		{
			if ($currentValues['DESKTOP_LAST_DATE'] != $previousValues['DESKTOP_LAST_DATE'])
			{
				if ($currentValues['DESKTOP_LAST_DATE'] instanceof \Bitrix\Main\Type\DateTime)
				{
					self::setDesktopOnline($currentValues['USER_ID'], $currentValues['DESKTOP_LAST_DATE'], $previousValues['DESKTOP_LAST_DATE']);
				}
				else
				{
					self::setDesktopOnline($currentValues['USER_ID'], $currentValues['DESKTOP_LAST_DATE'], null);
				}
			}
		}

		return true;
	}

	public static function onImDesktopStart(\Bitrix\Main\Event $event)
	{
		if (!self::isActive() || !self::isRegisterIdle())
		{
			return true;
		}

		$params = $event->getParameters();

		if (isset(\Bitrix\Main\Application::getInstance()->getKernelSession()['IM']['SET_LAST_DESKTOP']))
		{
			return false;
		}

		$result = self::setDesktopStart($params['USER_ID']);
		if ($result)
		{
			\Bitrix\Main\Application::getInstance()->getKernelSession()['IM']['SET_LAST_DESKTOP'] = 'Y';
		}

		return true;
	}

	public static function onUserSetLastActivityDate(\Bitrix\Main\Event $event)
	{
		if (!self::isActive() || !self::isRegisterOffline())
		{
			return true;
		}

		self::setStatusOnline($event->getParameter(0), $event->getParameter(1));

		return true;
	}




	/* TimeManager events */
	public static function onUserStartWorkDay($params)
	{
		self::addTimeManagerEvent($params['ID'], $params['USER_ID'], self::TYPE_TM_START);
	}

	public static function onUserPauseWorkDay($params)
	{
		self::addTimeManagerEvent($params['ID'], $params['USER_ID'], self::TYPE_TM_PAUSE);
	}

	public static function onUserContinueWorkDay($params)
	{
		self::addTimeManagerEvent($params['ID'], $params['USER_ID'], self::TYPE_TM_CONTINUE);
	}

	public static function onUserEndWorkDay($params)
	{
		self::addTimeManagerEvent($params['ID'], $params['USER_ID'], self::TYPE_TM_END);
	}

	private static function addTimeManagerEvent($entryId, $userId, $typeId)
	{
		if (!self::isActive())
			return true;

		$todayStart = new \Bitrix\Main\Type\DateTime((new \Bitrix\Main\Type\DateTime())->format('Y-m-d').' 00:00:00', 'Y-m-d H:i:s');

		$dateStart = new \Bitrix\Main\Type\DateTime();
		$timeStart = $dateStart->getTimestamp() - $todayStart->getTimestamp();

		\Bitrix\Timeman\Model\AbsenceTable::add(Array(
			'ENTRY_ID' => $entryId,
			'USER_ID' => $userId,
			'DATE_START' => $dateStart,
			'TIME_START' => $timeStart,
			'DATE_FINISH' => $dateStart,
			'TIME_FINISH' => $timeStart,
			'IP_START' => $_SERVER['REMOTE_ADDR'],
			'IP_FINISH' => $_SERVER['REMOTE_ADDR'],
			'DURATION' => 0,
			'TYPE' => $typeId,
			'SOURCE_START' => self::SOURCE_TM_EVENT,
			'SOURCE_FINISH' => self::SOURCE_TM_EVENT,
			'ACTIVE' => 'N',
		));

		return true;
	}


	/* reports methods */

	public static function getSubordinateDepartmentId($userId)
	{
		if (!\Bitrix\Main\Loader::includeModule('intranet'))
		{
			return Array();
		}

		return \CIntranetUtils::GetSubordinateDepartments($userId, true);
	}

	public static function getSubordinateDepartments($userId)
	{
		if (
			!\Bitrix\Main\Loader::includeModule('intranet')
			|| !\Bitrix\Main\Loader::includeModule('iblock')
		)
		{
			return Array();
		}

		$departmentId = self::getSubordinateDepartmentId($userId);

		$departments = [];
		if (!empty($departmentId) || Common::isAdmin())
		{
			$filter = array(
				"ID" => $departmentId,
				"IBLOCK_ID" => \COption::GetOptionInt('intranet', 'iblock_structure', 0)
			);
			if (Common::isAdmin())
			{
				unset($filter["ID"]);
			}
			$result = \CIBlockSection::GetList(
				array('LEFT_MARGIN' => 'ASC'),
				$filter,
				false,
				array('ID', 'NAME', 'IBLOCK_SECTION_ID', 'UF_HEAD', 'LEFT_MARGIN')
			);
			while ($row = $result->Fetch())
			{
				$departments[] = Array(
					'ID' => $row['ID'],
					'NAME' => $row['NAME'],
				);
			}
		}

		return $departments;
	}

	public static function getSubordinateUsers($departmentId, $userId)
	{
		$isAdmin = self::isAdmin();
		$nameTemplateSite = \CSite::GetNameFormat(false);
		$users = [];

		$subordinateDepartments = self::getSubordinateDepartmentId($userId);
		if (
			!$isAdmin && empty($subordinateDepartments)
			|| $isAdmin && !$departmentId
		)
		{
			$users[] = self::getUserData($userId);
		}
		else
		{
			if ($isAdmin || in_array($departmentId, $subordinateDepartments))
			{
				$res = \CIntranetUtils::getDepartmentEmployees([$departmentId], false, false, 'Y');
				while ($row = $res->fetch())
				{
					$avatar = \CFile::ResizeImageGet(
						$row["PERSONAL_PHOTO"],
						array('width' => 100, 'height' => 100),
						BX_RESIZE_IMAGE_EXACT
					);

					$users[] = Array(
						'ID' => (int)$row['ID'],
						'NAME' => \CUser::FormatName($nameTemplateSite, $row, true, false),
						'FIRST_NAME' => $row['NAME'],
						'LAST_NAME' => $row['LAST_NAME'],
						'WORK_POSITION' => $row['WORK_POSITION'],
						'AVATAR' => $avatar['src'],
						'PERSONAL_GENDER' => $row['PERSONAL_GENDER'] == 'M'? 'M': 'F',
						'LAST_ACTIVITY_DATE' => $row['LAST_ACTIVITY_DATE']? DateTime::createFromTimestamp(MakeTimeStamp($row['LAST_ACTIVITY_DATE'], 'YYYY-MM-DD HH:MI:SS')): null,
					);
				}
			}
		}

		return $users;
	}

	public static function getUserData($userId)
	{
		$nameTemplateSite = \CSite::GetNameFormat(false);

		$row = \Bitrix\Main\UserTable::getById($userId)->fetch();
		if (!$row)
		{
			return false;
		}

		$avatar = \CFile::ResizeImageGet(
			$row["PERSONAL_PHOTO"],
			array('width' => 100, 'height' => 100),
			BX_RESIZE_IMAGE_EXACT
		);

		return Array(
			'ID' => (int)$row['ID'],
			'ACTIVE' => $row['ACTIVE'] != 'N',
			'NAME' => \CUser::FormatName($nameTemplateSite, $row, true, false),
			'FIRST_NAME' => $row['NAME'],
			'LAST_NAME' => $row['LAST_NAME'],
			'WORK_POSITION' => $row['WORK_POSITION'],
			'AVATAR' => $avatar['src'],
			'PERSONAL_GENDER' => $row['PERSONAL_GENDER'] == 'M'? 'M': 'F',
			'LAST_ACTIVITY_DATE' => $row['LAST_ACTIVITY_DATE']? $row['LAST_ACTIVITY_DATE']: null,
		);
	}

	public static function hasAccessToReport($userId)
	{
		$currentUserId = $GLOBALS['USER']->GetID();

		if ($currentUserId == $userId || self::isAdmin())
		{
			return true;
		}

		if (
			!\Bitrix\Main\Loader::includeModule('intranet')
			|| !\Bitrix\Main\Loader::includeModule('iblock')
		)
		{
			return false;
		}

		$departments = \CIntranetUtils::GetSubordinateDepartments($currentUserId, true);

		$res = \CIntranetUtils::getDepartmentEmployees($departments, false, false);
		while ($row = $res->fetch())
		{
			if ($row['ID'] == $userId)
			{
				return true;
			}
		}

		return false;
	}


	public static function isHead()
	{
		$subordinateDepartments = self::getSubordinateDepartments($GLOBALS['USER']->GetID());
		if ($subordinateDepartments)
		{
			$isHead = true;
		}
		else
		{
			$isHead = self::isAdmin();
		}

		return $isHead;
	}

	public static function isAdmin()
	{
		return $GLOBALS['USER']->IsAdmin() || \Bitrix\Main\Loader::includeModule('bitrix24') && \CBitrix24::IsPortalAdmin($GLOBALS['USER']->GetID());
	}


	/* agents */

	public static function searchOfflineUsersWithActiveDayAgent()
	{
		if (!self::isActive() || !self::isRegisterOffline())
		{
			return "\Bitrix\Timeman\Absence::searchOfflineUsersWithActiveDayAgent();";
		}

		$dateStart = (new \Bitrix\Main\Type\DateTime())->format('Y-m-d').' 00:00:00';

		$orm = \Bitrix\Timeman\Model\EntriesTable::getList(Array(
			'select' => Array(
				'ID',
				'USER_ID',
				'DATE_START',
				'USER_LAST_ACTIVITY_DATE' => 'USER.LAST_ACTIVITY_DATE',
				'ABSENCE_ID' => 'ABSENCE.ID',
				'ABSENCE_TIME_START' => 'ABSENCE.TIME_START',
				'ABSENCE_DATE_START' => 'ABSENCE.DATE_START',
			),
			'filter' => Array(
				'>=DATE_START' => new \Bitrix\Main\DB\SqlExpression('?', $dateStart),
				'=DATE_FINISH' => null,
				'=USER.IS_ONLINE' => 'N',
				'!=ABSENCE.TYPE' => self::TYPE_OFFLINE
			),
			'runtime' => Array(
				new \Bitrix\Main\Entity\ReferenceField(
					'ABSENCE',
					'\Bitrix\Timeman\Model\AbsenceTable',
					array(
						"=ref.USER_ID" => "this.USER_ID",
						"=ref.ACTIVE" => new \Bitrix\Main\DB\SqlExpression('?', 'Y'),
						">=ref.DATE_START" => new \Bitrix\Main\DB\SqlExpression('?', $dateStart),
					),
					array("join_type"=>"left")
				)
			)
		));

		$todayStart = new \Bitrix\Main\Type\DateTime((new \Bitrix\Main\Type\DateTime())->format('Y-m-d').' 00:00:00', 'Y-m-d H:i:s');

		$offlineRecordAdded = false;
		while ($entry = $orm->fetch())
		{
			if ($entry['USER_LAST_ACTIVITY_DATE'] instanceof \Bitrix\Main\Type\DateTime)
			{
				$dateStart = $entry['USER_LAST_ACTIVITY_DATE'];
			}
			else
			{
				$dateStart = new \Bitrix\Main\Type\DateTime();
				$dateStart->add('-'.\Bitrix\Main\UserTable::getSecondsForLimitOnline().' SECONDS');
			}

			if ($dateStart->getTimestamp() < $entry['DATE_START']->getTimestamp())
			{
				$dateStart = $entry['DATE_START'];
			}

			if ($entry['ABSENCE_ID'])
			{
				$dateFinish = $dateStart;
				$timeFinish = $dateFinish->getTimestamp() - $todayStart->getTimestamp();
				$duration = $timeFinish - $entry['ABSENCE_TIME_START'];

				\Bitrix\Timeman\Model\AbsenceTable::update($entry['ABSENCE_ID'], Array(
					'ACTIVE' => 'N',
					'DATE_FINISH' => $dateFinish,
					'TIME_FINISH' => $timeFinish,
					'DURATION' => $duration,
					'SOURCE_FINISH' => self::SOURCE_OFFLINE_AGENT,
				));

				if (self::isReportEnableForUser($entry['USER_ID'], self::convertSecondsToMinutes($duration)))
				{
					\Bitrix\Pull\Event::add($entry['USER_ID'], Array(
						'module_id' => 'timeman',
						'command' => 'timeControlCommitAbsence',
						'params' => Array(
							'absenceId' => $entry['ABSENCE_ID'],
							'dateStart' => date('c', $entry['ABSENCE_DATE_START']->getTimestamp()),
							'dateFinish' => date('c', $dateFinish->getTimestamp()),
							'duration' => $duration
						)
					));

					if (false) // TODO remove debug case
					{
						\Bitrix\Main\IO\File::putFileContents(
							$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/timecontrol-debug.log",
							print_r([
								'ACTION' => 'OFFLINE',
								'USER_ID' => $entry['USER_ID'],
								Array(
									'absenceId' => $entry['ABSENCE_ID'],
									'dateStart' => date('c', $entry['ABSENCE_DATE_START']->getTimestamp()),
									'dateFinish' => date('c', $dateFinish->getTimestamp()),
									'duration' => $duration
								)
							], 1),
							\Bitrix\Main\IO\File::APPEND
						);
					}
				}
			}

			if (!$offlineRecordAdded)
			{
				$timeStart = $dateStart->getTimestamp() - $todayStart->getTimestamp();

				\Bitrix\Timeman\Model\AbsenceTable::add(Array(
					'ENTRY_ID' => $entry['ID'],
					'USER_ID' => $entry['USER_ID'],
					'DATE_START' => $dateStart,
					'TIME_START' => $timeStart,
					'TYPE' => self::TYPE_OFFLINE,
					'SOURCE_START' => self::SOURCE_OFFLINE_AGENT,
				));

				$offlineRecordAdded = true;
			}
		}

		return "\Bitrix\Timeman\Absence::searchOfflineUsersWithActiveDayAgent();";
	}

	public static function searchOfflineDesktopUsersWithActiveDayAgent()
	{
		if (
			!self::isActive()
			|| !self::isRegisterDesktop()
			|| !\Bitrix\Main\Loader::includeModule('im')
		)
		{
			return "\Bitrix\Timeman\Absence::searchOfflineDesktopUsersWithActiveDayAgent();";
		}

		$desktopLastDate = (new \Bitrix\Main\Type\DateTime())->format('Y-m-d').' 00:00:00';

		$filter = Array(
			'>=DATE_START' => new \Bitrix\Main\DB\SqlExpression('?', $desktopLastDate),
			'=DATE_FINISH' => null,
			'=USER.IS_ONLINE' => 'Y',
		);

		$requestReport = \Bitrix\Main\Config\Option::get('timeman', 'request_report', "0");
		if ($requestReport == "1")
		{
		}
		else if ($requestReport == "0")
		{
			return "\Bitrix\Timeman\Absence::searchOfflineDesktopUsersWithActiveDayAgent();";
		}
		else
		{
			$filter['=USER_ID'] = Json::decode($requestReport);
		}

		$orm = \Bitrix\Timeman\Model\EntriesTable::getList(Array(
			'select' => Array(
				'ID',
				'USER_ID',
				'DATE_START',
				'DESKTOP_LAST_DATE' => 'STATUS.DESKTOP_LAST_DATE',
				'ABSENCE_ID' => 'ABSENCE.ID',
				'ABSENCE_ACTIVE' => 'ABSENCE.ACTIVE',
				'ABSENCE_TYPE' => 'ABSENCE.TYPE',
				'ABSENCE_TIME_START' => 'ABSENCE.TIME_START',
				'ABSENCE_DATE_START' => 'ABSENCE.DATE_START',
			),
			'filter' => $filter,
			'runtime' => Array(
				new \Bitrix\Main\Entity\ReferenceField(
					'ABSENCE',
					'\Bitrix\Timeman\Model\AbsenceTable',
					array(
						"=ref.USER_ID" => "this.USER_ID",
						">=ref.DATE_START" => new \Bitrix\Main\DB\SqlExpression('?', $desktopLastDate),
					),
					array("join_type"=>"left")
				),
				new \Bitrix\Main\Entity\ReferenceField(
					'STATUS',
					'\Bitrix\Im\Model\StatusTable',
					array(
						"=ref.USER_ID" => "this.USER_ID",
					),
					array("join_type"=>"left")
				)
			),
		));

		$todayStart = new \Bitrix\Main\Type\DateTime((new \Bitrix\Main\Type\DateTime())->format('Y-m-d').' 00:00:00', 'Y-m-d H:i:s');

		$users = [];
		while($entry = $orm->fetch())
		{
			if (
				isset($users[$entry['USER_ID']])
				&& (int)$users[$entry['USER_ID']]['ABSENCE_ID'] >= (int)$entry['ABSENCE_ID']
			)
			{
				continue;
			}

			$users[$entry['USER_ID']] = $entry;
		}

		foreach ($users as $userId => $entry)
		{
			if (in_array($entry['ABSENCE_TYPE'], [self::TYPE_DESKTOP_OFFLINE, self::TYPE_OFFLINE]))
			{
				continue;
			}

			$dateNow = new \Bitrix\Main\Type\DateTime();

			$dateCheck = new \Bitrix\Main\Type\DateTime();
			$dateCheck->add('-'.\Bitrix\Main\UserTable::getSecondsForLimitOnline().' SECONDS');

			$desktopLastDateServer = $entry['DESKTOP_LAST_DATE'] instanceof \Bitrix\Main\Type\DateTime? $entry['DESKTOP_LAST_DATE']: false;
			$desktopLastDate = $desktopLastDateServer? $entry['DESKTOP_LAST_DATE']: $dateCheck;

			if ($desktopLastDate->getTimestamp() > $dateCheck->getTimestamp())
			{
				continue;
			}

			// give 10 minutes to get from the authorization terminal to the workplace
			if (
				$desktopLastDate->getTimestamp() < $entry['DATE_START']->getTimestamp()
				&& $dateNow->getTimestamp() < $entry['DATE_START']->getTimestamp()+600
			)
			{
				continue;
			}

			// if the time of the last activity of the desktop is less than the beginning of the working day
			if ($desktopLastDate->getTimestamp() < $entry['DATE_START']->getTimestamp())
			{
				$desktopLastDate = $dateNow;
			}

			$desktopLastTime = $desktopLastDate->getTimestamp() - $todayStart->getTimestamp();
			$desktopLastTimeText = ($desktopLastDateServer? $desktopLastDateServer->format(DateTime::getFormat()): Loc::getMessage('TIMEMAN_ABSENCE_EMPTY_INFO'));

			\Bitrix\Timeman\Model\AbsenceTable::add(Array(
				'ENTRY_ID' => $entry['ID'],
				'USER_ID' => $entry['USER_ID'],
				'DATE_START' => $desktopLastDate,
				'TIME_START' => $desktopLastTime,
				'DATE_FINISH' => $desktopLastDate,
				'TIME_FINISH' => $desktopLastTime,
				'DURATION' => 0,
				'ACTIVE' => 'N',
				'SYSTEM_TEXT' => Loc::getMessage('TIMEMAN_ABSENCE_TEXT_DESKTOP_LAST_DATE', ['#TIME#' => $desktopLastTimeText]),
				'TYPE' => self::TYPE_DESKTOP_OFFLINE,
				'SOURCE_START' => self::SOURCE_DESKTOP_OFFLINE_AGENT,
			));
		}

		return "\Bitrix\Timeman\Absence::searchOfflineDesktopUsersWithActiveDayAgent();";
	}

	private static function formatDuration($seconds)
	{
		$seconds = intval($seconds);

		$full = $seconds / 3600;
		$hour = floor($full);
		$min = floor((3600 * ($full - $hour)) / 60);

		$langHour = ' '.Loc::getMessage('TIMEMAN_ABSENCE_FORMAT_HOUR');
		$langMin = ' '.Loc::getMessage('TIMEMAN_ABSENCE_FORMAT_MINUTE');

		$result = ($hour > 0? $hour.$langHour: '').($min > 0? ' '.$min.$langMin: '');
		$result = trim($result);

		return $result? $result: Loc::getMessage('TIMEMAN_ABSENCE_FORMAT_LESS_MINUTE');
	}

	public static function getReportUsers()
	{
		$enableType = self::getOptionReportEnableType();
		if ($enableType !== self::TYPE_FOR_USER)
		{
			return $enableType;
		}

		$reportUsers = \Bitrix\Main\Config\Option::get('timeman', 'request_report', '0');
		return Json::decode($reportUsers);
	}

	public static function disableForUsers($userIds)
	{
		if (!is_array($userIds))
		{
			$userIds = [$userIds];
		}

		$reportUsers = self::getReportUsers();
		if ($reportUsers === self::TYPE_NONE)
		{
			return null;
		}

		if ($reportUsers === self::TYPE_ALL)
		{
			$reportSkipUsers = \Bitrix\Main\Config\Option::get('timeman', 'skip_report', '0');

			if ($reportSkipUsers === '0')
			{
				return self::setOptionSkipReport($userIds);
			}

			$reportSkipUsers = array_unique(array_merge(Json::decode($reportSkipUsers), $userIds));

			return self::setOptionSkipReport($reportSkipUsers);
		}

		$reportUsers = array_diff($reportUsers, $userIds);

		return self::setOptionRequestReport($reportUsers);
	}

	public static function disableForAll(): void
	{
		self::setOptionRequestReport(false);
	}
}