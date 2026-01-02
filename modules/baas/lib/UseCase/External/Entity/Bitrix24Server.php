<?php

namespace Bitrix\Baas\UseCase\External\Entity;

use Bitrix\Main;
use Bitrix\Bitrix24;

class Bitrix24Server extends Server
{
	private bool $enable = false;
	private Bitrix24\License $license;

	public function __construct()
	{
		if (Main\Loader::includeModule('bitrix24'))
		{
			$this->license = Bitrix24\License::getCurrent();
			$this->enable = true;
		}
	}

	public function getId(): string
	{
		return 'bitrix24';
	}

	public function isEnabled(): bool
	{
		return $this->enable;
	}

	public function getBillingCurrency(): ?string
	{
		$currency = \CBitrix24::BillingCurrency();

		return is_string($currency) ? $currency : null;
	}

	protected function getLicense(): Bitrix24\License|Main\License
	{
		return $this->license;
	}
}
