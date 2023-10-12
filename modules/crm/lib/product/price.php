<?php

namespace Bitrix\Crm\Product;

use Bitrix\Main\Loader;
use Bitrix\Catalog;

class Price
{
	/**
	 * Returns base price id.
	 *
	 * @return int|null
	 */
	public static function getBaseId(): ?int
	{
		if (!Loader::includeModule('catalog'))
		{
			return null;
		}

		return Catalog\GroupTable::getBasePriceTypeId();
	}
}
