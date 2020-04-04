<?php

namespace Bitrix\Imopenlines;

use Bitrix\Main\Config\Option;
use Bitrix\Main\ModuleManager;
use Bitrix\Bitrix24\Feature;

class Limit
{
	const OPTION_TRACKER_LIMIT = "tracker_limit";
	const OPTION_LAST_TRACKER_COUNTER_UPDATE = "tracker_month";
	const TRACKER_COUNTER = "tracker_count";

	/**
	 * @return bool
	 */
	public static function isDemoLicense()
	{
		if (!\CModule::IncludeModule('bitrix24'))
			return false;

		return \CBitrix24::getLicenseType() == 'demo';
	}

	/**
	 * @return int
	 */
	public static function getLinesLimit()
	{
		if (!\CModule::IncludeModule('bitrix24'))
			return 0;

		return (int)Feature::getVariable('imopenlines_max_lines_limit');
	}

	/**
	 * @return array|bool
	 */
	public static function getLicenseUsersLimit()
	{
		if (!\CModule::IncludeModule('bitrix24'))
			return false;

		if (\CBitrix24BusinessTools::isLicenseUnlimited())
			return false;

		return \CBitrix24BusinessTools::getUnlimUsers();
	}

	/**
	 * @return bool|mixed
	 */
	public static function canUseQueueAll()
	{
		if (!\CModule::IncludeModule('bitrix24'))
			return true;

		return Feature::getVariable('imopenlines_can_use_queue_all');
	}

	/**
	 * @return bool
	 */
	public static function canUseVoteClient()
	{
		if (!\CModule::IncludeModule('bitrix24'))
			return true;

		return Feature::isFeatureEnabled("imopenlines_can_use_vote_client");
	}

	/**
	 * @return bool
	 */
	public static function canUseVoteHead()
	{
		if (!\CModule::IncludeModule('bitrix24'))
			return true;

		return Feature::isFeatureEnabled("imopenlines_can_use_vote_head");
	}

	/**
	 * @return bool
	 */
	public static function canRemoveCopyright()
	{
		if(!\CModule::IncludeModule('bitrix24'))
			return true;

		return \CBitrix24::IsLicensePaid();
	}

	/**
	 * @param \Bitrix\Main\Event $event
	 */
	public static function onBitrix24LicenseChange(\Bitrix\Main\Event $event)
	{
		Config::checkLinesLimit();
		QueueManager::checkBusinessUsers();
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
}