<?php

namespace Bitrix\Intranet\Site\Sections;

use Bitrix\Bitrix24\Feature;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Timeman\Service\DependencyManager;
use COption;

class TimemanSection
{
	public static function getItems(): array
	{
		return [
			static::getWorkTime(),
			static::getWorkReport(),
			static::getSchedules(),
			static::getMonitorReport(),
			static::getBitrix24Time(),
			static::getMeetings(),
			static::getAbsence(),
			static::getPermissions(),
			static::getLoginHistory(),
		];
	}

	public static function getAbsence(): array
	{
		$available = static::isBitrix24() || \CBXFeatures::isFeatureEnabled('StaffAbsence');

		$locked = static::isBitrix24()
			? !(COption::GetOptionString("bitrix24", "absence_limits_enabled", "") !== "Y" || Feature::isFeatureEnabled("absence"))
			: !\CBXFeatures::isFeatureEnabled('StaffAbsence')
		;
		$onclick = '';
		$absenceUrl = SITE_DIR . 'timeman/';

		if ($locked)
		{
			$absenceUrl = '';
			$onclick = 'javascript:BX.UI.InfoHelper.show("limit_absence_management");';
		}

		return [
			'id' => 'absence',
			'title' => Loc::getMessage('TIMEMAN_SECTION_ABSENCE_ITEM_TITLE'),
			'available' => $available,
			'url' => $absenceUrl,
			'locked' => $locked,
			'menuData' => [
				'menu_item_id' => 'menu_absence',
				'is_locked' => $locked,
				'onclick' => $onclick,
			],
		];
	}

	public static function getLoginHistory(): array
	{
		$locked = static::isBitrix24() && !Feature::isFeatureEnabled('user_login_history');
		$onclick = '';
		$historyUrl = static::getUserLoginHistoryUrl();
		$available = true;

		if (static::isBitrix24() && (\CBitrix24::getPortalZone() === 'ua'))
		{
			$available = false;
		}

		if ($locked)
		{
			$onclick = 'javascript:BX.UI.InfoHelper.show("limit_office_login_history");';
			$historyUrl = false;
		}

		return [
			'id' => 'login_history',
			'title' => Loc::getMessage('TIMEMAN_SECTION_USER_LOGIN_HISTORY'),
			'available' => $available,
			'url' => $historyUrl,
			'locked' => $locked,
			'menuData' => [
				'is_locked' => $locked,
				'menu_item_id' => 'menu_login_history',
				'onclick' => $onclick,
			],
		];
	}

	public static function getWorkTime(): array
	{
		$available =
			static::isBitrix24()
				? static::isTimemanAvailable()
				: static::isTimemanInstalled() && \CBXFeatures::isFeatureEnabled('timeman')
		;

		$locked = false;
		$onclick = '';
		$workTimeUrl = SITE_DIR . 'timeman/timeman.php';

		if (static::isBitrix24() && !static::isTimemanInstalled())
		{
			$locked = true;
			$workTimeUrl = '';
			$onclick = 'javascript:BX.UI.InfoHelper.show("limit_office_worktime");';
		}

		return [
			'id' => 'worktime',
			'title' => Loc::getMessage('TIMEMAN_SECTION_WORK_TIME_ITEM_TITLE'),
			'available' => $available,
			'url' => $workTimeUrl,
			'locked' => $locked,
			'menuData' => [
				'menu_item_id' => 'menu_timeman',
				'is_locked' => $locked,
				'onclick' => $onclick,
			],
		];
	}

	public static function getBitrix24Time(): array
	{
		$available =
			static::isBitrix24()
				? static::isTimemanAvailable() && static::isBitrix24TimeAvailable()
				: static::isBitrix24TimeAvailable()
		;

		return [
			'id' => 'bitrix24_time',
			'title' => Loc::getMessage('TIMEMAN_SECTION_BITRIX24_TIME_ITEM_TITLE'),
			'available' => $available,
			'url' => static::isBitrix24() ? '/timeman/bitrix24time.php' : SITE_DIR . 'timeman/b24time.php',
			'menuData' => [
				'menu_item_id' => 'menu_bitrix24time',
			],
		];
	}

	public static function isBitrix24TimeAvailable()
	{
		//return Loader::includeModule('faceid') && FaceId::isClosed();

		if (Loader::includeModule('faceid'))
		{
			$last = \Bitrix\Faceid\TrackingWorkdayTable::query()
				->addSelect('DATE')
				->addOrder('ID', 'DESC')
				->setCacheTtl(3600)
				->setLimit(1)
				->fetch();

			if (!empty($last['DATE']) && (time() - $last['DATE']->getTimestamp()) < 3600*24*120)
			{
				return true;
			}
		}

		return false;
	}

	public static function getMonitorReport(): array
	{
		$available =
			Loader::includeModule('timeman')
			&& class_exists('\Bitrix\Timeman\Monitor\Config')
			&& method_exists('\Bitrix\Timeman\Monitor\Config', 'isAvailable')
			&& \Bitrix\Timeman\Monitor\Config::isAvailable()
		;

		return [
			'id' => 'monitor_report',
			'title' => Loc::getMessage('TIMEMAN_SECTION_MONITOR_REPORT_ITEM_TITLE'),
			'available' => $available,
			'url' => SITE_DIR . 'timeman/monitor_report.php',
			'menuData' => [
				'menu_item_id' => 'menu_pwt_report',
			],
		];
	}

