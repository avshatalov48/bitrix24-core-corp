<?php

namespace Bitrix\Crm\Timeline;

use Bitrix\Crm\PhaseSemantics;

class TimelineMarkType
{
	public const UNDEFINED = 0;
	public const WAITING = 1;
	public const SUCCESS = 2;
	public const RENEW = 3;
	public const IGNORED = 4;
	public const FAILED = 5;

	public static function getMarkTypeByPhaseSemantics(string $phaseSemantics): int
	{
		if ($phaseSemantics === PhaseSemantics::SUCCESS)
		{
			return static::SUCCESS;
		}
		if (PhaseSemantics::isLost($phaseSemantics))
		{
			return static::FAILED;
		}

		return static::UNDEFINED;
	}
}
