<?php
namespace Bitrix\Crm\Activity;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class StatisticsMark
{
	const None = 0;
	const Negative = 1;
	const Positive = 2;

	public static function getDescriptions()
	{
		return array(
			static::None => Loc::getMessage('CRM_ACTIVITY_STAT_MARK_NONE'),
			static::Negative => Loc::getMessage('CRM_ACTIVITY_STAT_MARK_NEGATIVE'),
			static::Positive => Loc::getMessage('CRM_ACTIVITY_STAT_MARK_POSITIVE')
		);
	}
}