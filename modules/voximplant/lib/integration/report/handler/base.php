<?php

namespace Bitrix\Voximplant\Integration\Report\Handler;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Context;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UI\Filter\DateType;
use Bitrix\Main\UI\Filter\NumberType;
use Bitrix\Main\UI\Filter\Options;
use Bitrix\Main\UserTable;
use Bitrix\Main\Web\Uri;
use Bitrix\Report\VisualConstructor\AnalyticBoard;
use Bitrix\Report\VisualConstructor\Handler\BaseReport;
use Bitrix\Report\VisualConstructor\Helper\Filter;
use Bitrix\Report\VisualConstructor\RuntimeProvider\AnalyticBoardProvider;
use Bitrix\Voximplant\Integration\Report\CallType;
use Bitrix\Voximplant\Security\Helper;
use Bitrix\Voximplant\Security\Permissions;
use Bitrix\Voximplant\StatisticTable;
use CTimeZone;

/**
 * Class Base
 * @package Bitrix\Voximplant\Integration\Report\Handler
 */
abstract class Base extends BaseReport
{
	protected const TELEPHONY_DETAIL_URI = '/telephony/detail.php';
	protected const CALL_STATUS_SUCCESS = 'SUCCESS';
	protected const CALL_STATUS_FAILURE = 'FAILURE';

	protected const GROUP_DAY = 1;
	protected const GROUP_MONTH = 2;
	protected const DEFAULT_AVATAR_WIDTH = 23;
	protected const DEFAULT_AVATAR_HEIGHT = 23;

	protected static $withoutNameCount = 0;
	protected static $userFields = [];
	protected static $requiredUserFieldsList = ['ID', 'LOGIN', 'NAME', 'LAST_NAME', 'SECOND_NAME','PERSONAL_PHOTO'];

	private $allowedUserIds;

	protected $reportFilterKeysForSlider = [
		'TIME_PERIOD_from',
		'TIME_PERIOD_to',
		'PORTAL_NUMBER',
		'PHONE_NUMBER'
	];

	/**
	 * Returns the parameters of the filter.
	 *
	 * @return mixed
	 */
	protected function getFilterParameters()
	{
		static $filterParameters = [];

		$filter = $this->getFilter();
		$filterId = $filter->getFilterParameters()['FILTER_ID'];

		if (!$filterParameters[$filterId])
		{
			$options = new Options($filterId, $filter::getPresetsList());
			$fieldList = $filter::getFieldsList();
			$filterParameters[$filterId] = $options->getFilter($fieldList);
		}

		$currentFilterParameters = $filterParameters[$filterId];

		//converting U1 to 1 for dest_selector
		$userId = $currentFilterParameters['PORTAL_USER_ID'];
		if ($userId !== null && $userId !== '')
		{
			$prefix = mb_substr($userId, 0, 1);
			if($prefix === 'U')
			{
				$currentFilterParameters['PORTAL_USER_ID'] = mb_substr($userId, 1);
			}
		}

		return $currentFilterParameters;
	}

	/**
	 * Gets the previous period based on the current one.
	 *
	 * @param Date $from
	 * @param Date $to
	 *
	 * @return array
	 * @throws ArgumentException
	 */
	protected function getPreviousPeriod(Date $from, Date $to): array
	{
		$difference = $to->getTimestamp() - $from->getTimestamp();

		if($difference < 0)
		{
			throw new ArgumentException("Date from should be earlier than date to");
		}

		$to = clone $from;
		$to->add('-1 second');
		$fromTimestamp = $from->getTimestamp() - $difference;
		$from = Date::createFromTimestamp($fromTimestamp);

		return ['from' => $from, 'to' => $to, 'diff' => $difference];
	}

	/**
	 * Returns the filter of the current report or create new.
	 *
	 * @return Filter
	 */
	protected function getFilter(): Filter
	{
		static $filter;
		if ($filter)
		{
			return $filter;
		}

		$boardKey = $this->getWidgetHandler()->getWidget()->getBoardId();
		$board = self::getAnalyticBoardByKey($boardKey);
		if ($board)
		{
			$filter = $board->getFilter();
		}
		else
		{
			$filter = new Filter($boardKey);
		}

		return $filter;
	}

