<?php

namespace Bitrix\CrmMobile\Controller;

use Bitrix\Intranet\ActionFilter\IntranetUser;
use Bitrix\Main\Loader;
use Bitrix\Main\Engine\Controller;

abstract class Base extends Controller
{
	protected function getDefaultPreFilters(): array
	{
		$defaultPreFilters = parent::getDefaultPreFilters();

		if (Loader::includeModule('intranet'))
		{
			$defaultPreFilters[] = new IntranetUser();
		}

		return $defaultPreFilters;
	}
}
