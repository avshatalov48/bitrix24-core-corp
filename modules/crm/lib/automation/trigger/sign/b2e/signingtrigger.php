<?php

namespace Bitrix\Crm\Automation\Trigger\Sign\B2e;

use Bitrix\Main\Localization\Loc;

final class SigningTrigger extends AbstractB2eDocumentTrigger
{
	public static function getCode(): string
	{
		return 'B2E_SIGNING';
	}

	public static function getName(): string
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_B2E_SIGNING_NAME') ?? '';
	}

	public static function getDescription(): string
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_B2E_SIGNING_DESCRIPTION') ?? '';
	}
}
