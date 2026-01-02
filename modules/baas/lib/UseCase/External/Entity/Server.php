<?php

namespace Bitrix\Baas\UseCase\External\Entity;

use Bitrix\Main;
use Bitrix\Baas;
use Bitrix\Bitrix24;

abstract class Server
{
	protected Baas\Config\Server $configs;

	abstract protected function getLicense(): Bitrix24\License|Main\License;

	public function getConfigs(): Baas\Config\Server
	{
		if (!isset($this->configs))
		{
			$this->configs = new Baas\Config\Server(
				$this->getLicense(),
			);
		}

		return $this->configs;
	}

	public function getUrl(): string
	{
		return $this->getConfigs()->getUrl();
	}

	public function getRegionId(): ?string
	{
		return $this->getLicense()->getRegion();
	}

	abstract public function getId(): string;

	abstract public function isEnabled(): bool;

	abstract public function getBillingCurrency(): ?string;
}
