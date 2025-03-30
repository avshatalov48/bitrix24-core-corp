<?php

namespace Bitrix\Crm\Settings;

use Bitrix\Crm\Counter\EntityCountableActivityTable;
use Bitrix\Crm\Counter\EntityCounter;
use Bitrix\Crm\Integration\Bitrix24Manager;
use Bitrix\Crm\Traits;
use Bitrix\Main\Application;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\ORM\Entity;
use Bitrix\Main\ORM\Query\Query;

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

	/**
	 * Enabling this option will change the counter count. The value will be based on the person responsible
	 * for the `Activity`, and not on the person responsible for the entity to which the `Activity` is attached.
	 */
	private BooleanSetting $useActivityResponsible;

	private function __construct()
	{
		$defaultValue = !$this->isLimitReached();

		$this->isEnabled = new BooleanSetting('is_counters_enabled', $defaultValue);
		$this->canBeCounted = new BooleanSetting('is_counters_enabled_ex', true);
		$this->useActivityResponsible = new BooleanSetting('is_counters_use_activity_responsible', false);
	}

	public function isEnabled(): bool
	{
		return $this->isEnabled->get();
	}

	public function canBeCounted(): bool
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

	public function isCounterCurrentValueExceeded(int $limit): int
	{
		$query = EntityCountableActivityTable::query()
			->setSelect(['ID'])
			->setLimit($limit + 1)
		;

		$newQuery = (new Query(Entity::getInstanceByQuery($query)));
		$newQuery->registerRuntimeField('', new ExpressionField('QTY', 'COUNT(%s)', 'ID'));
		$newQuery->addSelect('QTY');
		$count = $newQuery->fetch()['QTY'];

		return $count > $limit;
	}

	public function useActivityResponsible(): bool
	{
		return $this->useActivityResponsible->get();
	}

	public function setUseActivityResponsible(bool $val): void
	{
		$currentValue = $this->useActivityResponsible();

		if ($currentValue !== $val)
		{
			EntityCounter::resetAllCrmCountersForAllUsers();
			$this->useActivityResponsible->set($val);
		}
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
