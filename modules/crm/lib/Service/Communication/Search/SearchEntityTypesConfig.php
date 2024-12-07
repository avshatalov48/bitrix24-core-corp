<?php

namespace Bitrix\Crm\Service\Communication\Search;

use Bitrix\Crm\Service\Communication\Utils\Common;

final class SearchEntityTypesConfig
{
	private bool $useCalculatedSearchEntityTypeIds = false;

	/**
	 * @param int[] $searchEntityTypeIds
	 * @param int[] $touchedEntityIds
	 */
	public function __construct(private array $searchEntityTypeIds, array $touchedEntityIds)
	{
		$hasUndefinedEntityTypeId = in_array(\CCrmOwnerType::Undefined, $searchEntityTypeIds);
		if ($hasUndefinedEntityTypeId || empty($searchEntityTypeIds))
		{
			$this->useCalculatedSearchEntityTypeIds = true;

			$this->searchEntityTypeIds = array_filter(
				$touchedEntityIds,
				static fn ($entityTypeId) => Common::isClientEntityTypeId($entityTypeId),
			);
		}

		$hasLeadEntityTypeId = in_array(\CCrmOwnerType::Lead, $searchEntityTypeIds);
		if ($hasLeadEntityTypeId)
		{
			$this->searchEntityTypeIds = [\CCrmOwnerType::Lead];
		}
	}

	public function isUseCalculatedSearchEntityTypeIds(): bool
	{
		return $this->useCalculatedSearchEntityTypeIds;
	}

	public function getSearchEntityTypeIds(): array
	{
		return $this->searchEntityTypeIds;
	}

	public function hasClientsEntityTypes(): bool
	{
		return !empty(array_intersect($this->searchEntityTypeIds, Common::getClientEntityTypeIds()));
	}
}
