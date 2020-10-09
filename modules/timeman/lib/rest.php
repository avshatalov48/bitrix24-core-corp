<?php
namespace Bitrix\Timeman;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Main\Type\Date;
use Bitrix\Rest\AccessException;
use Bitrix\Rest\RestException;

Loader::includeModule('rest');

class Rest extends \IRestService
{
	const SCOPE = 'timeman';

	public static function onRestServiceBuildDescription()
	{
		return array(
			static::SCOPE => array(
				'timeman.settings' => array('callback' => array(__CLASS__, 'getSettings')),
				'timeman.status' => array('callback' => array(__CLASS__, 'getStatus')),
				'timeman.open' => array('callback' => array(__CLASS__, 'openDay')),
				'timeman.close' => array('callback' => array(__CLASS__, 'closeDay')),
				'timeman.pause' => array('callback' => array(__CLASS__, 'pauseDay')),

				'timeman.networkrange.get' => array('callback' => array(__CLASS__, 'networkRangeGet')),
				'timeman.networkrange.set' => array('callback' => array(__CLASS__, 'networkRangeSet')),
				'timeman.networkrange.check' => array('callback' => array(__CLASS__, 'networkRangeCheck')),

				'timeman.timecontrol.settings.get'=> array('callback' => array(__CLASS__, 'timeControlSettingsGet')),
				'timeman.timecontrol.settings.set'=> array('callback' => array(__CLASS__, 'timeControlSettingsSet')),
				'timeman.timecontrol.report.add'=> array('callback' => array(__CLASS__, 'timeControlReportAdd')),
				'timeman.timecontrol.reports.settings.get'=> array('callback' => array(__CLASS__, 'timeControlReportsSettingsGet')),
				'timeman.timecontrol.reports.users.get'=> array('callback' => array(__CLASS__, 'timeControlReportsUsersGet')),
				'timeman.timecontrol.reports.get'=> array('callback' => array(__CLASS__, 'timeControlReportsGet')),
				'timeman.timecontrol.report' =>  array('callback' => array(__CLASS__, 'timeControlReportAdd'), 'options' => array('private' => true)),
			)
		);
	}

	public static function getSettings($query, $n, \CRestServer $server)
	{
		global $USER;

		$query = static::prepareQuery($query);
		$tmUser = static::getUserInstance($query);

		$currentSettings = $tmUser->getSettings();

		// temporary fix timeman bug
		if(mb_strpos($currentSettings['UF_TM_ALLOWED_DELTA'], ':') !== false)
		{
			$currentSettings['UF_TM_ALLOWED_DELTA'] = \CTimeMan::MakeShortTS($currentSettings['UF_TM_ALLOWED_DELTA']);
		}

		$result = array(
			'UF_TIMEMAN' => $currentSettings['UF_TIMEMAN'],
			'UF_TM_FREE' => $currentSettings['UF_TM_FREE'],
			'UF_TM_MAX_START' => static::formatTime($currentSettings['UF_TM_MAX_START']),
			'UF_TM_MIN_FINISH' => static::formatTime($currentSettings['UF_TM_MIN_FINISH']),
			'UF_TM_MIN_DURATION' => static::formatTime($currentSettings['UF_TM_MIN_DURATION']),
			'UF_TM_ALLOWED_DELTA' => static::formatTime($currentSettings['UF_TM_ALLOWED_DELTA']),
		);

		if($USER->GetID() == $tmUser->GetID())
		{
			$result['ADMIN'] = \CTimeMan::IsAdmin();
		}

		return $result;
	}

