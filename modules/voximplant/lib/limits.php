<?php

namespace Bitrix\Voximplant;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Bitrix24;
use Bitrix\Main\Type\Date;
use Bitrix\Voximplant\Model\QueueTable;

class Limits
{
	const OPTION_INFOCALLS_LIMIT = "infocalls_limit";
	const OPTION_LAST_INFOCALLS_COUNTER_UPDATE = "infocalls_month";
	const INFOCALLS_COUNTER = "vi_infocalls";

	const OPTION_IGNORE_MAXIMUM_NUMBERS = "ignore_maximum_numbers";
	const OPTION_IGNORE_MAXIMUM_GROUPS = "ignore_maximum_groups";
	const OPTION_IGNORE_MAXIMUM_GROUP_MEMBERS = "ignore_maximum_group_members";

	public static function getRecordLimit($mode = false)
	{
		$sipConnectorActive = \CVoxImplantConfig::GetModeStatus(\CVoxImplantConfig::MODE_SIP);
		if ($mode == \CVoxImplantConfig::MODE_SIP && $sipConnectorActive)
		{
			return 0;
		}

		if (Loader::includeModule('bitrix24'))
		{
			return Bitrix24\Feature::getVariable('voximplant_records_limit');
		}
		else
		{
			return 0;
		}
	}

	public static function getRemainingRecordsCount()
	{
		$recordLimit = static::getRecordLimit();
		if(!$recordLimit)
		{
			return 0;
		}

		$recordMonth = Option::get("voximplant", "record_month");
		$currentMonth = date('Ym');

		if (!$recordMonth)
		{
			Option::set("voximplant", "record_month", $currentMonth);
			\CGlobalCounter::Set('vi_records', 0, \CGlobalCounter::ALL_SITES, '', false);
			return $recordLimit;
		}

		$recordCount = \CGlobalCounter::GetValue('vi_records', \CGlobalCounter::ALL_SITES);
		if($recordCount < $recordLimit)
		{
			return $recordLimit - $recordCount;
		}

		if($recordMonth < $currentMonth)
		{
			Option::set("voximplant", "record_month", $currentMonth);
			\CGlobalCounter::Set('vi_records', 0, \CGlobalCounter::ALL_SITES, '', false);
			return $recordLimit;
		}
		return 0;
	}

	public static function registerRecord()
	{
		\CGlobalCounter::Increment('vi_records', \CGlobalCounter::ALL_SITES, false);
	}

	/**
	 * @param string $lineMode Line mode (\CVoxImplantConfig::MODE_SIP or \CVoxImplantConfig::MODE_RENT)
	 * @return int|false
	 */
	public static function getInfocallsLimit($lineMode = '')
	{
		if(!ModuleManager::isModuleInstalled('bitrix24'))
			return false;

		if(\CVoxImplantAccount::IsPro())
		{
			return false;
		}

		if($lineMode == \CVoxImplantConfig::MODE_SIP && \CVoxImplantSip::isActive())
		{
			return false;
		}

		return (int)Option::get('voximplant', self::OPTION_INFOCALLS_LIMIT);
	}

	/**
	 * @param string $lineMode Line mode (\CVoxImplantConfig::MODE_SIP or \CVoxImplantConfig::MODE_RENT)
	 * @return int|false
	 */
	public static function getInfocallsLimitRemainder($lineMode = '')
	{
		$limit = self::getInfocallsLimit($lineMode);
		if($limit === false)
			return false;

		$month = (int)date('Ym');
		$previousMonth = (int)Option::get('voximplant', self::OPTION_LAST_INFOCALLS_COUNTER_UPDATE);

		if($previousMonth !== $month)
		{
			Option::set('voximplant', self::OPTION_LAST_INFOCALLS_COUNTER_UPDATE, $month);
			$counter = 0;
			\CGlobalCounter::Set(self::INFOCALLS_COUNTER, $counter, \CGlobalCounter::ALL_SITES, '',false);
		}
		else
		{
			$counter = \CGlobalCounter::GetValue(self::INFOCALLS_COUNTER, \CGlobalCounter::ALL_SITES);
		}

		$result = $limit - $counter;
		return ($result > 0 ? $result : 0);
	}

	/**
	 * @param string $lineMode Line mode (\CVoxImplantConfig::MODE_SIP or \CVoxImplantConfig::MODE_RENT)
	 * @return bool
	 */
	public static function addInfocall($lineMode = '')
	{
		$limit = self::getInfocallsLimit($lineMode);
		if($limit === false)
			return false;

		\CGlobalCounter::Increment(self::INFOCALLS_COUNTER, \CGlobalCounter::ALL_SITES);
		return true;
	}

	/**
	 * Returns maximum IVR depth according to the portal's tariff
	 * @return int|string
	 */
	public static function getIvrDepth()
	{
		if (Loader::includeModule('bitrix24'))
		{
			return Bitrix24\Feature::getVariable('voximplant_ivr_depth');
		}
		else
		{
			return 0;
		}
	}

