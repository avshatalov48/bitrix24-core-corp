<?php

namespace Bitrix\Crm\Restriction;

use Bitrix\Main;
use Bitrix\Crm\Integration\Bitrix24Manager;

class ClientFieldsRestriction extends Bitrix24QuantityRestriction
{
	protected $entityTypeId;
	private const CACHE_DIR = '/crm/entity_count/';
	private const CACHE_TTL = 60*60; // 1 hour

	public function __construct(int $entityTypeId)
	{
		$this->entityTypeId = $entityTypeId;
		// crm_client_fields_deal_limit
		$restrictionName = 'crm_client_fields_' . mb_strtolower(\CCrmOwnerType::ResolveName($entityTypeId)) . '_limit';
		$limit = max(0, (int)Bitrix24Manager::getVariable($restrictionName));
		$restrictionSliderInfo = [
			'ID' => 'limit_crm_filter_50000_fields',
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
		$cache = Main\Application::getInstance()->getCache();
		$cacheId = 'crm_client_fields_restriction_count_' . $entityTypeId;
		if ($cache->initCache(self::CACHE_TTL, $cacheId, self::CACHE_DIR))
		{
			return (int)$cache->getVars()['count'];
		}
		if ($entityTypeId === \CCrmOwnerType::Deal)
		{
			$cache->startDataCache();
			$count = \CCrmDeal::GetTotalCount();
			$cache->endDataCache(['count' => $count]);

			return $count;
		}

		throw new Main\NotSupportedException('Entity type ' . $entityTypeId . ' is not supported');
	}
}
