<?php

namespace Bitrix\Crm\Copilot\CallAssessment\Enum;

enum AvailabilityType: string
{
	case ALWAYS_ACTIVE = 'always_active';
	case INACTIVE = 'inactive';
	case PERIOD = 'period';
	case CUSTOM = 'custom';

	public static function values(): array
	{
		return array_map(static fn ($case) => $case->value, self::cases());
	}

	public static function isExtendedAvailabilityType(string $input): bool
	{
		return self::tryFrom($input) !== null
			&& in_array($input, [self::PERIOD->value, self::CUSTOM->value], true)
		;
	}
}
