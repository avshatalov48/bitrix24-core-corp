<?php

namespace Bitrix\Crm\Copilot\CallAssessment\Enum;

use Bitrix\Main\Localization\Loc;

// Left for the second iteration of the call rules evaluation wizard.
// It is expected that there will be an option to select the type of user controlling
// the evaluation of the manager's call

enum InspectorType: string
{
	case USER = 'user';
	case DIVISION_HEAD = 'division_head';

	public static function fromName(string $name): string
	{
		foreach (self::cases() as $status)
		{
			if ($name === $status->name)
			{
				return $status->value;
			}
		}

		throw new \ValueError("$name is not a valid backing value for enum " . self::class);
	}

	public static function getTitle(string $value): ?string
	{
		if ($value === self::USER->value)
		{
			return Loc::getMessage('CRM_COPILOT_CALL_ASSESSMENT_INSPECTOR_TYPE_USER');
		}

		if ($value === self::DIVISION_HEAD->value)
		{
			return Loc::getMessage('CRM_COPILOT_CALL_ASSESSMENT_CLIENT_TYPE_DIVISION_HEAD');
		}

		return null;
	}
}