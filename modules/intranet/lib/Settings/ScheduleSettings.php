<?php

namespace Bitrix\Intranet\Settings;

use Bitrix\Bitrix24;
use Bitrix\Intranet\Integration\Main\Culture;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

class ScheduleSettings extends AbstractSettings
{
	public const TYPE = 'schedule';

	public static function isAvailable(): bool
	{
		return Loader::includeModule('calendar');
	}

	public function save(): Result
	{
		if (isset($this->data['WEEK_START']))
		{
			$cultureFields['WEEK_START'] = $this->data['WEEK_START'];
		}

		if(!empty($cultureFields))
		{
			Culture::updateCulture($cultureFields);

			if(defined('BX_COMP_MANAGED_CACHE'))
			{
				global $CACHE_MANAGER;
				$CACHE_MANAGER->ClearByTag('sonet_group');
			}
		}


		$workTimeData = [];
		if (isset($this->data['work_time_start']))
		{
			$workTimeData['work_time_start'] = $this->data['work_time_start'];
		}

		if (isset($this->data['work_time_end']) && !empty($this->data['work_time_end']))
		{
			$workTimeData['work_time_end'] = $this->data['work_time_end'];
		}

		if (isset($this->data['week_holidays']))
		{
			$workTimeData['week_holidays'] = implode('|',$this->data['week_holidays']);
		}
		else
		{
			$workTimeData['week_holidays'] = '';
		}

		if (isset($this->data['year_holidays']))
		{
			$workTimeData['year_holidays'] = $this->data['year_holidays'];
		}
		else
		{
			$workTimeData['year_holidays'] = '';
		}

		if (!empty($workTimeData) && Loader::includeModule('calendar'))
		{
			\CCalendar::SetSettings($workTimeData);
		}

		return new Result();
	}

	public function get(): SettingsInterface
	{
		$data = [];
		$dayCodes = [1 => 'MO', 2 => 'TU', 3 => 'WE', 4 => 'TH', 5 => 'FR', 6 => 'SA', 0 => 'SU'];
		$currentSite = Culture::getCurrentSite();
		$values = [];

		foreach ($dayCodes as $i => $day)
		{
			$values[] = [
				'value' => $i,
				'name' => Loc::getMessage('SETTINGS_WEEKDAY_'.$day),
				'selected' => (int)$currentSite['WEEK_START'] === $i,
			];
		}

		$data['WEEK_START'] = [
			'name' => 'WEEK_START',
			'values' => $values,
			'current' => $currentSite['WEEK_START'],
		];

		if (Loader::includeModule('calendar'))
		{
			$calendarSet = \CCalendar::GetSettings(array('getDefaultForEmpty' => false));

			$values = [];
			foreach (['MO', 'TU', 'WE', 'TH', 'FR', 'SA', 'SU'] as $day)
			{
				$values[] = [
					'value' => $day,
					'name' => Loc::getMessage('SETTINGS_WEEKDAY_'.$day),
					'selected' => in_array($day, $calendarSet['week_holidays'])
				];
			}

			$data['WEEK_DAYS'] = [
				'name' => 'week_holidays[]',
				'values' => $values,
				'current' => $calendarSet['week_holidays'],
			];


			//work_time_start
			$values = [];
			$valuesEnd = [];
			for ($i = 0; $i < 24; $i++)
			{
				$values[] = [
					'value' => (string)$i,
					'name' => \CCalendar::FormatTime($i),
					'selected' => (string)$calendarSet['work_time_start'] === (string)$i
				];
				$values[] = [
					'value' => $i .'.30',
					'name' => \CCalendar::FormatTime($i, 30),
					'selected' => (string)$calendarSet['work_time_start'] === $i .'.30'
				];

				$valuesEnd[] = [
					'value' => (string)$i,
					'name' => \CCalendar::FormatTime($i),
					'selected' => (string)$calendarSet['work_time_end'] === (string)$i
				];
				$valuesEnd[] = [
					'value' => $i .'.30',
					'name' => \CCalendar::FormatTime($i, 30),
					'selected' => (string)$calendarSet['work_time_end'] === $i .'.30'
				];
			}

			$data['WORK_TIME_START'] = [
				'name' => 'work_time_start',
				'values' => $values,
				'current' => (string)$calendarSet['work_time_start'],
			];

			$data['WORK_TIME_END'] = [
				'name' => 'work_time_end',
				'values' => $valuesEnd,
				'current' => (string)$calendarSet['work_time_end'],
			];

			$data['year_holidays'] = (string)$calendarSet['year_holidays'];

			$data['TIMEMAN'] = [
				'enabled' => IsModuleInstalled('timeman') || IsModuleInstalled('bitrix24'),
				'restricted' => $this->isTimemanRestricted()
			];
		}
		return new static($data);
	}

	private function isTimemanRestricted(): bool
	{
		return Loader::includeModule('bitrix24') && !Bitrix24\Feature::isFeatureEnabled('timeman');
	}
}