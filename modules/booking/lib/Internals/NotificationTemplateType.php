<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals;

use Bitrix\Main\Localization\Loc;

enum NotificationTemplateType: string
{
	use DictionaryTrait;

	case Base = 'base';
	case Animate = 'animate';
	case Inanimate = 'inanimate';
	case InanimateLong = 'inanimate_long';

	public static function toArray(): array
	{
		$result = [];

		foreach (self::cases() as $case)
		{
			$result[$case->name] = [
				'name' => Loc::getMessage('BOOKING_NOTIFICATION_TEMPLATE_TYPE_' . mb_strtoupper($case->value)),
				'value' => $case->value,
			];
		}

		return $result;
	}
}
