<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage intranet
 * @copyright 2001-2013 Bitrix
 */

namespace Bitrix\Intranet\UStat;

use Bitrix\Main;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\DB\SqlException;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type;
use Bitrix\Main\UserTable;
use CFile;
use CUser;

class UStat
{
	/** @var array $affectedUsers Increment only once for each user during hit, [userId][service] => true */
	protected static $affectedUsers;

	// if user uses this amount of services, they are involved
	const INVOLVEMENT_SERVICE_COUNT = 4;

	const USER_LIMIT = 1000;

	const ADMIN_GROUP_ID = 1;

	const CACHE_ID = 'pulse_company_active_user_count';
	const CACHE_TTL = 3600;
	private const CACHE_PATH = '/intranet/ustat_user_count/';

	public static function sendNotificationIfLimitExceeded(): bool
	{
		$checkLimitCompanyPulse = Option::get('intranet', 'check_limit_company_pulse', 'N');
		if ($checkLimitCompanyPulse !== 'N')
		{
			return false;
		}

		$cache = \Bitrix\Main\Data\Cache::createInstance();
		if($cache->initCache(self::CACHE_TTL, self::CACHE_ID, self::CACHE_PATH))
		{
			$userCount = $cache->getVars();
		}
		else
		{
			$userCount = Main\Application::getInstance()->getLicense()->getActiveUsersCount();
			$cache->startDataCache();
			$cache->endDataCache($userCount);
		}
		if ($userCount < self::USER_LIMIT)
		{
			return false;
		}

		return true;
	}

	public static function sendAdminNotification(): void
	{
		IncludeModuleLangFile(__FILE__);

		$admins  = CUser::GetList('', '', array("GROUPS_ID" => array(static::ADMIN_GROUP_ID)), array("SELECT"=>array("ID")));
		if (\Bitrix\Main\Loader::includeModule("im"))
		{
			while ($admin = $admins->Fetch())
			{
				\CIMNotify::add([
					"TO_USER_ID" => $admin["ID"],
					"NOTIFY_TYPE" => IM_NOTIFY_SYSTEM,
					"NOTIFY_MODULE" => 'intranet',
					"NOTIFY_EVENT" => 'refresh_error',
					"NOTIFY_SUB_TAG" => "USER_LIMIT",
					"NOTIFY_MESSAGE" => Loc::getMessage('INTRANET_USTAT_RECOMMEND_DISABLING'),
					"NOTIFY_MESSAGE_OUT" => Loc::getMessage('INTRANET_USTAT_RECOMMEND_DISABLING'),
					"MESSAGE" => GetMessage('INTRANET_USTAT_RECOMMEND_DISABLING'),
					"RECENT_ADD" => 'Y',
				]);
			}
		}
		Option::set('intranet', 'check_limit_company_pulse', "Y");
	}

	public static function checkAvailableCompanyPulse(): bool
	{
		$allowCompanyPulseValue = Option::get('intranet', 'allow_company_pulse', 'Y');
		return $allowCompanyPulseValue === 'Y';
	}

	public static function checkAvailableCompanyPulseAndNotifyAdmin(): bool
	{
		if (self::checkAvailableCompanyPulse())
		{
			if (self::sendNotificationIfLimitExceeded())
			{
				self::sendAdminNotification();
			}

			return true;
		}
		else
		{
			return false;
		}
	}

	public static function incrementCounter($section, $userId = null)
	{
		if (!self::checkAvailableCompanyPulseAndNotifyAdmin())
		{
			return;
		}
		// try to update
		// if no update for DAY table, then:
		//   check if user is absent today, then we need to update ACTIVE_USERS counters for depts and company
		// endthen;

		// check userId emptiness
		if (empty($userId))
		{
			// try to get current user id
			if (isset($GLOBALS['USER']) && is_object($GLOBALS['USER']) && $GLOBALS['USER'] instanceof \CUser)
			{
				/** @var \CUser[] $GLOBALS */
				$userId = (int) $GLOBALS['USER']->getId();
			}
			else
			{
				return false;
			}
		}

		// avoid bots
		if (Main\Loader::includeModule('im'))
		{
			$botCache = \Bitrix\Im\Bot::getListCache();

			if (isset($botCache[$userId]))
			{
				return false;
			}
		}

		// check if user is in intranet and has a department
		$usersDepartments = static::getUsersDepartments();

		if (!isset($usersDepartments[$userId]))
		{
			return false;
		}

		// check if this counter has already been incremented during this hit
		if (isset(static::$affectedUsers[$userId][$section]))
		{
			return false;
		}
		static::$affectedUsers[$userId][$section] = true;

		// do increment
		$currentHour = new Type\DateTime(date('Y-m-d H:00:00'), 'Y-m-d H:00:00');

		// hourly stats
		$updResult = UserHourTable::update(
			array('USER_ID' => $userId, 'HOUR' => $currentHour),
			array($section => new SqlExpression('?# + 1', $section), 'TOTAL' => new SqlExpression('?# + 1', 'TOTAL'))
		);

		if (!$updResult->getAffectedRowsCount())
		{
			try
			{
				UserHourTable::add(array('USER_ID' => $userId, 'HOUR' => $currentHour, $section => 1, 'TOTAL' => 1));
			}
			catch (SqlException $e) {}
		}

		// daily stats
		$updResult = UserDayTable::update(
			array('USER_ID' => $userId, 'DAY' => $currentHour),
			array($section => new SqlExpression('?# + 1', $section), 'TOTAL' => new SqlExpression('?# + 1', 'TOTAL'))
		);

		if (!$updResult->getAffectedRowsCount())
		{
			try
			{
				UserDayTable::add(array('USER_ID' => $userId, 'DAY' => $currentHour, $section => 1, 'TOTAL' => 1));
			}
			catch (SqlException $e) {}

			// check if recounting ACTIVE_USERS is required
			$calendData = \CIntranetUtils::getAbsenceData(array(
				'DATE_START' => \ConvertTimeStamp(mktime(0, 0, 0), 'FULL'), // current day start
				'DATE_FINISH' => \ConvertTimeStamp(mktime(23, 59, 59), 'FULL'), // current day end
				'USERS' => array($userId),
				'PER_USER' => false
			));

			$userAbsentsToday = static::checkTodayAbsence($calendData);


			if ($userAbsentsToday)
			{
				static::recountDeptartmentsActiveUsers($userId);
				static::recountCompanyActiveUsers();
			}
		}

		// get user departments
		$allUDepts = static::getUsersDepartments();
		$userDepts = $allUDepts[$userId];
		$departmentHitStat = new DepartmentHitStat($userDepts);
		$departmentHitStat->hour($section, $currentHour);
		$departmentHitStat->day($section, $currentHour);
	}

	/**
	 * Recounts daily statistics: active users, activity and involvement for today and previous active day
	 */
	public static function recount()
	{
		if (!self::checkAvailableCompanyPulseAndNotifyAdmin())
		{
			return;
		}
		static::recountDeptartmentsActiveUsers();
		static::recountCompanyActiveUsers();
		static::recountDailyInvolvement();

		return '\\'.__METHOD__.'();';
	}

	/**
	 * Recounts hourly company activity
	 */
	public static function recountHourlyCompanyActivity()
	{
		$currentHour = new Type\DateTime(date('Y-m-d H:00:00'), 'Y-m-d H:00:00');

		// last record
		$lastRow = DepartmentHourTable::getRow(array(
			'filter' => array('=DEPT_ID' => 0, '<=HOUR' => \ConvertTimeStamp($currentHour->getTimestamp(), "FULL")),
			'order' => array('HOUR' => 'DESC'),
			'limit' => 1
		));

		if (!empty($lastRow))
		{
			$lastRowDate = is_object($lastRow['HOUR']) ? $lastRow['HOUR'] : new Type\DateTime($lastRow['HOUR'], 'Y-m-d H:00:00');
			$lastActivity = static::getHourlyCompanyActivitySince($lastRowDate);
		}
		else
		{
			// first ever company activity
			$lastActivity = static::getHourlyCompanyActivitySince(null);
		}

		// update db
		foreach ($lastActivity as $activity)
		{
			// skip if nothing changed for last hour
			if ($lastRow['HOUR'] === $activity['HOUR'] && $lastRow['TOTAL'] === $activity['TOTAL'])
			{
				continue;
			}

			$activityHour = is_object($activity['HOUR']) ? $activity['HOUR'] : new Type\DateTime($activity['HOUR'], 'Y-m-d H:00:00');
			unset($activity['HOUR']);

			$updResult = DepartmentHourTable::update(array('DEPT_ID' => 0, 'HOUR' => $activityHour), $activity);

			if (!$updResult->getAffectedRowsCount())
			{
				try
				{
					DepartmentHourTable::add(array_merge(array('DEPT_ID' => 0, 'HOUR' => $activityHour), $activity));
				}
				catch (SqlException $e) {}
			}
		}

		return '\\'.__METHOD__.'();';
	}

