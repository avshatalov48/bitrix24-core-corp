<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service;

enum CounterDictionary: string implements \JsonSerializable
{
	use DictionaryTrait;

	case LeftMenu = 'booking_total';
	case Total = 'total';
	case BookingUnConfirmed = 'booking_unconfirmed';
	case BookingDelayed = 'booking_delayed';

	public function jsonSerialize(): array
	{
		return [
			'name' => $this->name,
			'value' => $this->value,
		];
	}

	public static function isExists(string $value): bool
	{
		foreach (self::cases() as $case)
		{
			if ($case->value === $value)
			{
				return true;
			}
		}

		return false;
	}
}