	/**
	 * Returns maximum count of numbers, allowed to rent.
	 * @return int
	 */
	public static function getMaximumNumbers()
	{
		if (!Loader::includeModule('bitrix24'))
			return 0;

		if(Option::get('voximplant', static::OPTION_IGNORE_MAXIMUM_NUMBERS) === "Y")
			return 0;

		$licensePrefix = \CBitrix24::getLicensePrefix();
		if(in_array($licensePrefix, static::getRussianRegions()))
			return 0;

		$licenseType = \CBitrix24::getLicenseType();
		if($licenseType === 'project') //project
			return 1;
		elseif ($licenseType === 'tf') //project+
			return 3;
		elseif ($licenseType === 'reail') //retail+crm
			return 3;

		return 0;
	}

	/**
	 * Returns maximum allowed number of groups.
	 * @return int
	 */
	public static function getMaximumGroups()
	{
		if (!Loader::includeModule('bitrix24'))
		{
			return -1;
		}

		if(Option::get('voximplant', static::OPTION_IGNORE_MAXIMUM_GROUPS) === "Y")
		{
			return -1;
		}

		return (int)Bitrix24\Feature::getVariable('voximplant_groups_limit');
	}

	/**
	 * Returns maximum allowed number of users in the group.
	 * @return int
	 */
	public static function getMaximumGroupMembers()
	{
		if (!Loader::includeModule('bitrix24'))
			return -1;

		if(Option::get('voximplant', static::OPTION_IGNORE_MAXIMUM_GROUP_MEMBERS) === "Y")
			return -1;

		return (int)Bitrix24\Feature::getVariable('voximplant_max_group_members');
	}

	/**
	 * Returns true if telephony is limited to rest-only mode in current region
	 */
	public static function isRestOnly()
	{
		return \Bitrix\Voximplant\Integration\Bitrix24::getLicensePrefix() === 'by';
	}

	public static function hasAccountBalance(): bool
	{
		static $regions = ['kz', 'by'];
		return !in_array(\Bitrix\Main\Application::getInstance()->getLicense()->getRegion(), $regions, true);
	}

	/**
	 * Returns array or russian license prefixes.
	 * @return array
	 */
	protected static function getRussianRegions()
	{
		return array('ru', 'ua', 'kz', 'by');
	}

	/**
	 * Returns true if current portal is able to rent one more number.
	 * @return bool
	 */
	public static function canRentNumber()
	{
		$account = new \CVoxImplantAccount();
		if ($account->GetAccountLang() === 'ua')
		{
			return false;
		}

		$maximumNumbers = self::getMaximumNumbers();
		if ($maximumNumbers == 0)
			return true;

		$currentCount = \CVoxImplantPhone::GetRentedNumbersCount();
		return $currentCount < $maximumNumbers;
	}

	/**
	 * Returns true if current portal is able to rent packet of numbers.
	 * @return bool
	 */
	public static function canRentMultiple()
	{
		$account = new \CVoxImplantAccount();
		return ($account->GetAccountLang() === 'ru');
	}

	/**
	 * Returns true if current portal is able to create one more group.
	 * @return bool
	 */
	public static function canCreateGroup()
	{
		$maxGroups = self::getMaximumGroups();
		if ($maxGroups === -1)
		{
			return true;
		}

		$groupCount = QueueTable::getCount();
		return $groupCount < $maxGroups;
	}

	public static function canSelectLine()
	{
		return \CVoxImplantAccount::IsPro();
	}

	public static function canInterceptCall()
	{
		return (
			!Loader::includeModule('bitrix24')
			|| Bitrix24\Feature::isFeatureEnabled("voximplant_intercept_call")
		);
	}

	public static function canSelectCallSource()
	{
		return (
			!Loader::includeModule('bitrix24')
			|| Bitrix24\Feature::isFeatureEnabled("voximplant_call_source")
		);
	}

	public static function canExportCalls()
	{
		return (
			!Loader::includeModule('bitrix24')
			|| Bitrix24\Feature::isFeatureEnabled("voximplant_export_calls")
		);
	}

	public static function canVote()
	{
		return (
			!Loader::includeModule('bitrix24')
			|| Bitrix24\Feature::isFeatureEnabled("voximplant_vote")
		);
	}

	public static function isQueueAllAllowed()
	{
		return (
			!Loader::includeModule('bitrix24')
			|| Bitrix24\Feature::isFeatureEnabled("voximplant_queue_all")
		);
	}

	public static function isRedirectToQueueAllowed()
	{
		return (
			!Loader::includeModule('bitrix24')
			|| Bitrix24\Feature::isFeatureEnabled("voximplant_redirect_to_queue")
		);
	}

	public static function canManageTelephony()
	{
		if (!Loader::includeModule("bitrix24"))
		{
			return true;
		}

		if (Bitrix24\Feature::isFeatureEnabled("voximplant_manage_telephony"))
		{
			return true;
		}

		return false;
	}

	public static function canCall()
	{
		if (!Loader::includeModule("bitrix24"))
		{
			return true;
		}

		if (Bitrix24\Feature::isFeatureEnabled("voximplant_calls"))
		{
			return true;
		}

		return false;
	}
}