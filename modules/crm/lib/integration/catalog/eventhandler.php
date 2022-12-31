<?php

namespace Bitrix\Crm\Integration\Catalog;

use Bitrix\Crm\Integration\Catalog\Contractor\Provider;

/**
 * Class EventHandler
 *
 * @package Bitrix\Crm\Integration\Catalog
 */
class EventHandler
{
	/**
	 * @return Provider|null
	 */
	public static function onGetContractorsProviderEventHandler(): ?Provider
	{
		return new Provider();
	}
}
