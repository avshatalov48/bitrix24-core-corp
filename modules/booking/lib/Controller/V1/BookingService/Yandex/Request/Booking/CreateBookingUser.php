<?php

declare(strict_types=1);

namespace Bitrix\Booking\Controller\V1\BookingService\Yandex\Request\Booking;

class CreateBookingUser
{
	private string $name;
	private string $phone;
	private string|null $email;

	public function __construct(string $name, string $phone, string|null $email)
	{
		$this->name = $name;
		$this->phone = $phone;
		$this->email = $email;
	}

	public static function mapFromArray(array $request): self
	{
		return new self(
			$request['name'],
			$request['phone'],
			$request['email'] ?? null,
		);
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getPhone(): string
	{
		return $this->phone;
	}

	public function getEmail(): string|null
	{
		return $this->email;
	}
}
