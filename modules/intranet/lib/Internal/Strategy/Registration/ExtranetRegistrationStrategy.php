<?php
declare(strict_types=1);

namespace Bitrix\Intranet\Internal\Strategy\Registration;

use Bitrix\Bitrix24\Integration\Network\ProfileService;
use Bitrix\Intranet\Contract\Repository\UserRepository;
use Bitrix\Intranet\Contract\Strategy\RegistrationStrategy;
use Bitrix\Intranet\CurrentUser;
use Bitrix\Intranet\Entity\User;
use Bitrix\Intranet\Integration\Socialnetwork\Group\MemberServiceFacade;
use Bitrix\Main\Loader;
use Bitrix\Main\Security\Random;

class ExtranetRegistrationStrategy implements RegistrationStrategy
{
	public function __construct(
		private readonly UserRepository $userRepository,
	)
	{
		if (!Loader::includeModule("extranet"))
		{
			throw new \Exception("Module extranet is not installed");
		}
	}

	/**
	 * @throws \Exception
	 */
	public static function createByDefault(): ExtranetRegistrationStrategy
	{
		return new self(new \Bitrix\Intranet\Repository\UserRepository());
	}

	public function register(User $user): User
	{
		$user->setActive(true);
		$user->setConfirmCode(Random::getString(8, true));
		$siteId = \CExtranet::GetExtranetSiteID();
		$extranetGroupID = \CExtranet::GetExtranetUserGroupID();
		$user->setGroupIds(
			(int)$extranetGroupID > 0 ? [$extranetGroupID] : []
		);
		$user->setLid($siteId);
		$user->setPassword(\CUser::GeneratePasswordByPolicy($user->getGroupIds() ?? []));
		$user->setLanguageId(($site = \CSite::GetArrayByID($siteId)) ? $site['LANGUAGE_ID'] : LANGUAGE_ID);

		return $this->userRepository->create($user);
	}
}