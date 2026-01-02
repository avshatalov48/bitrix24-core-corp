<?php

namespace Bitrix\Baas\UseCase\External\Entity;

use Bitrix\Main;
use Bitrix\Bitrix24;

final class BusServer extends Server
{
	protected Main\License $license;

	public function __construct()
	{
		$this->license = new Main\License();
	}

	public function getId(): string
	{
		return 'bus';
	}

	public function isEnabled(): bool
	{
		return false;
	}

	public function getBillingCurrency(): ?string
	{
		return null;
	}

	protected function getLicense(): Bitrix24\License|Main\License
	{
		return $this->license;
	}
}
