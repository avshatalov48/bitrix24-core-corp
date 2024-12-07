<?php

namespace Bitrix\Crm\Service\Communication\Search;

use Bitrix\Crm\CategoryIdentifier;
use Bitrix\Crm\Service\Communication\Route\EntityReuseMode;
use Bitrix\Crm\Service\Communication\Search\Ranking\RankingTypes;

final class TouchedEntityConfig
{
	public function __construct(
		private readonly CategoryIdentifier $categoryIdentifier,
		private readonly RankingTypes $searchStrategy,
		private readonly ?EntityReuseMode $entityReuseMode,
	)
	{

	}

	public function getEntityTypeId(): int
	{
		return $this->categoryIdentifier->getEntityTypeId();
	}

	public function getCategoryId(): ?int
	{
		return $this->categoryIdentifier->getCategoryId();
	}

	public function getSearchStrategy(): RankingTypes
	{
		return $this->searchStrategy;
	}

	public function getEntityReuseMode(): ?EntityReuseMode
	{
		return $this->entityReuseMode;
	}

	public function isAlwaysCreateNewEntity(): bool
	{
		return ($this->entityReuseMode === EntityReuseMode::new);
	}
}
