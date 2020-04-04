<?php
namespace Bitrix\Crm\Activity;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class StatisticsStream
{
	const Undefined = 0;
	const Incoming = 1;
	const Outgoing = 2;
	const Reversing = 3; //Example: callbacks
	const Missing = 4; //Example: missed calls
	
	public static function getDescriptions()
	{
		return array(
			static::Undefined => Loc::getMessage('CRM_ACTIVITY_STAT_STREAM_UNDEFINED'),
			static::Incoming => Loc::getMessage('CRM_ACTIVITY_STAT_STREAM_INCOMING'),
			static::Outgoing => Loc::getMessage('CRM_ACTIVITY_STAT_STREAM_OUTGOING'),
			static::Reversing => Loc::getMessage('CRM_ACTIVITY_STAT_STREAM_REVERSING'),
			static::Missing => Loc::getMessage('CRM_ACTIVITY_STAT_STREAM_MISSING')
		);
	}
}