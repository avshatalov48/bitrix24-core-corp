<?php

namespace Bitrix\Crm\Activity;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class StatisticsMark
{
	public const None = 0;
	public const Negative = 1;
	public const Positive = 2;
	public const Neutral = 3;

	public static function getDescriptions()
	{
		return [
			static::None => Loc::getMessage('CRM_ACTIVITY_STAT_MARK_NONE'),
			static::Negative => Loc::getMessage('CRM_ACTIVITY_STAT_MARK_NEGATIVE'),
			static::Positive => Loc::getMessage('CRM_ACTIVITY_STAT_MARK_POSITIVE'),
			static::Neutral => Loc::getMessage('CRM_ACTIVITY_STAT_MARK_NEUTRAL'),
		];
	}
}
