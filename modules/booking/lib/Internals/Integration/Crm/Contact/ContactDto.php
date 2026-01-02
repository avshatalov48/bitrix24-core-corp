<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Integration\Crm\Contact;

class ContactDto
{
	private string $name;
	private string|null $phone = null;
	private string|null $email = null;

	public function __construct(string $name)
	{
		$this->name = $name;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getPhone(): string|null
	{
		return $this->phone;
	}

	public function setPhone(string|null $phone): self
	{
		$this->phone = $phone;

		return $this;
	}

	public function getEmail(): string|null
	{
		return $this->email;
	}

	public function setEmail(string|null $email): self
	{
		$this->email = $email;

		return $this;
	}
}
