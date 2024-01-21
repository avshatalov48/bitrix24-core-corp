<?php

namespace Bitrix\Tasks\Grid\Scope;

use Bitrix\Tasks\Grid;
use Bitrix\Tasks\Grid\Scope\Types\SpacesContext;
use Bitrix\Tasks\Grid\ScopeInterface;

class ScopeFactory
{
	public static function getScope(string $context, Grid $grid): ?ScopeInterface
	{
		return match (mb_strtolower($context))
		{
			Scope::SPACES => new SpacesContext($grid),
			default => null,
		};
	}
}
