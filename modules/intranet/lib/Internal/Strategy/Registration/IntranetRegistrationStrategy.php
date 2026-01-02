<?php
declare(strict_types=1);

namespace Bitrix\Intranet\Internal\Strategy\Registration;

use Bitrix\Intranet\Contract\Repository\UserRepository;
use Bitrix\Intranet\Contract\Strategy\RegistrationStrategy;
use Bitrix\Intranet\Entity\User;
use Bitrix\Main\Security\Random;

class IntranetRegistrationStrategy implements RegistrationStrategy
{
	public function __construct(
		private readonly UserRepository $userRepository,
	)
	{}

	public static function createByDefault(): IntranetRegistrationStrategy
	{
		return new self(new \Bitrix\Intranet\Repository\UserRepository());
	}

	public function register(User $user): User
	{
		$user->setActive(true);
		$user->setConfirmCode(Random::getString(8, true));
		$siteId = SITE_ID;
		$user->setGroupIds(
			\CIntranetInviteDialog::getUserGroups($siteId)
		);
		$user->setLid($siteId);
		$user->setPassword(\CUser::GeneratePasswordByPolicy($user->getGroupIds() ?? []));
		$user->setLanguageId(($site = \CSite::GetArrayByID($siteId)) ? $site['LANGUAGE_ID'] : LANGUAGE_ID);

		$user = $this->userRepository->create($user);

		return $user;
	}
}