	public static function getStatusInformation()
	{
		if (!self::checkAvailableCompanyPulseAndNotifyAdmin())
		{
			return [
				'ACTIVITY' => 0,
				'INVOLVEMENT' => 0,
			];
		}
		// 1. activity score: emulate last 60 minutes
		$currentActivity = static::getCurrentActivity();

		// 2. involvement: last 24 hours
		// SELECT COUNT(1) AS `INVOLVED_COUNT` FROM (SELECT CASE WHEN
		//		(CASE WHEN SUM(TASKS) > 0 THEN 1 ELSE 0 END + CASE WHEN SUM(CRM) > 0 THEN 1 ELSE 0 END + ...)  >= 4
		//		THEN 1 ELSE 0 END) AS INVOLVED FROM ... GROUP BY USER_ID)
		// WHERE INVOLVED = 1

		$fromDate = Type\DateTime::createFromTimestamp(mktime(0, 0, 0));

		$toDate = Type\DateTime::createFromTimestamp(mktime(24, 0, 0));

		$currentInvolvement = static::getDepartmentSummaryInvolvement(
			0,
			$fromDate,
			$toDate,
			'hour'
		);
		/*
		$names = UserHourTable::getSectionNames();

		$fieldExpressions = array_fill(0, count($names), 'CASE WHEN SUM(%s) > 0 THEN 1 ELSE 0 END');

		// user involved if used 4 or more services for last 24 hours
		$involvedExpression = sprintf('CASE WHEN (%s) >= %d THEN 1 ELSE 0 END',
			join (' + ', $fieldExpressions), static::INVOLVEMENT_SERVICE_COUNT
		);

		// subquery
		$queryByUser = new Entity\Query(UserHourTable::getEntity());

		$queryByUser->setSelect(array(
			'USER_ID',
			'INVOLVED' => array(
				'data_type' => 'integer',
				'expression' => array_merge(array($involvedExpression), $names)
			)))
			->setFilter(array(
				'><HOUR' => array(
					ConvertTimeStamp(mktime(date('G'), 0, 0, date('n'), date('j')-1), 'FULL'), // prev day, same hour
					ConvertTimeStamp(time(), 'FULL')
				)
			))
			->setGroup('USER_ID');

		// main query
		$query = new Entity\Query($queryByUser);

		$query->setSelect(array(
			'INVOLVED_COUNT' => array(
				'data_type' => 'integer',
				'expression' => array('COUNT(1)')
			)))
			->setFilter(array('=INVOLVED' => 1));

		$data = $query->exec()->fetch();
		$currentInvolvement = (int) $data['INVOLVED_COUNT'];
		*/

		/*
		// 3. total employees
		$usersDepartments = static::getUsersDepartments();
		$currentTotalUsers = count($usersDepartments);

		// 4. online employees
		$result = Main\UserTable::getList(array(
			'select' => array('ONLINE_COUNT' => array(
				'data_type' => 'integer',
				'expression' => array('COUNT(1)')
			)),
			'filter' => array('=IS_ONLINE' => true)
		));

		$data = $result->fetch();
		$currentUsersOnline = (int) $data['ONLINE_COUNT'];

		// 5. absentees
		$currentUsersAbsent = 0;
		$allUsers = array_keys($usersDepartments);

		$allAbsenceData = \CIntranetUtils::getAbsenceData(array(
			'DATE_START' => ConvertTimeStamp(mktime(0, 0, 0), 'FULL'), // current day start
			'DATE_FINISH' => ConvertTimeStamp(mktime(23, 59, 59), 'FULL'), // current day end
			'PER_USER' => true
		));

		foreach ($allUsers as $userId)
		{
			if (isset($allAbsenceData[$userId]) && static::checkTodayAbsence($allAbsenceData[$userId]))
			{
				++$currentUsersAbsent;
			}
		}
		*/

		// done!
		return array(
			'ACTIVITY' => $currentActivity,
			'INVOLVEMENT' => $currentInvolvement,
			//'TOTAL_USERS' => $currentTotalUsers,
			//'USERS_ONLINE' => $currentUsersOnline,
			//'USERS_ABSENT' => $currentUsersAbsent
		);
	}

	public static function getCurrentActivity($departmentId = 0, $section = null)
	{
		if (!self::checkAvailableCompanyPulseAndNotifyAdmin())
		{
			return 0;
		}

		$data = array();

		$fieldName = ($section) === null ? 'TOTAL' : $section;

		$currentHour = ConvertTimeStamp(mktime(date('G'), 0, 0), 'FULL');
		$previousHour = ConvertTimeStamp(mktime(date('G')-1, 0, 0), 'FULL');

		$currentHourClient = ConvertTimeStamp(mktime(date('G'), 0, 0)+\CTimeZone::getOffset(), 'FULL');
		$previousHourClient = ConvertTimeStamp(mktime(date('G')-1, 0, 0)+\CTimeZone::getOffset(), 'FULL');

		$result = DepartmentHourTable::getList(array(
			'select' => array('HOUR', $fieldName),
			'filter' => array('=DEPT_ID' => $departmentId, '=HOUR' => array($currentHourClient, $previousHourClient))
		));

		while ($row = $result->fetch())
		{
			$data[ConvertTimeStamp($row['HOUR']->getTimestamp(), 'FULL')] = $row[$fieldName];
		}

		$currentActivity = isset($data[$currentHour]) ? (int) $data[$currentHour] : 0;

		if (isset($data[$previousHour]))
		{
			// emulation of [60 - CURRENT_MINUTES] of previous hour
			$currentActivity += round($data[$previousHour] * (1 - date('i') / 60));
		}

		return $currentActivity;
	}

