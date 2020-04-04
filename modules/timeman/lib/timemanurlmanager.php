<?php
namespace Bitrix\Timeman;

class TimemanUrlManager
{
	const URI_SHIFT_CREATE = 'shiftCreate';
	const URI_SCHEDULE_CREATE = 'scheduleCreate';
	const URI_RECORD_REPORT = 'recordReport';
	const URI_SCHEDULE_UPDATE = 'scheduleUpdate';
	const URI_WORKTIME_STATS = 'worktimeStats';
	const URI_SCHEDULE_SHIFTPLAN = 'scheduleShiftplan';
	const URI_SETTINGS_PERMISSIONS = 'settingsPermissions';

	private function getRoutes()
	{
		return [
			static::URI_SCHEDULE_CREATE => [
				'name' => static::URI_SCHEDULE_CREATE,
				'uri' => '/timeman/schedules/add/',
			],
			static::URI_SETTINGS_PERMISSIONS => [
				'name' => static::URI_SETTINGS_PERMISSIONS,
				'uri' => '/timeman/settings/permissions/',
			],
			static::URI_SHIFT_CREATE => [
				'name' => static::URI_SHIFT_CREATE,
				'uri' => '/timeman/schedules/#SCHEDULE_ID#/shifts/add/',
				'requiredParams' => ['SCHEDULE_ID',],
			],
			static::URI_RECORD_REPORT => [
				'name' => static::URI_RECORD_REPORT,
				'uri' => '/timeman/worktime/records/#RECORD_ID#/report/',
				'requiredParams' => ['RECORD_ID',],
			],
			static::URI_SCHEDULE_UPDATE => [
				'name' => static::URI_SCHEDULE_UPDATE,
				'uri' => '/timeman/schedules/#SCHEDULE_ID#/update/',
				'requiredParams' => ['SCHEDULE_ID',],
			],
			static::URI_WORKTIME_STATS => [
				'name' => static::URI_WORKTIME_STATS,
				'uri' => '/timeman/timeman.php',
			],
			static::URI_SCHEDULE_SHIFTPLAN => [
				'name' => static::URI_SCHEDULE_SHIFTPLAN,
				'uri' => '/timeman/schedules/#SCHEDULE_ID#/shiftplan/',
				'requiredParams' => ['SCHEDULE_ID',],
			],
		];
	}

	/**
	 * @return TimemanUrlManager
	 */
	public static function getInstance()
	{
		return new static();
	}

	public function getUriTo($routeName, $params = [])
	{
		if (!$this->isRouteExists($routeName))
		{
			return ''; // throw exception?
		}
		if (!$this->hasAllRequiredParams($routeName, $params))
		{
			return ''; // throw exception?
		}

		return $this->replaceUriParams($routeName, $params);
	}

	private function getUriByName($routeName)
	{
		return !empty($this->getRouteByName($routeName)) ? $this->getRouteByName($routeName)['uri'] : '';
	}

	private function getRouteByName($routeName)
	{
		return !empty($this->getRoutes()[$routeName]) ? $this->getRoutes()[$routeName] : '';
	}

	private function isRouteExists($routeName)
	{
		return $this->getRouteByName($routeName) !== '';
	}

	private function hasAllRequiredParams($routeName, array $params)
	{
		if (empty($this->getRouteByName($routeName)['requiredParams']))
		{
			return true;
		}
		foreach ($this->getRouteByName($routeName)['requiredParams'] as $paramName)
		{
			if (!array_key_exists($paramName, $params))
			{
				return false;
			}
		}
		return true;
	}

	private function replaceUriParams($routeName, $params)
	{
		$resultUri = $this->getUriByName($routeName);

		$paramsReplaced = [];
		switch ($routeName)
		{
			default:
				foreach ($params as $index => $param)
				{
					$paramsReplaced['#' . $index . '#'] = $param;
				}
				break;
		}
		return strtr($resultUri, $paramsReplaced);
	}
}