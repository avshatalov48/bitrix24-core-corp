<?php

namespace Bitrix\Tasks\Grid;

interface ScopeStrategyInterface
{
	public function apply(array &$gridHeaders, array $parameters = []): void;
}
