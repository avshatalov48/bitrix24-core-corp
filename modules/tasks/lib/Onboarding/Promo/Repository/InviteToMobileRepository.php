<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\Promo\Repository;

use CUserOptions;

class InviteToMobileRepository implements InviteToMobileRepositoryInterface
{
	public function needToShow(int $userId): bool
	{
		$needToShow = CUserOptions::GetOption(
			'tasks.onboarding',
			'need_show_invite_to_mobile',
			false,
			$userId
		);

		return (bool)$needToShow;
	}

	public function setNeedToShow(int $userId): void
	{
		CUserOptions::SetOption(
			'tasks.onboarding',
			'need_show_invite_to_mobile',
			true,
			false,
			$userId
		);
	}

	public function setShown(int $userId): void
	{
		CUserOptions::DeleteOption(
			'tasks.onboarding',
			'need_show_invite_to_mobile',
			false,
			$userId
		);
	}
}