	public static function getStatus($query, $n, \CRestServer $server)
	{
		$query = static::prepareQuery($query);
		$tmUser = static::getUserInstance($query);

		$currentInfo = $tmUser->getCurrentInfo();

		$result = array(
			'STATUS' => $tmUser->State(),
		);

		$userOffset = $tmUser->getDayStartOffset($currentInfo) + date('Z');
		static::setCurrentTimezoneOffset($userOffset);

		if($currentInfo['DATE_START'])
		{

			$currentInfo['DATE_START'] = ConvertTimeStamp(MakeTimeStamp($currentInfo['DATE_START'], FORMAT_DATETIME), 'SHORT');

			if($currentInfo['DATE_FINISH'])
			{
				$currentInfo['DATE_FINISH'] = ConvertTimeStamp(MakeTimeStamp($currentInfo['DATE_FINISH'], FORMAT_DATETIME), 'SHORT');
			}

			$result['TIME_START'] = static::convertTimeToISO(intval($currentInfo['TIME_START']), $currentInfo['DATE_START'], $userOffset);
			$result['TIME_FINISH'] = $currentInfo['TIME_FINISH'] > 0 ? static::convertTimeToISO(intval($currentInfo['TIME_FINISH']), $currentInfo['DATE_FINISH'], $userOffset) : null;
			$result['DURATION'] = static::formatTime(intval($currentInfo['DURATION']));
			$result['TIME_LEAKS'] = static::formatTime(intval($currentInfo['TIME_LEAKS']));
			$result['ACTIVE'] = $currentInfo['ACTIVE'] == 'Y';
			$result['IP_OPEN'] = $currentInfo['IP_OPEN'];
			$result['IP_CLOSE'] = $currentInfo['IP_CLOSE'];
			$result['LAT_OPEN'] = doubleval($currentInfo['LAT_OPEN']);
			$result['LON_OPEN'] = doubleval($currentInfo['LON_OPEN']);
			$result['LAT_CLOSE'] = doubleval($currentInfo['LAT_CLOSE']);
			$result['LON_CLOSE'] = doubleval($currentInfo['LON_CLOSE']);
			$result['TZ_OFFSET'] = $userOffset;
		}

		if($result['STATUS'] == 'EXPIRED')
		{
			$result['TIME_FINISH_DEFAULT'] = static::convertTimeToISO($tmUser->getExpiredRecommendedDate(), $currentInfo['DATE_START'], $userOffset);
		}

		return $result;
	}

	public static function pauseDay($query, $n, \CRestServer $server)
	{
		$query = static::prepareQuery($query);
		$tmUser = static::getUserInstance($query);

		$currentInfo = $tmUser->getCurrentInfo();

		$userOffset = $tmUser->getDayStartOffset($currentInfo) + date('Z');
		static::setCurrentTimezoneOffset($userOffset);

		$tmUser->PauseDay();

		return static::getStatus($query, $n, $server);
	}

	public static function openDay($query, $n, \CRestServer $server)
	{
		$query = static::prepareQuery($query);
		$tmUser = static::getUserInstance($query);

		$openAction = $tmUser->OpenAction();

		$result = false;
		if($openAction)
		{
			if($openAction === 'OPEN')
			{
				if(isset($query['TIME']))
				{
					$timeInfo = static::convertTimeFromISO($query['TIME']);
					static::setCurrentTimezoneOffset($timeInfo['OFFSET']);

					if(!static::checkDate($timeInfo, ConvertTimeStamp()))
					{
						throw new DateTimeException('Day open date should correspond to the current date', DateTimeException::ERROR_WRONG_DATETIME);
					}

					$result = $tmUser->openDay($timeInfo['TIME'], $query['REPORT']);
				}
				else
				{
					$result = $tmUser->openDay();
				}

				if($result !== false)
				{
					static::setDayGeoPosition($result['ID'], $query, 'open');
				}
			}
			elseif($openAction === 'REOPEN')
			{
				if(isset($query['TIME']))
				{
					throw new ArgumentException('Unable to set time, work day is paused', 'TIME');
				}

				$currentInfo = $tmUser->getCurrentInfo();
				$userOffset = $tmUser->getDayStartOffset($currentInfo) + date('Z');

				static::setCurrentTimezoneOffset($userOffset);

				$result = $tmUser->ReopenDay();
			}
		}

		if(!$result)
		{
			global $APPLICATION;
			$ex = $APPLICATION->GetException();
			if($ex)
			{
				throw new RestException($ex->GetString(), $ex->GetID());
			}
		}

		return static::getStatus($query, $n, $server);
	}

