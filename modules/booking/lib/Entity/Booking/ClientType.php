<?php

declare(strict_types=1);

namespace Bitrix\Booking\Entity\Booking;

class ClientType
{
	private string|null $moduleId = null;
	private string|null $code = null;

	public function getModuleId(): string|null
	{
		return $this->moduleId;
	}

	public function setModuleId(string|null $module): self
	{
		$this->moduleId = $module;

		return $this;
	}

	public function getCode(): string|null
	{
		return $this->code;
	}

	public function setCode(string|null $code): self
	{
		$this->code = $code;

		return $this;
	}

	public function toArray(): array
	{
		return [
			'module' => $this->getModuleId(),
			'code' => $this->getCode(),
		];
	}

	public static function mapFromArray(array $props): self
	{
		return (new self())
			->setModuleId(isset($props['module']) ? (string)$props['module'] : null)
			->setCode(isset($props['code']) ? (string)$props['code'] : null)
		;
	}
}
