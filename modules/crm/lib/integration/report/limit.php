<?php

namespace Bitrix\Crm\Integration\Report;

use Bitrix\Bitrix24\Feature;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Loader;
use CCrmOwnerType;

class Limit
{
	private const CACHE_TTL = 86400;

	private const LIMITATION_MAP = [
		CCrmOwnerType::LeadName => [
			'class' => 'CCrmLead',
			'var' => 'crm_analytics_lead_max_count',
			'entityTypeId' => \CCrmOwnerType::Lead,
		],
		CCrmOwnerType::DealName => [
			'class' => 'CCrmDeal',
			'var' => 'crm_analytics_deal_max_count',
			'entityTypeId' => \CCrmOwnerType::Deal,
		],
		CCrmOwnerType::ContactName => [
			'class' => 'CCrmContact',
			'var' => 'crm_analytics_contact_max_count',
			'entityTypeId' => \CCrmOwnerType::Contact,
		],
		CCrmOwnerType::CompanyName => [
			'class' => 'CCrmCompany',
			'var' => 'crm_analytics_company_max_count',
			'entityTypeId' => \CCrmOwnerType::Company,
		],
	];

	private static array $boardLimits = [];

	/** @var array|null $entityCounts */
	private static $entityCounts = null;

	public static function getLimitationParams($board): array
	{
		if (!Loader::includeModule('bitrix24'))
		{
			return [];
		}

		if (isset(static::$boardLimits[$board]))
		{
			return static::$boardLimits[$board];
		}

		$boardLimits = Feature::getVariable('crm_analytics_limits_for_boards');
		$result = [];

		$entityCounts = static::getEntityCounts();
		foreach ($entityCounts as $entityTypeIdLower => &$info)
		{
			if ((is_array($boardLimits) && isset($boardLimits[$board])))
			{
				$info['maxCount'] = $boardLimits[$board][$entityTypeIdLower];
			}

			if ($info['actualCount'] > $info['maxCount'])
			{
				$result[$entityTypeIdLower] = $info;
			}
		}

		static::$boardLimits[$board] = $result;

		return $result;
	}

	public static function isAnalyticsLimited($board): bool
	{
		return !empty(self::getLimitationParams($board));
	}

	public static function getEntityCounts(bool $useCache = true): array
	{
		if ($useCache && static::$entityCounts === null)
		{
			$cache = Cache::createInstance();
			if ($cache->initCache(
				self::CACHE_TTL,
				'crm.integration.report.limit',
				'/crm/report/analytics/'
			))
			{
				static::$entityCounts = $cache->getVars();

				return static::$entityCounts;
			}
		}

		if (static::$entityCounts === null)
		{
			foreach (self::LIMITATION_MAP as $entityTypeId => $info)
			{
				$entityTypeIdLower = mb_strtolower($entityTypeId);
				$maxCount = Feature::getVariable($info['var']) ?? 0;

				$factory = Container::getInstance()->getFactory($info['entityTypeId']);
				if ($factory)
				{
					$isTotalItemsCountExceeded = $factory->checkIfTotalItemsCountExceeded($maxCount);
					$actualCount = $isTotalItemsCountExceeded ? $maxCount + 1 : self::getExactTotalCount($info['class']);
				}
				else
				{
					$actualCount = self::getExactTotalCount($info['class']);
				}

				static::$entityCounts[$entityTypeIdLower] = [
					'actualCount' => $actualCount,
					'maxCount' => $maxCount
				];
			}

			if ($useCache)
			{
				$cache->startDataCache();
				$cache->endDataCache(static::$entityCounts);
			}
		}

		return static::$entityCounts;
	}

	private static function getExactTotalCount(string $className): int
	{
		return (class_exists($className) && is_callable("{$className}::GetTotalCount"))
			? call_user_func("{$className}::GetTotalCount")
			: 0
		;
	}
}
