<?php

namespace Bitrix\Tasks\Grid\Scope;

use Bitrix\Tasks\Grid\Scope\Types\SpaceStrategy;
use Bitrix\Tasks\Grid\ScopeStrategyInterface;

class ScopeStrategyFactory
{
	public static function getStrategy(string $context): ?ScopeStrategyInterface
	{
		return match (mb_strtolower($context))
		{
			Scope::SPACES => new SpaceStrategy(),
			default => null,
		};
	}
}
