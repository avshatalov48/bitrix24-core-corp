<?php

declare(strict_types=1);

namespace Bitrix\Intranet\User\Command;

use Bitrix\Intranet\Exception\UpdateFailedException;
use Bitrix\Intranet\Internal\Repository\User\Profile\ProfileRepository;

class UpdateUserFieldsHandler
{
	public function __construct(
		private readonly ProfileRepository $profileRepository,
	)
	{
	}

	/**
	 * @throws UpdateFailedException
	 */
	public function __invoke(UpdateUserFieldsCommand $command): void
	{
		$userFields = $command->userFieldCollection;
		$userId = $command->userId;

		$this->profileRepository->saveUserProfileFields($userId, $userFields);
	}
}