	/**
	 * Adds filter parameters to the query.
	 *
	 * @param Query $query
	 * @param array $filterParameters
	 */
	protected function addToQueryFilterCase(Query $query, array $filterParameters): void
	{
		$allowedUserIds = $this->getAllowedUserIds();
		if (!$filterParameters['PORTAL_USER_ID'] && $allowedUserIds)
		{
			$query->whereIn('PORTAL_USER_ID', $allowedUserIds);
		}

		foreach ($filterParameters as $filterKey => $filterValue)
		{
			if ($filterValue === '')
			{
				continue;
			}

			switch ($filterKey)
			{
				case 'PORTAL_USER_ID':
					if ($allowedUserIds)
					{
						$availableUserIds = array_intersect($allowedUserIds, [$filterValue]);
						$portalUserIds = $availableUserIds ?: [-1];

						$query->whereIn('PORTAL_USER_ID', $portalUserIds);
					}
					else
					{
						$query->whereIn('PORTAL_USER_ID', $filterValue);
					}
					break;

				case 'PORTAL_NUMBER':
					$query->whereIn('PORTAL_NUMBER', $filterValue);
					break;

				case 'PHONE_NUMBER':
					$query->whereIn('PHONE_NUMBER', $filterValue);
					break;

				case 'COMMENT':
					$query->whereLike('COMMENT', $filterValue);
					break;

				case 'INCOMING':
					if (is_array($filterValue))
					{
						$query->whereIn('INCOMING', $filterValue);
					}
					else
					{
						$query->where('INCOMING', '=', $filterValue);
					}
					break;

				case 'STATUS':
					if ($filterValue === self::CALL_STATUS_SUCCESS)
					{
						$query->where('CALL_FAILED_CODE', '=', '200');
					}
					elseif ($filterValue === self::CALL_STATUS_FAILURE)
					{
						$query->where('CALL_FAILED_CODE', '!=', '200');
					}
					break;

				case 'CALL_DURATION_numsel':
					$durationField = 'CALL_DURATION';
					switch ($filterValue)
					{
						case NumberType::SINGLE:
						case NumberType::RANGE:
							$query->whereBetween(
								$durationField,
								$filterParameters[$durationField.'_from'],
								$filterParameters[$durationField.'_to']
							);
							break;
						case NumberType::MORE:
							$query->where($durationField, '>', $filterParameters[$durationField.'_from']);
							break;
						case NumberType::LESS:
							$query->where($durationField, '<', $filterParameters[$durationField.'_from']);
							break;
					}
					break;

				case 'HOUR':
					$dateWithShift = $this->getDateExpressionWithTimeShiftForQuery();
					$query->registerRuntimeField(new ExpressionField(
						'HOUR',
						"hour($dateWithShift)",
						['CALL_START_DATE']
					));
					$query->where('HOUR', '=', $filterValue);
					break;

				case 'DAY_OF_WEEK':
					$dateWithShift = $this->getDateExpressionWithTimeShiftForQuery();
					$query->registerRuntimeField(new \Bitrix\Main\Entity\ExpressionField(
						'DAY_OF_WEEK',
						"dayofweek($dateWithShift) - 1",
						['CALL_START_DATE']
					));
					$query->where('DAY_OF_WEEK', '=', $filterValue);
					break;
			}
		}
	}

	protected function getDateExpressionWithTimeShiftForQuery(): string
	{
		$offset = CTimeZone::GetOffset();
		$sign = ($offset > 0 ? '-' : '+');
		$difference = abs($offset);

		return "subdate(%s, interval $sign".$difference." second)";
	}

	/**
	 * Adds a DATE field and grouping based on the user's time zone and time period to query.
	 *
	 * @param Query $query
	 *
	 * @param bool $useTimePeriod
	 *
	 * @throws ArgumentException
	 * @throws SystemException
	 */
	public function addDateWithGrouping(Query $query, bool $useTimePeriod = false): void
	{
		$dateWithShift = $this->getDateExpressionWithTimeShiftForQuery();

		if ($useTimePeriod)
		{
			$timePeriod = $this->getFilterParameters()['TIME_PERIOD_datesel'];
			switch ($timePeriod)
			{
				case DateType::YEAR:
				case DateType::QUARTER:
				case DateType::CURRENT_QUARTER:
					$expression = "date_format($dateWithShift, \"%%Y-%%m\")";
					break;
				default:
					$expression = "date($dateWithShift)";
					break;
			}
		}
		else
		{
			$expression = "date($dateWithShift)";
		}

		$query->registerRuntimeField(new ExpressionField(
			'DATE',
			$expression,
			['CALL_START_DATE']
		));

		$query->addSelect('DATE');
		$query->addGroup('DATE');
	}

