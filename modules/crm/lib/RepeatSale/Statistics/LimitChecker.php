<?php

namespace Bitrix\Crm\RepeatSale\Statistics;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Data\Cache;

final class LimitChecker
{
	use Singleton;

	private const ENTITY_ITEMS_COUNT_LIMIT_OPTION_NAME = 'repeat_sale_entity_items_count_limit';
	private const ENTITY_ITEMS_COUNT_LIMIT = 300000;
	private const TTL = 86400;

	private static ?bool $isLimitExceeded = null;

	public function isLimitExceeded(): bool
	{
		$limit = $this->getEntityItemsCountLimit();

		if (self::$isLimitExceeded === null)
		{
			$cache = Cache::createInstance();
			if ($cache->initCache(
				self::TTL,
				'crm.repeatsale.statistics.limit',
				'/crm/repeatsale/statistics/limit/',
			))
			{
				self::$isLimitExceeded = $cache->getVars();

				return self::$isLimitExceeded;
			}
		}

		if (self::$isLimitExceeded === null)
		{
			$container = Container::getInstance();
			$isLimitExceeded = false;

			if ($container->getFactory(\CCrmOwnerType::Deal)?->checkIfTotalItemsCountExceeded($limit))
			{
				$isLimitExceeded = true;
			}

			if (!$isLimitExceeded && $container->getFactory(\CCrmOwnerType::Contact)?->checkIfTotalItemsCountExceeded($limit))
			{
				$isLimitExceeded = true;
			}

			if (!$isLimitExceeded && $container->getFactory(\CCrmOwnerType::Company)?->checkIfTotalItemsCountExceeded($limit))
			{
				$isLimitExceeded = true;
			}

			self::$isLimitExceeded = $isLimitExceeded;

			$cache->startDataCache();
			$cache->endDataCache(self::$isLimitExceeded);
		}

		return self::$isLimitExceeded;
	}

	private function getEntityItemsCountLimit(): int
	{
		return (int)Option::get('crm', self::ENTITY_ITEMS_COUNT_LIMIT_OPTION_NAME, self::ENTITY_ITEMS_COUNT_LIMIT);
	}
}