	public static function closeDay($query, $n, \CRestServer $server)
	{
		$query = static::prepareQuery($query);
		$tmUser = static::getUserInstance($query);

		if(isset($query['TIME']))
		{
			$currentInfo = $tmUser->getCurrentInfo();
			$userOffset = $tmUser->getDayStartOffset($currentInfo) + date('Z');

			static::setCurrentTimezoneOffset($userOffset);

			$timeInfo = static::convertTimeFromISO($query['TIME']);

			static::correctTimeOffset($userOffset, $timeInfo);

			if(!static::checkDate($timeInfo, ConvertTimeStamp(MakeTimeStamp($currentInfo['DATE_START'], FORMAT_DATETIME))))
			{
				throw new DateTimeException('Day close date should correspond to the day open date', DateTimeException::ERROR_WRONG_DATETIME);
			}

			$result = $tmUser->CloseDay($timeInfo['TIME'], trim($query['REPORT']));
		}
		else
		{
			$result = $tmUser->CloseDay();
		}

		if(!$result)
		{
			global $APPLICATION;
			$ex = $APPLICATION->GetException();
			if($ex)
			{
				throw new RestException($ex->GetString(), $ex->GetID());
			}
		}
		else
		{
			static::setDayGeoPosition($result['ID'], $query, 'close');

			$currentInfo = $tmUser->GetCurrentInfo();

			$reportData = $tmUser->SetReport('', 0, $currentInfo['ID']);

			$dailyReportFields = array(
				'ENTRY_ID' => $currentInfo['ID'],
				'REPORT_DATE' => $currentInfo['DATE_START'],
				'ACTIVE' => $currentInfo['ACTIVE'],
				'REPORT' => $reportData['REPORT'],
			);

			\CTimeManReportDaily::Add($dailyReportFields);
		}

		return static::getStatus($query, $n, $server);
	}



	public static function networkRangeGet($query, $n, \CRestServer $server)
	{
		if (!self::isAdmin())
		{
			throw new \Bitrix\Rest\RestException("You don't have access to user this method", "ACCESS_ERROR", \CRestServer::STATUS_WRONG_REQUEST);
		}

		return \Bitrix\Timeman\Common::getOptionNetworkRange();
	}

	public static function networkRangeSet($query, $n, \CRestServer $server)
	{
		if (!self::isAdmin())
		{
			throw new \Bitrix\Rest\RestException("You don't have access to user this method", "ACCESS_ERROR", \CRestServer::STATUS_WRONG_REQUEST);
		}

		$query = static::prepareQuery($query);

		if (is_string($query['RANGES']))
		{
			$query['RANGES'] = \CUtil::JsObjectToPhp($query['RANGES']);
		}
		$result = \Bitrix\Timeman\Common::checkOptionNetworkRange($query['RANGES']);
		if (!$result)
		{
			throw new \Bitrix\Rest\RestException("A wrong format for the RANGES field is passed", "INVALID_FORMAT", \CRestServer::STATUS_WRONG_REQUEST);
		}
		if (count($result['ERROR']) > 0)
		{
			$result = Array(
				'result' => false,
				'error_ranges' =>  $result['ERROR'],
			);
		}
		else
		{
			$result = Array(
				'result' => \Bitrix\Timeman\Common::setOptionNetworkRange($result['CORRECT'])
			);
		}


		return $result;
	}