	/**
	 * @param integer       $departmentId
	 * @param Type\DateTime $dateFrom
	 * @param Type\DateTime $dateTo
	 * @param string        $interval  hour | day | month
	 *
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function getDepartmentGraphData($departmentId, Type\DateTime $dateFrom, Type\DateTime $dateTo, $interval)
	{
		if (!self::checkAvailableCompanyPulseAndNotifyAdmin())
		{
			return [];
		}

		if (!in_array($interval, array('hour', 'day', 'month'), true))
		{
			throw new Main\ArgumentException('Interval should be the "hour", or "day", or "month".');
		}

		$data = array();

		if ($interval === 'hour')
		{
			$query = new Entity\Query(DepartmentHourTable::getEntity());

			$query->setSelect(array('DATE' => 'HOUR', 'TOTAL'));

			foreach (UserHourTable::getSectionNames() as $sectionName)
			{
				$query->addSelect($sectionName);
			}

			$query->setFilter(array(
				'=DEPT_ID' => $departmentId,
				'><HOUR' => array(
					ConvertTimeStamp($dateFrom->getTimestamp(), 'FULL'),
					ConvertTimeStamp($dateTo->getTimestamp(), 'FULL')
				)
			));

			$keyFormat = 'Y-m-d H:00:00';

			// no company involvement for hourly data
		}
		elseif ($interval === 'day')
		{
			$query = new Entity\Query(DepartmentDayTable::getEntity());

			$query->setSelect(array('DATE' => 'DAY', 'TOTAL', 'INVOLVEMENT'));

			foreach (UserHourTable::getSectionNames() as $sectionName)
			{
				$query->addSelect($sectionName);
			}

			$query->setFilter(array(
				'=DEPT_ID' => $departmentId,
				'><DAY' => array(
					ConvertTimeStamp($dateFrom->getTimestamp()),
					ConvertTimeStamp($dateTo->getTimestamp())
				)
			));

			$keyFormat = 'Y-m-d';
		}
		elseif ($interval === 'month')
		{
			$query = new Entity\Query(DepartmentDayTable::getEntity());
			$sqlHelper = Application::getConnection()->getSqlHelper();

			$monthExpression = array(
				'data_type' => 'string',
				'expression' => array(str_replace(
					$sqlHelper->formatDate('YYYY-MM'), // get db format
					str_replace('%', '%%', $sqlHelper->formatDate('YYYY-MM')), // and quote it for sprintf
					$sqlHelper->formatDate('YYYY-MM', '%1$s') // in main expression
				), 'DAY')
			);

			$query->registerRuntimeField('DATE', $monthExpression);
			$query->registerRuntimeField('TOTAL_SUM', array(
				'data_type' => 'integer',
				'expression' => array('SUM(%s)', 'TOTAL')
			));

			$query->setSelect(array('DATE', 'TOTAL_SUM'));

			foreach (UserHourTable::getSectionNames() as $sectionName)
			{
				$query->registerRuntimeField($sectionName.'_SUM', array(
					'data_type' => 'integer',
					'expression' => array('SUM(%s)', $sectionName)
				));

				$query->addSelect($sectionName.'_SUM');
			}

			$query->setFilter(array(
				'=DEPT_ID' => $departmentId,
				'><DAY' => array(
					ConvertTimeStamp($dateFrom->getTimestamp()),
					ConvertTimeStamp($dateTo->getTimestamp())
				)
			));

			$query->setGroup('DATE');

			$keyFormat = 'Y-m';

			// company involvement will be attached later
		}

		$query->setOrder('DATE');

		$result = $query->exec();

		while ($row = $result->fetch())
		{
			// back-format keys
			foreach ($row as $k => $v)
			{
				if (mb_substr($k, -4) === '_SUM')
				{
					$row[mb_substr($k, 0, -4)] = $v;
					unset($row[$k]);
				}
			}

			/** @var Type\DateTime[] $row */
			if (!is_object($row['DATE']))
			{
				$key = $row['DATE'];
				$row['DATE'] = new Type\DateTime($row['DATE'], $keyFormat);
			}
			else
			{
				$key = $row['DATE']->format($keyFormat);
			}

