<?php

namespace Bitrix\Crm\Controller\Requisite;

use Bitrix\Crm\Controller\Base;
use Bitrix\Crm\Integration\ClientResolver;

class Autocomplete extends Base
{
	public static function checkDefaultAppHandlerAction(int $countryId): bool
	{
		$detailSearchHandlersByCountry = ClientResolver::getDetailSearchHandlersByCountry();
		if (isset($detailSearchHandlersByCountry[$countryId]))
		{
			return true;
		}

		return false;
	}
}