	public static function networkRangeCheck($query, $n, \CRestServer $server)
	{
		if (!self::isAdmin())
		{
			throw new \Bitrix\Rest\RestException("You don't have access to user this method", "ACCESS_ERROR", \CRestServer::STATUS_WRONG_REQUEST);
		}

		$query = static::prepareQuery($query);

		$result = \Bitrix\Timeman\Common::isNetworkRange($query['IP']);
		if ($result)
		{
			$result = array_change_key_case($result, CASE_LOWER);
		}

		return $result;
	}

	public static function timeControlReportAdd($query, $n, \CRestServer $server)
	{
		$query = static::prepareQuery($query);

		$absenceId = isset($query['REPORT_ID'])? $query['REPORT_ID']: $query['ID'];
		$userId = null;

		if (self::isAdmin() && intval($query['USER_ID']) > 0)
		{
			$userId = intval($query['USER_ID']);
			$result = \Bitrix\Timeman\Model\AbsenceTable::getById($absenceId)->fetch();
			if ($result['USER_ID'] != $userId)
			{
				throw new \Bitrix\Rest\RestException("You don't have access for this report", "ACCESS_ERROR", \CRestServer::STATUS_WRONG_REQUEST);
			}
		}

		$text = $query['TEXT'];
		$type = mb_strtoupper($query['TYPE']) == \Bitrix\Timeman\Absence::REPORT_TYPE_WORK? \Bitrix\Timeman\Absence::REPORT_TYPE_WORK: \Bitrix\Timeman\Absence::REPORT_TYPE_PRIVATE;
		$addToCalendar = $query['CALENDAR'] === 'N'? false: (bool)$query['CALENDAR'];

		$text = trim($text);
		if ($text == '')
		{
			throw new \Bitrix\Rest\RestException("Text can't be empty", "TEXT_EMPTY", \CRestServer::STATUS_WRONG_REQUEST);
		}



		\Bitrix\Timeman\Absence::addReport($absenceId, $text, $type, $addToCalendar, $userId);

		return true;
	}

	public static function timeControlSettingsGet($query, $n, \CRestServer $server)
	{
		if (!self::isAdmin())
		{
			throw new \Bitrix\Rest\RestException("You don't have access to user this method", "ACCESS_ERROR", \CRestServer::STATUS_WRONG_REQUEST);
		}

		return Array(
			'active' => \Bitrix\Timeman\Absence::isActive(),
			'minimum_idle_for_report' => \Bitrix\Timeman\Absence::getMinimumIdleForReport(),

			'register_offline' => \Bitrix\Timeman\Absence::isRegisterOffline(),
			'register_idle' => \Bitrix\Timeman\Absence::isRegisterIdle(),
			'register_desktop' => \Bitrix\Timeman\Absence::isRegisterDesktop(),

			'report_request_type' => mb_strtolower(\Bitrix\Timeman\Absence::getOptionReportEnableType()),
			'report_request_users' => \Bitrix\Timeman\Absence::getOptionReportEnableUsers(),

			'report_simple_type' => mb_strtolower(\Bitrix\Timeman\Absence::getOptionReportListSimpleType()),
			'report_simple_users' => \Bitrix\Timeman\Absence::getOptionReportListSimpleUsers(),

			'report_full_type' => mb_strtolower(\Bitrix\Timeman\Absence::getOptionReportListFullType()),
			'report_full_users' => \Bitrix\Timeman\Absence::getOptionReportListFullUsers(),
		);
	}

