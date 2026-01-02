<?php

declare(strict_types=1);

namespace Bitrix\Baas\Internal\Service;

use Bitrix\Baas;
use Bitrix\Baas\Internal;
use Bitrix\Baas\Internal\Integration;

class BaasService
{
	protected static BaasService $instance;

	protected function __construct(
		protected Integration\Main\License|Integration\Bitrix24\License $license
	)
	{
	}

	public function isAvailable(): bool
	{
		return $this->license->isBaasAvailable();
	}

	public function isActive(): bool
	{
		return $this->license->isActive();
	}

	public function getLicense(): Baas\Contract\License
	{
		return $this->license;
	}

	public static function getInstance(): static
	{
		if (!isset(static::$instance))
		{
			$cloudLicense = new Integration\Bitrix24\License(
				new Baas\Config\MarketplaceConfig()
			);

			if ($cloudLicense->isAvailable())
			{
				$license = $cloudLicense;
			}
			else
			{
				$license = new Integration\Main\License(
					new Baas\Config\Client()
				);
			}

			static::$instance = new static($license);
		}

		return static::$instance;
	}
}