	public static function getWorkReport(): array
	{
		$available =
			static::isBitrix24()
				? static::isTimemanAvailable()
				: static::isTimemanInstalled() && \CBXFeatures::isFeatureEnabled('timeman')
		;

		$onclick = '';
		$locked = false;
		$workReportUrl = SITE_DIR . 'timeman/work_report.php';
		if (static::isBitrix24() && !static::isTimemanInstalled())
		{
			$locked = true;
			$workReportUrl = '';
			$onclick = 'javascript:BX.UI.InfoHelper.show("limit_office_reports");';
		}

		return [
			'id' => 'work_report',
			'title' => Loc::getMessage('TIMEMAN_SECTION_WORK_REPORT_ITEM_TITLE'),
			'available' => $available,
			'url' => $workReportUrl,
			'locked' => $locked,
			'menuData' => [
				'menu_item_id' => 'menu_work_report',
				'is_locked' => $locked,
				'onclick' => $onclick,
			],
		];
	}

	public static function getSchedules(): array
	{
		$canReadSchedules = false;
		if (Loader::includeModule('timeman'))
		{
			global $USER;
			$permissionsManager = DependencyManager::getInstance()->getUserPermissionsManager($USER);
			$canReadSchedules = $permissionsManager->canReadSchedules();
		}

		$available = false;
		$locked = false;
		$onclick = '';
		$workSchedulesUrl = SITE_DIR . 'timeman/schedules/';
		if (static::isBitrix24())
		{
			$available = static::isTimemanAvailable();
			if (!static::isTimemanInstalled())
			{
				$locked = true;
				$workSchedulesUrl = '';
				$onclick = 'javascript:BX.UI.InfoHelper.show("limit_office_shift_scheduling");';
			}
			else if (!$canReadSchedules)
			{
				$available = false;
			}
		}
		else if ($canReadSchedules && \CBXFeatures::isFeatureEnabled('timeman'))
		{
			$available = true;
		}

		return [
			'id' => 'schedules',
			'title' => Loc::getMessage('TIMEMAN_SECTION_SCHEDULES_ITEM_TITLE'),
			'available' => $available,
			'url' => $workSchedulesUrl,
			'locked' => $locked,
			'menuData' => [
				'menu_item_id' => 'menu_schedules_list',
				'is_locked' => $locked,
				'onclick' => $onclick,
			],
		];
	}

	public static function getPermissions(): array
	{
		$available = false;
		if (Loader::includeModule('timeman'))
		{
			global $USER;
			$permissionsManager = DependencyManager::getInstance()->getUserPermissionsManager($USER);
			$available = $permissionsManager->canUpdateSettings();
		}

		if ($available && !static::isBitrix24())
		{
			$available = \CBXFeatures::isFeatureEnabled('timeman');
		}

		return [
			'id' => 'timeman_permissions',
			'title' => Loc::getMessage('TIMEMAN_SECTION_PERMISSIONS_ITEM_TITLE'),
			'available' => $available,
			'url' => SITE_DIR . 'timeman/settings/permissions/',
			'menuData' => [
				'menu_item_id' => 'menu_worktime_settings_permissions',
			],
		];
	}

	public static function getMeetings(): array
	{
		$available =
			static::isBitrix24()
				? static::isMeetingAvailable()
				: static::isMeetingInstalled() && \CBXFeatures::isFeatureEnabled('Meeting')
		;
		$locked = false;
		$onclick = '';
		$meetingUrl = SITE_DIR . 'timeman/meeting/';

		if (static::isBitrix24() && !static::isMeetingInstalled())
		{
			$locked = true;
			$meetingUrl = '';
			$onclick = 'javascript:BX.UI.InfoHelper.show("limit_office_meetings");';
		}

		return [
			'id' => 'meetings',
			'title' => Loc::getMessage('TIMEMAN_SECTION_MEETINGS_ITEM_TITLE'),
			'available' => $available,
			'url' => $meetingUrl,
			'locked' => $locked,
			'menuData' => [
				'menu_item_id' => 'menu_meeting',
				'is_locked' => $locked,
				'onclick' => $onclick,
			],
		];
	}

	public static function isTimemanInstalled(): bool
	{
		return ModuleManager::isModuleInstalled('timeman');
	}

	public static function isTimemanAvailable(): bool
	{
		return Option::get('bitrix24', 'feature_timeman') !== 'N';
	}

	public static function isMeetingInstalled(): bool
	{
		return ModuleManager::isModuleInstalled('meeting');
	}

	public static function isMeetingAvailable(): bool
	{
		return Option::get('bitrix24', 'feature_meeting') !== 'N';
	}

	public static function isBitrix24(): bool
	{
		return ModuleManager::isModuleInstalled('bitrix24');
	}

	public static function isAvailable(): bool
	{
		$items = static::getItems();
		foreach ($items as $item)
		{
			if (isset($item['available']) && $item['available'] === true)
			{
				return true;
			}
		}

		return false;
	}

	public static function getPath(): string
	{
		return SITE_DIR . 'timeman/';
	}

	public static function getRootMenuItem(): array
	{
		$extraUrls = [];
		foreach (static::getItems() as $item)
		{
			if ($item['available'])
			{
				if (isset($item['url']) && is_string($item['url']))
				{
					$extraUrls[] = $item['url'];
				}

				if (isset($item['extraUrls']) && is_array($item['extraUrls']))
				{
					$extraUrls = array_merge($extraUrls, $item['extraUrls']);
				}
			}
		}

		return [
			Loc::getMessage('TIMEMAN_SECTION_ROOT_ITEM_TITLE'),
			static::getPath(),
			$extraUrls,
			[
				'menu_item_id' => 'menu_timeman',
			],
			'',
		];
	}

	public static function getUserLoginHistoryUrl(): string
	{
		return static::getPath() . 'login-history/';
	}

	public static function getUserLoginHistoryUrlById(int $id): string
	{
		return static::getUserLoginHistoryUrl() . "$id/";
	}
}