	public static function timeControlSettingsSet($query, $n, \CRestServer $server)
	{
		if (!self::isAdmin())
		{
			throw new \Bitrix\Rest\RestException("You don't have access to user this method", "ACCESS_ERROR", \CRestServer::STATUS_WRONG_REQUEST);
		}

		$query = static::prepareQuery($query);

		if (array_key_exists('ACTIVE', $query))
		{
			\Bitrix\Timeman\Absence::setOptionActive((bool)$query['ACTIVE']);
		}
		if (array_key_exists('MINIMUM_IDLE_FOR_REPORT', $query))
		{
			\Bitrix\Timeman\Absence::setOptionMinimumIdleForReport((int)$query['MINIMUM_IDLE_FOR_REPORT']);
		}

		if (array_key_exists('REGISTER_OFFLINE', $query))
		{
			\Bitrix\Timeman\Absence::setOptionRegisterOffline((bool)$query['REGISTER_OFFLINE']);
		}
		if (array_key_exists('REGISTER_IDLE', $query))
		{
			\Bitrix\Timeman\Absence::setOptionRegisterIdle((bool)$query['REGISTER_IDLE']);
		}
		if (array_key_exists('REGISTER_DESKTOP', $query))
		{
			\Bitrix\Timeman\Absence::setOptionRegisterDesktop((bool)$query['REGISTER_DESKTOP']);
		}

		if (array_key_exists('REPORT_REQUEST_TYPE', $query))
		{
			if (mb_strtoupper($query['REPORT_REQUEST_TYPE']) == \Bitrix\Timeman\Absence::TYPE_ALL)
			{
				\Bitrix\Timeman\Absence::setOptionRequestReport(true);
			}
			else if (mb_strtoupper($query['REPORT_REQUEST_TYPE']) == \Bitrix\Timeman\Absence::TYPE_FOR_USER)
			{
				if (array_key_exists('REPORT_REQUEST_USERS', $query))
				{
					if (is_string($query['REPORT_REQUEST_USERS']))
					{
						$query['REPORT_REQUEST_USERS'] = \CUtil::JsObjectToPhp($query['REPORT_REQUEST_USERS']);
					}
					\Bitrix\Timeman\Absence::setOptionRequestReport($query['REPORT_REQUEST_USERS']);
				}
				else
				{
					\Bitrix\Timeman\Absence::setOptionRequestReport([]);
				}
			}
			else
			{
				\Bitrix\Timeman\Absence::setOptionRequestReport(false);
			}
		}

		if (array_key_exists('REPORT_SIMPLE_TYPE', $query))
		{
			if (mb_strtoupper($query['REPORT_SIMPLE_TYPE']) == \Bitrix\Timeman\Absence::TYPE_ALL)
			{
				\Bitrix\Timeman\Absence::setOptionReportListSimple(true);
			}
			else if (mb_strtoupper($query['REPORT_SIMPLE_TYPE']) == \Bitrix\Timeman\Absence::TYPE_FOR_USER)
			{
				if (array_key_exists('REPORT_SIMPLE_USERS', $query))
				{
					if (is_string($query['REPORT_SIMPLE_USERS']))
					{
						$query['REPORT_SIMPLE_USERS'] = \CUtil::JsObjectToPhp($query['REPORT_SIMPLE_USERS']);
					}
					\Bitrix\Timeman\Absence::setOptionReportListSimple($query['REPORT_SIMPLE_USERS']);
				}
				else
				{
					\Bitrix\Timeman\Absence::setOptionReportListSimple([]);
				}
			}
			else
			{
				\Bitrix\Timeman\Absence::setOptionReportListSimple([]);
			}
		}

		if (array_key_exists('REPORT_FULL_TYPE', $query))
		{
			if (mb_strtoupper($query['REPORT_FULL_TYPE']) == \Bitrix\Timeman\Absence::TYPE_ALL)
			{
				\Bitrix\Timeman\Absence::setOptionReportListFull(true);
			}
			else if (mb_strtoupper($query['REPORT_FULL_TYPE']) == \Bitrix\Timeman\Absence::TYPE_FOR_USER)
			{
				if (array_key_exists('REPORT_FULL_USERS', $query))
				{
					if (is_string($query['REPORT_FULL_USERS']))
					{
						$query['REPORT_FULL_USERS'] = \CUtil::JsObjectToPhp($query['REPORT_FULL_USERS']);
					}
					\Bitrix\Timeman\Absence::setOptionReportListFull($query['REPORT_FULL_USERS']);
				}
				else
				{
					\Bitrix\Timeman\Absence::setOptionReportListFull([]);
				}
			}
			else
			{
				\Bitrix\Timeman\Absence::setOptionReportListFull([]);
			}
		}



		return true;
	}

