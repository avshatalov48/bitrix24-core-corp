<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\Internal\Factory;

use Bitrix\Tasks\Onboarding\Internal\Type;
use Bitrix\Tasks\Onboarding\Transfer\UserJob;

final class UserJobFactory
{
	/**
	 * @param Type[] $types
	 */
	public static function createUserJob(array $types, int $userId, int $taskId = 0): UserJob
	{
		return new UserJob($types, $userId, $taskId);
	}
}