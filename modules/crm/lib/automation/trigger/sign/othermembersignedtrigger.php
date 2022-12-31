<?php
namespace Bitrix\Crm\Automation\Trigger\Sign;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class OtherMemberSignedTrigger extends InitiatorSignedTrigger
{
	public static function getCode()
	{
		return 'SIGN_OTHER_SIGNING';
	}

	public static function getName()
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_SIGN_OTHER_MEMBER_SIGNING_NAME_2');
	}

	public static function getGroup(): array
	{
		return ['paperwork'];
	}

	public static function getDescription(): string
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_SIGN_OTHER_MEMBER_SIGNING_DESCRIPTION') ?? '';
	}
}