<?php

namespace Bitrix\Crm\Copilot\CallAssessment\Enum;

use Bitrix\Main\Localization\Loc;

enum ClientType: int
{
	case NEW = 1;
	case IN_WORK = 2;
	case REPEATED_APPROACH = 3;
	case RETURN_CUSTOMER = 4;

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
		if ($value === self::NEW->value)
		{
			return Loc::getMessage('CRM_COPILOT_CALL_ASSESSMENT_CLIENT_TYPE_NEW');
		}

		if ($value === self::IN_WORK->value)
		{
			return Loc::getMessage('CRM_COPILOT_CALL_ASSESSMENT_CLIENT_TYPE_IN_WORK');
		}

		if ($value === self::REPEATED_APPROACH->value)
		{
			return Loc::getMessage('CRM_COPILOT_CALL_ASSESSMENT_CLIENT_TYPE_REPEATED_APPROACH');
		}

		if ($value === self::RETURN_CUSTOMER->value)
		{
			return Loc::getMessage('CRM_COPILOT_CALL_ASSESSMENT_CLIENT_TYPE_RETURN_CUSTOMER');
		}

		return null;
	}

	public static function getTitleList(array $values): array
	{
		$titles = [];

		foreach ($values as $value)
		{
			$title = self::getTitle($value);
			if ($title === null)
			{
				continue;
			}

			$titles[] = $title;
		}

		return $titles;
	}

	public static function implodeTitles(array $values, string $separator = ', '): string
	{
		$titles = self::getTitleList($values);

		return implode($separator, $titles);
	}
}
