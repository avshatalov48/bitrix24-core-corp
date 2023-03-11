<?php

namespace Bitrix\Crm\Restriction;

use Bitrix\Crm\Integration\Bitrix24Manager;
use Bitrix\Main\NotSupportedException;
use CCrmOwnerType;
use CCrmDeal;

class ObserversFieldRestriction extends Bitrix24QuantityRestriction
{
	protected int $entityTypeId;
	protected bool $isFeatureEnabled;

	public function __construct(int $entityTypeId)
	{
		$this->entityTypeId = $entityTypeId;
		$this->isFeatureEnabled = false;

		$limit = 0;

		$restrictionSliderInfo = [
			'ID' => 'limit_crm_search_deals_by_observers',
		];

		// crm_search_by_observers_in_deal
		$featureName = sprintf(
			'crm_search_by_observers_in_%s',
			mb_strtolower(CCrmOwnerType::ResolveName($entityTypeId))
		);

		if (Bitrix24Manager::isFeatureEnabled($featureName))
		{
			$this->isFeatureEnabled = true;

			// crm_search_by_observers_in_deal_limit
			$restrictionName = sprintf(
				'crm_search_by_observers_in_%s_limit',
				mb_strtolower(CCrmOwnerType::ResolveName($entityTypeId))
			);

			$limit = max(0, (int)Bitrix24Manager::getVariable($restrictionName));
			$restrictionSliderInfo = [
				'ID' => 'limit_crm_50000_deals_by_observers',
			];
		}

		parent::__construct($featureName, $limit, null, $restrictionSliderInfo);

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

		$count = $this->getCount($this->entityTypeId);

		return ($count > $limit);
	}

	public function getCount(int $entityTypeId): int
	{
		if ($entityTypeId === CCrmOwnerType::Deal)
		{
			return CCrmDeal::GetTotalCount();
		}

		throw new NotSupportedException(sprintf('Entity type %d is not supported', $entityTypeId));
	}
}
