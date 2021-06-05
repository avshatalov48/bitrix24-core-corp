<?php

namespace Bitrix\Imopenlines;

use Bitrix\Main\Config\Option;
use Bitrix\Main\ModuleManager;
use Bitrix\Bitrix24\Feature;

class Limit
{
	const OPTION_TRACKER_LIMIT = 'tracker_limit';
	const OPTION_LAST_TRACKER_COUNTER_UPDATE = 'tracker_month';
	const TRACKER_COUNTER = 'tracker_count';
	const OPTION_REPORT = 'report_open_lines';

	const INFO_HELPER_LIMIT_CONTACT_CENTER_OL_NUMBER = 'limit_contact_center_ol_number';
	const INFO_HELPER_LIMIT_CONTACT_CENTER_CUSTOMER_RATE = 'limit_contact_center_ol_customer_rate';
	const INFO_HELPER_LIMIT_CONTACT_CENTER_MESSAGE_TO_ALL = 'limit_contact_center_ol_message_to_all';
	const INFO_HELPER_LIMIT_CONTACT_CENTER_ACCESS_PERMISSIONS = 'limit_contact_center_ol_access_permissions';
	const INFO_HELPER_LIMIT_CONTACT_CENTER_BOSS_RATE = 'limit_contact_center_ol_boss_rate';
	const INFO_HELPER_LIMIT_CONTACT_CENTER_ANALYTICS_REPORTS = 'limit_contact_center_ol_analytics_reports';
	const INFO_HELPER_LIMIT_CONTACT_CENTER_WORKHOUR_SETTING = 'limit_contact_center_ol_workhour_settings';
	const INFO_HELPER_LIMIT_CONTACT_CENTER_STATISTICS_EXCEL = 'limit_contact_center_ol_statistics_excel';
	const INFO_HELPER_LIMIT_CONTACT_CENTER_OL_CHAT_TRANSFER = 'limit_contact_center_ol_chat_transfer';

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
	public static function canJoinChatUser(): bool
	{
		$result = true;
		if (\CModule::IncludeModule('bitrix24'))
		{
			$result = Feature::isFeatureEnabled('imopenlines_can_join_chat_user');
		}

		return $result;
	}

	/**
	 * @return bool
	 */
	public static function canTransferToLine(): bool
	{
		$result = true;
		if (\CModule::IncludeModule('bitrix24'))
		{
			$result = Feature::isFeatureEnabled('imopenlines_can_transfer_to_line');
		}

		return $result;
	}

	/**
	 * @return bool
	 */
	public static function canWorkHourSettings(): bool
	{
		$result = true;
		if (\CModule::IncludeModule('bitrix24'))
		{
			$result = Feature::isFeatureEnabled('imopenlines_can_workhour_settings');
		}

		return $result;
	}


	/**
	 * @return bool
	 */
	public static function canStatisticsExcel(): bool
	{
		$result = true;
		if (\CModule::IncludeModule('bitrix24'))
		{
			$result = Feature::isFeatureEnabled('imopenlines_can_statistics_excel');
		}

		return $result;
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
	 * @return bool
	 */
	public static function canUseReport()
	{
		$result = true;

		if(\CModule::IncludeModule('bitrix24'))
		{
			$result = Feature::isFeatureEnabled(self::OPTION_REPORT);
		}

		return $result;
	}

	/**
	 * @param \Bitrix\Main\Event $event
	 */
	public static function onBitrix24LicenseChange(\Bitrix\Main\Event $event)
	{
		Config::checkLinesLimit();
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
	 *
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

	/**
	 * @deprecated
	 *
	 * @return array|bool
	 */
	public static function getLicenseUsersLimit()
	{
		return false;
	}
}