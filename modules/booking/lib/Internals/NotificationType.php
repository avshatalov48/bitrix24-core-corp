<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals;

use Bitrix\Main\Localization\Loc;

enum NotificationType: string
{
	use DictionaryTrait;

	case Info = 'info';
	case Confirmation = 'confirmation';
	case Reminder = 'reminder';
	case Delayed = 'delayed';
	case Feedback = 'feedback';

	public static function toArray(): array
	{
		$result = [];

		foreach (self::cases() as $case)
		{
			$result[$case->name] = [
				'name' => self::getName($case),
				'value' => $case->value,
			];
		}

		return $result;
	}

	public static function getName(NotificationType|string $notificationType): string|null
	{
		$notificationType =
			$notificationType instanceof NotificationType
				? $notificationType
				:  NotificationType::tryFrom($notificationType)
		;

		return Loc::getMessage('BOOKING_NOTIFICATION_TYPE_' . mb_strtoupper($notificationType->value));
	}
}
