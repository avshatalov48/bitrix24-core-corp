<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\Journal\EventProcessor\PushPull;

enum PushPullCommandType: string implements \JsonSerializable
{
	case BookingBoardUpdated = 'bookingBoardUpdated';
	case BookingAdded = 'bookingAdded';
	case BookingUpdated = 'bookingUpdated';
	case BookingClientUpdated = 'bookingClientUpdated';
	case BookingDeleted = 'bookingDeleted';
	case BookingConfirmed = 'bookingConfirmed';
	case BookingConfirmReminderSentToManager = 'bookingConfirmReminderToManager';

	case ResourceAdded = 'resourceAdded';
	case ResourceUpdated = 'resourceUpdated';
	case ResourceDeleted = 'resourceDeleted';

	case ResourceTypeAdded = 'resourceTypeAdded';
	case ResourceTypeUpdated = 'resourceTypeUpdated';
	case ResourceTypeDeleted = 'resourceTypeDeleted';

	case CountersUpdated = 'countersUpdated';

	public function jsonSerialize(): array
	{
		return [
			'name' => $this->name,
			'value' => $this->value,
		];
	}

	public static function toArray(): array
	{
		$result = [];

		foreach (self::cases() as $case)
		{
			$result[$case->name] = $case->value;
		}

		return $result;
	}

	public function getTag(): string
	{
		return match ($this)
		{
			self::ResourceAdded,
			self::ResourceUpdated,
			self::ResourceDeleted => 'resource',
			self::ResourceTypeAdded,
			self::ResourceTypeUpdated,
			self::ResourceTypeDeleted => 'resourceType',
			default => 'bookingBoard',
		};
	}
}