	/**
	 * Return valid interval for insert into query.
	 *
	 * @param $timePeriodDatasel
	 * @param int $seconds
	 *
	 * @return string
	 */
	protected function getDateInterval($timePeriodDatasel, int $seconds = 0): string
	{
		$interval = '';

		switch ($timePeriodDatasel)
		{
			case DateType::LAST_WEEK:
			case DateType::CURRENT_WEEK:
				$interval = '1 week';
				break;

			case DateType::LAST_MONTH:
			case DateType::CURRENT_MONTH:
			case DateType::MONTH:
			case DateType::CURRENT_QUARTER:
			case DateType::QUARTER:
				$interval = round($seconds / 2592000) . ' month';
				break;

			case DateType::YEAR:
				$interval = round($seconds / 31536000) . ' year';
				break;

			case DateType::LAST_7_DAYS:
			case DateType::LAST_30_DAYS:
			case DateType::LAST_60_DAYS:
			case DateType::LAST_90_DAYS:
			case DateType::PREV_DAYS:
			case DateType::RANGE:
				$interval = round($seconds / 86400) . ' day';
				break;

			//tmp solution, remove when the problem with "undefined" in the report filter will be solved.
			default:
				$interval = -1 . ' month';
				break;
		}

		return $interval;
	}

	/**
	 * Returns the interval between dates in seconds.
	 *
	 * @param Date $from
	 * @param Date $to
	 *
	 * @return int
	 */
	protected function getDifferenceInSeconds(Date $from, Date $to): int
	{
		return $to->getTimestamp() - $from->getTimestamp();
	}

	/**
	 * Returns a board with a report by its key.
	 *
	 * @param $key
	 *
	 * @return AnalyticBoard|null
	 */
	public static function getAnalyticBoardByKey($key): ?AnalyticBoard
	{
		$boardProvider = new AnalyticBoardProvider();
		$boardProvider->addFilter('boardKey', $key);

		return $boardProvider->execute()->getFirstResult();
	}

	/**
	 * Creates a link to an $baseUri with parameters from $params.
	 *
	 * @param string $baseUri
	 * @param array $params
	 *
	 * @return string
	 */
	public function createUrl(string $baseUri, array $params = []): string
	{
		$uri = new Uri($baseUri);
		$uri->addParams([
			'from_analytics' => 'Y',
			'report_id' => $this->getReport()->getGId(),
		]);

		foreach ($params as $key => $value)
		{
			if (!$value)
			{
				unset($params[$key]);
			}
		}

		if (!empty($params))
		{
			$uri->addParams($params);
		}
		return $uri->getUri();
	}

	/**
	 * Formats the date for insertion into graph.
	 *
	 * @param $date
	 *
	 * @return string
	 */
	protected function formatDateForGraph($date): string
	{
		if (!($date instanceof Date))
		{
			return '-';
		}

		return FormatDate($this->getDateFormatForGraph(), $date);
	}

	/**
	 * Formats the date for insertion into grid.
	 *
	 * @param $date
	 *
	 * @return string
	 */
	protected function formatDateForGrid($date): string
	{
		if (!($date instanceof Date))
		{
			return '&mdash;';
		}

		return FormatDate($this->getDateFormatForGrid(), $date);
	}

	/**
	 * Gets the date format for a graph.
	 *
	 * @return string
	 */
	protected function getDateFormatForGraph(): ?string
	{
		switch ($this->getDateGrouping())
		{
			case static::GROUP_DAY:
				return Context::getCurrent()->getCulture()->getDayMonthFormat();
			case static::GROUP_MONTH:
				return "f Y";
			default:
				return Context::getCurrent()->getCulture()->getLongDateFormat();
		}
	}

