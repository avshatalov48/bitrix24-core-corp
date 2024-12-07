<?php

namespace Bitrix\Tasks\Grid\Scope\Types;

use Bitrix\Tasks\Grid\ScopeStrategyInterface;

class ProjectFlowStrategy implements ScopeStrategyInterface
{
	public function apply(array &$gridHeaders, array $parameters = []): void
	{
		$gridHeaders['FLOW']['default'] = true;
	}
}