<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Integration\AI;

use Bitrix\Main\Config\Option;
use CUserOptions;

final class FlowCopilotFeature
{
	public const FEATURE = 'feature_flow_copilot_enabled';
	public const ADVICE_AUTO_GENERATION_OPTION = 'feature_flow_copilot_auto_generation_enabled';

	public static function isOn(): bool
	{
		return
			(bool)(Option::get('tasks', self::FEATURE, true))
			&& FlowSettings::isFlowsTextAvailable()
			&& FlowSettings::isQueueMode()
		;
	}

	public static function isAdviceAutoGenerationOn(): bool
	{
		return
			(bool)(Option::get('tasks', self::ADVICE_AUTO_GENERATION_OPTION, true))
			&& self::isOn()
		;
	}

	public static function isOnForUser(): bool
	{
		return (bool)CUserOptions::getOption(self::FEATURE, 'enabled');
	}

	public static function turnOn(): void
	{
		Option::set('tasks', self::FEATURE, true);
	}

	public static function turnOff(): void
	{
		Option::delete('tasks', ['name' => self::FEATURE]);
	}

	public static function turnOnAdviceAutoGeneration(): void
	{
		Option::set('tasks', self::ADVICE_AUTO_GENERATION_OPTION, true);
	}

	public static function turnOffAdviceAutoGeneration(): void
	{
		Option::delete('tasks', ['name' => self::ADVICE_AUTO_GENERATION_OPTION]);
	}

	public static function turnOnForUsers(int ...$userIds): void
	{
		foreach ($userIds as $userId)
		{
			CUserOptions::SetOption(self::FEATURE, 'enabled', true, false, $userId);
		}
	}

	public static function turnOffForUsers(int ...$userIds): void
	{
		foreach ($userIds as $userId)
		{
			CUserOptions::DeleteOption(self::FEATURE, 'enabled', false, $userId);
		}
	}
}
