<?php

namespace Bitrix\Crm\Integration\Report;

use Bitrix\Bitrix24\Feature;
use Bitrix\Main\Loader;
use CCrmContact;
use CCrmCompany;
use CCrmDeal;
use CCrmLead;
use CCrmOwnerType;

class Limit
{
	private const LIMITATION_MAP = [
		CCrmOwnerType::LeadName => [
			'class' => 'CCrmLead',
			'var' => 'crm_analytics_lead_max_count'
		],
		CCrmOwnerType::DealName => [
			'class' => 'CCrmContact',
			'var' => 'crm_analytics_deal_max_count'
		],
		CCrmOwnerType::ContactName => [
			'class' => 'CCrmLead',
			'var' => 'crm_analytics_contact_max_count'
		],
		CCrmOwnerType::CompanyName => [
			'class' => 'CCrmCompany',
			'var' => 'crm_analytics_company_max_count'
		],
	];

	private static array $boardLimits = [];

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
		foreach (self::LIMITATION_MAP as $index => $row)
		{
			if (!class_exists($row['class']) || !is_callable("{$row['class']}::GetTotalCount"))
			{
				continue;
			}

			$entityType = mb_strtolower($index);
			$maxCount = is_array($boardLimits) && isset($boardLimits[$board])
				? $boardLimits[$board][$entityType]
				: Feature::getVariable($row['var']) ?? 0;
			$actualCount = call_user_func("{$row['class']}::GetTotalCount");
			if ($actualCount > $maxCount)
			{
				$result[$entityType] = [
					'actualCount' => $actualCount,
					'maxCount' => $maxCount
				];
			}
		}

		static::$boardLimits[$board] = $result;

		return $result;
	}

	public static function isAnalyticsLimited($board): bool
	{
		return !empty(self::getLimitationParams($board));
	}
}
