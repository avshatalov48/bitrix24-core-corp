<?php
namespace Bitrix\Crm\Automation\Trigger;

Use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class EmailSentTrigger extends BaseTrigger
{
	public static function getCode()
	{
		return 'EMAIL_SENT';
	}

	public static function getName()
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_EMAIL_SENT_NAME');
	}
}