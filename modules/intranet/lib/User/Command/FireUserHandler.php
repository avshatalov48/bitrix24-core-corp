<?php

declare(strict_types=1);

namespace Bitrix\Intranet\User\Command;

use Bitrix\Intranet\Exception\UpdateFailedException;
use Bitrix\Intranet\Repository\UserRepository;
use Bitrix\Intranet\Service\UserService;

class FireUserHandler
{
	public function __construct(
		private readonly UserRepository $userRepository,
		private readonly UserService $userService,
	)
	{
	}

	/**
	 * @throws UpdateFailedException
	 */
	public function __invoke(FireUserCommand $command): void
	{
		$user = $command->user;
		$user->setActive(false);
		$this->userRepository->update($user);
		$this->userService->clearCache();
	}
}
