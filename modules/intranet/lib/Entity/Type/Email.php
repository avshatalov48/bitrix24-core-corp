<?php

namespace Bitrix\Intranet\Entity\Type;

class Email
{
	public function __construct(
		private readonly string $email
	)
	{}

	public function toLogin(): string
	{
		return $this->email;
	}

	public function isValid(): bool
	{
		return check_email($this->email);
	}
}