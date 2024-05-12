<?php

namespace Bitrix\Intranet\Settings;

use Bitrix\Bitrix24;
use Bitrix\Intranet\Integration\Main\Culture;
use Bitrix\Intranet\Settings\Controls\Field;
use Bitrix\Intranet\Settings\Controls\Section;
use Bitrix\Intranet\Settings\Controls\Selector;
use Bitrix\Intranet\Settings\Controls\Tab;
use Bitrix\Intranet\Settings\Controls\Text;
use Bitrix\Intranet\Settings\Search\SearchEngine;
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

		$data['sectionSchedule'] = new Section(
			'settings-schedule-section-schedule',
			Loc::getMessage('INTRANET_SETTINGS_SECTION_TITLE_SCHEDULE'),
			'ui-icon-set --calendar-1'
		);
		$data['sectionHoliday'] = new Section(
			'settings-schedule-section-holiday',
			Loc::getMessage('INTRANET_SETTINGS_SECTION_TITLE_HOLIDAYS'),
			'ui-icon-set --flag-2',
			false
		);

		$data['tabForCompany'] = new Tab(
			'settings-schedule-tab-for_company',
			[
				'title' => Loc::getMessage('INTRANET_SETTINGS_SECTION_TAB_TITLE_COMPANY')
			]
		);
		$data['tabForDepartment'] = new Tab(
			'settings-schedule-tab-for_department',
			[
				'title' => Loc::getMessage('INTRANET_SETTINGS_SECTION_TAB_TITLE_DEPARTMENT')
			],
			restricted: $this->isTimemanRestricted(),
			bannerCode: 'limit_office_shift_scheduling',
		);

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

		$data['WEEK_START'] = new Selector(
			'settings-schedule-field-week_start',
			'WEEK_START',
			Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_WEEK_START'),
			$values,
			$currentSite['WEEK_START']
		);

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

			$data['WEEK_DAYS'] = new Selector(
				'settings-schedule-field-week_days',
				'week_holidays[]',
				Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_WEEKEND'),
				$values,
				multiValue: $calendarSet['week_holidays']
			);

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

			$data['WORK_TIME_START'] = new Selector(
				'settings-schedule-field-work_time_start',
				'work_time_start',
				Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_WORK_TIME_START'),
				$values,
				(string)$calendarSet['work_time_start']
			);

			$data['WORK_TIME_END'] = new Selector(
				'settings-schedule-field-work_time_end',
				'work_time_end',
				Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_WORK_TIME_END'),
				$valuesEnd,
				(string)$calendarSet['work_time_end']
			);

			$data['year_holidays'] = new Text(
				'settings-schedule-field-year_holidays',
				'year_holidays',
				Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_HOLIDAYS'),
				(string)$calendarSet['year_holidays']
			);

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

	public function find(string $query): array
	{
		$searchEngine = SearchEngine::initWithDefaultFormatter([
			'week_holidays[]' => Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_WEEKEND'),
			'work_time_start' => Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_WORK_TIME_START'),
			'work_time_end' => Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_WORK_TIME_END'),
			'WEEK_START' => Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_WEEK_START'),
			'year_holidays' => Loc::getMessage('INTRANET_SETTINGS_FIELD_LABEL_HOLIDAYS'),
			'settings-schedule-section-schedule' => Loc::getMessage('INTRANET_SETTINGS_SECTION_TITLE_SCHEDULE'),
			'settings-schedule-section-holiday' => Loc::getMessage('INTRANET_SETTINGS_SECTION_TITLE_HOLIDAYS'),
			'settings-schedule-tab-for_company' => Loc::getMessage('INTRANET_SETTINGS_SECTION_TAB_TITLE_COMPANY'),
			'settings-schedule-tab-for_department' => Loc::getMessage('INTRANET_SETTINGS_SECTION_TAB_TITLE_DEPARTMENT'),
		]);

		return $searchEngine->find($query);
	}
}