<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\Notifications;

use Bitrix\Booking\Internals\Service\DictionaryTrait;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages($_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/modules/booking/lib/Internals/NotificationType.php');

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

	public static function getName(NotificationType|string $notificationType): string
	{
		$notificationType =
			$notificationType instanceof NotificationType
				? $notificationType
				:  NotificationType::tryFrom($notificationType)
		;

		$name = Loc::getMessage('BOOKING_NOTIFICATION_TYPE_' . mb_strtoupper($notificationType->value));

		return $name ?? '';
	}
}
