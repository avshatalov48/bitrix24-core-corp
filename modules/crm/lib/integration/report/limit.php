<?php

namespace Bitrix\Crm\Integration\Report;

use Bitrix\Bitrix24\Feature;
use Bitrix\Main\Loader;
use CCrmOwnerType;

class Limit
{
	private const LIMITATION_MAP = [
		CCrmOwnerType::LeadName => [
			'class' => 'CCrmLead',
			'var' => 'crm_analytics_lead_max_count'
		],
		CCrmOwnerType::DealName => [
			'class' => 'CCrmDeal',
			'var' => 'crm_analytics_deal_max_count'
		],
		CCrmOwnerType::ContactName => [
			'class' => 'CCrmContact',
			'var' => 'crm_analytics_contact_max_count'
		],
		CCrmOwnerType::CompanyName => [
			'class' => 'CCrmCompany',
			'var' => 'crm_analytics_company_max_count'
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

		$entityCounts = static::getEntityCounts(false);
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

	public static function getEntityCounts(bool $staticCache = true): array
	{
		if (!$staticCache || static::$entityCounts === null)
		{
			foreach (self::LIMITATION_MAP as $entityTypeId => $info)
			{
				$entityTypeIdLower = mb_strtolower($entityTypeId);
				$maxCount = Feature::getVariable($info['var']) ?? 0;
				$actualCount =
					(class_exists($info['class']) && is_callable("{$info['class']}::GetTotalCount"))
						? call_user_func("{$info['class']}::GetTotalCount")
						: 0
				;
				static::$entityCounts[$entityTypeIdLower] = [
					'actualCount' => $actualCount,
					'maxCount' => $maxCount
				];
			}
		}

		return static::$entityCounts;
	}
}
