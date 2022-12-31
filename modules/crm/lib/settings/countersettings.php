<?php

namespace Bitrix\Crm\Settings;

use Bitrix\Crm\Counter\EntityCountableActivityTable;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;

class CounterSettings
{
	private const LIMIT_CACHE_TTL = 86400;
	private const LIMIT_CACHE_ID = 'counter_limit';
	private const LIMIT_CACHE_PATH = '/crm/counter_limit/';

	private $isEnabled = null;

	private static $current = null;

	public static function getCurrent()
	{
		if (self::$current === null)
		{
			self::$current = new self();
		}

		return self::$current;
	}

	function __construct()
	{
		$defaultValue = !$this->isLimitReached();
		$this->isEnabled = new BooleanSetting('is_counters_enabled', $defaultValue);
	}

	public function isEnabled(): bool
	{
		return (bool)$this->isEnabled->get();
	}

	private function isLimitReached(): bool
	{
		$cache = Application::getInstance()->getCache();
		if($cache->initCache(self::LIMIT_CACHE_TTL, self::LIMIT_CACHE_ID, self::LIMIT_CACHE_PATH))
		{
			$result = $cache->getVars();
		}
		else
		{
			$result = [
				'TOTAL' => EntityCountableActivityTable::getCount()
			];

			$cache->startDataCache();
			$cache->endDataCache($result);
		}

		$maxActivitiesCount = Option::get('crm', 'max_countable_activities', 100000);

		return $result['TOTAL'] > $maxActivitiesCount;
	}
}
