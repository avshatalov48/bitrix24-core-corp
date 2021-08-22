<?php

namespace Bitrix\Crm\Product;

use Bitrix\Main\Loader;

if (Loader::includeModule('catalog'))
{
	class Price
	{
		/**
		 * Returns base price id.
		 *
		 * @return int|null
		 */
		public static function getBaseId(): ?int
		{
			$id = 0;
			$basePrice = \CCatalogGroup::GetBaseGroup();
			if (!empty($basePrice['ID']))
			{
				$id = (int)$basePrice['ID'];
			}

			return ($id > 0 ? $id : null);
		}
	}
}
