<?php

namespace Bitrix\Crm\Controller\RepeatSale;

use Bitrix\Main\Engine\ActionFilter\Scope;

abstract class Base extends \Bitrix\Crm\Controller\Base
{
	protected function getDefaultPreFilters(): array
	{
		$filters = parent::getDefaultPreFilters();
		$filters[] = new Scope(Scope::NOT_REST);

		return $filters;
	}
}