	/**
	 * Gets the date format for a grid.
	 *
	 * @return string
	 */
	protected function getDateFormatForGrid(): ?string
	{
		switch ($this->getDateGrouping())
		{
			case static::GROUP_DAY:
				return Context::getCurrent()->getCulture()->getShortDateFormat();
			case static::GROUP_MONTH:
				return "f Y";
			default:
				return Context::getCurrent()->getCulture()->getLongDateFormat();
		}
	}

	public function formatDuration($duration)
	{
		if ($duration == null)
		{
			return 0;
		}

		$duration = (int)$duration;
		$hours = floor($duration / 3600);
		$minutes = floor($duration / 60) - ($hours * 60);
		$seconds = $duration % 60;

		$text = '';
		if ($hours > 0)
		{
			$text = $hours .' '. Loc::getMessage('TELEPHONY_REPORT_BASE_HOUR');
		}

		if ($minutes > 0)
		{
			$minutesText = $minutes .' '. Loc::getMessage('TELEPHONY_REPORT_BASE_MIN');
			$text .= ($text === '' ? $minutesText : ', ' . $minutesText);
		}
		elseif ($seconds > 0)
		{
			$secondsText = $seconds .' '. Loc::getMessage('TELEPHONY_REPORT_BASE_SEC');
			$text .= ($text === '' ? $secondsText : ', ' . $secondsText);
		}

		return $text;
	}

	public function formatDurationByMinutes($duration)
	{
		if ($duration == null)
		{
			return null;
		}

		$duration = (int)$duration;
		$hours = floor($duration / 60);
		$minutes = $duration - ($hours * 60);

		if ($hours < 1 && $minutes < 1)
		{
			return Loc::getMessage('TELEPHONY_REPORT_BASE_LESS_THAN_MINUTE');
		}

		$text = '';
		if ($hours > 0)
		{
			$text = $hours .' '. Loc::getMessage('TELEPHONY_REPORT_BASE_HOUR');
		}

		if ($minutes >= 1)
		{
			$minutesText = $minutes .' '. Loc::getMessage('TELEPHONY_REPORT_BASE_MIN');
			$text .= ($text === '' ? $minutesText : ', ' . $minutesText);
		}

		return $text;
	}

	/**
	 * Returns the type of grouping by date.
	 *
	 * @return int
	 */
	protected function getDateGrouping(): ?int
	{
		$filter = $this->getFilterParameters();

		switch ($filter['TIME_PERIOD_datesel'])
		{
			case DateType::YEAR:
			case DateType::QUARTER:
			case DateType::CURRENT_QUARTER:
				return static::GROUP_MONTH;
			default:
				return static::GROUP_DAY;
		}
	}

	/**
	 * Converts a value to float, int, or null for use in a report view.
	 *
	 * @param $value
	 *
	 * @return int|string
	 */
	public function formatPeriodCompare($value)
	{
		if (!$value || !is_numeric($value))
		{
			return null;
		}

		return $value + 0;
	}

	/**
	 * @param $requestParameters
	 *
	 * @return Query
	 * @throws ArgumentException
	 * @throws ObjectException
	 * @throws SystemException
	 */
	public function prepareEntityListFilter($requestParameters): Query
	{
		$query = StatisticTable::query();
		$fields = StatisticTable::getEntity()->getFields();

		foreach ($fields as $field)
		{
			$query->addSelect($field->getName());
		}

		$sliderFilterParameters = $this->mergeRequestWithReportFilter($requestParameters->toArray());

		$this->addToQueryFilterCase($query, $sliderFilterParameters);

		$startDate = $requestParameters->get('START_DATE_from') ?: $sliderFilterParameters['TIME_PERIOD_from'];
		$finishDate = $requestParameters->get('START_DATE_to') ?: $sliderFilterParameters['TIME_PERIOD_to'];

		$query->whereBetween(
			'CALL_START_DATE',
			DateTime::createFromUserTime($startDate),
			DateTime::createFromUserTime($finishDate)
		);

		return $query;
	}

	protected function getReportFilterKeysForSlider(): array
	{
		return ($this->reportFilterKeysForSlider) ?: [];
	}

