<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2017 Bitrix
 */

namespace Bitrix\Crm\SiteButton;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;


Loc::loadMessages(__FILE__);

/**
 * Class WorkTime
 * @package Bitrix\Crm\SiteButton
 */
class WorkTime
{
	/** @var array $data Data */
	protected $externalType = null;
	protected $externalId = null;

	/** @var array $data Data */
	protected $data = array();

	public function __construct()
	{
		$this->data = $this->getDefaultArray();
	}

	/**
	 * Set work time settings as array
	 * @param array $data Work time data
	 * @return array
	 */
	public function setArray(array $data = array())
	{
		$this->data = $this->getDefaultArray();
		foreach ($data as $key => $value)
		{
			if (!isset($this->data[$key]))
			{
				continue;
			}

			$this->data[$key] = $this->prepare($key, $value);
		}
	}

	/**
	 * Returns work time settings as array
	 * @return array
	 */
	public function getArray()
	{
		return $this->data;
	}

	protected function prepare($key, $value)
	{
		switch ($key)
		{
			case 'ENABLED':
				$value = (bool) $value;
				break;

			case 'TIME_FROM':
			case 'TIME_TO':
				$list = self::getTimeList();
				$value = isset($list[$value]) ? $value : '';
				break;

			case 'TIME_ZONE':
				$list = self::getTimeZoneList();
				$value = isset($list[$value]) ? $value : '';
				break;

			case 'DAY_OFF':
				$list = self::getDayOffList();
				if (!is_array($value))
				{
					$value = array();
				}
				else
				{
					trimArr($value);
					$value = array_intersect($value, $list);
				}
				break;

			case 'HOLIDAYS':
				if (!is_array($value))
				{
					$value = array();
				}
				else
				{
					trimArr($value);
				}
				break;
		}

		return $value;
	}

	public static function getDefaultArray()
	{
		static $data = null;

		if ($data === null)
		{
			$holidays = array();
			$dayOff = array();
			$timeFrom = '9';
			$timeTo = '18';
			if (Loader::includeModule("calendar"))
			{
				$calendarSettings = \CCalendar::getSettings();

				$holidays = $calendarSettings['year_holidays'];
				if (!is_array($holidays))
				{
					$holidays = explode(',', $holidays);
					trimArr($holidays);
					$holidays = array_values($holidays);
				}

				$dayOff = $calendarSettings['week_holidays'];
				if (!is_array($dayOff))
				{
					$dayOff = array();
				}
				trimArr($dayOff);

				$timeFrom = $calendarSettings['work_time_start'];
				$timeTo = $calendarSettings['work_time_end'];
			}

			$data = array(
				'ENABLED' => false,
				'TIME_FROM' => $timeFrom,
				'TIME_TO' => $timeTo,
				'TIME_ZONE' => self::getTimeZoneByLanguage(),
				'HOLIDAYS' => $holidays,
				'DAY_OFF' => $dayOff,
				'ACTION_RULE' => '',
				'ACTION_TEXT' => '',
			);
		}

		return $data;
	}

	/**
	 * Returns work time settings for js
	 * @param array $workTime Work time data
	 * @return array
	 */
	public static function convertToJS($workTime = array())
	{
		static $timeZoneOffset = null;
		if ($timeZoneOffset === null)
		{
			$date = new \DateTime();
			if (!empty($workTime['TIME_ZONE']))
			{
				$dateTimeZone = new \DateTimeZone($workTime['TIME_ZONE']);
			}
			else
			{
				$dateTimeZone = $date->getTimezone();
			}
			$timeZoneOffset = $dateTimeZone->getOffset($date);
		}

		$holidays = count($workTime['HOLIDAYS']) > 0 ? $workTime['HOLIDAYS'] : null;
		$dayOffCodes = count($workTime['DAY_OFF']) > 0 ? $workTime['DAY_OFF'] : null;
		if ($dayOffCodes)
		{
			$days = array('SU' => 0, 'MO' => 1, 'TU' => 2, 'WE' => 3, 'TH' => 4, 'FR' => 5, 'SA' => 6);
			$dayOff = array();
			foreach ($dayOffCodes as $dayOffCode)
			{
				$dayOff[] = $days[$dayOffCode];
			}
		}
		else
		{
			$dayOff = null;
		}

		return array(
			'timeZoneOffset' => $timeZoneOffset / 60,
			'timeFrom' => (float) $workTime['TIME_FROM'],
			'timeTo' => (float) $workTime['TIME_TO'],
			'holidays' => $holidays,
			'dayOff' => $dayOff,
			'actionRule' => $workTime['ACTION_RULE'],
			'actionText' => $workTime['ACTION_TEXT'],
		);
	}