	public static function timeControlReportsSettingsGet($query, $n, \CRestServer $server)
	{
		$userId = $GLOBALS['USER']->GetId();
		$subordinateDepartments = \Bitrix\Timeman\Absence::getSubordinateDepartments($userId);
		foreach ($subordinateDepartments as $id => $value)
		{
			$subordinateDepartments[$id] = array_change_key_case($value, CASE_LOWER);
		}

		$isAdmin = self::isAdmin();
		$isHead = $subordinateDepartments || $isAdmin;

		$reportViewType = 'none';
		if ($isHead)
		{
			$reportViewType = 'head';
		}
		else if (\Bitrix\Timeman\Absence::isReportListFullEnableForUser($userId))
		{
			$reportViewType = 'full';
		}
		else if (\Bitrix\Timeman\Absence::isReportListSimpleEnableForUser($userId))
		{
			$reportViewType = 'simple';
		}

		return Array(
			'active' => \Bitrix\Timeman\Absence::isActive(),
			'user_id' => (int)$userId,
			'user_admin' => $isAdmin,
			'user_head' => $isHead,
			'departments' => $subordinateDepartments,
			'minimum_idle_for_report' => \Bitrix\Timeman\Absence::getMinimumIdleForReport(),
			'report_view_type' => $reportViewType,
		);
	}

	public static function timeControlReportsUsersGet($query, $n, \CRestServer $server)
	{
		$query = static::prepareQuery($query);

		$userId = $GLOBALS['USER']->GetId();
		$departmentId = intval($query['DEPARTMENT_ID']);

		$result = \Bitrix\Timeman\Absence::getSubordinateUsers($departmentId, $userId);

		return self::formatJsonAnswer($result);
	}


	public static function timeControlReportsGet($query, $n, \CRestServer $server)
	{
		$query = static::prepareQuery($query);

		$userId = $query['USER_ID'];
		$month = $query['MONTH'];
		$year = $query['YEAR'];
		$idleMinutes = $query['IDLE_MINUTES'];
		$workdayHours = $query['WORKDAY_HOURS'];

		$currentUserId = $GLOBALS['USER']->GetId();

		if (\Bitrix\Timeman\Absence::isHead())
		{
			$reportViewType = 'head';
		}
		else if (\Bitrix\Timeman\Absence::isReportListFullEnableForUser($currentUserId))
		{
			$reportViewType = 'full';
		}
		else if (\Bitrix\Timeman\Absence::isReportListSimpleEnableForUser($currentUserId))
		{
			$reportViewType = 'simple';
		}
		else
		{
			throw new \Bitrix\Rest\RestException("You don't have access to this method", "ACCESS_ERROR", \CRestServer::STATUS_WRONG_REQUEST);
		}

		if (!\Bitrix\Timeman\Absence::hasAccessToReport($userId))
		{
			throw new \Bitrix\Rest\RestException("You don't have access to report for this user", "USER_ACCESS_ERROR", \CRestServer::STATUS_WRONG_REQUEST);
		}

		if ($reportViewType != 'head')
		{
			$idleMinutes = null;
		}

		$result = \Bitrix\Timeman\Absence::getMonthReport($userId, $year, $month, $workdayHours, $idleMinutes);

		$fullTypesWhiteList = [
			\Bitrix\Timeman\Absence::SOURCE_ONLINE_EVENT,
			\Bitrix\Timeman\Absence::SOURCE_OFFLINE_AGENT,
			\Bitrix\Timeman\Absence::SOURCE_IDLE_EVENT,
			\Bitrix\Timeman\Absence::SOURCE_TM_EVENT,
		];

		if ($reportViewType != 'head')
		{
			foreach ($result['REPORT']['DAYS'] as $id => $entry)
			{
				if ($reportViewType == 'simple')
				{
					$result['REPORT']['DAYS'][$id]['REPORTS'] = [];
				}
				else
				{
					foreach ($entry['REPORTS'] as $reportId => $reportValue)
					{
						if (!in_array($reportValue['SOURCE_START'], $fullTypesWhiteList))
						{
							unset($result['REPORT']['DAYS'][$id]['REPORTS'][$reportId]);
						}
						else
						{
							unset($result['REPORT']['DAYS'][$id]['REPORTS'][$reportId]['IP_START']);
							unset($result['REPORT']['DAYS'][$id]['REPORTS'][$reportId]['IP_START_NETWORK']);
							unset($result['REPORT']['DAYS'][$id]['REPORTS'][$reportId]['IP_FINISH']);
							unset($result['REPORT']['DAYS'][$id]['REPORTS'][$reportId]['IP_FINISH_NETWORK']);
							unset($result['REPORT']['DAYS'][$id]['REPORTS'][$reportId]['SYSTEM_TEXT']);
						}
					}
				}
			}
		}

		return self::formatJsonAnswer($result);
	}








