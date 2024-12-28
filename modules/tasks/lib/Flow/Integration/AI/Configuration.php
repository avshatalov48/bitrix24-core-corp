<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Integration\AI;

use Bitrix\Main\Type\DateTime;

class Configuration
{
	public static function getCopilotTasksLimit(): int
	{
		return 1000;
	}

	public static function getCopilotStepLimit(): int
	{
		return 50;
	}

	public static function getCopilotDayPeriod(): int
	{
		return 30;
	}

	public static function getCopilotPeriod(): DateTime
	{
		$days = static::getCopilotDayPeriod();

		return (new DateTime())->add('-' . $days .  ' days');
	}

	public static function getMinFlowTasksCount(): int
	{
		return 10;
	}

	public static function getMinEfficiencyChangesByTasksCount(): array
	{
		return [
			static::getMinFlowTasksCount() => 30,
			30 => 10,
		];
	}

	public static function getMaxValueForLowEfficiency(): int
	{
		return 80;
	}

	public static function getPrecisionOfValues(): int
	{
		return 1;
	}

	public static function getUserPrefix(): string
	{
		return 'user_';
	}

	public static function getUserRegExp(): string
	{
		$userPrefix = static::getUserPrefix();

		return '/' . $userPrefix . '(\d+)/';
	}
}
