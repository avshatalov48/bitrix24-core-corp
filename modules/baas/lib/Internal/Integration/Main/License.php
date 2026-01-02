<?php

declare(strict_types=1);

namespace Bitrix\Baas\Internal\Integration\Main;

use Bitrix\Main;
use Bitrix\Baas;

class License implements Baas\Contract\License
{
	protected Baas\Config\Client $config;
	protected ?Main\License $license = null;
	protected bool $active = false;

	public function __construct(Baas\Config\Client $config)
	{
		$this->config = $config;

		$this->license = new Main\License();
		$this->active = !$this->license->isTimeBound() || $this->license->getExpireDate() >= new Main\Type\Date();
	}

	public function isAvailable(): bool
	{
		return true;
	}

	public function isActive(): bool
	{
		return $this->active;
	}

	public function isBaasAvailable(): bool
	{
		return $this->config->isRegistered() && $this->config->isTurnedOn();
	}

	public function isSellableToAll(): bool
	{
		return true;
	}
}
