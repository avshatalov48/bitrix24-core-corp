<?php

namespace Bitrix\Imopenlines;

use Bitrix\Main\Config\Option;
use Bitrix\Main\ModuleManager;

class Limit
{
	const OPTION_TRACKER_LIMIT = "tracker_limit";
	const OPTION_LAST_TRACKER_COUNTER_UPDATE = "tracker_month";
	const TRACKER_COUNTER = "tracker_count";

	public static function isDemoLicense()
	{
		if (!\CModule::IncludeModule('bitrix24'))
			return false;

		return \CBitrix24::getLicenseType() == 'demo';
	}

	public static function getLinesLimit()
	{
		if (!\CModule::IncludeModule('bitrix24'))
			return 0;

		if (\CBitrix24::IsNfrLicense())
			return 0;

		if (in_array(\CBitrix24::getLicenseType(), Array('company', 'demo', 'edu', 'bis_inc', 'crm', 'team')))
			return 0;

		if (in_array(\CBitrix24::getLicenseType(), Array('project')))
			return Option::get('imopenlines', 'demo_max_openlines', 1);

		if (in_array(\CBitrix24::getLicenseType(), Array('tf')))
			return 2;

		return Option::get('imopenlines', 'team_max_openlines', 2);
	}

	public static function getLicenseUsersLimit()
	{
		if (!\CModule::IncludeModule('bitrix24'))
			return false;

		if (\CBitrix24BusinessTools::isLicenseUnlimited())
			return false;

		return \CBitrix24BusinessTools::getUnlimUsers();
	}

	public static function canUseQueueAll()
	{
		if (!\CModule::IncludeModule('bitrix24'))
			return true;

		if (\CBitrix24::getLicenseType() != 'project')
			return true;

		return !\Bitrix\Main\Config\Option::get('imopenlines', 'limit_queue_all');
	}

	public static function canUseVoteClient()
	{
		if (!\CModule::IncludeModule('bitrix24'))
			return true;

		return \CBitrix24::getLicenseType() != 'project';
	}

	public static function canUseVoteHead()
	{
		if (!\CModule::IncludeModule('bitrix24'))
			return true;

		return !in_array(\CBitrix24::getLicenseType(), Array('project', 'tf'));
	}

	/**
	 * @deprecated
	 * TODO: delete
	 */
	public static function getTrackerLimit()
	{
		if(!ModuleManager::isModuleInstalled('bitrix24'))
			return false;

		if (\CBitrix24::IsLicensePaid())
			return false;

		if(\CBitrix24::IsDemoLicense())
			return false;

		if (\CBitrix24::IsNfrLicense())
			return false;

		return (int)Option::get('imopenlines', self::OPTION_TRACKER_LIMIT);
	}

	/**
	 * @deprecated
	 * TODO: delete
	 */
	public static function getTrackerLimitRemainder()
	{
		$limit = self::getTrackerLimit();
		if($limit === false)
			return true;

		$month = (int)date('Ym');
		$previousMonth = (int)Option::get('imopenlines', self::OPTION_LAST_TRACKER_COUNTER_UPDATE);

		if($previousMonth !== $month)
		{
			Option::set('imopenlines', self::OPTION_LAST_TRACKER_COUNTER_UPDATE, $month);
			$counter = 0;
			\CGlobalCounter::Set(self::TRACKER_COUNTER, $counter, \CGlobalCounter::ALL_SITES, '', false);
		}
		else
		{
			$counter = \CGlobalCounter::GetValue(self::TRACKER_COUNTER, \CGlobalCounter::ALL_SITES);
		}

		return $limit - $counter;
	}

	/**
	 * @deprecated
	 * TODO: delete
	 */
	public static function increaseTracker()
	{
		$limit = self::getTrackerLimit();
		if($limit === false)
			return false;

		\CGlobalCounter::Increment(self::TRACKER_COUNTER, \CGlobalCounter::ALL_SITES);

		return true;
	}

	public static function canRemoveCopyright()
	{
		if(!\CModule::IncludeModule('bitrix24'))
			return true;

		if(\CBitrix24::IsDemoLicense())
			return true;

		return \CBitrix24::IsLicensePaid();
	}

	public static function onBitrix24LicenseChange(\Bitrix\Main\Event $event)
	{
		Config::checkLinesLimit();
		QueueManager::checkBusinessUsers();
	}
}