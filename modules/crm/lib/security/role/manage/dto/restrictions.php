<?php

namespace Bitrix\Crm\Security\Role\Manage\DTO;

class Restrictions
{
	public function __construct(
		private bool $hasPermission,
		private ?string $restrictionScript,
		private ?string $sliderCode,
	)
	{
	}

	public function hasPermission(): bool
	{
		return $this->hasPermission;
	}

	public function restrictionScript(): ?string
	{
		return $this->restrictionScript;
	}

	public function sliderCode(): ?string
	{
		return $this->sliderCode;
	}
}
