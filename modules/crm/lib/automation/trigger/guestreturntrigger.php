<?php
namespace Bitrix\Crm\Automation\Trigger;

Use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class GuestReturnTrigger extends BaseTrigger
{
	public static function getCode()
	{
		return 'GUEST_RETURN';
	}

	public static function getName()
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_GUEST_RETURN_NAME');
	}
}