<?php

namespace Bitrix\Crm\Automation\Trigger;

use Bitrix\Main\Localization\Loc;

class OutgoingCallTrigger extends BaseTrigger
{
	public static function getCode()
	{
		return 'OUTGOING_CALL';
	}

	public static function getName()
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_OUTGOING_CALL_NAME');
	}
}
