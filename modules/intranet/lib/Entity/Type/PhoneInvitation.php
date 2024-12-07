<?php

namespace Bitrix\Intranet\Entity\Type;

class PhoneInvitation
{
	public function __construct(
		private string $phone,
		private ?string $name = null,
		private ?string $lastName = null,
		private ?string $phoneCountry = null
	)
	{
	}

	public function toArray(): array
	{
		return [
			'PHONE' => $this->phone,
			'NAME' => $this->name,
			'LAST_NAME' => $this->lastName,
			'PHONE_COUNTRY' => $this->phoneCountry,
		];
	}
}