<?php

namespace Bitrix\Intranet\Public\Provider\User;

use Bitrix\Intranet\Internal\Entity\User\Field\FieldCollection;
use Bitrix\Intranet\Internal\Repository\User\Profile\ProfileRepository;

class UserFieldsProvider
{
	public function __construct(
		private ProfileRepository $userProfileRepository,
	) {}

	public static function createByDefault(): UserFieldsProvider
	{
		return new UserFieldsProvider(
			ProfileRepository::createByDefault(),
		);
	}

	public function getByUserData(array $userData): FieldCollection
	{
		return $this->userProfileRepository->getUserFieldsByUserData($userData);
	}
}
