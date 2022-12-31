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
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_OUTGOING_CALL_NAME_1');
	}

	public static function getDescription(): string
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_OUTGOING_CALL_DESCRIPTION') ?? '';
	}

	public static function getGroup(): array
	{
		return ['clientCommunication'];
	}
}
