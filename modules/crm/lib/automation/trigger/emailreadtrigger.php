<?php

namespace Bitrix\Crm\Automation\Trigger;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class EmailReadTrigger extends BaseTrigger
{
	public static function getCode()
	{
		return 'EMAIL_READ';
	}

	public static function getName()
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_EMAIL_READ_NAME_1');
	}

	public static function getDescription(): string
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_EMAIL_READ_DESCRIPTION') ?? '';
	}

	public static function getGroup(): array
	{
		return ['clientCommunication'];
	}
}