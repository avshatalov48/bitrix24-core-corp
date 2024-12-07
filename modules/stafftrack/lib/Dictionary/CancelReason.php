<?php

namespace Bitrix\StaffTrack\Dictionary;

use Bitrix\Main\Localization\Loc;

enum CancelReason: string
{
	case ILLNESS = 'STAFFTRACK_CANCEL_REASON_ILLNESS';
	case SICK_LEAVE = 'STAFFTRACK_CANCEL_REASON_SICK_LEAVE';
	case TIME_OFF = 'STAFFTRACK_CANCEL_REASON_TIME_OFF';
	case VACATION = 'STAFFTRACK_CANCEL_REASON_VACATION';
	case CUSTOM = 'STAFFTRACK_CANCEL_REASON_CUSTOM';

	public static function getName(string $name): string
	{
		return self::tryFrom($name)
			? Loc::getMessage($name)
			: $name
		;
	}

	public static function getList(): array
	{
		$result = [];
		foreach (self::cases() as $phrase)
		{
			$result[$phrase->value] = Loc::getMessage($phrase->value);
		}

		return $result;
	}
}