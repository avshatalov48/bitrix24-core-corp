<?php

namespace Bitrix\Crm\Settings;

use Bitrix\Crm\Counter\EntityCountableActivityTable;
use Bitrix\Crm\Integration\Bitrix24Manager;
use Bitrix\Crm\Traits;
use Bitrix\Main\Application;

class CounterSettings
{
	use Traits\Singleton;

	private const MAX_ACTIVITIES_COUNT_VAR_NAME = 'crm_max_activities_count';

	private const LIMIT_CACHE_TTL = 3 * 60 * 60; // 3 hours
	private const LIMIT_CACHE_ID = 'counter_limit';
	private const LIMIT_CACHE_PATH = '/crm/counter_limit/';

	private BooleanSetting $isEnabled;
	private BooleanSetting $canBeCounted;
	private bool $useCache = true;

	private function __construct()
	{
		$defaultValue = !$this->isLimitReached();

		$this->isEnabled = new BooleanSetting('is_counters_enabled', $defaultValue);
		$this->canBeCounted = new BooleanSetting('is_counters_enabled_ex', true);
	}

	final public function isEnabled(): bool
	{
		return $this->isEnabled->get();
	}

	final public function canBeCounted(): bool
	{
		return $this->canBeCounted->get();
	}

	public function cleanCounterLimitCache(): void
	{
		\Bitrix\Main\Application::getInstance()->getCache()->clean(self::LIMIT_CACHE_ID, self::LIMIT_CACHE_PATH);
	}

	public function getCounterLimitValue(): int
	{
		if (!Bitrix24Manager::isEnabled())
		{
			return 0;
		}

		if (!Bitrix24Manager::getVariable(self::MAX_ACTIVITIES_COUNT_VAR_NAME))
		{
			return 0;
		}

		return (int)Bitrix24Manager::getVariable(self::MAX_ACTIVITIES_COUNT_VAR_NAME);
	}

	public function getCounterCurrentValue(): int
	{
		return EntityCountableActivityTable::getCount();
	}

	private function isLimitReached(): bool
	{
		if (!Bitrix24Manager::isEnabled())
		{
			return false;
		}

		if (!Bitrix24Manager::getVariable(self::MAX_ACTIVITIES_COUNT_VAR_NAME))
		{
			return false;
		}

		if ($this->useCache)
		{
			$cache = Application::getInstance()->getCache();
			if ($cache->initCache(self::LIMIT_CACHE_TTL, self::LIMIT_CACHE_ID, self::LIMIT_CACHE_PATH))
			{
				$result = $cache->getVars();
			}
			else
			{
				$result['TOTAL'] =  $this->getCounterCurrentValue();

				$cache->startDataCache();
				$cache->endDataCache($result);
			}
		}
		else
		{
			$result['TOTAL'] =  $this->getCounterCurrentValue();
		}

		$maxActivitiesCount = max(0, (int)$this->getCounterLimitValue());

		return $result['TOTAL'] > $maxActivitiesCount;
	}
}
