<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\Internal\Factory;

use Bitrix\Tasks\Onboarding\Internal\Type;
use Bitrix\Tasks\Onboarding\Transfer\CommandModel;

final class CommandModelFactory
{
	public static function create(Type $type, int $taskId, int $userId, bool $isCountable = false): CommandModel
	{
		return new CommandModel($type, $taskId, $userId, $isCountable);
	}
}