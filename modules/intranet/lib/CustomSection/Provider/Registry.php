<?php

namespace Bitrix\Intranet\CustomSection\Provider;

use Bitrix\Intranet\CustomSection\Provider;
use Bitrix\Main\Config\Configuration;
use Bitrix\Main\Loader;

/**
 * @internal Not covered by backwards compatibility
 */
class Registry
{
	private array $providers = [];

	public function getProvider(string $moduleId): ?Provider
	{
		if (isset($this->providers[$moduleId]))
		{
			return $this->providers[$moduleId];
		}

		if (!Loader::includeModule($moduleId))
		{
			return null;
		}

		$providerClass = $this->getProviderClass($moduleId);
		if (!$providerClass)
		{
			return null;
		}

		$provider = new $providerClass();
		$this->providers[$moduleId] = $provider;

		return $provider;
	}

	private function getProviderClass(string $moduleId): ?string
	{
		$config = Configuration::getInstance($moduleId)->get('intranet.customSection');
		if (empty($config))
		{
			return null;
		}

		$providerClass = $config['provider'] ?? null;
		if (empty($providerClass) || !is_a($providerClass, Provider::class, true))
		{
			return null;
		}

		return $providerClass;
	}
}
