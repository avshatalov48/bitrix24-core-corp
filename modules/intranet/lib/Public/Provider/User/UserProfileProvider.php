<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Public\Provider\User;

use Bitrix\Intranet\Exception\WrongIdException;
use Bitrix\Intranet\Internal\Entity\User\Profile\Profile;
use Bitrix\Intranet\Internal\Repository\User\Profile\ProfileRepository;
use Bitrix\Main\ObjectNotFoundException;

class UserProfileProvider
{
	public function __construct(
		private ProfileRepository $userProfileRepository,
	) {}

	public static function createByDefault(): UserProfileProvider
	{
		return new UserProfileProvider(
			ProfileRepository::createByDefault(),
		);
	}

	/**
	 * @throws ObjectNotFoundException
	 * @throws WrongIdException
	 */
	public function getByUserId(int $userId): Profile
	{
		return $this->userProfileRepository->getById($userId);
	}
}
