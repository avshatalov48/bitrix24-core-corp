<?php

namespace Bitrix\Intranet\Entity\Type;

class EmailInvitation
{
	public function __construct(
		private string $email,
		private ?string $name,
		private ?string $lastName)
	{}

	public function toArray(): array
	{
		return [
			'EMAIL' => $this->email,
			'NAME' => $this->name,
			'LAST_NAME' => $this->lastName,
		];
	}
}