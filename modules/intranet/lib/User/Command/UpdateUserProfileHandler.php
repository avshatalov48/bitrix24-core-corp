<?php

declare(strict_types=1);

namespace Bitrix\Intranet\User\Command;

use Bitrix\Intranet\Exception\UpdateFailedException;
use Bitrix\Intranet\Internal\Service\UserProfileService;
use Bitrix\Main\ArgumentException;

class UpdateUserProfileHandler
{
	public function __construct(
		private readonly UserProfileService $userProfileService,
	)
	{
	}

	/**
	 * @throws UpdateFailedException
	 * @throws ArgumentException
	 */
	public function __invoke(UpdateUserProfileCommand $command): void
	{
		$userProfile = $command->userProfile;

		$this->userProfileService->updateUserProfile($userProfile);
	}
}