	protected function mergeRequestWithReportFilter($requestFilter): array
	{
		$reportFilterParameters = $this->getFilterParameters();
		$reportKeysForSlider = $this->getReportFilterKeysForSlider();

		if (!$reportFilterParameters || !$reportKeysForSlider)
		{
			return $requestFilter;
		}

		$sliderFilterParameters = [];
		foreach ($reportFilterParameters as $key => $value)
		{
			if (in_array($key, $reportKeysForSlider, true))
			{
				$sliderFilterParameters[$key] = $value;
			}
		}

		$requestParametersForMerge = array_diff($requestFilter, $sliderFilterParameters);

		return array_merge($sliderFilterParameters, $requestParametersForMerge);
	}

	/**
	 * Returns an array with a date object,
	 * start and end dates of the period in string format to insert into the request.
	 *
	 * @param $date
	 *
	 * @return mixed
	 * @throws ObjectException
	 */
	public function getDateForUrl($date)
	{
		$groupByDay = $date instanceof Date;

		if ($groupByDay)
		{
			$result['date'] = $date;
			$result['start'] = $date->toString() . ' 00:00:00';
			$result['finish'] = $date->toString() . ' 23:59:59';
		}
		else
		{
			$result['date'] = new Date($date.'-01', 'Y-m-d');
			$result['start'] = $result['date']->toString();
			$result['finish'] = $result['date']->add('1 month -1day')->toString();
		}

		return $result;
	}

	/**
	 * Returns [id, name, link, icon] for the specified user id.
	 *
	 * @param int $userId Id of the user.
	 * @param array $params Additional optional parameters
	 *   <li> avatarWidth int
	 *   <li> avatarHeight int
	 *
	 * @return array|null
	 * @throws ArgumentException
	 * @throws SystemException
	 * @throws ObjectPropertyException
	 */
	public function getUserInfo($userId, array $params = []): ?array
	{
		static $users = [];

		$userId = (int)$userId;

		if (!$userId)
		{
			self::$withoutNameCount++;
			$defaultName = Loc::getMessage('TELEPHONY_REPORT_BASE_USER_DEFAULT_NAME');
			$userName = (self::$withoutNameCount < 2 ? $defaultName : $defaultName . ' ' . self::$withoutNameCount);

			return ['name' => $userName];
		}

		if(isset($users[$userId]))
		{
			return $users[$userId];
		}

		// prepare link to profile
		$replaceList = ['user_id' => $userId];
		$template = '/company/personal/user/#user_id#/';
		$link = \CComponentEngine::makePathFromTemplate($template, $replaceList);

		$this->preloadUserInfo([$userId]);
		$userFields = static::$userFields[$userId];

		if (!$userFields)
		{
			self::$withoutNameCount++;
			$defaultName = Loc::getMessage('TELEPHONY_REPORT_BASE_USER_DEFAULT_NAME');
			$userName = (self::$withoutNameCount < 2 ? $defaultName : $defaultName . ' ' . self::$withoutNameCount);

			return ['name' => $userName];
		}

		// format name
		$userName = \CUser::FormatName(
			\CSite::GetNameFormat(),
			[
				'LOGIN' => $userFields['LOGIN'],
				'NAME' => $userFields['NAME'],
				'LAST_NAME' => $userFields['LAST_NAME'],
				'SECOND_NAME' => $userFields['SECOND_NAME']
			],
			true,
			false
		);

		if (empty($userName))
		{
			self::$withoutNameCount++;
			$defaultName = Loc::getMessage('TELEPHONY_REPORT_BASE_USER_DEFAULT_NAME');
			$userName = (self::$withoutNameCount < 2 ? $defaultName : $defaultName . ' ' . self::$withoutNameCount);
		}

		// prepare icon
		$fileTmp = \CFile::ResizeImageGet(
			$userFields['PERSONAL_PHOTO'],
			[
				'width' => $params['avatarWidth'] ?? static::DEFAULT_AVATAR_WIDTH,
				'height' => $params['avatarHeight'] ?? static::DEFAULT_AVATAR_HEIGHT
			],
			BX_RESIZE_IMAGE_EXACT,
			false,
			false,
			true
		);
		$userIcon = $fileTmp['src'] ?: '/bitrix/js/ui/icons/b24/images/ui-user.svg?v2';

		$users[$userId] = [
			'id' => $userId,
			'name' => $userName,
			'link' => $link,
			'icon' => $userIcon
		];

		return $users[$userId];
	}

