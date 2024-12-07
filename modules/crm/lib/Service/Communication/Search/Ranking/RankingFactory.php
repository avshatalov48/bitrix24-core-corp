<?php

namespace Bitrix\Crm\Service\Communication\Search\Ranking;

class RankingFactory
{
	public function getRankingInstance(RankingTypes $rankStrategyId): ?BaseRanking
	{
		if ($rankStrategyId === RankingTypes::newestCreatedEntity)
		{
			return new NewestCreatedEntityRanking();
		}

		if ($rankStrategyId === RankingTypes::newestUpdatedEntity)
		{
			return new NewestUpdatedEntityRanking();
		}

		return null;
	}
}
