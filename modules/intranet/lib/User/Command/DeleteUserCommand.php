<?php

declare(strict_types=1);

namespace Bitrix\Intranet\User\Command;

use Bitrix\Intranet\Entity\User;
use Bitrix\Intranet\Exception\DeleteFailedException;
use Bitrix\Intranet\Exception\WrongIdException;
use Bitrix\Intranet\Service\ServiceContainer;
use Bitrix\Intranet\User\Access\UserActionDictionary;
use Bitrix\Main\Command\AbstractCommand;
use Bitrix\Main\Error;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Main\Result;

class DeleteUserCommand extends AbstractCommand
{
	public function __construct(
		public readonly User $user,
	)
	{
	}

	protected function beforeRun(): ?Result
	{
		$isActionAvailable = ServiceContainer::getInstance()
			->getUserService()
			->isActionAvailableForUser($this->user, UserActionDictionary::DELETE);

		if (!$isActionAvailable)
		{
			return (new Result())->addError(new Error('You can only delete invited users who have never logged into the portal'));
		}

		return null;
	}

	protected function execute(): Result
	{
		$result = new Result();

		try
		{
			$userRepository = ServiceContainer::getInstance()->userRepository();
			$handler = new DeleteUserHandler($userRepository);
			$handler($this);

			return $result;
		}
		catch (DeleteFailedException)
		{
			return $result->addError(new Error('Delete failed'));
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
