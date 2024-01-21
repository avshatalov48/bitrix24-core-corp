<?php

namespace Bitrix\Tasks\Internals\Notification\UseCase\Regularity;

use Bitrix\Tasks\Internals\Notification\BufferInterface;
use Bitrix\Tasks\Internals\Notification\ProviderCollection;
use Bitrix\Tasks\Internals\Notification\User;
use Bitrix\Tasks\Internals\Notification\UserRepositoryInterface;
use Bitrix\Tasks\Internals\TaskObject;

trait RecipientsTrait
{
	public function __construct(
		protected TaskObject $task,
		protected BufferInterface $buffer,
		protected UserRepositoryInterface $userRepository,
		protected ProviderCollection $providers
	)
	{
	}

	protected function addUserToRecipients(array $recipients, User $user): array
	{
		foreach ($recipients as $recipient)
		{
			if ($recipient->getId() === $user->getId())
			{
				// already exists
				return $recipients;
			}
		}

		$recipients[] = $user;

		return $recipients;
	}
}