<?php
namespace Bitrix\Crm\Automation\Trigger;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class MissedCallTrigger extends BaseTrigger
{
	public static function isEnabled()
	{
		return false; //TODO: to do a realization in voximplant
	}

	public static function getCode()
	{
		return 'MISSED_CALL';
	}

	public static function getName()
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_MISSED_CALL_NAME');
	}
}