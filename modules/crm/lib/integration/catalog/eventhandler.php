<?php

namespace Bitrix\Crm\Integration\Catalog;

use Bitrix\Main\Loader;
use Bitrix\Crm\Integration\Catalog\Contractor\StoreDocumentProvider;
use Bitrix\Crm\Integration\Catalog\Contractor\AgentContractProvider;
use Bitrix\Crm\Integration\Catalog\Contractor\Converter;
use Bitrix\Catalog\v2\Contractor\Provider;

/**
 * Class EventHandler
 *
 * @package Bitrix\Crm\Integration\Catalog
 */
class EventHandler
{
	/**
	 * @return \Bitrix\Crm\Integration\Catalog\Contractor\Provider[]
	 */
	public static function onGetContractorsProviderEventHandler(): array
	{
		if (!Loader::includeModule('catalog'))
		{
			return [];
		}

		return [
			Provider\Manager::PROVIDER_STORE_DOCUMENT => new StoreDocumentProvider(),
			Provider\Manager::PROVIDER_AGENT_CONTRACT => new AgentContractProvider(),
		];
	}

	public static function onGetContractorsConverterEventHandler(): Converter
	{
		return new Converter();
	}
}
