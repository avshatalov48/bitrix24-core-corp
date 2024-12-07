<?php

namespace Bitrix\Crm\Service\Communication\Search;

use Bitrix\Crm\Service\Communication\Search\Ranking\BaseRanking;
use Bitrix\Crm\Service\Communication\Search\Ranking\RankingTypes;
use Bitrix\Crm\Service\Container;

class EntityRanking
{
	protected ?BaseRanking $ranking;

	public function __construct(RankingTypes $rankingType)
	{
		$this->ranking = Container::getInstance()->getCommunicationRankingFactory()->getRankingInstance($rankingType);
	}

	public function rank(int $rankingEntityTypeId, array $searchEntityTypeIds, array $duplicates): array
	{
		if ($this->ranking === null)
		{
			return [];
		}

		$this->ranking->setSearchEntityTypeIds($searchEntityTypeIds);
		$this->ranking->setDuplicates($duplicates);

		return $this->ranking->rank($rankingEntityTypeId);
	}
}
