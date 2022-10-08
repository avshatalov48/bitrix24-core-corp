<?php

namespace Bitrix\Crm\Format;

use Bitrix\Main\Localization\Loc;

class Duration
{
	public static function format(int $duration): string
	{
		if ($duration < 0)
		{
			$duration = 0;
		}

		$now = new \DateTime();
		$cloneNow = clone($now);
		$now->add(new \DateInterval('PT' . $duration . 'S'));
		$interval = $now->diff($cloneNow);
		[$hours, $minutes, $seconds] = explode(' ', $interval->format('%H %I %S'));
		$minutesStr = sprintf('%s %s', $minutes, Loc::getMessage('DURATION_MIN'));
		$secondsStr = sprintf('%s %s', $seconds, Loc::getMessage('DURATION_SEC'));

		if ($hours !== '00')
		{
			return sprintf(
				'%s %s %s %s',
				$hours,
				Loc::getMessage('DURATION_HOUR'),
				$minutesStr,
				$secondsStr
			);
		}

		if ($minutes !== '00')
		{
			return sprintf('%s %s', $minutesStr, $secondsStr);
		}

		return $secondsStr;
	}
}
