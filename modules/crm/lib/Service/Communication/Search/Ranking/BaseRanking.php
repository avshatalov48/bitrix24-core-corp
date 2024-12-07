<?php

namespace Bitrix\Crm\Service\Communication\Search\Ranking;

abstract class BaseRanking
{
	protected array $duplicates = [];
	protected array $searchEntityTypeIds = [];

	final public function setDuplicates(array $duplicates): static
	{
		$this->duplicates = $duplicates;

		return $this;
	}

	final public function setDuplicatesByEntityType(int $entityTypeId, array $duplicateIds): static
	{
		if (\CCrmOwnerType::IsDefined($entityTypeId))
		{
			$this->duplicates[$entityTypeId] = $duplicateIds;
		}

		return $this;
	}

	final public function setSearchEntityTypeIds(array $searchEntityTypeIds): static
	{
		$this->searchEntityTypeIds = $searchEntityTypeIds;

		return $this;
	}

	abstract public function rank(int $rankedEntityTypeId): array;
}