	protected static function prepareQuery(array $query)
	{
		return array_change_key_case($query, CASE_UPPER);
	}

	public static function getPublicDomain()
	{
		return (\Bitrix\Main\Context::getCurrent()->getRequest()->isHttps() ? "https" : "http")."://".((defined("SITE_SERVER_NAME") && SITE_SERVER_NAME <> '') ? SITE_SERVER_NAME : \Bitrix\Main\Config\Option::get("main", "server_name", $_SERVER['SERVER_NAME']));
	}

	public static function formatJsonAnswer($array)
	{
		if (!is_array($array))
		{
			return $array;
		}

		foreach ($array as $name => $value)
		{
			if (is_array($value))
			{
				$array[$name] = self::formatJsonAnswer($value);
			}
			else if ($value instanceof \Bitrix\Main\Type\DateTime)
			{
				$array[$name] = date('c', $value->getTimestamp());
			}
			else if ($name == 'AVATAR' && is_string($value) && $value && mb_strpos($value, 'http') !== 0)
			{
				$array[$name] = self::getPublicDomain().$value;
			}
		}

		return array_change_key_case($array, CASE_LOWER);
	}

	/**
	 * @param array $query
	 *
	 * @return \CTimeManUser
	 * @throws AccessException
	 */
	protected static function getUserInstance(array $query)
	{
		global $USER;

		if(array_key_exists('USER_ID', $query) && $query['USER_ID'] != $USER->getId())
		{
			if(!\CTimeMan::isAdmin())
			{
				throw new AccessException('User does not have access to managing other users work time');
			}

			if(!static::checkUser($query['USER_ID']))
			{
				throw new ObjectNotFoundException('User not found');
			}

			return new \CTimeManUser($query['USER_ID']);
		}
		else
		{
			return \CTimeManUser::instance();
		}
	}

	protected static function checkUser($userId)
	{
		$dbRes = \CUser::getById($userId);
		return is_array($dbRes->fetch());
	}

	protected static function correctTimeOffset($offsetTo, &$timeInfo)
	{
		$timeInfo['TIME'] = $timeInfo['TIME'] - $timeInfo['OFFSET'] + $offsetTo;

		if($timeInfo['TIME'] < 0)
		{
			$timeInfo['TIME'] += 86400;

			$dt = new Date($timeInfo['DATE']);
			$dt->add('-1 day');
			$timeInfo['DATE'] = $dt->toString();
		}

		if($timeInfo['TIME'] >= 86400)
		{
			$timeInfo['TIME'] -= 86400;

			$dt = new Date($timeInfo['DATE']);
			$dt->add('1 day');
			$timeInfo['DATE'] = $dt->toString();
		}

		$timeInfo['OFFSET'] = $offsetTo;
	}