	/**
	 * Gets users fields by Ids
	 *
	 * @param array $userIds
	 *
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function preloadUserInfo(array $userIds): void
	{
		$missingUserIds = array_diff($userIds, array_keys(static::$userFields));
		if (empty($missingUserIds))
		{
			return;
		}

		$cursor = UserTable::getList([
			'select' => static::$requiredUserFieldsList,
			'filter' => [
				'=ID' => $missingUserIds
			]
		]);

		foreach ($cursor->getIterator() as $row)
		{
			static::$userFields[$row['ID']] = $row;
		}
	}

	/**
	 * Adds a date shift for the previous period.
	 *
	 * @param Query $query
	 * @param $timePeriodDatasel
	 *
	 * @param string $dateDifference
	 *
	 * @throws ArgumentException
	 * @throws SystemException
	 */
	protected function addIntervalByDatasel(Query $query, $timePeriodDatasel, string $dateDifference): void
	{
		switch ($timePeriodDatasel)
		{
			case DateType::YEAR:
			case DateType::QUARTER:
			case DateType::CURRENT_QUARTER:
				$expression = "date_format(subdate(date(%s), interval -$dateDifference), '%%Y-%%m')";
				break;
			default:
				$expression = "subdate(date(%s), interval -$dateDifference)";
				break;
		}

		$query->registerRuntimeField(new ExpressionField(
			'PREVIOUS_DATE',
			$expression,
			['CALL_START_DATE']
		));
	}

	/**
	 * Add a field to query for counting the number of calls depending on the type of call.
	 *
	 * @param Query $query
	 * @param $callType
	 * @param string $columnName
	 * @param bool $isMainQuery
	 *
	 * @throws ArgumentException
	 * @throws SystemException
	 */
	protected function addCallTypeField(Query $query, $callType, string $columnName, bool $isMainQuery = false): void
	{
		switch ($callType)
		{
			case CallType::INCOMING:
				$expression = 'count(if(((%s = 2 or %s = 3) and %s = 200), 1, null))';
				$buildFrom = ['INCOMING', 'INCOMING', 'CALL_FAILED_CODE'];
				break;
			case CallType::OUTGOING:
				$expression = 'count(if(%s = 1, 1, null))';
				$buildFrom = ['INCOMING'];
				break;
			case CallType::MISSED:
				$expression = 'count(if(%s = 2 and %s <> 200, 1, null))';
				$buildFrom = ['INCOMING', 'CALL_FAILED_CODE'];
				break;
			case CallType::CALLBACK:
				$expression = 'count(if(%s = 4, 1, null))';
				$buildFrom = ['INCOMING'];
				break;
			default:
				$expression = 'count(%s)';
				$buildFrom = ['INCOMING'];
				break;
		}

		if ($isMainQuery)
		{
			$query->addSelect( 'previous.' . $columnName, 'PREVIOUS_' . $columnName);
		}

		$query->addSelect($columnName);
		$query->registerRuntimeField(new ExpressionField(
			$columnName,
			$expression,
			$buildFrom
		));
	}

	/**
	 * Adds a field comparison with the previous period to the query.
	 *
	 * @param Query $query
	 * @param string $columnName
	 *
	 * @throws ArgumentException
	 * @throws SystemException
	 */
	protected function addCallTypeCompareField(Query $query, string $columnName): void
	{
		$query->addSelect($columnName . '_COMPARE');
		$query->registerRuntimeField(new ExpressionField(
			$columnName . '_COMPARE',
			'if(%s = 0, null, round((%s - %s) / %s * 100, 1))',
			[$columnName, $columnName, 'previous.' . $columnName, 'previous.' . $columnName]
		));
	}

	protected function isCurrentUserHasAccess(): bool
	{
		$allowedIds = $this->getAllowedUserIds();

		$isAllowedAll = ($allowedIds === null);
		$hasAllowedIds = (is_array($allowedIds) && $allowedIds[0]);

		return ($isAllowedAll || $hasAllowedIds);
	}

	protected function getAllowedUserIds()
	{
		return $this->allowedUserIds;
	}

	public function __construct()
	{
		parent::__construct();

		$this->allowedUserIds = Helper::getAllowedUserIds(
			Helper::getCurrentUserId(),
			Permissions::createWithCurrentUser()->getPermission(
				Permissions::ENTITY_CALL_DETAIL,
				Permissions::ACTION_VIEW
			)
		);
	}
}
