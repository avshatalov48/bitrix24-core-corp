<?php

namespace Bitrix\Crm\Copilot\CallAssessment\Enum;

use Bitrix\Main\Localization\Loc;

enum CallType: int
{
	case ALL = 1;
	case INCOMING = 2;
	case OUTGOING = 3;

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

	public static function getTitle(int $value): ?string
	{
		if ($value === self::ALL->value)
		{
			return Loc::getMessage('CRM_COPILOT_CALL_ASSESSMENT_CALL_TYPE_ALL');
		}

		if ($value === self::INCOMING->value)
		{
			return Loc::getMessage('CRM_COPILOT_CALL_ASSESSMENT_CALL_TYPE_INCOMING');
		}

		if ($value === self::OUTGOING->value)
		{
			return Loc::getMessage('CRM_COPILOT_CALL_ASSESSMENT_CALL_TYPE_OUTGOING');
		}

		return null;
	}
}