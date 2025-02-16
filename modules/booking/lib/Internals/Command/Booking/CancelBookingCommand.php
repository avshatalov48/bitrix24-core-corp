<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Command\Booking;

use Bitrix\Booking\Internals\Command\CommandInterface;

class CancelBookingCommand implements CommandInterface
{
	public function __construct(
		public readonly string $hash,
	)
	{

	}

	public function toArray(): array
	{
		return [
			'hash' => $this->hash,
		];
	}

	public static function mapFromArray(array $props): self
	{
		return new self(
			hash: $props['hash'],
		);
	}
}
