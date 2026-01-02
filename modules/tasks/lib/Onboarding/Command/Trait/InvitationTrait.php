<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\Command\Trait;

use CUser;

trait InvitationTrait
{
	protected function isInvitedUser(int $userId): bool
	{
		$user = CUser::GetByID($userId)->Fetch();
		if (!$user)
		{
			return false;
		}

		$lastLogin = $user['LAST_LOGIN'] ?? null;

		return empty($lastLogin);
	}
}