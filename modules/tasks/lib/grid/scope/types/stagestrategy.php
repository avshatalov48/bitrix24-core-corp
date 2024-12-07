<?php

namespace Bitrix\Tasks\Grid\Scope\Types;

use Bitrix\Tasks\Grid\ScopeStrategyInterface;

class StageStrategy implements ScopeStrategyInterface
{
	public function apply(array &$gridHeaders, array $parameters = []): void
	{
		if (empty($parameters['GROUP_ID']))
		{
			unset($gridHeaders['STAGE_ID']);
		}
	}
}