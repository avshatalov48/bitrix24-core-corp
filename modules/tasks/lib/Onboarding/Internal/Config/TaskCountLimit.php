<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\Internal\Config;

use Bitrix\Tasks\Onboarding\Internal\Type;

final class TaskCountLimit
{
	public static function get(Type $type): ?int
	{
		return match ($type)
		{
			Type::TooManyTasks => 10,
			Type::InviteToMobile => 3,
			default => null,
		};
	}
}