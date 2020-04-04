<?php
namespace Bitrix\Crm\Activity;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class StatisticsStatus
{
	const Undefined = 0;
	const Unanswered = 1;
	const Answered = 2;

	public static function getDescriptions()
	{
		return array(
			static::Undefined => Loc::getMessage('CRM_ACTIVITY_STAT_STATUS_UNDEFINED'),
			static::Unanswered => Loc::getMessage('CRM_ACTIVITY_STAT_STATUS_UNANSWERED'),
			static::Answered => Loc::getMessage('CRM_ACTIVITY_STAT_STATUS_ANSWERED')
		);
	}
}