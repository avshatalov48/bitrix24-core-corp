<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Entity\Type;

class PhoneInvitation extends BaseInvitation
{
	public function __construct(
		private readonly string $phone,
		private readonly ?string $name = null,
		private readonly ?string $lastName = null,
		private readonly ?string $phoneCountry = null
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
