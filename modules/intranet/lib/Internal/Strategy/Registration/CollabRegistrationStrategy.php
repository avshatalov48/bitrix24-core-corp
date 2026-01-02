<?php
declare(strict_types=1);

namespace Bitrix\Intranet\Internal\Strategy\Registration;

use Bitrix\Bitrix24\Integration\Network\ProfileService;
use Bitrix\Intranet\Contract\Repository\UserRepository;
use Bitrix\Intranet\Contract\Strategy\RegistrationStrategy;
use Bitrix\Intranet\CurrentUser;
use Bitrix\Intranet\Entity\User;
use Bitrix\Intranet\Integration\Socialnetwork\Group\MemberServiceFacade;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Security\Random;
use Bitrix\Main\SystemException;
use Bitrix\Socialnetwork\Collab\Collab;

class CollabRegistrationStrategy implements RegistrationStrategy
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
	 * @throws LoaderException
	 */
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

		if (is_null($user->getLanguageId()))
		{
			$user->setLanguageId(($site = \CSite::GetArrayByID($siteId)) ? $site['LANGUAGE_ID'] : LANGUAGE_ID);
		}

		$user = $this->userRepository->create($user);

		// only new users
		if (Loader::includeModule("bitrix24"))
		{
			ProfileService::getInstance()->markUserAsCollaber($user->getId());
		}

		return $user;
	}
}