<?php

namespace Bitrix\Intranet\Settings;

use Bitrix\Main\ArgumentException;
use Bitrix\Intranet\Settings;

class SettingsFactory
{
	private array $settingsList = [];

	public function register(string $type, SettingsPageProviderInterface $provider): void
	{
		$this->settingsList[$type] = $provider;
	}

	/**
	 * @throws ArgumentException
	 */
	public function build(string $type): SettingsInterface
	{
		if (!isset($this->settingsList[$type]) || !($this->settingsList[$type] instanceof SettingsPageProviderInterface))
		{
			throw new \Bitrix\Main\ArgumentException('This type of provider is not found', 'type');
		}

		return $this->settingsList[$type]->getDataManager();
	}

	/**
	 * @throws ArgumentException
	 * @return SettingsInterface[]
	 */
	public function buildAll(string $type): array
	{
		if (!isset($this->settingsList[$type]) || !($this->settingsList[$type] instanceof SettingsPageProviderInterface))
		{
			throw new \Bitrix\Main\ArgumentException('This type of provider is not found', 'type');
		}

		$response = [$this->settingsList[$type]->getDataManager()];

		foreach ($this->settingsList as $provider)
		{
			if ($provider instanceof Settings\SettingsSubPageProviderInterface && $provider->getParentType() === $type)
			{
				$response[] = $provider->getDataManager();
			}
		}

		return $response;
	}
}