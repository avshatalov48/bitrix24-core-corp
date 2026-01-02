<?php

declare(strict_types=1);

namespace Bitrix\Disk\QuickAccess;

use Bitrix\Main\Config;

final class Configuration
{
	private const CONFIG_SECTION = 'main.token_service';
	private const CONFIG_STORAGE = 'storage';
	private const CONFIG_KEY = 'key';

	public function __construct()
	{
	}

	private function getTokenService(): ?array
	{
		$value = Config\Configuration::getValue(self::CONFIG_SECTION) ?: null;
//		if (empty($value))
//		{
//			throw new \LogicException(
//				sprintf('Value for key %s is not configured in .settings.php.', self::CONFIG_SECTION)
//			);
//		}

		return $value;
	}

	public function getKey(): ?string
	{
		return $this->getTokenService()[self::CONFIG_KEY] ?? null;
	}

	public function getTokenStorage(): array
	{
		return $this->getTokenService()[self::CONFIG_STORAGE] ?? [];
	}
}