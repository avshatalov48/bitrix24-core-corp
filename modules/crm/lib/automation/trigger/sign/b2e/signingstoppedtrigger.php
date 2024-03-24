<?php

namespace Bitrix\Crm\Automation\Trigger\Sign\B2e;

use Bitrix\Main\Localization\Loc;

class SigningStoppedTrigger extends AbstractB2eDocumentTrigger
{
	public static function getCode(): string
	{
		return 'B2E_SIGNING_STOPPED';
	}

	public static function getName(): string
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_B2E_SIGNING_STOPPED_NAME') ?? '';
	}

	public static function getDescription(): string
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_B2E_SIGNING_STOPPED_DESCRIPTION') ?? '';
	}
}