	/**
	 * Returns work time settings
	 *
	 * @return array
	 */
	public static function getDictionaryArray()
	{
		return array(
			'TIME_ZONE' => array(
				'ENABLED' => self::isTimeZoneEnabled(),
				'LIST' => self::getTimeZoneList(),
				'DEFAULT' => self::getTimeZoneByLanguage(),
			),
			'WEEK_DAY_LIST' => self::getDayOffList(),
			'NAMED_WEEK_DAY_LIST' => self::getDayOffNamedList(),
			'TIME_LIST' => self::getTimeList(),
			'ACTIONS' => array(
				'callback' => array(
					'' => Loc::getMessage('CRM_SITE_BUTTON_WORK_TIME_ACTIONS_HIDE'),
					'text' => Loc::getMessage('CRM_SITE_BUTTON_WORK_TIME_ACTIONS_TEXT'),
				)
			),
			'ACTION_TEXT' => array(
				'callback' => Loc::getMessage('CRM_SITE_BUTTON_WORK_TIME_ACTIONS_TEXT_CALLBACK'),
			)
		);
	}

	/**
	 * Returns Time zone string
	 *
	 * @return string Time zone
	 */
	public static function getTimeZoneByLanguage()
	{
		$timeZone = null;
		switch (LANGUAGE_ID)
		{
			case 'ru':
				$timeZone = 'Europe/Moscow';
				break;

			case 'de':
				$timeZone = 'Europe/Berlin';
				break;

			case 'ua':
				$timeZone = 'Europe/Kiev';
				break;

			default:
				$timeZone = 'America/New_York';
				break;
		}

		return $timeZone;
	}

	/**
	 * Returns is time zones enabled
	 *
	 * @return bool
	 */
	public static function isTimeZoneEnabled()
	{
		return \CTimeZone::enabled();
	}

	/**
	 * Returns list of time zones
	 *
	 * @return array
	 */
	public static function getTimeZoneList()
	{
		return \CTimeZone::getZones();
	}

	/**
	 * Returns list of days of week codes
	 *
	 * @return array
	 */
	public static function getDayOffList()
	{
		return array('MO', 'TU', 'WE', 'TH', 'FR', 'SA', 'SU');
	}

	/**
	 * Returns list of days of week with names
	 *
	 * @return array
	 */
	public static function getDayOffNamedList()
	{
		$result = array();
		$days = self::getDayOffList();
		foreach($days as $day)
		{
			$result[$day] = Loc::getMessage('CRM_SITE_BUTTON_WORK_TIME_DAY_' . $day);
		}

		return $result;
	}

	/**
	 * Returns list of times
	 *
	 * @return array
	 */
	public static function getTimeList()
	{
		if (!Loader::includeModule('calendar'))
		{
			return array();
		}

		$result = array();
		$result[strval(0)] = \CCalendar::formatTime(0, 0);
		for ($i = 0; $i < 24; $i++)
		{
			if ($i !== 0)
			{
				$result[strval($i)] = \CCalendar::formatTime($i, 0);
				$result[strval($i)] = \CCalendar::formatTime($i, 0);
			}
			$result[strval($i).'.30'] = \CCalendar::formatTime($i, 30);
			$result[strval($i).'.30'] = \CCalendar::formatTime($i, 30);
		}
		$result[strval('23.59')] = \CCalendar::formatTime(23, 59);

		return $result;
	}
}
