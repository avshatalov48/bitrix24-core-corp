<?php

declare(strict_types=1);

namespace Bitrix\Intranet\User\Command;

use Bitrix\Intranet\Entity\User;
use Bitrix\Intranet\Exception\UpdateFailedException;
use Bitrix\Intranet\Service\ServiceContainer;
use Bitrix\Intranet\User\Access\UserActionDictionary;
use Bitrix\Main\Command\AbstractCommand;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

class FireUserCommand extends AbstractCommand
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
			->isActionAvailableForUser($this->user, UserActionDictionary::FIRE);

		if (!$isActionAvailable)
		{
			return (new Result())->addError(new Error('User already fired'));
		}

		return null;
	}

	protected function execute(): Result
	{
		$result = new Result();

		try
		{
			$userRepository = ServiceContainer::getInstance()->userRepository();
			$userService = ServiceContainer::getInstance()->getUserService();
			$handler = new FireUserHandler($userRepository, $userService);
			$handler($this);

			return $result;
		}
		catch (UpdateFailedException)
		{
			return $result->addError(new Error('Activity update failed'));
		}
	}

	public function toArray(): array
	{
		return [
			'user' => $this->user->toArray()
		];
	}
}
