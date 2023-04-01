<?php

namespace Bitrix\Crm\Restriction;

use Bitrix\Crm\Integration\Bitrix24Manager;
use Bitrix\Main\Application;
use Bitrix\Main\NotSupportedException;
use CCrmDeal;
use CCrmOwnerType;

class ClientFieldsRestriction extends Bitrix24QuantityRestriction
{
	protected int $entityTypeId;

	private const CACHE_DIR = '/crm/entity_count/';
	private const CACHE_TTL = 60*60; // 1 hour
	private const RESTRICTION_SLIDER_CODE = 'limit_crm_filter_deals';
	private const MAX_RESTRICTION_SLIDER_CODE = 'limit_crm_filter_50000_fields';

	public function __construct(int $entityTypeId)
	{
		$this->entityTypeId = $entityTypeId;

		// crm_client_fields_deal_limit
		$restrictionName = 'crm_client_fields_' . mb_strtolower(CCrmOwnerType::ResolveName($entityTypeId)) . '_limit';
		$limit = max(0, (int)Bitrix24Manager::getVariable($restrictionName));
		$maxLimit = Bitrix24Manager::getMaxVariable($restrictionName);
		$restrictionSliderInfo = [
			'ID' => $limit === $maxLimit ? static::MAX_RESTRICTION_SLIDER_CODE : static::RESTRICTION_SLIDER_CODE
		];

		parent::__construct($restrictionName, $limit, null, $restrictionSliderInfo);

		$this->load(); // load actual $limit from options
	}

	public function isExceeded(): bool
	{
		$limit = $this->getQuantityLimit();
		if ($limit <= 0)
		{
			return false;
		}
		$count = $this->getCount($this->entityTypeId);

		return ($count > $limit);
	}

	public function getCount(int $entityTypeId): int
	{
		$cache = Application::getInstance()->getCache();
		$cacheId = 'crm_client_fields_restriction_count_' . $entityTypeId;

		if ($cache->initCache(self::CACHE_TTL, $cacheId, self::CACHE_DIR))
		{
			return (int)$cache->getVars()['count'];
		}

		if ($entityTypeId === CCrmOwnerType::Deal)
		{
			$cache->startDataCache();
			$count = CCrmDeal::GetTotalCount();
			$cache->endDataCache(['count' => $count]);

			return $count;
		}

		throw new NotSupportedException('Entity type ' . $entityTypeId . ' is not supported');
	}
}
