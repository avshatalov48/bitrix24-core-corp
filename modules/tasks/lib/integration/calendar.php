<?
/**
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 *
 * @access private
 */

namespace Bitrix\Tasks\Integration;

abstract class Calendar extends \Bitrix\Tasks\Integration
{
	const MODULE_NAME = 'calendar';

	public static function getSettings()
	{
		if(!static::includeModule())
		{
			return array();
		}

		return \CCalendar::getSettings(array('getDefaultForEmpty' => false));
	}

	public static function setSettings(array $settings)
	{
		if(!static::includeModule())
		{
			return false;
		}

		$settings = array_intersect_key($settings, array_flip(array(
			'work_time_start', 'work_time_end', 'year_holidays', 'year_workdays', 'week_holidays', 'week_start',
		)));

		if(array_key_exists('week_holidays', $settings) && is_array($settings['week_holidays']))
		{
			$settings['week_holidays'] = implode('|', $settings['week_holidays']);
		}

		\CCalendar::setSettings($settings);
	}
}