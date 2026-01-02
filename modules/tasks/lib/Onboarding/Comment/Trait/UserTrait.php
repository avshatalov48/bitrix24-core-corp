<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\Comment\Trait;

use Bitrix\Tasks\Util\User;

trait UserTrait
{
	private function getBBCode(int $userId): string
	{
		$name = User::getUserName([$userId])[$userId] ?? '';

		return "[USER={$userId}]{$name}[/USER]";
	}
}