	/**
	 * Returns full datetime in ISO format (Y-m-dTH:i:sP) in user's timezone
	 *
	 * @param int $ts Short timestamp in timeman format (num of seconds from the day start)
	 * @param string $date Date in site format
	 * @param int $userOffset User's timezone offset
	 *
	 * @return string
	 */
	protected static function convertTimeToISO($ts, $date, $userOffset)
	{
		return static::formatDateToISO($date, $userOffset).'T'.static::formatTimeToISO($ts, $userOffset);
	}

	/**
	 * Returns date in ISO format in user's timezone
	 *
	 * @param string $date Date in site format
	 * @param int $userOffset User offset
	 *
	 * @return false|string
	 */
	protected static function formatDateToISO($date, $userOffset)
	{
		// no timezone fix here
		return date('Y-m-d', MakeTimeStamp($date));
	}

	/**
	 * Returns time in ISO format with offset (H:i:sP) in user's timezone
	 *
	 * @param int $ts Short timestamp in timeman format (num of seconds from the day start)
	 * @param int $offset User's timezone offset
	 *
	 * @return string
	 */
	protected static function formatTimeToISO($ts, $offset)
	{
		$offsetSign = $offset >= 0 ? '+' : '-';

		return static::formatTime($ts)
			.$offsetSign
			.str_pad(abs(intval($offset / 3600)), 2, '0', STR_PAD_LEFT).':'.str_pad(abs(intval($offset % 3600 / 60)), 2, '0', STR_PAD_LEFT);
	}

	protected static function formatTime($ts)
	{
		return str_pad(intval($ts / 3600), 2, '0', STR_PAD_LEFT)
			.':'.str_pad(intval(($ts % 3600) / 60), 2, '0', STR_PAD_LEFT)
			.':'.str_pad(intval($ts % 60), 2, '0', STR_PAD_LEFT);
	}

	protected static function convertTimeFromISO($isoTime)
	{
		global $DB;

		$date = \DateTime::createFromFormat(\DateTime::ATOM, $isoTime);
		if(!$date)
		{
			throw new DateTimeException('Wrong datetime format', DateTimeException::ERROR_WRONG_DATETIME_FORMAT);
		}

		return array(
			'DATE' => $date->format($DB->DateFormatToPHP(FORMAT_DATE)),
			'TIME' => 3600*$date->format('G') + 60 * $date->format('i') + intval($date->format('s')),
			'OFFSET' => $date->getOffset(),
		);
	}

	protected static function setCurrentTimezoneOffset($offset)
	{
		\CTimeZone::SetCookieValue(intval(-$offset/60));
	}

	protected static function setDayGeoPosition($entryId, $query, $action = 'open')
	{
		$updateFields = array(
			'LAT_'.ToUpper($action) => isset($query['LAT']) ? doubleval($query['LAT']) : '',
			'LON_'.ToUpper($action) => isset($query['LON']) ? doubleval($query['LON']) : '',
		);

		\CTimeManEntry::Update($entryId, $updateFields);
		static::getUserInstance($query)->GetCurrentInfo(true);
	}

	protected static function checkDate(array $timeInfo, $compareDate)
	{
		return $timeInfo['DATE'] === $compareDate;
	}

	protected static function isAdmin()
	{
		if ($GLOBALS['USER']->IsAdmin())
			return true;

		if (\Bitrix\Main\Loader::includeModule('bitrix24'))
		{
			if (\CBitrix24::IsPortalAdmin($GLOBALS['USER']->GetID()))
			{
				return true;
			}
			else if (\CBitrix24::isIntegrator($GLOBALS['USER']->GetID()))
			{
				return true;
			}
		}

		return false;
	}
}
