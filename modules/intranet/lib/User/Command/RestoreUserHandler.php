<?php

declare(strict_types=1);

namespace Bitrix\Intranet\User\Command;

use Bitrix\Intranet\Exception\UpdateFailedException;
use Bitrix\Intranet\Repository\UserRepository;

class RestoreUserHandler
{
	public function __construct(
		private readonly UserRepository $userRepository
	)
	{
	}

	/**
	 * @throws UpdateFailedException
	 */
	public function __invoke(RestoreUserCommand $command): void
	{
		$user = $command->user;
		$user->setActive(true);
		$this->userRepository->update($user);
	}
}
