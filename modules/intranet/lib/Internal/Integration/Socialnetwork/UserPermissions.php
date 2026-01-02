<?php

namespace Bitrix\Intranet\Internal\Integration\Socialnetwork;

use Bitrix\Intranet\Entity\User;
use Bitrix\Main\Loader;
use Bitrix\Socialnetwork\ComponentHelper;

class UserPermissions
{
	private bool $isAvailable;

	public function __construct()
	{
		$this->isAvailable = Loader::includeModule('socialnetwork');
	}

	public function canUserViewUserProfile(User $user, User $targetUser): bool
	{
		if (!$this->isAvailable)
		{
			return $user->isAdmin();
		}

		return \CSocNetUser::canProfileView(
			$user->getId(),
			$targetUser->getId(),
			SITE_ID,
			ComponentHelper::getUrlContext()
		);
	}

	public function canUserUpdateUserProfile(User $user, User $targetUser): bool
	{
		if (!$this->isAvailable)
		{
			return $user->isAdmin();
		}

		if (!$this->canUserViewUserProfile($user, $targetUser))
		{
			return false;
		}

		global $USER;

		$isSocNetAdmin = $user->isAdmin();

		if (
			!$isSocNetAdmin
			&& isset($USER)
			&& $USER instanceof \CUser
			&& $USER->getId() === $user->getId()
		)
		{
			$isSocNetAdmin = \CSocNetUser::isCurrentUserModuleAdmin(SITE_ID, false);
		}

		$userPerms = \CSocNetUserPerms::initUserPerms(
			$user->getId(),
			$targetUser->getId(),
			$isSocNetAdmin,
		);

		if (
			isset($userPerms['Operations']['modifyuser'], $userPerms['Operations']['modifyuser_main'])
			&& $userPerms['Operations']['modifyuser']
			&& $userPerms['Operations']['modifyuser_main']
		)
		{
			return true;
		}

		return $user->isAdmin();
	}
}
