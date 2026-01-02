<?php

declare(strict_types=1);

namespace Bitrix\Intranet\User\Command;

use Bitrix\Intranet\Entity\User;
use Bitrix\Intranet\Exception\UpdateFailedException;
use Bitrix\Intranet\Exception\WrongIdException;
use Bitrix\Intranet\Service\ServiceContainer;
use Bitrix\Intranet\User\Access\UserActionDictionary;
use Bitrix\Main\Command\AbstractCommand;
use Bitrix\Main\Error;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Main\Result;
use Bitrix\Main\Validation\ValidationResult;

/**
 * Delete user if invited
 * Delete user if awaiting
 * Fire user if active
 * Fire user if we can't delete
 */
class DeleteOrFireUserCommand extends AbstractCommand
{
	public function __construct(
		public readonly User $user,
	)
	{
	}

	protected function execute(): Result
	{
		$result = new Result();

		$userService = ServiceContainer::getInstance()->getUserService();
		$isActionAvailable = $userService->isActionAvailableForUser($this->user, UserActionDictionary::FIRE)
			|| $userService->isActionAvailableForUser($this->user, UserActionDictionary::DELETE);

		if (!$isActionAvailable)
		{
			return $result->addError(new Error('User already fired'));
		}

		try
		{
			$userRepository = ServiceContainer::getInstance()->userRepository();
			$userService = ServiceContainer::getInstance()->getUserService();
			$handler = new DeleteOrFireUserHandler($userRepository, $userService);
			$handler($this);

			return $result;
		}
		catch (UpdateFailedException)
		{
			return $result->addError(new Error('Activity update failed'));
		}
		catch (WrongIdException)
		{
			return $result->addError(new Error('Wrong user id'));
		}
		catch (ObjectNotFoundException $e)
		{
			return $result->addError(new Error($e->getMessage()));
		}
	}

	public function toArray(): array
	{
		return [
			'user' => $this->user->toArray()
		];
	}
}
