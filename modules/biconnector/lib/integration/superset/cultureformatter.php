<?php

namespace Bitrix\BIConnector\Integration\Superset;

use Bitrix\Main\Loader;

final class CultureFormatter
{
	/**
	 * Returns currency symbol that used in superset entities phrases
	 *
	 * @return string
	 */
	public static function getPortalCurrencySymbol(): string
	{
		if (Loader::includeModule('crm'))
		{
			return html_entity_decode(\CCrmCurrency::GetCurrencyText(\CCrmCurrency::GetBaseCurrencyID()));
		}

		return '';
	}
}