			$data[$key] = $row;
		}

		if ($interval === 'month')
		{
			// count involvement
			$invQuery = new Entity\Query(DepartmentDayTable::getEntity());

			$invQuery->setSelect(array('DATE' => 'DAY', 'INVOLVEMENT'));

			$invQuery->setFilter(array(
				'=DEPT_ID' => $departmentId,
				'><DAY' => array(
					ConvertTimeStamp($dateFrom->getTimestamp()),
					ConvertTimeStamp($dateTo->getTimestamp())
				)
			));

			$invQuery->addOrder('INVOLVEMENT', 'DESC');

			$result = $invQuery->exec();

			$invData = array();

			while ($row = $result->fetch())
			{
				/** @var Type\DateTime[] $row */
				$invData[$row['DATE']->format('Y-m')][$row['DATE']->format('j')] = $row['INVOLVEMENT'];
			}

			// get 70% most involved days
			foreach ($invData as $month => $monthData)
			{
				$bestDays = array_slice($monthData, 0, round(count($monthData)*0.7));

				if (count($bestDays))
				{
					$involvement = round(array_sum($bestDays)/count($bestDays));
					$data[$month]['INVOLVEMENT'] = $involvement;
				}
			}
		}

		return $data;
	}

	/**
	 * @param integer       $departmentId
	 * @param Type\DateTime $dateFrom
	 * @param Type\DateTime $dateTo
	 * @param string        $interval  hour | day | month
	 *
	 * @return int
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function getDepartmentSummaryInvolvement($departmentId, Type\DateTime $dateFrom, Type\DateTime $dateTo, $interval)
	{
		if (!self::checkAvailableCompanyPulseAndNotifyAdmin())
		{
			return 0;
		}
		// at this moment departmentId doesn't work, data will be counted for a whole company

		if (!in_array($interval, array('hour', 'day', 'month'), true))
		{
			throw new Main\ArgumentException('Interval should be the "hour", or "day", or "month".');
		}

		if ($interval === 'hour')
		{
			$entity = UserHourTable::getEntity();

			$filter = array(
				'><HOUR' => array(
					ConvertTimeStamp($dateFrom->getTimestamp(), 'FULL'),
					ConvertTimeStamp($dateTo->getTimestamp(), 'FULL')
				)
			);
		}
		else
		{
			$entity = UserDayTable::getEntity();

			$filter = array(
				'><DAY' => array(
					ConvertTimeStamp($dateFrom->getTimestamp()),
					ConvertTimeStamp($dateTo->getTimestamp())
				)
			);
		}

		$names = UserHourTable::getSectionNames();

		$fieldExpressions = array_fill(0, count($names), 'CASE WHEN SUM(%s) > 0 THEN 1 ELSE 0 END');

		// user involved if used 4 or more services for last 24 hours
		$involvedExpression = sprintf('CASE WHEN (%s) >= %d THEN 1 ELSE 0 END',
			join (' + ', $fieldExpressions), static::INVOLVEMENT_SERVICE_COUNT
		);

		// build query
		$subQuery = new Entity\Query($entity);

		$subQuery->registerRuntimeField('INVOLVED', array(
			'data_type' => 'integer',
			'expression' => array_merge(array($involvedExpression), $names)
		));

		$subQuery->setSelect(array('USER_ID', 'INVOLVED'));

		$subQuery->setFilter($filter);
		$subQuery->setGroup('USER_ID');

		// main query
		$query = new Entity\Query($subQuery);

		$query->registerRuntimeField('INVOLVED_COUNT', array(
			'data_type' => 'integer',
			'expression' => array('SUM(CASE WHEN %s > 0 THEN 1 ELSE 0 END)', 'INVOLVED')
		));

		$query->registerRuntimeField('USERS_COUNT', array(
			'data_type' => 'integer',
			'expression' => array('COUNT(1)')
		));

		$query->setSelect(array('INVOLVED_COUNT', 'USERS_COUNT'));

		$result = $query->exec();
		$data = $result->fetch();

		$involvement = 0;

		if (!empty($data) && !empty($data['USERS_COUNT']))
		{
			$involvement = (int) round($data['INVOLVED_COUNT'] / $data['USERS_COUNT'] * 100);
		}

		return $involvement;
	}

	/**
	 * @param Type\DateTime $dateFrom
	 * @param Type\DateTime $dateTo
	 * @param string        $interval   hour | day | month
	 *
	 * @return array|bool
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function getSectionsSummaryInvolvement(Type\DateTime $dateFrom, Type\DateTime $dateTo, $interval)
	{
		if (!in_array($interval, array('hour', 'day', 'month'), true))
		{
			throw new Main\ArgumentException('Interval should be the "hour", or "day", or "month".');
		}

		if ($interval === 'hour')
		{
			$entity = UserHourTable::getEntity();

			$filter = array(
				'><HOUR' => array(
					ConvertTimeStamp($dateFrom->getTimestamp(), 'FULL'),
					ConvertTimeStamp($dateTo->getTimestamp(), 'FULL')
				)
			);
		}
		else
		{
			$entity = UserDayTable::getEntity();

			$filter = array(
				'><DAY' => array(
					ConvertTimeStamp($dateFrom->getTimestamp()),
					ConvertTimeStamp($dateTo->getTimestamp())
				)
			);
		}

		$query = new Entity\Query($entity);

		foreach (UserHourTable::getSectionNames() as $sectionName)
		{
			$query->addSelect(new Entity\ExpressionField(
				$sectionName.'_IS_USED', 'CASE WHEN SUM(%s) > 0 THEN 1 ELSE 0 END', $sectionName
			));
		}

		$query->setFilter($filter);
		$query->setGroup('USER_ID');

		// ^ there was stats by user. now summarize it
		$finalQuery = new Entity\Query($query);

		$finalQuery->addSelect(new Entity\ExpressionField('USERS_COUNT', 'COUNT(1)'));

		foreach (UserHourTable::getSectionNames() as $sectionName)
		{
			$finalQuery->addSelect(new Entity\ExpressionField($sectionName.'_USAGE', 'SUM(%s)', $sectionName.'_IS_USED'));
		}

		$result = $finalQuery->exec();
		$stats = $result->fetch();

		if ($interval === 'hour')
		{
			// recount unique users from DAILY stats,
			// because there are empty records for each user
			$query = new Entity\Query(UserDayTable::getEntity());

			$query->addSelect(new Entity\ExpressionField('USERS_COUNT', 'COUNT(DISTINCT %s)', 'USER_ID'));

			$query->setFilter(array(
				'><DAY' => array(
					ConvertTimeStamp($dateFrom->getTimestamp()),
					ConvertTimeStamp($dateTo->getTimestamp())
				)
			));

			$result = $query->exec();
			$row = $result->fetch();

			$stats['USERS_COUNT'] = $row['USERS_COUNT'];
		}

		return $stats;
	}

	/**
	 * @param integer       $departmentId
	 * @param string        $section
	 * @param Type\DateTime $dateFrom
	 * @param Type\DateTime $dateTo
	 * @param string        $interval
	 *
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function getSectionInvolvement($departmentId, $section, Type\DateTime $dateFrom, Type\DateTime $dateTo, $interval)
	{
		if (!self::checkAvailableCompanyPulseAndNotifyAdmin())
		{
			return [];
		}
		// at this moment departmentId doesn't work, data will be counted for a whole company

		if (!in_array($interval, array('hour', 'day', 'month'), true))
		{
			throw new Main\ArgumentException('Interval should be the "hour", or "day", or "month".');
		}

		if (!in_array($section, UserHourTable::getSectionNames(), true))
		{
			throw new Main\ArgumentException(sprintf('Unknown section "%s"', $section));
		}

		if ($interval === 'hour')
		{
			$query = new Entity\Query(UserHourTable::getEntity());

			$query->setSelect(array(
				'DATE' => 'HOUR',
				new Entity\ExpressionField('TOTAL_USERS', 'COUNT(1)'),
				new Entity\ExpressionField($section.'_USAGE', 'SUM(CASE WHEN %s > 0 THEN 1 ELSE 0 END)', $section)
			));

			$query->setFilter(array(
				'><HOUR' => array(
					ConvertTimeStamp($dateFrom->getTimestamp(), 'FULL'),
					ConvertTimeStamp($dateTo->getTimestamp(), 'FULL')
				)
			));

			$query->setGroup('HOUR');

			$keyFormat = 'Y-m-d H:00:00';

			// need to rewrite TOTAL_USERS according to daily stats
		}
		elseif ($interval === 'day')
		{
			$query = new Entity\Query(UserDayTable::getEntity());

			$query->setSelect(array(
				'DATE' => 'DAY',
				new Entity\ExpressionField('TOTAL_USERS', 'COUNT(1)'),
				new Entity\ExpressionField($section.'_USAGE', 'SUM(CASE WHEN %s > 0 THEN 1 ELSE 0 END)', $section)
			));

			$query->setFilter(array(
				'><DAY' => array(
					ConvertTimeStamp($dateFrom->getTimestamp()),
					ConvertTimeStamp($dateTo->getTimestamp())
				)
			));

			$keyFormat = 'Y-m-d';

			$query->setGroup('DAY');
		}
		elseif ($interval === 'month')
		{
			$subQuery = new Entity\Query(UserDayTable::getEntity());
			$sqlHelper = Application::getConnection()->getSqlHelper();

			$monthExpression = array(
				'data_type' => 'string',
				'expression' => array(str_replace(
					$sqlHelper->formatDate('YYYY-MM'), // get db format
					str_replace('%', '%%', $sqlHelper->formatDate('YYYY-MM')), // and quote it for sprintf
					$sqlHelper->formatDate('YYYY-MM', '%1$s') // in main expression
				), 'DAY')
			);

			$subQuery->registerRuntimeField('DATE', $monthExpression);

			$subQuery->setSelect(array(
				'USER_ID',
				'DATE',
				new Entity\ExpressionField($section.'_SUM', 'SUM(%s)', $section)
			));

			$subQuery->setGroup(array('USER_ID', 'DATE'));

			$query = new Entity\Query($subQuery);

			$query->setSelect(array(
				'DATE',
				new Entity\ExpressionField($section.'_USAGE', 'SUM(CASE WHEN %s > 5 THEN 1 ELSE 0 END)', $section.'_SUM'),
				new Entity\ExpressionField('TOTAL_USERS', 'COUNT(1)')
			));

			$query->setGroup('DATE');

			$keyFormat = 'Y-m';
		}

		$query->addOrder('DATE');

		$result = $query->exec();

		$data = array();

		while ($row = $result->fetch())
		{
			/** @var Type\DateTime[] $row */
			if (!is_object($row['DATE']))
			{
				$key = $row['DATE'];
				$row['DATE'] = new Type\DateTime($row['DATE'], $keyFormat);
			}
			else
			{
				$key = $row['DATE']->format($keyFormat);
			}

			$data[$key] = $row;
		}

		if ($interval === 'hour')
		{
			// recount unique users from DAILY stats,
			// because there are empty records for each user
			$dailyActiveUsers = array();

			$query = new Entity\Query(DepartmentDayTable::getEntity());

			$result = $query->addSelect('DAY')
				->addSelect('ACTIVE_USERS')
				->addFilter('=DEPT_ID', 0)
				->addFilter('><DAY', array(
					ConvertTimeStamp($dateFrom->getTimestamp()), ConvertTimeStamp($dateTo->getTimestamp())
				))->exec();

			while ($row = $result->fetch())
			{
				/** @var Type\DateTime[] $row */
				$dailyActiveUsers[$row['DAY']->format('Y-m-d')] = $row['ACTIVE_USERS'];
			}

			foreach ($data as &$hourlyData)
			{
				/** @var Type\DateTime[] $hourlyData */
				$hourlyData['TOTAL_USERS'] = $dailyActiveUsers[$hourlyData['DATE']->format('Y-m-d')];
			}
		}

		return $data;
	}

	public static function getUserRatingApi(string $period = '', int $limit = 5)
	{
		$users = [];
		$toDate = new Type\DateTime();
		$fieldName = 'TOTAL';

		switch ($period)
		{
			case 'year':
				$fromDate = Type\DateTime::createFromTimestamp(mktime(0, 0, 0, date('n')-12, 1));
				$interval = 'month';
				break;
			case 'month':
				$fromDate = Type\DateTime::createFromTimestamp(mktime(0, 0, 0, date('n'), date('j')-30));
				$interval = 'day';
				break;
			case 'week':
				$fromDate = Type\DateTime::createFromTimestamp(mktime(0, 0, 0, date('n'), date('j')-7));
				$interval = 'day';
				break;
			default:
				// for today, from 00:00 till 23:59
				$fromDate = Type\DateTime::createFromTimestamp(mktime(-1, 0, 0));
				$toDate = Type\DateTime::createFromTimestamp(mktime(24, 0, 0));
				$interval = 'hour';
		}

		$posQuery = new Entity\Query(UserDayTable::getEntity());
		$posQuery->setFilter(array(
			 '><DAY' => array(
				 ConvertTimeStamp($fromDate->getTimestamp()),
				 ConvertTimeStamp($toDate->getTimestamp())
			 ),
			 '>SUM_'.$fieldName => 0
		 ));

		// now get position
		$posQuery->setSelect(array(
			'USER_ID',
			new Entity\ExpressionField('SUM_'.$fieldName, 'SUM(%s)', $fieldName),
			'US.NAME',
			'US.LAST_NAME',
			'US.PERSONAL_PHOTO',
			'US.WORK_POSITION'
		));
		$posQuery->addOrder('SUM_'.$fieldName, 'DESC');

		$userReference = new Entity\ReferenceField(
			'US',
			UserTable::getEntity(),
			[
				'=ref.ID' => 'this.USER_ID'
			],
			['join_type' => 'LEFT']
		);
		$posQuery->registerRuntimeField('US', $userReference);
		$posQuery->setLimit($limit);
		$result = $posQuery->exec();

		$prefix = strtoupper($posQuery->getInitAlias() . '_us_');

		while ($row = $result->fetch())
		{
			$personalPhotoFile = null;
			$avatarSrc = null;
			if(!empty($row[$prefix . "PERSONAL_PHOTO"]))
			{
				$personalPhotoFile = CFile::GetFileArray($row[$prefix . 'PERSONAL_PHOTO']);
				$avatarSrc = $personalPhotoFile["SRC"];
			}

			$users[] = [
				'user_id' => $row['USER_ID'],
				'name' => $row[$prefix .'NAME'],
				'last_name' => $row[$prefix .'LAST_NAME'],
				'work_position' => $row[$prefix .'WORK_POSITION'],
				'activity' => $row['SUM_'.$fieldName],
				'avatar' => $avatarSrc
			];
		}

		$sumInvolvement = self::getDepartmentSummaryInvolvement(0, $fromDate, $toDate, $interval);
		$rawData = self::getDepartmentGraphData(0, $fromDate, $toDate, $interval);
		$sumActivity = 0;
		foreach ($rawData as $k => $v)
		{
			$sumActivity += $v[$fieldName];
		}

		$data = [
			'involvement' => $sumInvolvement,
			'activity' => $sumActivity,
			'users' => $users
		];

		return $data;
	}

	/**
	 * @param string        $userId
	 * @param Type\DateTime $dateFrom
	 * @param Type\DateTime $dateTo
	 * @param string        $interval   hour | day | month
	 * @param string|null   $section
	 *
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function getUsersGraphData($userId, Type\DateTime $dateFrom, Type\DateTime $dateTo, $interval, $section = null)
	{
		if (!in_array($interval, array('hour', 'day', 'month'), true))
		{
			throw new Main\ArgumentException('Interval should be the "hour", or "day", or "month".');
		}

		$data = array();

		// rating for TOTAL activity or for an instrument
		$posField = ($section) === null ? 'TOTAL' : $section;

		if ($interval === 'hour')
		{
			$query = new Entity\Query(UserHourTable::getEntity());

			$query->setSelect(array('USER_ID', 'DATE' => 'HOUR', 'TOTAL'));

			foreach (UserHourTable::getSectionNames() as $sectionName)
			{
				$query->addSelect($sectionName);
			}

			$query->setFilter(array(
				'=USER_ID' => $userId,
				'><HOUR' => array(
					ConvertTimeStamp($dateFrom->getTimestamp(), 'FULL'),
					ConvertTimeStamp($dateTo->getTimestamp(), 'FULL')
				)
			));

			$keyFormat = 'Y-m-d H:00:00';

			// top position
			$posQuery = new Entity\Query(UserHourTable::getEntity());

			$posQuery->setFilter(array(
				'><HOUR' => array(
					ConvertTimeStamp($dateFrom->getTimestamp(), 'FULL'),
					ConvertTimeStamp($dateTo->getTimestamp(), 'FULL')
				),
				'>LEAD_'.$posField => 0,
				'>SUM_'.$posField => 0
			));
		}
		elseif ($interval === 'day')
		{
			$query = new Entity\Query(UserDayTable::getEntity());

			$query->setSelect(array('USER_ID', 'DATE' => 'DAY', 'TOTAL'));

			foreach (UserHourTable::getSectionNames() as $sectionName)
			{
				$query->addSelect($sectionName);
			}

			$query->setFilter(array(
				'=USER_ID' => $userId,
				'><DAY' => array(
					ConvertTimeStamp($dateFrom->getTimestamp()),
					ConvertTimeStamp($dateTo->getTimestamp())
				)
			));

			$keyFormat = 'Y-m-d';

			// top position
			$posQuery = new Entity\Query(UserDayTable::getEntity());

			$posQuery->setFilter(array(
				'><DAY' => array(
					ConvertTimeStamp($dateFrom->getTimestamp()),
					ConvertTimeStamp($dateTo->getTimestamp())
				),
				'>LEAD_'.$posField => 0,
				'>SUM_'.$posField => 0
			));
		}
		elseif ($interval === 'month')
		{
			$query = new Entity\Query(UserDayTable::getEntity());
			$sqlHelper = Application::getConnection()->getSqlHelper();

			$monthExpression = array(
				'data_type' => 'string',
				'expression' => array(str_replace(
					$sqlHelper->formatDate('YYYY-MM'), // get db format
					str_replace('%', '%%', $sqlHelper->formatDate('YYYY-MM')), // and quote it for sprintf
					$sqlHelper->formatDate('YYYY-MM', '%1$s') // in main expression
				), 'DAY')
			);

			$query->registerRuntimeField('DATE', $monthExpression);

			$query->setSelect(array(
				'USER_ID',
				'DATE',
				new Entity\ExpressionField('TOTAL_SUM', 'SUM(%s)', 'TOTAL')
			));

			foreach (UserHourTable::getSectionNames() as $sectionName)
			{
				$query->addSelect(new Entity\ExpressionField($sectionName.'_SUM', 'SUM(%s)', $sectionName));
			}

			$query->setFilter(array(
				'=USER_ID' => $userId,
				'><DAY' => array(
					ConvertTimeStamp($dateFrom->getTimestamp()),
					ConvertTimeStamp($dateTo->getTimestamp())
				)
			));

			$query->setGroup(array('USER_ID', 'DATE'));

			$keyFormat = 'Y-m';

			// top position
			$posQuery = new Entity\Query(UserDayTable::getEntity());

			$posQuery->setFilter(array(
				'><DAY' => array(
					ConvertTimeStamp($dateFrom->getTimestamp()),
					ConvertTimeStamp($dateTo->getTimestamp())
				),
				'>LEAD_'.$posField => 0,
				'>SUM_'.$posField => 0
			));
		}

		// and continue with main data
		$query->setOrder('DATE');

		$result = $query->exec();

		$posTotal = 0;

		while ($row = $result->fetch())
		{
			// back-format keys
			foreach ($row as $k => $v)
			{
				if (mb_substr($k, -4) === '_SUM')
				{
					$row[mb_substr($k, 0, -4)] = $v;
					unset($row[$k]);
				}
			}

			/** @var Type\DateTime[] $row */
			if (!is_object($row['DATE']))
			{
				$key = $row['DATE'];
				$row['DATE'] = new Type\DateTime($row['DATE'], $keyFormat);
			}
			else
			{
				$key = $row['DATE']->format($keyFormat);
			}

			$data[$key] = $row;

			$posTotal += $row[$posField];
		}

		// now get position
		$posQuery->setSelect(array(
			'USER_ID',
			new Entity\ExpressionField('SUM_'.$posField, 'SUM(%s)', $posField),
			new Entity\ExpressionField('LEAD_'.$posField, 'CASE WHEN SUM(%s) > '.$posTotal.' THEN 1 ELSE 0 END', $posField)
		));

		$posQuery->registerRuntimeField('MYSELF', array(
			'data_type' => 'integer',
			'expression' => array('CASE WHEN %s = '.(int)$userId.' THEN 1 ELSE 0 END', 'USER_ID')
		));

		$posQuery->addOrder('SUM_'.$posField, 'DESC');
		$posQuery->addOrder('MYSELF', 'DESC');

		// backup query
		$topQuery = clone $posQuery;

		if ($posTotal > 0)
		{
			$result = $posQuery->exec();
			$position = $result->getSelectedRowsCount() + 1;
		}
		else
		{
			$position = 0;
		}

		if ($position < 5)
		{
			//we need all from the top 5
			$filter = $topQuery->getFilter();
			unset($filter['>LEAD_'.$posField]);
			$topQuery->setFilter($filter);

			$topQuery->setLimit(5);

			$result = $topQuery->exec();
		}

		$topUsers = array();

		while (($row = $result->fetch()) && count($topUsers) < 5)
		{
			$topUsers[count($topUsers)+1] = array('USER_ID' => $row['USER_ID'], 'ACTIVITY' => $row['SUM_'.$posField]);
		}

		if ($position >= 5)
		{
			$topUsers[$position] = array('USER_ID' => $userId, 'ACTIVITY' => $posTotal);
		}

		$allQuery = new Entity\Query($query->getEntity());
		$allQuery->setFilter(array_diff_key($query->getFilter(), ['=USER_ID' => null]));
		$allQuery->addSelect(new Entity\ExpressionField('ALL_ACTIVE_USERS', 'COUNT(DISTINCT %s)', 'USER_ID'));
		$res = $allQuery->exec()->fetch();

		return array('data' => $data, 'rating' => array('top' => $topUsers, 'position' => $position, 'range' => intval($res['ALL_ACTIVE_USERS'])));
	}

	public static function getDepartmentAverageGraphData($deptId, Type\DateTime $dateFrom, Type\DateTime $dateTo, $interval, $section = null)
	{
		if (!in_array($interval, array('hour', 'day', 'month'), true))
		{
			throw new Main\ArgumentException('Interval should be the "hour", or "day", or "month".');
		}

		$data = array();

		$sectionField = ($section) === null ? 'TOTAL' : $section;

		if ($interval === 'hour')
		{
			$query = new Entity\Query(DepartmentHourTable::getEntity());

			$query->setSelect(array('DEPT_ID', 'DATE' => 'HOUR', 'AVG_ACTIVITY' => $sectionField));

			$query->setFilter(array(
				'=DEPT_ID' => $deptId,
				'><HOUR' => array(
					ConvertTimeStamp($dateFrom->getTimestamp(), 'FULL'),
					ConvertTimeStamp($dateTo->getTimestamp(), 'FULL')
				)
			));

			$keyFormat = 'Y-m-d H:00:00';
		}
		elseif ($interval === 'day')
		{
			$query = new Entity\Query(DepartmentDayTable::getEntity());

			$query->setSelect(array(
				'DEPT_ID',
				'DATE' => 'DAY',
				new Entity\ExpressionField(
					'AVG_ACTIVITY',
					'CASE WHEN %s > 0 THEN ROUND((%s / %s), 0) ELSE 0 END',
					array('ACTIVE_USERS', $sectionField, 'ACTIVE_USERS')
				)
			));

			$query->setFilter(array(
				'=DEPT_ID' => $deptId,
				'><DAY' => array(
					ConvertTimeStamp($dateFrom->getTimestamp()),
					ConvertTimeStamp($dateTo->getTimestamp())
				)
			));

			$keyFormat = 'Y-m-d';
		}
		elseif ($interval === 'month')
		{
			$query = new Entity\Query(DepartmentDayTable::getEntity());
			$sqlHelper = Application::getConnection()->getSqlHelper();

			$monthExpression = array(
				'data_type' => 'string',
				'expression' => array(str_replace(
					$sqlHelper->formatDate('YYYY-MM'), // get db format
					str_replace('%', '%%', $sqlHelper->formatDate('YYYY-MM')), // and quote it for sprintf
					$sqlHelper->formatDate('YYYY-MM', '%1$s') // in main expression
				), 'DAY')
			);

			$query->registerRuntimeField('DATE', $monthExpression);

			$query->setSelect(array(
				'DEPT_ID',
				'DATE',
				new Entity\ExpressionField(
					'AVG_ACTIVITY',
					'ROUND(SUM(CASE WHEN %s > 0 THEN %s / %s ELSE 0 END), 0)',
					array('ACTIVE_USERS', $sectionField, 'ACTIVE_USERS')
				)
			));

			$query->setFilter(array(
				'=DEPT_ID' => $deptId,
				'><DAY' => array(
					ConvertTimeStamp($dateFrom->getTimestamp()),
					ConvertTimeStamp($dateTo->getTimestamp())
				)
			));

			$query->setGroup(array('DEPT_ID', 'DATE'));

			$keyFormat = 'Y-m';
		}

		// and continue with main data
		$query->setOrder('DATE');

		$result = $query->exec();

		while ($row = $result->fetch())
		{
			/** @var Type\DateTime[] $row */
			if (!is_object($row['DATE']))
			{
				$key = $row['DATE'];
				$row['DATE'] = new Type\DateTime($row['DATE'], $keyFormat);
			}
			else
			{
				$key = $row['DATE']->format($keyFormat);
			}

			$data[$key] = $row;
		}

		if ($interval === 'hour')
		{
			// recount unique users from DAILY stats,
			// because there are empty records for each user

			// at this moment AVG_ACTIVITY is just sum of activity, and we should find average per user

			$dailyActiveUsers = array();

			$query = new Entity\Query(DepartmentDayTable::getEntity());

			$result = $query->addSelect('DAY')
				->addSelect('ACTIVE_USERS')
				->addFilter('=DEPT_ID', $deptId)
				->addFilter('><DAY', array(
					ConvertTimeStamp($dateFrom->getTimestamp()), ConvertTimeStamp($dateTo->getTimestamp())
				))->exec();

			while ($row = $result->fetch())
			{
				/** @var Type\DateTime[] $row */
				$dailyActiveUsers[$row['DAY']->format('Y-m-d')] = $row['ACTIVE_USERS'];
			}

			foreach ($data as &$hourlyData)
			{
				/** @var Type\DateTime[] $hourlyData */
				if (!empty($dailyActiveUsers[$hourlyData['DATE']->format('Y-m-d')]))
				{
					$hourlyData['AVG_ACTIVITY'] = round(
						$hourlyData['AVG_ACTIVITY'] / $dailyActiveUsers[$hourlyData['DATE']->format('Y-m-d')]
					);
				}
			}
		}

		return $data;
	}

	/**
	 * @param Type\DateTime $dateFrom
	 * @param Type\DateTime $dateTo
	 * @param               $interval
	 *
	 * @return array|bool
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function getMaxUserActivity(Type\DateTime $dateFrom, Type\DateTime $dateTo, $interval)
	{
		if (!in_array($interval, array('hour', 'day', 'month'), true))
		{
			throw new Main\ArgumentException('Interval should be the "hour", or "day", or "month".');
		}

		// first, get sum by user
		if ($interval === 'hour')
		{
			$subQuery = new Entity\Query(UserHourTable::getEntity());

			$subQuery->setFilter(array(
				'><HOUR' => array(
					ConvertTimeStamp($dateFrom->getTimestamp(), 'FULL'),
					ConvertTimeStamp($dateTo->getTimestamp(), 'FULL')
				)
			));
		}
		else
		{
			$subQuery = new Entity\Query(UserDayTable::getEntity());

			$subQuery->setFilter(array(
				'><DAY' => array(
					ConvertTimeStamp($dateFrom->getTimestamp()),
					ConvertTimeStamp($dateTo->getTimestamp())
				)
			));
		}

		$subQuery->addSelect(new Entity\ExpressionField('TOTAL_SUM', 'SUM(%s)', 'TOTAL'));

		foreach (UserHourTable::getSectionNames() as $sectionName)
		{
			$subQuery->addSelect(new Entity\ExpressionField($sectionName.'_SUM', 'SUM(%s)', $sectionName));
		}

		$subQuery->setGroup('USER_ID');

		// then get max values
		$query = new Entity\Query($subQuery);

		$query->addSelect(new Entity\ExpressionField('TOTAL', 'MAX(%s)', 'TOTAL_SUM'));

		foreach (UserHourTable::getSectionNames() as $sectionName)
		{
			$query->addSelect(new Entity\ExpressionField($sectionName, 'MAX(%s)', $sectionName.'_SUM'));
		}

		$result = $query->exec();
		$data = $result->fetch();

		return $data;
	}

	public static function getUsersTop(
		$userId,
		$departmentId,
		Type\DateTime $dateFrom,
		Type\DateTime $dateTo,
		$interval,
		$section = null,
		$nonInvolvedOnly = false,
		$from = 0,
		$limit = 100
	)
	{
		if (!in_array($interval, array('hour', 'day', 'month'), true))
		{
			throw new Main\ArgumentException('Interval should be the "hour", or "day", or "month".');
		}

		$data = array();

		// rating for TOTAL activity or for an instrument
		$sumField = ($section) === null ? 'TOTAL' : $section;

		if ($interval === 'hour')
		{
			$query = new Entity\Query(UserHourTable::getEntity());

			$query->setSelect(array(
				'USER_ID',
				new Entity\ExpressionField('SUM_'.$sumField, 'SUM(%s)', $sumField)
			));

			$query->setFilter(array(
				'><HOUR' => array(
					ConvertTimeStamp($dateFrom->getTimestamp(), 'FULL'),
					ConvertTimeStamp($dateTo->getTimestamp(), 'FULL')
				)
			));
		}
		else
		{
			$query = new Entity\Query(UserDayTable::getEntity());

			$query->setSelect(array(
				'USER_ID',
				new Entity\ExpressionField('SUM_'.$sumField, 'SUM(%s)', $sumField)
			));

			$query->setFilter(array(
				'><DAY' => array(
					ConvertTimeStamp($dateFrom->getTimestamp()),
					ConvertTimeStamp($dateTo->getTimestamp())
				)
			));
		}

		if ($sumField == 'TOTAL')
		{
			// count number of used services
			$names = UserHourTable::getSectionNames();

			$fieldExpressions = array_fill(0, count($names), 'CASE WHEN SUM(%s) > 0 THEN 1 ELSE 0 END');
			$serviceCountExpression = join(' + ', $fieldExpressions);

			$query->addSelect(new Entity\ExpressionField('SERVICES_COUNT', $serviceCountExpression, $names));

			if ($nonInvolvedOnly)
			{
				// who didn't use 4 or more instruments
				$query->addFilter('<SERVICES_COUNT', static::INVOLVEMENT_SERVICE_COUNT);
			}
		}
		else
		{
			if ($nonInvolvedOnly)
			{
				// who didn't use instrument
				$query->addFilter('=SUM_'.$sumField, 0);
			}
			else
			{
				// who used it
				$query->addFilter('>SUM_'.$sumField, 0);
			}
		}

		$query->addOrder('SUM_'.$sumField, 'DESC');

		if (!$nonInvolvedOnly)
		{
			// we don't need this for non-involved users
			$query->registerRuntimeField('MYSELF', array(
				'data_type' => 'integer',
				'expression' => array('CASE WHEN %s = '.(int)$userId.' THEN 1 ELSE 0 END', 'USER_ID')
			));

			$query->addOrder('MYSELF', 'DESC');
		}


		$query->setOffset($from);
		$query->setLimit($limit);

		$result = $query->exec();

		while ($row = $result->fetch())
		{
			$_data = array(
				'USER_ID' => $row['USER_ID'],
				'ACTIVITY' => $row['SUM_'.$sumField]
			);

			if ($sumField == 'TOTAL')
			{
				$_data['SERVICES_COUNT'] = $row['SERVICES_COUNT'];
				$_data['IS_INVOLVED'] = ($row['SERVICES_COUNT'] >= static::INVOLVEMENT_SERVICE_COUNT);
			}
			else
			{
				$_data['SERVICES_COUNT'] = null;
				$_data['IS_INVOLVED'] = ($row['SUM_'.$sumField] > 0);
			}

			$data[] = $_data;
		}

		return $data;
	}

	protected static function getHourlyCompanyActivitySince(Type\DateTime $hour = null)
	{
		$query = new Entity\Query('Bitrix\\Intranet\\UStat\\UserHourTable');

		// set all activity columns
		$uStatFields = UserHourTable::getEntity()->getFields();

		foreach ($uStatFields as $uStatField)
		{
			if ($uStatField instanceof Entity\ScalarField && !$uStatField->isPrimary())
			{
				$query->addSelect(new Entity\ExpressionField(
					$uStatField->getName().'_SUM', 'SUM(%s)', $uStatField->getName()
				));
			}
		}

		// add & automatically group by hour
		$query->addSelect('HOUR');

		// add filter by date
		if ($hour !== null)
		{
			$query->setFilter(array('>=HOUR' => \ConvertTimeStamp($hour->getTimestamp(), 'FULL')));
		}

		// collect activity
		$activity = array();

		$result = $query->exec();

		while ($row = $result->fetch())
		{
			foreach ($row as $k => $v)
			{
				if (mb_substr($k, -4) === '_SUM')
				{
					$row[mb_substr($k, 0, -4)] = $v;
					unset($row[$k]);
				}
			}

			$activity[] = $row;
		}

		return $activity;
	}

	protected static function recountDeptartmentsActiveUsers($forUserId = null)
	{
		$updates = array();

		list($deptData, $users) = static::getActivityInfo();

		// prepare data
		if (!empty($forUserId))
		{
			foreach ($deptData as $deptId => $department)
			{
				if (in_array($forUserId, $department['EMPLOYEES']))
				{
					$updates[$deptId] = $department['ACTIVE_USERS'];
				}
			}
		}
		else
		{
			foreach ($deptData as $deptId => $department)
			{
				$updates[$deptId] = $department['ACTIVE_USERS'];
			}
		}

		$currentHour = new Type\DateTime(date('Y-m-d H:00:00'), 'Y-m-d H:00:00');

		foreach ($updates as $deptId => $activeUsersCount)
		{
			$updResult = DepartmentDayTable::update(array('DEPT_ID' => $deptId, 'DAY' => $currentHour), array('ACTIVE_USERS' => $activeUsersCount));

			if (!$updResult->getAffectedRowsCount())
			{
				// if new ACTIVE_USERS value equal one in DB, affectedRows will return 0
				// in this case ignore duplicate entry error while trying to insert same values
				try
				{
					DepartmentDayTable::add(array('DEPT_ID' => $deptId, 'DAY' => $currentHour, 'ACTIVE_USERS' => $activeUsersCount));
				}
				catch (SqlException $e) {}
			}
		}
	}

	protected static function recountCompanyActiveUsers()
	{
		// if no record for today, then
		//  - update last record involment before today (usually yesterday)
		//  - insert new record for today
		// else
		//  - update record

		$currentDay = new Type\DateTime(date('Y-m-d 00:00:00'), 'Y-m-d 00:00:00');

		list($deptData, $users) = static::getActivityInfo();

		// today active users
		$activeUsers = array();

		foreach ($users as $k => $user)
		{
			if (!$user['ABSENT'])
			{
				$activeUsers[$k] = $user;
			}
		}

		// current record
		$todayRow = DepartmentDayTable::getByPrimary(array('DEPT_ID' => 0, 'DAY' => \ConvertTimeStamp(time(), "SHORT")))->fetch();

		// if no record for today, then
		if (empty($todayRow))
		{
			// today is a new day!

			// update last record involvement before today (usually yesterday)
			$lastRow = DepartmentDayTable::getRow(array(
				'filter' => array('=DEPT_ID' => 0, '<DAY' => \ConvertTimeStamp(time(), "SHORT")),
				'order' => array('DAY' => 'DESC'),
				'limit' => 1
			));

			if (!empty($lastRow))
			{
				$lastRowDate = is_object($lastRow['DAY']) ? $lastRow['DAY'] : new Type\Date($lastRow['DAY'], 'Y-m-d');
				static::recountDailyInvolvement($lastRowDate);
			}

			// insert new record for today
			try
			{
				DepartmentDayTable::add(array('DEPT_ID' => 0, 'DAY' => $currentDay, 'ACTIVE_USERS' => count($activeUsers)));
			}
			catch (SqlException $e) {}

			// insert empty users row for unique users stats (we need it for instrument's involvement)
			foreach (array_keys($activeUsers) as $userId)
			{
				try
				{
					UserDayTable::add(array('USER_ID' => $userId, 'DAY' => $currentDay));
				}
				catch (SqlException $e) {}
			}
		}
		else
		{
			// update current record
			if ($todayRow['ACTIVE_USERS'] != count($activeUsers))
			{
				DepartmentDayTable::update(array('DEPT_ID' => 0, 'DAY' => $currentDay), array('ACTIVE_USERS' => count($activeUsers)));
			}
		}
	}

	/**
	 * Recounts involvement and activity score for selected day
	 * @param Type\Date $day
	 */
	protected static function recountDailyInvolvement(Type\Date $day = null)
	{
		// should be called only after recount*ActiveUsers
		// because we need ACTIVE_USERS already set up

		$departments = array();

		if ($day === null)
		{
			$day = new Type\Date();
		}

		// users' departments
		$usersDepartments = static::getUsersDepartments();

		// add "company" for each user
		foreach ($usersDepartments as &$_usersDepartments)
		{
			$_usersDepartments[] = 0;
		}

		// count
		$result = UserDayTable::getList(array('filter' => array(
			'=DAY' => \ConvertTimeStamp($day->getTimestamp(), 'SHORT')
		)));

		while ($row = $result->fetch())
		{
			$invCount = 0;

			if (!isset($usersDepartments[$row['USER_ID']]))
			{
				// skip deleted users
				continue;
			}

			foreach ($row as $k => $v)
			{
				// skip non-activity fields
				if ($k == 'USER_ID' || $k == 'DAY')
				{
					continue;
				}

				// initialize
				foreach ($usersDepartments[$row['USER_ID']] as $deptId)
				{
					if (!isset($departments[$deptId][$k]))
					{
						$departments[$deptId][$k] = 0;
					}
				}

				// summarize
				foreach ($usersDepartments[$row['USER_ID']] as $deptId)
				{
					$departments[$deptId][$k] += $v;
				}

				// increment involvement count
				if ($k != 'TOTAL' && $v > 0)
				{
					++$invCount;
				}
			}

			// check involvement
			if ($invCount >= static::INVOLVEMENT_SERVICE_COUNT)
			{
				foreach ($usersDepartments[$row['USER_ID']] as $deptId)
				{
					if (!isset($departments[$deptId]['INVOLVED']))
					{
						$departments[$deptId]['INVOLVED'] = 0;
					}

					++$departments[$deptId]['INVOLVED'];
				}
			}

		}

		// normalize involved count
		foreach ($departments as &$_department)
		{
			if (!isset($_department['INVOLVED']))
			{
				$_department['INVOLVED'] = 0;
			}
		}

		// update db
		foreach ($departments as $deptId => $activity)
		{
			$activity['INVOLVEMENT'] = new SqlExpression(
				'CASE WHEN ?# > 0 THEN ROUND((?i / ?# * 100), 0) ELSE 0 END',
				'ACTIVE_USERS', $activity['INVOLVED'], 'ACTIVE_USERS'
			);

			unset($activity['INVOLVED']);

			DepartmentDayTable::update(array('DEPT_ID' => $deptId, 'DAY' => $day), $activity);
		}
	}

	protected static function getActivityInfo()
	{
		// real active users
		$allTodayActiveUsers = array();
		$result = UserDayTable::getList(array('filter' => array('=DAY' => \ConvertTimeStamp(time(), "SHORT"))));
		while ($row = $result->fetch())
		{
			$allTodayActiveUsers[$row['USER_ID']] = true;
		}

		// absence data from calendar
		$allAbsenceData = \CIntranetUtils::getAbsenceData(array(
			'DATE_START' => ConvertTimeStamp(mktime(0, 0, 0), 'FULL'), // current day start
			'DATE_FINISH' => ConvertTimeStamp(mktime(23, 59, 59), 'FULL'), // current day end
			'PER_USER' => true
		));

		// departments and its' employees
		$allDepartments = array();

		// userid -> true (working) | false (absent)
		$allUsers = array();

		$companyStructure = \CIntranetUtils::getStructure();

		foreach ($companyStructure['DATA'] as $departmentData)
		{
			// base structure
			$department = array(
				'EMPLOYEES' => array_filter(array_unique(array_merge(
					$departmentData['EMPLOYEES'], array($departmentData['UF_HEAD'])
				))),
				'ACTIVE_USERS' => 0
			);

			foreach ($department['EMPLOYEES'] as $employeeId)
			{
				$allUsers[$employeeId]['DEPARTMENTS'][] = $departmentData['ID'];

				// skip absentee
				if (isset($allUsers[$employeeId]['ABSENT']) && $allUsers[$employeeId]['ABSENT'] === true)
				{
					continue;
				}

				if (!isset($allUsers[$employeeId]['ABSENT']) &&
					isset($allAbsenceData[$employeeId]) && static::checkTodayAbsence($allAbsenceData[$employeeId]))
				{
					// but only if they are really not active today
					if (!isset($allTodayActiveUsers[$employeeId]))
					{
						$allUsers[$employeeId]['ABSENT'] = true;
						continue;
					}
				}

				// remember supposed & really active users
				++$department['ACTIVE_USERS'];

				$allUsers[$employeeId]['ABSENT'] = false;

			}

			$allDepartments[$departmentData['ID']] = $department;
		}

		return array($allDepartments, $allUsers);
	}

	protected static function checkTodayAbsence($absenceData)
	{
		$todayTimestamp = mktime(0, 0, 0);
		$tomorrowTimeStamp = mktime(0, 0, 0, date('n'), date('j')+1);

		foreach ($absenceData as $absence)
		{
			if (!isset($absence['DT_FROM_TS'], $absence['DT_TO_TS']))
			{
				continue;
			}

			if (
				// today is one of absence day
				($absence['DT_FROM_TS'] < $todayTimestamp && $absence['DT_TO_TS'] >= $tomorrowTimeStamp) ||
				// today
				($absence['DT_FROM_TS'] == $todayTimestamp && $absence['DT_TO_TS'] == $todayTimestamp) ||
				// until this day
				($absence['DT_FROM_TS'] < $todayTimestamp && $absence['DT_TO_TS'] == $todayTimestamp) ||
				// since this day
				($absence['DT_FROM_TS'] == $todayTimestamp && $absence['DT_TO_TS'] >= $tomorrowTimeStamp)
			)
			{
				return true;
			}
		}

		return false;
	}

	public static function getUsersDepartments()
	{
		$companyStructure = \CIntranetUtils::getStructure();

		$users = array();

		foreach ($companyStructure['DATA'] as $departmentData)
		{
			$employees = array_filter(array_unique(array_merge(
				$departmentData['EMPLOYEES'], array($departmentData['UF_HEAD'])
			)));

			foreach ($employees as $employee)
			{
				if (!isset($users[$employee]))
				{
					$users[$employee] = array();
				}

				$users[$employee][] = $departmentData['ID'];
			}
		}

		return $users;
	}

	public static function getHeadsOfDepartments()
	{
		$companyStructure = \CIntranetUtils::getStructure();

		$users = array();

		foreach ($companyStructure['DATA'] as $departmentData)
		{
			if (!empty($departmentData['UF_HEAD']))
			{
				$users[] = (int) $departmentData['UF_HEAD'];
			}
		}

		return array_unique($users);
	}

	public static function getFormattedNumber($number)
	{
		static $numberMap = array(
			'0' => '0',
			'1' => '1',
			'2' => '2',
			'3' => '3',
			'4' => '4',
			'5' => '5',
			'6' => '6',
			'7' => '7',
			'8' => '8',
			'9' => '9',
			'.' => 'point',
			'k' => 'k',
			'm' => 'm',
		);

		$strNumber = $number;

		if ($number > 999)
		{
			// short to k|m
			if ($number < 10000)
			{
				// x.y k
				$strNumber = floor($number / 100);
				$strNumber = ($strNumber / 10) . 'k';
			}
			elseif ($number < 1000000)
			{
				//x k
				$strNumber = floor($number / 1000) . 'k';
			}
			elseif ($number < 1000000 * 10)
			{
				// x.y m
				$strNumber = floor($number / 100000);
				$strNumber = ($strNumber / 10) . 'm';
			}
			else
			{
				// x m
				$strNumber = floor($number / 1000000) . 'm';
			}
		}

		$formatted = array();

		foreach (str_split($strNumber) as $char)
		{
			$formatted[] = array('char' => $char, 'code' => $numberMap[$char]);
		}

		return $formatted;
	}

	public static function enableEventHandler()
	{
		CrmEventHandler::registerListeners();
		SocnetEventHandler::registerListeners();
		LikesEventHandler::registerListeners();
		TasksEventHandler::registerListeners();
		ImEventHandler::registerListeners();
		DiskEventHandler::registerListeners();
		MobileEventHandler::registerListeners();
	}

	public static function disableEventHandler()
	{
		SocnetEventHandler::unregisterListeners();
		LikesEventHandler::unregisterListeners();
		TasksEventHandler::unregisterListeners();
		ImEventHandler::unregisterListeners();
		DiskEventHandler::unregisterListeners();
		MobileEventHandler::unregisterListeners();
		CrmEventHandler::unregisterListeners();
	}
}
