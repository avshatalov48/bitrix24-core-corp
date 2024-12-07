<?php

namespace Bitrix\StaffTrack\Dictionary;

use Bitrix\Main\Type\DateTime;
use Bitrix\StaffTrack\Model\Counter;

enum Mute: int
{
	case DISABLED = 0;
	case PERMANENT = 1;
	case TEMPORALLY = 2;

	/**
	 * @param Counter $counter
	 * @return bool
	 */
	public static function isMutedStatus(Counter $counter): bool
	{
		$todayDate = new DateTime();

		return $counter->getMuteStatus() === self::PERMANENT->value
			|| (
				$counter->getMuteStatus() === self::TEMPORALLY->value
				&& $counter->getMuteUntil()->getTimestamp() > $todayDate->getTimestamp()
			)
		;
	}
}