<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Entity\Type;

class EmailInvitation extends BaseInvitation
{
	public function __construct(
		private readonly string $email,
		private readonly ?string $name,
		private readonly ?string $lastName
	)
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
