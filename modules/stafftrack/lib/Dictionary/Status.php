<?php

namespace Bitrix\StaffTrack\Dictionary;

enum Status: int
{
	case WORKING = 1;
	case NOT_WORKING = 2;
	case CANCEL_WORKING = 3;

	/**
	 * @param ?int $status
	 * @return bool
	 */
	public static function isWorkingStatus(?int $status): bool
	{
		return $status === self::WORKING->value;
	}

	/**
	 * @param ?int $status
	 * @return bool
	 */
	public static function isNotWorkingStatus(?int $status): bool
	{
		return $status === self::NOT_WORKING->value
			|| $status === self::CANCEL_WORKING->value
		;
	}
}