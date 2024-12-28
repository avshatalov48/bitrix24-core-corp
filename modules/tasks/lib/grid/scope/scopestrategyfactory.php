<?php

namespace Bitrix\Tasks\Grid\Scope;

use Bitrix\Tasks\Grid\Scope\Types\CollabStrategy;
use Bitrix\Tasks\Grid\Scope\Types\ProjectFlowStrategy;
use Bitrix\Tasks\Grid\Scope\Types\SpaceStrategy;
use Bitrix\Tasks\Grid\Scope\Types\StageStrategy;
use Bitrix\Tasks\Grid\ScopeStrategyInterface;

class ScopeStrategyFactory
{
	/**
	 * @return ScopeStrategyInterface[]
	 */
	public static function getStrategies(array $parameters = []): array
	{
		$strategies = [new StageStrategy()];
		if (mb_strtolower($parameters['SCOPE'] ?? '') === Scope::SPACES)
		{
			$strategies[] = new SpaceStrategy();
		}

		if (mb_strtolower($parameters['SCOPE'] ?? '') === Scope::COLLAB)
		{
			$strategies[] = new CollabStrategy();
		}

		if (($parameters['GROUP_ID'] ?? 0) > 0)
		{
			$strategies[] = new ProjectFlowStrategy();
		}

		return $strategies;
	}
}
