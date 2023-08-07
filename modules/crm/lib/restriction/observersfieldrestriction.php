<?php

namespace Bitrix\Crm\Restriction;

use Bitrix\Crm\Integration\Bitrix24Manager;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Main\NotSupportedException;
use CCrmDeal;
use CCrmLead;
use CCrmOwnerType;

class ObserversFieldRestriction extends Bitrix24QuantityRestriction
{
	/**
	 * We use the feature code for all entities (not just for DEAL)
	 */
	private const FEATURE_NAME = 'crm_search_by_observers_in_deal';

	/**
	 * We use the limit variable code for all entities (not just for DEAL)
	 */
	private const LIMIT_VAR_NAME = 'crm_search_by_observers_in_deal_limit';

	private const FEATURE_SLIDER_CODE = 'limit_crm_search_elements_by_observers';
	private const LIMIT_SLIDER_CODE = 'limit_crm_max_number_search_by_observers';

	protected int $entityTypeId;
	protected bool $isFeatureEnabled;
	protected ?Factory $factory;

	public function __construct(int $entityTypeId)
	{
		$this->entityTypeId = $entityTypeId;
		$this->isFeatureEnabled = false;
		$this->factory = Container::getInstance()->getFactory($entityTypeId);

		$limit = 0;
		$sliderId = static::FEATURE_SLIDER_CODE;

		if (Bitrix24Manager::isFeatureEnabled(static::FEATURE_NAME))
		{
			$this->isFeatureEnabled = true;

			$limit = max(0, (int)Bitrix24Manager::getVariable(static::LIMIT_VAR_NAME));
			$sliderId = static::LIMIT_SLIDER_CODE;
		}

		parent::__construct(static::FEATURE_NAME, $limit, null, ['ID'=> $sliderId]);

		$this->load(); // load actual $limit from options
	}

	public function isExceeded(): bool
	{
		if (!$this->isFeatureEnabled)
		{
			return true;
		}

		$limit = $this->getQuantityLimit();
		if ($limit <= 0)
		{
			return false;
		}
		
		return $this->getCount($this->entityTypeId) > $limit;
	}

	public function getCount(int $entityTypeId): int
	{
		$cacheId = 'crm_observers_field_restriction_count_' . $entityTypeId;

		if ($this->cache->initCache(self::CACHE_TTL, $cacheId, self::CACHE_DIR))
		{
			return (int)$this->cache->getVars()['count'];
		}

		$this->cache->startDataCache();

		if ($entityTypeId === CCrmOwnerType::Deal)
		{
			$count = CCrmDeal::GetTotalCount();
		}
		elseif ($entityTypeId === CCrmOwnerType::Lead)
		{
			$count = CCrmLead::GetTotalCount();
		}
		elseif ($this->factory)
		{
			$count = $this->factory->getItemsCount();
		}
		elseif (CCrmOwnerType::isUseDynamicTypeBasedApproach($entityTypeId))
		{
			return 0;
		}
		else
		{
			$entityTypeName = CCrmOwnerType::ResolveName($entityTypeId);

			throw new NotSupportedException(sprintf('Entity type %d is not supported', $entityTypeName));
		}

		$this->cache->endDataCache(['count' => $count]);

		return $count;
	}
}
