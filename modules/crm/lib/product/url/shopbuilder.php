<?php
namespace Bitrix\Crm\Product\Url;

use Bitrix\Main\Loader;
use Bitrix\Catalog;

if (Loader::includeModule('catalog'))
{
	/**
	 * @deprecated
	 * @see Catalog\Url\ShopBuilder
	 */
	class ShopBuilder extends Catalog\Url\ShopBuilder
	{

	}
}
