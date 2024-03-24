<?php

namespace Bitrix\Crm\Automation\Trigger\Sign\B2e;

use Bitrix\Main\Localization\Loc;

class SigningStartedTrigger extends AbstractB2eDocumentTrigger
{
	public static function getCode(): string
	{
		return 'B2E_SIGNING_STARTED';
	}

	public static function getName(): string
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_B2E_SIGNING_STARTED_NAME') ?? '';
	}

	public static function getDescription(): string
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_B2E_SIGNING_STARTED_DESCRIPTION') ?? '';
	}

}
