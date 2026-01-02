<?php

declare(strict_types=1);

namespace Bitrix\Intranet\User\Command;

use Bitrix\Intranet\Exception\DeleteFailedException;
use Bitrix\Intranet\Exception\WrongIdException;
use Bitrix\Intranet\Repository\UserRepository;
use Bitrix\Main\ObjectNotFoundException;

class DeleteUserHandler
{
	public function __construct(
		private readonly UserRepository $userRepository
	)
	{
	}

	/**
	 * @param DeleteUserCommand $command
	 * @throws DeleteFailedException
	 * @throws ObjectNotFoundException
	 * @throws WrongIdException
	 */
	public function __invoke(DeleteUserCommand $command): void
	{
		$user = $command->user;
		$this->userRepository->delete($user);
	}
}