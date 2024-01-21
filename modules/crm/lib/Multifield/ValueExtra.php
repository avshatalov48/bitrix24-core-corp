<?php

namespace Bitrix\Crm\Multifield;

use Bitrix\Main\Type\Contract\Arrayable;

class ValueExtra implements Arrayable, \JsonSerializable
{
	private ?string $countryCode;

	public function getCountryCode(): ?string
	{
		return $this->countryCode;
	}

	public function setCountryCode(?string $countryCode): self
	{
		$this->countryCode = $countryCode;

		return $this;
	}

	public function isEqualTo(self $anotherValueExtra): bool
	{
		return (
			$this->countryCode === $anotherValueExtra->countryCode
		);
	}

	public function toArray(): array
	{
		return [
			'VALUE_COUNTRY_CODE' => $this->getCountryCode(),
		];
	}

	final public function jsonSerialize()
	{
		return [
			'countryCode' => $this->getCountryCode(),
		];
	}
}
